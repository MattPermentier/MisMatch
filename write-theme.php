<?php

function makeSelector($values) {
	$vals = [];
	foreach ($values as $v) {
		$attr = $v->attribute;
		$vals[$attr] = [
		"old" => $v->old, 
		"new" => $v->new];
	}
	return $vals;
}

function readStyles() {
	$path = plugin_dir_path(__FILE__);
	$json = json_decode(file_get_contents($path . "mm_styles.json"));
	$mm_theme = [];
	foreach ($json as $selector => $values) {
		$mm_theme[$selector] = makeSelector($values);
	}
	$css = parseCSS(wp_get_global_stylesheet());
	foreach ($css as $selector => $values) {
		if (array_key_exists($selector, $mm_theme)) {
			$sel = $mm_theme[$selector];
			foreach ($values as $attr => $v) {
				if (array_key_exists($attr, $sel)) {
					if ($sel[$attr]["old"] != $v) {
						unset($mm_theme[$selector][$attr]);
					}
				}
			}
		}
	}
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
	echo "<br>";
	$cssStr = "";
	foreach ($styles as $sel => $vals) {
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
	echo wp_enqueue_style("MisMatch", $url);
}

// e.g. ($styles, "body", "background-color", "#f2eef6")
function setThemeAttribute(&$styles, $selector, $attribute, $value) {
	if (!array_key_exists($selector, $styles)) {
		$styles[$selector] = [];
	}
	$attr = ["new" => $value];
	$css = parseCSS(wp_get_global_stylesheet());
	if (array_key_exists($selector, $css)) {
		$attr["old"] = $css[$selector][$attribute];
	}
	$styles[$selector][$attribute] = $attr;
}

add_action("wp_enqueue_scripts", "addStyles");
