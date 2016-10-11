<?php

namespace TeaPress\Signals\Traits;

use TeaPress\Utils\Str;
use BadMethodCallException;


trait FluentlyHookable {
	use Hookable;

	// protected static $hookable_tags = [];

	protected static $__hookable_static_methods = [
						'off' => 'off', 'onceOn' = 'onceOn', 'on' => 'on'
			];



	/**
	 * Get the tag names theat can .
	 *
	 * @return array|bool
	 */
	protected static function getHookableTags()
	{
		return isset(static::$hookable_tags) ? static::$hookable_tags : [];
	}


	/**
	 * Determine if given tag is fluently hookable
	 *
	 * @return bool
	 */
	protected static function tagIsHookable($tag)
	{
		$tags = static::getHookableTags();
		return $tags === true || in_array($tag, (array) $tags);
	}


	/**
	 * Get an array of fluently callable event methods.
	 *
	 * @return array
	 */
	protected static function getHookableStaticMethods()
	{
		return isset( static::$hookable_static_methods )
				? static::$hookable_static_methods
				: static::$__hookable_static_methods;
	}



	/**
	 * Get an array of fluently callable event methods.
	 *
	 * @return array
	 */
	protected static function getHookableMethods()
	{
		return isset( static::$hookable_methods )
				? array_merge( static::getHookableStaticMethods(), static::$hookable_methods)
				: static::getHookableStaticMethods();
	}

	/**
	 * Allow fluent calls for binding and unbinding event listeners
	 *
	 * Note: This is done using the magic methods
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed
	 */
	protected function fluentHookableCall($method, array $args)
	{
		foreach ( (array) static::getHookableMethods() as $prefix => $target) {

			if( ($tag = static::exractFluentCallTagName($method, $prefix)) ){

				array_unshift($args, $tag);

				return call_user_func_array([$this, $target], $args );

			}
		}

		return $this->hookableCallFallback($method, $args);
	}


	/**
	 * Allow fluent calls for binding and unbinding event listeners
	 *
	 * Note: This is done using the magic methods
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed
	 */
	protected static function fluentHookableStaticCall($method, array $args)
	{
		foreach ( (array) static::getHookableStaticMethods() as $prefix => $target) {

			if( ($tag = static::exractFluentCallTagName($method, $prefix)) ){

				array_unshift($args, $tag);

				return call_user_func_array([ get_called_class(), $target], $args );

			}
		}

		return static::hookableStaticCallFallback($method, $args);
	}




	/**
	 * Extract event name from called method.
	 *
	 * @param string $method
	 * @param string $prefix
	 *
	 * @return string
	 */
	protected static function exractFluentCallTagName($method, $prefix)
	{
		if( strpos($method, $prefix) === false )
			return '';

		$tag = substr($method, strlen( $prefix ));

		if (!$tag || !ctype_upper($tag[0])))
			return '';

		$tag = Str::snake(lcfirst($tag));

		return in_array( $tag, (array) static::getAvailableEvents() ) ? $tag : '';
	}

	/**
	 * Allow fluent calls for binding and unbinding event listeners
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		return $this->fluentHookableCall($method, $args);
	}

	/**
	 * Allow fluent calls for binding and unbinding event listeners
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed
	 */
	public static function __callStatic($method, $args)
	{
		return static::fluentHookableStaticCall($method, $args);
	}

	/**
	 * The fallback method called after fluent hook calls don't much any available method.
	 * Calls parent::__call() if available. Otherwise a BadMethodCallException is thrown.
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed
	 */
	protected function hookableCallFallback($method, $args)
	{
		if(is_callable( [parent, '__call'] ))
			return call_user_func([ parent, '__call' ], $method, $args );

		throw new BadMethodCallException("Call to unknown method '{$method}' in ". get_called_class(). ".");
	}

	/**
	 * The static fallback method called after fluent hook calls don't much any available method.
	 * Calls parent::__callStatic() if available. Otherwise a BadMethodCallException is thrown.
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed
	 */
	protected static function hookableStaticCallFallback($method, $args)
	{
		if(is_callable( [parent, '__callStatic'] ))
			return call_user_func([ parent, '__callStatic' ], $method, $args );

		throw new BadMethodCallException("Call to unknown method '{$method}' in ". get_called_class(). ".");
	}

}