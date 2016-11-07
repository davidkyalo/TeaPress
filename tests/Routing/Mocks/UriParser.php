<?php
namespace TeaPress\Tests\Routing\Mocks;

use TeaPress\Routing\UriParser as BaseUriParser;


class UriParser extends BaseUriParser
{
	protected $checkUri;
	protected $checkUriWasParsed;

	public function getParsed($uri)
	{
		return parent::getParsed($uri);
	}

	public function clearCached()
	{
		$this->parsedUris = [];
		return $this;
	}

	public function parseChecked($uri, $patterns = [])
	{
		$this->checkUri = $uri;
		$this->checkUriWasParsed = false;
		$parsed = $this->parse($uri, $patterns);
		$wasParsed = $this->checkUriWasParsed;
		$this->checkUri = null;
		$this->checkUriWasParsed = false;
		return [ $wasParsed, $parsed ];
	}


	/**
	 * Parses a URI rule string into multiple URI segment arrays.
	 *
	 * @param  string  $rule
	 * @param  array   $patterns
	 * @return array
	 */
	protected function parseUriRule($rule, array $patterns = [])
	{
		if($this->checkUri && $rule === $this->checkUri){
			$this->checkUriWasParsed = true;
		}

		return parent::parseUriRule($rule, $patterns);
	}
}
