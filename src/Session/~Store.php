<?php
namespace TeaPress\Session;

use TeaPress\Utils\Str;
use TeaPress\Carbon\TimeDelta;
use TeaPress\Encryption\Enigma;
use Tes\Utils\MessageBag;
use TeaPress\Hooks\Emitter;
use TeaPress\Contracts\Hooks\Emitter as EmitterInterface;
use TeaPress\Events\EmitterInterface;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Session\Store as IlluminateStore;

class Store extends IlluminateStore implements EmitterInterface {

	use Emitter;

	protected static $hooks_namespace = 'session';

	protected $lifetime;

	protected $oxygen;

	protected $hooks = [];

	public function __construct($name, FileSessionHandler $handler, TimeDelta $lifetime) {
		parent::__construct( $name, $handler, $handler->getSessionId( $name ) );
		$this->lifetime = $lifetime;
	}

	public function start(){

		$this->fireStartingCallbacks();

		if(!parent::start())
			return false;

		if ( !$this->sessionIsValid() ) {
			$this->reset();
		}
		else {
			$this->handlerUpdateSessionId();
		}

		$this->fireStartedCallbacks();

		return true;
	}

	public function starting($callback, $priority = null, $accepted_args = null, $once = null)
	{
		return static::bindHookCallback('starting', $callback, $priority, $accepted_args, $once);
	}


	public function started($callback, $priority = null, $accepted_args = null, $once = null)
	{
		return static::bindHookCallback('started', $callback, $priority, $accepted_args, $once);
	}

	protected function fireStartingCallbacks()
	{
		$this->doAction('starting', $this);
	}

	protected function fireStartedCallbacks()
	{
		$this->doAction('started', $this);
	}

	protected function sessionIsValid(){

		$modified = $this->get('_last_modified', time() );
		$idle = time() - $modified;

		if( $idle > $this->lifetime->totalSeconds() )
			return false;

		if( $idle >= 3600 )
			return $this->verifySessionMac( $this->getId() );

		return true;
	}

	public function isValidId($id){
		return is_string($id) && preg_match('/^[a-f0-9]{50}$/', $id);
	}

	protected function generateSessionId(){
		$salt = sha1(uniqid('', true).Str::random(25).microtime(true));
		$mac = $this->generateSessionMac($salt);
		return $mac.$salt;
	}

	protected function getSessionMacSalt($id = null){
		$id = $id ? $id : $this->getId();
		return $id ? substr($id, 10) : null;
	}

	protected function getSessionMac($id = null){
		$id = $id ? $id : $this->getId();
		return $id ? substr($id, 0, 10) : null;
	}

	protected function verifySessionMac($id = null){
		$mac = $this->getSessionMac($id);
		$salt = $this->getSessionMacSalt($id);

		$expected = $this->generateSessionMac();

		if( hash_equals( $this->hashSessionMac($expected, $salt), $mac) )
			return true;

		if( hash_equals( $this->hashSessionMac(($expected - 1), $salt), $mac) )
			return true;

		return false;
	}

	protected function generateSessionMac($salt = null){
		$mac = ceil( time() / ( 1209600 / 2 ));
		return $salt ? $this->hashSessionMac($mac, $salt) : $mac;
	}

	protected function hashSessionMac($mac, $salt){
		return substr(hash_hmac('md5', $mac, $salt), -12, 10);
	}


	protected function handlerUpdateSessionId()
	{
		$this->handler->saveSessionId($this->getName(), $this->getId());
	}

	public function migrate($destroy = false, $lifetime = null)
	{
		$migrated = parent::migrate($destroy, $lifetime);
		$this->handlerUpdateSessionId();
		return $migrated;
	}

	public function reset( $lifetime = null ){
		if( parent::invalidate($lifetime) ){
			$this->regenerateToken();
			$this->clearActionTokens();
			return true;
		}
		return false;
	}

	public function handlerNeedsRequest()
	{
		return $this->handler instanceof FileSessionHandler;
	}

	public function token($action = null){
		return $action ? $this->actionToken($action) : $this->get('_token');
	}

	protected function clearActionTokens(){
		$this->forget('_tokens');
	}

	public function actionToken($action){
		$token = $this->get('_tokens.'.$action);
		if(!$token){
			$token = $this->generateActionToken();
			$this->put('_tokens.'.$action, $token);
		}
		return $token;
	}

	protected function generateActionToken(){
		return strtolower(uniqid(Str::random(7)).Str::random(5));
	}

	public function verifyToken($token, $action = null ){
		$valid = $this->token($action);
		return ((mb_strlen($token) === mb_strlen($valid)) && ($token === $valid));
	}

	// public function flashMessages($key, $messages ){
	// 	$m = $messages instanceof Arrayable ? $messages->toArray() : (array) $messages;
	// 	$this->flash( "_messages.{$key}", $m);
	// }

	// public function flashedMssages($key = null, $default = [] ){
	// 	$key = is_null($key) ? "_messages" : "_messages.{$key}";
	// 	return $this->get($key, $default );
	// }

	public function save(){
		$this->updateUserDataKeys();
		$this->set('_last_modified', time());

		$this->emit('save', $this);
		return parent::save();
		$this->emit('saved', $this);
	}


	protected function updateUserDataKeys(){
		$old = $this->get('_user_data', []);
		$new = [];
		foreach ($old as $key) {
			if( $this->has($key) )
				$new[] = $key;
		}
		$this->set('_user_data', $new);
	}

	public function user( $key, $value = null){
		$this->put($key, $value);

		$this->push( '_user_data', $key);
	}

	public function flushUserData($keep = null){
		$flush = array_flip( $this->get('_user_data', []) );
		if($keep)
			$flush = array_forget( $flush, $keep );
		$this->forget( array_flip($flush) );
		$this->updateUserDataKeys();
	}

}