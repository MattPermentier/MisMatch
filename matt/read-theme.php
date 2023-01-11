<?php
/* theme.json relevant elements
 *
 * settings.colors.palette - defines CSS color variables
 *	* e.g. one of the array elements has as slug: "base". 
 *	* This defines var(wp-color-base)
 *
 * settings.styles.color
 *	* Defines default text & background colors
 *
 * settings.styles.elements
 *	* Some of these elements have a "color" attribute
 *	* The color attribute defines overrides of default colors
*/ 

class Theme {
	public static function get() {
		$themeDir = get_template_directory();
		$themePath = $themeDir . "/theme.json";
		if (!file_exists($themePath)) {
			echo "can't find theme.json";
			return;
		};
		$themeRaw = file_get_contents($themePath);
		echo "mogus";
		return json_decode($themeRaw);
	}
}

