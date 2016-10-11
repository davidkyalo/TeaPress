<?php

namespace TeaPress\Http;


abstract class Handler {

	protected $app;

	protected $views_dir = '';

	protected $parent_ctrl;

	protected $_context_data;
	protected $_global_context_data;

	public function __construct($app = null){
		$this->app = $app ? $app : app();
		$this->_context_data = [];
		$this->boot();
	}

	protected function boot(){}

	public static function launch($app = null){
		return new static($app);
	}

	protected function globalContextData($default = []){
		return array_get( $this->_context_data, '_global_', $default);
	}

	protected function defaultContextData($default = []){
		return array_get( $this->_context_data, '_default_', $default);
	}

	protected function viewContextData($view, $default = []){
		$key = str_replace('.', '_', $view);
		return array_get($this->_context_data, $key, $default);
	}

	public function setContextVar($key, $value, $context = null){
		$key = $context ? str_replace('.', '_', $context). '.'. $key : '_global_.'.$key;
		array_set( $this->_context_data, $key, $value );
	}

	public function setDefaultContextVar($key, $value){
		return $this->setContextVar($key, $value, '_default_');
	}

	public function getContextVar($key, $default = null, $context = null, $context_only = false){
		$fullkey = $context ? str_replace('.', '_', $context). '.'. $key : '_global_.'.$key;
		if(!$context_only &&  !array_has( $this->_context_data, $fullkey ) ){
			$fullkey = '_default_.'.$key;
		}
		return array_get($this->_context_data, $fullkey, $default);
	}

	public function getViewContextVar($view, $key, $default = null){
		return $this->getContextVar($key, $default, $view);
	}

	public function getDefaultContextVar($key, $default = null){
		return $this->getContextVar($key, $default, '_default_', true);
	}

	protected function processViewData($view, $data = null){
		if(is_null($data)){
			$data = [];
		}elseif (!is_array($data)) {
			$data = ['data' => $data];
		}
		return array_merge($this->defaultContextData(), $this->globalContextData(), $this->viewContextData($view), $data);
	}

	public function getViewsDir(){
		return $this->views_dir;
	}

	public function setViewsDir($value){
		$this->views_dir = $value;
	}

	public function setParentCtrl($parent){
		$this->parent_ctrl = $parent;
		if(!$this->getViewsDir()){
			$this->views_dir = $parent->getViewsDir();
		}
	}

	public function getParentCtrl(){
		return $this->parent_ctrl;
	}

	protected function addChildCtrl($ctrl){
		$ctrl->setParentCtrl($this);
		return $ctrl;
	}

	protected function getViewPath($view){
		return $view && $view[0] == '~'
				? join_paths($this->getViewsDir(), substr($view, 1) ) : $view;
	}

	protected function view($view, $data = null, $once = false){
		$filepath = $this->getViewPath($view);
		$data = $this->processViewData($view, $data);
		$markup = $this->app->view->make($filepath, $data, $once);
		return $markup;
	}

	protected function render($view, $data = null, $once = false){
		$output = $this->view($view, $data, $once);
		echo $output;
	}
}