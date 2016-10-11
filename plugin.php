<?php
/*
 *Plugin Name: TeaPress
 *Plugin URI: https://teapress.com/
 *Description: TeaPress
 *Version: 0.1.0
 *Author: Kelsam
 *Author URI: https://kelsam/
 *Text Domain: teapress
*/

require_once __DIR__.'/vendor/autoload.php';

if(defined('DOING_UNIT_TESTS') && DOING_UNIT_TESTS){
	require_once DOING_UNIT_TESTS .'/init.php';
}
