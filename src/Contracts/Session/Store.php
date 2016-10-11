<?php
namespace TeaPress\Contracts\Session;

use Illuminate\Session\SessionInterface;

interface Store extends SessionInterface
{
	public static function starting($callback, $priority = null, $accepted_args = null);

	public static function started($callback, $priority = null, $accepted_args = null);

	public static function saving($callback, $priority = null, $accepted_args = null);

	public static function saved($callback, $priority = null, $accepted_args = null);
}