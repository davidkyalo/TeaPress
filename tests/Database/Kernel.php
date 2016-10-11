<?php
namespace TeaPress\Tests\Database;

use TeaPress\Database\Capsule;
use TeaPress\Tests\Base\TestKernel;

class Kernel extends TestKernel
{

	protected function serviceAliases()
	{
		return [
			'db' => [
				'Illuminate\Database\ConnectionResolverInterface',
			],
			'db.container' => [
				'Illuminate\Database\ConnectionInterface',
			]
		];
	}

	public function register()
	{
		$this->app->singleton('db.manager', function($app){
			global $wpdb;

			$capsule = new Capsule($app, $app['signals']);
			$capsule->addConnection([
				'driver' => 'mysql',
				'host' => DB_HOST,
				'database' => DB_NAME,
				'username' => DB_USER,
				'password' => DB_PASSWORD,
				'charset' => DB_CHARSET,
				'collation' => DB_COLLATE ?: $wpdb->collate,
				'prefix' => $wpdb->prefix
			]);

			$capsule->setAsGlobal();
			$capsule->bootEloquent();

			return $capsule;
		});

		$this->app->bind('db', function ($app) {
			return $app['db.manager']->getDatabaseManager();
		});

		$this->app->bind('db.connection', function ($app) {
			return $app['db']->connection();
		});

		$this->aliasServices($this->serviceAliases());
	}

	public function boot()
	{

	}
}