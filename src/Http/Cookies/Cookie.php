<?php
namespace TeaPress\Http\Cookies;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use TeaPress\Utils\Carbon\Carbon;
use TeaPress\Utils\Carbon\TimeDelta;
use Symfony\Component\HttpFoundation\Cookie as BaseCookie;
/**
*
*/
class Cookie extends BaseCookie
{

	protected $lifetime = null;

	public function __construct($name, $value = null, $lifetime = null, $path = null, $domain = null, $secure = false, $httponly = true)
	{
		if(is_null($path)) $path = '/';
		if(is_null($lifetime)) $lifetime = 0;

		parent::__construct($name, $value, 0, $path, $domain, $secure, $httponly );

		if( $lifetime instanceof TimeDelta){
			$this->setLifetime($lifetime);
		}
		else{
			$this->expireOn($lifetime);
		}
	}

	public static function create($name, $value=null, $lifetime=null, $path = null, $domain = null, $secure = false, $httponly = true)
	{
		return new static($name, $value, $lifetime, $path, $domain, $secure, $httponly );
	}

	public static function createFromBase(BaseCookie $cookie)
	{
		if( $cookie instanceof static )
			return $cookie;

		return static::create(
					$cookie->getName(),
					$cookie->getValue(),
					$cookie->getExpiresTime(),
					$cookie->getPath(),
					$cookie->getDomain(),
					$cookie->isSecure(),
					$cookie->isHttpOnly());
	}

	public function getExpiresTime($createdAt = null)
	{
		if($this->expire || !$this->lifetime){
			return $this->expire;
		}

		$createdAt = $createdAt ?: Carbon::now();

		return Carbon::cast($createdAt)->add( $this->lifetime )->format('U');
	}

	public function getExpiresOn($createdAt = null)
	{
		return $this->getExpiresTime($createdAt);
	}

	public function isCleared()
	{
		return $this->getExpiresTime() < time();
	}

	/**
	* Sets the time the cookie expires.
	*
	* @param int|string|\DateTime|\DateTimeInterface $expire   The time the cookie expires
	* @return \TeaPress\Http\Cookie;
	*/
	public function expireOn($expire)
	{
		if ($expire instanceof DateTime || $expire instanceof DateTimeInterface) {
			$expire = $expire->format('U');
		}
		elseif (!is_numeric($expire)) {
			$expire = strtotime($expire);

			if (false === $expire || -1 === $expire) {
				throw new InvalidArgumentException('The cookie expiration time is not valid.');
			}
		}
		$this->expire = $expire;
		return $this;
	}

	/**
	* Sets the lifetime of the cookie in seconds.
	*
	* @param int $lifetime 	The number of minutes after which the cookie expires.
	* @return static;
	*/
	public function setLifetime($lifetime)
	{
		if(is_numeric($lifetime))
			$lifetime = TimeDelta::minutes($lifetime);

		if (! $lifetime instanceof TimeDelta){
			throw new InvalidArgumentException('The cookie lifetime value is not valid.');
			return $this;
		}

		$this->lifetime = $lifetime;
		return $this;
	}

	public function __get($key)
	{
		$getter = camel_case( 'get_'.$key );

		if(method_exists($this, $getter) )
			return $this->$getter();

		switch (camel_case($key)) {
			case 'expire':
				return $this->getExpiresTime();
				break;

			case 'lifetime':
				return $this->lifetime;
				break;

			case 'secure':
			case 'isSecure':
				return $this->isSecure();
				break;

			case 'httpOnly':
			case 'isHttpOnly':
				return $this->isHttpOnly();
				break;

			case 'isCleared':
				return $this->isCleared();
				break;

			default:
				throw new InvalidArgumentException(sprintf("Unknown getter '%s'", $key));
		}
	}

	public function __call($name, $args)
	{
		$arg = count($args) === 0 ? null: $args[0];
		switch ($name) {
			case 'value':
				$this->value = $arg;
				break;

			case 'domain':
				$this->domain = $arg;
				break;

			case 'expire':
				$this->expireOn( $arg );
				break;

			case 'lifetime':
				$this->setLifetime( $arg );
				break;

			case 'path':
				$this->path = $arg;
				break;

			case 'secure':
				$this->secure = (bool) $arg;
				break;

			case 'httpOnly':
				$this->httpOnly = (bool) $arg;
				break;
		}
		return $this;
	}
}