<?php
namespace TeaPress\Core\Bootstrap;

use TeaPress\Utils\Str;
use TeaPress\Utils\Arr;
use BadMethodCallException;
use InvalidArgumentException;
use TeaPress\Signals\Traits\Online;
use TeaPress\Contracts\Core\Application as AppContract;
use TeaPress\Core\Exception\ApplicationNotReadyException;

class Factory
{
	use Online;

	/**
	 * @var string
	 */
	protected $appClass = 'TeaPress\Core\Application';

	/**
	 * @var \TeaPress\Contracts\Core\Application
	 */
	protected $app;

	/**
	 * @var bool
	 */
	protected $canBootstrap = false;

	/**
	 * @var bool
	 */
	protected $bootstrapped = false;

	/**
	* The bootstrap classes for the application.
	*
	* They are of 3 types ie: base, eager and lazy.
	*
	* 1. Base.
	* 		Executed once the app instance is created and the factory has initialized (before bootstrapping).
	* 		This is done during the factory's construction. This is reserved for bootstrappers that should
	* 		be executed before the factory instance is ready.
	* 		Eg. Starting the signals hub which is in charge of events and wordpress' actions/filters functionality.
	*
	* 2. Eager.
	* 		Eagerly loaded bootstrappers. Executed once the bootstrap method is called.
	*
	* 3. Lazy.
	* 		Lazily loaded bootstrappers. When the bootstrap method is called, a callback will be bound to
	* 		the action hook returned by the getBootstrappersHook (the $bootstrappersHook property by default).
	* 		This callback executes all the lazy bootstrappers.
	*
	* 		The default hook (also the recommended) is ['plugins_loaded', -9999(the priority)]
	* 		TeaPress should be bootstrapped as early as possible but it's also good to let the plugins to load.
	* 		This way, plugins have access to the app before it's bootstrapped.
	* 		So you can split your functionality into normal plugins and still have full access to TeaPress.
	*
	*
	* @var array
	*/
	protected $bootstrappers = [

		'base' => [
			'init' => 'TeaPress\Core\Bootstrap\Initialize',
		],

		'eager' => [
			'config' 		=> 'TeaPress\Core\Bootstrap\LoadConfiguration',
			'teapress' 		=> 'TeaPress\Core\Bootstrap\LoadTeaPress',
		],

		'lazy' => [
			'facades' 	=> 'TeaPress\Core\Bootstrap\RegisterFacades',

			'kernels' => [
				'boot' => [
					'TeaPress\Core\Bootstrap\BootKernels',
				],
				'register' => [
					'TeaPress\Core\Bootstrap\RegisterKernels',
				],
				'run' => [
					'TeaPress\Core\Bootstrap\RunKernels',
				]
			]
		]
	];

	/**
	 * @var array
	 */
	protected $bootstrappersHook = ['plugins_loaded', -9999];

	/**
	* Create the factory instance
	*
	* @param string|null|\TeaPress\Contracts\Core\Application  $app
	*
	* @return void
	*/
	public function __construct($app = null)
	{
		$this->setApp( (is_object($app) ? $app : $this->createApp($app)) );

		$this->executeBaseBootstrappers();

		$this->initialize();
	}

	/**
	* Initialize the factory before bootstrapping.
	*
	* @return void
	*/
	protected function initialize()
	{
		//
	}

	/**
	* Get the application instance.
	*
	* @return \TeaPress\Contracts\Core\Application
	*/
	public function app()
	{
		return $this->app;
	}

	/**
	* Set the application instance.
	*
	* @param  \TeaPress\Contracts\Core\Application  $app
	*
	* @return void
	*/
	protected function setApp(AppContract $app)
	{
		$this->app = $app;
	}

	/**
	* Create the application instance.
	*
	* @param  string|null $app
	*
	* @return \TeaPress\Contracts\Core\Application
	*/
	protected function createApp($appClass = null)
	{
		$appClass = $appClass ?: $this->appClass;
		return new $appClass;
	}

	/**
	* Set the application's base path.
	*
	* @param  string $path
	*
	* @return void
	*/
	public function setBasePath($path)
	{
		if($this->bootstrapped){
			trigger_error("Late setting of the application base path. Application already bootstrapped.");
		}

		$this->app->setBasePath($path);
	}

	/**
	* Get the application's base path.
	*
	* @return string
	*/
	public function basePath()
	{
		$this->app->basePath();
	}

	/**
	* Bootstrap the application.
	*
	* @throws \TeaPress\Core\Exception\ApplicationNotReadyException
	*
	* @return void
	*/
	public function bootstrap()
	{
		if($this->bootstrapped) return;

		// Prepare the factory for bootstrapping
		$this->prepare();

		if(!$this->canBootstrap()){
			throw new ApplicationNotReadyException("Application not ready for bootstrapping.");
		}

		$this->bootstrapApplication();

		$this->bootstrapped = true;
	}

	/**
	* Determine whether the application is ready to be bootstrapped.
	*
	* @return bool
	*/
	protected function canBootstrap()
	{
		return $this->canBootstrap;
	}

	/**
	* Prepare the factory and/or application for bootstrapping.
	* Checks whether the application is ready for bootstrapped.
	* If the application is ready, the canBootstrap property should be set to true.
	*
	* @return void
	*/
	protected function prepare()
	{
		if($this->canBootstrap) return;

		$this->canBootstrap = $this->pathsAreReady();
	}

	/**
	* Determine whether the application's critical paths have been set.
	*
	* @return bool
	*/
	protected function pathsAreReady()
	{
		return $this->app->basePath() && $this->app->configPath();
	}

	/**
	* Bootstrap the application.
	* Runs and queues the registered eager and lazy bootstrap classes.
	*
	* @return void
	*/
	protected function bootstrapApplication()
	{
		$this->executeEagerBootstrappers();
		$this->queueLazyBootstrappers();
	}


	/**
	* Get all bootstrap classes.
	*
	* @param bool 			$collapse
	*
	* @return array
	*/
	public function getAllBootstrappers($collapse = false)
	{
		return $collapse ? Arr::flatten($this->bootstrappers) : $this->bootstrappers;
	}

	/**
	* Get bootstrap classes under the given key or all if key is null.
	*
	* @param string|null 	$key
	* @param bool 			$collapse
	* @param mixed 			$default
	*
	* @return array|string|mixed
	*/
	public function getBootstrappers($key=null, $collapse = false, $default=null)
	{
		$bootstrappers = Arr::get($this->getAllBootstrappers(false), $key, NOTHING);

		if( $bootstrappers === NOTHING )
			return value($default);

		return $collapse ? Arr::flatten( (array) $bootstrappers ) : $bootstrappers;
	}

	/**
	* Get base bootstrap classes under the given key or all the base ones if key is null.
	*
	* @param string|null 	$key
	* @param bool 			$collapse
	* @param mixed 			$default
	*
	* @return array|string|mixed
	*/
	public function getBaseBootstrappers($key=null, $collapse=false, $default=null)
	{
		$key = is_null($key) ? "base" : "base.{$key}";
		return $this->getBootstrappers($key, $collapse, $default);
	}

	/**
	* Get eager bootstrap classes under the given key or all the eager ones if key is null.
	*
	* @param string|null 	$key
	* @param bool 			$collapse
	* @param mixed 			$default
	*
	* @return array|string|mixed
	*/
	public function getEagerBootstrappers($key=null, $collapse=false, $default=null)
	{
		$key = is_null($key) ? "eager" : "eager.{$key}";
		return $this->getBootstrappers($key, $collapse, $default);
	}

	/**
	* Get lazy bootstrap classes under the given key or all the lazy ones if key is null.
	*
	* @param string|null 	$key
	* @param bool 			$collapse
	* @param mixed 			$default
	*
	* @return array|string|mixed
	*/
	public function getLazyBootstrappers($key=null, $collapse=false, $default=null)
	{
		$key = is_null($key) ? "lazy" : "lazy.{$key}";
		return $this->getBootstrappers($key, $collapse, $default);
	}

	/**
	* Get the event hook on which lazy bootstrappers should be executed
	* This should return an array ['wp-action', 'priority'] eg  ['plugins_loaded', -99]
	*
	* @return array
	*/
	public function getBootstrappersHook()
	{
		return $this->bootstrappersHook;
	}

	/**
	* Run the given array of bootstrap classes.
	*
	* @param  array  $bootstrappers
	* @param  bool  $force
	* @param  bool  $silent
	*
	* @return void
	*/
	protected function executeBootstrappers($bootstrappers, $force =false, $silent = false)
	{
		$this->app->bootstrapWith($bootstrappers, $force, $silent);
	}

	/**
	* Run all the base bootstrap classes.
	*
	* @return void
	*/
	protected function executeBaseBootstrappers()
	{
		$this->executeBootstrappers( $this->getBaseBootstrappers(null, true, []) , false, true );
	}

	/**
	* Run all the eager bootstrap classes.
	*
	* @return void
	*/
	protected function executeEagerBootstrappers()
	{
		$this->fireAppEvent('bootstrapping.eager');
		$this->executeBootstrappers( $this->getEagerBootstrappers(null, true, []) );
		$this->fireAppEvent('bootstrapped.eager');
	}

	/**
	* Run all the lazy bootstrap classes.
	*
	* @return void
	*/
	protected function executeLazyBootstrappers()
	{
		$this->fireAppEvent('bootstrapping.lazy');
		$this->executeBootstrappers( $this->getEagerBootstrappers(null, true, []) );
		$this->fireAppEvent('bootstrapped.lazy');
	}

	/**
	* Make sure all bootstrap classed have executed.
	*
	* @return void
	*/
	protected function executeSkippedBootstrappers()
	{
		$all = $this->getAllBootstrappers(true);
		$skipped = array_diff( $all, array_keys( (array) $this->app->bootstrapped()) );

		if(count($skipped) > 0)
			$this->executeBootstrappers( $skipped );
	}

	/**
	* Register lazily loaded bootstrappers to their respective events.
	*
	* @return void
	*/
	protected function queueLazyBootstrappers()
	{
		list($event, $priority) = (array) $this->getBootstrappersHook();

		$callback = [$this, '_executeLazyBootstrappersCallback'];

		if($this->eventWasFired($event)){
			call_user_func($callback);
		}
		else{
			$this->bindEventCallback($event, $callback, ($priority ?: -999), true);
		}

	}

	public function _executeLazyBootstrappersCallback()
	{
		$this->executeLazyBootstrappers();
		$this->setReady();
	}
	/**
	* Mark the application as fully loaded,
	*
	* @return void
	*/
	protected function setReady()
	{
		$this->executeSkippedBootstrappers();

		$this->bootstrapped = true;

		$this->app->setAppReady();
	}

	/**
	* Get the namespaced application event tag.
	*
	* @param string 	$event
	*
	* @return string|array
	*/
	protected function getAppEventTag($event)
	{
		return $this->app->appEventTag($event);
	}

	/**
	* Determine if an event or action was fired.
	*
	* @param string|array 	$event
	*
	* @return bool
	*/
	protected function eventWasFired($event)
	{
		return (bool) $this->getSignals()->fired( $event );
	}

	/**
	* Determine if an application event or action was fired.
	*
	* @param string|array 	$event
	*
	* @return bool
	*/
	protected function appEventWasFired($event)
	{
		return (bool) $this->getSignals()->fired( $this->getAppEventTag($event) );
	}

	/**
	* Fires an event.
	*
	* @param string 	$event
	* @param mixed 		...$payload
	*
	* @return mixed
	*/
	protected function fireEvent($event, ...$payload)
	{
		return $this->getSignals()->fire( $event, $payload );
	}

	/**
	* Fires an application's event.
	*
	* @param string 	$event
	* @param mixed 		...$payload
	*
	* @return mixed
	*/
	protected function fireAppEvent($event, ...$payload)
	{
		$payload = array_merge($payload, [ $this->app, $this]);
		return $this->getSignals()->fire( $this->getAppEventTag($event), $payload );
	}

	/**
	* Bind the given callback to the specified $event.
	*
	* @param  string						$event
	* @param  \Closure|array|string 		$callback
	* @param  int						$priority
	* @param  bool							$once
	*
	* @return void
	*/
	protected function bindEventCallback($event, $callback, $priority = null, $once = false)
	{
		$this->getSignals()->bind($event, $callback, $priority, null, $once);
	}


	/**
	* Bind the given callback to the specified application's $event.
	*
	* @param  string						$event
	* @param  \Closure|array|string 		$callback
	* @param  int						$priority
	* @param  bool							$once
	*
	* @return void
	*/
	protected function bindAppCallback($event, $callback, $priority = null, $once = false)
	{
		$this->getSignals()->bind($this->getAppEventTag($event), $callback, $priority, null, $once);
	}


	/**
	 * Allow fluent and dynamic get/set calls of paths.
	 *
     * @param string $method
     * @param array  $args
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
	 */
	public function __call($method, $args)
	{
		if(substr($method, -4) === 'Path'){

			$key = substr($method, 0, -4);

			if(strpos($key, 'use') === 0){
				$key = substr($key, 3);
				$method = 'usePath';
			}
			elseif(strpos($key, 'add') === 0){
				$key = substr($key, 3);
				$method = 'addPath';
			}
			else{
				$method = 'getPath';
			}

			return method_exists($this, $method)
					? $this->{$method}( Str::snake($key), ...$args )
					: $this->app->{$method}( Str::snake($key), ...$args );
		}

		throw new BadMethodCallException("Call to undefined method '{$method}' not in factory.");

	}

}

