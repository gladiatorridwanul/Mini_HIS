<?php
// app/helpers/DateHelper.php

if (!function_exists('calculateAge')) {
    /**
     * Calculate age from date of birth
     * 
     * @param string $dateOfBirth Date of birth in Y-m-d format
     * @return string Formatted age string
     */
    function calculateAge($dateOfBirth) {
        if (empty($dateOfBirth) || $dateOfBirth == '0000-00-00') {
            return 'N/A';
        }
        
        try {
            $dob = new DateTime($dateOfBirth);
            $today = new DateTime('today');
            $age = $dob->diff($today);
            
            if ($age->y > 0) {
                return $age->y . 'Y ' . $age->m . 'M';
            } elseif ($age->m > 0) {
                return $age->m . 'M ' . $age->d . 'D';
            } else {
                return $age->d . 'D';
            }
        } catch (Exception $e) {
            return 'N/A';
        }
    }
}
?>