<?php
namespace TeaPress\Contracts\Plugin;


interface Plugin
{

	public function addProviders($providers);

	public function register();

	public function registerProviders();

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public function activate();

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public function deactivate();


	/**
	 * Set the base path.
	 *
	 * @param $path
	 */
	public function setBasePath($path);

	/**
	 * Get the base path.
	 *
	 * @return mixed
	 */
	public function basePath();

	/**
	 * Sets the IoC Container.
	 *
	 * @param \Illuminate\Contracts\Container\Container $container
	 */
	public function setContainer(Container $container);

	/**
	 * Gets the IoC Container.
	 *
	 * @return \Illuminate\Contracts\Container\Container
	 */
	public function getContainer();

}