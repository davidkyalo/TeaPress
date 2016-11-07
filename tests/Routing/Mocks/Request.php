<?php
namespace TeaPress\Tests\Routing\Mocks;

use TeaPress\Http\Request as BaseRequest;

class Request extends BaseRequest
{
	protected $testPath;

	public function setTestPath($path)
	{
		$this->testPath = trim($path, '/');

		return $this;
	}

	public function fullPath()
	{
		return is_null($this->testPath) ? parent::fullPath() : $this->path();
	}

	public function path()
	{
		if(!is_null($this->testPath)){
			return $this->testPath == '' ? '/' : $this->testPath;
		}

		return parent::path();
	}

	public function setMethod($method)
	{
		parent::setMethod($method);
		return $this;
	}
}
