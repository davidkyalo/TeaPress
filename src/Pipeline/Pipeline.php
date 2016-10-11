<?php

namespace TeaPress\Hooks;

use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;
use TeaPress\Contracts\Hooks\Hub as HubContract;
use TeaPress\Contracts\Hooks\Pipeline as Contract;

class Pipeline implements Contract
{
	/**
	 * The container implementation.
	 *
	 * @var \Illuminate\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * Events manager.
	 *
	 * @var \TeaPress\Contracts\Hooks\Hub
	 */
	protected $hub;


	/**
	 * Hook tags.
	 *
	 * @var array
	 */
	protected $tags;

	/**
	 * The object being passed through the pipeline.
	 *
	 * @var mixed
	 */
	protected $cargo;

	/**
	 * The array of callable pipes.
	 *
	 * @var array
	 */
	protected $pipes = [];

	/**
	 * The array of parameters for pipes.
	 *
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * The method to call on each pipe.
	 *
	 * @var string
	 */
	protected $method = 'handle';

	/**
	 * Create a new class instance.
	 *
	 * @param  \Illuminate\Contracts\Container\Container  $container
	 * @param  \TeaPress\Contracts\Hooks\Hub 			  $hub
	 * @return void
	 */
	public function __construct(Container $container, HubContract $hub)
	{
		$this->container = $container;
		$this->hub = $hub;
	}

	/**
	 * Set the object being sent through the pipeline.
	 *
	 * @param  mixed  $cargo
	 * @return $this
	 */
	public function send($cargo)
	{
		$this->cargo = $cargo;

		return $this;
	}

	/**
	 * Set the hook tag for the pipes to send the cargo through.
	 * Callbacks bound to this tag will be used as pipes.
	 *
	 * @param  string|array  $tag
	 * @return $this
	 */
	public function as($tag)
	{
		return $this->mergedAs( func_get_args() );
	}


	/**
	 * Set the hook tags for the pipes to send the cargo through.
	 * Callbacks bound to all these hooks will be used as pipes.
	 *
	 * @param  string|array  $tag
	 * @return $this
	 */
	public function mergedAs(array $tags)
	{

		$this->tags = [];
		$pipes = [];

		foreach ($tags as $tag) {
			$this->tags[] = $this->hub->getTag($tag);
			$pipes = array_merge( $pipes, $this->hub->getBoundCallbacks($tag, true) );
		}

		return $this->through($pipes);
	}

	/**
	 * Set an array of parameters to be passed to the pipes.
	 *
	 * @param  array|mixed  $parameters
	 * @return $this
	 */
	public function with($parameters)
	{
		$this->parameters = func_num_args() > 1 ? func_get_args() : (array) $parameters;

		return $this;
	}

	/**
	 * Set the array of pipes.
	 *
	 * @param  array|mixed  $pipes
	 * @return $this
	 */
	public function through($pipes)
	{
		$this->pipes = is_array($pipes) ? $pipes : func_get_args();

		return $this;
	}

	/**
	 * Set the method to call on the pipes.
	 *
	 * @param  string  $method
	 * @return $this
	 */
	public function via($method)
	{
		$this->method = $method;

		return $this;
	}

	/**
	 * Run the pipeline with a final destination callback.
	 *
	 * @param  \Closure|array|string  $destination
	 * @return mixed
	 */
	public function then($destination)
	{
		$firstSlice = $this->getInitialSlice( $destination );

		$pipes = array_reverse($this->pipes);

		if($this->isTagged())
			$this->runHooks();

		return call_user_func(
				array_reduce($pipes, $this->getSlice(), $firstSlice), $this->cargo
			);
	}

	/**
	 * Check if the pipeline is tagged
	 *
	 * @return bool
	 */
	public function isTagged()
	{
		return !empty((array) $this->tags);
	}

	protected function beforePiping()
	{
		if($this->isTagged())
			$this->runHooks();

	}

	protected function runHooks()
	{
		global $wp_filter, $wp_current_filter;

		foreach ( (array) $this->tags as $tag) {

			$wp_current_filter[] = $tag;

			if (function_exists('_wp_call_all_hook') && isset($wp_filter['all']))
				_wp_call_all_hook([ $tag, $this->cargo, $this->parameters ]);

			array_pop($wp_current_filter);

		}
	}

	protected function afterPiping()
	{

	}

	/**
	 * Get a Closure that represents a slice of the application onion.
	 *
	 * @return \Closure
	 */
	protected function getSlice()
	{
		return function ($stack, $pipe) {
			return function ($cargo) use ($stack, $pipe) {
				return call_user_func_array(
					$this->getCallable($pipe),
					array_merge( [$stack, $cargo], $this->parameters ));

			};
		};
	}

	/**
	 * Get the initial slice to begin the stack call.
	 *
	 * @param  \Closure|array|string  $destination
	 * @return \Closure
	 */
	protected function getInitialSlice($destination)
	{
		return function ($cargo) use ($destination) {
			return call_user_func_array(
					$this->getCallable($destination),
					array_merge( [$cargo], $this->parameters ));
		};
	}


	/**
	 * Creates the class based callable for callback if callback is not callable, Returns callback if callable.
	 *
	 * @param  callable|string  $callback
	 *
	 * @return callable
	 */
	protected function getCallable($callback)
	{
		if(is_callable($callback))
			return $callback;

		if(!is_string($callback))
			throw new InvalidArgumentException("Error creating callable. Unknown Callback (".$callback.").");

		list($class, $method) = $this->parseClassCallable($callback);

		return $class === '' ? $this->container->make($method) : [$this->container->make($class), $method];
	}

	/**
	 * Parse the class based callback into class and method.
	 *
	 * @param  string  $callback
	 * @return array
	 */
	protected function parseClassCallable($callback){
		$segments = explode('@', $callback);
		return [$segments[0], count($segments) == 2 ? $segments[1] : $this->method ];
	}

	/**
	* Parse full callback string to get name and parameters.
	*
	* @param  mixed $callback
	* @return array
	*/
	protected function parseParamString($callback)
	{
		if( !is_string($callback) )
			return [ $callback, [] ];

		list($callback, $parameters) = array_pad(explode(':', $callback, 2), 2, []);

		if (is_string($parameters)) {
			$parameters = explode(',', $parameters);
		}

		return [$callback, $parameters];
	}

}
