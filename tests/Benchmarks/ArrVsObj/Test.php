<?php
namespace TeaPress\Tests\Benchmarks\ArrVsObj;

use TeaPress\Tests\Base\TestCase;

class Test extends TestCase
{
	protected $people;

	protected function setUp()
	{
		$this->people = $this->app(Factory::class);
	}



	public function testArrays()
	{
		$watu = $this->people->arrays(20000);
		foreach ($watu as $key => $person) {
			// pprint("");
			foreach ($person as $key => $value) {
				$value = value($value);
				// pprint($key, $value);
			}
			// pprint("---------------------");
		}
	}

	public function testObjects()
	{
		$watu = $this->people->objects(0);
		foreach ($watu as $key => $person) {
			// pprint("");
			foreach ($person as $key => $value) {
				$value = value($value);
				// pprint($key, $value);
			}
			// pprint("---------------------");
		}
	}
}