<?php
namespace TeaPress\Tests\Core\Application;

use TeaPress\Core\Application;
use TeaPress\Tests\Base\ServiceTestCase;

class TestCase extends ServiceTestCase
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
		return ['signals' => $this->withSignals];
	}

	protected function sharedApp()
	{
		return $this->getContainer()->make('app.shared', $this->getMakeServiceParameters());
	}

	protected function newApp($bootstrapped = false)
	{
		$key = $bootstrapped ? 'app.new' : 'app.non_bootstrapped';
		return $this->getContainer()->make($key, $this->getMakeServiceParameters());
	}
}