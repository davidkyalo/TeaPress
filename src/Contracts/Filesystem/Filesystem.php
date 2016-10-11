<?php
namespace TeaPress\Contracts\Filesystem;


interface Filesystem
{

	/**
	 * Determine if a file exists.
	 *
	 * @param  string  $path
	 * @return bool
	 */
	public function exists($path);

	/**
	 * Get the contents of a file.
	 *
	 * @param  string  $path
	 * @return string
	 *
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	public function get($path);

	/**
	 * Write the contents of a file.
	 *
	 * @param  string  $path
	 * @param  string|resource  $contents
	 * @param  string  $visibility
	 * @return bool
	 */
	public function put($path, $contents, $visibility = null);

	/**
	 * Prepend to a file.
	 *
	 * @param  string  $path
	 * @param  string  $data
	 * @return int
	 */
	public function prepend($path, $data);

	/**
	 * Append to a file.
	 *
	 * @param  string  $path
	 * @param  string  $data
	 * @return int
	 */
	public function append($path, $data);

	/**
	 * Delete the file at a given path.
	 *
	 * @param  string|array  $paths
	 * @return bool
	 */
	public function delete($paths);

	/**
	 * Copy a file to a new location.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return bool
	 */
	public function copy($from, $to);

	/**
	 * Move a file to a new location.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return bool
	 */
	public function move($from, $to);

	/**
	 * Get the file size of a given file.
	 *
	 * @param  string  $path
	 * @return int
	 */
	public function size($path);

	/**
	 * Get the file's last modification time.
	 *
	 * @param  string  $path
	 * @return int
	 */
	public function lastModified($path);

	/**
	 * Get an array of all files in a directory.
	 *
	 * @param  string  $directory
	 * @return array
	 */
	public function files($directory);

	/**
	 * Get all of the files from the given directory (recursive).
	 *
	 * @param  string  $directory
	 * @return array
	 */
	public function allFiles($directory);


	/**
	 * Get all of the directories within a given directory.
	 *
	 * @param  string  $directory
	 * @return array
	 */
	public function directories($directory);

	/**
	 * Get all (recursive) of the directories within a given directory.
	 *
	 * @param  string|null  $directory
	 * @return array
	 */
	public function allDirectories($directory);

	/**
	 * Create a directory.
	 *
	 * @param  string  $path
	 * @return bool
	 */
	public function makeDirectory($path);

	/**
	 * Recursively delete a directory.
	 *
	 * @param  string  $directory
	 * @return bool
	 */
	public function deleteDirectory($directory);
	/**
	 * Require the given file while exposing the provided data.
	 * If the file does not exist, the $default will be returned if provided. Otherwise an error will be thrown.
	 *
	 * @param  string  $file
	 * @param  array  $data
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function requireOnce($file, $data = [], $default = null);


	/**
	 * Require the given file while exposing the provided data.
	 * If the file does not exist, the $default will be returned if provided. Otherwise an error will be thrown.
	 *
	 * @param  string  $file
	 * @param  array  $data
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function require($file, $data = [], $default = null);


	/**
	 * Get an iterator for all of the files in the given directory.
	 *
	 * @param  string  $directory
	 * @param  int|null  $depth
	 * @return array
	 */
	public function findFiles($directory, $depth = null);

	/**
	 * Get an iterator for all of the directories in the given path.
	 *
	 * @param  string  	$path
	 * @param  int 		$depth
	 * @return array
	 */
	public function findDirs($path, $depth = null);


}