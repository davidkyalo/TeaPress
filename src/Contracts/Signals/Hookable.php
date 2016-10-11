<?php

namespace TeaPress\Contracts\Signals;

interface Hookable extends Emitter {

	/**
	* Bind the given callback to the specified event hook.
	*
	* @param  array|string					$event
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	* @param  bool							$once
	*
	* @return bool
	*/
	public static function on($event, $callback, $priority = null, $accepted_args = null, $once = null);


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
	public static function onceOn($event, $callback, $priority = null, $accepted_args = null);


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
	public static function addAction($tag, $callback, $priority = null, $accepted_args = null, $once = null);

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
	public static function addFilter($tag, $callback, $priority = null, $accepted_args = null, $once = null);

	/**
	* Unbind the given callback from the specified event
	*
	* @param  string						$event
	* @param  \Closure|array|string			$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public static function off($event, $callback, $priority = null);

	/**
	* Removes a callback from a specified action hook.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public static function removeAction($tag, $callback, $priority = null);

	/**
	* Removes a callback from a specified filter hook.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public static function removeFilter($tag, $callback, $priority = null);

}