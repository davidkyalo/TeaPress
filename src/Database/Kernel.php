<?php
namespace TeaPress\Database;

use TeaPress\Core\Kernel as BaseKernel;
use TeaPress\Database\ORM\Model;


class Kernel extends BaseKernel
{

	/**
	 * Register the module's services.
	 */
	public function boot()
	{
		$this->setupWpDbConfig();

	}

	/**
	 * Register the module's services.
	 */
	public function register()
	{
		$this->share('DB', function($app){
			return Database::instance();
		});

		$this->share('wpdb', function($app){
			global $wpdb;
			return $wpdb;
		});

		$this->share('db_conn.resolver', function($app){
			return new Resolver;
		});

	}

	/**
	 * Setup WP's database configuration.
	 */
	protected function setupWpDbConfig()
	{
		$config = $this->app['config'];
		if(!$config->has()){
			$config->set('database.connections.wp', [

			];
		}
	}

}