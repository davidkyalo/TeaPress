<?php

namespace TeaPress\Utils\Traits;

use Closure;
use TeaPress\Contracts\Exceptions\BadMethodOrExtensionCall;

trait Extendable {

	protected $extensions = [];

	public function extend($method, $extension){
		$this->extensions[$method] = $extension;
		return $this;
	}

	public function hasExtension($method){
		return isset($this->extensions[$method]);
	}

	protected function getExtensions(){
		return $this->extensions;
	}

	public function setExtensions(array $extensions){
		foreach ($extensions as $method => $extension) {
			$this->extend($method, $extension);
		}
		return $this;
	}

	protected function callExtension($method, $parameters, $silent = false){
		if( !$this->hasExtension($method) ){
			$exception = new BadMethodOrExtensionCall($method, $this);
			if( !$silent )
				throw $exception;
			return $exception;
		}
		$callback = $this->extensions[$method];
		$parameters = $this->getExtensionParameters($parameters, $method);
		if( $callback instanceof Closure )
			return call_user_func_array( $callback->bindTo($this, get_class($this)), $parameters );
		else
			return call_user_func_array($callback, $parameters );
	}

	protected function getExtensionParameters($parameters, $method = null){
		$parameters[] = $this;
		return $parameters;
	}

	public function __call($method, $parameters){
		return $this->callExtension( $method, $parameters, false );
	}
}