<?php
namespace TeaPress\Routing;

use TeaPress\Utils\Str;
use FastRoute\BadRouteException;
use FastRoute\RouteParser\Std as FastRouteParser;

class UriParser implements UriParserInterface
{
	const VARIABLE_REGEX = <<<'REGEX'
\{
    \s* ([a-zA-Z_][a-zA-Z0-9_-]*|) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
    )?
\}
REGEX;

	const DEFAULT_PLACEHOLDER_REGEX = '[^/]+';


	protected $parsedUris = [];

	/**
	 * Parse a URI rule into an array of acceptable uri segments.
	 *
	 * If patterns are provided, they will be used in place of the default.
	 * Explicitly defined regex patterns (won the uri string)
	 * override the provided patterns.
	 *
	 * @param string 	$uri
	 * @param array 	$patterns
	 * @return array
	 */
	public function parse($uri, array $patterns = [])
	{
		if( $parsed = $this->getParsed($uri) ){

			if( $parsed['patterns'] === $patterns ){
				return $parsed['segments'];
			}

			$parsed = $this->setUriPatterns($uri, $parsed['segments'], $patterns);
		}
		else{
			$parsed = $this->parseUriRule($uri, $patterns);
		}

		$this->parsedUris[$uri] = [
					'segments' => $parsed,
					'patterns' => $patterns
				];

		return $parsed;
	}

	/**
	 * Get the cached uris for the given url rule
	 *
	 * @param string 	$uri
	 * @return array|null
	 */
	protected function getParsed($uri)
	{
		return isset($this->parsedUris[$uri]) ? $this->parsedUris[$uri] : null;
	}

	/**
	 * Set the where clauses for the given uris.
	 *
	 * @param string 	$uri
	 * @param array 	$parsed
	 * @param array 	$patterns
	 *
	 * @return array
	 */
	protected function setUriPatterns($uri, array $parsed, array $patterns = [])
	{
		if(empty($patterns)){
			return $parsed;
		}

		foreach ($parsed as &$segments) {

			if(count($segments) === 1 && is_string($segments[0]))
				continue;

			foreach ($segments as $key => &$segment) {

				if(is_array($segment)){
					$segment[1] = $this->getSegmentPattern($uri, $segment, $patterns);
				}

			}

		}

		return $parsed;
	}

	/**
	 * Get the appropriate regex pattern for a parsed URI segment
	 * based on whether the old pattern was explicitly defined on the URI string or not.
	 *
	 * @param string $uri
	 * @param array $segment
	 * @param array $patterns
	 *
	 * @return string
	 */
	protected function getSegmentPattern($uri, array $segment, array $patterns)
	{
		list($name, $pattern) = $segment;

		if( isset($patterns[$name]) )
			return $this->checkPattern( $uri, $name, $pattern, $patterns[$name] );

		return $pattern;
	}

	/**
	 * Get the appropriate regex pattern for a URI parameter
	 * based on whether the old pattern was explicitly defined on the URI string or not.
	 *
	 * @param string $uri
	 * @param string $name
	 * @param string $old
	 * @param string $new
	 *
	 * @return string
	 */
	protected function checkPattern($uri, $name, $old, $new)
	{
		if($old !== $new && Str::contains(Str::compact($uri, ''), '{'.$name.'}'))
			return $new;

		return $old;
	}

	/**
	 * Determine if the given regex is the default parameters regex.
	 *
	 * @param string $pattern
	 * @return bool
	 */
	protected function isDefaultPattern($pattern)
	{
		return $pattern === static::DEFAULT_PLACEHOLDER_REGEX;
	}

	/**
	 * Determine if the given uri segments consist of a static uri.
	 * (uri with no parameters)
	 *
	 * @param array $segments
	 * @return bool
	 */
	public function isStaticUri(array $segments)
	{
		return count($segments) === 1 && is_string($segments[0]);
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
		$ruleWithoutClosingOptionals = rtrim($rule, ']');
		$numOptionals = strlen($rule) - strlen($ruleWithoutClosingOptionals);

		// Split on [ while skipping placeholders
		$segments = preg_split('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \[~x', $ruleWithoutClosingOptionals);
		if ($numOptionals !== count($segments) - 1) {
			// If there are any ] in the middle of the rule, throw a more specific error message
			if (preg_match('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \]~x', $ruleWithoutClosingOptionals)) {
				throw new BadRouteException("Optional segments can only occur at the end of a URI rule");
			}
			throw new BadRouteException("Number of opening '[' and closing ']' does not match");
		}

		$currentRule = '';
		$ruleDatas = [];
		foreach ($segments as $n => $segment) {
			if ($segment === '' && $n !== 0) {
				throw new BadRouteException("Empty optional part");
			}
			$currentRule .= $segment;
			$ruleDatas[] = $this->parsePlaceholders($currentRule, $patterns);
		}
		return $ruleDatas;
	}

	/**
	 * Parses a URI rule string that does not contain optional segments.
	 *
	 * @param  string  $rule
	 * @param  array   $patterns
	 * @return array
	 */
	private function parsePlaceholders($rule, array $patterns = [])
	{
		if (!preg_match_all(
			'~' . self::VARIABLE_REGEX . '~x', $rule, $matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER
			)) {
			return [$rule];
		}

		$i = 0;

		$offset = 0;
		$ruleData = [];
		foreach ($matches as $set) {
			if ($set[0][1] > $offset) {
				$ruleData[] = substr($rule, $offset, $set[0][1] - $offset);
			}

			if($set[1][0] === ''){
				$set[1][0] = $i;
				++$i;
			}

			$ruleData[] = [
				$set[1][0],
				isset($set[2])
					? trim($set[2][0])
					: ( isset($patterns[$set[1][0]])
							? $patterns[$set[1][0]]
							: self::DEFAULT_PLACEHOLDER_REGEX)
			];

			$offset = $set[0][1] + strlen($set[0][0]);
		}

		if ($offset != strlen($rule)) {
			$ruleData[] = substr($rule, $offset);
		}

		return $ruleData;
	}
}
