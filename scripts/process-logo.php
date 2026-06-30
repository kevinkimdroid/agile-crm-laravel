<?php

$src = __DIR__ . '/../public/images/kenya-orient-logo.png';
$img = imagecreatefrompng($src);
if (!$img) {
    fwrite(STDERR, "Failed to load logo\n");
    exit(1);
}

$w = imagesx($img);
$h = imagesy($img);

$light = imagecreatetruecolor($w, $h);
$trans = imagecreatetruecolor($w, $h);
imagealphablending($light, false);
imagesavealpha($light, true);
imagealphablending($trans, false);
imagesavealpha($trans, true);

$white = imagecolorallocatealpha($light, 255, 255, 255, 0);
$clear = imagecolorallocatealpha($trans, 0, 0, 0, 127);
imagefilledrectangle($light, 0, 0, $w, $h, $white);
imagefilledrectangle($trans, 0, 0, $w, $h, $clear);

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgba = imagecolorat($img, $x, $y);
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;
        $a = ($rgba >> 24) & 0xFF;

        if ($r < 45 && $g < 45 && $b < 45) {
            imagesetpixel($light, $x, $y, $white);
            continue;
        }

        $cLight = imagecolorallocatealpha($light, $r, $g, $b, $a);
        imagesetpixel($light, $x, $y, $cLight);
        $cTrans = imagecolorallocatealpha($trans, $r, $g, $b, $a);
        imagesetpixel($trans, $x, $y, $cTrans);
    }
}

$outLight = __DIR__ . '/../public/images/kenya-orient-logo-light.png';
$outTrans = __DIR__ . '/../public/images/kenya-orient-logo-transparent.png';
imagepng($light, $outLight, 9);
imagepng($trans, $outTrans, 9);

echo "Created {$w}x{$h} variants\n";
