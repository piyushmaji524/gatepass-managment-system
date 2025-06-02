<?php
// favicon_generator.php - A comprehensive favicon generator for Gunayatan Gatepass System
// This script generates all favicon formats from the SVG source

// Define the base path
$base_path = __DIR__;

// Source SVG file
$svg_file = $base_path . '/favicon.svg';

// Check if source file exists
if (!file_exists($svg_file)) {
    die('Error: Source SVG file (favicon.svg) not found');
}

// Check if GD library is available
if (!extension_loaded('gd')) {
    die('Error: PHP GD library is required to create the favicons');
}

// Define sizes to generate
$sizes = [
    'favicon.ico' => 32,
    'favicon.png' => 32,
    'apple-touch-icon.png' => 180,
    'android-chrome-192x192.png' => 192,
    'android-chrome-512x512.png' => 512
];

// Function to convert SVG to PNG
function convertSvgToPng($svg_file, $output_file, $size) {
    // For this demo, we'll use Imagick if available
    if (extension_loaded('imagick')) {
        try {
            $imagick = new \Imagick();
            $imagick->readImage($svg_file);
            $imagick->setImageFormat("png24");
            $imagick->resizeImage($size, $size, \Imagick::FILTER_LANCZOS, 1);
            $imagick->writeImage($output_file);
            $imagick->clear();
            $imagick->destroy();
            return true;
        } catch (Exception $e) {
            echo "Error converting SVG: " . $e->getMessage() . "\n";
            return false;
        }
    } else {
        // Fallback to system command if available
        $command = "convert -background none -size {$size}x{$size} $svg_file $output_file";
        exec($command, $output, $return_var);
        return $return_var === 0;
    }
}

// Function to create ICO file
function createIcoFile($png_file, $ico_file) {
    // For this demo, we'll use a simple conversion approach
    if (extension_loaded('imagick')) {
        try {
            $imagick = new \Imagick($png_file);
            $imagick->setImageFormat('ico');
            $imagick->writeImage($ico_file);
            return true;
        } catch (Exception $e) {
            echo "Error creating ICO: " . $e->getMessage() . "\n";
            return false;
        }
    } else {
        // Fallback to system command if available
        $command = "convert $png_file $ico_file";
        exec($command, $output, $return_var);
        return $return_var === 0;
    }
}

// Create a simple HTML page for favicon generation status
echo "<!DOCTYPE html>
<html>
<head>
    <title>Favicon Generator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Gunayatan Gatepass System - Favicon Generator</h1>
    <p>Generating favicons from the source SVG file...</p>
    <table>
        <tr>
            <th>File</th>
            <th>Size</th>
            <th>Status</th>
        </tr>";

// First generate the PNG files
foreach ($sizes as $filename => $size) {
    if ($filename !== 'favicon.ico') {
        $output_file = $base_path . '/' . $filename;
        $result = convertSvgToPng($svg_file, $output_file, $size);
        
        echo "<tr>
            <td>$filename</td>
            <td>{$size}x{$size}</td>
            <td class='" . ($result ? "success" : "error") . "'>" . 
            ($result ? "✓ Success" : "✗ Failed") . "</td>
        </tr>";
    }
}

// Then create the ICO file from the PNG
$png_file = $base_path . '/favicon.png';
$ico_file = $base_path . '/favicon.ico';

if (file_exists($png_file)) {
    $result = createIcoFile($png_file, $ico_file);
    
    echo "<tr>
        <td>favicon.ico</td>
        <td>32x32</td>
        <td class='" . ($result ? "success" : "error") . "'>" . 
        ($result ? "✓ Success" : "✗ Failed") . "</td>
    </tr>";
} else {
    echo "<tr>
        <td>favicon.ico</td>
        <td>32x32</td>
        <td class='error'>✗ Failed - Source PNG not found</td>
    </tr>";
}

// Provide a download link and preview
echo "</table>
    <h2>Preview</h2>
    <div style='padding: 20px; background-color: #f5f5f5; border: 1px solid #ddd; display: inline-block;'>
        <img src='favicon.svg' style='width: 32px; height: 32px; margin-right: 15px;'>
        <img src='favicon.png' style='width: 32px; height: 32px; margin-right: 15px;'>
        <img src='favicon.ico' style='width: 32px; height: 32px;'>
    </div>
    
    <h2>Installation</h2>
    <p>The favicon files have been generated in the assets/img directory. The header.php file should already include references to these files.</p>
    
    <h3>Manual Installation</h3>
    <p>If you need to manually add the favicon references to your HTML, use the following code:</p>
    <pre style='background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow: auto;'>
&lt;!-- Standard Favicon -->
&lt;link rel=\"icon\" type=\"image/svg+xml\" href=\"assets/img/favicon.svg\">
&lt;link rel=\"icon\" type=\"image/png\" href=\"assets/img/favicon.png\" sizes=\"32x32\">
&lt;link rel=\"icon\" type=\"image/x-icon\" href=\"assets/img/favicon.ico\">

&lt;!-- Apple Touch Icon -->
&lt;link rel=\"apple-touch-icon\" href=\"assets/img/apple-touch-icon.png\">

&lt;!-- Android Chrome -->
&lt;link rel=\"manifest\" href=\"assets/img/site.webmanifest\">
&lt;meta name=\"theme-color\" content=\"#2c3e50\">
    </pre>
</body>
</html>";

// For command line usage, provide a simple summary
if (PHP_SAPI === 'cli') {
    echo "\nFavicon generation complete.\n";
    foreach ($sizes as $filename => $size) {
        echo "$filename: " . (file_exists($base_path . '/' . $filename) ? "Created" : "Failed") . "\n";
    }
    echo "favicon.ico: " . (file_exists($ico_file) ? "Created" : "Failed") . "\n";
}
?>
