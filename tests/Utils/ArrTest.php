<?php

namespace TeaPress\Tests\Utils;


use TeaPress\Utils\Arr;
use PHPUnit_Framework_TestCase;

class ArrTest extends PHPUnit_Framework_TestCase
{
	/*
	* @var array
	*/
	protected $prefixTable;

	protected function setUp()
	{
		$this->getPrefixTable();
	}

	protected function getPrefixTable()
	{
		if(!$this->prefixTable){
			$this->prefixTable = ['parent_', 'child_', 'grand_child_'];
			for ($d=1; $d < 6; $d++) {
				$this->prefixTable[] =  str_repeat('great_', $d+1) . 'grand_child_';
			}
		}
		return $this->prefixTable;
	}


	public function testGet()
	{
		$subject = $this->sampleArray();

		$this->assertEquals( 'Value 2.3.2', Arr::get($subject, 'parent_2.child_3.grand_child_2') );
	}


	public function testGetNotated()
	{
		$subject = $this->notatedSampleArray();

		$this->assertEquals( 'Value 2.3.2', Arr::get($subject, ['parent.2->child.3->grand_child.2' , '->']) );
	}


	public function testSet()
	{
		$subject = $this->sampleArray();
		$key = 'parent_2.child_3.grand_child_4';
		$value = 'Value 2.3.4';
		Arr::set($subject, $key, $value );
		$this->assertEquals( $value, Arr::get($subject, $key) );
	}


	public function testSetNotated()
	{
		$subject = $this->notatedSampleArray();
		$key = ['parent.2->child.3->grand_child.2' , '->'];
		$orig = Arr::get($subject, $key);
		Arr::set($subject, $key, 'New '. $orig );
		$this->assertEquals( 'New '. $orig , Arr::get($subject, $key) );
	}

	public function testSetNotatedNew()
	{
		$subject = $this->notatedSampleArray();
		$key = ['parent.2->child.3->grand_child.4' , '->'];
		$value = 'Value 2.3.4';
		Arr::set($subject, $key, $value );
		$this->assertEquals( $value, Arr::get($subject, $key) );
	}

	public function testHas()
	{
		$subject = $this->sampleArray();
		$key = 'parent_2.child_3.grand_child_2';
		$this->assertTrue( Arr::has($subject, $key) );
	}


	public function testHasNot()
	{
		$subject = $this->sampleArray();
		$key = 'parent_2.child_3.grand_child_2.xxxxx';
		$this->assertFalse( Arr::has($subject, $key) );
	}


	public function testHasNotated()
	{
		$subject = $this->notatedSampleArray();
		$key = ['parent.2->child.3->grand_child.2' , '->'];
		$this->assertTrue( Arr::has($subject, $key) );
	}


	public function testHasNotatedAsArg()
	{
		$subject = $this->notatedSampleArray();
		$key = 'parent.2->child.3->grand_child.2';
		$this->assertTrue( Arr::has($subject, $key, '->') );
	}

	public function testHasNotNotated()
	{
		$subject = $this->notatedSampleArray();
		$key = ['parent.2->child.3->grand_child.2->xxx' , '->'];
		$this->assertFalse( Arr::has($subject, $key) );
	}


	public function testExtend()
	{
		$subject = $this->sampleArray();
		$key = 'parent_3.child_2';
		$items = [
			[ 'Value 3.2.6', 'Value 3.2.7', 'Value 3.2.8' ],
			[ 'Value 3.2.9', 'Value 3.2.10', 'Value 3.2.11' ],
		];

		$expected = array_merge(Arr::get($subject, $key), ...$items);

		Arr::extend($subject, $key, ...$items);

		$this->assertEquals( $expected,  Arr::get($subject, $key) );
	}

	public function testExtendNew()
	{
		$subject = $this->sampleArray();
		$key = 'parent_1.child_3.grand_child_4';
		$items = [
			[ 'Value 1.3.4.1', 'Value 1.3.4.2', 'Value 1.3.4.3' ],
			[ 'Value 1.3.4.4', 'Value 1.3.4.5', 'Value 1.3.4.6' ],
		];

		$expected = array_merge(...$items);

		Arr::extend($subject, $key, ...$items);

		$this->assertEquals( $expected,  Arr::get($subject, $key) );
	}

	public function testExtendNotated()
	{
		$subject = $this->notatedSampleArray();
		$key = ['parent.3>child.2', '>'];
		$items = [
			[ 'Value 3.2.6', 'Value 3.2.7', 'Value 3.2.8' ],
			[ 'Value 3.2.9', 'Value 3.2.10', 'Value 3.2.11' ],
		];

		$expected = array_merge(Arr::get($subject, $key), ...$items);

		Arr::extend($subject, $key, ...$items);

		$this->assertEquals( $expected,  Arr::get($subject, $key) );
	}

	public function testPushUnique()
	{
		$subject = $this->sampleArray();
		$key = 'parent_3.child_2';
		$items = [
			'Value 3.2.2', 'Value 3.2.3', 'Value 3.2.4',
			'Value 3.2.6', 'Value 3.2.7', 'Value 3.2.8',
		];

		$expected = array_values( array_unique( array_merge(Arr::get($subject, $key), $items)) );

		Arr::pushUnique($subject, $key, ...$items);

		$this->assertEquals( $expected,  Arr::get($subject, $key) );
	}

	public function testPush()
	{
		$subject = $this->sampleArray();
		$key = 'parent_1.child_3.grand_child_4';
		$items = [
			'Value 1.3.4.1', 'Value 1.3.4.2', 'Value 1.3.4.3',
			'Value 1.3.4.4', 'Value 1.3.4.5', 'Value 1.3.4.6',
		];

		$expected = $items;

		Arr::push($subject, $key, ...$items);

		$this->assertEquals( $expected,  Arr::get($subject, $key) );
	}


	public function testPushNotated()
	{
		$subject = $this->notatedSampleArray();
		$key = ['parent.1>>child.3>>grand_child.4', '>>'];
		$items = [
			'Value 1.3.4.1', 'Value 1.3.4.2', 'Value 1.3.4.3',
			'Value 1.3.4.4', 'Value 1.3.4.5', 'Value 1.3.4.6',
		];

		$expected = $items;

		Arr::push($subject, $key, ...$items);

		$this->assertEquals( $expected,  Arr::get($subject, $key) );
	}

	public function testPut()
	{
		$r = 7;
		$expected = [];
		$single =  range(1, $r);
		$actual = [];

		for ($i=0; $i < $r; $i++) {
			$values = [];
			$items = [];
			for ($v=0; $v < $r; $v++) {
				if( $v === $i ||  $v === ($i + 1 ))
					$items[] = (int) $v+1;
				else
					$values[] = $v+1;
			}

			Arr::put( $values, null, $i, ...$items );
			$actual[] = $values;
			$expected[] = $single;

		}

		$this->assertEquals($expected, $actual);
	}



	public function testPutUnique()
	{
		$r = 7;
		$expected = [];
		$single =  range(1, $r);
		$actual = [];

		for ($i=0; $i < $r; $i++) {
			$values = [];
			$items = [];
			for ($v=0; $v < $r; $v++) {
				$values[] = $items[] = $v+1;
			}

			Arr::putUnique( $values, null, $i, ...$items );
			$actual[] = $values;
			$expected[] = $single;

		}

		$this->assertEquals($expected, $actual);
	}



	protected function makeAssocArr($len = 7, $l = 0, $val_id='')
	{
		$results = [];
		for ($i=1; $i <=$len; $i++) {
			$end = $val_id ? $val_id.'.'.$id : $id;
			$results[ $this->makeKey($i, $l) ] =  "Value {$end}";
		}
		return $results;
	}

	protected function makeArr($len=7, $val_id ='')
	{
		$results = [];
		for ($i=1; $i <=$len; $i++) {
			$end = $val_id ? $val_id.'.'.$id : $id;
			$results[] =  "Value {$end}";
		}
		return $results;
	}

	protected function makeKey($i, $l)
	{
		return $this->prefixTable[$l].$i;
	}

	protected function notatedSampleArray()
	{
		return [
			'parent.1' => [
				'child.1' => 'Value 1.1',
				'child.2' => [
					'Value 1.2.1',
					'Value 1.2.2',
					'Value 1.2.3',
					'Value 1.2.4',
					'Value 1.2.5',
				],
				'child.3' => [
					'grand_child.1' => 'Value 1.3.1',
					'grand_child.2' => 'Value 1.3.2',
					'grand_child.3' => [
						'Value 1.3.3.1',
						'Value 1.3.3.2',
						'Value 1.3.3.3',
					]
				],
				'child.4' => [
					'grand_child.1' => 'Value 1.4.1',
					'grand_child.2' => [
						'great_grand_child.1' => 'Value 1.4.2.1',
						'great_grand_child.2' => 'Value 1.4.2.2',
						'great_grand_child.3' => 'Value 1.4.2.3',
					],
					'grand_child.3' => [
						'Value 1.4.3.1',
						'Value 1.4.3.2',
						'Value 1.4.3.3',
					]
				],
			],
			'parent.2' => [
				'child.1' => 'Value 2.1',
				'child.2' => [
					'Value 2.2.1',
					'Value 2.2.2',
					'Value 2.2.3',
					'Value 2.2.4',
					'Value 2.2.5',
				],
				'child.3' => [
					'grand_child.1' => 'Value 2.3.1',
					'grand_child.2' => 'Value 2.3.2',
					'grand_child.3' => [
						'Value 2.3.3.1',
						'Value 2.3.3.2',
						'Value 2.3.3.3',
					]
				],
				'child.4' => [
					'grand_child.1' => 'Value 2.4.1',
					'grand_child.2' => [
						'great_grand_child.1' => 'Value 2.4.2.1',
						'great_grand_child.2' => 'Value 2.4.2.2',
						'great_grand_child.3' => 'Value 2.4.2.3',
					],
					'grand_child.3' => [
						'Value 2.4.3.1',
						'Value 2.4.3.2',
						'Value 2.4.3.3',
					]
				],
			],

			'parent.3' => [
				'child.1' => 'Value 3.1',
				'child.2' => [
					'Value 3.2.1',
					'Value 3.2.2',
					'Value 3.2.3',
					'Value 3.2.4',
					'Value 3.2.5',
				],
				'child.3' => [
					'grand_child.1' => 'Value 3.3.1',
					'grand_child.2' => 'Value 3.3.2',
					'grand_child.3' => [
						'Value 3.3.3.1',
						'Value 3.3.3.2',
						'Value 3.3.3.3',
					]
				],
				'child.4' => [
					'grand_child.1' => 'Value 3.4.1',
					'grand_child.2' => [
						'great_grand_child.1' => 'Value 3.4.2.1',
						'great_grand_child.2' => 'Value 3.4.2.2',
						'great_grand_child.3' => 'Value 3.4.2.3',
					],
					'grand_child.3' => [
						'Value 3.4.3.1',
						'Value 3.4.3.2',
						'Value 3.4.3.3',
					]
				],
			],

		];


	}

	protected function sampleArray($len=7, $depth=5, array $ratios=[[1, 8], [1,15]], $level=0, $vprepend='')
	{
		return [
			'parent_1' => [
				'child_1' => 'Value 1.1',
				'child_2' => [
					'Value 1.2.1',
					'Value 1.2.2',
					'Value 1.2.3',
					'Value 1.2.4',
					'Value 1.2.5',
				],
				'child_3' => [
					'grand_child_1' => 'Value 1.3.1',
					'grand_child_2' => 'Value 1.3.2',
					'grand_child_3' => [
						'Value 1.3.3.1',
						'Value 1.3.3.2',
						'Value 1.3.3.3',
					]
				],
				'child_4' => [
					'grand_child_1' => 'Value 1.4.1',
					'grand_child_2' => [
						'great_grand_child_1' => 'Value 1.4.2.1',
						'great_grand_child_2' => 'Value 1.4.2.2',
						'great_grand_child_3' => 'Value 1.4.2.3',
					],
					'grand_child_3' => [
						'Value 1.4.3.1',
						'Value 1.4.3.2',
						'Value 1.4.3.3',
					]
				],
			],
			'parent_2' => [
				'child_1' => 'Value 2.1',
				'child_2' => [
					'Value 2.2.1',
					'Value 2.2.2',
					'Value 2.2.3',
					'Value 2.2.4',
					'Value 2.2.5',
				],
				'child_3' => [
					'grand_child_1' => 'Value 2.3.1',
					'grand_child_2' => 'Value 2.3.2',
					'grand_child_3' => [
						'Value 2.3.3.1',
						'Value 2.3.3.2',
						'Value 2.3.3.3',
					]
				],
				'child_4' => [
					'grand_child_1' => 'Value 2.4.1',
					'grand_child_2' => [
						'great_grand_child_1' => 'Value 2.4.2.1',
						'great_grand_child_2' => 'Value 2.4.2.2',
						'great_grand_child_3' => 'Value 2.4.2.3',
					],
					'grand_child_3' => [
						'Value 2.4.3.1',
						'Value 2.4.3.2',
						'Value 2.4.3.3',
					]
				],
			],
			'parent_3' => [
				'child_1' => 'Value 3.1',
				'child_2' => [
					'Value 3.2.1',
					'Value 3.2.2',
					'Value 3.2.3',
					'Value 3.2.4',
					'Value 3.2.5',
				],
				'child_3' => [
					'grand_child_1' => 'Value 3.3.1',
					'grand_child_2' => 'Value 3.3.2',
					'grand_child_3' => [
						'Value 3.3.3.1',
						'Value 3.3.3.2',
						'Value 3.3.3.3',
					]
				],
				'child_4' => [
					'grand_child_1' => 'Value 3.4.1',
					'grand_child_2' => [
						'great_grand_child_1' => 'Value 3.4.2.1',
						'great_grand_child_2' => 'Value 3.4.2.2',
						'great_grand_child_3' => 'Value 3.4.2.3',
					],
					'grand_child_3' => [
						'Value 3.4.3.1',
						'Value 3.4.3.2',
						'Value 3.4.3.3',
					]
				],
			],

		];


		$prefix_table = ['parent_', 'child_', 'grand_child_'];
		for ($d=0; $d < $depth-2; $d++) {
			$prefix_table[] =  str_repeat('great_', $d+1) . 'grand_child_';
		}

		$getKey= function($i, $l=null) use (&$depth, &$level, $prefix_table)
		{
			$l = is_null($l) ? $level: $l;
			return $prefix_table[$l].$i;
		};

		$useArr = function(...$r) use(&$ratios){
			$r = count($r) === 0 ? $ratios[0]: $r;
			return rand(...$r) <= 5;
		};

		$useAssoc = function(...$r) use(&$ratios){
			$r = count($r) === 0 ? $ratios[1]: $r;
			return rand(...$r) <= 5;
		};

		$results = [];

		$level = 0;
		for ($i=1; $i <= $len; $i++) {
			if( $useArr() ){
				// $results[$getKey($i, $level)] = $useAssoc() ? $this->makeAssocArr(7,)
			}

		}
		// $results[$getkey()]
/*
		for ($i=1; $i <= $len; $i++) {

			$key = $getkey($i, $level);

			$vprepend = $vprepend ? $vprepend.'.'.$i : $vprepend;

			$use_arr = rand(...$ratios[0]) <= 5;
			$use_assoc = rand(...$ratios[1]) <= 5;
			if($use_arr){
				if($depth > $level){
					if($use_assoc){
						$results[$key] = $this->sampleArray($len, $depth, $ratios, $level+1, $vprepend);
					}
					else{
						$results[$key] = $this->sampleArray($len, $depth, [ [1,5],[6,10]], $level+1, $vprepend);
					}
				}
				else{

				}
			}
			else{
				$results[$key] = "Value {$vprepend}";
			}

		}
*/
		return $results;
	}

}