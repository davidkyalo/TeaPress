<?php

namespace TeaPress\Http;

use TeaPress\Auth\Sentry;
use TeaPress\Messages\Notifier;
use TeaPress\Messages\Bag as MessageBag;

abstract class Shortcode extends Handler {

	protected $view_name;
	protected $css_views;
	protected $js_views;

	protected $styles_printed = false;
	protected $scripts_printed = false;
	protected $shortcode_args;
	protected $_input;
	protected $errors;
	protected $messages;
	protected $lang_prefix;
	protected $_user;
	protected $notices;

	protected static $contexts = [

		];

	public function __construct($app = null, $properties = []){
		parent::__construct($app);
		foreach ($properties as $key => $value) {
			$this->{$key} = $value;
		}
		$this->notices = $this->app->make(Notifier::class);
		$this->boot();
	}

	protected function boot(){}

	public static function getInstance(){
		return app(get_called_class(), [app()]);
	}

	public static function enqueueJS($hook = null, $priority = 10){
		setifnull($hook, 'wp_footer');
		static::getInstance()->renderJS( $hook, $priority );
	}

	public static function enqueueCSS($hook = null, $priority = 10){
		setifnull($hook, 'wp_head');
		static::getInstance()->renderCSS( $hook, $priority );
	}

	public static function enqueueAssets($rule, $js_tag = null, $css_tag = null, $js_priority = 10, $css_priority = 10){
		static::enqueueJS( $js_tag, $js_priority );
		static::enqueueCSS( $css_tag, $css_priority );
	}

	public static function getStatic($key, $default = null){
		return isset(static::${$key})? static::${$key}: $default;
	}

	public static function getShortcodeTag(){
		return isset( static::$shortcode_tag ) ? static::$shortcode_tag : null;
	}

	public static function getFormId(){
		return isset( static::$form_id ) ? static::$form_id : null;
	}

	protected static function registerShortcode(){
		$cls = get_called_class();
		if($tag = static::getShortcodeTag()){
			add_shortcode( $tag, $cls.'::doShortCode' );
		}
	}

	protected static function registerForm(){
		$cls = get_called_class();
		if($form = static::getFormId()){
			add_form( $form, $cls.'::formSubmited', static::getStatic( 'form_methods', ['POST'] ) );
		}
	}
	public static function register(){
		app()->singleton( get_called_class() );
		static::registerForm();
		static::registerShortcode();
	}

	public static function doShortCode($args, $content = ''){
		$instance = static::getInstance();
		$instance->setShortcodeArgs( $args );
		return $instance->shortcode( $instance->getArgs(), $content );
	}

	public static function formSubmited($valid, $input, $form_id){
		if($form_id != static::getFormId() )
			return;
		$instance = static::getInstance();
		$instance->setInput( $input );
		$success = $instance->submit( $valid, $input );
		$instance->processNotices( $this->app->make(Notifier::class) );
		static::firePostSubmitAction($success);
	}



	public static function onSave( $callback, $priority = 10, $args = 1 ){
		add_action( static::$form_id . '_submit_succcess', $callback, $priority, $args);
	}

	public static function onSaveError( $callback, $priority = 10 , $args = 1){
		add_action(static::$form_id . '_submit_error', $callback, $priority, $args);
	}

	protected static function firePostSubmitAction($success){
		if( $success ){
			do_action( static::$form_id . '_submit_succcess', static::getInstance()->getMessages());
		}else{
			do_action( static::$form_id . '_submit_error', static::getInstance()->getErrors());
		}
	}

	public function setInput( $input ){
		$this->_input =  $input;
	}

	public function input($key = null, $default = null){
		$input = is_null($this->_input) ? app('input') : $this->_input;
		return $key ? $input->get($key, $default) : $input;
	}

	protected function langKey($key){
		return $this->lang_prefix ? str_finish($this->lang_prefix, '.').$key : $key;
	}

	public function getErrors(){
		if(is_null( $this->errors )){
			$this->errors = $this->notices->getBag('error', true);
		}
		return $this->errors;
	}

	public function hasErrors($key = null){
		if(!$this->errors)
			return false;

		return  $key ? $this->errors->has($key) : $this->errors->any();
	}

	public function getMessages(){
		if(is_null( $this->messages )){
			$this->messages = $this->notices->getBag('error', true);
		}
		return $this->messages;
	}

	public function hasMessages($key = null){
		if(!$this->messages)
			return false;

		return  $key ? $this->messages->has($key) : $this->messages->any();
	}

	protected function inputAddFieldError( $field, $code =null ){
		if(is_array($field)){
			foreach ($field as $name) {
				$this->input()->addError($name, $code);
			}
		}else{
			$this->input()->addError($field, $code);
		}

	}

	protected function addInputError( $field, $code, $message = null, $data = null ){
		$this->inputAddFieldError($field, $code);
		$this->addError( $code, $message, $data );
	}

	protected function addError($code, $message = null, $data = null){
		if(is_null($message)){
			$message = $this->app->lang->get( $this->langKey( 'error.'.$code ));
		}
		$this->getErrors()->add( $code, $message, $data );
	}

	protected function addMessage($code, $message = null, $data = 'message'){
		if(is_null($message)){
			$message =  $this->app->lang->get( $this->langKey( 'info.'.$code ));
		}
		$this->getMessages()->add( $code, $message, $data );
	}

	protected function defaultShortcodeArgs(){
		return [];
	}

	public function getArgs($key = null, $default = null){
		if( is_null( $this->shortcode_args )){
			$this->setShortcodeArgs();
		}
		return $key ? array_get( $this->shortcode_args, $key, $default) : $this->shortcode_args;
	}

	public function setShortcodeArgs($args = ''){
		setifempty($args, []);
		if(!is_array( $args )){
			$args = [$args];
		}
		$this->shortcode_args = array_merge( $this->defaultShortcodeArgs(), $args );
	}

	public function submit($verified, $input){}

	abstract public function shortcode($args, $content = "");


	public function setUser($user){
		$this->_user = $user;
	}

	protected function sentry(){
		return $this->app->make(Sentry::class);
	}

	protected function user(){
		return $this->_user ? $this->_user : $this->sentry()->user();
	}


	protected function authCheck($strict = true){
		return !$strict && $this->user() ? true : $this->sentry()->check();
	}

	protected function setupShortcodeViewData(){
		$input = $this->input();
		if($this->hasErrors())
			$input->setErrorNotices( $this->getErrors() );

		if($this->hasMessages())
			$input->setMessageNotices( $this->getMessages() );

		$this->setDefaultContextVar('errors', $this->getErrors());
		$this->setDefaultContextVar('messages', $this->getMessages());

		if( $form_id = static::getFormId() ){
			$this->setDefaultContextVar('form', get_form( $form_id ));
			$this->setDefaultContextVar('input', $input);
		}
	}

	protected function renderView($data = null, $view = null){
		setifnull( $view, $this->view_name );
		if(is_null($view)){
			trigger_error( 'Shortcode view not set. In shortcode handler '. get_called_class());
			return '';
		}
		$this->setupShortcodeViewData();
		$this->renderAssests();
		$output = $this->view($view, $data);
		return $output;
	}

	protected function renderAssests(){
		$this->renderCSS();
		$this->renderJS('wp_footer');
	}

	public function renderJS($hook = null, $priority = 10){
		if(!$this->js_views || $this->scripts_printed)
			return;
		if(!$hook){
			$views = !is_array( $this->js_views ) ? [$this->js_views] : $this->js_views;
			foreach ($views as $view) {
				$this->render( $view, null, true );
			}

			$this->scripts_printed = true;
		}else{
			add_action( $hook, function(){
				$this->renderJS();
			}, $priority);
		}

	}

	public function renderCSS($hook = null, $priority = 10){
		if(!$this->css_views || $this->styles_printed)
			return;

		if(!$hook){
			$views = !is_array( $this->css_views ) ? [$this->css_views] : $this->css_views;
			foreach ($views as $view) {
				 $this->render( $view, null, true );
			}
			$this->styles_printed = true;
		}else{
			add_action( $hook, function(){
				$this->renderCSS();
			}, $priority);
		}
	}
}
