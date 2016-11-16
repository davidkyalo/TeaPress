<?php
namespace TeaPress\Signals;

use TeaPress\Contracts\Core\Container;

class Handler
{
	/**
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $container;

	/**
	 * @var mixed
	 */
	protected $callback;

	/**
	 * Create the handle instance.
	 *
	 * @param  \TeaPress\Contracts\Core\Container  $container
	 * @param  mixed  $callback
	 * @return void
	*/
	public function __construct(Container $container, $callback)
	{
		$this->container = $container;
		$this->callback = $callback;
	}

	/**
	 * Execute the handler's callback.
	 *
	 * @param  mixed  ...$parameters
	 * @return mixed
	*/
	public function __invoke(...$parameters)
	{
		return call_user_func_array($this->getCallable($this->callback), $parameters);
	}

	/**
	 * Get the handler's callback.
	 *
	 * @return mixed
	*/
	public function getCallback()
	{
		return $this->callback;
	}

	/**
	 * Creates the class based callable for callback if callback is not callable, Returns callback if callable.
	 *
	 * @param  callable|string  $callback
	 *`
	 * @return callable
	 */
	protected function getCallable($callback)
	{
		if(is_callable($callback)){
			return $callback;
		}

		if(!is_string($callback)){
			throw new InvalidArgumentException("Invalid event callback.");
		}
		return $this->parseClassCallable($callback);
	}

	/**
	 * Parse the class based callback into class and method.
	 *
	 * @param  string  $callback
	 * @return array|callable
	 */
	protected function parseClassCallable($callback)
	{
		if(strpos($callback, '@') === 0){
			return $this->container->make( substr($callback, 1) );
		}

		$segments = explode('@', $callback);

		if(!count($segments)){
			throw new InvalidArgumentException("Invalid event callback [$callback]");
		}

		$object = $this->container->make($segments[0]);
		$method = count($segments) === 2 ? $segments[1] : 'handle';

		return [$object, $method];
	}
}