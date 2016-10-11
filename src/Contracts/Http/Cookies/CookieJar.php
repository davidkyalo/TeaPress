<?php
namespace TeaPress\Contracts\Cookies;

use TeaPress\Contracts\Http\Request;
use Illuminate\Contracts\Cookie\QueueingFactory;
use Symfony\Component\HttpFoundation\Cookie as BaseCookie;

interface CookieJar extends QueueingFactory
{

	/**
	 * Get the specified queued cookie. If a name is not provided, all queued cookies are returned.
	 *
	 * @param  string|null	$name
	 * @param  mixed		$default
	 *
	 * @return \TeaPress\Http\Cookies\Cookie|array
	 */
	public function queued($name = null, $default = null);


	/**
	 * Determine if the specified cookie is queued.
	 *
	 * @param  string	$name
	 *
	 * @return bool
	 */
	public function isQueued($name);

	/**
	* Send the provided cookie with the rest of the HTTP headers.
	*
	* @param \TeaPress\Http\Cookies\Cookie  $cookie
	*
	* @return bool
	*/
	public function sendCookie(BaseCookie $cookie);


	/**
	* Send the provided cookie objects with the rest of the HTTP headers.
	* If string/array of strings, the queued cookies by the given names will be sent.
	*
	* @param \TeaPress\Http\Cookies\Cookie|string|array
	*
	* @return void
	*/
	public function send($cookies);

	/**
	* Sends all the queued cookies with the rest of the HTTP headers.
	*
	* @return void
	*/
	public function flush();


/****** Request Cookies ********/

	/**
	 * Determine if the specified cookie is set on the request.
	 *
	 * @param  string	$name
	 *
	 * @return bool
	 */
	public function has($name);


	/**
	 * Get all set cookies from the request.
	 *
	 * @return array
	 */
	public function all();


	/**
	 * Get the specified queued cookie. If a name is not provided, all queued cookies are returned.
	 *
	 * @param  string|null	$name
	 * @param  mixed		$default
	 *
	 * @return string|mixed
	 */
	public function get($name, $default = null);


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
	protected function set($name, $value);


	/**
	* Removes a cookie from the request.
	*
	* NOTE: The cookie is only removed from the current request. Use forget() to delete the cookie from the client.
	*
	* @param string $name
	*/
	public function remove($name);


/* Request Cookies */

	/**
	 * Get the value of the given cookie.
	 *
	 * @param  \TeaPress\Http\Cookies\Cookie|mixed	$cookie
	 *
	 * @return mixed
	 */
	public function value($cookie);

	/**
	* Set The request instance.
	*
	* @param \TeaPress\Contracts\Http\Request
	*
	* @return void
	*/
	public function setRequest(Request $request);

}