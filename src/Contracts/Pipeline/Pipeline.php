<?php
namespace TeaPress\Contracts\Pipeline;

use Illuminate\Contracts\Pipeline\Pipeline as IlluminatePipeline;

interface Pipeline extends IlluminatePipeline
{

	/**
	 * The event tag to send the cargo through. Listeners bound to this tag will be used as pipes.
	 *
	 * @param  string|array  $tag
	 * @return static
	 */
	public function as($tag);

	/**
	 * Set the hook tags for the pipes to send the cargo through.
	 * Callbacks bound to all these hooks will be used as pipes.
	 *
	 * @param  string|array  $tag
	 * @return static
	 */
	public function mergedAs(array $tags);

	/**
	 * Set an array of parameters to be passed to the pipes.
	 *
	 * @param  array|mixed  $parameters
	 * @return static
	 */
	public function with($parameters);

}