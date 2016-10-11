<?php
namespace TeaPress\Contracts\Core;

interface ServiceProvider {

	/**
	 * Determine if the provider has been registered.
	 *
	 * @return bool
	 */
	public function isRegistered();


	/**
	 * Determine if the provider has been registered.
	 *
	 * @param bool 	$registered
	 *
	 * @return void
	 */
	public function setRegistered($registered);


	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register();


	/**
	 * Boot the provider.
	 *
	 * @return void
	 */
	public function boot();

	/**
	 * Determine if the provider is deferred.
	 *
	 * @return bool
	 */
	public function isDeferred();

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides();

	/**
	 * Get the actions that trigger this service provider to register.
	 *
	 * @return array
	 */
	public function when();


}