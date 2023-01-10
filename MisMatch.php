<?php
/**
 * @package Miss_Match
 * version 0.1.0
 */
/*
Plugin Name: MissMatch
Plugin URI: https://github.com/MattPermentier/MisMatch
Description: Check kleurencontrast op jouw WordPress website.
Author: MisMatch
Version: 0.0.1
License: CC0
*/

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
}

add_action("admin_notices", "misMatch");

