<?php

namespace TeaPress\Utils;

use Illuminate\Support\Str as Base;

class Str extends Base
{
	const STRIP_BOTH = 0;
	const STRIP_LEFT = -1;
	const STRIP_RIGHT = 1;

	/**
	 * Prefix a string with a single instance of a given value.
	 *
	 * @param  string  $string
	 * @param  string  $prefix
	 * @return string
	 */
	public static function begin($string, $prefix)
	{
		return $prefix.static::lstrip($string, $prefix);
	}

	/**
	 * Remove all whitepaces from text
	 *
	 * @param string $text
	 * @param string $whitespace
	 *
	 * @return string
	*/
	public static function compact($text, $whitespace = ' ')
	{
		return  trim( preg_replace('/\s+/', $whitespace, $text) );
	}


	/**
	 * Cap a string with a single instance of a given value.
	 *
	 * @param  string  $string
	 * @param  string  $cap
	 * @return string
	 */
	public static function finish($string, $cap)
	{
		return static::rstrip($string, $cap).$cap;

		// $quoted = preg_quote($cap, '/');

		// return preg_replace('/(?:'.$quoted.')+$/u', '', $value).$cap;
	}


	/**
	 * Join provided pieces with single instances of the value (glue)
	 *
	 * @param  string 			$glue
	 * @param  strings|array 	...$pieces
	 *
	 * @return string
	 */
	public static function join($glue, ...$pieces)
	{
		if(count($pieces) === 1 && is_array($pieces[0])){
			$pieces = $pieces[0];
		}

		$joined = (string) array_shift($pieces);

		foreach ($pieces as $piece) {
			$joined = static::finish($joined, $glue) . static::lstrip($piece, $glue);
		}

		return $joined;
	}

	/**
	 * Join provided pieces with single instances of the value (glue)
	 *
	 * @param  string 			$glue
	 * @param  strings|array 	...$pieces
	 *
	 * @return string
	 */
	public static function _joinFuture($glue, ...$pieces)
	{
		if(count($pieces) === 1 && is_array($pieces[0]))
			$pieces = array_values($pieces[0]);

		$wrapped = '__{_{__'.$glue.'__}_}__';


		$text = join($wrapped, $pieces);

		$qw = preg_quote($wrapped, '/');

		// $qa = preg_quote($wrapped, '/');

		// $text = preg_replace('/(?:'. $qw.'){2,}/u', '', $text);
		$text = preg_replace('/(?:'. $qw.'){2,}/u', '', $text);

		return preg_replace('/(?:'. $qw.')+/u', $glue, $text);

	}

	/**
	 * Minify the text to a single line and remove whitespaces
	 *
	 * @param string $text
	 * @param string $linesep
	 * @param string $whitespace
	 *
	 * @return string
	*/
	public static function minify($text, $linesep = ' ',  $whitespace = ' ')
	{
		$search = ["\r\n", "\n", "\r"];
		$replace = array_fill( 0, count($search), $linesep);
		$service[] = "\t";
		$replace[] = " ";

		$text = str_replace($search, $replace, $text);

		return static::compact($text, $whitespace);
	}

	/**
	 * Generate a URL friendly "slug" from a given string.
	 *
	 * @param  string  $title
	 * @param  string  $separator
	 * @return string
	 */
	public static function slug($title, $separator = '-', $remove_invalid = true, $preserve_case = false)
	{
		$title = static::ascii($title);

		// Convert all dashes/underscores into separator
		$flip = $separator == '-' ? '_' : '-';

		$title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);

		$invalids = $remove_invalid ? '' : $separator;
		// Remove all characters that are not the separator, letters, numbers, or whitespace.
		$title = $preserve_case ? $title :  mb_strtolower($title);
		$title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', $invalids, $title);

		// Replace all separator characters and whitespace by a single separator
		$title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);

		return trim($title, $separator);
	}


	/**
	 * Strip a substring from the specified part(s) of a string.
	 * If value is not provided, beginning or/and trialling whitespaces are striped.
	 *
	 * You can set the max number of occurrences to be stripped by providing the limit.
	 * By default, all occurrences are stripped.
	 *
	 * The part(s) could be one of the following:
	 * 		Str::STRIP_LEFT  //Beginning of string. eq to -1
	 * 		Str::STRIP_RIGHT //End of string. eq to 1
	 * 		Str::STRIP_BOTH //Both sides of the string. eq to 0
	 *
	 * If a part is not specified, Str::STRIP_BOTH which is equal to 0 is used.
	 *
	 * @param  string  	$string
	 * @param  string  	$value
	 * @param  int  	$limit
	 * @param  int  	$parts
	 * @return string
	 */
	public static function strip($string, $value = ' ', $limit = -1, $parts = self::STRIP_BOTH)
	{
		/**
		 * If the substring being replaced is a single character or empty string,
		 * we can simply use the 'trim' functions to avoid regex overhead.
		*/
		if(static::length($value) < 2 && $limit == -1){
			switch ($parts) {
				case self::STRIP_LEFT:
					return ltrim($string, $value);

				case self::STRIP_RIGHT:
					return rtrim($string, $value);

				default:
					return trim($string, $value);
			}
		}

		$quoted = preg_quote($value, '/');

		$limit = $limit > 0 ? '{1,'.$limit.'}' : '+';

		switch ($parts) {
			case self::STRIP_LEFT:
				return preg_replace('/^(?:'.$quoted.')'.$limit.'/u', '', $string);

			case self::STRIP_RIGHT:
				return preg_replace('/(?:'.$quoted.')'.$limit.'$/u', '', $string);

			default:
				$start = '^(?:'.$quoted.')'.$limit.'';
				$end = '(?:'.$quoted.')'.$limit.'$';
				return preg_replace('/('.$start.'|'.$end.')/u', '', $string);
		}

		// if($parts === static::STRIP_BOTH || $parts === static::STRIP_LEFT)
		// 	$string = $trim ? ltrim($string, $quoted) : preg_replace('/^(?:'.$quoted.')'.$limit.'/u', '', $string);

		// if($parts === static::STRIP_BOTH || $parts === static::STRIP_RIGHT)
		// 	$string = preg_replace('/(?:'.$quoted.')'.$limit.'$/u', '', $string);

		// return $string;
	}

	/**
	 * Strip a substring from the beginning of a string.
	 * You can set the max number of occurrences to be stripped by providing the limit.
	 * By default, all occurrences are stripped.
	 *
	 * @param  string  	$string
	 * @param  string  	$value
	 * @param  int  	$limit
	 * @return string
	 */
	public static function lstrip($string, $value = ' ', $limit = -1)
	{
		return static::strip($string, $value, $limit, static::STRIP_LEFT);
	}

	/**
	 * Strip a substring from the end of a string.
	 * You can set the max number of occurrences to be stripped by providing the limit.
	 * By default, all occurrences are stripped.
	 *
	 * @param  string  	$string
	 * @param  string  	$value
	 * @param  int  	$limit
	 * @return string
	 */
	public static function rstrip($string, $value = ' ', $limit = -1)
	{
		return static::strip($string, $value, $limit, static::STRIP_RIGHT);
	}


	/**
	 * Parse the given string into a human readable phrase.
	 *
	 * @param  string  $text
	 * @param  int  $size
	 * @param  string  $value
	 *
	 * @return string
	 */
	public static function phrase($text, $separator = ' ')
	{
		$text = static::slug($text, '_', false, true);
		$text = static::snake(static::studly( $text ));
		return str_replace('_', $separator, $text);
	}

	/**
	 * Pad a string with provided value.
	 *
	 * @param  string  $text
	 * @param  int  $size
	 * @param  string  $value
	 *
	 * @return string
	 */
	public static function pad($text, $size, $value)
	{
		$text = (string) $text;
		$size = (int) $size;
		$value = (string) $value;

		$space = abs($size) - static::length($text);

		if($space < 1)
			return $text;

		$pad = str_repeat( $value, ceil( $space / static::length($value) ) );
		$pad = substr( $pad, 0, $space );
		return $size > 0 ? $text.$pad : $pad.$text;
	}


	/**
	 * Wrap a string with single instances of the given prefix and suffix.
	 * If suffix is not provided, the prefix is used.
	 *
	 * @param  string  $string
	 * @param  string  $value
	 * @return string
	 */
	public static function wrap($string, $prefix, $suffix = null)
	{
		if(is_null($suffix) || $prefix === $suffix)
			return $prefix.static::strip($string, $prefix).$prefix;
		else
			return str::finish(static::begin($string, $prefix), $suffix);
	}


	public static function isbase64($data, $strict = true)
	{
		$recoded = static::tobase64( static::base64Decode($data), ends_with($data, '='), true );
		return $recoded === $data;
	}

	public static function ishex($value)
	{
		return ctype_xdigit($value);
	}

	public static function tobytes($data)
	{
		if(static::ishex($data))
			return hex2bin($data);
		elseif(static::isbase64($data))
			return static::base64Decode($data);
		else
			return $data;
	}

	public static function tohex($data, $asitis = false)
	{
		if(!$asitis)
			$data = static::tobytes($data);

		return bin2hex($data);
	}

	public static function tobase64($data, $pad = false, $asitis = false)
	{
		if(!$asitis)
			$data = static::tobytes($data);
		$data = str_replace(array('+', '/'), array('-', '_'), base64_encode($data));
		return $pad ? $data : rtrim($data, '=');
	}

	public static function base64Decode($data, $strict = true)
	{
		$data = str_replace(array('-', '_'), array('+', '/'), $data);
		return base64_decode($data, $strict);
	}

	public static function bitlen($bytes)
	{
		return mb_strlen($bytes, '8bit');
	}

	public static function chunk($bytes, $start = 0, $len = null, $enc = 'UTF-8')
	{
		return mb_substr($bytes, $start, $len, $enc);
	}

	public static function bitChunk($bytes, $start = 0, $len = null)
	{
		return mb_substr($bytes, $start, $len, '8bit');
	}
}
