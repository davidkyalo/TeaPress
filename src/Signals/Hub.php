<?php
namespace TeaPress\Signals;

use Closure;
use TeaPress\Utils\Arr;
use TeaPress\Utils\Str;
use BadMethodCallException;
use InvalidArgumentException;
use TeaPress\Utils\Traits\Extendable;
use TeaPress\Contracts\Core\Container;
use TeaPress\Contracts\Signals\Hub as Contract;


class Hub implements Contract
{

	use Extendable;

	const NO_RESPONSE = NOTHING;

	const ACTION = 'ACTION';

	const FILTER = 'FILTER';

	const WILDCARD = 'WILDCARD';

	/**
	 * @var \TeaPress\Contracts\Core\Container
	 */
	protected $container;

	/**
	 * @var string
	 */
	protected $default_class_callback = 'handle';

	/**
	 * @var array
	 */
	protected $knownAbstracts = [];

	/**
	 * @var array
	 */
	protected $filters = [];

	/**
	 * @var array
	 */
	protected $wrappers = [];

	/**
	 * The wildcard listeners.
	 *
	 * @var array
	 */
	protected $wildcards = [];

	/**
	 * @var array
	 */
	protected $flushedCallbacks = [];

	/**
	 * @var array
	 */
	protected $bound = [];


	/**
	 * @var array
	 */
	protected $responses = [];


	/**
	 * @var array
	 */
	protected $halting = [];

	/**
	 * Create an events dispatcher instance.
	 *
	 * @param \TeaPress\Contracts\Core\Container $container
	 *
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;

		$this->registerAllActionHook();
	}

	/**
	 * Register a logger for evaluated events.
	 *
	 * @return void
	 *
	 */
	public function registerAllActionHook()
	{
		$this->bindWeak('all', function($tag){
			global $wp_actions;

			$this->unbindFlushedCallbacks();

			if(array_key_exists($tag, $wp_actions))
				return;

			if(!isset($this->filters[$tag]))
				$this->filters[$tag] = 1;
			else
				++$this->filters[$tag];

		}, 0);
	}


	/**
	 * Get the current event.
	 *
	 * @return string|null
	 */
	public function current()
	{
		$active = $this->active();
		return end( $active );
	}


	/**
	 * Get the current action.
	 *
	 * @return string|null
	 */
	public function currentAction()
	{
		foreach ( array_reverse($this->active()) as $tag) {
			if($this->didAction($tag))
				return $tag;
		}
	}

	/**
	 * Get the current filter.
	 *
	 * @return string|null
	 */
	public function currentFilter()
	{
		foreach ( array_reverse($this->active()) as $tag) {
			if($this->didFilter($tag))
				return $tag;
		}
	}

	/**
	 * Get the current filter.
	 *
	 * @return string|null
	 */
	public function getType($tag = null)
	{
		$tag = is_null($tag) ? $this->current() : $this->getTag($tag);

		// if($tag && $this->isWildcard($tag))
		if($tag && isset($this->wildcards[$tag]))
			return static::WILDCARD;
		elseif($tag && $this->didFilter($tag) )
			return static::FILTER;
		elseif($tag && $this->didAction($tag) )
			return static::ACTION;
		else
			return false;
	}

	/**
	 * Determine if the given event hook is the current
	 *
	 * @param  array|string		$tag
	 *
	 * @return bool
	 */
	public function isCurrent($tag)
	{
		return $this->current() === $this->getTag($tag);
	}


	/**
	 * Determine if the given action hook is the current
	 *
	 * @param  array|string		$tag
	 *
	 * @return bool
	 */
	public function isCurrentAction($tag)
	{
		return $this->isCurrent($tag) && $this->getType($tag) === static::ACTION;
	}

	/**
	 * Determine if the given filter hook is the current
	 *
	 * @param  array|string		$tag
	 *
	 * @return bool
	 */
	public function isCurrentFilter($tag)
	{
		return $this->isCurrent($tag) && $this->getType($tag) === static::FILTER;
	}

	/**
	 * Get the number of times an event has been evaluated or an array of all evaluated events if none is given.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return int|array
	 */
	public function filtered($tag = null)
	{
		return is_null($tag) ? $this->filters : Arr::get($this->filters, [$this->getTag($tag), '->->'], 0);
	}


	/**
	 * Get the number of times a filter has been evaluated or an array of all evaluated filters if none is given.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return int|array
	 */
	public function didFilter($tag = null)
	{
		return $this->filtered($tag);
	}

	/**
	 * Get the number of times an event has been triggered or an array of all triggered events if none is given.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return int|array
	 */
	public function emitted($tag = null)
	{
		global $wp_actions;

		if(is_null($tag))
			return $wp_actions;

		return Arr::get( $wp_actions, [$this->getTag($tag), '->->'], 0 );
	}


	/**
	 * Get the number of times an action has been triggered or an array of all triggered actions if none is given.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return int|array
	 */
	public function didAction($tag = null)
	{
		return $this->emitted($tag);
	}

	/**
	 * Get all currently active events.
	 *
	 * @return array
	 */
	public function active()
	{
		global $wp_current_filter;
		return $wp_current_filter;
	}

	/**
	 * Determine the given event is currently being executed. If null is given, checks whether any event is being executed.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return bool
	 */
	public function isDoing($tag = null)
	{
		return is_null($tag)
			? !empty($this->active())
			: in_array($this->getTag($tag), $this->active());
	}

	/**
	 * Determine the given action hook is currently being executed. If null is given, checks whether any action is being executed.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return bool
	 */
	public function doingAction($tag = null)
	{
		return $this->isDoing($tag);
	}

	/**
	 * Determine the given filter hook is currently being executed. If null is given, checks whether any filter is being executed.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return bool
	 */
	public function doingFilter($tag = null)
	{
		return $this->isDoing($tag);
	}

	/**
	 * Get the real hook tag string.
	 *
	 * @param  string|array  $tag
	 *
	 * @return string
	 */
	public  function getTag($tag)
	{
		$this->isValidTag($tag, false);
		return is_string($tag) ? $tag : $this->getEmitterTag($tag[0], $tag[1]);
	}

	/**
	 * Check if the given argument is a valid event name.
	 *
	 * @param  mixed  $tag The argument to check.
	 * @param  bool  $silent Whether to throw exception if validation fails
	 * @return bool
	 *
	 * @throws \UnexpectedValueException
	 */
	public function isValidTag($tag, $silent = true)
	{
		if(is_string($tag) || (is_array($tag) && count($tag) === 2))
			return true;

		if(!$silent)
			throw new InvalidArgumentException("'" . $event . "' is not a valid event name.");

		return false;
	}

	/**
	 * Get the name of an emitter's hook tag.
	 *
	 * If emitter is bound to the container, the abstract will be used.
	 * Else if object is passed, the class name will be used.
	 * Otherwise, the passed string value will be used.
	 *
	 * @param  object|string  $emitter
	 * @param  string 		  $tag
	 *
	 * @return string
	 */
	public function getEmitterTag($emitter, $tag)
	{
		return $this->getAbstract($emitter).':'.$tag;
	}

	/**
	 * Tries to get the abstract name of the given emitter incase it's bound to the service container.
	 * If the emitter is a known service it's real name is returned.
	 * Else if an object is passed, it's class name will be returned.
	 * Otherwise, the provided value is returned.
	 *
	 * @param  object|string  $emitter
	 *
	 * @return string
	 */
	public function getAbstract($emitter)
	{
		$name = is_object($emitter) ? get_class($emitter) : $emitter;

		if( !isset($this->knownAbstracts[$name]) )
			$this->knownAbstracts[$name] =  $this->container->getAlias($name);

		return $this->knownAbstracts[$name];
	}

	/**
	* Bind the given callback to the specified $event.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	* @param  bool							$once
	*
	* @return static
	*/
	public function bind($tag, $callback, $priority = null, $accepted_args = null, $once = null, $weak = false)
	{
		if( is_bool($accepted_args) && is_null($once) ){
			$once = $accepted_args;
			$accepted_args = null;
		}

		$tag = $this->getTag($tag);
		$priority = $this->checkPriority($priority);
		$accepted_args = $this->checkAcceptedArgs($accepted_args);

		$xbindings = $this->getBindingsCount( $callback, $tag, 0);

		if($xbindings === true || $xbindings && $once )
			throw new BadMethodCallException("Bind once constraint broken. Can't rebind '{$callback}' to '{$tag}'.");

		if(is_null($weak)) $weak = false;

		if( !$weak || $once || !$this->isBindable($callback) )
			$bindable = $this->getOrCreateWrapper($callback, $priority);
		else
			$bindable = $callback;

		if ($this->isWildcard($tag)){
			$this->addWildcard($tag);
		}

		add_filter( $tag, $bindable, $priority, $accepted_args );

		$this->bumpBindingsCount($callback, $tag, $once);

		return $this;
	}

	/**
	* Bind an action callback.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	* @param  bool							$once
	*
	* @return static
	*/
	public function bindWeak($tag, $callback, $priority = null, $accepted_args = null)
	{
		return $this->bind($tag, $callback, $priority, $accepted_args, null, true);
	}

	/**
	* Bind an action callback.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	* @param  bool							$once
	*
	* @return static
	*/
	public function addAction($tag, $callback, $priority = null, $accepted_args = null, $once = null)
	{
		return $this->bind($tag, $callback, $priority, $accepted_args, $once);
	}


	/**
	* Bind a filter callback.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	* @param  bool							$once
	*
	* @return static
	*/
	public function addFilter($tag, $callback, $priority = null, $accepted_args = null, $once = null)
	{
		return $this->bind($tag, $callback, $priority, $accepted_args, $once);
	}


	/**
	* Bind the given $callback to the specified hook once.
	*
	* The callback will be executed once after which it will be removed.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null						$accepted_args
	*
	* @return static
	*/
	public function once($tag, $callback, $priority = null, $accepted_args = null)
	{
		return $this->bind($tag, $callback, $priority, $accepted_args, true);
	}

	/**
	* Remove bound callback from specified hook
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public function unbind($tag, $callback, $priority = null)
	{
		$removed = remove_filter(
					$this->getTag($tag),
					$this->getWrapper( $callback, $callback ),
					$this->checkPriority($priority)
				);

		if( $this->isBound( $callback, $tag ) )
			$this->subBindingsCount( $callback, $this->getTag($tag) );


		if( !$this->isBound($callback) )
			$this->deleteWrapper($callback);

		return $removed;
	}


	/**
	* Removes a callback from a specified action hook.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public function removeAction($tag, $callback, $priority = null)
	{
		return $this->unbind($tag, $callback, $priority);
	}


	/**
	* Removes a callback from a specified filter hook.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public function removeFilter($tag, $callback, $priority = null)
	{
		return $this->unbind($tag, $callback, $priority);
	}

	/**
	* Determine a given hook has the given callback bound.
	* If callback is omitted, checks whether the hook has anything bound.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string|null	$callback
	*
	* @return bool
	*/
	public function has($tag, $callback = null)
	{
		if(is_null($callback))
			return has_filter( $this->getTag( $tag ), false );

		return false !== has_filter( $this->getTag($tag), $this->getWrapper( $callback, $callback ) );
	}


	/**
	* Determine a given filter hook has the given callback bound.
	* If callback is omitted, checks whether the hook has anything bound.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string|null	$callback
	*
	* @return bool
	*/
	public function hasFilter($tag, $callback = null)
	{
		return $this->has($tag, $callback);
	}


	/**
	* Determine a given action hook has the given callback bound.
	* If callback is omitted, checks whether the hook has anything bound.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string|null	$callback
	*
	* @return bool
	*/
	public function hasAction($tag, $callback = null)
	{
		return $this->has($tag, $callback);
	}

	/**
	* Get the number of times a callback is bound to the given hook.
	* If the hook (tag) is omitted or is false, return the total number of times
	* the callback is hooked to all events. If true, gets an array of all hooks the callback is bound to.
	*
	* @param  \Closure|array|string 		$callback
	* @param  array|string|bool				$tag=false
	*
	* @return int|array
	*/
	public function bound($callback, $tag = false)
	{
		$as_arr = $tag === true;

		$tag = $tag && !$as_arr ? $this->getTag($tag) : null;

		$bindings = $this->getBindingsCount($callback, $tag, ($as_arr ? [] : 0) );

		if(is_array($bindings) && !$as_arr){
			$count = 0;
			array_walk($bindings, function($c) use (&$count){
				$count += (int) $c;
			});
			$bindings = $count;
		}

		return $as_arr ? (array) $bindings : (int) $bindings;
	}



	/**
	* Determine if a callback is bound the given hook.
	* If hook is omitted, Will check if the callback is bound to any hook
	*
	* @param  \Closure|array|string 		$callback
	* @param  array|string|null 			$tag
	*
	* @return bool
	*/
	public function isBound($callback, $tag = null)
	{
		return $this->bound($callback, $tag) > 0;
	}



	/**
	* Determine if a callback is bound ONCE the provided hook.
	*
	* @param  \Closure|array|string 		$callback
	* @param  array|string 					$tag
	*
	* @return bool
	*/
	public function boundOnce($callback, $tag)
	{
		return $this->getBindingsCount($callback, $this->getTag($tag), 0) === true;
	}


	/**
	* Execute wildcards. Calls all available wildcard callbacks for the given event.
	*
	* @param  array|string					$tag
	* @param  array							$payload
	*
	* @return void
	*/
	protected function callWildcards($tag, array $payload = [], $type = false)
	{
		global $wp_filter, $merged_filters, $wp_current_filter;

		if(empty($this->wildcards))
			return;

		//Set the current real event tag since it had been popped.
		$wp_current_filter[] = $tag;

		if( $this->stacksResponses($type) ){
			$payload[] = $this->response($tag);
		}

		$toempty = [];

		reset( $this->wildcards );

		do {

			$wildcard = current($this->wildcards);
			if( !Str::is($wildcard, $tag) ){
				continue;
			}

			//In case this wildcard has no bound callbacks, mark for emptying and skip to the next.
			if ( !isset($wp_filter[$wildcard]) ){
				$toempty[] = $wildcard;
				continue;
			}

			// Sort.
			if ( !isset( $merged_filters[ $wildcard ] ) ) {
				ksort($wp_filter[$wildcard]);
				$merged_filters[ $wildcard ] = true;
			}

			reset( $wp_filter[ $wildcard ] );

			do {
				foreach ( (array) current($wp_filter[$wildcard]) as $the_ )
					if ( !is_null($the_['function']) )
						call_user_func_array( $the_['function'], $payload );

			} while ( next($wp_filter[$wildcard]) !== false );


		} while ( next( $this->wildcards ) !== false );

		//Pop out the current event
		array_pop($wp_current_filter);

	}



	/**
	* Execute callbacks hooked the specified action.
	*
	* Calls the do_action_ref_array() wordpress function with $payload as $args.
	*
	* @param  array|string					$tag
	* @param  array							$payload
	* @param  bool							$halt
	*
	* @return void
	*/
	public function emitSignalWith($tag, array $payload = [], $halt = false)
	{
		$tag = $this->getTag($tag);

		if(!$this->beforeDoAction($tag, $payload, $halt)){
			return;
		}

		do_action_ref_array($tag, $payload);

		$this->callWildcards($tag, $payload, static::ACTION );

		return $this->afterDoAction($tag);

	}


	/**
	* Execute callbacks hooked the specified action.
	*
	* Equivalent to do_action() wordpress function.
	*
	* @param  array|string		$tag
	* @param  mixed				$payload
	*
	* @return void
	*/
	public function emit($tag, ...$payload)
	{
		return $this->emitSignalWith($tag, $payload, false);
	}

	/**
	* Execute callbacks hooked the specified action.
	*
	* Equivalent to do_action() wordpress function.
	*
	* @param  array|string		$tag
	* @param  mixed				$payload
	*
	* @return void
	*/
	public function doAction($tag, ...$payload)
	{
		return $this->emitSignalWith($tag, $payload, false);
	}


	/**
	* Filter a value by executing callbacks hooked to the given filter hook.
	*
	* If $value is array and $payload is false, the value array is used as the payload with
	* the first element as the value to filter.
	*
	* Calls the apply_filters_ref_array() wordpress function.
	*
	* @param  array|string					$tag
	* @param  array 						$payload
	*
	* @return mixed
	*/
	public function applyFiltersWith($tag, array $payload=[])
	{
		$tag = $this->getTag($tag);

		if(!$this->beforeApplyFilters($tag, $payload)){
			return Arr::first($payload);
		}

		$response = apply_filters_ref_array($tag, $payload);

		$payload = array_values($payload);
		$payload[0] = $response;

		$this->callWildcards($tag, $payload, static::FILTER );

		return $this->afterApplyFilters($tag, $response);
	}

	/**
	* Filter a item by executing callbacks hooked to the given filter hook.
	*
	* Equivalent to apply_filters() wordpress function.
	*
	* @param  array|string		$tag
	* @param  mixed				$item
	* @param  mixed				$payload
	*
	* @return void
	*/
	public function filter($tag, $item=null, ...$payload)
	{
		array_unshift($payload, $item);
		return $this->applyFiltersWith($tag, $payload);
	}


	/**
	* Filter a item by executing callbacks hooked to the given filter hook.
	*
	* Equivalent to apply_filters() wordpress function.
	*
	* @param  array|string		$tag
	* @param  mixed				$item
	* @param  mixed				$payload
	*
	* @return void
	*/
	public function applyFilters($tag, $item=null, ...$payload)
	{
		array_unshift($payload, $item);
		return $this->applyFiltersWith($tag, $payload);
	}


	/**
	* Get an array of all the bound callbacks for the given hook grouped by priority unless merged is true.
	*
	* @param  string|array 	$tag
	* @param  bool			$sorted
	* @param  bool			$merged
	*
	* @return array
	*/
	public function getCallbacks($tag, $sorted=true, $merged=false)
	{
		global $wp_filter, $merged_filters;

		$tag = $this->getTag($tag);

		if ( !isset($wp_filter[$tag]) )
			return [];

		$copy = $wp_filter[$tag];

		if( $sorted && !isset($merged_filters[$tag]) )
			ksort($copy);

		$callbacks = [];
		foreach ($copy as $pr => $cbs) {
			foreach ( (array) $cbs as $cb) {
				$callback = [
						'callback' 		=> $cb['function'] instanceof Handler
								? $cb['function']->getCallback() : $cb['function'],
						'priority' 		=> $pr,
						'handler' => $cb['function'],
						'accepted_args' => $cb['accepted_args']
					];


				if($merged)
					$callbacks[] = $callback;
				else
					$callbacks[$pr][] = $callback;
			}
		}
		return $callbacks;
	}


	/**
	* Gets a flat array of all the bound callbacks for the given hook
	*
	* @param  string|array 	$tag
	* @param  bool			$sorted
	*
	* @return array
	*/
	public function getCallbacksMerged($tag, $sorted=true)
	{
		return $this->getCallbacks($tag, $sorted, true);
	}


	/**
	* Get an array of all the bound callbacks for the given hook grouped by priority unless merged is true.
	*
	* @param  string|array 	$tag
	* @param  bool			$sorted
	* @param  bool			$merged
	*
	* @return array
	*/
	// public function getHandlers($tag, $sorted=true, $merged=false)
	// {
	// 	global $wp_filter, $merged_filters;

	// 	$tag = $this->getTag($tag);

	// 	if ( !isset($wp_filter[$tag]) )
	// 		return [];

	// 	$copy = $wp_filter[$tag];

	// 	if( $sorted && !isset($merged_filters[$tag]) )
	// 		ksort($copy);

	// 	$callbacks = [];
	// 	foreach ($copy as $pr => $cbs) {
	// 		foreach ( (array) $cbs as $cb) {
	// 			$callback = [
	// 					'callback' 		=> $cb['function'] instanceof Handler
	// 							? $cb['function']->getCallback() : $cb['function'],
	// 					'priority' 		=> $pr,
	// 					'handler' => $cb['function'],
	// 					'accepted_args' => $cb['accepted_args']
	// 				];


	// 			if($merged)
	// 				$callbacks[] = $callback;
	// 			else
	// 				$callbacks[$pr][] = $callback;
	// 		}
	// 	}
	// 	return $callbacks;
	// }



/* Illuminate\Contracts\Events\Dispatcher */

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  string|array  $events
	 * @param  mixed  $listener
	 * @param  int  $priority
	 * @return void
	 */
	public function listen($events, $listener, $priority = null, $once = null)
	{
		foreach ((array) $events as $event) {
			$this->bind($event, $listener, $priority, null, $once, false);
		}
	}

	/**
	 * Determine if a given event has listeners.
	 *
	 * @param  string  $eventName
	 * @return bool
	 */
	public function hasListeners($eventName)
	{
		return $this->has($eventName);
	}

	/**
	 * Register an event and payload to be fired later.
	 *
	 * @param  string  $event
	 * @param  array  $payload
	 * @return void
	 */
	public function push($event, $payload = [])
	{
		$event = $this->getTag($event);
		$this->listen($event.'_pushed', function () use ($event, $payload)
		{
			$this->fire($event, $payload);
		});
	}

	/**
	 * Register an event subscriber with the dispatcher.
	 *
	 * @param  object|string  $subscriber
	 * @return void
	 */
	public function subscribe($subscriber)
	{
		$subscriber = $this->resolveSubscriber($subscriber);

		$subscriber->subscribe($this);
	}

	/**
	 * Fire an event until the first non-null response is returned.
	 *
	 * @param  string  $event
	 * @param  array  $payload
	 * @return mixed
	 */
	public function until($event, $payload = [])
	{
		return $this->fire($event, $payload, true);
	}

	/**
	 * Flush a set of pushed events.
	 *
	 * @param  string  $event
	 * @return void
	 */
	public function flush($event)
	{
		$event = $this->getTag($event);
		$this->fire($event.'_pushed');
	}

	/**
	 * Fire an event and call the listeners.
	 *
	 * @param  string|object  $event
	 * @param  mixed  $payload
	 * @param  bool  $halt
	 * @return array|null
	 */
	public function fire($event, $payload = [], $halt = false)
	{
		return $this->emitSignalWith($event,  (array) $payload, $halt);
	}

	/**
	 * Get the event that is currently firing.
	 *
	 * @return string
	 */
	public function firing()
	{
		return $this->current();
	}

	/**
	* Get the number of times an event has been triggered or an array of all triggered events if none is given.
	*
	* @param  array|string|null		$tag
	*
	* @return int|array
	*/
	public function fired($tag = null)
	{
		return $this->emitted($tag);
	}

	/**
	 * Remove a set of listeners from the dispatcher.
	 *
	 * @param  string  $event
	 * @return void
	 */
	public function forget($event)
	{
		trigger_error('Method '.__METHOD__.' not implemented.');
	}

	/**
	 * Forget all of the queued listeners.
	 *
	 * @param  string|array  $events
	 * @return void
	 */
	public function forgetPushed($events = null)
	{
		trigger_error('Method '.__METHOD__.' not implemented.');
	}


	/**
	 * Get the responses of the given event
	 *
	 * @return array
	 */
	public function responses($tag = null, $default = [])
	{
		if(is_null($tag))
			return $this->responses;

		$tag = $this->getTag($tag);

		// return Arr::get($this->responses, $tag, $default, '->->');
		// return Arr::get($this->responses, [$tag, '->->'], $default);
		return isset($this->responses[$tag]) ? $this->responses[$tag] : value($default);
	}

	/**
	 * Get the responses of the given event
	 *
	 * @return mixed|array|null
	 */
	public function response($tag, $default = NOTHING)
	{
		// return Arr::get($this->responses, $tag, $default, '->->');
		// return Arr::get($this->responses, [$tag, '->->'], $default);

		// if(!$this->stacksResponses( $this->getType($tag) ))
		// 	return;

		$halt = $this->halting($tag);

		if($default === NOTHING)
			$default = $halt ? null : [];

		$all = $this->responses($tag, $default);

		return $halt && is_array($all) ?  Arr::last($all, null, $default) : $all;
	}

	/**
	 * Determine if the given hook should be halted or all events that should be halted if hook tag is not provided.
	 *
	 * @return bool|array
	 */
	public function halting($tag = null)
	{
		if(is_null($tag))
			return $this->halting;

		$tag = $this->getTag($tag);
		// return Arr::get($this->halting, $tag, false, '->->');
		// return Arr::get($this->halting, [$tag, '->->'], false);
		return isset($this->halting[$tag]) ? $this->halting[$tag] : false;
	}

	/**
	 * Resolve the subscriber instance.
	 *
	 * @param  object|string  $subscriber
	 * @return mixed
	 */
	protected function resolveSubscriber($subscriber)
	{
		if (is_string($subscriber)) {
			return $this->container->make($subscriber);
		}

		return $subscriber;
	}

	/**
	 * Determine whether the given tag is a wildcard.
	 *
	 * @param  string  $tag
	 *
	 * @return bool
	 */
	protected function isWildcard($tag)
	{
		return Str::contains($tag, '*');
	}

	/**
	 * Registers a wildcard tag.
	 *
	 * @param  string  $tag
	 *
	 * @return void
	 */
	protected function addWildcard($tag)
	{
		if(!in_array($tag, $this->wildcards))
			$this->wildcards[] = $tag;
	}


	/**
	 * Get the matching wildcard tags for the event.
	 *
	 * @param  string|null  $tag
	 *
	 * @return array
	 */
	protected function wildcards($tag = null)
	{
		if(is_null($tag))
			return $this->wildcards;

		$wildcards = [];

		foreach ($this->wildcards as $wildcard) {
			if ( $wildcard !== $tag && Str::is($wildcard, $tag) ) {
				$wildcards[] = $wildcard;
			}
		}

		return $wildcards;
	}


/* End Illuminate\Contracts\Events\Dispatcher */

	protected function beforeDoAction($tag, $args = [], $halt = false)
	{
		$this->unbindFlushedCallbacks($tag);
		$this->prepareSignal($tag, null, $halt);
		return true;
	}

	protected function beforeApplyFilters($tag, array $payload)
	{
		$this->unbindFlushedCallbacks($tag);
		// $this->prepareSignal($tag, Arr::first($payload), false);
		$this->prepareSignal($tag, null, false);
		return true;
	}

	protected function afterDoAction($tag)
	{
		$this->unbindFlushedCallbacks($tag);
		return $this->terminateSignal($tag);
	}

	protected function afterApplyFilters($tag, $response)
	{
		$this->unbindFlushedCallbacks($tag);
		return $this->terminateSignal($tag, $response);
	}

	protected function prepareSignal($tag, $item = null, $halt = false)
	{
		$this->responses[$tag] = (array) $item;
		$this->halting[$tag] = $halt;
	}

	protected function terminateSignal($tag, $response = self::NO_RESPONSE)
	{
		if($response === self::NO_RESPONSE){
			// array_shift($this->responses[$tag]);
			$response = $this->halting[$tag]
					? Arr::last($this->responses[$tag])
					: $this->responses[$tag];
		}

		unset($this->responses[$tag]);
		unset($this->halting[$tag]);

		return $response;
	}

	public function currentSignal()
	{
		$signal = $this->current();
		return [ $signal, $this->getType($signal), ( $signal && isset($this->responses[$signal]) ) ];
	}


	/**
	 * Get the appropriate event listener priority based on the given value.
	 *
	 * @param  int|null $priority
	 *
	 * @return int
	 */
	public function checkPriority($priority = null){
		return is_null($priority) ? 10 : (int) $priority;
	}

	/**
	 * Get the appropriate event listener accepted args based on the given value.
	 *
	 * @param  int|null $accepted_args
	 *
	 * @return int
	 */
	public function checkAcceptedArgs($accepted_args = null){
		return is_null($accepted_args) ? 20 : (int) $accepted_args;
	}

	/**
	* Determine if a callback can be bound as it is without wrapping with a closure
	*
	* @param  \Closure|array|string 	$callback
	*
	* @return bool
	*/
	protected function isBindable($callback)
	{
		return ( !is_string($callback) || is_callable($callback) );
	}

	/**
	* Get a callback's Unique ID for storage and retrieval.
	*
	* @param  \Closure|array|string 	$callback
	*
	* @return string
	*/
	protected function getCallbackId($callback)
	{
		if ( is_string($callback) )
			return $callback;

		list($object, $method) = is_object($callback) ? [$callback, ''] : (array) $callback;

		if(is_object($object))
			return spl_object_hash($object) . $method;

		if(is_string($object))
			return $object.'::'.$method;
	}

	/**
	* Get the closure that wraps the given listener if it exists or create one.
	*
	* @param  string|array|\Closure 	$callback
	*
	* @return \Closure
	*/
	protected function getOrCreateWrapper($callback, $priority = null)
	{
		$wrapper = $this->getWrapper( $callback );

		if(is_null($wrapper)){
			$wrapper = $this->wrapCallback($callback, $priority);
			$this->registerWrapper( $callback, $wrapper);
		}

		return $wrapper;
	}


	/**
	* Get the current closure that wraps the given callback or return default
	*
	* @param  string|array|\Closure 	$callback
	* @param  mixed 					$default
	*
	* @return \Closure|mixed
	*/
	protected function getWrapper($callback, $default = null)
	{
		$id = $this->getCallbackId( $callback );
		return isset($this->wrappers[$id]) ? $this->wrappers[$id] : $default;
	}


	/**
	* Register the given callback's wrapper for reuse
	*
	* @param  string|array|\Closure 	$callback
	* @param  \Closure 					$wrapper
	*
	* @return \Closure
	*/
	protected function registerWrapper($callback, $wrapper)
	{
		return $this->wrappers[$this->getCallbackId( $callback )] = $wrapper;
	}


	/**
	* Delete the given callback's wrapper is it exists.
	*
	* @param  string|array|\Closure 	$callback
	*
	* @return void
	*/
	protected function deleteWrapper($callback)
	{
		$id = $this->getCallbackId( $callback );
		if(isset($this->wrappers[$id]))
			unset($this->wrappers[$id]);
	}


	/**
	* Bump the number of times a callback is bound to a given hook.
	*
	* @param  string|array|\Closure 	$callback
	* @param  string					$tag
	* @param  bool						$once
	*
	* @return bool|int
	*/
	protected function bumpBindingsCount($callback, $tag, $once = false)
	{
		/*
		Before new Arr key notations.

		$id = $this->getCallbackId( $callback );

		$n = '->->';
		$key = $id.$n.$tag;
		$count = Arr::get( $this->bound, $key, 0, $n);

		if( $count === true )
			return $count;

		$count = ($once ? true : $count+1);

		Arr::set( $this->bound, $key, $count, $n);

		return (int) $count;

		*/

		// With new Arr key notations.
		$id = $this->getCallbackId( $callback );

		$n = '->->';
		$key = [$id.$n.$tag, $n];
		$count = Arr::get( $this->bound, $key, 0);

		if( $count === true )
			return $count;

		$count = ($once ? true : $count+1);

		Arr::set( $this->bound, $key, $count);

		return (int) $count;
	}

	/**
	* Reduce the number of times a callback is bound to a given hook by one.
	*
	* @param  string|array|\Closure 	$callback
	* @param  string					$tag
	*
	* @return bool|int
	*/
	protected function subBindingsCount($callback, $tag)
	{
		/*
		Before new Arr key notations.

		$id = $this->getCallbackId( $callback );

		$n = '->->';
		$key = $id.$n.$tag;
		$count = Arr::get( $this->bound, $key, null, $n);

		if(!is_null($count)){
			$count = ( (int) $count ) - 1;
			if($count <= 0)
				Arr::forget($this->bound, $key, $n);
			else
				Arr::set( $this->bound, $key, $count, $n);
		}

		if(empty($this->bound[$id]))
			unset($this->bound[$id]);

		return $count;
		*/

		// With new Arr key notations.
		$id = $this->getCallbackId( $callback );

		$n = '->->';
		$key = [$id.$n.$tag, $n];
		$count = Arr::get( $this->bound, $key, null);

		if(!is_null($count)){
			$count = ( (int) $count ) - 1;
			if($count <= 0)
				Arr::forget($this->bound, $key[0], $n);
			else
				Arr::set( $this->bound, $key, $count);
		}

		if(empty($this->bound[$id]))
			unset($this->bound[$id]);

		return $count;
	}



	/**
	* Get the number of times a callback is bound to the given hook.
	* If the hook (tag) is omitted, gets an array of all hooks the callback is bound to or 0 if none.
	*
	* @param  \Closure|array|string 		$callback
	* @param  array|string|null				$tag
	* @param  mixed							$default=0
	*
	* @return int|array
	*/
	protected function getBindingsCount($callback, $tag = null, $default = 0)
	{
		$n = '->->';
		$id = $this->getCallbackId($callback);
		$key = is_null($tag) ? $id : $id.$n.$tag;
		return  Arr::get($this->bound,[$key, $n], $default);
	}


	/**
	* Mark the given callback to be unbound in the next event.
	*
	* @param  array|string|null				$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return void
	*/
	protected function flushCallback($tag, $callback, $priority = null)
	{
		$this->flushedCallbacks[$tag][ $this->getCallbackId($callback) ] = [$callback, $priority];
	}

	protected function unbindFlushedCallbacks($tags = null)
	{
		$tags = is_null($tags) ? array_keys( $this->flushedCallbacks ) : (array) $tags;

		foreach ($tags as $tag) {

			if( !isset($this->flushedCallbacks[$tag]) || $this->isDoing($tag) )
				continue;

			$callbacks = $this->flushedCallbacks[$tag];

			foreach ((array) $callbacks as $id => $value) {

				list($callback, $priority) = $value;

				$this->unbind( $tag, $callback, $priority );

				unset($this->flushedCallbacks[$tag][$id]);
			}

			if(empty($this->flushedCallbacks[$tag]))
				unset($this->flushedCallbacks[$tag]);
		}

		return true;
	}

	/**
	 * Wrap a callback with a closure.
	 *
	 * If it's a class based callback, the class is resolved and a callable to the specified method or to' handle' method is created.
	 * 		Syntax Class@method
	 *
	 * @param  string|array|\Closure  $callback
	 *
	 * @return \Closure
	 */
	protected function wrap($callback)
	{
		return function () use ($callback)
		{
			return call_user_func_array( $this->getCallable($callback), func_get_args() );
		};
	}

	/**
	 * Determine whether the given event type should stack responses.
	 *
	 * @param  string  $eventType
	 *
	 * @return bool
	 */
	protected function stacksResponses($eventType)
	{
		return $eventType === static::ACTION;
	}

	public function invokeCallback($callback, $parameters, $priority)
	{
		list($signal, $type, $emitting) = $this->currentSignal();

		$stackResponses = $this->stacksResponses($type);

		$response = $emitting && $stackResponses ? Arr::last($this->responses[$signal]) : null;

		if($stackResponses && $emitting && (!$this->halting[$signal] || is_null($response)) ){
			// $this->responses[$signal][] = $response = call_user_func_array( $this->getCallable($callback), $parameters );
			$response = call_user_func_array( $this->getCallable($callback), $parameters );

			if(!is_null($response)) //
				$this->responses[$signal][] = $response; //

		}
		elseif (!$stackResponses || !$emitting) {
			$response = call_user_func_array( $this->getCallable($callback), $parameters );
		}

		if( $this->boundOnce( $callback, $signal ) )
			$this->flushCallback($signal, $callback, $priority);

		return $response;
	}

	/**
	 * Wrap a hook's callback with a closure to ensure it's executed correctly.
	 *
	 * @param  string|array|\Closure  $callback
	 *
	 * @return \Closure
	 */
	protected function wrapCallback($callback, $priority = null)
	{
		return new Handler($callback, $priority, $this);

		// return function () use ($callback, $priority)
		// {
		// 	list($signal, $emitting) = $this->currentSignal();

		// 	$response = $emitting ? Arr::last($this->responses[$signal]) : null;

		// 	if($emitting && (!$this->halting[$signal] || is_null($response)) ){
		// 		$this->responses[$signal][] = $response = call_user_func_array( $this->getCallable($callback), func_get_args() );
		// 	}
		// 	elseif (!$emitting) {
		// 		$response = call_user_func_array( $this->getCallable($callback), func_get_args() );
		// 	}

		// 	if( $this->boundOnce( $callback, $signal ) )
		// 		$this->flushCallback($signal, $callback, $priority);

		// 	return $response;
		// };
	}

	/**
	 * Creates the class based callable for callback if callback is not callable, Returns callback if callable.
	 *
	 * @param  callable|string  $callback
	 *`
	 * @return callable
	 */
	protected function getCallable($callback)
	{
		if(is_callable($callback))
			return $callback;

		if(!is_string($callback))
			throw new InvalidArgumentException("Error creating callable. Unknown Callback (".$callback.").");

		list($class, $method) = $this->parseClassCallable($callback);

		return $class === '' ? $this->container->make($method) : [$this->container->make($class), $method];
	}

	/**
	 * Parse the class based callback into class and method.
	 *
	 * @param  string  $callback
	 * @return array
	 */
	protected function parseClassCallable($callback){
		$segments = explode('@', $callback);
		return [$segments[0], count($segments) == 2 ? $segments[1] : $this->default_class_callback];
	}

	/**
	* Parse full callback string to get name and parameters.
	*
	* @param  mixed $callback
	* @return array
	*/
	protected function parseParamString($callback)
	{
		if( !is_string($callback) )
			return [ $callback, [] ];

		list($callback, $parameters) = array_pad(explode(':', $callback, 2), 2, []);

		if (is_string($parameters)) {
			$parameters = explode(',', $parameters);
		}

		return [$callback, $parameters];
	}


}