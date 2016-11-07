<?php
namespace TeaPress\Routing\Matching;

use FastRoute\Dispatcher\GroupCountBased;
use TeaPress\Contracts\Routing\Matching\Matcher as Contract;

class Matcher extends GroupCountBased implements Contract
{
	/**
	 * @var int
	 */
	protected $status;

	/**
	 * @var mixed
	 */
	protected $matched;

	/**
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * @var array
	 */
	protected $allowedMethods = [];

	/**
	 * Get status
	 *
	 * @return int
	 */
	public function status()
	{
		return $this->status;
	}

	/**
	 * Get matched route
	 *
	 * @return mixed
	 */
	public function matched()
	{
		return $this->matched;
	}

	/**
	 * Get matched route parameters
	 *
	 * @return array
	 */
	public function parameters()
	{
		return $this->parameters;
	}

	/**
	 * Get the allowed http methods.
	 *
	 * @return array
	 */
	public function allowed()
	{
		return $this->allowedMethods;
	}

	/**
	 * Determine whether any route was matched.
	 * If status is given, checks whether it's eq to self::FOUND.
	 *
	 * @param null|int $status
	 * @return bool
	 */
	public function found($status = null)
	{
		$status = is_null($status) ? $this->status : $status;

		return $status === static::FOUND;
	}

	/**
	 * Determine whether non of the given routes was matched.
	 * If status is given, checks whether it's eq to self::NOT_FOUND.
	 *
	 * @param null|int $status
	 * @return bool
	 */
	public function notFound($status = null)
	{
		$status = is_null($status) ? $this->status : $status;

		return $status === static::NOT_FOUND;
	}

	/**
	 * Determine whether the dispatched method is not allowed for the matched route.
	 * If status is given, checks whether it's eq to self::METHOD_NOT_ALLOWED.
	 *
	 * @param null|int $status
	 * @return bool
	 */
	public function methodNotAllowed($status = null)
	{
		$status = is_null($status) ? $this->status : $status;

		return $status === static::METHOD_NOT_ALLOWED;
	}

	/**
	 * Matches the routes against the provided HTTP method verb and URI.
	 *
	 * @param string $method
	 * @param string $uri
	 * @return static
	 */
	public function dispatch($method, $uri)
	{
		$this->status = null;
		$this->matched = null;
		$this->parameters = $this->allowedMethods = [];

		$this->parseMatchedResults( parent::dispatch($method, $uri), $method, $uri);

		return $this;
	}

	/**
	 * Matches the routes against the provided HTTP method verb and URI.
	 *
	 * @param  string $method
	 * @param  string $uri
	 * @return $this
	 */
	public function match($method, $uri)
	{
		return $this->dispatch($method, $uri);
	}

	/**
	 * Parse the matched results.
	 *
	 * @param  array  $results
	 * @param  string $method
	 * @param  string $uri
	 * @return void
	 */
	protected function parseMatchedResults($results, $method, $uri)
	{
		$this->status = $results[0];

		if($this->found()){
			$this->matched = $results[1];
			$this->parameters = $results[2];
			if( $this->matched instanceof Matchable){
				$this->matched->setMatchedUri($uri);
			}
		}
		elseif ($this->methodNotAllowed()) {
			$this->allowedMethods = $results[1];
		}
	}

}