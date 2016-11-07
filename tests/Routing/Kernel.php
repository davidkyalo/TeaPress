<?php
namespace TeaPress\Tests\Routing;

use TeaPress\Routing\RouteCollector;
use TeaPress\Tests\Base\TestKernel;
use TeaPress\Tests\Routing\Mocks\Router;
use TeaPress\Tests\Routing\Mocks\Request;
use TeaPress\Tests\Routing\Mocks\UriParser;
use TeaPress\Routing\Matching\Factory as Matcher;

class Kernel extends TestKernel
{

	protected function serviceAliases()
	{
		return [
			'router' => [
				'TeaPress\Routing\Router',
				'TeaPress\Contracts\Routing\Router'
			]
		];
	}

	public function register()
	{
		$this->app->singleton( 'router.shared', function($app){
			return new Router($app['router.collector'], $app['router.parser'], $app, $app['signals']);
		});

		$this->app->bind('router', function($app){
			return new Router($app['router.collector'], $app['router.parser'], $app, $app['signals']);
		});

		$this->registerMatcher();
		$this->registerUrlParser();
		$this->registerRouteCollector();
		$this->registerTestRequest();

		$this->aliasServices($this->serviceAliases());
	}

	protected function registerUrlParser()
	{
		$this->app->singleton('router.parser', function($app){
			return new UriParser();
		});
	}

	protected function registerMatcher()
	{
		$this->app->singleton('router.matcher', function($app){
			return new Matcher();
		});
	}

	protected function registerRouteCollector()
	{
		$this->app->bind('router.collector', function($app){
			return new RouteCollector($app['router.matcher']);
		});
	}


	protected function registerTestRequest()
	{
		$this->app->bind('router.request', function($app){
			return Request::capture();
		});
	}



	public function boot()
	{
		require_once __DIR__.'/Mocks/functions.php';
	}

}