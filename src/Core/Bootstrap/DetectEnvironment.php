<?php

namespace TeaPress\Core\Bootstrap;

use Dotenv;
use InvalidArgumentException;
use TeaPress\Contracts\Core\Application;

class DetectEnvironment
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param  \TeaPress\Contracts\Core\Application  $app
	 *
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		try {
			Dotenv::load($app->environmentPath(), $app->environmentFile());
		} catch (InvalidArgumentException $e) {
			//
		}

		$app->detectEnvironment(function () {
			return env('APP_ENV', 'production');
		});
	}
}
