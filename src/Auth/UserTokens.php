<?php
namespace TeaPress\Auth;

use WP_User;
use Exception;
use WP_Session_Tokens;
use WP_User_Meta_Session_Tokens;
use TeaPress\Http\Request;
use Illuminate\Support\Traits\Macroable;

class UserTokens extends WP_User_Meta_Session_Tokens {

	use Macroable;

	protected static $_session_hook_prefix = 'build_user_session_token_';

	protected static $user_model = WP_User::class;

	protected $user;

	protected static $current_session = null;

	protected static $cookie_validated = false;

	protected static $cookie_validation_locked = false;

	protected static $service_container_resolver = 'app';

	protected static $plugins_have_loaded = false;

	protected static $cookie_validation_hooks = [
				'auth_cookie_valid',
				'auth_cookie_malformed',
				'auth_cookie_expired',
				'auth_cookie_bad_username',
				'auth_cookie_bad_hash',
				'auth_cookie_bad_session_token'
		];

	protected static $autoload_hooks = ['plugins_loaded', 'after_setup_theme', 'init'];

	protected function __construct( $user ) {
		$this->user = static::getUserInstance($user, false);
		$this->user_id = $this->user ? $this->user->ID : 0;
		add_filter('attach_session_information', function($session, $user_id){
			return is_array($session) ? static::createTokenInstance($this->user, $session) : $session;
		}, -9999, 2);

	}

	protected static function getUserInstance($user, $strict = true){
		$cls = static::$user_model;
		if(is_int($user)){
			return $cls == "WP_User" ? new $cls($user) : $cls::get($user);
		}
		elseif( is_object($user) ){
			if( $user instanceof $cls ){
				return $user;
			}
			else{
				return trim($cls, '\\') == "WP_User" ? new $cls($user->ID) : $cls::get($user);
			}
		}
		if($strict){
			throw new Exception("Error getting user instance. Invalid/Incompatible user : '{$user}'.", 1);
		}
		return null;
	}


	public static function setUserModel($model){
		static::$user_model = $model;
	}

	public static function make(WP_User $user){
		$manager = static::get_instance($user);
		return $manager;
	}

	public static function getCurrent($force = true){
		if( ! static::$cookie_validated )
			trigger_error("WP_Session_Tokens ~ UserTokens::getCurrent() called too early. Current user is not yet determined.");


		if(!static::$current_session && $force){
			$user = function_exists('wp_get_current_user')
					? wp_get_current_user() : apply_filters( 'determine_current_user', false );
			return static::getCurrent(false);
		}

		return static::$current_session;
	}

	public static function setCurrentSession($user = null, $data = []){
		static::$current_session = static::createTokenInstance($user, $data);
		return static::$current_session;
	}

	public static function createTokenInstance($user = null, $data = []){
		if( ($user instanceof Token) ){
			$instance = $user;
			$instance->setProperties( $data );
		}else{
			$instance = new Token( static::getUserInstance($user, false), $data, static::request() );
		}
		return $instance;
	}

	public function newSessionObject(array $data = [], $filter = true){
		$session = static::createTokenInstance($this->user, $data);
		return $filter ? apply_filters('attach_session_information', $session, $this->user_id, $this->user) : $session;
	}

	public function getSessionObject($session, $filter = false){
		return is_array($session) ? $this->newSessionObject($session, $filter) : $session;
	}

	public static function signonWith($session, $remember = false, $redirect_to = '', $secure = ''){
		$manager = static::make( $session->user() );
		if($manager){
			return $manager->signon( $session, $remember,$redirect_to, $secure );
		}
		return null;
	}

	public function signon($session, $remember = false, $secure = ''){
		if($session && $session->user->ID == $this->user_id){
			if($wpuser = $this->setCurrent($session->token, $remember, $secure) ){
				static::setCurrentSession( $session );
				do_action('wp_login', $wpuser->user_login, $wpuser);
				return $wpuser;
			}
		}
		return false;
	}

	public function setCurrent($token, $remember = false, $secure = ''){
		if( $session = $this->get($token) ){
			$user = $this->user;
			wp_logout();
			wp_set_current_user(0);
			wp_set_auth_cookie( $user->ID, $remember, $secure, $token );
			wp_set_current_user($user->ID);
			return wp_get_current_user();
		}
		return null;
	}

	public function save($session){
		if( $session->user->ID == $this->user_id ){
			$token = $session->token;
			$this->update( $token, $session );
			return true;
		}
		return false;
	}

	protected function prepare_session( $session ) {
		if ( is_int( $session ) ) {
			$session = ['expiration' => $session];
		}
		return $this->getSessionObject($session, false);
	}

	protected function update_sessions( $sessions ) {
		$data = $sessions ? array_map( [$this, 'dbCastSession'], $sessions ) : $sessions;
		return parent::update_sessions( $data );
	}

	protected function dbCastSession($session){
		return $session->toDbArray();
	}

	public function build($name = 'login', array $data = []){
		setifnull($name, 'login');
		if(is_array($name)){
			$data = $name;
			$name = 'login';
		}
		array_add( $data, 'name', $name );
		$session = $this->newSessionObject($data, true);

		return $this->save($session) ? $session : null;
	}

	public function __get($key){
		if($key == 'user_id'){
			return $this->user->ID;
		}elseif(property_exists($this, $key)){
			return $this->$key;
		}
	}

	protected static function request(){
		return static::serviceContainer()->make(Request::class);
	}


	protected static function serviceContainer(){
		$resolver = static::$service_container_resolver;
		return $resolver();
	}

	public static function setServiceContainerResolver($resolver){
		static::$service_container_resolver = $resolver;
	}

	public static function currentSessionSetupHooks(){
		static::registerCookieValidationHooks();
		static::__determineCurrentUser( false );
	}

	public static function __determineCurrentUser($attempt = true){

		$callback = __CLASS__.'::__determineCurrentUser';
		$priority = -99999;

		$now = current_filter();
		$next = !empty(static::$autoload_hooks) ? array_shift(static::$autoload_hooks) : null;

		$user = $attempt && function_exists('wp_get_current_user')
					? wp_get_current_user() : null;

		if($next && !static::$cookie_validated)
			add_action($next, $callback, $priority, 0);

		if($user instanceof WP_User && static::$cookie_validated){
			static::removeCookieValidationHooks();
			static::$autoload_hooks = '';
		}
	}

	protected static function registerCookieValidationHooks(){
		$tags = static::$cookie_validation_hooks;
		$callback = $invalid_cookie = __CLASS__.'::__authCookieValidated';

		foreach ($tags as $tag) {
			add_action( $tag, $callback, 99, 2 );
		}
	}

	protected static function removeCookieValidationHooks(){
		$tags = static::$cookie_validation_hooks;
		$callback = $invalid_cookie = __CLASS__.'::__authCookieValidated';

		foreach ($tags as $tag) {
			remove_action( $tag, $callback, 99 );
		}
		static::$cookie_validation_hooks = [];
	}


	public static  function __authCookieValidated($cookie = null, $user = null){
		if( static::$cookie_validation_locked )
			return;

		$user = $user && $user instanceof WP_User ? $user : null;

		$cookie_status = current_filter();
		$cookie = (array) $cookie;
		$session = null;

		$token = array_get( $cookie, 'token');

		if( $user  && $token ){
			$session = static::make($user)->get($token);
			$cookie_status = $session ? true : $cookie_status;
		}

		$session = $session ? $session : static::createTokenInstance($user);
		$session->setProperties( compact('cookie', 'cookie_status') );

		static::setCurrentSession($session);
		static::$cookie_validated = true;

		do_action('current_user_token_validated', $user, $session, $token );
	}

}