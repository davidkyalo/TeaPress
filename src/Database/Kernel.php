<?php

namespace TeaPress\ORM;

use TeaPress\Arch\BaseKernel;
use WeDevs\ORM\Eloquent\Resolver;

class Kernel extends BaseKernel {

	public function register(){
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
}