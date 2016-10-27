<?php
namespace TeaPress\Tests\Core\Mocks\Kernels;

use TeaPress\Core\Kernel;

use TeaPress\Contracts\Signals\Hub as Signals;
use TeaPress\Contracts\Core\Container as ContainerContract;


class Base extends Kernel
{
	protected static $instances = [];

	/**
	 * Creates the kernel instance.
	 *
	 * @param \TeaPress\Contracts\Core\Container $app
	 * @param \TeaPress\Contracts\Signals\Hub $signals
	 *
	 * @return void
	 */
	public function __construct(ContainerContract $app, Signals $signals = null)
	{
		static::$instances[get_called_class()][] = microtime();
		parent::__construct($app, $signals);
	}

	public static function getAllInstances()
	{
		return static::$instances;
	}

	public static function getInstances($klass = null)
	{
		$klass = $klass ?: get_called_class();
		return isset(static::getAllInstances()[$klass]) ? static::getAllInstances()[$klass] : [];
	}

	public static function flushInstances($klasses=true)
	{
		if($klasses === true){
			static::$instances = [];
			return;
		}

		foreach ((array) $klasses as $klass) {
			if(isset(static::$instances[$klass]))
				unset(static::$instances[$klass]);
		}

	}

	public static function countAll()
	{
		$i = static::getAllInstances();
		return count($i, COUNT_RECURSIVE) - count($i);
	}

	public static function count($klass = null)
	{
		return count(static::getInstances($klass));
	}
}
