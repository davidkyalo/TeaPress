<?php

namespace TeaPress\Signals\Traits;

use BadMethodCallException;
use TeaPress\Contracts\Signals\Hub as HubContract;

trait Emitter {

	// protected static $events_namespace;

	/**
	 * @var \TeaPress\Contracts\Signals\Hub
	 */
	protected static $_signals_hub;

	/**
	* Get the signals hub instance
	*
	* @return \TeaPress\Contracts\Signals\Hub
	*/
	public static function getSignalsHub()
	{
		return isset( static::$signals_hub ) && static::$signals_hub
				? static::$signals_hub : static::$_signals_hub;
	}

	/**
	* Set the signals hub instance
	*
	* @param  \TeaPress\Contracts\Signals\Hub		$hub
	*
	* @return void
	*/
	public static function setSignalsHub(HubContract $hub)
	{
		static::$_signals_hub = $hub;
	}

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
		if($hub = static::getSignalsHub()){
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
		if($hub = static::getSignalsHub()){
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
	// 	if($hub = static::getSignalsHub())
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
	// 	if($hub = static::getSignalsHub())
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
	* @return void
	*/
	protected function emitSignal($hook, array $payload = [], $halt = false)
	{
		if( $hub = static::getSignalsHub())
		{
			$tag = static::getHookTag($hook);

			if(!in_array($this, $payload))
				$payload[] = $this;

			$hub->emitSignal($tag, $payload, $halt );
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
	protected function mapItem($tag, $item = null, array $payload = [])
	{
		if( $hub = static::getSignalsHub()){

			$tag = static::getHookTag($tag);

			if(!in_array($this, $payload))
				$payload[] = $this;

			return $hub->mapItem( $tag, $item, $payload);
		}

		return $item;
	}

}