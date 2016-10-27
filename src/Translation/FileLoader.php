<?php

namespace TeaPress\Translation;

use TeaPress\Filesystem\Filesystem;

class FileLoader implements LoaderInterface
{
	/**
	 * The filesystem instance.
	 *
	 * @var \TeaPress\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The default path for the loader.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * All of the namespace hints.
	 *
	 * @var array
	 */
	protected $hints = [];

	/**
	 * Create a new file loader instance.
	 *
	 * @param  \TeaPress\Filesystem\Filesystem  $files
	 * @param  string  $path
	 * @return void
	 */
	public function __construct(Filesystem $files, $path)
	{
		$this->path = $path;
		$this->files = $files;
	}

	/**
	 * Load the messages for the given locale.
	 *
	 * @param  string  $locale
	 * @param  string  $group
	 * @param  string  $namespace
	 * @return array
	 */
	public function load($locale, $group, $namespace = null)
	{
		if (is_null($namespace) || $namespace == '*')
			return $this->loadPath($this->path, $locale, $group);

		return $this->loadNamespaced($locale, $group, $namespace);
	}

	/**
	 * Load a namespaced translation group.
	 *
	 * @param  string  $locale
	 * @param  string  $group
	 * @param  string  $namespace
	 * @return array
	 */
	protected function loadNamespaced($locale, $group, $namespace)
	{
		if (isset($this->hints[$namespace])) {
			$lines = $this->loadPath($this->hints[$namespace], $locale, $group);

			return $this->loadNamespaceOverrides($lines, $locale, $group, $namespace);
		}

		return [];
	}

	/**
	 * Load a local namespaced translation group for overrides.
	 *
	 * @param  array  $lines
	 * @param  string  $locale
	 * @param  string  $group
	 * @param  string  $namespace
	 * @return array
	 */
	protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
	{
		$file = "{$this->path}/vendor/{$namespace}/{$locale}/{$group}.php";

		if ($this->files->exists($file)) {
			return array_replace_recursive($lines, $this->loadFile($file));
		}

		return $lines;
	}

	/**
	 * Load a locale from a given path.
	 *
	 * @param  string  $path
	 * @param  string  $locale
	 * @param  string  $group
	 * @return array
	 */
	protected function loadPath($path, $locale, $group)
	{
		return $this->loadFile("{$path}/{$locale}/{$group}.php");
	}

	/**
	 * Load the configurations from the given file.
	 *
	 * @param  string  $filename
	 * @param  mixed  $default
	 *
	 * @return array|mixed
	 */
	public function loadFile($filename, $default = NOTHING)
	{
		return $default === NOTHING || $this->files->isReadableFile($filename)
				? $this->files->require($filename, []) : value($default);
	}

	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  $namespace
	 * @param  string  $hint
	 * @return void
	 */
	public function addNamespace($namespace, $hint)
	{
		$this->hints[$namespace] = $hint;
	}
}
