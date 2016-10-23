<?php
namespace TeaPress\Contracts\Core;

use TeaPress\Contracts\Signals\Hub as Signals;

interface Kernel {

	/**
	* Boot the kernel. Called when booting the application.
	* This method will always be called regardless of whether the kernel is differed or not.
	*
	* This is a good point to register event listeners for events fired early in the application.
	* For example some services might fire an event when started or apply filters for a config value they need.
	*
	* @return void
	*/
	public function boot();


	/**
	 * Register the services this kernel provides. At this point,
	 * all kernels have booted but not all have registered so please refrain from directly using other services.
	 * You can use them inside your service builder closure functions though.
	 *
	 * @return void
	 */
	public function register();


	/**
	 * Runs the kernel. Called after all kernels have been registered.
	 * At this point you are free to use any services.
	 *
	 * @return void
	 */
	public function run();


	/**
	 * Get the services provided by the kernel.
	 *
	 * @return array
	 */
	public function provides();

	/**
	 * Get the events that trigger this kernel to register and run.
	 * This is only necessary if the kernel is differed.
	 *
	 * @return array
	 */
	public function when();

	/**
	 * Determine if the kernel is deferred.
	 *
	 * @return bool
	 */
	public function isDeferred();


	/**
	 * Set the signals hub instance.
	 *
	 * @param \TeaPress\Contracts\Signals\Hub $signals
	 *
	 * @return void
	 */
	public function setSignals(Signals $signals);

}