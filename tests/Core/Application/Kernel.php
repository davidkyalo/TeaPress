<?php
namespace TeaPress\Tests\Core\Application;

use TeaPress\Signals\Hub;
use Teapress\Tests\Base\TestKernel;
use TeaPress\Signals\Traits\Online;
use TeaPress\Core\Bootstrap\Factory as AppFactory;
use TeaPress\Tests\Core\Mocks\Application;


class Kernel extends TestKernel
{
	protected function serviceAliases()
	{
		return [
			'app.shared' => [
				'TeaPress\Core\ApplicationShared',
			],
			'app.new' => [
				'TeaPress\Core\ApplicationNew',
			],

		];
	}

	public function register()
	{
		$this->registerSignals();
		$this->registerAppFactory();
		$this->registerApps();

		$this->aliasServices($this->serviceAliases());
	}

	public function registerApps()
	{
		$concrete =  function($container, $args=[]) {

			$app = $container->make('app.factory', $args);

			$factory = new AppFactory($app);
			$factory->setBasePath(dirname( dirname(__DIR__) ));
			$factory->bootstrap();

			return $app;
		};

		$this->app->singleton('app.shared',$concrete);

		$this->app->bind('app.new', $concrete);

		$this->app->bind('app.non_bootstrapped', function($container, $args=[]) {
			return $container->make('app.factory', $args);
		});
	}

	protected function registerSignals()
	{
		$this->app->bind('app.signals.factory', function($app, $args)
		{
			$container = isset($args['app']) ? $args['app'] : $app;

			if($app->bound('signals.factory'))
				return $app->make('signals.factory', $args);

			$hub = new Hub( $container );
			Online::setSignals($hub);
			return $hub;
		});

		$this->app->bind('app.signals.aliases', function($app){
			return $app->serviceAliases('signals');
		});
	}

	protected function registerAppFactory()
	{
		$this->app->bind('app.factory', function($container, $args){

			$app = new Application;

			$with_signals = count($args) > 0 ? (bool) array_shift($args) : true;

			if( $with_signals ){
				$app->instance('signals', $container->make('app.signals.factory', ['app' => $app]) ) ;
				$app->alias('signals', $container['app.signals.aliases']);
			}

			return $app;
		});
	}
}

