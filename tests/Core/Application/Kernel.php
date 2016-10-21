<?php
namespace TeaPress\Tests\Core\Application;

use TeaPress\Core\Application;
use Teapress\Tests\Base\TestKernel;

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
			]
		];
	}

	public function register()
	{

		$this->app->bind('app.signals', function($app){
			return $app['signals'];
		});

		$this->app->bind('app.signals_aliases', function($app){
			return $app->serviceAliases('signals');
		});

		$container = $this->app;

		$setSignals = function($app) use($container){
			$app->instance('signals', $container['app.signals']);
			$app->alias('signals', $container['app.signals_aliases']);
			return $app;
		};

		$this->app->singleton('app.shared', function($container, $params) use ($setSignals){
			$app = new Application;
			if(isset($params['with_signals']) && $params['with_signals'])
				$setSignals( $app );

			return $app;
		});

		$this->app->bind('app.new', function($container, $params) use ($setSignals){
			$app = new Application;

			if(isset($params['with_signals']) && $params['with_signals'])
				$setSignals( $app );

			return $app;
		});

		$this->aliasServices($this->serviceAliases());
	}
}

