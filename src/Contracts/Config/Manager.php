<?php
namespace TeaPress\Contracts\Config;

interface Manager extends Repository
{

	/**
	 * Get the repository instance for the given namespace
	 *
	 * @param  string  $namespace
	 * @param  bool  $or_create_empty
	 *
	 * @return \TeaPress\Config\Repository|null
	 */
	public function getRepository($namespace = null, $or_create_empty = false);

	/**
	 * Get the repository instance for the given namespace
	 *
	 * @param  string  $namespace
	 * @param  bool  $create
	 *
	 * @return \TeaPress\Config\Repository
	 */
	public function getOrCreateRepository($namespace = null);


	/**
	 * Load the specified config group.
	 *
	 * @param  \TeaPress\Config\Repository|string  $repository
	 * @param  string|array|null  $path
	 *
	 * @return \TeaPress\Config\Repository|null
	 */
	public function load($repository, $path = null);


	/**
	 * Determine if the given namespace is loaded.
	 *
	 * @param  string  $namespace
	 * @return bool
	 */
	public function isLoaded($namespace);

	/**
	 * Determine if the given namespace is registered.
	 *
	 * @param  string  $namespace
	 *
	 * @return bool
	 */
	public function hasNamespace($namespace);


	/**
	 * Add a path  new namespace to the loader.
	 *
	 * @param  array|string  $path
	 * @param  string  $namespace
	 * @return void
	 */
	public function addPath($path, $namespace = null);

	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  $namespace
	 * @param  string|array  $paths
	 * @return void
	 */
	public function addNamespace($namespace, $paths);

	/**
	 * Get the configuration files loader.
	 *
	 * @return \TeaPress\Config\LoaderInterface
	 */
	public function getLoader();

}