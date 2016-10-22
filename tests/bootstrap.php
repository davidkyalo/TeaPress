<?php

if(defined('ABSPATH'))
	return;

if(!defined('DOING_UNIT_TESTS'))
	define('DOING_UNIT_TESTS', true);

if(!defined('WORDPRESS_ROOT_DIR'))
	define('WORDPRESS_ROOT_DIR', __DIR__ .'/../../wordpress');


spl_autoload_register(function($class)
{
	$class = ltrim($class, '\\');
	if (0 === strpos($class, 'TeaPress\Tests\\')) {
		$basedir = __DIR__;
		$file = '/' . strtr( substr($class, 15), '\\', '/').'.php';
		$path = $basedir . $file;

		if(!file_exists($path))
			$path = $basedir . '/src' . $file;

		if (is_file($path) && is_readable($path)) {
			require_once $path;
			return true;
		}
	}
});

require WORDPRESS_ROOT_DIR .'/wp-load.php';
