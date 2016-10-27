<?php
namespace TeaPress\Tests\Core\Application;

use TeaPress\Tests\Core\Mocks\Kernels\KernelOne;
use TeaPress\Tests\Core\Mocks\Kernels\KernelTwo;
use TeaPress\Tests\Core\Mocks\Kernels\KernelThree;
use TeaPress\Tests\Core\Mocks\Kernels\KernelFour;

class KernelTest extends TestCase
{
	protected $serviceName='app.shared';

	public function testInstantiation()
	{
		$app = $this->app;
	}
}