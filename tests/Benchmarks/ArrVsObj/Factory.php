<?php
namespace TeaPress\Tests\Benchmarks\ArrVsObj;

use TeaPress\Utils\Str;
use TeaPress\Utils\Carbon\Carbon;
use Faker\Generator as Faker;
use TeaPress\Contracts\Core\Container;
/**
*
*/
class Factory
{

	public $app;

	public $faker;

	public $people = [];


	public function __construct(Container $app, Faker $faker)
	{
		$this->app = $app;
		$this->faker = $faker;
	}

	public function arrays($count)
	{
		$people = [];

		for ($i=0; $i < $count; $i++) {
			$people[] = $this->array();
		}

		return $people;
	}

	public function objects($count)
	{
		$people = [];

		for ($i=0; $i < $count; $i++) {
			$people[] = $this->object();
		}

		return $people;
	}

	public function array()
	{
		$person = $this->getTemplate();

		$person['dob'] = $this->makeDob();
		// $person['about'] = $this->faker->text( mt_rand(250,750) );

		$person['firstName'] = $this->faker->firstName($person['gender']);
		$person['lastName'] = $this->faker->lastName($person['gender']);

		$person['name'] = $this->makeNameGetter( $person );
		$person['email'] = $this->makeEmailGetter( $person );
		$person['age'] = $this->makeAgeGetter( $person );

		return $this->people[] = &$person;
	}

	public function object()
	{
		$temp =$this->getTemplate();
		return $this->people = new Person($this, $temp['gender'], $temp['company']);
	}

	public function getTemplate()
	{
		return [
			'gender' => mt_rand(0,99) >= 50 ? 'female' : 'male',
			'company' => $this->faker->company
		];

	}

	public function makeNameGetter(&$person)
	{
		return function() use (&$person){
			static $value;

			if(is_null($value))
				$value = $person['firstName'] . ' ' . $person['lastName'];

			return $value;
		};
	}

	public function makeEmailGetter(&$person)
	{
		return function() use (&$person){
			static $value;

			if(is_null($value)){
				$value = Str::slug( Str::compact( $person['name']() ), '_' )."@";
				$value .= $this->makeDomain($person['company']);
			}

			return $value;
		};
	}

	public function makeAgeGetter(&$person)
	{
		return function() use (&$person){
			static $value;

			if(is_null($value))
				$value = $person['dob']->age;

			return $value;
		};
	}


	public function makeDomain($name)
	{
		$exts = ['.com', '.net', '.gov', '.org', '.edu'];

		$name = Str::slug(Str::compact($name));

		$rand = mt_rand(0, 499);
		$key = Str::pad($rand, -3, '0');

		$ext = $exts[ (int) $key[0]];

		return $name . $ext;
	}

	public function makeDob($max = '-12 years')
	{
		return Carbon::cast( $this->faker->dateTime($max = '-12 years') );
	}

}