<?php
namespace TeaPress\Core;

use TeaPress\Contracts\Core\Plugin;
use TeaPress\Contracts\Core\ServiceProvider as Contract;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

abstract class ServiceProvider extends IlluminateServiceProvider implements Contract
{

	protected $app;

	protected $plugin;

	// protected $hooks;

	protected $defer = false;

	protected $registered = false;

	/**
	 * Create a new service provider instance.
	 *
	 * @param  \TeaPress\Contracts\Core\Application  $app
	 * @param  \TeaPress\Contracts\Core\Plugin|null  $plugin
	 *
	 * @return void
	 */
	public function __construct($app, $plugin = null)
	{
		$this->app = $app;
		$this->plugin = $plugin;
		$this->init();
	}

	protected function init(){}


	abstract public function register();

	protected function registerServiceAliases(array $aliases)
	{
		foreach ($aliases as $key => $aliases) {
			foreach ((array) $aliases as $alias) {
				$this->app->alias($key, $alias);
			}
		}
	}

	/**
	 * Determine if the provider has been registered.
	 *
	 * @return bool
	 */
	public function isRegistered()
	{
		return $this->registered;
	}

	/**
	 * Determine if the provider has been registered.
	 *
	 * @param bool 	$registered
	 *
	 * @return void
	 */
	public function setRegistered($registered)
	{
		$this->registered = $registered;
	}


	public function boot(){}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

	/**
	 * Get the actions that trigger this service provider to register.
	 *
	 * @return array
	 */
	public function when()
	{
		return [];
	}

	/**
	 * Determine if the provider is deferred.
	 *
	 * @return bool
	 */
	public function isDeferred()
	{
		return $this->defer;
	}


}