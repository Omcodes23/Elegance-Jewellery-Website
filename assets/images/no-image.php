<?php
// Set the content type to JPEG
header('Content-Type: image/jpeg');

// Create a simple gray placeholder image (200x200 pixels)
$image = imagecreatetruecolor(200, 200);
$gray = imagecolorallocate($image, 240, 240, 240);
imagefilledrectangle($image, 0, 0, 199, 199, $gray);

// Add text "No Image" to the placeholder
$text_color = imagecolorallocate($image, 150, 150, 150);
$font = 5; // Built-in font
$text = "No Image";
$text_width = imagefontwidth($font) * strlen($text);
$text_height = imagefontheight($font);
$x = (200 - $text_width) / 2;
$y = (200 - $text_height) / 2;
imagestring($image, $font, $x, $y, $text, $text_color);

// Output the image
imagejpeg($image);
imagedestroy($image);
?>