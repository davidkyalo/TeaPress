<?php
namespace TeaPress\Contracts\Signals;

use Illuminate\Contracts\Events\Dispatcher;

interface Signals extends Dispatcher {

	/**
	 * Get the current event.
	 *
	 * @return string|null
	 */
	public function current();

	/**
	 * Get the number of times the given filter has been run.
	 * If a tag is not provided, returns an array of all ran filters.
	 *
	 * @param  string|null		$tag
	 * @return int|array
	 */
	public function filtered($tag = null);


	/**
	 * Get the number of times the given event/action has been fired.
	 * If a tag is not provided, returns an array of all fired events/actions.
	 *
	 * @param  string|null		$tag
	 * @return int|array
	 */
	public function fired($tag = null);

	/**
	 * Get all currently active events.
	 *
	 * @return array
	 */
	public function active();

	/**
	 * Determine the given event is currently being executed.
	 * If null is given, checks whether any event is being executed.
	 *
	 * @param  array|string|null		$tag
	 * @return bool
	 */
	public function isDoing($tag = null);

	/**
	 * Get the real hook tag string.
	 *
	 * @param  string  $tag
	 * @return string
	 */
	public  function getTag($tag);

	/**
	* Bind the given callback to the specified $event.
	*
	* @param  string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null 						$accepted_args
	* @return void
	*/
	public function bind($tag, $callback, $priority = null, $accepted_args = null);

	/**
	* Bind a filter callback.
	*
	* @param  string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null 						$accepted_args
	* @return void
	*/
	public function addFilter($tag, $callback, $priority = null, $accepted_args = null);


	/**
	* Bind an action callback.
	*
	* @param  array|string					$tag
	* @param  \Closure|array|string 		$callback
	* @param  int|null						$priority
	* @param  int|null|bool					$accepted_args
	* @param  bool							$once
	*
	* @return void
	*/
	public function addAction($tag, $callback, $priority = null, $accepted_args = null);

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
	public function filter($tag, $item=null, $payload = []);

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