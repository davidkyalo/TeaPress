<?php
namespace TeaPress\Tests\Core\Mocks\Bootstrap;

use TeaPress\Contracts\Core\Application;

class CountExecutions
{
	public static $executions = 0;

	public function bootstrap(Application $app)
	{
		$app->instance('num_executions', ( $app->bound('num_executions') ? $app['num_executions'] : 0 ) + 1);
	}
}