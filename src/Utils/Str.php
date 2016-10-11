<?php

namespace TeaPress\Utils;

use Illuminate\Support\Str as Base;

class Str extends Base {

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

	public static function phrase($text, $separator = ' '){
		$text = static::slug($text, '_', false, true);
		$text = static::snake(static::studly( $text ));
		return str_replace('_', $separator, $text);
	}

	public static function pad($text, $size, $value){
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
