<?php
namespace TeaPress\Tests\Core\Application;

use TeaPress\Core\Application;
use TeaPress\Tests\Base\ServiceTestCase;

class BaseTestCase extends ServiceTestCase
{
	protected $serviceName;
	protected $serviceClass = Application::class;
	protected $servicePropertyName = 'app';
	protected $withSignals = true;

	protected function setUp()
	{
		parent::setUp();
		$this->resolveTheApps();
	}

	protected function resolveTheApps()
	{
		for ($i=0; $i < 4; $i++) {
			$this->sharedApp();
			$this->newApp();
		}
	}

	protected function getMakeServiceParameters()
	{
		return ['with_signals' => $this->withSignals];
	}

	protected function sharedApp()
	{
		return $this->getContainer()->make('app.shared');
	}

	protected function newApp()
	{
		return $this->getContainer()->make('app.new');
	}
}