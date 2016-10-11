<?php
namespace TeaPress\Auth;

use JsonSerializable;
use TeaPress\Carbon\Carbon;
use TeaPress\Encryption\Enigma;
use TeaPress\Utils\Traits\Fluent;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Permit implements Arrayable, Jsonable, JsonSerializable {
	use Fluent;

	const DYNAMIC_SCHEMA = true;
	const PROPERTY_CONTAINER = 'properties';

	protected $enigma;

	protected  $properties = [];

	protected $owner;
	protected $time;
	protected $hash;
	protected $hash_verified = null;
	protected $is_valid = null;
	protected $is_stamped = null;

	protected $private_properties = ['enigma'];
	protected $readonly_properties = ['is_valid', 'is_stamped', 'hash_verified'];

	protected static $url_base = '/premits/auth/';
	protected static $cipher_keys = [
			'user_login'		=> 'u',
			'key'				=> 'k',
			'name'				=> 'n',
			'url'				=> 'r',
			'time'				=> 't',
			'data'				=> 'd',
		];

	protected static $serializables = [
			'user_login', 'key', 'name',
			'url', 'time','data'
		];

	public function __construct(Enigma $enigma, $owner, $properties = []){
		$this->enigma = $enigma;
		$this->owner = $owner;
		$this->time = Carbon::now();
		$this->fill($properties);
	}

	public function userLoginGetter(){
		return $this->user->login;
	}

	public function userGetter(){
		return $this->owner->user;
	}

	public function keyGetter(){
		return $this->owner->key;
	}

	public function hashGetter(){
		return $this->getHash();
	}

	public function timeSetter($value){
		if($value){
			$this->time = Carbon::cast($value);
		}
	}

	public function fill(array $attributes = []){
		foreach ($attributes as $key => $value) {
			$this->_setProperty($key, $value, false);
		}
		return $this;
	}

	public function makeHash( $raw = true ){
		return $this->enigma->hash( $this->time->timestamp . $this->owner->secret, $raw );
	}

	public function getHash($create = true){
		if(is_null($this->hash) && $create){
			return $this->makeHash(true);
		}
		return $this->hash;
	}

	public function verifyHash($hash = null){
		if(is_null($this->hash_verified) || !is_null($hash)){
			setifnot($hash, $this->hash);
			if($hash){
				$this->hash_verified = $this->enigma->verifyHash( $this->makeHash(), $hash );
			}
		}
		return $this->hash_verified;
	}


	public function valid(){
		return (boolean) ($this->verifyHash() && $this->stamp());
	}

	public function stamp(){
		if(is_null($this->is_stamped)){
			$this->is_stamped = $this->owner->stamp();
		}
		return $this->is_stamped;
	}

	protected function arrayCastProperty($name){
		$value = $this->{$name};
		if($value instanceof Carbon)
			return $value->timestamp;


		switch ($name) {
			case 'url':
				return $value; // parse_url($value, PHP_URL_PATH);
				break;

			default:
				return $value;
				break;
		}
	}

	public function toArray(array $properties = []){
		$arr = [];
		foreach (static::$serializables as $property) {
			if(empty($properties) || in_array($property, $properties))
				$arr[$property] = $this->arrayCastProperty($property);
		}
		return $arr;
	}

	public function toJson($options = 0){
		return json_encode($this->toArray(), $options);
	}

	public function jsonSerialize() {
		return $this->toArray();
	}

	public function cryptDataArray(array $properties = []){
		$arr = $this->toArray($properties);
		return array_transform($arr, function($value, $key){
					return !is_null($value)
								? array_get(static::$cipher_keys, $key, null) : null;
				});
	}

	public function __toString(){
		return $this->toString([], false);
	}

	public function toString(array $properties = [], $encryptable = false){
		$data = $encryptable ? $this->cryptDataArray($properties) : $this->toArray($properties);
		return json_encode($data);
	}

	public function encrypt(array $properties = []){
		$text = $this->toString($properties, true);
		return $this->enigma->cipher( $text );
	}

	public function toUrl( $scheme = null ){
		$enigma = $this->enigma;
		$cipher = $enigma->tobase64( $this->encrypt() );
		$hash = $enigma->tobase64( $this->getHash() );
		$path = static::$url_base;

		return $cipher && $hash
				? site_url(join_paths($path, $hash, $cipher, '/'), $scheme)
				: null;
	}

	public static function setSerializables(array $fields = []){
		if($fields)
			static::$serializables = $fields;
	}

	public static function setCipherKeys(array $fields = []){
		if($fields)
			static::$cipher_keys = $fields;
	}

	public static function setUrlBase($base = null){
		if($base)
			static::$url_base = $base;
	}

}