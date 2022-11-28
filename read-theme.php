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
		if (array_key_exists("color", $styles)) {
			$txt = ["default" => $styles["color"]["text"]];
			$bg = ["default" => $styles["color"]["text"]];
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
		echo $c;
	}

	private function checkColorContrast($txtRaw, $bgRaw) {
		$txt = $this->getColorValue($txtRaw);
		$bg = $this->getColorValue($bgRaw);
		$txtCol = Color::fromHex($txt);
		$bgCol = Color::fromHex($bg);
		echo " | bg: " . $bg . " (" . $bgRaw . ") - text: " . $txt . " (" . $txtRaw . ") <br>";
		return Color::contrast($txtCol->luminance(), $bgCol->luminance());
	}

	function checkContrast() {
		$good = [];
		$meh = [];
		$bad = [];
		foreach ($this->textColors as $c => $txt) {
			echo $c;
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
			echo $c;
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

	function suggest($bad) {
		$suggestions = [];
		foreach ($bad as $sel) {
			$txt = Theme::getColor($this->textColors, $sel);
			$bg = Theme::getColor($this->backgroundColors, $sel);
			$suggs = Color::suggestColors(
				Color::fromHex(Theme::getColorValue($txt)),
				Color::fromHex(Theme::getColorValue($bg)),
				false);
			echo $sel;
			var_dump($suggs);
		}
		return $suggestions;
	}
}

function dumpTheme() {
	$theme = Theme::get();
	$contrast = $theme->checkContrast();
	var_dump($theme->textColors);
	echo "<br>";
	var_dump($theme->backgroundColors);
	echo "<br>";
	echo "<br>Good: " . $contrast["count"]["good"] / 
		$contrast["count"]["total"] * 100 . "%";
	echo "<br>Ok: " . $contrast["count"]["meh"] / 
		$contrast["count"]["total"] * 100 . "%";
	echo "<br>Bad: " . $contrast["count"]["bad"] / 
		$contrast["count"]["total"] * 100 . "%<br><br>";
	var_dump($theme->suggest($contrast["bad"]));
}

add_action("admin_notices", "dumpTheme");

