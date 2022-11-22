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

function misMatch() {
	$theme = Theme::get();
	$bg = $theme->styles->color->background;
	$fg = $theme->styles->color->text;
	echo "succesful theme.json read! Text: (" . $fg . ") Background: (" . $bg . ")";
}

add_action("admin_notices", "misMatch");

