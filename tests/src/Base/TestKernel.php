<?php
namespace TeaPress\Tests\Base;


abstract class TestKernel extends \PHPUnit_Framework_Assert
{
	protected $app;

	public function __construct($app)
	{
		$this->app = $app;
	}

	abstract public function register();

	public function boot(){}


	protected function aliasServices(array $aliases)
	{
		foreach ($aliases as $key => $aliases) {
			foreach ((array) $aliases as $alias) {
				$this->app->alias($key, $alias);
			}
		}
	}


	protected function runServiceAliasesTest($service, array $aliases, $params = [])
	{
		$expected = array_pad( [], count( (array) $aliases), (is_string($service) ? $service : get_class($service)) );

		$results = [];

		foreach ((array) $aliases as $alias) {

			$service = $this->app->make($alias, $params);

			$results[] = is_object($service) ? get_class($service) : $service;
		}

		$this->assertEquals( $expected, $results );
	}


}

