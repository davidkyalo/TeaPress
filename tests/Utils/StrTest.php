<?php

namespace Tea\Tests\Utils;

use Tea\Utils\Str;
use PHPUnit_Framework_TestCase;

class StrTest extends PHPUnit_Framework_TestCase
{

	public function testPadByAppend()
	{
		$orig = 123;
		$size = 5;
		$with = 0;
		$result = Str::pad($orig, $size, $with);
		fwd_dump( 'Str Padding: '. __FUNCTION__, compact('orig', 'size', 'with', 'result') );
		$this->assertEquals( '12300', $result );
	}

	public function testPadByPrepend()
	{
		$orig = 123;
		$size = -5;
		$with = 0;
		$result = Str::pad($orig, $size, $with);
		fwd_dump( 'Str Padding: '. __FUNCTION__, compact('orig', 'size', 'with', 'result') );
		$this->assertEquals( '00123', $result );
	}

	public function testPadNothing()
	{
		$orig = 12345;
		$size = -4;
		$with = 0;
		$result = Str::pad($orig, $size, $with);
		fwd_dump( 'Str Padding: '. __FUNCTION__, compact('orig', 'size', 'with', 'result') );
		$this->assertEquals( '12345', $result );
	}

	public function testPadWithMultiChar()
	{
		$orig = 123;
		$size = -7;
		$with = '<->';
		$result = Str::pad($orig, $size, $with);
		fwd_dump( 'Str Padding: '. __FUNCTION__, compact('orig', 'size', 'with', 'result') );
		$this->assertEquals( '<-><123', $result );
	}


}