<?php
namespace TeaPress\Utils;

use WP_Error;
use Countable;
use ArrayIterator;
use IteratorIterator;
use TeaPress\Contracts\Utils\Error as Contract;

class Error extends WP_Error implements Contract
{

	protected $code = 1;

	/**
	 * Initialize the error.
	 *
	 * If `$code` is empty, the default error code is used (if present).
	 * Otherwise the other parameters will be ignored.
	 *
	 * The default code can be set on the $code class property.
	 *
	 * When `$code` is not empty, `$message` will be used even if it is empty.
	 * The `$data` parameter will be used only if it is not empty.
	 *
	 * @param string $message
	 * @param mixed $data
	 * @param string|int|null $code
	 */
	public function __construct($code = '', $message = '', $data = '')
	{
		parent::__construct($this->checkCode($code), $message, $data);
	}

	/**
	 * Retrieve first error code available.
	 * Returns the default error code if none is available.
	 *
	 * @return string|int
	 */
	public function code()
	{
		$code = $this->get_error_code();

		return empty($code) ? $this->code : $code;
	}

	/**
	 * Retrieve available error codes.
	 *
	 * @return array
	 */
	public function codes()
	{
		return $this->get_error_codes();
	}

	/**
	 * Get single error message.
	 *
	 * This will get the first message available for the code.
	 * If no code is given then the first code available will be used.
	 *
	 * @param string|int $code
	 * @return string
	 */
	public function message($code = null)
	{
		return $this->get_error_message($this->checkCode($code));
	}

	/**
	 * Retrieve all error messages for the matching code.
	 * If no code is given then the first code available will be used.
	 *
	 * @param string|int $code
	 * @return array
	 */
	public function messages($code = null)
	{
		return $this->get_error_messages($this->checkCode($code));
	}

	/**
	 * Retrieve all error messages,
	 *
	 * @return array
	 */
	public function getMessages()
	{
		return $this->errors;
	}


	/**
	 * Retrieve a flattend array of all error messages
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->get_error_messages('');
	}


	/**
	 * Add an error or append additional message to an existing error.
	 *
	 * @param string|int $code
	 * @param string $message
	 * @param mixed $data
	 * @return static
	 */
	public function add($code, $message, $data = '')
	{
		parent::add($this->checkCode($code), $message, $data);

		return $this;
	}

	/**
	 * Add data for error code.
	 *
	 * The error code can only contain one error data.
	 *
	 * @param mixed $data
	 * @param string|int $code
	 * @return static
	 */
	public function addMessage($message, $code = null)
	{
		return $this->add($code, $message);
	}

	/**
	 * Add data for error code.
	 *
	 * The error code can only contain one error data.
	 *
	 * @param mixed $data
	 * @param string|int $code
	 * @return static
	 */
	public function setData($data, $code = null)
	{
		$this->add_data($add, $this->checkCode($code));

		return $this;
	}

	/**
	 * Retrieve error data for error code.
	 * Uses the default error code if none is provided.
	 *
	 * @param string|int|null $code
	 * @return mixed
	 */
	public function data($code = null)
	{
		return $this->get_error_data($this->checkCode($code));
	}

	/**
	 * Retrieve all error data.
	 *
	 * @return array
	 */
	public function allData()
	{
		return $this->error_data;
	}

	/**
	 * Get the appropriate error code based on the one provided.
	 * Will return the default error code if null or an empty string is provided.
	 *
	 * @param string|int|null $code
	 * @return string|int
	 */
	protected function checkCode($code = null)
	{
		return is_null($code) || $code === '' ? $this->code() : $code;
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
	 * Count the number of error codes.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->codes());
	}

	/**
	 * Get an iterator for the items.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->all());
	}

}