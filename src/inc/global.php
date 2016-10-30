<?php

use TeaPress\Utils\Arr;
use TeaPress\Utils\Str;
use TeaPress\Carbon\Carbon;
use TeaPress\Core\Application;
use TeaPress\Carbon\TimeDelta;


function teapress($abstract = null, $parameters = []){
	$container = Application::getInstance();
	return $abstract ? $container->make($abstract, $parameters) : $container;
}
/**
 * Wrap the given callable or class such that its dependencies will be injected when executed.
 *
 * @param  \Closure|string|array  $item
 * @param  array  $parameters
 * @return \Closure
 */
function inject($item, array $parameters = []){
	return function () use ($item, $parameters) {
		$app = teapress();
		if(is_string($item) && !is_callable($item) && strpos($item, '@') === false)
			if( class_exists($item) || $app->bound($item) )
				return $app->make( $item, $parameters );

		return $app->call($callable, $parameters);
	};
}


if ( !function_exists('responses') ) :

	function responses(){
		return teapress("\TeaPress\Http\Response\Factory");
	}

endif;

if ( !function_exists('make_response') ) :

	function make_response($content = '', $status = 200, $headers = []){
		return responses()->make( $content, $status, $headers );
	}

endif;

function is_exception($thing, $type = null){
	setifnull($type, Exception::class);
	return ($thing instanceof $type);
}

function is_assoc_array(array $array){
	return Arr::isAssoc($array);
}

/**
 * Transform array's values and keys.
 * preserve
 * @param  array $arr
 * @param  \Closure $transformer
 * @return array $arr
 */
function array_transform(&$original, $transformer, $preserve_original = true){
	$transformed = [];
	$acopy = $original;
	foreach ($acopy as $key => $value) {
		$newkey = $transformer($value, $key, $original);
		if(!is_null($newkey)){
			$transformed[$newkey] = $value;
		}
	}

	if(!$preserve_original){
		$original = $transformed;
		return $original;
	}
	return $transformed;
}

function as_datetime($value, $strict = false, $fallback = 'Y-m-d H:i:s'){
	return Carbon::cast($value, $strict, $fallback);
}


// function get_instanceof_datetime($value, $create_new = true) {
// 	_deprecated_function(__FUNCTION__, '1.0.0', 'TeaPress\Carbon\Carbon::cast()');
// 	return Carbon::cast($value);
// }

// function as_instanceof_datetime($value){
// 	_deprecated_function(__FUNCTION__, '1.0.0', 'TeaPress\Carbon\Carbon::cast()');
// 	return Carbon::cast($value);
// }

function is_timedelta($thing){
	return ( $thing instanceof TimeDelta);
}


function array_random($arr, $len = 1, $keep_keys = false) {
	$alen = count($arr);
	$shuffles = mt_rand(2,5);
	while ($shuffles > 0) {
		shuffle($arr);
		$shuffles -=1;
	}
	$omax = $alen - $len;
	if($omax < 1){
		return $arr;
	}
	$offs = mt_rand(0, $omax);
	return array_slice($arr, $offs, $len, $keep_keys);
}


function array_keys_rand($arr, $len = 1) {
	$akeys = array_keys($arr);
	$alen = count($akeys);

	$shuffles = mt_rand(2,5);
	while ($shuffles > 0) {
		shuffle($akeys);
		$shuffles -=1;
	}
	$omax = $alen - $len;
	if($omax < 1){
		return $akeys;
	}
	$offset = mt_rand(0, $omax);
	return array_slice($akeys, $offset, $len);
}

function trimslash($path, $slash = '/')
{
	return trim($path, $slash);
}

function trimslashes($path, $slash = '/')
{
	return trimslash($path, $slash);
}
function ltrimslash($path, $slash = '/')
{
	return ltrim($path, $slash);
}
function rtrimslash($path, $slash = '/')
{
	return rtrim($path, $slash);
}

function wrapslash($path, $slash = '/')
{
	return  lwrapslash( rwrapslash($path, $slash), $slash );
}

function lwrapslash($path, $slash = '/')
{
	return $slash . ltrimslash($path, $slash);
}

function rwrapslash($path, $slash = '/')
{
	return  rtrimslash($path, $slash) . $slash;
}



/**
 * Create a clean path string by joining all the given fragments with a single separator ('/')
 *
 * @param string|array	$fragments Path fragments
 * @param string		$slash Path separator to use if fragments is Array
 *
 * @return string
 */
function join_paths_classic(){
	$args = func_get_args();
	$nargs = func_num_args();
	$slash = $nargs === 2 && is_array($args[0]) ? $args[1] : '/';
	$fragments = ($nargs === 1 || $nargs === 2) && is_array($args[0])
				? $args[0] : $args;

	$first = array_shift($fragments);
	$last = array_pop($fragments);
	$path = '';
	foreach ($fragments as $key => $fragment) {
		$path .= $slash . trimslash($fragment, $slash);
	}
	return rtrimslash($first, $slash) . rtrimslash($path, $slash) . lwrapslash($last, $slash);
}

/**
 * Safely join the given path fragments with non-repeating slashes
 *
 * @param strings|array	...$fragments
 * @return string
 */
function join_paths(...$fragments)
{
	if(count($fragments) === 1 && is_array($fragments[0])){
		$fragments = $fragments[0];
	}

	return Str::join(
			DIRECTORY_SEPARATOR,
			array_filter($fragments, function($fragment){
				return $fragment !== "";
			})
		);
}

/**
 * Safely join the given uri fragments with non-repeating slashes
 *
 * @param strings|array	...$fragments
 * @return string
 */
function join_uris(...$fragments)
{
	if(count($fragments) === 1 && is_array($fragments[0])){
		$fragments = $fragments[0];
	}

	return Str::join('/', array_filter($fragments, function($fragment){
		return $fragment !== "";
	}));
}


function long_rand($len) {
	$max = mt_getrandmax();
	$min = (integer) ($max/1000);
	$rand = ''.mt_rand($min, $max);
	$rlen = strlen($rand);
	if($rlen <= $len){
		return $rand . long_rand($len - $rlen);
	}else{
		return substr($rand, 0, $len);
	}

}

function is_message_bag($thing){
	return is_wp_error($thing);
}


function abspath_check($exit = true){
	$isdefined = defined( 'ABSPATH' );

	if ( !$isdefined && $exit )
		exit;

	return $isdefined;
}


function is_ajax(){
	if (defined('DOING_AJAX') && DOING_AJAX){
		return true;
	}else{
		return false;
	}
}

function is_cli()
{
	return php_sapi_name() == 'cli';
}


/**
 * Return $other if $condition
 *
 * @param mixed						&$thing
 * @param mixed|void				$other
 * @param bool|Closure|null|mixed	$condition The condition to check. If null $thing is used as condition.
 *
 * @return mixed
 */
function iif($thing, $other, $condition = null){
	$passed = is_bool($condition)
	? $condition
	: (is_null($condition) ? $thing : value($condition));
	return $passed ? $other : $thing;
}

/**
 * Change value of $thing to $other if $condition
 *
 * @param mixed						&$thing
 * @param mixed|void				$other
 * @param bool|Closure|null|mixed	$condition The condition to check. If null $thing is used as condition.
 *
 * @return mixed
 */
function setif(&$thing, $other, $condition = null){
	$thing = iif( $thing, $other, $condition );
	return $thing;
}

/**
 * Return $other if NOT $condition
 *
 * @param mixed						$thing
 * @param mixed|void				$other
 * @param bool|Closure|null|mixed	$condition The condition to check. If null $thing is used as condition.
 *
 * @return mixed
 */
function ifnot($thing, $other, $condition = null){
	$passed = is_bool($condition)
	? $condition
	: (is_null($condition) ? $thing : value($condition));
	return !$passed ? $other : $thing;
}


/**
 * Change value of $thing to $other if NOT $condition
 *
 * @param mixed						&$thing
 * @param mixed|void				$other
 * @param bool|Closure|null|mixed	$condition The condition to check. If null $thing is used as condition.
 *
 * @return mixed
 */
function setifnot(&$thing, $other, $condition = null){
	$thing = ifnot( $thing, $other, $condition );
	return $thing;
}


/**
 * Return $other if $thing === null
 *
 * @param mixed	$thing
 * @param mixed	$other
 *
 * @return mixed
 */
function ifnull($thing, $other){
	return is_null($thing) ? $other : $thing;
}



/**
 * Change value of $thing to $other if $thing === null
 *
 * @param mixed	&$thing
 * @param mixed	$other
 *
 * @return mixed
 */
function setifnull(&$thing, $other){
	$thing = ifnull( $thing, $other );
	return $thing;
}



/**
 * Return $other if $thing is empty
 *
 * @param mixed	$thing
 * @param mixed	$other
 *
 * @return mixed
 */
function ifempty($thing, $other){
	return empty($thing) ? $other : $thing;
}


/**
 * Change value of $thing to $other if $thing is empty
 *
 * @param mixed	&$thing
 * @param mixed $other
 *
 * @return mixed
 */
function setifempty(&$thing, $other){
	$thing = ifempty( $thing, $other );
	return $thing;
}

