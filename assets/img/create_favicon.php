<?php
// Script to create a complete set of favicons for the Gunayatan Gatepass System
// This script generates multiple favicon sizes from either the logo.png or existing favicon.svg

// Set source file - use logo.png as primary source or favicon.svg if available
$source_file = __DIR__ . '/favicon.svg'; // Prefer SVG for better scaling
$logo_file = __DIR__ . '/logo.png';      // Fallback to logo.png

// Define output files
$output_files = [
    'favicon.png' => 32,                 // Standard 32x32 PNG favicon
    'favicon.ico' => [16, 32],           // Multi-size ICO file (16x16 and 32x32)
    'apple-touch-icon.png' => 180,       // Apple Touch Icon
    'android-chrome-192x192.png' => 192, // Android Chrome 192x192
    'android-chrome-512x512.png' => 512  // Android Chrome 512x512
];

// Check if ImageMagick is available (preferred for SVG conversion)
$use_imagemagick = extension_loaded('imagick');

// Check if source files exist
if (!file_exists($source_file) && !file_exists($logo_file)) {
    die('Error: Neither source file (favicon.svg or logo.png) found');
}

// Use logo.png as fallback if SVG doesn't exist
if (!file_exists($source_file)) {
    $source_file = $logo_file;
}

// Check if GD library is available
if (!extension_loaded('gd')) {
    die('Error: PHP GD library is required to create the favicons');
}

// Function to create a square favicon
function create_favicon($source, $size, $output_file) {
    // Create a square favicon
    $favicon = imagecreatetruecolor($size, $size);
    
    // Make the background transparent
    imagesavealpha($favicon, true);
    $trans_color = imagecolorallocatealpha($favicon, 0, 0, 0, 127);
    imagefill($favicon, 0, 0, $trans_color);

    // Get source dimensions
    $width = imagesx($source);
    $height = imagesy($source);

    // Resize and crop the logo to fit the favicon
    $aspect_ratio = $width / $height;
    if ($aspect_ratio > 1) {
        // Width is greater than height
        $src_width = $height;
        $src_height = $height;
        $src_x = ($width - $height) / 2;
        $src_y = 0;
    } else {
        // Height is greater than width
        $src_width = $width;
        $src_height = $width;
        $src_x = 0;
        $src_y = ($height - $width) / 2;
    }

    // Copy and resize the image
    imagecopyresampled(
        $favicon, $source,
        0, 0, $src_x, $src_y,
        $size, $size, $src_width, $src_height
    );

    // Save the favicon
    imagepng($favicon, $output_file);
    imagedestroy($favicon);

    return true;
}

// Save the favicon
imagepng($favicon, $output_file);

// Clean up
imagedestroy($source);
imagedestroy($favicon);

echo "Favicon created successfully at $output_file";
?>
