<?php

include_once("color.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 

$txtRgb = [46, 42, 40];
if (array_key_exists("text-color", $_GET)) {
	$txtRgb = sscanf($_GET["text-color"], "#%02x%02x%02x");
}
$bgRgb = [0, 209, 88];
if (array_key_exists("bg-color", $_GET)) {
	$bgRgb = sscanf($_GET["bg-color"], "#%02x%02x%02x");
}
$txtc = Color::fromRgb($txtRgb[0]/255,$txtRgb[1]/255,$txtRgb[2]/255);
$bgc = Color::fromRgb($bgRgb[0]/255,$bgRgb[1]/255,$bgRgb[2]/255);

$cols = array($txtc, $bgc);

$txtLum = $txtc->luminance();
$bgLum = $bgc->luminance();

$contrast = Color::contrast($txtLum, $bgLum);
$textSuggestions = Color::suggestColors($txtc, $bgc);
$bgSuggestions = Color::suggestColors($bgc, $txtc);
?>
<head>
<style>
body {
	--text-color: <?= $txtc->toString() ?>;
	--bg-color: <?= $bgc->toString() ?>;
}
</style>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<main>
	<h1>Lorem Ipsum</h1>
	<p> According to all known laws of aviation, there is no way that a bee
	should be able to fly. The bee, of course, doesn't care what humans think
	it can and cannot do. </p>
	<h2>The contrast between the text and background is: <?= $contrast ?></h2>
	<div class="table-wrapper">
	<?php foreach ($cols as $c): ?>
	<table>
		<tr>
			<th>Light</th>
			<th>hsL</th>
			<th>hsI</th>
			<th>hsV</th>
			<th>hcL</th>
			<th>hSI</th>
			<th>hSV</th>
			<th>hCL</th>
		</tr>
		<?php for($i = -10; $i <= 10; $i+= 0.1) { ?>
		<tr>
			<?php if (abs($i) < 0.04) { ?>
			<td>Base</td>
			<?php } else { ?>
			<td></td>
			<?php } ?>
			<?php 
			$inc = $i * 0.1;
			$current_hsl = Color::hslToRgb($c->hue, $c->hsl_saturation, $c->light + $inc);
			$current_hsi_sat = Color::hsiToRgb($c->hue, $c->hsi_saturation - $inc, $c->intensity + $inc);
			$current_hsi = Color::hsiToRgb($c->hue, $c->hsi_saturation, $c->intensity + $inc);
			$current_hsv_sat = Color::hsvToRgb($c->hue, $c->hsv_saturation - $inc, $c->value + $inc);
			$current_hsv = Color::hsvToRgb($c->hue, $c->hsv_saturation, $c->value + $inc);
			$current_hcl_chrom = Color::hclToRgb($c->hue, $c->chroma - abs($inc / 2), $c->luma + $inc);
			$current_hcl = Color::hclToRgb($c->hue, $c->chroma, $c->luma + $inc);
			?>
			<td style="background-color: <?= Color::arrToString($current_hsl) ?>;"
			></td>
			<td style="background-color: <?= Color::arrToString($current_hsi) ?>;"
			></td>
			<td style="background-color: <?= Color::arrToString($current_hsv) ?>;"
			></td>
			<td style="background-color: <?= Color::arrToString($current_hcl) ?>;"
			></td>
			<td style="background-color: <?= Color::arrToString($current_hsi_sat) ?>;"
			></td>
			<td style="background-color: <?= Color::arrToString($current_hsv_sat) ?>;"
			></td>
			<td style="background-color: <?= Color::arrToString($current_hcl_chrom) ?>;"
			></td>
		<tr>
		<?php } ?>
	</table>
	<?php endforeach; ?>
	</div>
</main>
<aside>
	<form method="GET" action="">
		<label for="bg-color">Background color</label>
		<input type="color" id="bg-color" name="bg-color" value="<?= $bgc->toHex() ?>">
		<br>
		<br>
		<label for="text-color">Text color</label>
		<input type="color" id="text-color" name="text-color" value="<?= $txtc->tohex() ?>">
		<br>
		<br>
		<input type="submit" value="Update">

		<h2>Suggestions</h2>
		<h4>Text</h4>
		<div class="suggestion-wrapper">
			<?php foreach ($textSuggestions as $col) { ?>
			<div class="suggestion" data-ctype="txt"
			data-color="<?= $col->toHex() ?>"style="background-color: <?= $col->toHex() ?>;"></div>
			<?php } ?>
		</div>
		<h4>Background</h4>
		<div class="suggestion-wrapper">
			<?php foreach ($bgSuggestions as $col) { ?>
			<div class="suggestion" data-ctype="bg"
			data-color="<?= $col->toHex() ?>"style="background-color: <?= $col->toHex() ?>;"></div>
			<?php } ?>
		</div>
	</form>
</aside>
<script async defer src="js/script.js"></script>
</body>
