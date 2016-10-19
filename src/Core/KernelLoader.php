<?php
namespace TeaPress\Core;


use TeaPress\Contracts\Core\Application as ApplicationContract;

class KernelLoader
{
	/**
	 * The application implementation.
	 *
	 * @var \TeaPress\Contracts\Core\Application
	 */
	protected $app;


	/**
	 * @var string
	 */
	protected $manifestKey;

	/**
	 * Create a new kernel manager instance.
	 *
	 * @param  \TeaPress\Contracts\Core\Application  $app
	 * @param  string  $manifestKey
	 *
	 * @return void
	 */
	public function __construct(ApplicationContract $app, $manifestKey)
	{
		$this->app = $app;
		$this->manifestKey = $manifestKey;
	}

	/**
	 * Register the application kernels.
	 *
	 * @param  array  $kernels
	 * @return void
	 */
	public function load(array $kernels)
	{
		$manifest = $this->loadManifest();

		if ( ($compiled = $this->shouldRecompile($manifest, $kernels)) ) {
			$manifest = $this->compileManifest($kernels);
		}

		// Next, we will register events to load the kernels for each of the events
		// that it has requested. This allows the kernel to defer itself
		// while still getting automatically loaded when a certain event occurs.
		foreach ($manifest['when'] as $kernel => $events) {
			$this->registerLoadEvents($kernel, $events);
		}

		// We will go ahead and register all of the eagerly loaded kernels with the
		// application so their services can be registered with the application as
		// a provided service. Then we will set the deferred service list on it.
		foreach ($manifest['eager'] as $kernel) {
			$this->app->register($kernel);
		}

		$this->app->addDeferredServices($manifest['deferred']);
	}

	/**
	 * Register the load events for the given kernel.
	 *
	 * @param  string  $kernel
	 * @param  array  $events
	 * @return void
	 */
	protected function registerLoadEvents($kernel, array $events)
	{
		if (count($events) < 1)
			return;

		$app = $this->app;

		$callback = function() use ($app, $kernel) {
			$app->register($kernel);
		}

		$app->make('signals')->listen($events, );
	}

	/**
	 * Compile the application manifest file.
	 *
	 * @param  array  $kernels
	 * @return array
	 */
	protected function compileManifest($kernels)
	{
		// The service manifest should contain a list of all of the kernels for
		// the application so we can compare it on each request to the service
		// and determine if the manifest should be recompiled or is current.
		$manifest = $this->freshManifest($kernels);

		foreach ($kernels as $kernel) {
			$instance = $this->getKernel($kernel);

			// When recompiling the service manifest, we will spin through each of the
			// kernels and check if it's a deferred kernel or not. If so we'll
			// add it's provided services to the manifest and note the kernel.
			if ($instance->isDeferred()) {
				foreach ($instance->provides() as $service) {
					$manifest['deferred'][$service] = $kernel;
				}

				$manifest['when'][$kernel] = $instance->when();
			}

			// If the kernels are not deferred, we will simply add it to an
			// array of eagerly loaded kernels that will get registered on every
			// request to this application instead of "lazy" loading every time.
			else {
				$manifest['eager'][] = $kernel;
			}
		}

		return $this->updateManifest($manifest);
	}

	/**
	 * Create a new kernel instance.
	 *
	 * @param  string  $kernel
	 * @return \TeaPress\Core\Kernel
	 */
	public function getKernel($kernel)
	{
		return $this->app->kernel($kernel);
	}

	/**
	 * Determine if the manifest should be compiled.
	 *
	 * @param  array  $manifest
	 * @param  array  $kernels
	 * @return bool
	 */
	public function shouldRecompile($manifest, $kernels)
	{
		return is_null($manifest) || $manifest['kernels'] != $kernels;
	}

	/**
	 * Load the kernel manifest from wp-options
	 *
	 * @return array|null
	 */
	public function loadManifest()
	{
		$manifest = get_option($this->manifestKey, null);

		if ($manifest)
			$manifest = array_merge(['when' => []], $manifest);

		return $manifest;
	}

	/**
	 * Write the service manifest file to disk.
	 *
	 * @param  array  $manifest
	 * @return array
	 */
	public function updateManifest(array $manifest)
	{
		update_option( $this->manifestKey, $manifest, false);

		return array_merge(['when' => []], $manifest);
	}

	/**
	 * Create a fresh kernel manifest data structure.
	 *
	 * @param  array  $kernels
	 * @return array
	 */
	protected function freshManifest(array $kernels)
	{
		return ['kernels' => $kernels, 'eager' => [], 'deferred' => []];
	}
}
