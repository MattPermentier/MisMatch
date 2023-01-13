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
	echo ("<script>
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
	setThemeAttribute($styles, "body", "color", "ff0000");
}

function changeTheme() {
	$css = [];
	$suggs = getSuggestions();
	foreach ($suggs as $selector => $values) {
		foreach ($values as $type => $colors) {
			$c = $colors[0];
			if ($type == "txt") {
				setThemeAttribute($css, $selector, "color", $c->toHex());
			} else if ($type == "bg") {
				setThemeAttribute($css, $selector, "background-color", $c->toHex());
			} else {
				setThemeAttribute($css, $selector, "color", $c[0]->toHex());
				setThemeAttribute($css, $selector, "background-color", $c[1]->toHex());
			}
		}
	}
	saveStyles($css);
	wp_die("hi", 200);
}

function suggestJson() {
	$suggs = getSuggestions();
	foreach ($suggs as $selector => $values) {
		foreach ($values as $type => $colors) {
			foreach ($colors as $ci => $c) {
				if ($type == "pair") {
					foreach ($c as $i => $cc) {
						$suggs[$selector][$type][$ci][$i] = $cc->toHex();
					}
				} else {
					$suggs[$selector][$type][$ci] = $c->toHex();
				}
			}
		}
	}
	wp_die("", 200);
}

function adaptTheme() {
	$css = readStyles();
	if (!array_key_exists("suggestions", $_REQUEST)) {
		wp_die("No suggestions", 400);
	}
	$suggestions = getSuggestions();
	foreach (explode(" ", $_REQUEST["suggestions"]) as $sug) {
		$sug = explode("_", $sug);
		$selector = $sug[0];
		$colorType = $sug[1];
		$color = $sug[2];
		if ($colorType == "pair") {
			$c = $suggestions[$selector][$colorType][$color];
			$txtc = $c[0]->toHex();
			$bgc = $c[1]->toHex();
			setThemeAttribute($css, $selector, "color", $txtc);
			setThemeAttribute($css, $selector, "background-color", $bgc);
		} else {
			$c = $suggestions[$selector][$colorType][$color]->toHex();
			setThemeAttribute($css, $selector, $colorType, $c);
		}
	}
	saveStyles($css);
	wp_die("", 200);
}

add_action('wp_ajax_missmatch', 'changeTheme');
add_action('wp_ajax_nopriv_missmatch', 'changeTheme');

add_action('wp_ajax_get_suggestions', 'suggestJson');
add_action('wp_ajax_nopriv_get_suggestions', 'suggestJson');

add_action('wp_ajax_give_suggestion', 'adaptTheme');
add_action('wp_ajax_nopriv_give_suggestion', 'adaptTheme');

add_action("admin_notices", "misMatch");
