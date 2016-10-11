<?php

namespace TeaPress\Http;

use Exception;
use TeaPress\Utils\Traits\Fluent;

class Forms {

	use Fluent;
	const DYNAMIC_SCHEMA = false;

	protected $private_properties = [];
	protected $readonly_properties = ['forms'];
	protected $fillable_properties = ['*'];
	protected $forms;

	protected $request;
	protected $form_hook_tags_prefix = 'tea_form_handler-';
	protected $success_hook_tags_prefix = 'tea_form_success-';
	protected $error_hook_tags_prefix = 'tea_form_error-';

	protected $form_id_field =  '_form';
	protected $form_nonce_field = '_form_nonce';
	protected $form_key_field =  '_form_nonce_key';

	public function __construct(array $properties = []){
		// $this->forms = [];
		$this->setProperties($properties);

		Form::setFieldNames( [
		                'id' => $this->form_id_field,
					 	'key' => $this->form_key_field,
					 	'nonce' => $this->form_nonce_field ] );
		$this->request = app('request');
		$this->checkSubmitted();
	}

	private function checkSubmitted(){
		add_action('init', function(){
			if(!isset($_REQUEST[ $this->form_id_field ]) )
				return;

			$form_id = $_REQUEST[ $this->form_id_field ];
			if( $this->formExists($form_id) ){
				// $form = $this->get( $form_id );
				$methods = Form::getFormConfig( $form_id, 'methods', [] );
				if($this->request->isMethod($methods)){
					return $this->processForm($form_id);
				}else{
					$msg = "Error Processing Form (Invalid HTTP Method). Form '{$form_id}' only allows [ ". implode(', ', $methods) ." ] HTTP methods. ".$this->request->method()." not allowed.";
					trigger_error($msg);
				}
			}else{
				trigger_error("Submitted form '{$form_id}' is not yet registered.");
			}
		}, 9999);
	}

	public function createForm($form_id, array $methods){
		return Form::create($form_id, $methods);
	}

	public function get($form_id){
		if(!$this->formExists($form_id))
			return null;
		return Form::instance($form_id);
	}

	public function add($form_id, $handler, $methods = null, $args = null){
		if($this->formExists($form_id)){
			throw new Exception("Error Registering Form. Form ID : '{$form_id}' already registered", 1);
			return false;
		}
		if(is_null($methods)){
			$methods = ['post', 'put', 'get'];
		}
		elseif(!is_array($methods)){
			$methods = [$methods];
		}
		setifnull( $args, 3 );
		add_action( $this->getFormHookTag($form_id), $handler, 10, $args );
		return $this->createForm($form_id, $methods);
	}

	public function formExists($form_id){
		// return in_array($form_id, $this->forms);
		return has_filter( $this->getFormHookTag($form_id) );
	}

	public function getFormHookTag($form_id){
		return $this->form_hook_tags_prefix . $form_id;
	}

	protected function processForm($form_id){
		$form = $this->get($form_id);
		return $form->submit( $this->request, $this->getFormHookTag($form_id) );
	}
}