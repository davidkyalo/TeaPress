<?php

namespace TeaPress\Http;

use TeaPress\Http\Response;
use TeaPress\Arch\BaseKernel;
use TeaPress\Events\Dispatcher;

class Kernel extends BaseKernel {

	public function run(){
		// $this->action('.cookieJarSendCookies', 'sending_http_headers');
		// $this->app->make('router');

	}


	protected function addHooks(){
	}

	public function register(){

		// $this->app->class_loader->alias( 'TeaPress\\Http\\Controller', 'TeaPress\\Http\\Handler');

		$this->instance([ Request::class => 'request'], Request::capture());

		$this->share( [ CookieJar::class => 'cookie_jar'], function($app){
			return new CookieJar( $app['request'] );
		});

		// $this->share('request.classic', function($app){
		// 	return new RequestClassic();
		// });

		$this->share([ Input::class => 'input' ]);

		$this->share([ UrlFactory::class => 'url'], function($app){
			$factory = new UrlFactory;
			$factory->setRequest( $app['request'] );
			return $factory;
		});

		$this->share([ RouterClassic::class => 'classic_router'], function($app){
			return new RouterClassic($app['request'], $app['url']);
		});

		$this->share([ Routing\Router::class => 'router'], function($app){
			$router = new Routing\Router(
									$app,
									$app['request'],
									$app['response'],
									$app->make(Dispatcher::class) );
			$app['url']->setRouter( $router );
			return $router;
		});

		$this->share([ Response\Factory::class => 'response'], function($app){
			return new Response\Factory(
								$app->make("TeaPress\\View\\Factory"),
								$app['request'],
								$app['url'],
								$app['cookie_jar'],
								$app->make(Dispatcher::class) );
		});

	}

	public function cookieJarSendCookies(){
		$jar = $this->app['cookie_jar'];
		$jar->dispatchQueue();
	}
}