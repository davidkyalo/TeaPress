<?php
namespace TeaPress\Http;

use TeaPress\Utils\AttributeBag;

class Input extends AttributeBag {

	protected $items;

	protected $errors;

	protected $notices;

	public function __construct(){
		$this->items = [];
		$this->items['args'] = AttributeBag::make($_GET);
		$this->items['form'] = AttributeBag::make($_POST);
		$this->errors = AttributeBag::make();
		$this->notices = ['error' => null, 'message' => null];
	}

	public function hasError($input, $code = null){
		$key = $code ? $input . '.' . $code : $input;
		return $this->errors->has($key);
	}

	public function getError($input, $code = null){
		$key = $code ? $input . '.' . $code : $input;
		$errors = $this->errors->get($key, null);
		return $code || !is_array($errors) ? $errors : array_shift($errors);
	}

	public function getErrors($input){
		return $this->errors->get($input, []);
	}

	public function addError($input, $message, $code = null){
		$messages = $this->errors->get($input, []);

		if( is_array($message) ){
			$messages = array_merge( $messages, $message );
		}else{
			if($code){
				$messages[$code] = $message;
			}else{
				$messages[] = $message;
			}

		}
		$this->errors->set( $input, $messages );
	}


	public function setErrorNotices($notices){
		return $this->notices['error'] = $notices;
	}

	public function setMessageNotices($notices){
		return $this->notices['message'] = $notices;
	}

	public function getErrorNotices(){
		return $this->notices['error'];
	}

	public function getMessageNotices(){
		return $this->notices['message'];
	}



}