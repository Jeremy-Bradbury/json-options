<?php
/**
 * @package json-options
 * @version 0.0.1
 */
/*
Plugin Name: JSON Options
Plugin URI: http://wordpress.org/extend/plugins/json-options/
Description: Import and Export Wordpress Options to JSON with filters
Author: JeremyJanrain
Version: 0.0.1
Author URI: http://www.jeremybradbury.com/
*/
class jsonOptions {
	public static $name = 'json_options';

	function init(){
		if ( is_admin() ) {
	        require_once plugin_dir_path( __FILE__ ) . "/json-options-admin.php";
	        $admin = new jsonOptionsAdmin();
		}
	}
	
	/**
	 * Method bound to register_activation_hook.
	 */
	function activate() {
		require_once plugin_dir_path( __FILE__ ) . "/json-options-admin.php";
	    $admin = new jsonOptionsAdmin();
		$admin->activate();
	}
}
add_action('init', jsonOptions::$name . '_init_wrap');
function json_options_init_wrap() {
	$jsonOptions = new jsonOptions;
	$jsonOptions->init();
}

?>
