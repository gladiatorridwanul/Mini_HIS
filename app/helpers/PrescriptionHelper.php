<?php
// app/helpers/PrescriptionHelper.php - Helper functions for prescription
class PrescriptionHelper {
    
    public static function calculateAge($dob) {
        if (empty($dob) || $dob == '0000-00-00') {
            return 'N/A';
        }
        try {
            $birthDate = new DateTime($dob);
            $today = new DateTime('today');
            $age = $birthDate->diff($today);
            $parts = [];
            if ($age->y > 0) $parts[] = $age->y . ' Y';
            if ($age->m > 0) $parts[] = $age->m . ' M';
            if ($age->d > 0 && $age->y == 0 && $age->m == 0) $parts[] = $age->d . ' D';
            return !empty($parts) ? implode(' ', $parts) : '0 D';
        } catch (Exception $e) {
            return 'N/A';
        }
    }
    
    public static function getStatusBadge($status) {
        $map = [
            'draft' => 'secondary',
            'issued' => 'primary',
            'dispensed' => 'info',
            'completed' => 'success',
            'canceled' => 'danger'
        ];
        return $map[$status] ?? 'secondary';
    }
    
    public static function getPharmacyStatusBadge($status) {
        $map = [
            'pending' => 'warning',
            'processing' => 'info',
            'ready' => 'primary',
            'dispensed' => 'success',
            'collected' => 'success'
        ];
        return $map[$status] ?? 'secondary';
    }

    function getPrescriptionDropdownOptions($type, $selectedValue = '') {
        global $db; // Your database connection
        
        $query = "SELECT id, option_value, display_label 
                  FROM prescription_dropdown_options 
                  WHERE option_type = ? AND is_active = 1 
                  ORDER BY sort_order ASC, id ASC";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $options = [];
        while ($row = $result->fetch_assoc()) {
            $options[] = $row;
        }
        
        return $options;
    }

    function renderDropdownOptions($type, $namePrefix, $selectedValue = '', $style = '') {
        $options = getPrescriptionDropdownOptions($type, $selectedValue);
        
        $html = '<select class="form-control-simple" name="' . htmlspecialchars($namePrefix) . '" style="' . htmlspecialchars($style) . '">';
        
        foreach ($options as $opt) {
            $selected = ($opt['option_value'] === $selectedValue) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($opt['option_value']) . '" ' . $selected . '>' 
                     . htmlspecialchars($opt['display_label']) . '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }

    // Individual helper functions for convenience
    function getFoodRelationOptions($selectedValue = '') {
        return renderDropdownOptions('food_relation', '', $selectedValue);
    }

    function getFrequencyOptions($selectedValue = '') {
        return renderDropdownOptions('frequency', '', $selectedValue);
    }

    function getDurationOptions($selectedValue = '') {
        return renderDropdownOptions('duration', '', $selectedValue);
    }

    function getInstructionOptions($selectedValue = '') {
        return renderDropdownOptions('instruction', '', $selectedValue);
    }

    function getDosageFormOptions($selectedValue = '') {
        return renderDropdownOptions('dosage_form', '', $selectedValue);
    }

}