<?php
namespace TeaPress\Core;

use TeaPress\Utils\Arr;
use TeaPress\Utils\Str;
use InvalidArgumentException;
use TeaPress\Contracts\Signals\Hub as Signals;
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
	 * The signals implementation.
	 *
	 * @var \TeaPress\Contracts\Signals\Hub
	 */
	protected $signals;

	/**
	 * @var string
	 */
	protected $manifestKey;

	/**
	 * @var int
	 */
	protected $defaultKernelLoadEventPriority = -99;

	/**
	 * Create a new kernel manager instance.
	 *
	 * @param  \TeaPress\Contracts\Core\Application  $app
	 * @param  string  $manifestKey
	 *
	 * @return void
	 */
	public function __construct(ApplicationContract $app, Signals $signals, $manifestKey)
	{
		$this->app = $app;
		$this->signals = $signals;
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

		$firedEvents = array_keys($this->signals->fired());

		foreach ($manifest['when'] as $kernel => $events) {

			if( Arr::any( $firedEvents, array_keys($events), '->->' ) )
				$this->app->register($kernel);
			else
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
		};

		foreach ($events as $event => $priority) {
			$this->signals->once( $event, $callback, $priority);
		}
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

				$manifest['when'][$kernel] = $this->parseKernelLoadEvents($instance->when(), $kernel);
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
	 * Parse the given kernel events into an array [ event => priority ]
	 *
	 * @param  array  $events
	 * @param  string  $kernel
	 *
	 * @return array
	 */
	protected function parseKernelLoadEvents(array $events, $kernel = null)
	{
		$parsed = [];
		foreach ($events as $k => $v) {
			if(is_string($k))
				$v = [ $k => $v ];

			list($key, $priority) = $this->parseKernelLoadEvent($v, $kernel);
			$parsed[$key] = $priority;
		}

		return $parsed;
	}

	/**
	 * Parse the given event into an array [ event, priority ]
	 *
	 * @param  string|array  $event
	 * @param  string  $kernel
	 *
	 * @return array
	 */
	protected function parseKernelLoadEvent($event, $kernel =null)
	{
		$default = $this->defaultKernelLoadEventPriority;

		if(is_array($event) && count($event) > 1){
			$priority = count($event) > 2 ? array_pop($event) : $default;
			$event = [ $this->signals->getTag($event) => $priority];
		}
		elseif( is_string($event) ){
			$segments = array_pad(explode('|', $event), 2, $default);
			$event = [ $segments[0] => ( $segments[1] == '' ? $default : $segments[1] ) ];
		}

		if( !is_array($event) || count($event) > 1 || !is_string( $tag = key($event) ) || !is_numeric( $priority = current($event) ) ){
			$e = Str::minify(var_export($event, true));
			throw new InvalidArgumentException("Error Parsing Kernel Load Event [{$e}] in kernel [{$kernel}]");
		}

		return  [$tag, (int) $priority];
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
		return $this->app->debug() || is_null($manifest) || $manifest['kernels'] != $kernels;
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
