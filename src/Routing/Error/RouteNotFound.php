<?php
namespace TeaPress\Routing\Error;

use TeaPress\Contracts\Utils\ThrowsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RouteNotFound extends RoutingError implements ThrowsException
{
	/**
	 * Throw the exception
	 *
	 * @return void
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function throw()
	{
		throw new NotFoundHttpException;
	}
}