<?php

function makeSelector($values) {
	$vals = [];
	foreach ($values as $v) {
		$attr = $v->attribute;
		$old = "empty";
		if ($v->old) $old = $v->old;
		$vals[$attr] = [
		"old" => $old, 
		"new" => $v->new];
	}
	return $vals;
}

function trimSelector($sel) {
	$sel = explode(":", $sel)[0];
	$sel = explode(">", $sel)[0];
	return $sel;
}

function readStyles() {
	$path = plugin_dir_path(__FILE__) . "mm_styles.json";
	if (!file_exists($path)) {
		return [];
	}
	$json = json_decode(file_get_contents($path));
	$mm_theme = [];
	foreach ($json as $selector => $values) {
		$mm_theme[$selector] = makeSelector($values);
	}
	$css = parseCSS(wp_get_global_stylesheet());
	return $mm_theme;
}

function saveStyles($styles) {
	$path = plugin_dir_path(__FILE__);
	$obj = [];
	foreach ($styles as $selector => $values) {
		$current = [];
		foreach ($values as $name => $data) {
			$data["attribute"] = $name;
			array_push($current, $data);
		}
		$obj[$selector] = $current;
	}
	file_put_contents($path . "mm_styles.json", json_encode($obj));
}

function saveStylesheet($sheet) {
	$path = plugin_dir_path(__FILE__);
	file_put_contents($path . "styles.css", $sheet);
}

function makeStylesheet($styles) {
	$cssStr = "";
	foreach ($styles as $sel => $vals) {
		$sel = selToCss($sel);
		$cssStr .= $sel . " {\n";
		foreach ($vals as $attr => $v) {
			$cssStr .= "\t" . $attr . ": " . $v['new'] . ";\n";
		}
		$cssStr .= "}\n";
	}
	return $cssStr;
}

function addStyles() {
	saveStylesheet(makeStylesheet(readStyles()));
	$url = plugins_url("styles.css", __FILE__);
	wp_enqueue_style("MisMatch", $url);
}

// e.g. ($styles, "body", "background-color", "#f2eef6")
function setThemeAttribute(&$styles, $selector, $attribute, $value) {
	if ($attribute == "txt") $attribute = "color";
	if ($attribute == "bg") $attribute = "background-color";
	if (!array_key_exists($selector, $styles)) {
		$styles[$selector] = [];
	}
	$attr = ["new" => $value];
	$css = parseCSS(wp_get_global_stylesheet());
	$attr["old"] = "empty";
	$selector = selToWp(trimSelector($selector));
	if (array_key_exists($selector, $css)) {
		$attr["old"] = $css[$selector][$attribute];
	}
	$styles[$selector][$attribute] = $attr;
}

add_action("wp_enqueue_scripts", "addStyles");
