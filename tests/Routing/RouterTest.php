<?php
namespace TeaPress\Tests\Routing;

use TeaPress\Utils\Arr;
use TeaPress\Tests\Base\TestCase;

/**
*
*/
class RouterTest extends TestCase
{
	protected $router;

	public function testAddRouteReturnsRouteInstance()
	{
		$router = $this->newRouter();

		$route = $router->get('/foo/');

		$this->assertInstanceOf('TeaPress\Contracts\Routing\Route', $route);
	}

	public function testAddRouteCollectsToRoutesList()
	{
		$router = $this->newRouter();

		$route = $router->put('/bar');

		$collected = Arr::last($router->getRoutes());

		$this->assertSame($route, $collected);
	}

	public function testAddRouteCollectsByMethod()
	{
		$router = $this->newRouter();

		$route = $router->post('/users/');

		$expected = ['/users/' => $route];

		$actual = $router->getRoutes('POST');

		$this->assertSame($expected, $actual);
	}

	public function testAddRouteWithClosureHandler()
	{
		$router = $this->newRouter();

		$handler = function(){};

		$route = $router->get('/foo', $handler);

		$this->assertSame($handler, $route->getHandler());
	}

	public function testAddRouteWithFunctionHandler()
	{
		$router = $this->newRouter();

		$handler = 'tea_tests_routing_sample_handler';

		$route = $router->get('/foo', $handler);

		$this->assertSame($handler, $route->getHandler());
	}

	public function testAddRouteWithControllerHandler()
	{
		$router = $this->newRouter();

		$handler = 'FooController@get';

		$route = $router->get('/foo', $handler);

		$this->assertSame($handler, $route->getHandler());
	}

	public function testAddRouteWithCallableArrayHandler()
	{
		$router = $this->newRouter();

		$handler = [$this, __FUNCTION__];

		$route = $router->get('/foo', $handler);

		$this->assertSame($handler, $route->getHandler());
	}

	public function testAddRouteWithNamedHandler()
	{
		$router = $this->newRouter();

		$handler = 'FooController@get';

		$name = 'foo';

		$expected = ['as' => $name, 'handler' => $handler];

		$route = $router->get('/foo', $expected);

		$actual = [
			'as' => $route->getName(),
			'handler' => $route->getHandler(),
		];

		$this->assertSame($expected, $actual);
	}


	public function testAddRouteWithNamedHandler2()
	{
		$router = $this->newRouter();

		$handler = 'FooController@get';

		$name = 'foo';

		$expected = ['as' => $name, 'to' => $handler];

		$route = $router->get('/foo', $expected);

		$actual = [
			'as' => $route->getName(),
			'to' => $route->getHandler(),
		];

		$this->assertSame($expected, $actual);
	}

	public function testAddRouteWithNamedHandler3()
	{
		$router = $this->newRouter();

		$handler = 'FooController@get';

		$name = 'foo';

		$expected = ['as' => $name, $handler];

		$route = $router->get('/foo', $expected);

		$actual = [
			'as' => $route->getName(),
			 $route->getHandler(),
		];

		$this->assertSame($expected, $actual);
	}


	public function testGroupUriPrefix()
	{
		$router = $this->newRouter();

		$router->group('foo', function($router){

			$router->group(['prefix' => 'bar'], function($router){
				$baz = $router->get('baz');
				$this->assertEquals('foo/bar/baz', $baz->getUri());
			});
		});
	}

	public function testGroupName()
	{
		$router = $this->newRouter();

		$router->group(['as' => 'foo'], function($router){

			$router->group(['as' => 'admin.bar.'], function($router){
				$baz = $router->get('foo/bar')->as('.baz');
				$this->assertEquals( 'foo.admin.bar.baz' , $baz->getName());
			});
		});
	}


	public function testGroupNamespace()
	{
		$router = $this->newRouter();

		$router->group(['namespace' => '\\Foo'], function($router){

			$router->group(['namespace' => 'Bar'], function($router){
				$handler = 'BarController@action';
				$baz = $router->get('foo/bar', $handler);
				$this->assertEquals( 'Foo\\Bar\\BarController@action' , $baz->getHandler());
			});
		});
	}

	public function testGroupCallableHanderNotNamespaced()
	{
		$router = $this->newRouter();

		$router->group(['namespace' => '\\Foo'], function($router){

			$router->group(['namespace' => 'Bar', 'to' => 'BarController@action'], function($router){
				$handler = 'tea_tests_routing_sample_handler';
				$baz = $router->get('foo/bar', $handler);
				$this->assertEquals( 'tea_tests_routing_sample_handler' , $baz->getHandler());
			});
		});
	}

	public function testGroupedHander()
	{
		$router = $this->newRouter();

		$router->group(['namespace' => '\\Foo', 'to' => 'Foo\\BarController@action'], function($router){

			$router->group(['namespace' => 'Bar'], function($router){
				$baz = $router->get('foo/bar');
				$this->assertEquals( 'Foo\\BarController@action' , $baz->getHandler());

			});
		});
	}

	public function testGroupedMiddleware()
	{
		$router = $this->newRouter();

		$router->group(['middleware' => ['a', 'b', 'c']], function($router){

			$router->group(['middleware' => ['c|d|e']], function($router){

				$baz = $router->get('foo/bar');

				$expected = ['a', 'b', 'c', 'd', 'e'];

				$this->assertEquals($expected, $baz->getMiddleware());

			});
		});
	}

	public function testDispatch()
	{
		$router = $this->newRouter();

		$baz = $router->get('foo/bar/baz')->to(function(){
			return "The Response!!";
		});

		$response = $router->dispatch( $this->request('/foo/bar/baz', 'GET') );
		$this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
		$this->assertEquals("The Response!!", $response->getContent());
	}

	public function testDispatchToController()
	{
		$router = $this->newRouter();

		$baz = $router->get('user/{id:\d+}[/{foo}]')
				->to('TeaPress\Tests\Routing\Mocks\Controller@param')
				->default("foo", 'foo');

		$response = $router->dispatch( $this->request('/user/25', 'GET') );
		$this->assertEquals( '25,param,foo' , $response->getContent());
	}

	public function testDispatchToControllerWithActionChanged()
	{
		$router = $this->newRouter();

		$baz = $router->get('foo')
				->to('TeaPress\Tests\Routing\Mocks\Controller@change');

		$response = $router->dispatch( $this->request('/foo', 'GET') );
		$this->assertEquals( 'response changed' , $response->getContent());
	}

	public function testDispatchToControllerWithMissingAction()
	{
		$router = $this->newRouter();

		$baz = $router->get('foo')
				->to('TeaPress\Tests\Routing\Mocks\Controller@foo');

		$response = $router->dispatch( $this->request('/foo', 'GET') );
		$this->assertEquals( 'foo missing' , $response->getContent());
	}

	protected function request($path = '/test', $method = 'GET')
	{
		return $this->container('router.request')->setTestPath($path)->setMethod($method);
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
