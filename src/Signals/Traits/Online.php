<?php

namespace TeaPress\Signals\Traits;

use Closure;
use BadMethodCallException;
use TeaPress\Contracts\Signals\Signals;

trait Online {

	/**
	 * @var \TeaPress\Contracts\Signals\Signals
	 */
	protected static $_signals;

	/**
	* Get the signals instance
	*
	* @return \TeaPress\Contracts\Signals\Signals
	*/
	public static function getSignals()
	{
		return static::$_signals;
	}

	/**
	* Set the signals instance
	*
	* @param  \TeaPress\Contracts\Signals\Signals	$signals
	*
	* @return void
	*/
	public static function setSignals(Signals $signals)
	{
		static::$_signals = $signals;
	}

}