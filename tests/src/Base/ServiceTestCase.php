<?php

namespace TeaPress\Tests\Base;

use TeaPress\Utils\Str;
use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use TeaPress\Core\Application;

abstract class ServiceTestCase extends TestCase
{
	use AppTrait;

	protected $serviceName;
	protected $serviceClass;



	protected function setUp()
	{
		$name = $this->getServicePropertyName();
		if($name && property_exists($this, $name)){
			$this->{$name} = $this->getService();
		}
	}

	protected function getServicePropertyName()
	{
		return isset($this->servicePropertyName)
			? $this->servicePropertyName
			: Str::camel( Str::snake( $this->getServiceName() ) );
	}

	protected function getServiceName()
	{
		return $this->serviceName;
	}

	protected function getServiceClass()
	{
		return $this->serviceClass;
	}

	public function getService()
	{
		return $this->app($this->getServiceName());
	}

	public function runRegisteredTest()
	{

		$msg = "Service '".$this->getServiceName()."' (".$this->getServiceClass().") not registered with the contaner.";
		$this->assertInstanceOf($this->getServiceClass(), $this->getService(), $msg);
	}


	protected function runServiceAliasesTest(array $aliases = null)
	{
		if(is_null($aliases))
			$this->app()->serviceAliases( $this->getServiceName() );

		$expected = array_pad( [], count( (array) $aliases), get_class( $this->getService() ));
		$results = [];

		foreach ((array) $aliases as $alias) {
			$service = $this->app->make($alias);
			$results[] = is_object($service) ? get_class($service) : $service;
		}

		$this->assertEquals( $expected, $results );
	}

	public function __get($key)
	{
		if( in_array($key, [ $this->getServicePropertyName(), 'service']))
			return $this->getService();

		throw new OutOfBoundsException("Property {$key} not defined");

	}
}