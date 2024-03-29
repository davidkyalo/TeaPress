<?php
namespace TeaPress\Filesystem;

use SplFileInfo;
use TeaPress\Utils\Str;
use TeaPress\Contracts\Utils\Arrayable;
use Symfony\Component\Finder\Finder as SymfonyFinder;

class Finder extends SymfonyFinder implements Arrayable
{

	protected $filePropertyAliases = [
			'name' => 'basename',
			'dirname' => 'path'
		];

	/**
	* Returns an array of all the items in the collection.
	*
	* @return array
	*/
	public function all()
	{
		return iterator_to_array($this->getIterator(), false);
	}

	/**
	* Returns an array of the items in the collection.
	*
	* @param array|string|null $properties
	* @param int|null $limit
	*
	* @return array
	*/
	public function get($properties = null, $limit = null)
	{
		$files = [];
		$count = 0;

		foreach ($this->getIterator() as $name => $file) {

			if(!is_null($limit) && $count >= (int) $limit)
				break;

			$files[] = $this->extractFileProperties($file, $properties);
			++$count;

		}

		return $files;
	}

	/**
	* Returns the first items in the collection.
	*
	* @param array|string $properties
	*
	* @return SplFileInfo|array|null|mixed
	*/
	public function first($properties = null)
	{
		return Arr::first($this->get($properties, 1));
	}

	/**
	* Returns the last items in the collection.
	*
	* @param array|string $properties
	*
	* @return SplFileInfo|array|null|mixed
	*/
	public function last($properties = null)
	{
		$file = Arr::last($this->all());
		return !is_null($file) ? $this->extractFileProperties($file, $properties) : $file;
	}


	/**
	* Returns an array of the path names in the collection.
	*
	* @return array
	*/
	public function toArray()
	{
		return $this->get(['name', 'filename', 'type', 'path', 'pathname', 'real_path']);
	}

	/**
	* Extracts the specified properties from a file info object.
	*
	* @param \SplFileInfo $file
	* @param array|string $properties
	*
	* @return SplFileInfo|array|null|mixed
	*/
	protected function extractFileProperties(SplFileInfo $file, $properties = null)
	{
		if(is_null($properties)){
			return $file;
		}

		if(is_string($properties)){
			return $this->getFilePropertyValue($file, $properties);
		}

		$results = [];
		foreach ((array) $properties as $property) {
			$results[$property] = $this->getFilePropertyValue($file, $property);
		}

		return $results;
	}


	/**
	* Extracts the specified property from a file info object.
	*
	* @param \SplFileInfo $file
	* @param string $property
	*
	* @return mixed
	*/
	protected function getFilePropertyValue(SplFileInfo $file, $property)
	{
		$property = $this->getPropertyName($property);

		$method = Str::startsWith($property, 'is_') ? Str::camel($property) :Str::camel( "get_{$property}" );

		return $file->{$method}();
	}

	/**
	* Get the real file info property name.
	*
	* @param string $alias
	*
	* @return string
	*/
	protected function getPropertyName($alias)
	{
		return isset($this->filePropertyAliases[$alias]) ? $this->filePropertyAliases[$alias] : $alias;
	}


}