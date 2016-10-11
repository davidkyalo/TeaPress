<?php
namespace TeaPress\Http;
use Exception;
use TeaPress\Http\Routing\Router;
use Illuminate\Support\Traits\Macroable;

class UrlFactory {

use Macroable;

	protected $router;
	protected $current_request;
	protected $endpoint_hook_tags_prefix = 'tea_endpoint_url_';

	public function __construct() {
	}

	public function setRouter(Router $router){
		$this->router = $router;
	}

	public function setRequest(Request $request){
		$this->current_request = $request;
	}

	public function request(){
		return $this->current_request;
	}

	public function parse($url){
		$parts = parse_url( $url );
		if(!is_array($parts))
			return $parts;

		$default_parts = [
				'scheme' => null, 'host' => null,
				'port' => null, 'user' => null,
				'pass'  => null, 'path'  => null,
				'query' => null, 'uri' => null, 'fragment' => null
			];
		$parts = array_merge($default_parts, $parts);
		if($parts['path']){
			$query = $parts['query'] ? '?'.$parts['query'] : '';
			$fragment = $parts['fragment'] ? '#'.$parts['fragment'] : '';
			$parts['uri'] = $parts['path'] . $query . $fragment;
		}
		return $parts;
	}

	public function parts($url = null, $key = null){
		if(is_null($url))
			$url = $this->current(true);

		$parts = $this->parse($url);
		if(!is_array($parts))
			return $parts;

		return $key ? $parts[$key] : $parts;
	}

	public function isValid($url){
		$url = $this->sanitize($url);
		return (filter_var($url, FILTER_VALIDATE_URL) !== false);
	}

	public function isAlien($url, $strict = true){
		$local = wp_validate_redirect($this->sanitize($url), null);
		return is_null($local) ? true : false;
	}

	public function sanitizeUrl($url){
		_deprecated_function( __FUNCTION__, '1.0.0', 'sanitize()' );
		return $this->sanitize($url);
	}

	public function sanitize($url){
		return esc_url_raw($url);
	}

	public function escape($url){
		return esc_url($url);
	}

	public function current($full_url = false){
		return $full_url ? $this->current_request->fullUrl() : $this->current_request->url();
	}

	public function path($url = null, $unslash = false){
		return $unslash
					? trimslashes( $this->parts($url, 'path') )
					: $this->parts($url, 'path');
	}

	public function uri($url = null){
		return is_null($url) ? $this->current_request->uri() : $this->parts($url, 'uri');
	}

	public function baseslashit($path, $ignore_if_fullurl = true){
		if($ignore_if_fullurl && strpos($path, 'http') === 0)
			return $url;
		return $path && $path[0] == '/' ? $path : '/' . $path;
	}

	public function unbaseslashit($path){
		return $path && $path[0] == '/' ? substr($path, 1) : $path;
	}

	public function trailslashit($path, $check_for_filepath_or_querystr = true){
		if($check_for_filepath_or_querystr){
			if($this->parts($path, 'query') )
				return $path;
			if( pathinfo( $path, PATHINFO_EXTENSION ) )
				return $path;
		}
		return trailingslashit($path);
	}

	public function untrailslashit($path){
		return untrailingslashit($path);
	}

	public function stripslashit($path){
		return $this->untrailslashit( $this->unbaseslashit( $path ) );
	}

	public function unslashit( $path ){
		return $this->stripslashit($path);
	}

	public function wrapslashit($path, $strict = true){
		return $this->trailslashit( $this->baseslashit( $path, $strict ), $strict );
	}

	public function setQuery($url, $args = []){
		if( $q = $this->parts($url, 'query') ){
			$url = str_replace($q, '', $url );
		}
		return $this->addQuery( $url, $args );
	}

	public function addQuery($url, array $args = [], $encode = true){
		if(empty($args))
			return $url;

		return $this->addQueryArg( $args, $url, $encode );
	}

	public function addQueryArg($key, $value = null, $url = null, $encode = true){
		if( is_array($key) ){
			if($url || (is_null($url) && $encode) )
				$key = urlencode_deep( $key );

			$url = is_null($value) ? $this->current(true) : $value;
			return add_query_arg($key, $url );
		}else{
			$url = is_null($url) ? $this->current(true) : $url;
			if( $encode )
				$value = urlencode($value);

			return add_query_arg( $key, $value, $url );
		}
	}

	public function removeQueryArg($keys, $url = null){
		setifnull( $url, $this->current(true) );
		return remove_query_arg( $keys, $url);
	}

	public function to($path = null, $query = null, $scheme = null) {
		setifnull($path, '');
		if(is_numeric($path) && (int) $path == $path ){
			$query['page_id'] = $path;
			$path = '';
		}
		$url = $query
			? $this->addQuery( site_url( $path, $scheme ), $query )
			: site_url( $path, $scheme );
		return $url;
	}

	/* PROXY */
	public function route($name, array $args = null){
		return $this->router->url($name, (array) $args);
	}

	public function isAdmin($url = null){
		setifnull($url, $this->current());
		$path = $this->parts($url, 'path');
		$admin_path = $this->parts( admin_url(), 'path' );
		return strpos( ltrimslash($path), ltrimslash($admin_path) ) === 0 ? true : false;
	}

	public function home($query = null, $scheme = null){
		return $query ? $this->addQuery( home_url('/', $scheme), $query )
					: home_url('/', $scheme);
	}

	public function admin($path = null, $query = null, $scheme = 'admin' ){
		setifnull($path, '/');
		return $query
			? $this->addQuery( admin_url( $path, $scheme ), $query )
			: admin_url( $path, $scheme );
	}

	public function cron($query = null, $scheme = 'admin'){
		return $this->to('wp-cron.php', $query, $scheme);
	}

	public function ajax($query = null, $scheme = 'admin'){
		return $this->admin('admin-ajax.php', $query, $scheme);
	}

	public function isAjax($url = null){
		$path = $this->parts($url, 'path');
		$ajax_path = $this->parts( $this->ajax(), 'path' );
		return strpos( ltrimslash($path), ltrimslash($ajax_path) ) === 0 ? true : false;
	}

	public function isCron($url = null){
		$path = $this->parts($url, 'path');
		$cron_path = $this->parts( $this->cron(), 'path' );
		return strpos( ltrimslash($path), ltrimslash($cron_path) ) === 0 ? true : false;
	}

	public function setScheme($url, $scheme = null){
		return set_url_scheme($url, $scheme);
	}

	public function redirect($location, $status = null, $exit = true){
		setifnull($status, 302);
		wp_redirect($location, $status);
		if($exit) exit;
	}

	public function safeRedirect($location, $status = null, $exit = true){
		setifnull($status, 302);
		wp_safe_redirect($location, $status);
		if($exit) {
			exit;
		}
	}

	public function redirectTo($path = null, $query = null, $status = null, $exit = true){
		$this->redirect($this->to($path, $query) , $status, $exit);
	}

	public function safeRedirectTo($path = null, $query = null, $status = null, $exit = true){
		$this->safeRedirect($this->to($path, $query) , $status, $exit);
	}

	public function validateRedirect($location, $default = '')
	{

		if( function_exists('wp_validate_redirect') )
			return wp_validate_redirect($location, $default);

		$location = trim( $location );
		// browsers will assume 'http' is your protocol, and will obey a redirect to a URL starting with '//'
		if ( substr($location, 0, 2) == '//' )
			$location = 'http:' . $location;

		// In php 5 parse_url may fail if the URL query part contains http://, bug #38143
		$test = ( $cut = strpos($location, '?') ) ? substr( $location, 0, $cut ) : $location;

		// @-operator is used to prevent possible warnings in PHP < 5.3.3.
		$lp = @parse_url($test);

		// Give up if malformed URL
		if ( false === $lp )
			return $default;

		// Allow only http and https schemes. No data:, etc.
		if ( isset($lp['scheme']) && !('http' == $lp['scheme'] || 'https' == $lp['scheme']) )
			return $default;

		// Reject if certain components are set but host is not. This catches urls like https:host.com for which parse_url does not set the host field.
		if ( ! isset( $lp['host'] ) && ( isset( $lp['scheme'] ) || isset( $lp['user'] ) || isset( $lp['pass'] ) || isset( $lp['port'] ) ) ) {
			return $default;
		}

		// Reject malformed components parse_url() can return on odd inputs
		foreach ( array( 'user', 'pass', 'host' ) as $component ) {
			if ( isset( $lp[ $component ] ) && strpbrk( $lp[ $component ], ':/?#@' ) ) {
				return $default;
			}
		}

		$wpp = parse_url($this->home());

		$allowed_hosts = [array($wpp['host'])];

		if ( isset($lp['host']) && ( !in_array($lp['host'], $allowed_hosts) && $lp['host'] != strtolower($wpp['host'])) )
			$location = $default;

		return $location;
	}

	public function sanitizeRedirect($location)
	{
		if( function_exists('wp_sanitize_redirect') )
			return wp_sanitize_redirect($location);

		$regex = '/
		(
			(?: [\xC2-\xDF][\x80-\xBF]        # double-byte sequences   110xxxxx 10xxxxxx
			|   \xE0[\xA0-\xBF][\x80-\xBF]    # triple-byte sequences   1110xxxx 10xxxxxx * 2
			|   [\xE1-\xEC][\x80-\xBF]{2}
			|   \xED[\x80-\x9F][\x80-\xBF]
			|   [\xEE-\xEF][\x80-\xBF]{2}
			|   \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
			|   [\xF1-\xF3][\x80-\xBF]{3}
			|   \xF4[\x80-\x8F][\x80-\xBF]{2}
		){1,40}                              # ...one or more times
		)/x';

		$urlencode_matched = function ($matches)
				{
					return urlencode( $matches[0] );
				};



		$location = preg_replace_callback( $regex, $urlencode_matched, $location );
		$location = preg_replace('|[^a-z0-9-~+_.?#=&;,/:%!*\[\]()@]|i', '', $location);
		$location = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $location );

		// remove %0d and %0a from location
		$strip = array('%0d', '%0a', '%0D', '%0A');
		$count = 1;
		while ( $count ) {
			$location = str_replace( $strip, '', $location, $count );
		}

		return $location;
	}

	public function reloadCurrent( $query = true, $status = 302 ){
		$this->current_request->reload( $query, $status );
	}

	public function hasEndpoint($name){
		return static::hasMacro(camel_case($name));
	}

	public function get($endpoint, $args = []){
		$endpoint = camel_case( $endpoint );
		return $this->__call( $endpoint, $args );
	}

}