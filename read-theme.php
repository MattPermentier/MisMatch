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
 *	* Palette
 *	* Default Kleuren
 *	* Element Kleuren
 *	* Block Kleuren
 */
require_once("color.php");

$CSS_COLOR_NAMES = json_decode(file_get_contents(
	WP_CONTENT_DIR . "/plugins/MisMatch/color-names.json"),
	true);

function selToCss($sel) {
	$sel = str_replace("core/", ".wp-block-", $sel);
	$sel = str_replace("default", "body", $sel);
	$sel = str_replace("link", "a", $sel);
	return $sel;
}

function selToWp($sel) {
	$sel = str_replace(".wp-block-", "core/", $sel);
	$sel = str_replace("body", "default", $sel);
	$sel = str_replace("a", "link", $sel);
	return $sel;
}

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

function setIfColor($srcArr, &$destArr, $akey, $key) {
	$v = getKey($srcArr, $key);
	if ($v != [] && $v != "inherit") {
		$destArr[$akey] = $v;
	}
}

// slightly modified from stackoverflow answer
// https://stackoverflow.com/questions/3618381/parse-a-css-file-with-php
function parseCSS($css) {
	preg_match_all( '/(.+?)\s?\{\s?(.+?)\s?\}/', $css, $arr);
	$result = array();
	foreach ($arr[0] as $i => $x){
		$selector = trim($arr[1][$i]);
		$rules = explode(';', trim($arr[2][$i]));
		$rules_arr = array();
		foreach ($rules as $strRule){
			if (!empty($strRule)){
				$rule = explode(":", $strRule);
				$k = trim($rule[0]);
				$v = trim($rule[1]);
				$rules_arr[$k] = $v;
			}
		}
		
		$selectors = explode(',', trim($selector));
		foreach ($selectors as $strSel){
			if (array_key_exists($strSel, $result)) {
				foreach ($rules_arr as $rkey => $rvalue) {
					$result[$strSel][$rkey] = $rvalue;
				}
			} else {
				$result[$strSel] = $rules_arr;
			}
		}
	}
	return $result;
}

function getStyle($styles, $selector, $attr) {
	
	return "none";
}

class Theme {
	// definitions of CSS variables
	// flattened arrays of global styles
	// blocks->core/title->link->:hover => "core/title@link@hover"
	// easy-er color comparison
	public $textColors;
	public $backgroundColors;
	public $variables;
	public $css;

	public function __construct($txt, $bg, $vars, $css) {
		$this->textColors = $txt;
		$this->backgroundColors	= $bg;
		$this->variables = $vars;
		$this->css = $css;
	}

	public static function get() {
		$styles = wp_get_global_styles();
		$mm_styles = readStyles();
		$txtc = getStyle($mm_styles, "default", "color");
		$bgc = getStyle($mm_styles, "default", "background-color");
		if (array_key_exists("color", $styles)) {
			if ($txtc == "none") $txtc = $styles["color"]["text"];
			$txt = ["default" => $txtc];
			if ($bgc == "none") $bgc = $styles["color"]["background"];
			$bg = ["default" => $bgc];
		}
		// array keys to look for within elements
		$eKeys = [
			["", ""],
			[":hover", "@hover@"],
			[":active", "@active@"],
			[":focus", "@focus@"],
			[":visited", "@visited@"],
		];

		foreach (getKey($styles, "elements") as $el => $eValue) {
			foreach ($eKeys as $eKey) {
				$s = $eKey[0] . "color@";
				$k = $el . $eKey[1];
				setIfColor($eValue, $txt, $k, $s . "text");
				setIfColor($eValue, $bg, $k, $s . "background");
			}
		}

		foreach (getKey($styles, "blocks") as $block => $bValue) {
			setIfColor($bValue, $txt, "~" . $block, "color@text");
			setIfColor($bValue, $bg, "~" . $block, "color@background");
			foreach (getKey($bValue, "elements") as $el => $eValue) {
				foreach ($eKeys as $eKey) {
					$s = $eKey[0] . "color@";
					// identifier for blocks
					$k = "~" . $block . "@" . $el . $eKey[1];
					setIfColor($eValue, $txt, $k, $s . "text");
					setIfColor($eValue, $bg, $k, $s . "background");
				}
			}
		}

		// used to read all variables
		// can be used later for backwards compatibility with older themes
		$css = parseCSS(wp_get_global_stylesheet());
		$vars = [];

		foreach ($css as $selector => $values) {
			foreach ($values as $attr => $v) {
				if (str_starts_with($attr, "--")) {
					$vars[$attr] = $v;
				}
			}
		}
		return new Theme($txt, $bg, $vars, $css);
	}

	// takes óne array (bg/txt-color) as input (and color string)
	public static function getColor($arr, $s) {
		$comps = explode("@", $s);
		$start = 0;
		$cnt = count($comps);
		for ($i = 0; $i < $cnt; $i++) {
			$colName = implode("@", array_slice($comps, 0, $cnt - $i));
			if (array_key_exists($colName, $arr)) {
				return $arr[$colName];
			}
		}
		if (str_starts_with($comps[0], "~")) {
			$newArr = implode("@", array_slice($comps, 1));
			return Theme::getColor($arr, $newArr);
		}
		return $arr["default"];
	}

	private function getColorValue($c) {
		if (str_starts_with($c, "#")) return $c;
		if (str_starts_with($c, "var:preset|color")) {
			$var = "--wp--preset--color-" . str_replace("|", "-", substr($c, 16));
			if (array_key_exists($var, $this->variables))
				return $this->variables[$var];
		}
		if (str_starts_with($c, "var(")) {
			$var = substr($c, 4, -1);
			if (array_key_exists($var, $this->variables))
				return $this->variables[$var];
		}
		global $CSS_COLOR_NAMES;
		if (array_key_exists($c, $CSS_COLOR_NAMES)) {
			return $CSS_COLOR_NAMES[$c];
		}
	}

	private function checkColorContrast($txtRaw, $bgRaw) {
		$txt = $this->getColorValue($txtRaw);
		$bg = $this->getColorValue($bgRaw);
		$txtCol = Color::fromHex($txt);
		$bgCol = Color::fromHex($bg);
		return Color::contrast($txtCol->luminance(), $bgCol->luminance());
	}

	function checkContrast() {
		$good = [];
		$meh = [];
		$bad = [];
		foreach ($this->textColors as $c => $txt) {
			$bg = Theme::getColor($this->backgroundColors, $c);
			$contrast = $this->checkColorContrast($txt, $bg);
			if ($contrast > 7) {
				array_push($good, $c);
			} else if ($contrast > 4.5) {
				array_push($meh, $c);
			} else {
				array_push($bad, $c);
			}
		}
		foreach ($this->backgroundColors as $c => $bg) {
			$txt = Theme::getColor($this->textColors, $c);
			$this->checkColorContrast($txt, $bg);
			$contrast = $this->checkColorContrast($txt, $bg);
			if ($contrast > 7) {
				array_push($good, $c);
			} else if ($contrast > 4.5) {
				array_push($meh, $c);
			} else {
				array_push($bad, $c);
			}
		}
		$good = array_unique($good);
		$meh = array_unique($meh);
		$bad = array_unique($bad);
		$goodCount = count($good);
		$mehCount = count($meh);
		$badCount = count($bad);
		return ["good" => $good, "meh" => $meh, "bad" => $bad, "count" => [
			"good" => $goodCount, "meh" => $mehCount, "bad" => $badCount,
			"total" => $goodCount + $mehCount + $badCount
		]];
	}

	// doesn't work if colors are identical
	function suggest($bad) {
		$suggestions = [];
		foreach ($bad as $sel) {
			$txt = Theme::getColor($this->textColors, $sel);
			$bg = Theme::getColor($this->backgroundColors, $sel);
			$txtCol = Color::fromHex(Theme::getColorValue($txt));
			$bgCol = Color::fromHex(Theme::getColorValue($bg));
			$suggestions[$sel] = [];
			$txtSuggs = Color::suggestColors($txtCol, $bgCol, false);
			if ($txtSuggs != []) $suggestions[$sel]["txt"] = $txtSuggs;
			$bgSuggs = Color::suggestColors($bgCol, $txtCol, false);
			if ($bgSuggs != []) $suggestions[$sel]["bg"] = $bgSuggs;
			$pairSuggs = Color::suggestColors($txtCol, $bgCol, true);
			if ($pairSuggs != []) $suggestions[$sel]["pair"] = $pairSuggs;
			
		}
		return $suggestions;
	}
}

// returns an array of suggestions
// 	[css selector] => {
// 		[txt] => array(colors), 
// 		[bg] => array(colors), 
// 		[pair] => array(colors)
// 	}
function getSuggestions() {
	$theme = Theme::get();
	$contrast = $theme->checkContrast();
	$suggestionsRaw = $theme->suggest($contrast["bad"]);
	$suggestions = [];
	foreach ($suggestionsRaw as $selector => $values) {
		$sel = str_replace('@', ' ', $selector);
		$sel = str_replace('~', '', $sel);
		$suggestions[$sel] = $values;
	}
	return $suggestions;
}

// debugging function
function dumpTheme() {
	$theme = Theme::get();
	$contrast = $theme->checkContrast();
	var_dump($theme->textColors);
	var_dump($theme->backgroundColors);
}

add_action("admin_notices", "dumpTheme");
