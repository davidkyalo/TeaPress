<?php
namespace TeaPress\Tests\Benchmarks\ArrVsObj;

use TeaPress\Utils\Str;
use TeaPress\Utils\Carbon\Carbon;

class Person implements \ArrayAccess, \Countable, \IteratorAggregate
{
	protected $faker;

	protected $factory;

	protected $gender;

	protected $firstName;

	protected $lastName;

	protected $name;

	protected $email;

	protected $company;

	protected $dob;

	protected $age;

	protected $about;

	protected $isApproved = false;


	public function __construct($factory, $gender, $company)
	{
		$this->factory = $factory;
		$this->faker = $factory->faker;
		$this->gender = $gender;
		$this->company = $company;

		$this->dob = $this->factory->makeDob();
		// $this->about = $this->faker->text( mt_rand(250,750) );

		$this->firstName = $this->faker->firstName($gender);
		$this->lastName = $this->faker->lastName($gender);

		$this->name = $this->factory->makeNameGetter( $this );
		$this->email = $this->factory->makeEmailGetter( $this );
		$this->age = $this->factory->makeAgeGetter( $this );
	}

	public function get($key)
	{
		$value = property_exists($this, $key) ? $this->{$key} : null;

		if(method_exists($this, $getter = Str::camel("get_{$key}") ))
			return $this->{$getter}($value);
		else
			return $value;
	}

	public function set($key, $value)
	{
		if(method_exists($this, $setter = Str::camel("set_{$key}") ))
			return $this->{$setter}($value);
		elseif(property_exists($this, $key))
			$this->{$key} = $value;
		else
			throw new Exception("Error stting undefined property {$key}.");
	}

	public function __get($key)
	{
		return $this->get($key);
	}

	public function __set($key, $value)
	{
		$this->set($key, $value);
	}


	public function offsetExists($key)
	{
		return property_exists($this, $key);
	}

	public function offsetGet($key)
	{
		return $this->get($key);
	}

	public function offsetSet($key, $value)
	{
		return $this->set($key, $value);
	}

	public function offsetUnset($key)
	{
		$this->set($key, null);
	}

	public function toArray()
	{
		$array = get_object_vars($this);
		unset( $array['factory'] );
		unset( $array['faker'] );
		return $array;
	}

	public function count()
	{
		return count( $this->toArray() );
	}


	public function getIterator()
	{
		return new \ArrayIterator($this->toArray());
	}

}