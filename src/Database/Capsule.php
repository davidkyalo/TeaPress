<?php
namespace TeaPress\Database;

use TeaPress\Contracts\Core\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as BaseCapsule;

class Capsule extends BaseCapsule
{

	protected $dispatcher;

	/**
	 * Create a new database capsule manager.
	 *
	 * @param  \Illuminate\Container\Container|null  $container
	 * @param  \Illuminate\Contracts\Events\Dispatcher|null  $dispatcher
	 * @return void
	 */
	public function __construct(Container $container = null, Dispatcher $dispatcher = null)
	{
		parent::__construct($container);
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Get the current event dispatcher instance.
	 *
	 * @return \Illuminate\Contracts\Events\Dispatcher|null
	 */
	public function getEventDispatcher()
	{
		return $this->dispatcher;
	}

	/**
	 * Set the event dispatcher instance to be used by connections.
	 *
	 * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
	 * @return void
	 */
	public function setEventDispatcher(Dispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}
}
