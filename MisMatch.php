<?php
/**
 * @package Miss_Match
 * version 0.1.0
 */
/*
Plugin Name: MissMatch
Plugin URI: https://github.com/MattPermentier/MisMatch
Description: Fix kleurencontrast op jouw WordPress website.
Author: MissMatch
Version: 0.0.1
License: CC0
if( ! defined( 'ABSPATH') ) {
	exit;
}
*/

include_once('src/metabox.php');

function myprefix_enqueue_assets() {
	echo (
	"<script>
		const ajax_url = '" . admin_url("admin-ajax.php") . "';
	</script>");
	wp_enqueue_script(
		'myprefix-gutenberg-sidebar',
		plugins_url( 'build/index.js', __FILE__ ),
		array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data' )
	);
}
add_action( 'enqueue_block_editor_assets', 'myprefix_enqueue_assets' );

require_once("color.php");
require_once("read-theme.php");
require_once("write-theme.php");

function misMatch() {
	$styles = readStyles();
	saveStyles($styles);
	$sheet = makeStylesheet($styles);
	saveStylesheet($sheet);
	print_r($styles);
	echo "<br><br>";
	setThemeAttribute($styles, "body", "color", "ff0000");
	print_r($styles);
	echo "<br>";
}

function changeTheme() {
	$ssh = readStyles();
	print_r($ssh);
	echo "<br><br>";
	setThemeAttribute($ssh, "body", "background-color", "#ff0000");
	print_r($ssh);
	saveStyles($ssh);
	/*
	print_r($ssh);
	$ssheet = makeStylesheet($ssh);
	echo "<br><br>";
	print_r($ssheet);
	saveStylesheet($ssheet);
	*/
	echo "SUCCES";
	wp_die("hi", 200);
}

function suggestJson() {
	echo wp_json_encode(getSuggestions());
	wp_die("", 200);
}

add_action('wp_ajax_missmatch', 'changeTheme');
add_action('wp_ajax_nopriv_missmatch', 'changeTheme');

add_action('wp_ajax_get_suggestions', 'suggestJson');

add_action("admin_notices", "misMatch");
