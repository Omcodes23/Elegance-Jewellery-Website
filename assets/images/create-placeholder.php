<?php
// Create a blank 300x300 image
$image = imagecreatetruecolor(300, 300);

// Set the background to light gray
$bgColor = imagecolorallocate($image, 240, 240, 240);
imagefill($image, 0, 0, $bgColor);

// Add text "No Image"
$textColor = imagecolorallocate($image, 120, 120, 120);
$font = 5; // Built-in font
$text = "No Image";

// Calculate position to center text
$textWidth = imagefontwidth($font) * strlen($text);
$textHeight = imagefontheight($font);
$x = (300 - $textWidth) / 2;
$y = (300 - $textHeight) / 2;

// Add text to image
imagestring($image, $font, $x, $y, $text, $textColor);

// Set the content type header
header('Content-Type: image/jpeg');

// Output the image
imagejpeg($image, __DIR__ . '/no-image.jpg', 90);
imagedestroy($image);

// Ensure the directory exists
if (!file_exists(__DIR__ . '/no-image.jpg')) {
    echo "Failed to create image file";
}
?>