<?php

if(!function_exists('is_unit_testing')):

function is_unit_testing(){
	return php_sapi_name() == 'cli' && defined('DOING_UNIT_TESTS') ? (bool) DOING_UNIT_TESTS : false;
}

endif;

if(!is_unit_testing())
	return;

require_once __DIR__ .'/src/run.php';