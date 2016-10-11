<?php
namespace TeaPress\Auth;

use WP_User;
use Countable;
use ArrayAccess;
use IteratorAggregate;
use TeaPress\Utils\Traits\Fluent;

class Token implements ArrayAccess, Countable, IteratorAggregate {
	use Fluent;

	const PROPERTY_CONTAINER = 'data';
	const DYNAMIC_SCHEMA = true;

	const LIMITED_SESSION_DURATION = 5400; //90 mins
	const NORMAL_SESSION_DURATION = 172800; // 2 Days

	const NORMAL_SESSION = 'NORMAL_SESSION';
	const LIMITED_SESSION = 'LIMITED_SESSION';

	protected $user = false;
	protected $_token;
	protected $cookie_status = null;
	protected $data = [];
	protected $cookie = null;
	protected $fillable_properties = ['*'];

	public function __construct($user = null, array $data = [], $request = null){
		$this->user = $user;
		$this->name = 'login';
		$this->login = time();
		$this->normal(true);
		if( $request ){
			$this->ip = $request->ip();
			$this->ua = $request->header('user_agent');
		}
		else{
			if (!empty( $_SERVER['REMOTE_ADDR'] ) )
				$this->ip = $_SERVER['REMOTE_ADDR'];

			if (!empty( $_SERVER['HTTP_USER_AGENT'] ) )
				$this->ua = wp_unslash( $_SERVER['HTTP_USER_AGENT'] );
		}
		$this->setProperties($data);
	}



	// public function expirationSetter($value){
	// 	if($this->limited() && ($value - $this->login) > (static::LIMITED_MAX_DURATION + 2) ){
	// 		$value = $this->login + static::LIMITED_MAX_DURATION;
	// 	}
	// 	$this->data['expiration'] = $value;
	// }

	public function durationSetter($value){
		$this->expiration = $this->login + $value;
	}

	public function durationGetter(){
		return $this->data['expiration'] ? $this->expiration - $this->login: 0;
	}

	public function cookieSetter($cookie){
		$this->cookie = $cookie;
		$this->token = array_get( $cookie, 'token', '');
	}

	public function cookieIsInvalid(){
		return $this->cookie_status === true && $this->cookie && !empty($this->cookie)
				? false : $this->cookie_status;
	}

	public function isExpired($grace_period = 0){
		return (bool) ( ($this->expiration + $grace_period) < time());
	}

	public function isvalid(){
		return (bool) ($this->user && $this->user->ID && !$this->cookieIsInvalid());
	}

	public function generateToken(){
		return wp_generate_password( 43, false, false );
	}

	public function getToken($generate = true){
		$value = $this->data('token');
		if( is_null( $value ) && $generate )
			$value = $this->generateToken();

		return (is_null( $value ) && $generate)
				? $this->generateToken() : $value;
	}

	public function is($name){
		return ($this->name == $name);
	}

	public function limitedSetter( $value ){
		$this->limited( $value );
	}

	public function limited($duration = null){
		if($duration){
			$this->type = static::LIMITED_SESSION;
			$max = static::LIMITED_SESSION_DURATION;
			$this->duration = $duration === true || $duration > $max ? $max : $duration;
		}

		return (bool) ($this->type == static::LIMITED_SESSION);
	}

	public function normal($duration = null){
		if($duration){
			$this->type = static::NORMAL_SESSION;
			$this->duration = $duration === true ? static::NORMAL_SESSION_DURATION : $duration;
		}
		return (bool) ($this->type == static::NORMAL_SESSION);
	}

	public function setUser($user){
		return $this->user = $user;
	}

	public function user(){
		return $this->user;
	}

	public function isLoggedInUser(){
		$wp_user = wp_get_current_user();
		return (bool) ($this->user && $wp_user && $wp_user->ID == $this->user->ID);
	}

	public function check(){
		return (bool) ($this->isvalid() && $this->isLoggedInUser());
	}

	public function setDataFrom($session){
		$this->data = $session->getData();
		return $this->data;
	}

	public function getData(){
		return $this->data;
	}

	public function data($key, $default = null){
		return array_get( $this->data, $key, $default );
	}

	public function toDbArray(){
		if($this->expiration == null){
			$this->duration = $this->limited() ? static::LIMITED_SESSION_DURATION : static::NORMAL_SESSION_DURATION;
		}
		return $this->data;
	}
}
