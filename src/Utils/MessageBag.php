<?php

namespace TeaPress\Utils;

use Closure;
use WP_Error;
use Exception;
use ArrayIterator;
use JsonSerializable;
use BadMethodCallException;
use TeaPress\Contracts\Utils\Jsonable;
use TeaPress\Contracts\Utils\Arrayable;
use TeaPress\Contracts\Utils\ArrayBehavior;
use TeaPress\Contracts\Utils\MessageProvider;
use TeaPress\Contracts\Utils\MessageBag as Contract;


class MessageBag extends WP_Error implements Contract, ArrayBehavior, Arrayable, Jsonable, JsonSerializable, MessageProvider {

	const EMPTY_MESSAGE_FLAG = "__MESSAGE__";
	const DEFAULT_KEY = '-';

	public $errors;
	public $error_data;

	protected $type = 'notice';

	protected $factory;

	protected $messages = [];

	protected $message_data = [];

	protected $format = ':message';

	protected $flashed = [];

	protected static $message_loader;

	public static function setMessageLoader($loader){
		static::$message_loader = $loader;
	}

	public function __construct( $key = null, $message = '', $data = '' ) {

		if($key && is_string( $key )) {
			$this->add($key, $message, $data);
		}
		elseif( $key instanceof WP_Error ||  $key instanceof MessageProvider) {
			$this->merge( $key );
		}
		elseif (is_array($key) || ($key instanceof IteratorAggregate)) {
			foreach ($key as $index => $value) {
				$this->add( $index, $value );
			}
		}

		if($key instanceof WP_Error)
			$this->message_data = $key instanceof self
				? $key->getMessageData() : $key->error_data;

	}

	public static function create($key = null, $message = '', $data = ''){
		return new static($key, $message, $data);
	}

	public static function cast($bag, $force = false){
		if( $bag instanceof self)
			return $bag;

		if( is_array($bag) || ($bag instanceof MessageProvider)
			|| ($bag instanceof IteratorAggregate) || ($bag instanceof WP_Error))
			return new static( $bag );

		if( $force === true )
			return new static( $bag );
	}

	// public function setFactory(Factory $factory){
	// 	$this->factory = $factory;
	// 	return $this;
	// }


	// public function getFactory()
	// {
	// 	if(is_null($this->factory))
	// 		throw new Exception("Message bag factory not set.");

	// 	return $this->factory;
	// }


	// public function setType($type)
	// {
	// 	$this->type = $type;
	// 	return $this;
	// }

	// public function getType()
	// {
	// 	return $this->type;
	// }

	public function emptyMessageFlag(){
		return static::EMPTY_MESSAGE_FLAG;
	}

	public function push($message, $data = '')
	{
		return $this->add(static::DEFAULT_KEY, $message, $data);
	}

	// public function flashAll()
	// {
	// 	$manager = $this->getManager();
	// 	$name = $this->getName();

	// 	if(!$manager || !$name)
	// 		return false;

	// 	$manager->flashAll($name);
	// 	return $this;
	// }

	// public function flash($keys = null)
	// {
	// 	$keys = is_null($keys) ? $this->keys() : $keys;


	// }

	public function add($key, $messages = '', $data = '')
	{
		$messages = !is_array($messages) ? (array) ( (string) $messages ) : $messages;
		foreach ($messages as $message) {
			$message = strlen($message) === 0 ? $this->emptyMessageFlag() : $message;
			if($this->isUnique($key, $message))
				$this->messages[$key][] = $message;
		}
		if ( !empty($data) )
			$this->message_data[$key] = $data;

		return $this;
	}

	public function first($key = null, $format = true, $replace = null)
	{
		$messages = is_null($key)
				? $this->all($format, $replace) : $this->get($key, $format, $replace);
		return count($messages) > 0 ? $messages[0] : '';
	}

	public function get($key, $format = true, $replace = null)
	{
		$messages = array_key_exists($key, $this->messages) ? $this->messages[$key] : [];
		if(empty( $messages ) || $format === false)
			return $messages;

		$format = $this->checkFormat( ($format === true ? null : $format) );
		return $this->transform( $this->cleanMessages($messages, $replace), $format, $key);
	}

	public function all($format = true, $replace = null)
	{
		$all = [];
		foreach ($this->keys() as $key) {
			$all = array_merge($all, $this->get($key, $format, $replace));
		}
		return $all;
	}

	protected function cleanMessage($message, $replace = null )
	{
		$clean = $this->cleanMessages( (array) $message, $replace);
		return is_array( $message ) ? $clean : array_shift( $clean );
	}

	protected function cleanMessages(array $messages, $replace = null)
	{
		if(empty($messages))
			return $messages;

		$nonempty = [];
		foreach ($messages as $key => $message) {
			if(is_array($message)){
				$nonempty[$key] = $this->cleanMessages($message, $replace);
			}else{

				$replacement = '';
				if( is_array($replace) && is_assoc_array($replace) ){
					$replacement = array_values( $replace );
					$replace = array_keys( $replace );
				}

				$message = trim( (!$replace ? $message : str_replace($replace, $replacement, $message)) );

				if($message !== $this->emptyMessageFlag() && !empty($message))
					$nonempty[] = $message;
			}

		}
		return $nonempty;
	}

	public function clean($replace = null){
		$clean = $this->cleanMessages( $this->getMessages(), $replace );
		$this->messages = $clean;
		return $this;
	}

	public function getMessageData($key = null, $default = null){
		return is_null($key) ? $this->message_data : Arr::get($this->message_data, $key, $default ) ;
	}

	public function get_error_codes() {
		return $this->keys();
	}

	public function get_error_code() {
		$codes = $this->keys();
		return empty($codes) ? '' : $codes[0];
	}

	public function get_error_messages($key = '') {
		return empty($key) ? $this->all(true) : $this->get($key, true);
	}

	public function get_error_message($key = null) {
		return $this->first($key);
	}

	public function get_error_data($code = '') {
		if ( empty($code) )
			$code = $this->get_error_code();

		return $this->getMessageData($code);
	}

	public function add_data($data, $key = '') {
		setifempty($key, $this->get_error_code());
		$this->message_data[$key] = $data;
		return $this;
	}

	public function flushEverything(){
		$this->flushAllMessages();
		$this->flushAllData();
	}

	public function flushAllMessages(){
		$this->messages = [];
	}

	public function flushAllData(){
		$this->message_data = [];
	}

	public function remove( $key ) {
		if( is_array($key) ){
			foreach ($key as $k) {
				$this->remove($k);
			}
		}else{
			unset( $this->messages[ $key ] );
			unset( $this->message_data[ $key ] );
		}
		return $this;
	}

	public function __get($key){
		if($key == 'errors'){
			return $this->messages;
		}elseif ($key == 'error_data') {
			return $this->message_data;
		}else{
			return $this->get($key);
		}
	}

	public function __set($key, $message){
		$this->add($key, $message);
	}


	public function offsetExists($key){
		return $this->has($key);
	}

	public function offsetGet($key){
		return $this->get($key);
	}

	public function offsetSet($key, $value){
		return $this->add($key, $value);
	}

	public function offsetUnset($key){
		$this->remove($key);
	}

	/**
	 * Get the keys present in the message bag.
	 *
	 * @return array
	 */
	public function keys()
	{
		return array_keys($this->messages);
	}

	/**
	 * Merge a new array of messages into the bag.
	 *
	 * @param  \Illuminate\Contracts\Support\MessageProvider|\WP_Error|array  $messages
	 * @return $this
	 */
	public function merge($messages)
	{
		$data = [];
		if($messages instanceof self){
			// $this->messages = array_merge_recursive($this->messages, $messages->getMessages());
			$messages = $messages->getMessages();
			$data = $messages->getMessageData(null, []);
		}
		elseif ($messages instanceof WP_Error) {
			$messages = $messages->errors;
			$data = $messages->error_data;
		}
		elseif ($messages instanceof MessageProvider) {
			$messages = $messages->getMessageBag()->getMessages();
		}

		foreach ((array) $messages as $k => $m) {
			$this->add( $k, $m );
		}

		if(!empty($data))
			$this->message_data = array_merge_recursive($this->message_data, $data);

		return $this;
	}

	/**
	 * Determine if a key and message combination already exists.
	 *
	 * @param  string  $key
	 * @param  string  $message
	 * @return bool
	 */
	protected function isUnique($key, $message)
	{
		$messages = (array) $this->messages;

		return ! isset($messages[$key]) || ! in_array($message, $messages[$key]);
	}

	/**
	 * Determine if messages exist for a given key.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key = null)
	{
		return $this->first($key, false) !== '';
	}



	/**
	 * Format an array of messages.
	 *
	 * @param  array   $messages
	 * @param  string  $format
	 * @param  string  $messageKey
	 * @return array
	 */
	protected function transform($messages, $format, $messageKey)
	{
		$messages = (array) $messages;

		// We will simply spin through the given messages and transform each one
		// replacing the :message place holder with the real message allowing
		// the messages to be easily formatted to each developer's desires.
		$replace = [':message', ':key'];

		foreach ($messages as &$message) {
			$message = str_replace($replace, [$message, $messageKey], $format);
		}

		return $messages;
	}

	/**
	 * Get the appropriate format based on the given format.
	 *
	 * @param  string|null  $format
	 * @param  string|null  $default
	 * @return string
	 */
	public function checkFormat($format = null, $default = null)
	{
		$default = is_null($default) ? $this->format : $default;
		return $format ?: $this->format;
	}

	/**
	 * Get the raw messages in the container.
	 *
	 * @return array
	 */
	public function getMessages()
	{
		return $this->messages;
	}

	/**
	 * Get the messages for the instance.
	 *
	 * @return \Illuminate\Support\MessageBag
	 */
	public function getMessageBag()
	{
		return $this;
	}

	/**
	 * Get the default message format.
	 *
	 * @return string
	 */
	public function getFormat()
	{
		return $this->format;
	}

	/**
	 * Set the default message format.
	 *
	 * @param  string  $format
	 * @return \Illuminate\Support\MessageBag
	 */
	public function setFormat($format = ':message')
	{
		$this->format = $format;

		return $this;
	}

	/**
	 * Determine if the message bag has any messages.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return ! $this->any();
	}

	/**
	 * Determine if the message bag has any messages.
	 *
	 * @return bool
	 */
	public function any()
	{
		return $this->count() > 0;
	}

	/**
	 * Get the number of messages in the container.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->getMessages();
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

	/**
	 * Convert the message bag to its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}


	public function getIterator()
	{
		return new ArrayIterator($this->toArray());
	}
}