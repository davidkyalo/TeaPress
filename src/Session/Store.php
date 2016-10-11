<?php
namespace TeaPress\Session;

use TeaPress\Utils\Arr;
use TeaPress\Utils\Str;
use TeaPress\Signals\Traits\Emitter;
use Illuminate\Session\Store as IlluminateStore;
use TeaPress\Contracts\Session\Store as Contract;
use TeaPress\Contracts\Signals\Emitter as EmitterInterface;

class Store extends IlluminateStore implements Contract, EmitterInterface {

	use Emitter;

	protected static $hooks_namespace = 'session';

	public function __construct(SessionHandlerInterface $handler)
	{
		parent::__construct('', $handler);
	}

	public function start()
	{
		$this->loadSessionId();

		$this->emitSignal('starting');

		if(!parent::start())
			return false;

		$this->emitSignal('started');

		$this->updateSessionId();

		return true;
	}

	public static function starting($callback, $priority = null, $accepted_args = null)
	{
		return static::bindCallback('starting', $callback, $priority, $accepted_args);
	}


	public static function started($callback, $priority = null, $accepted_args = null)
	{
		return static::bindCallback('started', $callback, $priority, $accepted_args);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return $this->handler->getSessionName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function setName($name)
	{
		$this->handler->setSessionName($name);
	}


	/**
	* Get a new, random session ID.
	*
	* @return string
	*/
	protected function generateSessionId()
	{
		return sha1(uniqid('', true).Str::random(32).microtime(true));
	}

	protected function loadSessionId()
	{
		$this->setId($this->handler->getSessionId());
	}

	protected function updateSessionId()
	{
		$this->handler->setSessionId($this->getId());
	}

	public function migrate($destroy = false, $lifetime = null)
	{
		$migrated = parent::migrate($destroy, $lifetime);
		$this->updateSessionId();
		return $migrated;
	}

	public function reset( $lifetime = null )
	{
		if( parent::invalidate($lifetime) ){
			$this->regenerateToken();
			$this->clearActionTokens();
			return true;
		}
		return false;
	}

	public function verifyToken($token, $expected = null)
	{
		return hash_equals( ($expected?:$this->token()), $token );
	}

	public function nonce($action = -1)
	{
		return wp_create_nonce($action);
	}

	public function verifyNonce($nonce, $action = -1)
	{
		return wp_verify_nonce($nonce, $action);
	}

	public function save()
	{
		$this->updateUserDataKeys();
		$this->emitSignal('saving');

		return parent::save();

		$this->emitSignal('saved');
	}

	public function shutdown()
	{
		$this->emitSignal('shutdown');
		$this->save();
		$this->attributes=[];
	}

	public static function saving($callback, $priority = null, $accepted_args = null)
	{
		return static::bindCallback('saving', $callback, $priority, $accepted_args);
	}

	public static function saved($callback, $priority = null, $accepted_args = null)
	{
		return static::bindCallback('saved', $callback, $priority, $accepted_args);
	}

	public static function shutingdown($callback, $priority = null, $accepted_args = null)
	{
		return static::bindCallback('shutdown', $callback, $priority, $accepted_args);
	}

	protected function updateUserDataKeys()
	{
		$old = $this->get('_user_data', []);
		$new = [];
		foreach ($old as $key) {
			if( $this->has($key) )
				$new[] = $key;
		}
		$this->set('_user_data', $new);
	}

	public function user( $key, $value = null)
	{
		$this->put($key, $value);
		$this->push( '_user_data', $key);
	}

	public function flushUserData($keep = null)
	{
		$keys = $this->get('_user_data', []);

		$keep = (array) $keep;

		foreach ((array) $keys as $key){
			if(!in_array($key, $keep))
				$this->forget($key);
		}
		$this->set('_user_data', $keep);
	}


}