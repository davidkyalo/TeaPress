<?php

namespace TeaPress\Utils\Traits;
use Closure;
use Exception;

trait AddsWpHooks {

	protected $_open_hook_groups = [];
	protected $_hook_group_data = [];

	protected function _getRealCallback( $callback ){
		if( is_string($callback) && starts_with($callback, '.')){
			$callback = substr($callback, 1);
			if(method_exists($this, $callback)){
				$callback = [ $this, $callback ];
			}
		}
		return $callback;
	}

	protected function _getCurrentGroupData($key = null){
		$data = !empty($this->_hook_group_data)
					? $this->_hook_group_data
					: [ 'tag' => null, 'callback' => null, 'priority' => null, 'args' => null ];
		return $key ? $data[$key] : $data;
	}

	protected function _setCurrentGroupData(){
		$data = [ 'tag' => null, 'callback' => null, 'priority' => null, 'args' => null ];
		foreach ($this->_open_hook_groups as $group) {
			foreach ($group as $key => $value) {
				if( !is_null($value) ){
					$data[$key] = $value;
				}
			}
		}
		$this->_hook_group_data = $data;
	}

	protected function _hookGroupStart(array $data = []){
		$default = [ 'tag' => null, 'callback' => null, 'priority' => null, 'args' => null ];
		$data = array_merge( $default, $data );
		$this->_open_hook_groups[] = $data;
		$this->_setCurrentGroupData();
	}

	protected function _hookGroupEnd(){
		array_pop( $this->_open_hook_groups );
		$this->_setCurrentGroupData();
	}

	protected function _getHookArgValue( $name, $value, $default = null, $silent = false){
		setifnull( $default, $this->_getCurrentGroupData($name) );
		setifnull( $value, $default );
		if(is_null($value) && !$silent)
			throw new Exception("Error Registering Hook. Argument '{$name}' missing.", 1);
		return $value;
	}

	public function action($callback = null, $tag = null, $priority = null, $args = null){
		$tag = $this->_getHookArgValue( 'tag', $tag );
		$callback = $this->_getHookArgValue( 'callback', $callback );
		$priority = $this->_getHookArgValue( 'priority', $priority, 10 );
		$args = $this->_getHookArgValue( 'args', $args, 1 );

		return add_action( $tag, $this->_getRealCallback($callback), $priority, $args );
	}

	public function filter($callback = null, $tag = null, $priority = null, $args = null){
		$tag = $this->_getHookArgValue( 'tag', $tag );
		$callback = $this->_getHookArgValue( 'callback', $callback );
		$priority = $this->_getHookArgValue( 'priority', $priority, 10 );
		$args = $this->_getHookArgValue( 'args', $args, 1 );
		return add_filter( $tag, $this->_getRealCallback($callback), $priority, $args );
	}

	public function hooks($wrapper, $data = [] ){
		if($data && is_string($data)){
			$data = ['tag' => $data];
		}
		$this->_hookGroupStart( $data );
		$wrapper_func = $this->_getRealCallback($wrapper);
		$wrapper_func($data);
		$this->_hookGroupEnd();
	}
}