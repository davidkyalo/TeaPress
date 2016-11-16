<?php
namespace TeaPress\Shortcode;

use TeaPress\Utils\Str;
use TeaPress\Utils\Bag;
use InvalidArgumentException;
use Illuminate\Http\Response;
use TeaPress\Contracts\Core\Container;
use TeaPress\Contracts\General\Actionable;
use Illuminate\Http\Exception\HttpResponseException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Shortcode
{
	/**
	 * @var string
	 */
	protected $tag;

	/**
	 * @var bool
	 */
	protected $camelCaseAttrs = true;

	/**
	 * @var mixed
	 */
	protected $callback;

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * Create the shortcode instance.
	 *
	 * @param  string $tag
	 * @return void
	 */
	public function __construct($tag)
	{
		$this->tag = $tag;
	}

	/**
	 * Set the shortcode's handler.
	 *
	 * @param  \Closure|string|array  $handler
	 * @return $this
	 */
	public function handler($handler)
	{
		$this->handler = $handler;

		return $this;
	}

	/**
	 * Register an attribute and set the default value.
	 *
	 * @param  bool  $convert
	 * @return $this
	 */
	public function camelCaseAttrs($convert = true)
	{
		$this->camelCaseAttrs = $convert;

		return $this;
	}

	/**
	 * Register an attribute and set the default value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return $this
	 */
	public function attribute($key, $value = null)
	{
		$this->attributes[$key] = $value;

		return $this;
	}

	/**
	 * Register an array of attributes and their default values.
	 *
	 * @param  array  $attributes
	 * @return $this
	 */
	public function attributes(array $attributes)
	{
		foreach ($attributes as $key => $value) {
			$this->attribute($key, $value);
		}

		return $this;
	}

	/**
	 * Get the shortcode's tag.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return $this->tag;
	}

	/**
	 * Get the shortcode's handler.
	 *
	 * @return string
	 */
	public function getHandler()
	{
		return $this->handler;
	}

	/**
	 * Get the shortcode's attributes.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Get the key / value list of attributes without null values.
	 *
	 * @return array
	 */
	public function attributesWithoutNulls()
	{
		return array_filter($this->attributes, function ($p) {
			return ! is_null($p);
		});
	}

	/**
	 * Invoke the shortcode's handler.
	 * Executed by the do_shortcode() function.
	 *
	 * @param  array  $attributes
	 * @param  string  $content
	 * @return string
	 */
	public function __invoke($attributes = [], $content = null)
	{
		$this->mergeAttributes($attributes, $content);
		return $this->getResponse($this->run())->getContent();
	}

	/**
	 * Run the shortcode handler and return the response.
	 *
	 * @return mixed
	 */
	protected function run()
	{
		try {
			if($this->isCallableWithAtSign($this->handler)){
				return $this->runClassMethod($this->handler, $this->attributesWithoutNulls());
			}
			return $this->runCallable($this->handler, $this->attributesWithoutNulls());
		} catch (HttpResponseException $e) {
			return $e->getResponse();
		}
	}


	/**
	 * Run a callable handler.
	 *
	 * @param  \Closure|array|string 	$handler
	 * @param  array 					$parameters
	 * @return mixed
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	protected function runCallable($handler, $parameters = [])
	{
		if(!is_callable($handler)){
			throw new NotFoundHttpException;
		}

		return $this->container->call($handler, $parameters);
	}

	/**
	 * Run handler which is in the Class@method syntax.
	 *
	 * @param  string $handler
	 * @param  array $parameters
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	protected function runClassMethod($handler, $parameters = [])
	{
		list($class, $method) = array_pad(explode('@', $handler), 2, null);

		if (is_null($method)){
			$msg = "Shortcode \"". $this->tag ."\". Method for handler \"{$handler}\" not provided";
			throw new InvalidArgumentException($msg);
		}

		$instance = $this->container->make($class);

		$parameters = Bag::create($parameters);

		if($instance instanceof Actionable){
			$method = $instance->beforeAction($method, $parameters) ?: $method;
		}

		if (!method_exists($instance, $method)) {
			if($instance instanceof Actionable)
				return $instance->missingAction($method, $parameters);
			else
				throw new NotFoundHttpException;
		}

		$response = $this->runCallable([$instance, $method], $parameters->all());

		if($instance instanceof Actionable){
			$response = $instance->afterAction($response, $method, $parameters);
		}

		return $response;
	}

	/**
	 * Parse the appropriate for the given attribute.
	 *
	 * @param  string $name
	 * @return string
	 */
	protected function attrName($name)
	{
		return $this->camelCaseAttrs ? Str::camel($name) : $name;
	}

	/**
	 * Merge the shortcode's attributes.
	 *
	 * @param  array $attributes
	 * @return array
	 */
	protected function mergeAttributes($attributes, $content)
	{
		$this->parseDefaultAttributes();
		$attributes = $attributes ? (array) $attributes : [];
		$attributes['content'] = $content;
		foreach ($attributes as $key => $value) {
			$key = $this->attrName($key);
			$this->attributes[$key] = isset($value) ? $value : Arr::get($this->attributes, $key);
		}
		return $this->attributes;
	}

	/**
	 * Parse the shortcode's default attributes.
	 *
	 * @return array
	 */
	protected function parseDefaultAttributes()
	{
		$results = [];
		foreach ($this->attributes as $key => $value) {
			$results[$this->attrName($key)] = $value;
		}
		return $this->attributes = $results;
	}


	/**
	 * Determine if the given string is in Class@method syntax.
	 *
	 * @param  mixed  $callback
	 * @return bool
	 */
	protected function isCallableWithAtSign($callback)
	{
		return is_string($callback) ? (strpos($callback, '@') !== false) : false;
	}



	/**
	 * Set the IOC container instance.
	 *
	 * @param \TeaPress\Contracts\Core\Container $container
	 * @return static
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}


	/**
	 * Get/Create a response instance from the given value.
	 * If the given value is already a valid response instance,
	 * it is returned as it is.
	 *
	 * @param  mixed  $response
	 * @param  bool  $force
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function getResponse($response)
	{
		if($response instanceof SymfonyResponse)
			return $response;

		if ($response instanceof PsrResponseInterface)
			return (new HttpFoundationFactory)->createResponse($response);

		return new Response($response);
	}



}