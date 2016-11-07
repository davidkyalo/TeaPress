<?php
namespace TeaPress\Tests\Routing;

use TeaPress\Utils\Arr;
use TeaPress\Routing\UriParser;
use TeaPress\Tests\Base\TestCase;

/**
*
*/
class UriParserTest extends TestCase
{
	protected $parser;

	public function testParseStatic()
	{
		$parser = $this->parser;

		$this->assertEquals([['/foo/bar']], $parser->parse('/foo/bar'));
	}

	public function testParseWithPlaceholders()
	{
		$parser = $this->parser;

		$expected = [
				[
					'/foo/',
					['bar', '\w+'],
					'/',
					['id', '\d+']
				]
			];

		$actual = $parser->parse('/foo/{bar:\w+}/{id:\d+}');

		$this->assertEquals( $expected, $actual);
	}


	public function testParseWithAnonymousPlaceholders()
	{
		$parser = $this->parser;

		$expected = [
				[
					'/foo/',
					[ 0, '\w+'],
					'/',
					[ 1, $this->defaultPlaceholderRegex()]
				]
			];

		$actual = $parser->parse('/foo/{:\w+}/{}');

		$this->assertEquals( $expected, $actual);
	}

	public function testParseSetsPlaceholderDefaultRegex()
	{
		$parser = $this->parser;

		$expected = [
				[
					'/foo/',
					['bar', $this->defaultPlaceholderRegex()]
				]
			];

		$actual = $parser->parse('/foo/{bar}');

		$this->assertEquals( $expected, $actual);
	}


	public function testParseWithOptionalPlaceholder()
	{
		$parser = $this->parser;

		$expected = [
				[
					'/foo/',
					['bar', '\w+']
				],
				[
					'/foo/',
					['bar', '\w+'],
					'/',
					['id', '\d+']
				]
			];

		$actual = $parser->parse('/foo/{bar:\w+}[/{id:\d+}]');

		$this->assertEquals( $expected, $actual);
	}


	public function testParseWithOptionalPlaceholders()
	{
		$parser = $this->parser;

		$expected = [
				[
					'/foo',
				],
				[
					'/foo/',
					['bar', '\w+']
				],
				[
					'/foo/',
					['bar', '\w+'],
					'/',
					['id', '\d+']
				]
			];

		$actual = $parser->parse('/foo[/{bar:\w+}[/{id:\d+}]]');

		$this->assertEquals( $expected, $actual);
	}


	public function testParseWithPartterns()
	{
		$parser = $this->parser;

		$expected = [
				[
					'/foo/',
					['bar', '\w+'],
					'/',
					['id', '\d+']
				]
			];

		$patterns = [
				'bar' => '\w+',
				'id' => '\w+',
			];

		$actual = $parser->parse('/foo/{bar}/{id:\d+}', $patterns);

		$this->assertEquals( $expected, $actual);
	}

	public function testCache()
	{
		$parser = $this->parser->clearCached();

		$uri = '/users/{id:\d+}';

		$expected = [
				[
					'/users/',
					['id', '\d+']
				]
			];

		$patterns = [
				'bar' => '\w+',
				'id' => '\w+',
			];

		list($wasParsed_1, $result_1) = $parser->parseChecked( $uri );
		list($wasParsed_2, $result_2) = $parser->parseChecked( $uri );

		$this->assertTrue( ($wasParsed_1 && !$wasParsed_2 && $result_1 === $result_2 && $result_1 === $expected) );
	}

	public function testCacheUpdatesPatterns()
	{
		$parser = $this->parser->clearCached();

		$uri = '/foo/{bar}/{id:\d+}';

		$expected = [
			[
				[
					'/foo/',
					['bar', '\d+'],
					'/',
					['id', '\d+']
				]
			],
			[
				[
					'/foo/',
					['bar', '\w+'],
					'/',
					['id', '\d+']
				]
			],
		];

		$patterns = [
				['bar' => '\d+'],
				['bar' => '\w+'],
			];


		list($wasParsed_1, $result_1) = $parser->parseChecked( $uri, $patterns[0]);
		$ok_1 = $result_1 === $expected[0] && $wasParsed_1;

		list($wasParsed_2, $result_2) = $parser->parseChecked( $uri, $patterns[1] );
		$ok_2 = $result_2 === $expected[1] && !$wasParsed_2;

		$this->assertTrue( $ok_1 && $ok_2 );
	}

	protected function defaultPlaceholderRegex()
	{
		return  UriParser::DEFAULT_PLACEHOLDER_REGEX;
	}

	protected function setUp()
	{
		$this->parser = $this->container('router.parser');
	}
}
