<?php
namespace TeaPress\Tests\Core\Mocks;

use TeaPress\Tests\Core\Mocks\Kernels\Base as BaseKernel;
use TeaPress\Core\Application as BaseApplication;

class Application extends BaseApplication
{
	protected function registerBaseBindings()
	{
		parent::registerBaseBindings();

		$this->alias('app', self::class);
	}

	public function bindTestAppCallback($event, $callback, $priority = null, $once = false)
	{
		return $this->bindAppCallback($event, $callback, $priority, $once);
	}

	public function fireTestAppCallbacks($event, ...$payload)
	{
		return $this->fireAppCallbacks($event, ...$payload);
	}

	public function flush()
	{
		parent::flush();
		$this->registeredKernels = [];
	}
}