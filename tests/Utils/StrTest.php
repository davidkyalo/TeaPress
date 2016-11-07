<?php

namespace TeaPress\Tests\Utils;

use TeaPress\Utils\Str;
use PHPUnit_Framework_TestCase;

class StrTest extends PHPUnit_Framework_TestCase
{
	public function testCompact()
	{
		$str = "My   name    is Big           Space       ";
		$this->assertEquals( 'My name is Big Space', Str::compact($str) );
	}

	public function testCompactNoSpace()
	{
		$str = "My   name    is Big           Space       ";
		$this->assertEquals( 'MynameisBigSpace', Str::compact($str, '') );
	}


	public function testCompactUnicode()
	{
		$str = "My   name    is  \xa0-   Big         Space       ";
		$this->assertEquals( "My name is \xa0- Big Space", Str::compact($str) );
	}


	public function testMinify()
	{
		$str = "Hello!\nMy   name    is \r\n\tBig         Space.   \n Thanks    ";
		$this->assertEquals( "Hello! My name is Big Space. Thanks", Str::minify($str) );
	}



	public function testPadByAppend()
	{
		$orig = 123;
		$size = 5;
		$with = 0;
		$result = Str::pad($orig, $size, $with);
		$this->assertEquals( '12300', $result );
	}

	public function testPadByPrepend()
	{
		$orig = 123;
		$size = -5;
		$with = 0;
		$result = Str::pad($orig, $size, $with);

		$this->assertEquals( '00123', $result );
	}

	public function testPadNothing()
	{
		$orig = 12345;
		$size = -4;
		$with = 0;
		$result = Str::pad($orig, $size, $with);

		$this->assertEquals( '12345', $result );
	}

	public function testPadWithMultiChar()
	{
		$orig = 123;
		$size = -7;
		$with = '<->';
		$result = Str::pad($orig, $size, $with);

		$this->assertEquals( '<-><123', $result );
	}

	public function testStripSingleChar()
	{
		$str = "//strip/slash/";
		$result = Str::strip($str, '/');
		$this->assertEquals('strip/slash', $result);
	}

	public function testStripSubStr()
	{
		$str = "-++plus-+minus-+-+";
		$result = Str::strip($str, '-+');
		$this->assertEquals('+plus-+minus', $result);
	}


	public function testStripWhitespaces()
	{
		$str = "   strip whitespaces   ";
		$result = Str::strip($str);
		$this->assertEquals('strip whitespaces', $result);
	}

	public function testLStripSingleChar()
	{
		$str = "//strip/slash/";
		$result = Str::lstrip($str, '/');
		$this->assertEquals('strip/slash/', $result);
	}

	public function testLStripSubStr()
	{
		$str = "-++plus-+minus-+";
		$result = Str::lstrip($str, '-+');
		$this->assertEquals('+plus-+minus-+', $result);
	}


	public function testLStripWhitespaces()
	{
		$str = "   strip whitespaces   ";
		$result = Str::lstrip($str);
		$this->assertEquals('strip whitespaces   ', $result);
	}


	public function testRStripSingleChar()
	{
		$str = "//strip/slash/";
		$result = Str::rstrip($str, '/');
		$this->assertEquals('//strip/slash', $result);
	}

	public function testRStripSubStr()
	{
		$str = "-+plus-+minus-+-+";
		$result = Str::rstrip($str, '-+');
		$this->assertEquals('-+plus-+minus', $result);
	}


	public function testRStripWhitespaces()
	{
		$str = "   strip whitespaces   ";
		$result = Str::rstrip($str);
		$this->assertEquals('   strip whitespaces', $result);
	}

	public function testBegin()
	{
		$str = "xbegin";
		$result = Str::begin($str, 'x_');
		$this->assertEquals('x_xbegin', $result);
	}

	public function testBeginExisting()
	{
		$str = "x_x_xbeginx_";
		$result = Str::begin($str, 'x_');
		$this->assertEquals('x_xbeginx_', $result);
	}

	public function testFinish()
	{
		$str = "finishx";
		$result = Str::finish($str, 'x_');
		$this->assertEquals('finishxx_', $result);
	}

	public function testFinishExisting()
	{
		$str = "finish___";
		$result = Str::finish($str, '_');
		$this->assertEquals('finish_', $result);
	}

}