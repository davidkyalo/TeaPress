<?php
namespace TeaPress\Session;

use SessionHandlerInterface as Base;

interface SessionHandlerInterface extends Base
{
	public function getSessionId();

	public function setSessionId($id);

	public function setSessionName($name);

	public function getSessionName();

}