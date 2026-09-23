<?php
/**
 * Barcode Helper - PHP Barcode Generator
 * Generates consistent Code128 barcodes
 */
class BarcodeHelper
{
    /**
     * Generate barcode as SVG
     * @param string $code The patient code to encode
     * @return string SVG markup
     */
    public static function generateBarcodeSVG($code)
    {
        $code = (string)$code;
        $binary = self::encodeToCode128($code);
        
        $barWidth = 2;
        $height = 60;
        $width = strlen($binary) * $barWidth;
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . ($height + 20) . '" viewBox="0 0 ' . $width . ' ' . ($height + 20) . '">' . "\n";
        $svg .= '  <rect width="100%" height="100%" fill="white"/>' . "\n";
        
        $x = 0;
        for ($i = 0; $i < strlen($binary); $i++) {
            if ($binary[$i] == '1') {
                $svg .= '  <rect x="' . $x . '" y="5" width="' . $barWidth . '" height="' . $height . '" fill="black"/>' . "\n";
            }
            $x += $barWidth;
        }
        
        // Add text below barcode
        $svg .= '  <text x="50%" y="' . ($height + 15) . '" font-family="monospace" font-size="11" font-weight="bold" text-anchor="middle" fill="#333" letter-spacing="1">' . htmlspecialchars($code) . '</text>' . "\n";
        $svg .= '</svg>';
        
        return $svg;
    }
    
    /**
     * Encode to Code128 binary pattern
     */
    private static function encodeToCode128($code)
    {
        // Code128 character patterns
        $patterns = [
            '0' => '11011001100', '1' => '11001101100', '2' => '11001100110',
            '3' => '10010011000', '4' => '10010001100', '5' => '10001001100',
            '6' => '10011001000', '7' => '10011000100', '8' => '10001100100',
            '9' => '11001001000', 'A' => '11001000100', 'B' => '11000100100',
            'C' => '10110011100', 'D' => '10011011100', 'E' => '10011001110',
            'F' => '10111001100', 'G' => '10011101100', 'H' => '10011100110',
            'I' => '11001110010', 'J' => '11001011100', 'K' => '11001001110',
            'L' => '11011100100', 'M' => '11001110100', 'N' => '11101101110',
            'O' => '11101001100', 'P' => '11100101100', 'Q' => '11100100110',
            'R' => '11101100100', 'S' => '11100110100', 'T' => '11100110010',
            'U' => '11011011000', 'V' => '11011000110', 'W' => '11000110110',
            'X' => '10100011000', 'Y' => '10001011000', 'Z' => '10001000110'
        ];
        
        $binary = '';
        $codeUpper = strtoupper($code);
        
        for ($i = 0; $i < strlen($codeUpper); $i++) {
            $char = $codeUpper[$i];
            if (isset($patterns[$char])) {
                $binary .= $patterns[$char];
            } else {
                // Use a default pattern for unknown characters
                $binary .= '11001101100';
            }
        }
        
        // Add start (Code128 Start A) and stop patterns
        $binary = '11010000100' . $binary . '1100011101011';
        
        return $binary;
    }
}
?>