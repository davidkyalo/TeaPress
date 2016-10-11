<?php

namespace TeaPress\Http;
use TeaPress\Utils\func;

use TeaPress\Utils\Traits\Fluent;

use \Exception;

class Form {
	use Fluent;
	const DYNAMIC_SCHEMA = false;
	const PROPERTY_CONTAINER = 'properties';

	protected static $_configs = [];

	protected static $_field_names = ['id' => '', 'key' => '', 'nonce' => ''];

	protected $readonly_properties = ['id'];
	protected $properties = [
				// 'methods' => ['post'],
				// 'handler' => null,
				// 'nonce' => null,
				// 'key' => null,
				// 'id_field_name' => null, 'nonce_field_name' => null, 'key_field_name' => null
			];
	protected $id;

	protected $fillable_properties = ['*'];

	public function __construct($id){
		$this->id = $id;
		// $this->setProperties($properties);
	}

	public static function create($id, $methods){
		static::setFormConfig($id, ['methods' => $methods]);
		return static::instance($id);
	}

	public static function instance($id){
		return new static($id);
	}


	public static function getFormConfig($id, $key = null, $default = null){
		if(!isset( static::$_configs[$id] )){
			static::$_configs[$id] = [
					'nonce' => null,
					'key' => str_random(8),
					'methods' => []
				];
		}
		return $key ?  array_get(static::$_configs[$id], $key, $default) : static::$_configs[$id];
	}

	public static function setFormConfig($id, array $config){
		static::$_configs[$id] = array_merge( static::getFormConfig($id), $config );
	}

	public static function getFormNonceAction($id, $key = null){
		setifnull($key, static::getFormConfig($id, 'key'));
		return 'form_submit_' . $id . '_' . $key;
	}

	public static function setFieldNames($names){
		static::$_field_names = $names;
	}

	public function config($key = null, $default = null){
		return static::getFormConfig($this->id, $key, $default);
	}

	public function setConfig(array $value){
		static::setFormConfig($this->id, $value);
	}

	public function nonceGetter(){
		$nonce = $this->config('nonce');
		if(!$nonce){
			$nonce = wp_create_nonce( $this->nonce_action );
			$this->setConfig(['nonce' =>  $nonce]);
		}
		return $nonce;
	}

	public function idFieldNameGetter(){
		return static::$_field_names['id'];
	}

	public function keyFieldNameGetter(){
		return static::$_field_names['key'];
	}

	public function nonceFieldNameGetter(){
		return static::$_field_names['nonce'];
	}

	public function keyGetter(){
		return $this->config('key');
	}

	public function nonceActionGetter(){
		return $this->getNonceAction();
	}

	public function getNonceAction($key = null){
		return static::getFormNonceAction($this->id, $key);
	}

	public function getFieldsHtml(){
		$output = "\n";
		$output .='<input type="hidden" name="'.$this->id_field_name.'" value="'.$this->id.'" />' . " \n";
		$output .='<input type="hidden" name="'.$this->nonce_field_name.'" value="'.$this->nonce.'" />' . " \n";
		$output .='<input type="hidden" name="'.$this->key_field_name.'" value="'.$this->key.'" />' . " \n";
		return $output;
	}

	public function verifyRequest($request){
		$nonce = $request->input($this->nonce_field_name, '');
		$key = $request->input($this->key_field_name, '');
		$action = $this->getNonceAction( $key );
		return wp_verify_nonce( $nonce, $action );
	}

	public function submit($request, $hook_tag){
		$valid = $this->verifyRequest($request);
		do_action( $hook_tag, $valid, app('input'), $this->id );
		return;
	}

}