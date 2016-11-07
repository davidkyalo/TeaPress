<?php
namespace TeaPress\Routing\Error;


use TeaPress\Contracts\Utils\ThrowsException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 *
*/
class MethodNotAllowed extends RoutingError implements ThrowsException
{
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
	 * @param array $allow
	 * @param string $message
	 */
	public function __construct(array $allow, $message = '')
	{
		parent::__construct('', $message, $allow);
	}

	/**
	 * Get allowed HTTP methods
	 *
	 * @return array
	 */
	public function allowed()
	{
		return $this->data();
	}

	/**
	 * Throw the exception
	 *
	 * @return void
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
	 */
	public function throw()
	{
		throw new MethodNotAllowedHttpException($this->allowed());
	}
}