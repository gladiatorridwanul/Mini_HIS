<?php
/**
 * Barcode Helper - PHP Barcode Generator (Fallback)
 */
class BarcodeHelper
{
    /**
     * Generate Code128 barcode as SVG
     * @param string $code The patient code to encode
     */
    public static function generateCode128($code)
    {
        $code128 = new self();
        return $code128->encodeCode128($code);
    }
    
    /**
     * Encode to Code128
     */
    private function encodeCode128($code)
    {
        // Simple Code128 encoding
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
            'X' => '10100011000', 'Y' => '10001011000', 'Z' => '10001000110',
            'a' => '10010000110', 'b' => '10000100110', 'c' => '10100100010',
            'd' => '10100001100', 'e' => '10010100010', 'f' => '10010010110',
            'g' => '10000101100', 'h' => '10000100110', 'i' => '10110010010',
            'j' => '11000110010', 'k' => '11010001000', 'l' => '11000101000',
            'm' => '11010010000', 'n' => '11000110000', 'o' => '11001010000',
            'p' => '11001000100', 'q' => '11000110100', 'r' => '11000101100',
            's' => '11001101000', 't' => '11001100100', 'u' => '11010100000',
            'v' => '11010011000', 'w' => '11001011000', 'x' => '11001110000',
            'y' => '11001110100', 'z' => '11010110000'
        ];
        
        $binary = '';
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            if (isset($patterns[$char])) {
                $binary .= $patterns[$char];
            } else {
                // If character not found, use a default pattern
                $binary .= '11001101100';
            }
        }
        
        // Add start and stop
        $binary = '11010000100' . $binary . '1100011101011';
        
        return $binary;
    }
    
    /**
     * Generate barcode as SVG from binary pattern
     * @param string $code The patient code
     */
    public static function generateBarcodeSVG($code)
    {
        $helper = new self();
        $binary = $helper->encodeCode128($code);
        
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
        
        $svg .= '  <text x="50%" y="' . ($height + 15) . '" font-family="monospace" font-size="11" font-weight="bold" text-anchor="middle" fill="#333">' . htmlspecialchars($code) . '</text>' . "\n";
        $svg .= '</svg>';
        
        return $svg;
    }
    
    /**
     * Generate barcode as HTML (for display)
     * @param array $patient The patient data
     */
    public static function generateBarcodeHTML($patient)
    {
        $code = is_array($patient) ? $patient['patient_code'] : $patient;
        $code = (string)$code;
        
        $helper = new self();
        $binary = $helper->encodeCode128($code);
        
        $html = '<div class="barcode-wrapper" style="text-align:center;background:white;padding:10px;display:inline-block;">';
        $html .= '<div class="barcode" style="display:block;white-space:nowrap;margin:0 auto;padding:0;line-height:0;">';
        
        for ($i = 0; $i < strlen($binary); $i++) {
            if ($binary[$i] == '1') {
                $html .= '<span style="display:inline-block;width:2px;height:50px;background:#000;margin:0;padding:0;"></span>';
            } else {
                $html .= '<span style="display:inline-block;width:2px;height:50px;background:#fff;margin:0;padding:0;"></span>';
            }
        }
        
        $html .= '</div>';
        $html .= '<div style="margin-top:8px;font-family:monospace;font-size:12px;font-weight:bold;letter-spacing:1px;color:#333;">' . htmlspecialchars($code) . '</div>';
        $html .= '</div>';
        
        return $html;
    }
}
?>