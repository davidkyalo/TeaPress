<?php
namespace TeaPress\Auth;
/*
* Services.
* 1. Enigma - encryption decryption.
* 2. Validation - Validates decrypted ciphers.
* 3. Request -
* 4. UrlFactory -
* 5. Session -
* 6. UserTokens
* 7. Token
* 8. User - model
* 9. Authkey - model
* 10. Permit.
*/

use WP_User;
use Carbon\Carbon;
use TeaPress\Utils\Arr;
use TeaPress\Config\Lang;
use TeaPress\Http\Request;
use TeaPress\Http\UrlFactory;
use TeaPress\Messages\Bag as MessageBag;
use TeaPress\Utils\Validation;
use TeaPress\Encryption\Enigma;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\Macroable;

class Sentry {

	use Macroable;

	protected $app;

	protected $config = [];

	protected $current_user_token;

	protected $gates = [];


	public function __construct(Container $app, array $config){
		$this->app = $app;
		$this->setConfig($config);
	}

	protected function enigma(){
		return $this->app->make(Enigma::class);
	}

	protected function URL(){
		return $this->app->make(UrlFactory::class);
	}

	protected function validation(){
		return $this->app->make(Validation::class);
	}

	protected function request(){
		return $this->app->make(Request::class);
	}

	protected function response(){
		return $this->app->make(Lang::class);
	}

	protected function lang(){
		return $this->app->make(Lang::class);
	}

	public function setUserModel($model){
		Arr::set($this->config, 'model', $model);
		return $this;
	}

	public function setPermitModel($model){
		Arr::set($this->config, 'permits.model', $model);
		return $this;
	}

	public function setConfig(array $config){
		$this->config = $config;
		return $this;
	}

	public function getPermitModel(){
		return $this->getConfig('permits.model');
	}

	protected function getPermitModelInstance($key){
		$model = $this->getPermitModel();
		return $model::find($key);
	}

	public function getUserModel(){
		return $this->getConfig('model');
	}

	public function getConfig($key = null, $default = null){
		return Arr::get($this->config, $key, $default);
	}

	public function getUserInstance($user){
		$model = $this->getUserModel();
		return $model ? $model::get($user) : WP_User($user);
	}

	public function get($user){
		return $this->getUserInstance($user);
	}

	public function exists($user, $field = 'email'){
		$allowed = ['ID', 'email', 'login'];
		if(!in_array($field, $allowed)){
			trigger_error("Checking user existence by '{$field}'' field is highly discouraged.
				Recommended '". implode(', ', $allowed) ."'.");
		}
		$user = $this->getWpUserBy($field, $user);
		return $user ? $user->ID : false;
	}

	public function getWpUserBy($field, $value){
		return get_user_by($field, $value);
	}

	public function register($user_email, array $userdata = []){
		if( $this->exists($user_email, 'email') || Arr::get($userdata, 'ID') )
			return new MessageBag('user_exists', "User already exists.");

		$user_login = $this->generateUsername();
		$user_pass = $this->generatePassword();
		$display_name = $user_email;
		$nickname = $user_email;
		$default_data = compact( 'user_email', 'user_login', 'user_pass', 'display_name', 'nickname' );

		$user_id = wp_insert_user( array_merge( $default_data, $userdata ) );
		if(is_wp_error($user_id))
			return $user_id;

		$user = $this->getUserInstance($user_id);
		do_action('new_user_created', $user);
		return $user;
	}

	public function generateUsername($unique = true){
		$model = $this->getUserModel();
		return $model::generateUsername( $unique,
					$this->getConfig('username_prefix'),
					$this->getConfig('username_length') );
	}

	public function generatePassword( $length = 12, $special_chars = true, $extra_special_chars = false ){
		$model = $this->getUserModel();
		return $model::generatePassword($length, $special_chars, $extra_special_chars);
	}


	public function validateLoginCredentials($user, $userlogin, $password) {
		if(is_object($user) && ($instance = $this->getUserInstance($user))){
			return $instance;
		}

		$errors = new MessageBag();

		$errkey = 'users.login.error.';
		$def_err = $this->lang()->get($errkey.'authentication_failed', 'Invalid Login Credentials.');

		if ( empty($userlogin) || empty($password) ) {
			$err_msg = 'The login field is empty.';
			if ( empty($userlogin) )
				$errors->load('empty_username', $errkey . 'empty_username', $err_msg);

			if ( empty($password) )
				$errors->load('empty_password', $errkey . 'empty_password', $err_msg);

			return $errors;
		}

		$use_email = $this->getConfig('login_by_email', false);
		if( $use_email && !is_email($userlogin)){
			$errors->load('invalid_username', $errkey . 'invalid_username', $def_err);
			return $errors;
		}

		$model = $this->getUserModel();
		$user = $use_email ? $model::findByEmail($userlogin) : $model::findByLogin($userlogin);

		if ( !$user ){
			$errors->load('incorrect_username', $errkey . 'incorrect_username', $def_err);
			return $errors;
		}


		$wpuser = apply_filters( 'wp_authenticate_user', $user->wpuser, $password );
		if ( is_wp_error($wpuser) ){
			return $wpuser;
		}


		if ( !$user->checkPassword( $password ) ){
			$errors->load('incorrect_password', $errkey . 'incorrect_password', $def_err);
			return $errors;
		}

		return $user;
	}

	public function login($user, $remember = false, $sname = null, $sdata = []){
		$manager = $this->manager( $user );
		if( $session_token = $manager->build( $sname, $sdata ) ){
			return (boolean) $manager->signon($session_token, $remember);
		}
		return false;
	}

	public function permit($permit, $session = null){
		if( $permit->valid() && $permit->user ){
			if($this->check() && $this->user()->ID == $permit->user->ID)
				return true;

			$session['limited'] = true;
			$session['name'] = $permit->name;
			$session['auth_key'] = $permit->key;
			$session['meta'] = $permit->data;
			return $this->login($permit->user, false, $session);
		}
		return false;
	}

	public function logout(){
		wp_logout();
		wp_set_current_user(0);
	}

	public function user(){
		return $this->token()->user();
	}

	public function check($capability = null){
		$logged_in = $this->token()->check();
		return $logged_in && !is_null($capability) ? $this->userCan($capability) : $logged_in;
	}

	public function guest(){
		return !$this->check();
	}

	public function userCan($capability){
		return $this->user() ? $this->user()->can($capability) : false;
	}

	public function token(){
		return UserTokens::getCurrent();
	}

	public function manager($user){
		return UserTokens::make( $user );
	}

	public function hasAbility($ability){
		return isset($this->abilities[$ability]);
	}

	public function authRedirect($cap = 1, $user = null, $redirect_to = true){

		if( $user && is_string($user) || is_bool($user) ){
			$redirect_to = $user;
			$user = null;
		}

		if($this->authorize($cap, $user))
			return;

		nocache_headers();
		$this->URL()->redirect( $this->URL()->auth($cap, $redirect_to ) );
		die();
	}

	public function authExit($cap = 1, $user = null, $exit = false){
		if( is_bool($user) ){
			$exit = $user;
			$user = null;
		}

		if($this->authorize($cap, $user))
			return;

		$response = abort(401)->with('login_prompt', $this->URL()->auth($cap, true ) );
		return $exit ? $response->exit() : $response;
	}

	public function authorize($cap = 1, $user = null){
		setif($cap, (bool) $cap, is_numeric($cap));
		if(is_null($user)){
			return !$cap || ($cap === true && $this->check()) || $this->check($cap);
		}
		elseif( !$cap && (!$this->check() || $this->user()->ID == $user->ID) ){
			return true;
		}
		elseif($cap === true && $this->check() && $this->user()->ID == $user->ID){
			return true;
		}
		elseif ($this->check($cap) && $this->user()->ID == $user->ID) {
			return true;
		}
		return false;
	}

	public function defineAbility($ability, $callback){
		$this->abilities[$ability] = $callback;
	}
/*
* Permits
*/
	public function getPermit($user, $url = null, array $args = [] ){
		if( $user = $this->getUserInstance($user)){
			$args['url'] = $url ?  $this->URL()->uri($url) : home_url('/', 'relative');
			return $this->createPermit($user->getPermitsKey(), $args);
		}
		return null;
	}

	public function createPermit($owner, array $args = []){
		$permit = new Permit($this->enigma(), $owner, $args);
		return $permit;
	}

	public function loadPermit($ciphertext, $hash = null){
		$enigma = $this->enigma();

		$errors = new MessageBag();
		$errors->load(
			'invalid_permit',
			'users.login.error.invalid_permit',
			'Error Authenticating Request.');

		$ciphertext = $enigma->tobytes($ciphertext);

		$decrypted = $enigma->decipher($ciphertext);
		if( !$decrypted ){
			$errors->add('invalid_cipher');
			return $errors;
		}

		$data = $decrypted ? $this->parsePermitString( $decrypted, true ) : null;
		if(!$data){
			$errors->add('malformed_cipher');
			return $errors;
		}

		$data = $this->validatePermitData($data);
		if(is_wp_error($data)){
			$errors->merge($data);
			return $errors;
		}

		$owner = $this->getPermitModelInstance($data['key']);
		if( !$owner || $owner->user->login !== $data['user_login'] ){
			$errors->add( (!$owner ? 'invalid_key' : 'invalid_user') );
			trigger_error( "Error validating Login Permit:
					User '{$data['user_login']}' is not the owner of permit Key '{$data['key']}'.");
			return $errors;
		}

		// $data['hash'] = $hash;
		$permit = $this->createPermit($owner, $data);
		$permit->hash = !is_null($hash) ? $enigma->tobytes($hash) : null;

		if(!is_null($hash) && !$permit->verifyHash()){
			$errors->add('invalid_hash');
			return $errors;
		}
		return $permit;
	}


	protected function parsePermitString($str, $crypted = false, $strict = true){
		$raw = json_decode( $str, true);

		if(!is_array($raw) || empty($raw))
			return null;

		if(!$crypted)
			return $raw;

		$data = array_transform( $raw, [$this, 'getRealPermitCipherFieldName']);
		return $strict && count($raw) !== count($data) ? null : $data;
	}

	public function getRealPermitCipherFieldName($value, $key){
		$fields = array_flip($this->getConfig('permits.cipher_keys'));
		return !is_null($value) ? Arr::get($fields, $key, null) : null;
	}

	protected function validatePermitData(array $data){
		$validator = $this->validation()->make( $data, $this->getConfig('permits.rules') );
		$validator->setCustomMessages( $this->getPermitsValidationMessages( $validator->getRules() ) );
		if($validator->fails()){
			$errors = $validator->errors()->all();
			trigger_error( "Error validating Login Permit: ". implode(', ', $errors) );
		}
		return $validator->passes() ? $data : $validator->errors();
	}

	protected function getPermitsValidationMessages(array $allrules){
		$messages = [];
		foreach ($allrules as $field => $rules) {
			foreach ($rules as $part) {
				$part = trim($part);
				if(!empty($part)){
					$split = explode(':', $part, 2);
					$rule = $split[0];
					$messages[trim($rule)] = MessageBag::EMPTY_MESSAGE_FLAG;
				}
			}
		}
		return $messages;
	}

/*
* Permits End <---
*/

}