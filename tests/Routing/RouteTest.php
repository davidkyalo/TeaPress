<?php
namespace TeaPress\Tests\Routing;

use TeaPress\Utils\Arr;
use TeaPress\Tests\Base\TestCase;

/**
*
*/
class RouteTest extends TestCase
{
	protected $router;

	public function testAs()
	{
		$router = $this->newRouter();

		$route = $router->get('/')->as('name');

		$this->assertEquals('name', $route->getName());

	}

	public function testTo()
	{
		$router = $this->newRouter();

		$route = $router->get('/')->to('handler');

		$this->assertEquals('handler', $route->getHandler());

	}

	public function testAddMiddleware()
	{
		$router = $this->newRouter();

		$route = $router->get('/')->middleware('a|b', 'c');
		$route->middleware([ 'd', 'e|f']);

		$this->assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], $route->getMiddleware());

	}

	public function testParsed()
	{
		$router = $this->newRouter();

		$route = $router->get('/user/{id:[^/]+}[/{name:\w+}]');

		$route->where('id', '\d+');

		$expected = [
				'id' => '[^/]+',
				'name' => '\w+'
			];

		$results =  $route->getPatterns();
		// pprint('Parsed', $results);
		$this->assertEquals($expected, $results);

	}

	protected function newRouter()
	{
		return $this->container('router');
	}

	protected function setUp()
	{
		$this->router = $this->container('router.shared');
	}
}
