<?php
namespace TeaPress\Session;

use Exception;
use TeaPress\Utils\Carbon\Carbon;
use TeaPress\Utils\Carbon\TimeDelta;
use TeaPress\Contracts\Cookies\CookieJar;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Session\ExistenceAwareInterface;

class SessionHandler implements SessionHandlerInterface, ExistenceAwareInterface
{
	/**
	 * The database connection instance.
	 *
	 * @var \Illuminate\Database\ConnectionInterface
	 */
	protected $connection;

	/**
	 * The name of the session table.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The cookie name of the session.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The cookie jar/factory instance
	 *
	 * @var \TeaPress\Contracts\Cookies\CookieJar.
	 */
	protected $cookies;

	/**
	 * The amount of time the session should be valid.
	 *
	 * @var \TeaPress\Utils\Carbon\TimeDelta
	 */
	protected $lifetime;

	/**
	 * The existence state of the session.
	 *
	 * @var bool
	 */
	protected $exists;

	/**
	 * Create a new database session handler instance.
	 *
	 * @param  \Illuminate\Database\ConnectionInterface  	$connection
	 * @param  \TeaPress\Contracts\Cookie\CookieJar  		$cookies
	 * @param  \TeaPress\Utils\Carbon\TimeDelta  			$lifetime
	 * @param  string  										$name
	 * @param  string  										$table
	 *
	 * @return void
	 */
	public function __construct(ConnectionInterface $connection, CookieJar $cookies, TimeDelta $lifetime, $name, $table)
	{
		$this->name = $name;
		$this->table = $table;
		$this->cookies = $cookies;
		$this->lifetime = $lifetime;
		$this->connection = $connection;
	}

	/**
	 * {@inheritdoc}
	 */
	public function open($savePath, $sessionName)
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function close()
	{
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function read($sessionId)
	{
		$session = (object) $this->getQuery()->find($sessionId);

		if (isset($session->last_activity)) {
			if ($session->last_activity < $this->lifetime->totalSeconds()) {
				$this->exists = true;
				return;
			}
		}

		if (isset($session->payload)){
			$this->exists = true;
			return $this->castRawPayload($session->payload);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function write($sessionId, $data)
	{
		$payload = $this->getRawPayload($data);

		if ($this->exists) {
			$this->getQuery()->where('id', $sessionId)->update([
				'payload' => $payload, 'last_activity' => time(),
			]);
		} else {
			$this->getQuery()->insert([
				'id' => $sessionId, 'payload' => $payload, 'last_activity' => time(),
			]);
		}

		$this->exists = true;
	}

	protected function getRawPayload($payload)
	{
		return base64_encode($payload);
	}

	protected function castRawPayload($payload)
	{
		return base64_decode($payload);
	}

	/**
	 * {@inheritdoc}
	 */
	public function destroy($sessionId)
	{
		$this->getQuery()->where('id', $sessionId)->delete();
	}

	/**
	 * {@inheritdoc}
	 */
	public function gc($lifetime)
	{
		if($lifetime instanceof TimeDelta)
			$lifetime = $lifetime->totalSeconds();
		$this->getQuery()->where('last_activity', '<=', time() - $lifetime)->delete();
	}

	/**
	 * Get a fresh query builder instance for the table.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function getQuery()
	{
		return $this->connection->table($this->table);
	}

	/**
	 * Set the existence state for the session.
	 *
	 * @param  bool  $value
	 * @return $this
	 */
	public function setExists($value)
	{
		$this->exists = $value;

		return $this;
	}

	public function getSessionId()
	{
		$id = $this->cookies->value($this->cookies->queued($this->name));

		return !is_null($id) ? $id : $this->cookies->get($this->name);
	}

	public function setSessionId($id)
	{
		$old = $this->getSessionId();

		if(!$old || !hash_equals($id, $old)){
			$this->cookies->queue( $this->cookies->forever( $this->name, $id ) );
		}

	}

	public function setSessionName($name)
	{
		$this->name = $name;
	}

	public function getSessionName()
	{
		return $this->name;
	}

}
