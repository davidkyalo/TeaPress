<?php

namespace TeaPress\Signals\Traits;

use Closure;
use TeaPress\Signals\Tag;
use BadMethodCallException;
use TeaPress\Contracts\Signals\Hub as HubContract;

trait Emitter
{
	use Online;

	/**
	* Get this emitter's events namespace.
	*
	* @return string
	*/
	public static function getSignalsNamespace()
	{
		return isset( static::$signals_namespace ) ? static::$signals_namespace : get_called_class();
	}

	/**
	*  Get the event's tag instance.
	*
	* @param  string|TeaPress\Signals\Tag	$tag
	* @return TeaPress\Signals\Tag
	*/
	public static function getSignalTag($tag)
	{
		return $tag instanceof Tag ? $tag : new Tag($tag, static::getSignalsNamespace());
	}

	/**
	* Bind the given callback to the specified event.
	*
	* @param  string						$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null 						$accepted_args
	*
	* @return bool
	*/
	protected static function bindCallback($tag, $callback, $priority = null, $accepted_args = null)
	{
		if($signals = static::getSignals()){
			$signals->bind( static::getSignalTag($tag), $callback, $priority, $accepted_args);
			return true;
		}

		return false;
	}

	/**
	* Unbind the given callback to the specified event.
	*
	* @param  string						$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	protected static function unbindCallback($tag, $callback, $priority = null)
	{
		if($signals = static::getSignals()){
			return $signals->unbind( static::getSignalTag($tag), $callback, $priority);
		}

		return false;
	}

	/**
	* Execute callbacks hooked the specified event.
	*
	* @param  string			$tag
	* @param  array				$payload
	* @param  bool				$halt
	* @return mixed
	*/
	protected static function fireSignal($tag, $payload = [], $halt = false)
	{
		if( $signals = static::getSignals())
			return $signals->fire(static::getSignalTag($tag), $payload, $halt);
	}


	/**
	* Execute callbacks hooked the specified action until the first non-null response is returned.
	*
	* @param  string			$tag
	* @param  array				$payload
	* @return mixed
	*/
	protected static function fireSignalUntil($tag, $payload = [])
	{
		if( $signals = static::getSignals())
			return $signals->until(static::getSignalTag($tag), $payload);
	}


	/**
	* Pass the given item through filters registered under the given tag
	* and return the final result.
	*
	* @param  string						$tag
	* @param  mixed 						$payload
	* @return mixed
	*/
	protected static function doAction($tag, ...$payload)
	{
		if( $signals = static::getSignals()){
			return $signals->fire(static::getSignalTag($tag), $payload);
		}
	}

	/**
	* Pass the given item through filters registered under the given tag
	* and return the final result.
	*
	* @param  string						$tag
	* @param  mixed 						$item
	* @param  array 						$payload
	* @return mixed
	*/
	protected static function applyFilters($tag, $item = null, $payload = [])
	{
		if( $signals = static::getSignals()){
			return $signals->filter(static::getSignalTag($tag), $item, $payload);
		}
		return $item;
	}

}