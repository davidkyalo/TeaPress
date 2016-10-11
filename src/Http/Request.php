<?php
namespace TeaPress\Http;

use TeaPress\Utils\Arr;
use TeaPress\Utils\Str;
use TeaPress\Signals\Traits\Emitter;
use TeaPress\Contracts\Session\Store as Session;
use TeaPress\Contracts\Http\Request as Contract;
use Illuminate\Http\Request as IlluminateRequest;
use TeaPress\Contracts\Signals\Emitter as EmitterContract;

class Request extends IlluminateRequest implements Contract, EmitterContract {

	use Emitter;

	public function reload($query = true, $status = 302){
		if($query === true){
			$url = $this->fullUrl();
		}
		elseif(is_array($query)) {
			$url = $this->url() . '?' . http_build_query($query);
		}
		else{
			$url = $this->url();
		}
		if(function_exists('esc_url'))
			$url = esc_url($url);

		if(false !== $this->_PerformHttpRedirect($url, $status)){
			die();
		}
	}

	private function _PerformHttpRedirect( $location, $status ){
		if(function_exists('wp_safe_redirect')){
			return wp_safe_redirect( $location, $status );
		}else{
			// if(parse_url($location, PHP_URL_HOST) != parse_url($this->root(), PHP_URL_HOST))
			// 	return false;

			if ( PHP_SAPI != 'cgi-fcgi' )
				status_header($status);

			header("Location: $location", true, $status);
		}
	}

	public function ajax(){
		return (parent::ajax() || ( defined('DOING_AJAX') && DOING_AJAX ));
	}

	public function isWpAdmin(){
		return is_admin();
	}

	public function isWpCron(){
		return trimslashes($this->getScriptName()) === 'wp-cron.php' ? true : false;
	}

	public function url( $unslash = false ){
		$url = preg_replace('/\?.*/', '', $this->getUri());
		return $unslash ? rtrim( $url, '/') : $url;
	}

	public function uri( $unslash = false ){
		$query = $this->getQueryString();
		return $query ? $this->path($unslash) . '?' . $query : $this->path($unslash);
	}

	public function path($unslash = false){
		return $unslash ? parent::path() : $this->getPathInfo();
	}

	public function is()
	{
		foreach (func_get_args() as $pattern) {
			$pattern = trim($pattern, '/');
			$pattern = $pattern == '' ? '/' : $pattern;
			if (Str::is($pattern, urldecode($this->path( true )))) {
				return true;
			}
		}

		return false;
	}

	public function referer(){
		return wp_get_referer();
	}

	public function previous()
	{
		return $this->session->previousUrl();
	}

	public function segment($index, $default = null){
		$segments = $this->segments();
		$index = $index < 0 ? count($segments) + $index : $index - 1;

		return Arr::get($segments, $index, $default);
	}


	public function isMethod($method)
	{
		if(is_array($method)){
			foreach ($method as $name) {
				if( $this->isMethod($name) )
					return true;
			}
			return false;
		}
		return parent::isMethod($method);
	}

	public function setQueryVar($key, $value, $update_globals = false)
	{
		$this->query->set($key, $value);
		if($update_globals)
			$this->updateGlobalVar('get');
	}

	public function setInput($key, $value, $update_globals = false)
	{
		if($this->isMethod('get')){
			return $this->setQueryVar($key, $value, $update_globals);
		}

		$this->getInputSource()->set($key, $value);

		if($update_globals){
			$this->updateGlobalVar('post');
		}
	}

	/**
	* Get the cookie bag instance.
	*
	* @return \Symfony\Component\HttpFoundation\ParameterBag
	*/
	public function getCookieBag()
	{
		return $this->cookies;
	}

	protected function updateGlobalVar($var)
	{

		switch (strtolower( ltrim($var, '_') )) {
			case 'get':
			$this->server->set('QUERY_STRING', static::normalizeQueryString(http_build_query($this->query->all(), null, '&')));
			$_GET = $this->query->all();
			$this->updateGlobalVar('request');
			break;

			case 'post':
			$_POST = $this->request->all();
			$this->updateGlobalVar('request');
			break;

			case 'cookie':
			$_COOKIE = $this->cookies->all();
			$this->updateGlobalVar('request');
			break;

			case 'server':
			$_SERVER = $this->server->all();
			break;

			case 'request':
			$request = array('g' => $_GET, 'p' => $_POST, 'c' => $_COOKIE);
			$requestOrder = ini_get('request_order') ?: ini_get('variables_order');
			$requestOrder = preg_replace('#[^cgp]#', '', strtolower($requestOrder)) ?: 'gp';

			$_REQUEST = array();
			foreach (str_split($requestOrder) as $order) {
				$_REQUEST = array_merge($_REQUEST, $request[$order]);
			}
			break;

			case 'headers':
			foreach ($this->headers->all() as $key => $value) {
				$key = strtoupper(str_replace('-', '_', $key));
				if (in_array($key, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
					$_SERVER[$key] = implode(', ', $value);
				} else {
					$_SERVER['HTTP_'.$key] = implode(', ', $value);
				}
			}
			break;
		}


	}

	public static function flashingPreviousUrl($callback, $priority = null, $accepted_args = null)
	{
		$this->bindCallback('previous_url', $callback, $priority, $accepted_args);
	}

	public function _storeDataToSession($session)
	{

		trigger_error('The previous url needs to be set preferably through signals');

		return;

		// if ($this->isMethod('get') && !$this->ajax() && !$this->isWpCron())
		// 	$previous_url = $this->fullUrl();
		// else
		// 	$previous_url = $this->previous();

		// ;

		// $this->session->setPreviousUrl( $this->mapItem('previous_url', $previous_url, [$this]) );

		// $flash = $this->emit('flash', [false, $this]);
		// if( $flash === true ){
		// 	$this->flash();
		// }elseif ( is_array($flash)) {
		// 	$filter = key($flash);
		// 	$keys = current($flash);
		// 	if( count($flash) > 1 || !$filter || !$keys || !is_string($filter) || !is_array($keys) ){
		// 		trigger_error('Error flashing request input to session. Invalid filter and keys arguments.');
		// 		return;
		// 	}
		// 	$this->flash( $filter, $keys );
		// }elseif(is_string($flash)) {
		// 	list($filter, $keys) = explode(':', $flash, 2);
		// 	if(!$filter || !$keys){
		// 		trigger_error('Error flashing request input to session. Invalid filter and keys arguments.');
		// 		return;
		// 	}
		// 	$this->flash( $filter, explode(',', $keys));
		// }
	}

	public function setSession(Session $session)
	{
		$this->session = $session;
		$this->session->shutingdown([$this, '_storeDataToSession']);
	}

}
