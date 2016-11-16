<?php
namespace TeaPress\Signals;

use Closure;
use TeaPress\Utils\Arr;
use TeaPress\Utils\Str;
use BadMethodCallException;
use InvalidArgumentException;
use TeaPress\Signals\Traits\Online;
use TeaPress\Utils\Traits\Extendable;
use TeaPress\Contracts\Core\Container;
use TeaPress\Contracts\Signals\Signals as Contract;


class Signals implements Contract
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
	 * @var array
	 */
	protected $filters = [];

	/**
	 * The wildcard listeners.
	 *
	 * @var array
	 */
	protected $wildcards = [];

	/**
	 * Create an events dispatcher instance.
	 *
	 * @param \TeaPress\Contracts\Core\Container $container
	 * @param \TeaPress\Signals\TagResolver $resolver	 *
	 * @return void
	 */
	public function __construct(Container $container, TagResolver $resolver)
	{
		$this->container = $container;

		Online::setSignals($this);
		Tag::setResolver($resolver);

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
		$this->bind('all', function($tag){
			global $wp_actions;

			if(array_key_exists($tag, $wp_actions)){
				return;
			}

			$this->bumpFilterCount($tag);

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
	 * Get the signal type for the given tag.
	 *
	 * @param  \TeaPress\Signals\Tag|string|null	$tag
	 * @return string|false
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
	 * @param  \TeaPress\Signals\Tag|string	$tag
	 * @return bool
	 */
	public function isCurrent($tag)
	{
		return $this->current() === $this->getTag($tag);
	}


	/**
	 * Determine if the given action hook is the current
	 *
	 * @param  \TeaPress\Signals\Tag|string	$tag
	 * @return bool
	 */
	public function isCurrentAction($tag)
	{
		return $this->isCurrent($tag) && $this->getType($tag) === static::ACTION;
	}

	/**
	 * Determine if the given filter hook is the current
	 *
	 * @param  \TeaPress\Signals\Tag|string	$tag
	 * @return bool
	 */
	public function isCurrentFilter($tag)
	{
		return $this->isCurrent($tag) && $this->getType($tag) === static::FILTER;
	}

	/**
	 * Get the number of times an event has been evaluated or an array of all evaluated events if none is given.
	 *
	 * @param  \TeaPress\Signals\Tag|string|null	$tag
	 * @return int|array
	 */
	public function filtered($tag = null)
	{
		if(is_null($tag))
			return $this->filters;

		$tag = $this->getTag($tag);
		return isset($this->filters[$tag]) ? $this->filters[$tag] : 0;
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
	 * @param  \TeaPress\Signals\Tag|string|null	$tag
	 * @return int|array
	 */
	public function fired($tag = null)
	{
		global $wp_actions;

		if(is_null($tag))
			return $wp_actions;

		$tag = $this->getTag($tag);
		return isset($wp_actions[$tag]) ? $wp_actions[$tag] : 0;
	}


	/**
	 * Get the number of times an action has been triggered or an array of all triggered actions if none is given.
	 *
	 * @param  \TeaPress\Signals\Tag|string|null	$tag
	 * @return int|array
	 */
	public function didAction($tag = null)
	{
		return $this->fired($tag);
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
	 * Determine the given event is currently being executed.
	 *
	 * @param  \TeaPress\Signals\Tag|string|null	$tag
	 * @return bool
	 */
	public function isDoing($tag = null)
	{
		return is_null($tag)
				? !empty($this->active())
				: in_array($this->getTag($tag), $this->active());
	}

	/**
	 * Determine the given action hook is currently being executed.
	 *
	 * @param  \TeaPress\Signals\Tag|string|null		$tag
	 * @return bool
	 */
	public function doingAction($tag = null)
	{
		return $this->isDoing($tag);
	}

	/**
	 * Determine the given filter hook is currently being executed.
	 * If null is given, checks whether any filter is being executed.
	 *
	 * @param  \TeaPress\Signals\Tag|string|null		$tag
	 * @return bool
	 */
	public function doingFilter($tag = null)
	{
		return $this->isDoing($tag);
	}

	/**
	 * Get the real hook tag string.
	 *
	 * @param  string|\TeaPress\Signals\Tag $tag
	 * @param  string|object|null  $namespace
	 * @return string
	 */
	public function getTag($tag, $namespace = null)
	{
		if(is_array($tag)){
			trigger_error("Argument tag should be a string or Tag instance. [".implode(', ', $tag)."] given.");
			$tag = $this->tag($tag[1], $tag[0]);
		}
		elseif (!is_null($namespace)) {
			$tag = $this->tag($tag, $namespace);
		}
		return (string) $tag;
	}

	/**
	 * Create a new tag instance.
	 *
	 * @param  string  $name
	 * @param  string|object|null  $namespace
	 * @return \TeaPress\Signals\Tag
	 */
	public function tag($name, $namespace = null)
	{
		return new Tag($name, $namespace);
	}

	/**
	* Bind the given callback to the specified $event.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null						$accepted_args
	*
	* @return static
	*/
	public function bind($tag, $callback, $priority = null, $accepted_args = null)
	{
		global $wp_filter, $merged_filters;

		$tag = $this->getTag($tag);
		$priority = $this->checkPriority($priority);
		$accepted_args = $this->checkAcceptedArgs($accepted_args);

		$idx = $this->getCallbackId($callback);
		$bindable = $this->isBindable($callback) ? $callback : $this->newHandler($callback);

		$wp_filter[$tag][$priority][$idx] = ['function' => $bindable, 'accepted_args' => $accepted_args];

		unset( $merged_filters[ $tag ] );

		if ($this->isWildcard($tag)){
			$this->addWildcard($tag);
		}

		return $this;
	}

	/**
	* Bind an action callback.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null						$accepted_args
	*
	* @return static
	*/
	public function addAction($tag, $callback, $priority = null, $accepted_args = null)
	{
		return $this->bind($tag, $callback, $priority, $accepted_args);
	}

	/**
	* Bind a filter callback.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null						$accepted_args
	*
	* @return static
	*/
	public function addFilter($tag, $callback, $priority = null, $accepted_args = null)
	{
		return $this->bind($tag, $callback, $priority, $accepted_args);
	}

	/**
	* Remove bound callback from specified hook
	*
	* @param  string|TeaPress\Signals\Tag	$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public function unbind($tag, $callback, $priority = null)
	{
		return remove_filter($this->getTag($tag), $callback, $this->checkPriority($priority));
	}


	/**
	* Removes a callback from a specified action hook.
	*
	* @param  string|TeaPress\Signals\Tag	$tag
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
	* @param  string|TeaPress\Signals\Tag	$tag
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

		return false !== has_filter($this->getTag($tag), $callback);
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
		_deprecated_function(__METHOD__, '0.1.0', 'fire');
		return $this->dispatchAction($tag, $payload, $halt);
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
		_deprecated_function(__METHOD__, '0.1.0', 'doAction');
		return $this->dispatchAction($tag, $payload, false);
	}

	/**
	* Execute callbacks hooked the specified action.
	*
	* Equivalent to do_action() wordpress function.
	*
	* @param  array|string		$tag
	* @param  mixed				$payload
	*
	* @return mixed
	*/
	public function doAction($tag, ...$payload)
	{
		return $this->dispatchAction($tag, $payload, false);
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
	public function applyFiltersWith($tag, array $payload = [])
	{
		_deprecated_function(__METHOD__, '0.1.0', 'filter');
		return $this->runFilters($tag, array_shift($payload), $payload);
	}

	/**
	* Pass the given item through filters registered under the given tag
	* and return the final result.
	*
	* @param  array|string		$tag
	* @param  mixed				$item
	* @param  array				$payload
	*
	* @return mixed
	*/
	public function filter($tag, $item=null, $payload = [])
	{
		return $this->runFilters($tag, $item, $payload);
	}

	/**
	* Pass the given item through filters registered under the given tag
	* and return the final result.
	*
	* @param  array|string		$tag
	* @param  mixed				$item
	* @param  mixed				...$payload
	*
	* @return mixed
	*/
	public function applyFilters($tag, $item=null, ...$payload)
	{
		return $this->runFilters($tag, $item, $payload);
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
		return $this->dispatchAction($event, $payload, true);
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
		return $this->dispatchAction($event, $payload, $halt);
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
		// if(!in_array($tag, $this->wildcards))
		$this->wildcards[$tag] = $tag;
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

	/**
	* Execute callbacks hooked the specified action.
	*
	* @param  array|string					$tag
	* @param  array							$payload
	* @param  bool							$halt
	*
	* @return void
	*/
	protected function dispatchAction($tag, $payload = [], $halt = false)
	{
		$tag = $this->getTag($tag);

		if(!is_array($payload)){
			$payload = [$payload];
		}

		$this->pushActive($tag);

		$this->bumpActionCount($tag);

		$this->callAllHook($tag, $payload);

		$response = $this->callActionCallbacks($tag, $payload, $halt);
		if(!$response->halted())
			$response = $this->callActionWildcards($tag, $response, $payload, $halt);

		$this->popActive();

		if($halt) $response->halt(null);

		return $response->get();

	}

	/**
	* Pass the given item through filters registered under the given tag
	* and return the final result.
	*
	* @param  array|string		$tag
	* @param  mixed				$item
	* @param  array				$payload
	*
	* @return mixed
	*/
	protected function runFilters($tag, $item, $payload = [])
	{
		$tag = $this->getTag($tag);

		if(!is_array($payload)){
			$payload = [$payload];
		}

		$this->pushActive($tag);
		$this->callAllHook($tag, $item, ...$payload);

		$item = $this->callFilterWildcards($tag, $item, $payload);
		$item = $this->callFilterCallbacks($tag, $item, $payload);

		$this->popActive();

		return $item;
	}

	/**
	* Execute wildcards. Calls all available wildcard callbacks for the given event.
	*
	* @param  array|string					$tag
	* @param  array							$payload
	*
	* @return \TeaPress\Signals\Response
	*/
	protected function callActionWildcards($tag, Response $response, array $payload = [], $halt = false)
	{
		if(empty($this->wildcards)){
			return $response;
		}

		reset( $this->wildcards );

		do {
			$wildcard = current($this->wildcards);

			if( $wildcard === $tag || !Str::is($wildcard, $tag) )
				continue;

			$this->bumpActionCount($wildcard);
			$response->merge( $this->callActionCallbacks($wildcard, $payload, $halt) );

			if($response->halted())
				break;
		}
		while ( next( $this->wildcards ) !== false );

		reset( $this->wildcards );

		return $response;
	}

	/**
	* Execute callbacks hooked the specified action.
	*
	* @param  array|string	$tag
	* @param  array			$payload
	* @param  bool			$halt
	*
	* @return \TeaPress\Signals\Response
	*/
	protected function callActionCallbacks($tag, array $payload = [], $halt = false)
	{
		global $wp_filter, $merged_filters;

		$response = new Response;

		if ( !isset($wp_filter[$tag]) )
			return $response;

		// Sort
		if ( !isset( $merged_filters[ $tag ] ) ) {
			ksort($wp_filter[$tag]);
			$merged_filters[ $tag ] = true;
		}

		reset( $wp_filter[ $tag ] );

		do {
			foreach ( (array) current($wp_filter[$tag]) as $the_ ){
				if ( is_null($the_['function']) )
					continue;

				$arg_slice = array_slice($payload, 0, (int) $the_['accepted_args']);
				$last = call_user_func_array( $the_['function'], $arg_slice);

				// If a response is returned from the listener and event halting is enabled
				// we will just return this response, and not call the rest of the event
				// listeners. Otherwise we will add the response on the response list.
				if(!is_null($last) && $halt){
					$response->halt($last);
					break 2;
				}

				// If a boolean false is returned from a listener, we will stop propagating
				// the event to any further listeners down in the chain, else we keep on
				// looping through the listeners and firing every one in our sequence.
				if ($last === false){
					$response->halt();
					break 2;
				}

				if(!is_null($last)){
					$response[] = $last;
				}
			}
		}
		while ( next($wp_filter[$tag]) !== false );

		reset( $wp_filter[ $tag ] );
		return $response;
	}


	/**
	* Execute wildcards. Calls all available wildcard callbacks for the given filter.
	*
	* @param  array|string					$tag
	* @param  array							$payload
	*
	* @return mixed
	*/
	protected function callFilterWildcards($tag, $item, array $payload)
	{
		if(empty($this->wildcards))
			return $item;

		reset( $this->wildcards );
		do {
			$wildcard = current($this->wildcards);

			if( $wildcard === $tag || !Str::is($wildcard, $tag) )
				continue;

			$item = $this->callFilterCallbacks($wildcard, $item, $payload);
		}
		while ( next( $this->wildcards ) !== false );
		return $item;
	}

	/**
	* Execute callbacks hooked the specified filter.
	*
	* @param  array|string					$tag
	* @param  array							$payload
	* @param  bool							$halt
	*
	* @return array
	*/
	protected function callFilterCallbacks($tag, $item, array $payload)
	{
		global $wp_filter, $merged_filters;

		if ( !isset($wp_filter[$tag]) )
			return $item;

		// Sort
		if ( !isset( $merged_filters[ $tag ] ) ) {
			ksort($wp_filter[$tag]);
			$merged_filters[ $tag ] = true;
		}

		$payload = array_merge([$item], $payload);

		reset( $wp_filter[ $tag ] );
		do {
			foreach ( (array) current($wp_filter[$tag]) as $the_ ){
				if ( is_null($the_['function']) )
					continue;

				$arg_slice = array_slice($payload, 0, (int) $the_['accepted_args']);
				$payload[0] = call_user_func_array( $the_['function'], $arg_slice);
			}
		}
		while ( next($wp_filter[$tag]) !== false );

		return $payload[0];
	}


	/**
	* Execute callbacks hooked to the 'all' event.
	*
	* @param  array|string					$tag
	* @param  array							$payload
	*
	* @return void
	*/
	protected function callAllHook($tag, ...$payload)
	{
		global $wp_filter;

		if ( !isset($wp_filter['all']) )
			return;

		_wp_call_all_hook( func_get_args() );
	}

	/**
	* Bump the number of times an action was fires.
	*
	* @param  string  $tag
	* @return void
	*/
	protected function bumpActionCount($tag)
	{
		global $wp_actions;

		if ( ! isset($wp_actions[$tag]) )
			$wp_actions[$tag] = 1;
		else
			++$wp_actions[$tag];
	}

	/**
	* Add the given tag to the active events list.
	*
	* @param  string  $tag
	* @param  bool  $unique
	* @return void
	*/
	protected function bumpFilterCount($tag)
	{
		if(!isset($this->filters[$tag]))
			$this->filters[$tag] = 1;
		else
			++$this->filters[$tag];
	}

	/**
	* Add the given tag to the active events list.
	*
	* @param  string  $tag
	* @param  bool  $unique
	* @return void
	*/
	protected function pushActive($tag, $strict = false)
	{
		global $wp_current_filter;

		if(!$strict || end($wp_current_filter) != $tag){
			$wp_current_filter[] = $tag;
		}
	}

	/**
	* Pop (remove) the given tag from the active events list.
	*
	* @param  null|string  $tag
	* @return void
	*/
	protected function popActive($tag = null)
	{
		global $wp_current_filter;

		if(is_null($tag) || end($wp_current_filter) != $tag){
			array_pop($wp_current_filter);
		}
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
		return is_null($accepted_args) ? 99 : (int) $accepted_args;
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
		return !is_string($callback) || is_callable($callback);
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
	 * Create a new handler instance.
	 *
	 * @param mixed $callback
	 *
	 * @return \TeaPress\Signals\Handler
	 */
	protected function newHandler($callback)
	{
		return new Handler($this->container, $callback);
	}
}