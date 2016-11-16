<?php

namespace TeaPress\Tests\Base;

use TeaPress\Utils\Str;
use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use TeaPress\Core\Application;

abstract class ServiceTestCase extends TestCase
{
	use AppTrait;

	protected $container;
	protected $serviceName;
	protected $serviceClass;

	/**
	 * Constructs a test case with the given name.
	 *
	 * @param string $name
	 * @param array  $data
	 * @param string $dataName
	 */
	public function __construct(...$args)
	{
		parent::__construct(...$args);
	}


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
		return $this->makeTheService($this->getMakeServiceParameters() );
	}

	protected function makeTheService($parameters = [])
	{
		return $this->container($this->getServiceName(), $parameters);
	}

	protected function getMakeServiceParameters()
	{
		return [];
	}

	public function runRegisteredTest()
	{

		$msg = "Service '".$this->getServiceName()."' (".$this->getServiceClass().") not registered with the contaner.";
		$this->assertInstanceOf($this->getServiceClass(), $this->getService(), $msg);
	}


	protected function runServiceAliasesTest(array $aliases = null)
	{
		if(is_null($aliases))
			$this->container()->serviceAliases( $this->getServiceName() );

		$expected = array_pad( [], count( (array) $aliases), get_class( $this->getService() ));
		$results = [];

		foreach ((array) $aliases as $alias) {
			$service = $this->container()->make($alias);
			$results[] = is_object($service) ? get_class($service) : $service;
		}

		$this->assertEquals( $expected, $results );
	}

	public function __get($key)
	{
		if( in_array($key, [ $this->getServicePropertyName(), 'service']))
			return $this->getService();

		if($key === 'contaner')
			return $this->container();

		throw new OutOfBoundsException("Property {$key} not defined");

	}
}