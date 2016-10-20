<?php

namespace TeaPress\Signals\Traits;

use Closure;
use BadMethodCallException;
use TeaPress\Contracts\Signals\Hub as HubContract;

trait Emitter
{
	use Online;

	/**
	* Get this emitter's events namespace.
	*
	* @return string|null
	*/
	public static function setSignalsNamespace($namespace)
	{
		static::$signals_namespace = $namespace;
	}

	/**
	* Get this emitter's events namespace.
	*
	* @return string|null
	*/
	public static function getSignalsNamespace()
	{
		return isset( static::$signals_namespace ) ? static::$signals_namespace : null;
	}

	/**
	*  Get the event's array name used for binding with the dispatcher.
	*
	* @param  string		$tag
	*
	* @return string|array
	*/
	public static function getHookTag( $tag )
	{
		$namespace = static::getSignalsNamespace();
		return !is_null($namespace) ? $namespace .':'.$tag : [ get_called_class(), $tag ];
	}


	/**
	* Bind the given callback to the specified event.
	*
	* @param  string						$hook
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	* @param  bool							$once
	*
	* @return bool
	*/
	protected static function bindCallback($hook, $callback, $priority = null, $accepted_args = null, $once = null)
	{
		if($hub = static::getSignals()){
			$hub->bind( static::getHookTag($hook), $callback, $priority, $accepted_args, $once);
			return true;
		}

		return false;
	}


	/**
	* Unbind the given callback to the specified event.
	*
	* @param  string						$hook
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	protected static function unbindCallback($hook, $callback, $priority = null)
	{
		if($hub = static::getSignals()){
			return $hub->unbind( static::getHookTag($hook), $callback, $priority);
		}

		return false;
	}


	// /**
	//  * Get the number of times an action has been triggered.
	//  *
	//  * @param  string		$hook
	//  *
	//  * @return int
	//  */
	// public function didAction($hook)
	// {
	// 	if($hub = static::getSignals())
	// 		return $hub->didAction( static::getHookTag( $hook ) );

	// 	return 0;
	// }

	// /**
	//  *  Get the number of times a filter has been evaluated
	//  *
	//  * @param  string		$hook
	//  *
	//  * @return int
	//  */
	// public function didFilter($hook)
	// {
	// 	if($hub = static::getSignals())
	// 		return $hub->didFilter( static::getHookTag( $hook ) );

	// 	return 0;
	// }

	/**
	* Execute callbacks hooked the specified action.
	*
	* @param  string			$hook
	* @param  array				$payload
	* @param  bool				$halt
	*
	* @return mixed
	*/
	protected function emitSignal($hook, ...$payload)
	{
		if( $hub = static::getSignals())
		{
			$tag = static::getHookTag($hook);

			if(!in_array($this, $payload))
				$payload[] = $this;

			return $hub->emitSignalWith($tag, $payload, false);
		}
	}


	/**
	* Execute callbacks hooked the specified action until the first non-null response is returned.
	*
	* @param  string			$hook
	* @param  array				$payload
	* @param  bool				$halt
	*
	* @return mixed
	*/
	protected function emitSignalUntil($hook, ...$payload)
	{
		if( $hub = static::getSignals())
		{
			$tag = static::getHookTag($hook);

			if(!in_array($this, $payload))
				$payload[] = $this;

			return $hub->emitSignalWith($tag, $payload, true);
		}
	}


	/**
	* Evaluate the final value by executing listeners hooked the specified emitter event.
	*
	* If $value is array and $payload is false, the value array is used as the payload with
	* the first element as the value to evaluate.
	*
	* Calls \TeaPress\Hooks\Hub::evaluate()
	*
	* @param  string						$tag
	* @param  mixed 						$item
	* @param  array 						$payload
	*
	* @return mixed
	*/
	protected function applyFilters($hook, $item = null, ...$payload)
	{
		if( $hub = static::getSignals()){

			$tag = static::getHookTag($hook);

			if(!in_array($this, $payload))
				$payload[] = $this;

			return $hub->applyFilters( $tag, $item, ...$payload);
		}

		return $item;
	}

}