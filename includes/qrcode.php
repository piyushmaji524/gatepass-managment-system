<?php
/**
 * QR Code Generator for PHP
 * Simple QR Code generation script for Gatepass Management System
 * 
 * @author Piyush Maji
 */

class QRCodeGenerator {
    private $size;
    private $margin;
    private $errorCorrectionLevel;
    
    /**
     * Constructor
     * 
     * @param int $size Size of the QR code (default: 300)
     * @param int $margin Margin of the QR code (default: 10)
     * @param string $errorCorrectionLevel Error correction level: L, M, Q, H (default: M)
     */
    public function __construct($size = 300, $margin = 10, $errorCorrectionLevel = 'M') {
        $this->size = $size;
        $this->margin = $margin;
        $this->errorCorrectionLevel = $errorCorrectionLevel;
    }
      /**
     * Generate QR code image using Google Chart API with fallback
     * 
     * @param string $data The data to encode in the QR code
     * @return string Base64 encoded image data
     */
    public function generate($data) {
        // First try using Google Chart API
        $data = urlencode($data);
        $url = "https://chart.googleapis.com/chart?cht=qr&chs={$this->size}x{$this->size}";
        $url .= "&chl={$data}&chld={$this->errorCorrectionLevel}|{$this->margin}";
        
        // Get the image data with error suppression
        $context = stream_context_create(['http' => ['timeout' => 3]]); // 3 second timeout
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            return base64_encode($response);
        }
        
        // Fallback: Use QR Code API
        $fallbackUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$this->size}x{$this->size}";
        $fallbackUrl .= "&data=" . urlencode($data);
        
        $fallbackResponse = @file_get_contents($fallbackUrl, false, $context);
        
        if ($fallbackResponse !== false) {
            return base64_encode($fallbackResponse);
        }
        
        // Final fallback: Generate a text-based QR representation
        return $this->generateTextFallback($data);
    }
    
    /**
     * Generate a text-based QR code representation as fallback
     * 
     * @param string $data The data to encode
     * @return string Base64 encoded image data of a text representation
     */
    private function generateTextFallback($data) {
        // Create a simple image with text
        $width = 300;
        $height = 150;
        $image = imagecreatetruecolor($width, $height);
        
        // Colors
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        
        // Fill background
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        
        // Add text
        $text1 = "QR Code Generation Failed";
        $text2 = "Please use this URL:";
        $text3 = urldecode($data);
        
        imagettftext($image, 14, 0, 20, 30, $textColor, 'arial', $text1);
        imagettftext($image, 12, 0, 20, 60, $textColor, 'arial', $text2);
        imagettftext($image, 10, 0, 20, 90, $textColor, 'arial', $text3);
        
        // Capture output
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        
        // Clean up
        imagedestroy($image);
        
        return base64_encode($imageData);
    }
    
    /**
     * Generate and save QR code image to a file
     * 
     * @param string $data The data to encode in the QR code
     * @param string $filename The filename to save the QR code to
     * @return bool True if successful, false otherwise
     */
    public function generateAndSave($data, $filename) {
        $imageData = $this->generate($data);
        
        if ($imageData) {
            return file_put_contents($filename, base64_decode($imageData)) !== false;
        }
        
        return false;
    }
    
    /**
     * Generate HTML img tag with QR code
     * 
     * @param string $data The data to encode in the QR code
     * @param string $alt Alt text for the image (default: 'QR Code')
     * @param array $attributes Additional attributes for the img tag (default: [])
     * @return string HTML img tag
     */
    public function generateHtmlImage($data, $alt = 'QR Code', $attributes = []) {
        $imageData = $this->generate($data);
        
        if (!$imageData) {
            return '';
        }
        
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " {$key}=\"" . htmlspecialchars($value) . "\"";
        }
        
        return "<img src=\"data:image/png;base64,{$imageData}\" alt=\"" . 
               htmlspecialchars($alt) . "\"{$attrString}>";
    }
}
