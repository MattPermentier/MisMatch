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

/*
 * Thema Uitlezen
 * 	* Palette
 * 	* Default Kleuren
 * 	* Element Kleuren
 * 	* Block Kleuren
 */

function getKey($arr, $key) {
	$keys = explode("@", $key);
	$a = $arr;
	foreach ($keys as $k) {
		if (array_key_exists($k, $a)) {
			$a = $a[$k];
		} else {
			return [];
		}
	}
	return $a;
}

function setIfExists($srcArr, &$destArr, $akey, $key) {
	$v = getKey($srcArr, $key);
	if ($v != []) {
		$destArr[$akey] = $v;
	}
}

class Theme {
	// definitions of CSS variables
	// flattened arrays of global styles
	// blocks->core/title->link->:hover => "core/title@link@hover"
	// easy-er color comparison
	public $textColors;
	public $backgroundColors;

	public function __construct($txt, $bg) {
		$this->textColors = $txt;
		$this->backgroundColors	= $bg;
	}

	public static function get() {
		$styles = wp_get_global_styles();
		$txt = ["default" => $styles["color"]["text"]];
		$bg = ["default" => $styles["color"]["text"]];
		// array keys to look for within elements
		$eKeys = [
			["", ""],
			[":hover", "@hover"],
			[":active", "@active"],
			[":focus", "@focus"],
			[":visited", "@visited"],
		];

		foreach (getKey($styles, "elements") as $el => $eValue) {
			foreach ($eKeys as $eKey) {
				$s = $eKey[0] . "@color@";
				$k = $el . $eKey[1];
				setIfExists($eValue, $txt, $k, $s . "text");
				setIfExists($eValue, $bg, $k, $s . "background");
			}
		}

		foreach (getKey($styles, "blocks") as $block => $bValue) {
			setIfExists($bValue, $txt, $block, "color@text");
			setIfExists($bValue, $bg, $block, "color@background");
			foreach (getKey($bValue, "elements") as $el => $eValue) {
				foreach ($eKeys as $eKey) {
					$s = $eKey[0] . "@color@";
					// identifier for blocks
					$k = "~" . $block . "!@" . $el . $eKey[1];
					setIfExists($eValue, $txt, $k, $s . "text");
					setIfExists($eValue, $bg, $k, $s . "background");
				}
			}
		}
		return new Theme($txt, $bg);
	}

	public static function make($theme) {
	}
}

function dumpTheme() {
	$theme = Theme::get();
	var_dump($theme);
}

add_action("admin_notices", "dumpTheme");

