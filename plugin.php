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

function custom_rewrite_basic() {
  add_rewrite_rule('^leaf/?', 'index.php?page_id=12', 'top');
}
add_action('init', 'custom_rewrite_basic');