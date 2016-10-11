<?php

namespace TeaPress\Signals\Traits;

use BadMethodCallException;


trait Hookable {

	use Emitter;

	/**
	* Bind the given callback to the specified event hook.
	*
	* @param string							$hook
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	* @param  bool							$once
	*
	* @return bool
	*/
	public static function on($hook, $callback, $priority = null, $accepted_args = null, $once = null)
	{
		return static::bindCallback($hook, $callback, $priority, $accepted_args, $once );
	}


	/**
	* Bind the given callback to the specified event once.
	*
	* The callback will be executed once after which it will be removed.
	*
	* @param  string						$event
	* @param  \Closure|array|string			$callback
	* @param  int|null						$priority
	* @param  int|null						$accepted_args
	*
	* @return bool
	*/
	public static function onceOn($event, $callback, $priority = null, $accepted_args = null)
	{
		return static::on( $event, $callback, $priority, $accepted_args, true );
	}

	/**
	* Bind an action callback.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	* @param  bool							$once
	*
	* @return bool
	*/
	public static function addAction($tag, $callback, $priority = null, $accepted_args = null, $once = null)
	{
		return static::on($tag, $callback, $priority, $accepted_args, $once);
	}


	/**
	* Bind a filter callback.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	* @param  bool							$once
	*
	* @return static
	*/
	public static function addFilter($tag, $callback, $priority = null, $accepted_args = null, $once = null)
	{
		return static::on($tag, $callback, $priority, $accepted_args, $once);
	}

	/**
	* Unbind the given callback from the specified event
	*
	* @param  string						$hook
	* @param  \Closure|array|string			$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public static function off($hook, $callback, $priority = null)
	{
		return static::unbindCallback($hook, $callback, $priority);
	}

	/**
	* Removes a callback from a specified action hook.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public static function removeAction($tag, $callback, $priority = null)
	{
		return static::off($tag, $callback, $priority);
	}


	/**
	* Removes a callback from a specified filter hook.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public static function removeFilter($tag, $callback, $priority = null)
	{
		return static::off($tag, $callback, $priority);
	}
}