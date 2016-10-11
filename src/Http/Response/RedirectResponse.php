<?php

namespace TeaPress\Http\Response;

use InvalidArgumentException;
use TeaPress\Events\EmitterInterface;
use Illuminate\Http\RedirectResponse as BaseRedirectResponse;

class RedirectResponse extends BaseRedirectResponse implements EmitterInterface
{
	use ResponseTrait;

	public function setStatusCode($code, $text = null)
	{
		parent::setStatusCode($code, $text);

		$this->statusCode = $code = (int) $code;
		if ($this->isRedirect()) {
			throw new InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $code));
		}

		return $this;
	}
}