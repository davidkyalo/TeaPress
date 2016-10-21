<?php

namespace TeaPress\Signals\Traits;

use Closure;
use BadMethodCallException;
use TeaPress\Contracts\Signals\Hub as Signals;

trait Online {

	/**
	 * @var \TeaPress\Contracts\Signals\Hub
	 */
	protected static $signals_hub;

	/**
	* Get the signals hub instance
	*
	* @return \TeaPress\Contracts\Signals\Hub
	*/
	public static function getSignals()
	{
		return static::$signals_hub;
	}

	/**
	* Set the signals hub instance
	*
	* @param  \TeaPress\Contracts\Signals\Hub		$hub
	*
	* @return void
	*/
	public static function setSignals(Signals $hub)
	{
		static::$signals_hub = $hub;
	}

}