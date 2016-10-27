<?php
namespace TeaPress\Http\Cookies;


use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;
use TeaPress\Utils\Carbon\TimeDelta;
use TeaPress\Contracts\Http\Request;
use TeaPress\Contracts\Utils\Arrayable;
use TeaPress\Contracts\Utils\ArrayBehavior;
use TeaPress\Contracts\Http\Cookies\CookieJar as Contract;
use Symfony\Component\HttpFoundation\Cookie as BaseCookie;

class CookieJar implements Contract, ArrayBehavior, Arrayable, IteratorAggregate
{

	protected $request;

	protected $queued = [];

	protected $path = '/';

	protected $domain = null;

	protected $secure = false;


	/**
	* Create a new CookieJar instance.
	*
	* @param \TeaPress\Contracts\Http\Request|null 	$request
	* @param  string  $cookieHash
	* @param  string  $path
	* @param  string  $domain
	* @param  bool 	 $secure
	*
	* @return void
	*/
	public function __construct(Request $request = null, $cookieHash = null, $path = null, $domain = null, $secure = false)
	{
		if($request) $this->setRequest($request);

		$this->cookieHash = !is_null($cookieHash) ? $cookieHash : (defined('COOKIEHASH') ? COOKIEHASH : '');
		$this->path = !is_null($path) ? $path : (defined('COOKIEPATH') ? COOKIEPATH : '/');
		$this->domain = !is_null($domain) ? $domain : (defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : false);
		$this->secure = $secure;
	}


	/**
	* Create a new cookie instance.
	*
	* @param  string  $name
	* @param  string  $value
	* @param  \DateInterval|\DateTime|int|     $lifetime Interval/Datetime or int minutes.
	* @param  string  $path
	* @param  string  $domain
	* @param  bool    $secure
	* @param  bool    $httpOnly
	*
	* @return \TeaPress\Http\Cookies\Cookie
	*/
	public function make($name, $value, $lifetime = null, $path = null, $domain = null, $secure = null, $httponly = true)
	{
		setifnull($path, $this->path);
		setifnull($domain, $this->domain);
		setifnull($secure, $this->secure);
		if( is_numeric($lifetime) && abs($lifetime) > 0 && abs($lifetime) < 30000000 ){
			$lifetime = TimeDelta::minutes($lifetime);
		}

		return new Cookie( $name, $value, $lifetime, $path, $domain, $secure, $httponly );
	}

	/**
	* Create a cookie that lasts "forever" (five years).
	*
	* @param  string  $name
	* @param  string  $value
	* @param  string  $path
	* @param  string  $domain
	* @param  bool    $secure
	* @param  bool    $httpOnly
	*
	* @return \TeaPress\Http\Cookies\Cookie
	*/
	public function forever($name, $value, $path = null, $domain = null, $secure = null, $httponly = true)
	{
		return $this->make( $name, $value, TimeDelta::years(5), $path, $domain, $secure, $httponly );
	}

	/**
	* Expire the given cookie.
	*
	* @param  string  $name
	* @param  string  $path
	* @param  string  $domain
	*
	* @return \TeaPress\Http\Cookies\Cookie
	*/
	public function forget($name, $path = null, $domain = null)
	{
		return $this->queue( $name, '', TimeDelta::inverted()->years(5), $path, $domain );
	}


	/**
	* Queue the given cookie for the next response. Creates the cookie if given args.
	*
	* @param  TeaPress\Http\Cookies\Cookie|string  		$cookie 	Cookie instance or cookie name if new.
	* @param  string  						$value
	* @param  int|TeaPress\Utils\Carbon\Timedelta 		$lifetime
	* @param  string  						$path
	* @param  string  						$domain
	* @param  bool    						$secure
	* @param  bool    						$httpOnly
	*
	* @return \TeaPress\Http\Cookies\Cookie
	*/
	public function queue($cookie, ...$parameters)
	{
		if($cookie instanceof BaseCookie)
			$cookie = Cookie::createFromBase($cookie);
		else
			$cookie = call_user_func_array([$this, 'make'], func_get_args());

		$this->queued[$cookie->getName()] = $cookie;

		return $cookie;
	}

	/**
	 * Remove the specified cookies from the queue.
	 *
	 * @param  string|array  			$names
	 *
	 * @return void
	 */
	public function unqueue($names)
	{
		foreach ( (array) $names as $name){
			if(isset($this->queued[$name]))
				unset($this->queued[$name]);
		}
	}

	/**
	 * Get the specified queued cookie. If a name is not provided, all queued cookies are returned.
	 *
	 * @param  string|null	$name
	 * @param  mixed		$default
	 *
	 * @return \TeaPress\Http\Cookies\Cookie|array
	 */
	public function queued($name = null, $default = null)
	{
		if( is_null($name) )
			return $this->queued;

		return isset($this->queued[$name]) ? $this->queued[$name] : $default;
	}


	/**
	 * Determine if the specified cookie is queued.
	 *
	 * @param  string	$name
	 *
	 * @return bool
	 */
	public function isQueued($name)
	{
		return array_key_exists($name, $this->queued);
	}


	/**
	* Send the provided cookie with the rest of the HTTP headers.
	*
	* @param \TeaPress\Http\Cookies\Cookie  $cookie
	*
	* @return bool
	*/
	public function sendCookie(BaseCookie $cookie)
	{
		return setcookie(
				$this->suffixCookieName($cookie->getName()),
				$cookie->getValue(),
				$cookie->getExpiresTime(),
				$cookie->getPath(),
				$cookie->getDomain(),
				$cookie->isSecure(),
				$cookie->isHttpOnly() );
	}


	/**
	* Send the provided cookie objects with the rest of the HTTP headers.
	* If string/array of strings, the queued cookies by the given names will be sent.
	*
	* @param \TeaPress\Http\Cookies\Cookie|string|array
	*
	* @return void
	*/
	public function send($cookies)
	{
		if(!is_array($cookies)) $cookies = [$cookies];

		foreach ($cookies as $cookie) {
			if(is_string($cookie))
				$cookie = $this->queued($cookie);

			if(!($cookie instanceof BaseCookie))
				continue;

			$this->sendCookie($cookie);

			$this->unqueue( $this->trimCookieName($cookie->getName()) );
		}
	}


	/**
	* Sends all the queued cookies with the rest of the HTTP headers.
	*
	* @return void
	*/
	public function flush()
	{
		$this->send( array_keys($this->queued) );
	}


	/**
	 * Get all the queued cookies.
	 *
	 * @return array
	 */
	public function getQueuedCookies()
	{
		return $this->queued;
	}

/* Queued Cookies */


/****** Request Cookies ********/

	/**
	 * Determine if the specified cookie is set on the request.
	 *
	 * @param  string	$name
	 *
	 * @return bool
	 */
	public function has($name)
	{
		return $this->request->hasCookie($name);
	}

	/**
	 * Get all set cookies from the request.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->request->getCookieBag()->all();
	}


	/**
	 * Get the specified queued cookie. If a name is not provided, all queued cookies are returned.
	 *
	 * @param  string|null	$name
	 * @param  mixed		$default
	 *
	 * @return string|mixed
	 */
	public function get($name, $default = null)
	{
		return $this->request->cookie( $name, $default );
	}

	/**
	 * Set the specified cookie value in the request.
	 *
	 * NOTE: This method does not queue new cookies. The set value will only be available on the current request
	 *
	 * @param  string		$name
	 * @param  mixed		$value
	 *
	 * @return void
	 */
	protected function set($name, $value)
	{
		$this->request->getCookieBag()->set($name, $value);
	}


	/**
	* Removes a cookie from the request.
	*
	* NOTE: The cookie is only removed from the current request. Use forget() to delete the cookie from the client.
	*
	* @param string $name
	*/
	public function remove($name)
	{
		$this->request->getCookieBag()->remove($name);
	}

/* Request Cookies */


/******* Misc ********/

	/**
	 * Get the value of the given cookie.
	 *
	 * @param  \TeaPress\Http\Cookies\Cookie|mixed	$cookie
	 *
	 * @return mixed
	 */
	public function value($cookie)
	{
		return ($cookie instanceof BaseCookie) ? $cookie->getValue() : $cookie;
	}


	/**
	 * Get the values of the given cookie objects.
	 *
	 * @param  \TeaPress\Http\Cookies\Cookie|array	$cookie
	 * @param  bool									$deep
	 *
	 * @return array|string|mixed
	 */
	public function cookieValue($cookie, $deep = true)
	{
		if( ($cookie instanceof BaseCookie) ){
			return $cookie->getValue();
		}
		elseif($deep && is_array( $cookie )){
			$values = [];
			foreach ($cookie as $k => $v) {
				$values[$k] = $this->cookieValue($v, false);
			}
			return $values;
		}
		else{
			return $cookie;
		}
	}

	/**
	* Trim the cookie suffix from the given cookie name.
	*
	* @param string 	$name
	*
	* @return string
	*/
	public function trimCookieName($name)
	{
		return rtrim( $name, $this->cookieSuffix() );
	}


	/**
	* Get the suffixed value of the given cookie name.
	*
	* @param string 	$name
	*
	* @return string
	*/
	public function suffixCookieName($name)
	{
		return $this->trimCookieName($name).$this->cookieSuffix();
	}


	/**
	* Get the hash string appended to cookie names
	*
	* @return string
	*/
	public function cookieHash()
	{
		return $this->cookieHash;
	}

	/**
	* Get the suffix appended to cookie names based on the cookieHash value
	*
	* @return string
	*/
	public function cookieSuffix()
	{
		return $this->cookieHash ? '_'.$this->cookieHash : '';
	}


	/**
	* Set The request instance.
	*
	* @param \TeaPress\Contracts\Http\Request
	*
	* @return void
	*/
	public function setRequest(Request $request)
	{
		$this->trimRequestCookieNames( $request );
		$this->request = $request;
	}

/* Misc */

/******* ArrayBehavior, Arrayable ******/

	public function offsetExists($key)
	{
		return $this->has($key);
	}

	public function offsetGet($key)
	{
		return $this->get($key);
	}

	public function offsetSet($key, $value)
	{
		return $this->set($key, $value);
	}

	public function offsetUnset($key)
	{
		$this->remove($key);
	}

	public function offsets()
	{
		return array_keys($this->all());
	}

	public function count()
	{
		return count($this->all());
	}

	public function toArray()
	{
		return $this->all();
	}

	public function getIterator()
	{
		return new ArrayIterator($this->toArray());
	}

/* ArrayBehavior, Arrayable */



/******* Internals ******/

	/**
	* Trims the cookie hash off the keys of the cookies in the given request.
	*
	* @param \TeaPress\Contracts\Http\Request
	*
	* @return void
	*/
	protected function trimRequestCookieNames(Request $request)
	{
		$cookies = $request->getCookieBag();
		$cookies->replace( $this->trimCookieNames( $cookies->all() ) );
	}

	/**
	* Trims the cookie hash off the keys of the given cookies array.
	*
	* @param array
	*
	* @return array
	*/
	protected function trimCookieNames(array $cookies)
	{
		$trimed = [];
		foreach ($cookies as $name => $value) {
			$trimed[$this->trimCookieName($name)] = $value;
		}
		return $trimed;
	}

/* Internals */
}
