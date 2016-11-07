<?php

if(!defined('NOTHING'))
	define('NOTHING', '<---____I_JUST_MEAN_NOTHING____--->');


if(!defined('EMPTY'))
	define('EMPTY', '<---____AM_EMPTY____--->');


require_once __DIR__ . '/inc/autoload.php';
// require_once __DIR__ . '/Html/index.php';
use TeaPress\Http\Request;

add_action('init', function(){
	if(defined('DOING_UNIT_TESTS') && DOING_UNIT_TESTS)
		return;

	echo "<pre>\n\n";
	echo "\n\t\t\t\t";
	$request = Request::capture();
	$request->setHomePath(home_url());
	echo "PARSE PATH : ". parse_url(home_url(), PHP_URL_PATH);
	echo "\n\t\t\t\t";
	echo "URI        : ".$request->getUri();
	echo "\n\t\t\t\t";
	echo "REQ URI    : ".$request->getRequestUri();
	echo "\n\t\t\t\t";
	echo "FULL URI   : ".$request->fullPath();
	echo "\n\t\t\t\t";
	echo "ROOT       : ".$request->root();
	echo "\n\t\t\t\t";
	echo "HOST       : ".$request->getSchemeAndHttpHost();
	echo "\n\t\t\t\t";
	echo "PATH_INFO  : ".$request->getPathInfo();
	echo "\n\t\t\t\t";
	echo "PATH       : ".$request->path();
	echo "\n\t\t\t\t";
	echo "BASE_PATH  : ".$request->getBasePath();
	echo "\n\t\t\t\t";
	echo "URL        : ".$request->url();
	echo "\n\t\t\t\t";
	echo "FULL_URL   : ".$request->fullUrl();
	echo "\n\t\t\t\t";
	echo "SAMPLE     : ". join_uris($request->url(), 'some/path/', urlencode('var:a=b'));
	echo "\n\t\t\t\t";
	echo "FULL_URL   : ".$request->fullUrl();
	echo "\n\n";

	echo "</pre>";

});