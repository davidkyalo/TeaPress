<?php
namespace TeaPress\Tests\Core\Mocks\Bootstrap;

use TeaPress\Contracts\Core\Application;

class ItWorks
{
	public function bootstrap(Application $app)
	{
		$app->instance('it_works', true);
	}
}