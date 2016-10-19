<?php

namespace TeaPress\Config;

use TeaPress\Utils\Arr;
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
	 * All of the namespace paths.
	 *
	 * @var array
	 */
	protected $namespaces = [];

	/**
	 * Create a new file loader instance.
	 *
	 * @param  \TeaPress\Filesystem\Filesystem  $files
	 * @param  string  $path
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		$this->files = $files;
	}


	/**
	 * Load the configurations from the given file.
	 *
	 * @param  string  $filename
	 * @param  mixed  $default
	 *
	 * @return array|mixed
	 */
	public function loadFile($filename, $default = [])
	{
		return $this->files->require($filename, [], $default);
	}

	/**
	 * Load the configurations from the given path.
	 *
	 * @param  string|array  $path
	 * @param  string|bool  $basekey
	 *
	 * @return array
	 */
	public function loadPath($path, $basekey = true)
	{
		$basekey = $basekey === true && !is_array($path)
					? $this->getPathKey($path, dirname($path))
					: ( is_bool($basekey) ? null : $basekey);

		if(is_array($path))
		{
			$config = [];
			foreach ($path as $k => $p) {
				$bk = is_string($k) ? $k : null;
				$config = array_merge( $config, $this->loadPath($p, $bk));
			}
		}
		else
		{
			if($this->files->isDirectory($path)){
				$config = [];
				foreach ($this->files->findFiles($path)->name('*.php') as $file){
					$filename = $file->getRealPath();
					Arr::set($config,
							$this->getPathKey($filename, $path),
							$this->loadFile($filename));
				}
			}
			else{
				$config = $this->loadFile($path, []);
			}
		}
		return is_null($basekey) ? $config : [ $basekey => $config ];
	}


	/**
	 * Load the configurations from the given path.
	 *
	 * @param  array  $path
	 * @param  string|bool  $basekey
	 *
	 * @return array
	 */
	public function loadPaths(array $paths, $basekey = true)
	{

	}

	/**
	 *  Load the configurations from the given namespace
	 *
	 * @param  string  $namespace
	 *
	 * @return array
	 */
	public function loadNamespace($namespace)
	{
		if(isset($this->namespaces[$namespace]))
			return $this->loadPath( (array) $this->namespaces[$namespace] );

		return [];
	}


	protected function getPathKey($path, $base = '')
	{
		$path = trim($path, '/');
		$base = trim($base, '/');

		$key = substr($path, strlen($base));

		if( substr($key, -4) == '.php' )
			$key = substr($key, 0, -4);

		return str_replace('/','.', trim($key, '/'));
	}


	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  		 $namespace
	 * @param  array|string  $paths
	 * @return void
	 */
	public function addNamespace($namespace, $paths)
	{
		if( is_array($paths) && Arr::isAssoc($paths) ){
			foreach ($paths as $key => $path) {
				Arr::pushUnique($this->namespaces, $namespace.'.'.$key, ...(array) $path);
				// if(is_array($path))
				// 	Arr::pushAll($this->namespaces, $namespace.'.'.$key, $path, true);
				// else
				// 	Arr::push($this->namespaces, $namespace.'.'.$key, $path, true);
			}
		}
		else{
			Arr::pushUnique($this->namespaces, $namespace, ...(array) $paths);
			// Arr::pushAll($this->namespaces, $namespace, (array) $paths, true);
		}
	}


	/**
	 * Determine if the given namespace is registered.
	 *
	 * @param  string  $namespace
	 *
	 * @return bool
	 */
	public function hasNamespace($namespace)
	{
		return isset($this->namespaces[$namespace]);
	}
}
