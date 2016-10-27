<?php

namespace TeaPress\Config;

interface LoaderInterface
{

	/**
	 * Load the configurations from the given file.
	 *
	 * @param  string  $filename
	 * @param  mixed  $default
	 *
	 * @return array|mixed
	 */
	public function loadFile($filename, $default = null);

	/**
	 * Load the configurations from the given path.
	 *
	 * @param  string  $path
	 * @param  string|bool  $basekey
	 *
	 * @return array
	 */
	public function loadPath($path, $basekey = true);

	/**
	 *  Load the configurations from the given namespace
	 *
	 * @param  string  $namespace
	 *
	 * @return array
	 */
	public function loadNamespace($namespace);

	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  		 $namespace
	 * @param  array|string  $paths
	 * @return void
	 */
	public function addNamespace($namespace, $paths);

	/**
	 * Get all registered namespaces.
	 *
	 * @return array
	 */
	public function namespaces();
}
