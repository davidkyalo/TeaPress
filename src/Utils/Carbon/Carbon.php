<?php

namespace TeaPress\Utils\Carbon;

use DateTime;
use Carbon\Carbon as Base;
use InvalidArgumentException;

class Carbon extends Base {

	public static function cast($value, $strict = false, $fallback = 'Y-m-d H:i:s'){
		if ($value instanceof Base)
			return $value;

		if ($value instanceof DateTime)
			return static::instance($value);

		if (is_numeric($value))
			return static::createFromTimestamp($value);

		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value))
			return static::createFromFormat('Y-m-d', $value)->startOfDay();

		if($fallback)
			return static::cast( DateTime::createFromFormat( $fallback, $value ), $strict, null );


		$dt = static::createFromString($value, null, false);

		if( $dt || !$strict )
			return $dt;

		throw new InvalidArgumentException("Unable to cast date/time. Don't understand the provided value.");

	}

	public static function createFromString($value, $tz = null, $strict = true){
		if( ( $time = strtotime( (string) $value ) ) || !$strict )
			return $time ? static::createFromTimestamp($time, $tz) : false;

		throw new InvalidArgumentException("The date/time string should be of a format as required by 'strtotime()'.");
	}

}