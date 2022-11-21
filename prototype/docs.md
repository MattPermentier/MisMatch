# Documentatie Color-Class
De `color` class is er om tussen kleurruimtes te rekenen.

## Kleurruimtes
- RGB ($red, $green, $blue)
- HSL ($hue, $hsl_saturation, $light)
- HSI ($hue, $hsi_saturation, $intensity)
- HSV ($hue, $hsv_saturation, $value)
- HCL ($hue, $chroma, $luma)

* Alle attributen worden als getal van 0-1 opgeslagen.
* Het H-attribuut is bij elke kleurruimte hetzelfde
* Het S-attribuut van HSL, HSI en HSV heet bij elke officiëel "saturation" maar
het wordt per kleurruimte anders berekend.

## Conversie
```php
Color::fromRgb(float $r, float $g, float $b) -> Color
```
Maakt een kleur-object aan op basis van rood, groen en blauw-waarden. R, G, B
elk tussen de 0 en 1. B.v. (0.15, 0.7, 0.55)

```php
Color::fromHex(string $hex) -> Color
```
Maakt een kleur-object aan op basis van een hexadecimale string. B.v "#0dde65".

```php
Color::hslToRgb(float $h, float $s, float $l) -> [$r, $g, $b]
Color::hsiToRgb(float $h, float $s, float $i) -> [$r, $g, $b]
Color::hsvToRgb(float $h, float $s, float $v) -> [$r, $g, $b]
Color::hclToRgb(float $h, float $c, float $l) -> [$r, $g, $b]
```
Berekent r, g en b-waarden vanuit een andere kleurruimte. Parameters tussen de
0 en 1.

```php
$col->luminance() -> float
```
Berekent de "lichtheid" van de kleur die wordt gebruik om het contrast te
berekenen.

```php
$col->toString() -> string
```
Maakt een rgb-string. B.v. "rgb(12, 204, 144)"

```php
$col->toString() -> string
```
Maakt een hexadecimale string. B.v. "#0dde65"

## Bronnen
[Conversie Kleuren](https://en.wikipedia.org/wiki/HSL_and_HSV#Luma,_chroma_and_hue_to_RGB)

