<?php
namespace TeaPress\Shortcode;

use TeaPress\Contracts\Core\Container;
use TeaPress\Contracts\Utils\Actionable;
use TeaPress\Utils\Traits\DependencyResolver;
use Illuminate\Http\Exception\HttpResponseException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Shortcode
{
	use DependencyResolver;

	/**
	 * @var string
	 */
	protected $id;

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



	public function __construct($id)
	{
		$this->id = $id;
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
		foreach ($attributes as $name => $value) {
			$this->attribute($name, $value);
		}

		return $this;
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

		$response = $this->run($content);

		if($response instanceof SymfonyResponse){
			$response->sendHeaders();
			$response = $response->getContent();
		}

		return $response;
	}

	/**
	 * Get the shortcode's ID.
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
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
	 * Run the route action and return the response.
	 *
	 * @param  \TeaPress\Http\Request  $request
	 * @return mixed
	 */
	protected function run($content)
	{
		$this->container = $this->container ?: new Container;

		try {

			if( $this->isCallableClassMethod($this->handler) ){
				return $this->runController($content);
			}

			return $this->runCallable($content);

		} catch (HttpResponseException $e) {
			return $e->getResponse();
		}
	}


	/**
	 * Run the route action and return the response.
	 *
	 * @param  \TeaPress\Http\Request  $request
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 * @return mixed
	 */
	protected function runCallable($content)
	{
		if(is_array($this->handler))
			$parameters = $this->resolveClassMethodDependencies(
					$this->attributesWithoutNulls(), $this->handler[0], $this->handler[1]
				);
		else
			$parameters = $this->resolveMethodDependencies(
					$this->attributesWithoutNulls(), new ReflectionFunction($this->handler)
				);

		if (is_array($this->handler) && !method_exists($this->handler[0], $this->handler[1]) ) {
			throw new NotFoundHttpException;
		}

		return call_user_func_array($this->handler, $parameters);
	}

	/**
	 * Run the route action and return the response.
	 *
	 * @param  \TeaPress\Http\Request  $request
	 * @return mixed
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	protected function runController($content)
	{
		list($class, $method) = explode('@', $this->handler);

		$parameters = $this->resolveClassMethodDependencies(
				$this->attributesWithoutNulls(), $class, $method
			);

		$instance = $this->container->make($class);

		if (!method_exists($instance, $method)) {
			if($instance instanceof Actionable)
				return $instance->missingAction($content, $method, $parameters);
			else
				throw new NotFoundHttpException;
		}

		if($instance instanceof Actionable){
			$newParameters = $instance->beforeAction($content, $method, $parameters);
			if(!is_null($newParameters)){
				$parameters = $newParameters;
			}
		}

		$response = call_user_func_array([$instance, $method], $parameters);

		if($instance instanceof Actionable){
			$newResponse = $instance->afterAction($response, $content, $method, $parameters);
			if(!is_null($newResponse)){
				$response = $newResponse;
			}
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


}