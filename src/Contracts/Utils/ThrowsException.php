<?php
namespace TeaPress\Contracts\Utils;

interface ThrowsException
{
	/**
	 * Throw the exception
	 *
	 * @return void
	 *
	 * @throws \Throwable
	 */
	public function throw();
}