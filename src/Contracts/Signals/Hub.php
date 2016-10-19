<?php
namespace TeaPress\Contracts\Signals;

use Illuminate\Contracts\Events\Dispatcher;

interface Hub extends Dispatcher {

	/**
	 * Get the current event.
	 *
	 * @return string|null
	 */
	public function current();


	/**
	 * Get the current action.
	 *
	 * @return string|null
	 */
	public function currentAction();


	/**
	 * Get the current filter.
	 *
	 * @return string|null
	 */
	public function currentFilter();


	/**
	 * Determine if the given event hook is the current
	 *
	 * @param  array|string		$tag
	 *
	 * @return bool
	 */
	public function isCurrent($tag);


	/**
	 * Determine if the given action hook is the current
	 *
	 * @param  array|string		$tag
	 *
	 * @return bool
	 */
	public function isCurrentAction($tag);


	/**
	 * Determine if the given filter hook is the current
	 *
	 * @param  array|string		$tag
	 *
	 * @return bool
	 */
	public function isCurrentFilter($tag);

	/**
	 * Get the number of times an event has been evaluated or an array of all evaluated events if none is given.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return int|array
	 */
	public function filtered($tag = null);


	/**
	 * Get the number of times a filter has been evaluated or an array of all evaluated filters if none is given.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return int|array
	 */
	public function didFilter($tag = null);

	/**
	 * Get the number of times an event has been triggered or an array of all triggered events if none is given.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return int|array
	 */
	public function emitted($tag = null);

	/**
	 * Get the number of times an action has been triggered or an array of all triggered actions if none is given.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return int|array
	 */
	public function didAction($tag = null);


	/**
	 * Get all currently active events.
	 *
	 * @return array
	 */
	public function active();

	/**
	 * Determine the given event is currently being executed. If null is given, checks whether any event is being executed.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return bool
	 */
	public function isDoing($tag = null);


	/**
	 * Determine the given action hook is currently being executed. If null is given, checks whether any action is being executed.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return bool
	 */
	public function doingAction($tag = null);


	/**
	 * Determine the given filter hook is currently being executed. If null is given, checks whether any filter is being executed.
	 *
	 * @param  array|string|null		$tag
	 *
	 * @return bool
	 */
	public function doingFilter($tag = null);


	/**
	 * Get the real hook tag string.
	 *
	 * @param  string|array  $tag
	 *
	 * @return string
	 */
	public  function getTag($tag);


	/**
	 * Check if the given argument is a valid event name.
	 *
	 * @param  mixed  $tag The argument to check.
	 * @param  bool  $silent Whether to throw exception if validation fails
	 * @return bool
	 *
	 * @throws \UnexpectedValueException
	 */
	public function isValidTag($tag, $silent = true);


	/**
	 * Get the name of an emitter's hook tag.
	 *
	 * If emitter is bound to the container, the abstract will be used.
	 * Else if object is passed, the class name will be used.
	 * Otherwise, the passed string value will be used.
	 *
	 * @param  object|string  $emitter
	 * @param  string  $tag
	 *
	 * @return string
	 */
	public function getEmitterTag($emitter, $tag);


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
	public function bind($tag, $callback, $priority = null, $accepted_args = null, $once = null);

	/**
	* Bind an action callback.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	*
	* @return static
	*/
	public function bindWeak($tag, $callback, $priority = null, $accepted_args = null);

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
	public function addFilter($tag, $callback, $priority = null, $accepted_args = null, $once = null);


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
	public function addAction($tag, $callback, $priority = null, $accepted_args = null, $once = null);

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
	public function once($tag, $callback, $priority = null, $accepted_args = null);


	/**
	* Remove bound callback from specified hook
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public function unbind($tag, $callback, $priority = null);


	/**
	* Removes a callback from a specified action hook.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public function removeAction($tag, $callback, $priority = null);


	/**
	* Removes a callback from a specified filter hook.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	*
	* @return bool
	*/
	public function removeFilter($tag, $callback, $priority = null);


	/**
	* Determine a given hook has the given callback bound.
	* If callback is omitted, checks whether the hook has anything bound.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string|null	$callback
	*
	* @return bool
	*/
	public function has($tag, $callback = null);


	/**
	* Determine a given filter hook has the given callback bound.
	* If callback is omitted, checks whether the hook has anything bound.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string|null	$callback
	*
	* @return bool
	*/
	public function hasFilter($tag, $callback = null);


	/**
	* Determine a given action hook has the given callback bound.
	* If callback is omitted, checks whether the hook has anything bound.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string|null	$callback
	*
	* @return bool
	*/
	public function hasAction($tag, $callback = null);

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
	public function bound($callback, $tag = false);



	/**
	* Determine if a callback is bound the given hook.
	* If hook is omitted, Will check if the callback is bound to any hook
	*
	* @param  \Closure|array|string 		$callback
	* @param  array|string|null 			$tag
	*
	* @return bool
	*/
	public function isBound($callback, $tag = null);


	/**
	* Determine if a callback is bound ONCE the provided hook.
	*
	* @param  \Closure|array|string 		$callback
	* @param  array|string 					$tag
	*
	* @return bool
	*/
	public function boundOnce($callback, $tag);



	/**
	* Execute callbacks hooked the specified action.
	*
	* Calls the do_action_ref_array() wordpress function with $payload as $args.
	*
	* @param  array|string					$tag
	* @param  array							$payload
	*
	* @return void
	*/
	public function emitSignalWith($tag, array $payload = [], $halt=false);


	/**
	* Execute callbacks hooked the specified action.
	*
	* Calls the do_action() wordpress function.
	*
	* @param  array|string		$tag
	* @param  mixed				$payload
	*
	* @return void
	*/
	public function emit($tag, ...$payload);



	/**
	* Execute callbacks hooked the specified action.
	*
	* Calls the do_action() wordpress function.
	*
	* @param  array|string		$tag
	* @param  mixed				$payload
	*
	* @return void
	*/
	public function doAction($tag, ...$payload);


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
	public function applyFiltersWith($tag, array $payload=[]);


	/**
	* Filter a item by executing callbacks hooked to the given filter hook.
	*
	* Equivalent to apply_filters() wordpress function.
	*
	* @param  array|string		$tag
	* @param  mixed				$item
	* @param  mixed				...$payload
	*
	* @return void
	*/
	public function filter($tag, $item=null, ...$payload);

	/**
	* Filter a value by executing callbacks hooked to the given filter hook.
	*
	* Calls the apply_filters() wordpress function.
	*
	* @param  array|string		$tag
	* @param  mixed				$item
	* @param  mixed				...$payload
	*
	* @return void
	*/
	public function applyFilters($tag, $item=null, ...$payload);


	/**
	* Get an array of all the bound callbacks for the given hook grouped by priority unless merged is true.
	*
	* @param  string|array 	$tag
	* @param  bool			$sorted
	* @param  bool			$merged
	*
	* @return array
	*/
	public function getCallbacks($tag, $sorted=true, $merged=false);


	/**
	* Gets a flat array of all the bound callbacks for the given hook
	*
	* @param  string|array 	$tag
	* @param  bool			$sorted
	*
	* @return array
	*/
	public function getCallbacksMerged($tag, $sorted=true);
}