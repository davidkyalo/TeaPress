<?php
namespace Forewordz\Models;

use WeDevs\ORM\WP\Post as WpPost;


class WpModelMeta {

	protected $user_id;

	protected $get_func = '';
	protected $set_func = '';
	protected $del_func = '';


	public function __construct($model_id){

	}

	public function scopeOne($value=''){

	}

	public function __get($key){
		return $this->_getProperty($key);
	}

	public function __set($key, $value){
		return $this->_setProperty($key, $value);
	}
	public function __call($method, $parameters)
	{
		if (in_array($method, array('increment', 'decrement')))
		{
			return call_user_func_array(array($this, $method), $parameters);
		}

		$query = $this->newQuery();

		return call_user_func_array(array($query, $method), $parameters);
	}

	/**
	 * Handle dynamic static method calls into the method.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters)
	{
		$instance = new static;

		return call_user_func_array(array($instance, $method), $parameters);
	}

}