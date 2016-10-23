<?php

namespace TeaPress\Filesystem;

use Closure;
use ErrorException;
use FilesystemIterator;
use TeaPress\Utils\Str;
use TeaPress\Utils\Arr;
use TeaPress\Filesystem\Finder;
use Illuminate\Support\Traits\Macroable;
use TeaPress\Contracts\Filesystem\Filesystem as Contract;
use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;

class Filesystem extends IlluminateFilesystem implements Contract
{
	// use Macroable;

	/**
	 * Determine if a file or directory exists.
	 *
	 * @param  string  $path
	 * @return bool
	 */
	public function exists($path)
	{
		return file_exists($path);
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param  string  $path
	 * @return string
	 *
	 * @throws \TeaPress\Filesystem\FileNotFoundException
	 */
	public function get($path)
	{
		if ($this->isFile($path)) {
			return file_get_contents($path);
		}

		throw new FileNotFoundException("File does not exist at path {$path}");
	}


	/**
	 * Get the returned value of a file.
	 *
	 * @param  string  $path
	 * @param  array  $data
	 * @return mixed
	 *
	 * @throws \TeaPress\Filesystem\FileNotFoundException
	 */
	public function getRequire($path, $data = [])
	{
		return $this->require($path, $data);
	}



	/**
	 * Require the given file while exposing the provided data.
	 * If the file does not exist, the $default will be returned if provided. Otherwise an error will be thrown.
	 *
	 * @param  string  $file
	 * @param  array  $data
	 * @param  mixed  $default
	 *
	 * @throws \TeaPress\Filesystem\FileNotFoundException
	 * @return mixed
	 */
	public function requireOnce($file, $data = [], $default = NOTHING)
	{
		return $this->requireScript($file, $data, true, $default);
	}

	/**
	 * Require the given file while exposing the provided data.
	 * If the file does not exist, the $default will be returned if provided. Otherwise an error will be thrown.
	 *
	 * @param  string  $file
	 * @param  array  $data
	 * @param  mixed  $default
	 *
	 * @throws \TeaPress\Filesystem\FileNotFoundException
	 * @return mixed
	 */
	public function require($file, $data = [], $default = NOTHING)
	{
		return $this->requireScript($file, $data, false, $default);
	}

	/**
	 * Require all .php files from the given paths while exposing the provided data.
	 * If a file does not exist or a path is broken, a FileNotFoundException exception is thrown.
	 *
	 * @param  string|array  $paths
	 * @param  array  $data
	 * @param  bool  $once
	 *
	 * @throws \TeaPress\Filesystem\FileNotFoundException
	 *
	 * @return array
	 */
	public function requireAll($paths, $data = [], $once = false)
	{
		return $this->requireScriptsFromPaths($paths, $data, $once);
	}

	/**
	 * Require all .php files once (require_once) from the given paths while exposing the provided data.
	 * If a file does not exist or a path is broken, a FileNotFoundException exception is thrown.
	 *
	 * @param  string|array  $paths
	 * @param  array  $data
	 *
	 * @throws \TeaPress\Filesystem\FileNotFoundException
	 *
	 * @return array
	 */
	public function requireAllOnce($paths, $data = [])
	{
		return $this->requireScriptsFromPaths($paths, $data, true);
	}

	/**
	 * Require the given file while exposing the provided data.
	 * If the file does not exist, the $default will be returned if provided. Otherwise an error will be thrown.
	 *
	 * @param  string $__script
	 * @param  array  $__data
	 * @param  bool  $__once
	 * @param  mixed  $__default
	 *
	 * @throws \TeaPress\Filesystem\FileNotFoundException
	 *
	 * @return mixed
	 */
	protected function requireScript($__script, $__data = [], $__once=false, $__default=NOTHING)
	{

		if( !$this->isFile($__script) ){

			if($__default === NOTHING){
				throw new FileNotFoundException("Failed requiring file(s). Path '{$__script}' is not a valid file.");
			}
			else{
				if($__default instanceof Closure)
					return call_user_func_array($__default, (array) $__data);
				else
					return $__default;
			}
		}

		extract( (array) $__data );
		return $__once ? require_once($__script) : require($__script);
	}

	/**
	 * Require all .php files from the given paths while exposing the provided data.
	 * If a file does not exist or a path is broken, a FileNotFoundException exception is thrown.
	 *
	 * @param  string $___paths
	 * @param  array  $___data
	 * @param  bool  $___once
	 *
	 * @throws \TeaPress\Filesystem\FileNotFoundException
	 *
	 * @return array
	 */
	protected function requireScriptsFromPaths($___paths, $___data = [], $___once=false)
	{
		extract( (array) $___data );

		$___results = [];

		foreach ((array) $___paths as $___i => $___path) {

			$___files = $this->isDirectory($___path)
					? $this->files($___path, true, ['*.php', '*.html']) : [$___path];

			foreach ( $___files as $___file) {

				if(!$this->isFile($___file))
					throw new FileNotFoundException("Failed requiring file(s). Path '{$___file}' is not a valid file or directory.");

				$___key = $this->parsePathToKey( $___file, ($___file === $___path ? dirname($___path) : $___path ) );

				Arr::set($___results[$___i], $___key, ($___once ? require_once( $___file ) : require( $___file )) );
			}
		}

		return is_array($___paths) ? $___results : Arr::first($___results, null, []);
	}


	protected function parsePathToKey($path, $base = '')
	{
		$slash = DIRECTORY_SEPARATOR;

		$path = trim($path, $slash);
		$base = trim($base, $slash);

		$key = strtolower(substr($path, Str::length($base)));

		foreach (['.php', '.html'] as $ext) {
			if( Str::endsWith($key, $ext) ){
				$key = substr($key, 0,  (-1 * Str::length($ext) ) );
			}
		}

		return str_replace($slash,'.', trim($key, $slash));
	}



	/**
	 * Write the contents of a file.
	 *
	 * @param  string  $path
	 * @param  string  $contents
	 * @param  bool  $lock
	 * @return int
	 */
	public function put($path, $contents, $lock = false)
	{
		return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
	}

	/**
	 * Prepend to a file.
	 *
	 * @param  string  $path
	 * @param  string  $data
	 * @return int
	 */
	public function prepend($path, $data)
	{
		if ($this->exists($path)) {
			return $this->put($path, $data.$this->get($path));
		}

		return $this->put($path, $data);
	}

	/**
	 * Append to a file.
	 *
	 * @param  string  $path
	 * @param  string  $data
	 * @return int
	 */
	public function append($path, $data)
	{
		return file_put_contents($path, $data, FILE_APPEND);
	}

	/**
	 * Delete the file at a given path.
	 *
	 * @param  string|array  $paths
	 * @return bool
	 */
	public function delete($paths)
	{
		$paths = is_array($paths) ? $paths : func_get_args();

		$success = true;

		foreach ($paths as $path) {
			try {
				if (! @unlink($path)) {
					$success = false;
				}
			} catch (ErrorException $e) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Move a file to a new location.
	 *
	 * @param  string  $path
	 * @param  string  $target
	 * @return bool
	 */
	public function move($path, $target)
	{
		return rename($path, $target);
	}

	/**
	 * Copy a file to a new location.
	 *
	 * @param  string  $path
	 * @param  string  $target
	 * @return bool
	 */
	public function copy($path, $target)
	{
		return copy($path, $target);
	}

	/**
	 * Extract the file name from a file path.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function name($path)
	{
		return pathinfo($path, PATHINFO_FILENAME);
	}

	/**
	 * Extract the file extension from a file path.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function extension($path)
	{
		return pathinfo($path, PATHINFO_EXTENSION);
	}

	/**
	 * Get the file type of a given file.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function type($path)
	{
		return filetype($path);
	}

	/**
	 * Get the mime-type of a given file.
	 *
	 * @param  string  $path
	 * @return string|false
	 */
	public function mimeType($path)
	{
		return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
	}

	/**
	 * Get the file size of a given file.
	 *
	 * @param  string  $path
	 * @return int
	 */
	public function size($path)
	{
		return filesize($path);
	}

	/**
	 * Get the file's last modification time.
	 *
	 * @param  string  $path
	 * @return int
	 */
	public function lastModified($path)
	{
		return filemtime($path);
	}

	/**
	 * Determine if the given path is a directory.
	 *
	 * @param  string  $directory
	 * @return bool
	 */
	public function isDirectory($directory)
	{
		return is_dir($directory);
	}

	/**
	 * Determine if the given path is writable.
	 *
	 * @param  string  $path
	 * @return bool
	 */
	public function isWritable($path)
	{
		return is_writable($path);
	}

	/**
	 * Determine if the given path is a file.
	 *
	 * @param  string  $file
	 * @return bool
	 */
	public function isFile($file)
	{
		return is_file($file);
	}

	/**
	 * Find path names matching a given pattern.
	 *
	 * @param  string  $pattern
	 * @param  int     $flags
	 * @return array
	 */
	public function glob($pattern, $flags = 0)
	{
		return glob($pattern, $flags);
	}

	/**
	 * Get an array of all files in a directory.
	 *
	 * Provide a boolean value (true/false) for recursive to turn it on/off,
	 * an integer or string expression to specify the search depth.
	 *
	 * You can pass filters to customize the search results.
	 *
	 * For example :
	 * 		$filters = ['name' => '*.php', 'path' => ['src', 'tests' ] ]
	 * 	Searches for files with a .php extension whose path consists of a
	 * 	directory/sub-directory named 'src' or 'tests'.
	 *
	 * You can provide an array of filters with string keys as the finder's method to be
	 * called with the value passed as a parameter.
	 *
	 * If a string or integer indexed array is provided, the finder's 'name' method is used.
	 * So all the following will return the same results
	 * 		$filters = '*.txt'
	 * 		$filters = ['*.txt']
	 * 		$filters = ['name' => '*.txt']
	 *
	 * Valid filter methods methods are basically all methods in
	 * Symfony\Component\Finder\Finder and \TeaPress\Filesystem\Finder
	 * classes that return a Finder instance.
	 *
	 * You can specify a string (for one) or an array of snake_cased properties to retrieve
	 * from from the file info (SplFileInfo) object. Though SplFileInfo has no public properties,
	 * the properties will be retrieved by calling the respective getter method(s).
	 * If properties is false (bool) the entire SplFileInfo objects are returned.
	 *
	 * 		How it works:
	 * 			Property			Method Called
	 * 			basename			: SplFileInfo::getBasename();
	 * 			real_path			: SplFileInfo::getRealPath();
	 * 			is_dir				: SplFileInfo::isDir();
	 *
	 * 		You can refer to \TeaPress\Filesystem\Finder for more info
	 *
	 *
	 * @param  string  				$directory
	 * @param  bool|int|string		$recursive 		true/false = on/off. Int/string for a search depth.
	 * @param  string|array|null	$filters 		Check above.
	 * @param  string|array|bool	$properties		The properties to retrieve.
	 * @param  int 					$limit 			Limit the number of files retrieved.
	 *
	 * @return array
	 *
	 */
	public function files($directory, $recursive = false, $filters = null, $properties = null, $limit = null)
	{
		$depth = !is_bool($recursive) ? $recursive : ($recursive ? null : 0);

		$finder = $this->findFiles($directory, $depth);

		if(is_string($filters)){
			$filters = ['name' => $filters];
		}

		foreach ( (array) $filters as $method => $patterns) {

			$method = Str::camel( is_string($method) ? $method : 'name');

			foreach ((array) $patterns as $pattern) {
				$finder->{$method}($pattern);
			}
		}

		$properties = $properties === false ? null : (is_null($properties) ? 'pathname' : $properties);

		return $finder->get($properties, $limit);
	}

	/**
	 * Get all of the files from the given directory (recursive).
	 * You can specify the properties to retrieve.
	 * Returns entire SplFileInfo object if properties = null.
	 *
	 * @param  string|null 			$directory
	 * @param  string|array|null	$properties
	 *
	 * @return array
	 */
	public function allFiles($directory, $properties = null)
	{
		return $this->findFiles($directory)->get($properties);
	}

	/**
	 * Get an iterator for all of the files in the given directory.
	 *
	 * @param  string  $directory
	 * @param  int|null  $depth
	 *
	 * @return \TeaPress\Filesystem\Finder
	 */
	public function findFiles($directory, $depth = null)
	{
		if(is_null($depth))
			return $this->finder()->files()->in($directory);
		else
			return $this->finder()->files()->in($directory)->depth($depth);
	}

	/**
	 * Get all of the directories within a given directory.
	 *
	 * Provide a boolean value (true/false) for recursive to turn it on/off,
	 * an integer or string expression to specify the search depth.
	 *
	 * You can pass filters to customize the search results.
	 *
	 * For example :
	 * 		$filters = ['name' => 'prefix_*', 'path' => ['src', 'tests' ] ]
	 * 	Searches for directories whose names start with a 'prefix_' and whose path consists of a
	 * 	directory/sub-directory named 'src' or 'tests'.
	 *
	 * You can provide an array of filters with string keys as the finder's method to be
	 * called with the value passed as a parameter.
	 *
	 * If a string or integer indexed array is provided, the finder's 'name' method is used.
	 * So all the following will return the same results
	 * 		$filters = '*_suffix'
	 * 		$filters = ['*_suffix']
	 * 		$filters = ['name' => '*_suffix']
	 *
	 * Valid filter methods methods are basically all methods in
	 * Symfony\Component\Finder\Finder and \TeaPress\Filesystem\Finder
	 * classes that return a Finder instance.
	 *
	 * You can specify a string (for one) or an array of snake_cased properties to retrieve
	 * from from the file info (SplFileInfo) object. Though SplFileInfo has no public properties,
	 * the properties will be retrieved by calling the respective getter method(s).
	 * If properties is false (bool) the entire SplFileInfo objects are returned.
	 *
	 * 		How it works:
	 * 			Property			Method Called
	 * 			basename			: SplFileInfo::getBasename();
	 * 			real_path			: SplFileInfo::getRealPath();
	 * 			is_dir				: SplFileInfo::isDir();
	 *
	 * 		You can refer to \TeaPress\Filesystem\Finder for more info
	 *
	 *
	 * @param  string  				$directory
	 * @param  bool|int|string		$recursive 		true/false = on/off. Int/string for a search depth.
	 * @param  string|array|null	$filters 		Check above.
	 * @param  string|array|bool	$properties		The properties to retrieve.
	 * @param  int 					$limit 			Limit the number of directories retrieved.
	 *
	 * @return array
	 */
	public function directories($directory, $recursive = false, $filters = null, $properties = null, $limit = null)
	{
		$depth = !is_bool($recursive) ? $recursive : ($recursive ? null : 0);

		$finder = $this->findDirs($directory, $depth);

		if(is_string($filters)){
			$filters = ['name' => $filters];
		}

		foreach ( (array) $filters as $method => $patterns) {

			$method = Str::camel( is_string($method) ? $method : 'name');

			foreach ((array) $patterns as $pattern) {
				$finder->{$method}($pattern);
			}
		}

		$properties = $properties === false ? null : (is_null($properties) ? 'pathname' : $properties);

		return $finder->get($properties, $limit);
	}



	/**
	 * Get all (recursive) of the directories within a given directory.
	 * You can specify the properties to retrieve.
	 * Returns entire SplFileInfo object if properties = null.
	 *
	 * @param  string|null 			$directory
	 * @param  string|array|null	$properties
	 *
	 * @return array
	 */
	public function allDirs($directory, $properties = null)
	{
		return $this->allDirectories($directory, $properties);
	}

	/**
	 * Get all (recursive) of the directories within a given directory.
	 * You can specify the properties to retrieve.
	 * Returns entire SplFileInfo object if properties = null.
	 *
	 * @param  string|null 			$directory
	 * @param  string|array|null	$properties
	 *
	 * @return array
	 */
	public function allDirectories($directory, $properties = null)
	{
		return $this->findDirs($directory)->get($properties);
	}

	/**
	 * Get an iterator for all of the directories in the given path.
	 *
	 * @param  string  	$path
	 * @param  int 		$depth
	 *
	 * @return \TeaPress\Filesystem\Finder
	 */
	public function findDirs($path, $depth = null)
	{
		if(is_null($depth))
			return $this->finder()->in($path)->directories();
		else
			return $this->finder()->in($path)->directories()->depth($depth);
	}

	/**
	 * Create a directory.
	 *
	 * @param  string  $path
	 * @param  int     $mode
	 * @param  bool    $recursive
	 * @param  bool    $force
	 * @return bool
	 */
	public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
	{
		if ($force) {
			return @mkdir($path, $mode, $recursive);
		}

		return mkdir($path, $mode, $recursive);
	}

	/**
	 * Copy a directory from one location to another.
	 *
	 * @param  string  $directory
	 * @param  string  $destination
	 * @param  int     $options
	 * @return bool
	 */
	public function copyDirectory($directory, $destination, $options = null)
	{
		if (! $this->isDirectory($directory)) {
			return false;
		}

		$options = $options ?: FilesystemIterator::SKIP_DOTS;

		// If the destination directory does not actually exist, we will go ahead and
		// create it recursively, which just gets the destination prepared to copy
		// the files over. Once we make the directory we'll proceed the copying.
		if (! $this->isDirectory($destination)) {
			$this->makeDirectory($destination, 0777, true);
		}

		$items = new FilesystemIterator($directory, $options);

		foreach ($items as $item) {
			// As we spin through items, we will check to see if the current file is actually
			// a directory or a file. When it is actually a directory we will need to call
			// back into this function recursively to keep copying these nested folders.
			$target = $destination.'/'.$item->getBasename();

			if ($item->isDir()) {
				$path = $item->getPathname();

				if (! $this->copyDirectory($path, $target, $options)) {
					return false;
				}
			}

			// If the current items is just a regular file, we will just copy this to the new
			// location and keep looping. If for some reason the copy fails we'll bail out
			// and return false, so the developer is aware that the copy process failed.
			else {
				if (! $this->copy($item->getPathname(), $target)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Recursively delete a directory.
	 *
	 * The directory itself may be optionally preserved.
	 *
	 * @param  string  $directory
	 * @param  bool    $preserve
	 * @return bool
	 */
	public function deleteDirectory($directory, $preserve = false)
	{
		if (! $this->isDirectory($directory)) {
			return false;
		}

		$items = new FilesystemIterator($directory);

		foreach ($items as $item) {
			// If the item is a directory, we can just recurse into the function and
			// delete that sub-directory otherwise we'll just delete the file and
			// keep iterating through each file until the directory is cleaned.
			if ($item->isDir() && ! $item->isLink()) {
				$this->deleteDirectory($item->getPathname());
			}

			// If the item is just a file, we can go ahead and delete it since we're
			// just looping through and waxing all of the files in this directory
			// and calling directories recursively, so we delete the real path.
			else {
				$this->delete($item->getPathname());
			}
		}

		if (! $preserve) {
			@rmdir($directory);
		}

		return true;
	}

	/**
	 * Empty the specified directory of all files and folders.
	 *
	 * @param  string  $directory
	 * @return bool
	 */
	public function cleanDirectory($directory)
	{
		return $this->deleteDirectory($directory, true);
	}

	/**
	 * Create a Finder instance
	 *
	 * @throws \TeaPress\Filesystem\Finder
	 */
	public function finder()
	{
		return Finder::create();
	}
}
