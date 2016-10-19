<?php
namespace TeaPress\Core;

class AppFactory
{

	/**
	 * @var string
	 */
	protected $app_class;

	/**
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $app;

	/**
	 * @var array
	 */
	protected $bootstrapers = [
		'TeaPress\Core\Bootstrap\LoadConfiguration',
		'TeaPress\Core\Bootstrap\RegisterFacades',
		'TeaPress\Core\Bootstrap\BootKernels',
		'TeaPress\Core\Bootstrap\RegisterKernels',
		'TeaPress\Core\Bootstrap\RunKernels'
	];

	protected $runsWhen = ['plugins_loaded', -9999];


	public function __construct($app_class = null)
	{
		if($app_class)
			$this->app_class = $app_class;

		$this->initialize();
	}

	protected function initialize()
	{
		$this->createApp();
	}

	protected function createApp()
	{
		$class = $this->app_class;
		$this->app = $class();
	}

	public function start()
	{

	}

	protected function run()
	{

	}
}

