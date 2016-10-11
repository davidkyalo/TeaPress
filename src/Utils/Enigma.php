<?php
namespace TeaPress\Utils;

use Exception;
use TeaPress\Contracts\Utils\Enigma as Contract;

class Enigma implements Contract
{
	const IV_SIZE = 16;

	protected $config = [

			'hash' => [
				'algo'	=> 'sha256',
				'salt'	=> '73d1dcff8980ebba1475bf58791926ef8939e6c3880a80215606b790de5e7784'
			],

			'cipher' => [
				'method' 	=> 'aes-128-cbc',
				'default'	=> 'rS5XHivk2lJppJVx',
				'secrets'	=> [
					"rS5XHivk2lJppJVx"	=> "6778bda861142a966332f3668781346024568189c6aefc1766e490484bde4afe",
					"rHBoqQvICb54yxr6"	=> "fb734a5418efde09956ed60d5c97ce2bc7b7df96473b28ab3d25df935b0d2e2c",
					"qBYibz3quKRQHgIh"	=> "cfdcce83e0e659312ed3e51ec0044efd78bc0e26678546e81f3692af702ccd9f",
					"bvXzKlgwqwv6FA8j"	=> "0518bc21bdb1b5adcf5ca7b18a83162fc4db53d81c5f9da619220200d8ab9911",
					"aVL8Zqm7S8BjyhJU"	=> "006d9c3aa77a07d4dec6fdfc0d20fa6d1fcca94894b62283000ea5458aa38891",
				]
			]
		];

	public function __construct(Array $config = [])
	{
		$this->setConfig($config);
	}

	public function setConfig($key, $value = null){
		if(!is_array($key))
			$key = [$key => $value];

		$config = Arr::dot($key);
		foreach ($config as $k => $v) {
			Arr::set( $this->config, $k, $v );
		}
	}

	public function config($key = null, $default = null){
		return Arr::get($this->config, $key, $defult);
	}

	public function isHashAlgo( $thing ){
		return is_string($thing) && in_array( $thing, hash_algos() );
	}

	public function hashAlgo(){
		return $this->config('hash.algo');
	}

	public function salt($raw = true){
		$salt = $this->config('hash.salt');
		return $raw ? $this->tobytes($salt) : $salt;
	}

	public function cipherMethod(){
		return $this->config('cipher.method');
	}

	public function secret($key = null, $raw = true){
		if( is_bool($key) ){
			$raw = $key;
			$key = null;
		}

		setifnot($key, $this->config('cipher.default'));

		$secret = $this->config( 'cipher.secrets.' . $key);
		return $raw ? $this->tobytes($secret) : $secret;
	}

	public function randomSecret($raw = true){
		$key = array_rand($this->config('cipher.secrets'), 1);
		$secret = $this->config('cipher.secrets.'.$key)
		return [ $key, ($raw ? $this->tobytes($secret) : $secret) ];
	}

	public function iv(){
		return $this->rand( self::IV_SIZE );
	}

	public function encrypt($data, $iv, $secret = null) {
		setifnull($secret, $this->secret());
		$ciphertext = openssl_encrypt($data, $this->cipherMethod(), $secret, OPENSSL_RAW_DATA, $iv );
		return $ciphertext;
	}

	public function decrypt($ciphertext, $iv, $secret = null) {
		setifnull($secret, $this->secret());
		$data = openssl_decrypt($ciphertext, $this->cipherMethod(), $secret, OPENSSL_RAW_DATA, $iv );
		return $data;
	}


	public function cipher($data, $data_type = null){
		list($key, $secret) = $this->randomSecret();
		$iv = $this->iv();
		$cipher = $this->encrypt( $data, $iv, $secret);
		$seal = $this->createSeal($iv, $key);
		if(!$seal){
			throw new Exception("Error Sealing Data.", 1);
			return null;
		}
		$mac = $this->hash( $this->hmacBuildMsg( $cipher, $iv ) );
		$sealed = $mac . $seal . $cipher;
		return $data_type ? $this->parseData($sealed, $data_type) : $sealed;
	}

	public function decipher($package, $data_type = null){
		$mac = $this->chunk($package, 0, 32);
		$seal = $this->chunk($package, 32, 32);
		$cipher = $this->chunk($package, 64);

		$iv = "";
		$key = "";
		$seal_opened = $this->openSeal($seal, $iv, $key);

		if(!$seal_opened || ( $key && !$this->secret($key) ) ){
			trigger_error("Error Opening Seal. Seal seems to be broken.");
			return null;
		}

		$sl = strlen( bin2hex($seal) );


		$msgmac = $this->hash( $this->hmacBuildMsg( $cipher, $iv ) );
		if( !$this->verifyHash( $msgmac, $mac) ){
			trigger_error("Invalid MAC.");
			return null;
		}

		$data = $this->decrypt($cipher, $iv, $this->secret($key));
		return $data_type ? $this->parseData($data, $data_type): $data;
	}

	public function createSeal($iv, $key){
		if(  $this->bitlen($iv) != 16 || $this->bitlen($key) != 16){
			trigger_error("Error Creating Seal. IV and Key needs to be 16 bytes long.");
			return false;
		}
		$seal = "";
		for ($i = 0; $i < 16; $i++) {
			$chunk = $this->chunk($iv, $i, 1) . $this->chunk($key, $i, 1);
			$seal .= $chunk;
		}
		return $seal;
	}

	public function openSeal($seal, &$iv, &$key){
		if(  $this->bitlen($seal) != 32 ){
			trigger_error("Error Opening Seal. Needs 32 bytes.");
			return false;
		}
		$iv = "";
		$key = "";
		for ($i = 0; $i < 32; $i++) {
			$iv .= $this->chunk($seal, $i, 1);
			$i += 1;
			$key .= $this->chunk($seal, $i, 1);
		}
		return $this->bitlen($iv) == 16 && $this->bitlen($key) == 16 ? true : false;
	}

	public function hash($data, $algo = null, $raw = false, $salt = null){
		$nargs = func_num_args();
		if( is_bool($algo) && $nargs < 4){
			$raw = $algo;
			$algo = '';
		}

		if(is_string($raw) && !is_numeric($raw) && $nargs === 3 ){
			$salt = $raw;
			$raw = false;
		}
		return $this->hashHmac($data, $algo, $raw, $salt);
	}

	public function hashHmac($data, $algo = '', $raw = true, $salt = null){
		setifnot($algo,  $this->hashAlgo());
		setifnull( $salt, $this->salt() );
		return hash_hmac( $algo, $data, $salt, $raw);
	}

	public function hmacBuildMsg( $message, $prefix = null, $surfix = null ){
		setifnull($prefix, '');
		setifnull($surfix, '');
		return $prefix . $this->bitlen($message) . $message . $surfix;
	}

	public function verifyHash($hmac0, $hmac1){
		return hash_equals( $this->tobytes($hmac0), $this->tobytes($hmac1) );
	}

	public function parseData($data, $type){
		switch ($type) {
			case 'HEXIT':
			return $this->tohex($data);
			break;

			case 'BASE64':
			return $this->tobase64($data);
			break;

			case 'BINARY':
			default:
			return $this->tobytes($data);
			break;
		}
	}

	public function isbase64($data, $strict = true)
	{
		return Str::isbase64($data, $strict);
	}

	public function ishex($value)
	{
		return Str::ishex($value);
	}

	public function tobytes($data)
	{
		return Str::tobytes($data);
	}

	public function tohex($data, $asitis = false)
	{
		return Str::tohex($data, $asitis);
	}

	public function tobase64($data, $pad = false, $asitis = false)
	{
		return Str::tobase64($data, $pad, $asitis);
	}

	public function base64Decode($data, $strict = true)
	{
		return Str::base64Decode($data, $strict);
	}

	public function bitlen($bytes)
	{
		return mb_strlen($bytes, '8bit');
	}

	public function chunk($bytes, $start = 0, $len = null, $enc = '8bit')
	{
		return mb_substr($bytes, $start, $len, '8bit');
	}

	public function rand($len)
	{
		return openssl_random_pseudo_bytes($len);
	}

	public function randStr($len)
	{
		return Str::random($len);
	}


}
