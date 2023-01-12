<?php
// Colors spaces:
// Red Green Blue
// Hue Saturation Light
// Hue Saturation Value (saturation not the same as with hsl)
// Hue Saturation Intensity (saturation also unique)
// Hue Chroma Luma

class Color {
	// attributes are a floating point number 0-1
	// simplifies conversions as opposed to 0-255
	public $red;
	public $green;
	public $blue;
	public $hue;
	public $hsl_saturation;
	public $hsv_saturation;
	public $hsi_saturation;
	public $light;
	public $value;
	public $intensity;
	public $chroma;
	public $luma;

	public function __construct(float $r, float $g, float $b, float $h, float $c, 
		float $lm, float $l, float $v, float $i,
		float $hsl_s, float $hsv_s, float $hsi_s) {
		$this->red = $r;
		$this->green = $g;
		$this->blue = $b;
		$this->hue = $h;
		$this->chroma = $c;
		$this->luma = $lm;
		$this->light = $l;
		$this->value = $v;
		$this->intensity = $i;
		$this->hsl_saturation = $hsl_s;
		$this->hsv_saturation = $hsv_s;
		$this->hsi_saturation = $hsi_s;
	}

	public static function fromRgb(float $r, float $g, float $b) {
		$r = max(0, min(1, $r));
		$g = max(0, min(1, $g));
		$b = max(0, min(1, $b));
		$cMin = min($r, $g, $b);
		$cMax = max($r, $g, $b);
		// necessary for color conversion
		$chroma = $cMax - $cMin;
		
		// hue, probably most complex but the same accross color spaces
		$hue = -12;

		if ($chroma == 0) $hue = -12;
		else if ($cMax == $r) {
			$hue = fmod((($g - $b) / $chroma), 6);
		} else if ($cMax == $g) {
			$hue = ($b - $r) / $chroma + 2;
		} else {
			$hue = ($r - $g) / $chroma + 4;
		}

		$hue /= 6;
		if ($hue < 0) $hue += 1;
		
		// chroma, illumination, value, light. easy to calculate
		$intensity = ($r + $g + $b) / 3;
		$value = $cMax;
		$light = ($cMax + $cMin) / 2;
		$luma = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
		
		// saturations
		$hsl_saturation = 0;

		if ($chroma == 0) $hsl_saturation = 0;
		else {
			$hsl_saturation = $chroma / (1 - abs(2 * $light - 1));
		}

		$hsv_saturation = 0;
		if ($value == 0) $hsv_saturation = 0;
		else {
			$hsv_saturation = $chroma / $value;
		}

		$hsi_saturation = 0;
		if ($intensity == 0) $hsi_saturation = 0;
		else {
			$hsi_saturation = 1 - ($cMin / $intensity);
		}

		return new Color($r, $g, $b, $hue, $chroma,
			$luma, $light, $value, $intensity,
			$hsl_saturation, $hsv_saturation, $hsi_saturation);
	}

	public static function fromHex(string $hex) {
		$rgb = sscanf($hex, "#%02x%02x%02x");
		return Color::fromRgb($rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255);
	}

	public static function luminanceColor(float $c) {
		if ($c <= 0.03928) {
			$c /= 12.92;
		} else {
			$c = (($c + 0.055) / 1.055) ** 2.4;
		}
		return $c;
	}

	public static function luminanceFromRgb(float $r, float $g, float $b) {
		$r = Color::luminanceColor(round($r * 255) / 255);
		$g = Color::luminanceColor(round($g * 255) / 255);
		$b = Color::luminanceColor(round($b * 255) / 255);

		return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
	}

	public function luminance() {
		return Color::luminanceFromRgb($this->red, $this->green, $this->blue);
	}

	private static function hcxToRgb($hue, $chroma, $x) {
            $rgb = [];
		if ($hue < 0) {
			return [0, 0, 0];
		} else if ($hue < 1) {
                  $rgb = [$chroma, $x, 0];
            } else if ($hue < 2) {
                  $rgb = [$x, $chroma, 0];
            } else if ($hue < 3) {
                  $rgb = [0, $chroma, $x];      
            } else if ($hue < 4) {
                  $rgb = [0, $x, $chroma];
            } else if ($hue < 5) {
                  $rgb = [$x, 0, $chroma];
            } else {
                  $rgb = [$chroma, 0, $x];
            }

		return $rgb;
	}

	public static function hcmxToRgb($hue, $chroma, $m, $x) {
		$rgb = Color::hcxToRgb($hue, $chroma, $x);
		
		return ["red" => $rgb[0] + $m, "green" => $rgb[1] + $m, "blue" => $rgb[2] + $m];
	}

	public static function hslToRgb($h, $s, $l) {
		$s = max(0, min(1, $s));
		$l = max(0, min(1, $l));
		$chroma = (1 - abs(2 * $l - 1)) * $s;
		$hue = $h * 6;
		$x = $chroma * (1 - abs(fmod($hue, 2) - 1));
		$m = $l - ($chroma / 2);
		return Color::hcmxToRgb($hue, $chroma, $m, $x);
	}

	public static function hsvToRgb($h, $s, $v) {
		$v = max(0, min(1, $v));
		$s = max(0, min(1, $s));
		$chroma = $v * $s;
		$hue = $h * 6;
		$x = $chroma * (1 - abs(fmod($hue, 2) - 1));
		$m = $v - $chroma;
		return Color::hcmxToRgb($hue, $chroma, $m, $x);
	}

	public static function hsiToRgb($h, $s, $i) {
		$s = max(0, min(1, $s));
		$i = max(0, min(1, $i));
		$hue = $h * 6;
		$z = 1 - abs(fmod($hue, 2) - 1);
		$chroma = (3 * $i * $s) / (1 + $z);
		$x = $chroma * $z;
		$m = $i * (1 - $s);
		return Color::hcmxToRgb($hue, $chroma, $m, $x);
	}

	public static function hclToRgb($h, $c, $l) {
		$c = max(0, min(1, $c));
		$l = max(0, min(1, $l));
		$hue = $h * 6;
		$x = $c * (1 - abs(fmod($hue, 2) - 1));
		$rgb = Color::hcxToRgb($hue, $c, $x);
		$m = $l - ($rgb[0] * 0.2126 + $rgb[1] * 0.7152 + $rgb[2] * 0.0722);
		$r = $rgb[0] + $m;
		$g = $rgb[1] + $m;
		$b = $rgb[2] + $m;
		return ["red" => $r, "green" => $g, "blue" => $b];
	}

	public static function rgbToString($r, $g, $b) {
		$sr = strval($r * 255);
		$sg = strval($g * 255);
		$sb = strval($b * 255);
		return "rgb($sr, $sg, $sb)";
	}

	// shorthand for rgbToString
	public function toString() {
		return Color::rgbToString($this->red, $this->green, $this->blue);
	}

	public static function rgbToHex($r, $g, $b) {
		$r = max(0, min(255, $r));
		$g = max(0, min(255, $g));
		$b = max(0, min(255, $b));
		return sprintf("#%02x%02x%02x", $r, $g, $b);
	}

	// shorthand for rgbToHex
	public function toHex() {
		return Color::rgbToHex(
			round($this->red * 255), 
			round($this->green * 255), 
			round($this->blue * 255)
		);
	}
	
	// shorthand for rgbToString
	public static function arrToString($c) {
		return Color::rgbToString($c['red'], $c['green'], $c['blue']);
	}

	// contrast between two luminances
	public static function contrast($lum1, $lum2) {
		$contrast = ($lum1 + 0.05) / ($lum2 + 0.05);
		if ($contrast < 1) $contrast = 1 / $contrast;
		return $contrast;
	}

	public static function pairSuggestion($c1, $c2, $lum, $sat, $lum2, $sat2, $conFn, $doSat = false) {
		$lum1 = $c1->luminance();
		$lum2 = $c2->luminance();
		$contrast = Color::contrast($lum1, $lum2);
		$l = $lum;
		$s = $sat;
		$l2 = $lum2;
		$s2 = $sat2;
		$dir = $lum1 > $lum2 ? 1 : -1;
		while ($contrast < 7.5) {
			if ($doSat) { 
				$s -= 0.01;
				$s2 -= 0.01;
			}
			$l += $dir * 0.02;
			$l2 -= $dir * 0.02;

			$rgb = call_user_func("Color::" . $conFn, $c1->hue, $s, $l);
			$c1 = Color::fromRgb($rgb["red"], $rgb["green"], $rgb["blue"]);
			$lum1 = $c1->luminance();

			$rgb2 = call_user_func("Color::" . $conFn, $c2->hue, $s2, $l2);
			$c2 = Color::fromRgb($rgb2["red"], $rgb2["green"], $rgb2["blue"]);
			$lum2 = $c2->luminance();

			$contrast = Color::contrast($lum1, $lum2);
			if ($doSat) {
				if ($s < 0 || $s > 1) return null;
			}
			else if ($l < 0 || $l > 1) {
				return Color::makeSuggestion($c1, $c2, $lum, $s, $conFn, true);
			}
		}

		return [$c1, $c2];
	}

	public static function makeSuggestion($c1, $c2, $lum, $sat, $conFn, $doSat = false) {
		$lum1 = $c1->luminance();
		$lum2 = $c2->luminance();
		$contrast = Color::contrast($lum1, $lum2);
		$l = $lum;
		$s = $sat;
		$dir = $lum1 > $lum2 ? 1 : -1;
		while ($contrast < 7.5) {
			if ($doSat) $s -= 0.025;
			$l += $dir * 0.05;
			$rgb = call_user_func("Color::" . $conFn, $c1->hue, $s, $l);
			$c1 = Color::fromRgb($rgb["red"], $rgb["green"], $rgb["blue"]);
			$lum1 = $c1->luminance();
			$contrast = Color::contrast($lum1, $lum2);
			if ($doSat) {
				if ($s < 0 || $s > 1) return null;
			}
			else if ($l < 0 || $l > 1) {
				return Color::makeSuggestion($c1, $c2, $lum, $s, $conFn, true);
			}
		}
		return $c1;
	}

	// how good is contrast between the two colors?
	// shift colour until contrast is ~7
	// repeat for all color spaces
	public static function suggestColors($c1, $c2, $pair = false) {
		$lum1 = Color::luminanceFromRgb($c1->red, $c1->green, $c1->blue);
		$lum2 = Color::luminanceFromRgb($c2->red, $c2->green, $c2->blue);
		$con = Color::contrast($lum1, $lum2);
		if ($con >= 7) return [];
		// How to list color spaces and corresponding function?
		$colors = [];
		foreach ([[$c1, $c2]] as $cArr) {
			$c = $cArr[0];
			$cBg = $cArr[1];
			for ($i = 0; $i < 4; $i++) {
				$sat = false;
				$luminances = [$c->luma, $c->intensity, $c->value, $c->light];
				$saturations = [$c->chroma, $c->hsi_saturation,
					$c->hsv_saturation, $c->hsl_saturation];
				$bgLuminances = [$cBg->luma, $cBg->intensity, $cBg->value, $cBg->light];
				$bgSaturations = [$cBg->chroma, $cBg->hsi_saturation,
					$cBg->hsv_saturation, $cBg->hsl_saturation];
				$conFunctions = ["hclToRgb", "hsiToRgb", "hsvToRgb", "hslToRgb"];
				if ($pair) {
					$cols = Color::pairSuggestion($c, $cBg, 
					$luminances[$i], $saturations[$i],
					$bgLuminances[$i], $bgSaturations[$i],
					$conFunctions[$i], $sat);
					if (gettype($cols) == "array")
						array_push($colors, $cols);
				} else {
					$col = Color::makeSuggestion($c, $cBg, 
					$luminances[$i], $saturations[$i], $conFunctions[$i], $sat);
					if ($col != null) 
						array_push($colors, $col);
				}
			}
		}
		return $colors;
	}
}
