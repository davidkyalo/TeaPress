<?php

namespace TeaPress\Utils\Traits;

use Closure;
use TeaPress\Contracts\Exceptions\BadMethodOrExtensionCall;

trait Extendable {


	protected static $extensions = [];

	public static function extend($method, callable $extension)
	{
		static::$extensions[$method] = $extension;
	}

	public static function hasExtension($method)
	{
		return isset(static::$extensions[$method]);
	}

	protected static function getExtensions(){
		return static::$extensions;
	}

	public static function addExtensions(array $extensions)
	{
		foreach ($extensions as $method => $extension) {
			static::extend($method, $extension);
		}
	}

	protected function callExtension($method, $parameters, $silent = false)
	{
		if( !$this->hasExtension($method) ){
			$exception = new BadMethodOrExtensionCall($method, $this);
			if( !$silent )
				throw $exception;
			return $exception;
		}

		$callback = static::$extensions[$method];
		$parameters = $this->getExtensionParameters($parameters, $method);

		if( $callback instanceof Closure )
			return call_user_func_array( $callback->bindTo($this), $parameters );
		else
			return call_user_func_array($callback, $parameters );
	}

	protected function getExtensionParameters($parameters, $method = null)
	{
		$parameters[] = $this;
		return $parameters;
	}

	public function __call($method, $parameters)
	{
		return $this->callExtension( $method, $parameters, false );
	}
}