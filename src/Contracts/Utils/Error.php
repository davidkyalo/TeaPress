<?php
namespace TeaPress\Contracts\Utils;

use Countable;
use ArrayIterator;
use IteratorAggregate;

interface Error extends Arrayable, IteratorAggregate, Countable
{

	/**
	 * Retrieve first error code available.
	 * Returns the default error code if none is available.
	 *
	 * @return string|int
	 */
	public function code();

	/**
	 * Retrieve available error codes.
	 *
	 * @return array
	 */
	public function codes();


	/**
	 * Get single error message.
	 *
	 * This will get the first message available for the code.
	 * If no code is given then the first code available will be used.
	 *
	 * @param string|int $code
	 * @return string
	 */
	public function message($code = null);

	/**
	 * Retrieve all error messages for the matching code.
	 * If no code is given then the first code available will be used.
	 *
	 * @param string|int $code
	 * @return array
	 */
	public function messages($code = null);


	/**
	 * Retrieve all error messages,
	 *
	 * @return array
	 */
	public function getMessages();

	/**
	 * Retrieve a flattend array of all error messages
	 *
	 * @return array
	 */
	public function all();

	/**
	 * Add an error or append additional message to an existing error.
	 *
	 * @param string|int $code
	 * @param string $message
	 * @param mixed $data
	 * @return static
	 */
	public function add($code, $message, $data = '');

	/**
	 * Add data for error code.
	 *
	 * The error code can only contain one error data.
	 *
	 * @param mixed $data
	 * @param string|int $code
	 * @return static
	 */
	public function addMessage($message, $code = null);

	/**
	 * Add data for error code.
	 *
	 * The error code can only contain one error data.
	 *
	 * @param mixed $data
	 * @param string|int $code
	 * @return static
	 */
	public function setData($data, $code = null);


	/**
	 * Retrieve error data for error code.
	 * Uses the default error code if none is provided.
	 *
	 * @param string|int|null $code
	 * @return mixed
	 */
	public function data($code = null);

	/**
	 * Retrieve all error data.
	 *
	 * @return array
	 */
	public function allData();
}