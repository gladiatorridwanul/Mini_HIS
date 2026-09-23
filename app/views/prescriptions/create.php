<?php
// app/views/prescriptions/create.php - Standalone like register.php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');

// Fetch dropdown options from database
function getDropdownOptions($type) {
    global $conn;
    $stmt = $conn->prepare("SELECT option_value, display_label FROM prescription_dropdown_options WHERE option_type = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC");
    $stmt->bind_param('s', $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $options = [];
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }
    return $options;
}

// Cache dropdown options for use in JavaScript
$foodRelationOptions = getDropdownOptions('food_relation');
$frequencyOptions = getDropdownOptions('frequency');
$durationOptions = getDropdownOptions('duration');
$instructionOptions = getDropdownOptions('instruction');
$dosageFormOptions = getDropdownOptions('dosage_form');

// Convert to JSON for JavaScript
$foodRelationJson = json_encode($foodRelationOptions);
$frequencyJson = json_encode($frequencyOptions);
$durationJson = json_encode($durationOptions);
$instructionJson = json_encode($instructionOptions);
$dosageFormJson = json_encode($dosageFormOptions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Prescription - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== BASE STYLES ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        
        #submitBtn { display: none; }
        
        .card-custom {
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .card-header-custom {
            background: white;
            border-bottom: 2px solid #3b82f6;
            padding: 10px 15px;
            font-weight: 600;
            font-size: 14px;
        }
        .filter-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        .filter-label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 3px;
            display: block;
        }
        
        /* ===== BUTTONS ===== */
        .btn-add-row {
            background: #dbeafe;
            color: #2563eb;
            border: none;
            padding: 2px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            height: 26px;
        }
        .btn-add-row:hover { background: #2563eb; color: white; }
        .btn-remove-row {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .btn-remove-row:hover { background: #dc2626; color: white; }
        .btn-load-previous {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-load-previous:hover { background: #e2e8f0; color: #1e293b; }
        .btn-tab {
            padding: 8px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            text-decoration: none;
        }
        .btn-save { background: #3b82f6; color: white; }
        .btn-save:hover { background: #2563eb; }
        .btn-update { background: #f59e0b; color: white; }
        .btn-update:hover { background: #d97706; }
        .btn-complete { background: #10b981; color: white; }
        .btn-complete:hover { background: #059669; }
        .btn-next { background: #10b981; color: white; }
        .btn-next:hover { background: #059669; }
        .btn-cancel { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-cancel:hover { background: #e2e8f0; color: #1e293b; }
        .btn-print { background: #8b5cf6; color: white; }
        .btn-print:hover { background: #7c3aed; }
        .btn-print-pad { background: #f59e0b; color: white; }
        .btn-print-pad:hover { background: #d97706; }
        .btn-load-all {
            background: #8b5cf6;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-load-all:hover { background: #7c3aed; }

        /* ===== FORM ELEMENTS ===== */
        .form-control-simple {
            border: none;
            border-bottom: 1px dashed #d1d5db;
            padding: 4px 0;
            font-size: 13px;
            background: transparent;
            width: 100%;
            transition: all 0.2s;
            color: #1e293b;
        }
        .form-control-simple:focus {
            outline: none;
            border-bottom-color: #3b82f6;
            border-bottom-style: solid;
        }
        textarea.form-control-simple {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px 12px;
            min-height: 60px;
            resize: vertical;
            font-family: inherit;
        }
        textarea.form-control-simple:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        /* ===== MEDICINE ITEM ===== */
        .medicine-item {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        .medicine-item .form-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: block;
            margin-bottom: 2px;
        }
        .medicine-item .form-label .text-danger { color: #ef4444; }
        
        .medicine-detail-row {
            display: flex;
            gap: 6px;
            align-items: center;
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
            margin-bottom: 4px;
            flex-wrap: wrap;
        }
        .medicine-detail-row .form-control-simple {
            border-bottom: 1px solid #d1d5db;
            flex: 1;
            min-width: 80px;
        }
        .medicine-detail-row select.form-control-simple {
            border-bottom: 1px solid #d1d5db;
            flex: 1;
            min-width: 80px;
            background: transparent;
            padding: 4px 0;
            font-size: 13px;
            color: #1e293b;
        }
        .medicine-detail-row select.form-control-simple:focus {
            outline: none;
            border-bottom-color: #3b82f6;
        }

        /* ===== DRUG DROPDOWN ===== */
        .drug-select-wrapper { position: relative; }
        .drug-select-wrapper input {
            width: 100%;
            padding: 4px 8px;
            border: none;
            border-bottom: 1px dashed #d1d5db;
            background: transparent;
            font-size: 13px;
            color: #1e293b;
        }
        .drug-select-wrapper input:focus {
            outline: none;
            border-bottom-color: #3b82f6;
            border-bottom-style: solid;
        }
        .drug-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 999;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .drug-dropdown.show { display: block; }
        .drug-dropdown .drug-item {
            padding: 6px 10px;
            cursor: pointer;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .drug-dropdown .drug-item:hover { background: #f1f5f9; }
        .drug-dropdown .drug-item .drug-name { font-weight: 500; }
        .drug-dropdown .drug-item .drug-strength { color: #64748b; font-size: 12px; }
        .drug-dropdown .drug-item .drug-code { color: #94a3b8; font-size: 11px; margin-left: 8px; }
        .drug-dropdown .no-results { padding: 10px; text-align: center; color: #94a3b8; font-size: 13px; }
        .drug-dropdown .drug-item .drug-dosage-form { color: #3b82f6; font-size: 11px; margin-left: 6px; }

        /* ===== COMPLAINT/HISTORY ITEMS ===== */
        .complaint-item, .history-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
            font-size: 13px;
            border-bottom: 1px dotted #f1f5f9;
            flex-wrap: wrap;
        }
        .complaint-item:last-child, .history-item:last-child { border-bottom: none; }
        .complaint-item .bullet, .history-item .bullet { color: #3b82f6; font-weight: bold; }

        /* ===== VITAL GRID ===== */
        .vital-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 6px;
        }
        .vital-item label {
            font-size: 9px;
            font-weight: 500;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: block;
            margin-bottom: 1px;
        }
        .vital-item input {
            width: 100%;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            padding: 3px 6px;
            font-size: 12px;
            height: 28px;
            transition: all 0.2s;
            background: white;
        }
        .vital-item input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        .bp-group { display: flex; gap: 3px; align-items: center; }
        .bp-group input { flex: 1; }
        .bp-group span { line-height: 28px; color: #64748b; font-weight: 500; font-size: 12px; }

        /* ===== CHECKBOX GROUP ===== */
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .checkbox-group .form-check {
            display: flex;
            align-items: center;
            gap: 4px;
            margin: 0;
            padding: 2px 8px;
            background: #f8fafc;
            border-radius: 4px;
            border: 1px solid #e9edf2;
        }
        .checkbox-group .form-check:hover { background: #f1f5f9; }
        .checkbox-group .form-check-input {
            width: 13px;
            height: 13px;
            margin: 0;
            cursor: pointer;
        }
        .checkbox-group .form-check-input:checked {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        .checkbox-group .form-check-label {
            font-size: 12px;
            color: #334155;
            cursor: pointer;
            margin: 0;
        }

        /* ===== PATIENT INFO GRID ===== */
        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px 16px;
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #e9edf2;
        }
        .patient-info-grid .info-item {
            display: flex;
            align-items: baseline;
            gap: 4px;
            font-size: 13px;
        }
        .patient-info-grid .info-item .label {
            font-weight: 500;
            color: #64748b;
            font-size: 11px;
            min-width: 35px;
        }
        .patient-info-grid .info-item .value {
            font-weight: 600;
            color: #0f172a;
            font-size: 13px;
        }
        .patient-info-grid .info-item input,
        .patient-info-grid .info-item select {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            height: auto !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            font-size: 13px !important;
            width: auto !important;
            display: inline-block !important;
        }
        .patient-info-grid .info-item input:focus,
        .patient-info-grid .info-item select:focus {
            box-shadow: none !important;
            outline: none !important;
        }

        /* ===== SECTION TITLES ===== */
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .section-title .title-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }
        .title-icon.blue { background: #dbeafe; color: #2563eb; }
        .title-icon.green { background: #d1fae5; color: #059669; }
        .title-icon.orange { background: #fef3c7; color: #d97706; }
        .title-icon.purple { background: #ede9fe; color: #7c3aed; }
        .title-icon.red { background: #fee2e2; color: #dc2626; }
        .title-icon.teal { background: #ccfbf1; color: #0d9488; }
        .title-icon.pink { background: #fce7f3; color: #db2777; }
        .title-icon.indigo { background: #e0e7ff; color: #4f46e5; }
        .title-icon.amber { background: #fef3c7; color: #d97706; }
        .title-icon.slate { background: #f1f5f9; color: #475569; }
        .section-content { padding: 4px 0 12px 0; }
        .subsection-title {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin: 8px 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ===== TABS ===== */
        .tab-nav {
            display: flex;
            background: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
            overflow-x: auto;
            flex-wrap: nowrap;
        }
        .tab-nav .tab-item {
            padding: 12px 24px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            border-top: none;
            border-left: none;
            border-right: none;
            flex-shrink: 0;
        }
        .tab-nav .tab-item:hover { color: #1e293b; background: #f1f5f9; }
        .tab-nav .tab-item.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
            background: white;
        }
        .tab-nav .tab-item .tab-icon { font-size: 14px; }
        .tab-nav .tab-item .tab-badge {
            background: #dbeafe;
            color: #2563eb;
            font-size: 10px;
            padding: 1px 8px;
            border-radius: 10px;
            margin-left: 4px;
        }
        .tab-content { display: none; padding: 20px 24px; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== MODAL ===== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            padding: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 16px;
        }
        .modal-header h5 { margin: 0; font-size: 18px; font-weight: 600; }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
            padding: 0 8px;
        }
        .modal-close:hover { color: #1e293b; }
        .list-item {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .list-item:hover { background: #f8fafc; }
        .list-item .item-info { display: flex; flex-direction: column; gap: 2px; }
        .list-item .item-info .item-title { font-weight: 500; color: #1e293b; }
        .list-item .item-info .item-sub { font-size: 12px; color: #64748b; }
        .list-item .btn-load-selected {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
        }
        .list-item .btn-load-selected:hover { background: #2563eb; }
        .list-item .btn-load-all {
            background: #8b5cf6;
            color: white;
            border: none;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
        }
        .list-item .btn-load-all:hover { background: #7c3aed; }

        /* ===== TAB FOOTER ===== */
        .tab-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            margin-top: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .tab-footer .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ===== ALERTS ===== */
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 10px 14px;
            font-size: 13px;
        }
        .alert-success-custom {
            background: #ecfdf5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .alert-danger-custom {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* ===== TOAST ===== */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }
        .toast {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 8px;
            min-width: 250px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: toastSlideIn 0.3s ease;
            color: white;
        }
        .toast-success { background: #10b981; }
        .toast-error { background: #ef4444; }
        .toast-warning { background: #f59e0b; }
        .toast-info { background: #3b82f6; }
        @keyframes toastSlideIn {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* ===== LAB TEST DROPDOWN ===== */
        .lab-test-select-wrapper { position: relative; }
        .lab-test-select-wrapper input {
            width: 100%;
            padding: 4px 8px;
            border: none;
            border-bottom: 1px dashed #d1d5db;
            background: transparent;
            font-size: 13px;
            color: #1e293b;
        }
        .lab-test-select-wrapper input:focus {
            outline: none;
            border-bottom-color: #3b82f6;
            border-bottom-style: solid;
        }
        .lab-test-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 999;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .lab-test-dropdown.show { display: block; }
        .lab-test-dropdown .lab-test-item {
            padding: 6px 10px;
            cursor: pointer;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .lab-test-dropdown .lab-test-item:hover { background: #f1f5f9; }
        .lab-test-dropdown .lab-test-item .test-name { font-weight: 500; }
        .lab-test-dropdown .lab-test-item .test-category { color: #64748b; font-size: 11px; }
        .lab-test-dropdown .lab-test-item .test-price { color: #059669; font-size: 12px; font-weight: 500; }
        .lab-test-dropdown .lab-test-item .test-code { color: #94a3b8; font-size: 11px; margin-left: 8px; }
        .lab-test-dropdown .no-results { padding: 10px; text-align: center; color: #94a3b8; font-size: 13px; }

        /* ===== VACCINATION ITEM ===== */
        .vaccination-item {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .vaccination-item:last-child { border-bottom: none; margin-bottom: 0; }

        /* ===== BUTTONS NEXT APPT ===== */
        .btn-next-appt {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 2px 12px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-next-appt:hover { background: #e2e8f0; }
        .btn-next-appt.active { background: #3b82f6; color: white; border-color: #3b82f6; }

        .btn-edit-vac {
            background: #dbeafe;
            color: #2563eb;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-edit-vac:hover { background: #2563eb; color: white; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .patient-info-grid { grid-template-columns: repeat(2, 1fr); }
            .tab-nav .tab-item { padding: 10px 16px; font-size: 12px; }
        }
        @media (max-width: 768px) {
            .patient-info-grid { grid-template-columns: 1fr; }
            .tab-nav .tab-item { padding: 8px 12px; font-size: 11px; }
            .tab-content { padding: 14px 16px; }
            .tab-footer { flex-direction: column; align-items: stretch; }
            .tab-footer .btn-group { justify-content: stretch; }
            .tab-footer .btn-group .btn-tab { flex: 1; justify-content: center; }
            .vital-grid { grid-template-columns: 1fr 1fr; }
            .medicine-detail-row { flex-direction: column; align-items: stretch; }
            .medicine-detail-row .form-control-simple { min-width: unset; }
        }
        @media (max-width: 480px) {
            .tab-nav .tab-item { padding: 6px 10px; font-size: 10px; }
            .tab-nav .tab-item .tab-icon { font-size: 11px; }
            .tab-nav .tab-item .tab-badge { display: none; }
            .vital-grid { grid-template-columns: 1fr; }
        }
        .tab-nav::-webkit-scrollbar { height: 4px; }
        .tab-nav::-webkit-scrollbar-track { background: #f1f5f9; }
        .tab-nav::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        @keyframes highlightPulse {
            0% { border-color: #3b82f6; background-color: #eff6ff; }
            50% { border-color: #60a5fa; background-color: #dbeafe; }
            100% { border-color: #3b82f6; background-color: #eff6ff; }
        }
        .vaccination-item.highlight {
            animation: highlightPulse 1s ease 3;
        }
        
        .spinner-border-sm {
            width: 16px;
            height: 16px;
            border-width: 2px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <!-- ===== HEADER ===== -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-prescription" style="color: #3b82f6;"></i> Create Prescription</h5>
                <p class="text-muted" style="font-size: 11px;">Fill in the prescription details below</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/prescriptions" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-home"></i>
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-custom alert-success-custom mb-3">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert" style="font-size: 10px;"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['errors']) && count($_SESSION['errors']) > 0): ?>
            <div class="alert-custom alert-danger-custom mb-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert" style="font-size: 10px;"></button>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <!-- Main Card -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-prescription text-primary"></i> Prescription Form
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo BASE_URL; ?>/prescriptions/save" id="prescriptionForm" data-save-url="<?php echo BASE_URL; ?>/prescriptions/save" data-update-url="<?php echo BASE_URL; ?>/prescriptions/update/">
                    
                    <!-- Tab Navigation -->
                    <div class="tab-nav" id="tabNav">
                        <button type="button" class="tab-item active" data-tab="tab1">
                            <span class="tab-icon">📋</span> Patient Information
                            <span class="tab-badge">1</span>
                        </button>
                        <button type="button" class="tab-item" data-tab="tab2">
                            <span class="tab-icon">🩺</span> O/E & Investigation
                            <span class="tab-badge">2</span>
                        </button>
                        <button type="button" class="tab-item" data-tab="tab3">
                            <span class="tab-icon">💊</span> Treatment / Medicine
                            <span class="tab-badge">3</span>
                        </button>
                        <button type="button" class="tab-item" data-tab="tab4">
                            <span class="tab-icon">📄</span> Advice & Followup
                            <span class="tab-badge">4</span>
                        </button>
                        <button type="button" class="tab-item" data-tab="tab5">
                            <span class="tab-icon">💉</span> Vaccinations & Lab
                            <span class="tab-badge">5</span>
                        </button>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TAB 1: PATIENT INFORMATION -->
                    <!-- ============================================================ -->
                    <div class="tab-content active" id="tab1">
                        <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn-load-previous" onclick="loadPreviousPrescriptions('tab1')">
                                <i class="fas fa-history"></i> Load Previous
                            </button>
                        </div>

                        <div class="section-title">
                            <span class="title-icon blue"><i class="fas fa-user"></i></span> Patient Information
                        </div>
                        <div class="section-content">
                            <div class="patient-info-grid">
                                <div class="info-item">
                                    <span class="label">ID:</span>
                                    <span class="value"><?php echo htmlspecialchars($patient['patient_code'] ?? ''); ?></span>
                                    <input type="hidden" name="patient_id" value="<?php echo $patient['id'] ?? 0; ?>">
                                </div>
                                <div class="info-item">
                                    <span class="label">Visit:</span>
                                    <span class="value"><?php echo $visitNumber ?? 1; ?></span>
                                    <input type="hidden" name="visit_number" value="<?php echo $visitNumber ?? 1; ?>">
                                </div>
                                <div class="info-item">
                                    <span class="label">Name:</span>
                                    <span class="value"><?php echo htmlspecialchars($patient['full_name'] ?? ($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '')); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Age:</span>
                                    <span class="value"><?php 
                                        $dob = $patient['date_of_birth'] ?? '';
                                        if (!empty($dob) && $dob != '0000-00-00') {
                                            $birth = new DateTime($dob);
                                            $today = new DateTime('today');
                                            $age = $birth->diff($today);
                                            echo $age->y . 'Y ' . $age->m . 'M';
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Sex:</span>
                                    <span class="value"><?php echo ucfirst($patient['gender'] ?? ''); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Marital:</span>
                                    <select name="marital_status" style="border:none !important;background:transparent !important;padding:0 !important;font-weight:600 !important;color:#0f172a !important;font-size:13px !important;cursor:pointer;outline:none;">
                                        <option value="">Select</option>
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="divorced">Divorced</option>
                                        <option value="widowed">Widowed</option>
                                    </select>
                                </div>
                                <div class="info-item">
                                    <span class="label">Mobile:</span>
                                    <span class="value"><?php echo htmlspecialchars($patient['phone'] ?? ''); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Date:</span>
                                    <input type="date" name="prescription_date" value="<?php echo date('Y-m-d'); ?>" style="border:none !important;background:transparent !important;padding:0 !important;width:auto !important;font-weight:600 !important;color:#0f172a !important;font-size:13px !important;">
                                </div>
                                <div class="info-item">
                                    <span class="label">Appointment:</span>
                                    <span class="value"><?php echo htmlspecialchars($appointment['appointment_number'] ?? 'N/A'); ?></span>
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointmentId ?? 0; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Doctor Information -->
                        <div class="section-title mt-3">
                            <span class="title-icon blue"><i class="fas fa-user-md"></i></span> 
                            Doctor Information
                            <?php if (!empty($appointment)): ?>
                                <span class="badge bg-success ms-2" style="font-size:10px;">
                                    <i class="fas fa-calendar-check me-1"></i> 
                                    Appointment: <?php echo htmlspecialchars($appointment['appointment_number'] ?? 'N/A'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="section-content">
                            <div style="display:flex;flex-wrap:wrap;gap:16px;background:#f8fafc;padding:10px 16px;border-radius:6px;border:1px solid #e9edf2;">
                                <div>
                                    <span style="font-size:10px;color:#64748b;display:block;">Doctor Name</span>
                                    <span style="font-weight:600;font-size:13px;">
                                        <?php 
                                        $doctorName = htmlspecialchars(
                                            ($doctor['title'] ?? 'Dr.') . ' ' . 
                                            ($doctor['first_name'] ?? '') . ' ' . 
                                            ($doctor['last_name'] ?? '')
                                        );
                                        echo trim($doctorName) ?: 'Not Assigned';
                                        ?>
                                        <?php if (!empty($appointment)): ?>
                                            <span class="badge bg-info ms-1" style="font-size:9px;">
                                                <i class="fas fa-check-circle"></i> Assigned
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div>
                                    <span style="font-size:10px;color:#64748b;display:block;">Specialization</span>
                                    <span style="font-weight:500;font-size:13px;">
                                        <?php echo htmlspecialchars($doctor['specialization'] ?? 'General'); ?>
                                    </span>
                                </div>
                                <div>
                                    <span style="font-size:10px;color:#64748b;display:block;">BMDC Number</span>
                                    <span style="font-weight:500;font-size:13px;">
                                        <?php echo htmlspecialchars($doctor['bmdc_number'] ?? 'N/A'); ?>
                                    </span>
                                </div>
                                <div>
                                    <span style="font-size:10px;color:#64748b;display:block;">Qualification</span>
                                    <span style="font-weight:500;font-size:13px;">
                                        <?php echo htmlspecialchars($doctor['qualification'] ?? 'MBBS'); ?>
                                    </span>
                                </div>
                                <?php if (!empty($doctor['consultation_fee']) && $doctor['consultation_fee'] > 0): ?>
                                <div>
                                    <span style="font-size:10px;color:#64748b;display:block;">Consultation Fee</span>
                                    <span style="font-weight:600;font-size:13px;color:#059669;">
                                        ৳ <?php echo number_format($doctor['consultation_fee'], 2); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($appointment)): ?>
                                <div>
                                    <span style="font-size:10px;color:#64748b;display:block;">Appointment Date</span>
                                    <span style="font-weight:500;font-size:13px;">
                                        <?php echo date('d M Y', strtotime($appointment['appointment_date'] ?? 'now')); ?>
                                        <span style="font-size:11px;color:#64748b;">
                                            (<?php echo ucfirst($appointment['session_type'] ?? ''); ?>)
                                        </span>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['id'] ?? 0; ?>">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointmentId ?? 0; ?>">
                        </div>

                        <!-- Chief Complaints -->
                        <!-- Chief Complaints & Treatment History Side by Side -->
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="section-title">
                                    <span class="title-icon orange"><i class="fas fa-notes-medical"></i></span> Chief Complaints
                                    <button type="button" class="btn-add-row ms-2" onclick="addComplaint()">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                                <div class="section-content">
                                    <div id="complaintsContainer">
                                        <div class="complaint-item">
                                            <span class="bullet">•</span>
                                            <input type="text" class="form-control-simple" name="complaints[0][text]" placeholder="Enter complaint..." style="flex:1;">
                                            <input type="text" class="form-control-simple" name="complaints[0][duration]" placeholder="Duration" style="min-width:80px;max-width:120px;">
                                            <input type="text" class="form-control-simple" name="complaints[0][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                                            <button type="button" class="btn-remove-row" onclick="removeRow(this, 'complaintsContainer')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="section-title">
                                    <span class="title-icon teal"><i class="fas fa-notes-medical"></i></span> Treatment History
                                    <button type="button" class="btn-add-row ms-2" onclick="addTreatmentHistory()">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                                <div class="section-content">
                                    <div id="treatmentHistoryContainer">
                                        <div class="history-item">
                                            <span class="bullet">•</span>
                                            <input type="text" class="form-control-simple" name="treatment_history[0][name]" placeholder="Enter treatment..." style="flex:1;">
                                            <input type="text" class="form-control-simple" name="treatment_history[0][duration]" placeholder="Duration" style="min-width:80px;max-width:120px;">
                                            <input type="text" class="form-control-simple" name="treatment_history[0][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                                            <button type="button" class="btn-remove-row" onclick="removeRow(this, 'treatmentHistoryContainer')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Drug & Disease History -->
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="section-title">
                                    <span class="title-icon purple"><i class="fas fa-pills"></i></span> Drug History
                                    <button type="button" class="btn-add-row ms-2" onclick="addDrugHistory()">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                                <div class="section-content">
                                    <div id="drugHistoryContainer">
                                        <div class="history-item">
                                            <span class="bullet">•</span>
                                            <input type="text" class="form-control-simple" name="drug_history[0][name]" placeholder="Drug name..." style="flex:1;">
                                            <input type="text" class="form-control-simple" name="drug_history[0][duration]" placeholder="Duration" style="min-width:80px;max-width:120px;">
                                            <input type="text" class="form-control-simple" name="drug_history[0][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                                            <button type="button" class="btn-remove-row" onclick="removeRow(this, 'drugHistoryContainer')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="section-title">
                                    <span class="title-icon pink"><i class="fas fa-notes-medical"></i></span> Disease History
                                    <button type="button" class="btn-add-row ms-2" onclick="addDiseaseHistory()">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                                <div class="section-content">
                                    <div id="diseaseHistoryContainer">
                                        <div class="history-item">
                                            <span class="bullet">•</span>
                                            <input type="text" class="form-control-simple" name="disease_history[0][name]" placeholder="Disease..." style="flex:1;">
                                            <input type="text" class="form-control-simple" name="disease_history[0][duration]" placeholder="Duration" style="min-width:80px;max-width:120px;">
                                            <input type="text" class="form-control-simple" name="disease_history[0][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                                            <button type="button" class="btn-remove-row" onclick="removeRow(this, 'diseaseHistoryContainer')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Special Note -->
                        <div class="section-title mt-2">
                            <span class="title-icon teal"><i class="fas fa-sticky-note"></i></span> Special Note
                        </div>
                        <div class="section-content">
                            <textarea class="form-control-simple" name="special_note" rows="2" placeholder="Enter any special notes..."></textarea>
                        </div>

                        <!-- TAB 1 FOOTER -->
                        <div class="tab-footer">
                            <div class="btn-group">
                                <button type="button" class="btn-tab btn-next" onclick="saveAndGoNext(); return false;">
                                    <i class="fas fa-save me-1"></i> Save & Next <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                            <div class="btn-group">
                                <a href="<?php echo BASE_URL; ?>/prescriptions" class="btn-tab btn-cancel">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TAB 2: O/E & INVESTIGATION -->
                    <!-- ============================================================ -->
                    <div class="tab-content" id="tab2">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <button type="button" class="btn-load-previous" onclick="loadPreviousPrescriptions('tab2')">
                                <i class="fas fa-history"></i> Load Previous
                            </button>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn-load-previous" onclick="saveTemplate('investigation')">
                                    <i class="fas fa-save"></i> Save Template
                                </button>
                                <button type="button" class="btn-load-previous" onclick="showTemplateModal('investigation')">
                                    <i class="fas fa-file-alt"></i> Insert Template
                                </button>
                            </div>
                        </div>

                        <!-- Physical Examination -->
                        <div class="section-title">
                            <span class="title-icon green"><i class="fas fa-heartbeat"></i></span> Physical Examination
                        </div>
                        <div class="section-content">
                            <div class="subsection-title">G/E (General Examination)</div>
                            <div class="checkbox-group mb-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="physical_exam[anaemia]" id="anaemia">
                                    <label class="form-check-label" for="anaemia">Anaemia</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="physical_exam[jaundice]" id="jaundice">
                                    <label class="form-check-label" for="jaundice">Jaundice</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="physical_exam[cyanosis]" id="cyanosis">
                                    <label class="form-check-label" for="cyanosis">Cyanosis</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="physical_exam[oedema]" id="oedema">
                                    <label class="form-check-label" for="oedema">Oedema</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="physical_exam[dehydration]" id="dehydration">
                                    <label class="form-check-label" for="dehydration">Dehydration</label>
                                </div>
                            </div>
                            
                            <div class="subsection-title mt-3">Vital Signs</div>
                            <div class="vital-grid">
                                <div class="vital-item">
                                    <label>Pulse (bpm)</label>
                                    <input type="number" name="vital_signs[pulse]" placeholder="84" oninput="calculateBMI()">
                                </div>
                                <div class="vital-item">
                                    <label>Weight (Kg)</label>
                                    <input type="number" step="0.01" name="vital_signs[weight]" placeholder="70" id="vitalWeight" oninput="calculateBMI()">
                                </div>
                                <div class="vital-item">
                                    <label>R/R (min)</label>
                                    <input type="number" name="vital_signs[respiratory_rate]" placeholder="16">
                                </div>
                                <div class="vital-item">
                                    <label>Height (cm)</label>
                                    <input type="number" step="0.01" name="vital_signs[length]" placeholder="170" id="vitalHeight" oninput="calculateBMI()">
                                </div>
                                <div class="vital-item">
                                    <label>BP (mmHg)</label>
                                    <div class="bp-group">
                                        <input type="number" name="vital_signs[bp_systolic]" placeholder="120">
                                        <span>/</span>
                                        <input type="number" name="vital_signs[bp_diastolic]" placeholder="80">
                                    </div>
                                </div>
                                <div class="vital-item">
                                    <label>Temperature (°F)</label>
                                    <input type="number" step="0.1" name="vital_signs[temperature]" placeholder="98.6">
                                </div>
                                <div class="vital-item">
                                    <label>O2 Saturation (%)</label>
                                    <input type="number" name="vital_signs[oxygen_saturation]" placeholder="98">
                                </div>
                                <div class="vital-item">
                                    <label>BMI</label>
                                    <input type="number" step="0.1" name="vital_signs[bmi]" placeholder="24.5" id="vitalBMI" readonly style="background:#f1f5f9;cursor:not-allowed;">
                                </div>
                                <div class="vital-item">
                                    <label>Others</label>
                                    <input type="text" name="vital_signs[others]" placeholder="Other findings">
                                </div>
                            </div>
                            
                            <div class="subsection-title mt-3">System Examination</div>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <input type="text" class="form-control-simple" name="physical_exam[abdomen]" placeholder="Abdomen" style="border:1px solid #e5e7eb;border-radius:4px;padding:4px 8px;">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control-simple" name="physical_exam[cvs]" placeholder="CVS" style="border:1px solid #e5e7eb;border-radius:4px;padding:4px 8px;">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control-simple" name="physical_exam[respiratory]" placeholder="Respiratory System" style="border:1px solid #e5e7eb;border-radius:4px;padding:4px 8px;">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control-simple" name="physical_exam[lymphoreticular]" placeholder="Lymphoreticular System" style="border:1px solid #e5e7eb;border-radius:4px;padding:4px 8px;">
                                </div>
                            </div>
                        </div>

                        <!-- Investigations -->
                        <div class="section-title mt-3">
                            <span class="title-icon indigo"><i class="fas fa-microscope"></i></span> Investigations
                            <button type="button" class="btn-add-row ms-2" onclick="addInvestigation()">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                        <div class="section-content">
                            <div id="investigationsContainer">
                                <div class="history-item">
                                    <span class="bullet">•</span>
                                    <input type="text" class="form-control-simple" name="investigations[0][name]" placeholder="Investigation..." style="flex:1;">
                                    <input type="text" class="form-control-simple" name="investigations[0][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                                    <button type="button" class="btn-remove-row" onclick="removeRow(this, 'investigationsContainer')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Lab Test Request -->
                        <div class="section-title mt-3">
                            <span class="title-icon indigo"><i class="fas fa-flask"></i></span> Lab Test Request
                            <button type="button" class="btn-add-row ms-2" onclick="addLabTestRow()">
                                <i class="fas fa-plus"></i> Add Test
                            </button>
                        </div>
                        <div class="section-content">
                            <div id="labTestsContainer">
                                <div class="lab-test-item" data-test-index="0">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Test Name <span class="text-danger">*</span></label>
                                            <div class="lab-test-select-wrapper">
                                                <input type="text" class="lab-test-search-input form-control-simple" 
                                                       placeholder="Search lab test..." 
                                                       data-test-index="0"
                                                       autocomplete="off"
                                                       style="width:100%;">
                                                <input type="hidden" name="lab_tests[0][test_id]" value="">
                                                <div class="lab-test-dropdown" id="labTestDropdown_0"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Priority</label>
                                            <select class="form-control-simple" name="lab_tests[0][priority]" style="width:100%;background:transparent;">
                                                <option value="routine" selected>Routine</option>
                                                <option value="urgent">Urgent</option>
                                                <option value="stat">STAT</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Notes</label>
                                            <input type="text" class="form-control-simple" name="lab_tests[0][notes]" placeholder="Special instructions..." style="width:100%;">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn-remove-row" onclick="removeLabTestRow(this)" style="margin-top:18px;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col-md-12">
                                            <small class="text-muted" id="labTestInfo_0" style="font-size:11px;">
                                                <span class="text-muted">Select a test to see details</span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-add-row mt-2" onclick="addLabTestRow()">
                                <i class="fas fa-plus"></i> Add Another Lab Test
                            </button>
                        </div>

                        <!-- Diagnosis -->
                        <div class="section-title mt-3">
                            <span class="title-icon red"><i class="fas fa-stethoscope"></i></span> Diagnosis / Plan
                            <div class="ms-auto d-flex gap-1">
                                <button type="button" class="btn-load-previous" onclick="saveTemplate('diagnosis')">
                                    <i class="fas fa-save"></i> Save Template
                                </button>
                                <button type="button" class="btn-load-previous" onclick="showTemplateModal('diagnosis')">
                                    <i class="fas fa-file-alt"></i> Insert Template
                                </button>
                            </div>
                        </div>
                        <div class="section-content">
                            <textarea class="form-control-simple" name="diagnosis" rows="3" placeholder="Enter diagnosis..." style="border:1px solid #e5e7eb;border-radius:6px;padding:8px 12px;min-height:60px;width:100%;"></textarea>
                        </div>

                        <!-- TAB 2 FOOTER -->
                        <div class="tab-footer">
                            <div class="btn-group">
                                <button type="button" class="btn-tab btn-cancel" onclick="switchTab('tab1')">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="btn-tab btn-next" onclick="saveAndGoNext()">
                                    Save & Next <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                            <div class="btn-group">
                                <a href="<?php echo BASE_URL; ?>/prescriptions" class="btn-tab btn-cancel">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TAB 3: TREATMENT / MEDICINE -->
                    <!-- ============================================================ -->
                    <div class="tab-content" id="tab3">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <button type="button" class="btn-load-previous" onclick="loadPreviousPrescriptions('tab3')">
                                <i class="fas fa-history"></i> Load Previous
                            </button>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn-load-previous" onclick="saveTemplate('medicine')">
                                    <i class="fas fa-save"></i> Save Template
                                </button>
                                <button type="button" class="btn-load-previous" onclick="showTemplateModal('medicine')">
                                    <i class="fas fa-file-alt"></i> Insert Template
                                </button>
                            </div>
                        </div>

                        <div class="section-title">
                            <span class="title-icon green"><i class="fas fa-prescription"></i></span> Treatment / Medicine
                            <button type="button" class="btn-add-row" id="addMedicineBtn" onclick="addNextMedicine()">
                                <i class="fas fa-plus"></i> Add Medicine
                            </button>
                            <span class="badge bg-secondary" id="medicineCountDisplay" style="font-size:11px;">0 / 20</span>
                        </div>
                        <div class="section-content">
                            <div id="medicinesContainer">
                                <!-- Medicines will be rendered by JavaScript -->
                            </div>
                        </div>

                        <!-- TAB 3 FOOTER -->
                        <div class="tab-footer">
                            <div class="btn-group">
                                <button type="button" class="btn-tab btn-cancel" onclick="switchTab('tab2')">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="btn-tab btn-next" onclick="saveAndGoNext()">
                                    Save & Next <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                            <div class="btn-group">
                                <a href="<?php echo BASE_URL; ?>/prescriptions" class="btn-tab btn-cancel">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TAB 4: ADVICE & PRINT -->
                    <!-- ============================================================ -->
                    <div class="tab-content" id="tab4">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <button type="button" class="btn-load-previous" onclick="loadPreviousPrescriptions('tab4')">
                                <i class="fas fa-history"></i> Load Previous
                            </button>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn-load-previous" onclick="saveTemplate('advice')">
                                    <i class="fas fa-save"></i> Save Template
                                </button>
                                <button type="button" class="btn-load-previous" onclick="showTemplateModal('advice')">
                                    <i class="fas fa-file-alt"></i> Insert Template
                                </button>
                            </div>
                        </div>

                        <!-- Advice -->
                        <div class="section-title">
                            <span class="title-icon teal"><i class="fas fa-comment-medical"></i></span> Advice
                        </div>
                        <div class="section-content">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <textarea class="form-control-simple" name="advice[text]" id="adviceText" rows="4" placeholder="Enter advice for patient..." style="border:1px solid #e5e7eb;border-radius:6px;padding:8px 12px;min-height:80px;width:100%;"></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertAdvice('কম তেল-মসলা জাতীয় খাবার খাবেন।')">কম তেল-মসলা</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertAdvice('বাহিরের ভাজা-পোড়া খাবার খাবেন না।')">ভাজা-পোড়া নয়</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertAdvice('রাতে হালকা খাবার খাবেন।')">রাতে হালকা</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertAdvice('পর্যাপ্ত পানি পান করুন।')">পানি পান</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertAdvice('সপ্তাহে ৫ দিন ৩০-৪০ মিনিট হাঁটবেন।')">হাঁটবেন</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertAdvice('নিয়মিত ব্যায়াম করুন।')">ব্যায়াম</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertAdvice('পরিমিত ঘুম প্রয়োজন।')">ঘুম</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Next Appointment -->
                        <div class="section-title mt-3">
                            <span class="title-icon amber"><i class="fas fa-calendar-alt"></i></span> Next Appointment
                        </div>
                        <div class="section-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <button type="button" class="btn-next-appt" onclick="setNextAppointment(3, this)">3 days</button>
                                        <button type="button" class="btn-next-appt" onclick="setNextAppointment(5, this)">5 days</button>
                                        <button type="button" class="btn-next-appt" onclick="setNextAppointment(7, this)">7 days</button>
                                        <button type="button" class="btn-next-appt" onclick="setNextAppointment(10, this)">10 days</button>
                                        <button type="button" class="btn-next-appt" onclick="setNextAppointment(15, this)">15 days</button>
                                        <button type="button" class="btn-next-appt" onclick="setNextAppointment(20, this)">20 days</button>
                                        <button type="button" class="btn-next-appt" onclick="setNextAppointment(30, this)">30 days</button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label style="font-size:11px;color:#64748b;display:block;">Custom Days</label>
                                            <input type="number" class="form-control-simple" name="follow_up_days" id="follow_up_days" placeholder="0" value="0" style="border-bottom:1px solid #d1d5db;width:100%;">
                                        </div>
                                        <div class="col-md-6">
                                            <label style="font-size:11px;color:#64748b;display:block;">Appointment Date</label>
                                            <input type="date" class="form-control-simple" id="appointment_date" name="appointment_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" style="border-bottom:1px solid #d1d5db;width:100%;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4 FOOTER -->
                        <div class="tab-footer">
                            <div class="btn-group">
                                <button type="button" class="btn-tab btn-cancel" onclick="switchTab('tab3')">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="btn-tab btn-next" onclick="saveAndGoNext()">
                                    Save & Next <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                            <div class="btn-group">
                                <a href="<?php echo BASE_URL; ?>/prescriptions" class="btn-tab btn-cancel">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TAB 5: VACCINATIONS & LAB - FINAL SAVE -->
                    <!-- ============================================================ -->
                    <div class="tab-content" id="tab5">
                        <!-- Add New Vaccine Section -->
                        <div class="section-title">
                            <span class="title-icon green"><i class="fas fa-syringe"></i></span> Add New Vaccine
                            <button type="button" class="btn-add-row ms-2" onclick="addVaccinationRow()">
                                <i class="fas fa-plus"></i> Add Vaccine
                            </button>
                        </div>
                        <div class="section-content">
                            <div id="newVaccineContainer">
                                <div class="vaccination-item row g-2 align-items-end mb-2" data-vac-index="0">
                                    <div class="col-md-3">
                                        <label class="form-label" style="font-size:10px;color:#64748b;">Vaccine Name</label>
                                        <input type="text" class="form-control-simple" name="vaccinations[0][vaccine_name]" placeholder="e.g. Hepatitis B">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label" style="font-size:10px;color:#64748b;">Dose</label>
                                        <select class="form-control-simple" name="vaccinations[0][dose]" style="width:100%;background:transparent;">
                                            <option value="">Select Dose</option>
                                            <option value="Dose 1">Dose 1</option>
                                            <option value="Dose 2">Dose 2</option>
                                            <option value="Dose 3">Dose 3</option>
                                            <option value="Dose 4">Dose 4</option>
                                            <option value="Dose 5">Dose 5</option>
                                            <option value="Dose 6">Dose 6</option>
                                            <option value="Dose 7">Dose 7</option>
                                            <option value="Dose 8">Dose 8</option>
                                            <option value="Booster Dose">Booster Dose</option>
                                            <option value="Advance Dose">Advance Dose</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label" style="font-size:10px;color:#64748b;">Date Given</label>
                                        <input type="date" class="form-control-simple" name="vaccinations[0][date_given]">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label" style="font-size:10px;color:#64748b;">Next Due</label>
                                        <input type="date" class="form-control-simple" name="vaccinations[0][next_due]">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label" style="font-size:10px;color:#64748b;">Batch / Lot</label>
                                        <input type="text" class="form-control-simple" name="vaccinations[0][batch_number]" placeholder="Batch">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label" style="font-size:10px;color:#64748b;">Site</label>
                                        <input type="text" class="form-control-simple" name="vaccinations[0][site]" placeholder="Left deltoid">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label" style="font-size:10px;color:#64748b;">Administered By</label>
                                        <input type="text" class="form-control-simple" name="vaccinations[0][administered_by]" placeholder="Dr. Name">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label" style="font-size:10px;color:#64748b;">Notes</label>
                                        <input type="text" class="form-control-simple" name="vaccinations[0][notes]" placeholder="Notes">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn-remove-row" onclick="removeVaccinationRow(this)" style="margin-top:18px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-add-row mt-2" onclick="addVaccinationRow()">
                                <i class="fas fa-plus"></i> Add Another Vaccine
                            </button>
                        </div>

                        <!-- Previous Vaccination Record -->
                        <div class="section-title mt-4">
                            <span class="title-icon green"><i class="fas fa-history"></i></span> Previous Vaccination Record
                            <small class="text-muted ms-2" style="font-size:10px;">(Historical data - read only)</small>
                        </div>
                        <div class="section-content">
                            <?php 
                            $vaccinations = $vaccinations ?? [];
                            if (!empty($vaccinations)): 
                            ?>
                            <div class="table-responsive" style="max-height:250px; overflow-y:auto; border:1px solid #e5e7eb; border-radius:6px;">
                                <table class="table table-bordered table-sm table-striped" style="font-size:11px; margin-bottom:0;">
                                    <thead>
                                        <tr>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;">Vaccine</th>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;">Dose</th>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;">Date Given</th>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;">Next Due</th>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;">Batch/Lot</th>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;">Site</th>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;">Admin By</th>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vaccinations as $vac): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($vac['vaccine_name'] ?? ''); ?></strong></td>
                                            <td><?php echo htmlspecialchars($vac['dose'] ?? $vac['dose_number'] ?? ''); ?></td>
                                            <td><?php echo !empty($vac['date_given']) && $vac['date_given'] != '0000-00-00' ? date('d-m-Y', strtotime($vac['date_given'])) : '-'; ?></td>
                                            <td>
                                                <?php 
                                                $nextDue = $vac['next_due'] ?? $vac['next_due_date'] ?? '';
                                                if (!empty($nextDue) && $nextDue != '0000-00-00'):
                                                    $today = new DateTime();
                                                    $dueDate = new DateTime($nextDue);
                                                    $diff = $today->diff($dueDate);
                                                    $days = (int)$diff->format('%r%a');
                                                    if ($days < 0):
                                                        echo '<span class="badge bg-danger">' . date('d-m-Y', strtotime($nextDue)) . ' (Overdue)</span>';
                                                    elseif ($days <= 7):
                                                        echo '<span class="badge bg-warning text-dark">' . date('d-m-Y', strtotime($nextDue)) . ' (Due in ' . $days . 'd)</span>';
                                                    else:
                                                        echo date('d-m-Y', strtotime($nextDue));
                                                    endif;
                                                else:
                                                    echo '-';
                                                endif;
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($vac['batch_number'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($vac['site'] ?? $vac['injection_site'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($vac['administered_by'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($vac['notes'] ?? ''); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-muted py-2">No previous vaccination records found for this patient.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Lab History Section -->
                        <div class="section-title mt-4">
                            <span class="title-icon indigo"><i class="fas fa-flask"></i></span> Previous Lab Results
                        </div>
                        <div class="section-content">
                            <?php 
                            $labHistory = $labHistory ?? ['test_names' => [], 'results' => []];
                            if (!empty($labHistory['test_names']) && !empty($labHistory['results'])): 
                            ?>
                            <div class="table-responsive" style="max-height:250px; overflow-y:auto; border:1px solid #e5e7eb; border-radius:6px;">
                                <table class="table table-bordered table-sm table-striped" style="font-size:11px; margin-bottom:0;">
                                    <thead>
                                        <tr>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;">Date</th>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;">Source</th>
                                            <?php foreach ($labHistory['test_names'] as $testName): ?>
                                            <th style="position:sticky;top:0;background:#f8fafc;z-index:2;"><?php echo htmlspecialchars($testName); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($labHistory['results'] as $dateKey => $row): ?>
                                        <tr>
                                            <td><strong><?php echo $row['date']; ?></strong></td>
                                            <td>
                                                <?php if ($row['source'] == 'manual'): ?>
                                                    <span class="badge bg-info text-white">Manual Entry</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Lab Order</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php foreach ($labHistory['test_names'] as $testName): ?>
                                            <td>
                                                <?php if (isset($row['tests'][$testName])): 
                                                    $test = $row['tests'][$testName];
                                                    $value = $test['value'] . ($test['unit'] ? ' ' . $test['unit'] : '');
                                                    $class = $test['is_abnormal'] ? 'text-danger fw-bold' : 'text-success';
                                                ?>
                                                    <span class="<?php echo $class; ?>"><?php echo htmlspecialchars($value); ?></span>
                                                    <?php if ($test['is_abnormal']): ?>
                                                        <span class="badge bg-danger">Abnormal</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Showing recent lab results. <span class="badge bg-info">Manual</span> indicates manually entered results.</small>
                            <?php else: ?>
                            <div class="text-muted py-2">No previous lab test results found for this patient.</div>
                            <?php endif; ?>
                        </div>

                        <!-- TAB 5 FOOTER - FINAL SAVE -->
                        <div class="tab-footer">
                            <div class="btn-group">
                                <button type="button" class="btn-tab btn-cancel" onclick="switchTab('tab4')">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn-tab btn-save" id="finalSaveBtn">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button type="button" class="btn-tab btn-complete" onclick="completePrescription();">
                                    <i class="fas fa-check-circle"></i> Complete
                                </button>
                            </div>
                            <div class="btn-group">
                                <a href="<?php echo BASE_URL; ?>/prescriptions" class="btn-tab btn-cancel">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden Inputs -->
                    <input type="hidden" name="final_save" id="finalSave" value="0">
                    <input type="hidden" name="complete" id="complete" value="0">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="prescription_id" id="prescriptionId" value="<?php echo $prescription['id'] ?? ''; ?>">
                    <input type="hidden" name="action_type" id="actionType" value="save">
                    <input type="hidden" name="prescription_status" id="prescriptionStatus" value="<?php echo $prescription['status'] ?? 'draft'; ?>">
                    <button type="submit" id="submitBtn" style="display: none;"></button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODALS -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="loadPreviousModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-history"></i> Load Previous Prescriptions</h5>
                <button class="modal-close" onclick="closeModal('loadPreviousModal')">&times;</button>
            </div>
            <div class="modal-body" id="prescriptionList">
                <div class="text-center py-3">Loading...</div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="templateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-file-alt"></i> <span id="templateModalTitle">Templates</span></h5>
                <button class="modal-close" onclick="closeModal('templateModal')">&times;</button>
            </div>
            <div class="modal-body" id="templateList">
                <div class="text-center py-3">Loading...</div>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <!-- ============================================================ -->
    <!-- SCRIPTS -->
    <!-- ============================================================ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // ================================================================
    // NOTE: This file contains the complete JavaScript implementation
    // For the full file, please see the complete create.php above
    // ================================================================
    
    const BASE_URL = '<?php echo BASE_URL; ?>';

    // ================================================================
    // DROPDOWN OPTIONS - FETCHED FROM DATABASE VIA PHP
    // ================================================================
    
    // These arrays are populated from the database via PHP
    const FOOD_RELATION_OPTIONS = <?php echo $foodRelationJson; ?>;
    const FREQUENCY_OPTIONS = <?php echo $frequencyJson; ?>;
    const DURATION_OPTIONS = <?php echo $durationJson; ?>;
    const INSTRUCTION_OPTIONS = <?php echo $instructionJson; ?>;
    const DOSAGE_FORM_OPTIONS = <?php echo $dosageFormJson; ?>;

    // ================================================================
    // GET DOSAGE FORM HTML - Using database options
    // ================================================================
    function getDosageFormHtml(namePrefix, selectedValue) {
        let html = `<select class="form-control-simple" name="${namePrefix}[dosage]" style="width:100%;background:transparent;">`;
        DOSAGE_FORM_OPTIONS.forEach(opt => {
            const selected = (opt.option_value === selectedValue) ? 'selected' : '';
            html += `<option value="${opt.option_value}" ${selected}>${opt.display_label}</option>`;
        });
        html += `</select>`;
        return html;
    }

    // ================================================================
    // GET FOOD RELATION HTML - Using database options
    // ================================================================
    function getFoodRelationHtml(namePrefix, selectedValue) {
        let html = `<select class="form-control-simple" name="${namePrefix}[relation_to_food]" style="width:100%;background:transparent;">`;
        FOOD_RELATION_OPTIONS.forEach(opt => {
            const selected = (opt.option_value === selectedValue) ? 'selected' : '';
            html += `<option value="${opt.option_value}" ${selected}>${opt.display_label}</option>`;
        });
        html += `</select>`;
        return html;
    }

    // ================================================================
    // GET FREQUENCY HTML - Using database options
    // ================================================================
    function getFrequencyHtml(namePrefix, selectedValue) {
        let html = `<select class="form-control-simple" name="${namePrefix}" style="flex:1;">`;
        FREQUENCY_OPTIONS.forEach(opt => {
            const selected = (opt.option_value === selectedValue) ? 'selected' : '';
            html += `<option value="${opt.option_value}" ${selected}>${opt.display_label}</option>`;
        });
        html += `</select>`;
        return html;
    }

    // ================================================================
    // GET DURATION HTML - Using database options
    // ================================================================
    function getDurationHtml(namePrefix, selectedValue) {
        let html = `<select class="form-control-simple" name="${namePrefix}" style="flex:1;">`;
        DURATION_OPTIONS.forEach(opt => {
            const selected = (opt.option_value === selectedValue) ? 'selected' : '';
            html += `<option value="${opt.option_value}" ${selected}>${opt.display_label}</option>`;
        });
        html += `</select>`;
        return html;
    }

    // ================================================================
    // GET INSTRUCTION HTML - Using database options
    // ================================================================
    function getInstructionHtml(namePrefix, selectedValue) {
        let html = `<select class="form-control-simple" name="${namePrefix}" style="flex:1.5;">`;
        INSTRUCTION_OPTIONS.forEach(opt => {
            const selected = (opt.option_value === selectedValue) ? 'selected' : '';
            html += `<option value="${opt.option_value}" ${selected}>${opt.display_label}</option>`;
        });
        html += `</select>`;
        return html;
    }

    // ================================================================
    // SAMPLE DRUG LIST (Fallback only)
    // ================================================================
    const SAMPLE_DRUGS = [
        { id: 1, name: 'Paracetamol', strength: '500mg', dosage_form: 'Tablet', code: 'MED001' },
        { id: 2, name: 'Azithromycin', strength: '500mg', dosage_form: 'Capsule', code: 'MED002' },
        { id: 3, name: 'Metformin', strength: '500mg', dosage_form: 'Tablet', code: 'MED003' },
        { id: 4, name: 'Amoxicillin', strength: '500mg', dosage_form: 'Capsule', code: 'MED004' },
        { id: 5, name: 'Omeprazole', strength: '20mg', dosage_form: 'Capsule', code: 'MED005' },
        { id: 6, name: 'Losartan', strength: '50mg', dosage_form: 'Tablet', code: 'MED006' },
        { id: 7, name: 'Vitamin C', strength: '500mg', dosage_form: 'Tablet', code: 'MED007' },
        { id: 8, name: 'Cetirizine', strength: '10mg', dosage_form: 'Tablet', code: 'MED008' },
        { id: 9, name: 'Napa', strength: '500mg', dosage_form: 'Tablet', code: 'MED009' },
        { id: 10, name: 'Cefalexin', strength: '500mg', dosage_form: 'Capsule', code: 'MED010' },
        { id: 11, name: 'Doxycycline', strength: '100mg', dosage_form: 'Capsule', code: 'MED011' },
        { id: 12, name: 'Ciprofloxacin', strength: '500mg', dosage_form: 'Tablet', code: 'MED012' },
        { id: 13, name: 'Pantoprazole', strength: '40mg', dosage_form: 'Tablet', code: 'MED013' },
        { id: 14, name: 'Amlodipine', strength: '5mg', dosage_form: 'Tablet', code: 'MED014' },
        { id: 15, name: 'Atorvastatin', strength: '10mg', dosage_form: 'Tablet', code: 'MED015' },
        { id: 16, name: 'Clopidogrel', strength: '75mg', dosage_form: 'Tablet', code: 'MED016' },
        { id: 17, name: 'Enalapril', strength: '5mg', dosage_form: 'Tablet', code: 'MED017' },
        { id: 18, name: 'Furosemide', strength: '40mg', dosage_form: 'Tablet', code: 'MED018' },
        { id: 19, name: 'Hydrochlorothiazide', strength: '25mg', dosage_form: 'Tablet', code: 'MED019' },
        { id: 20, name: 'Ibuprofen', strength: '400mg', dosage_form: 'Tablet', code: 'MED020' }
    ];

    // ================================================================
    // COUNTERS
    // ================================================================
    let complaintCounter = 1;
    let drugHistoryCounter = 1;
    let diseaseHistoryCounter = 1;
    let investigationCounter = 1;
    let vaccinationCounter = 1;
    let labTestCounter = 1;
    let medicineCounter = 0;
    let currentTab = 'tab1';
    let drugCache = {};
    let labTestCache = {};
    let usedDrugIndices = [];
    let isSaving = false;
    let currentPrescriptionId = null;
    let loadedPrescriptionId = null;

    // ================================================================
    // BMI CALCULATION
    // ================================================================
    function calculateBMI() {
        const weightInput = document.getElementById('vitalWeight');
        const heightInput = document.getElementById('vitalHeight');
        const bmiInput = document.getElementById('vitalBMI');
        
        if (!weightInput || !heightInput || !bmiInput) return;
        
        const weight = parseFloat(weightInput.value);
        const heightCm = parseFloat(heightInput.value);
        
        if (weight > 0 && heightCm > 0) {
            const heightM = heightCm / 100;
            const bmi = weight / (heightM * heightM);
            if (isFinite(bmi) && bmi > 0) {
                bmiInput.value = bmi.toFixed(1);
            } else {
                bmiInput.value = '';
            }
        } else {
            bmiInput.value = '';
        }
    }

    // ================================================================
    // TOAST NOTIFICATIONS
    // ================================================================
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) {
            console.log('Toast:', message);
            alert(message);
            return;
        }
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ================================================================
    // CREATE MEDICINE DETAIL ROW - Using database options
    // ================================================================
    function createMedicineDetailRow(parentIndex, detailIndex, freqValue, durValue, instValue) {
        const row = document.createElement('div');
        row.className = 'medicine-detail-row';
        row.setAttribute('data-parent-index', parentIndex);
        row.setAttribute('data-detail-index', detailIndex);
        
        const freqName = `medicines[${parentIndex}][details][${detailIndex}][frequency]`;
        const durName = `medicines[${parentIndex}][details][${detailIndex}][duration]`;
        const instName = `medicines[${parentIndex}][details][${detailIndex}][instruction]`;
        
        row.innerHTML = `
            ${getFrequencyHtml(freqName, freqValue || '')}
            ${getDurationHtml(durName, durValue || '')}
            ${getInstructionHtml(instName, instValue || '')}
            <button type="button" class="btn-remove-row" onclick="removeDetailRow(this)" style="flex-shrink:0;width:24px;height:24px;background:#fee2e2;color:#dc2626;border:none;border-radius:4px;cursor:pointer;font-size:10px;display:inline-flex;align-items:center;justify-content:center;">
                <i class="fas fa-times"></i>
            </button>
        `;
        return row;
    }

    // ================================================================
    // REMOVE DETAIL ROW
    // ================================================================
    function removeDetailRow(button) {
        const row = button.closest('.medicine-detail-row');
        if (!row) {
            showToast('Error: Could not find detail row.', 'error');
            return;
        }
        
        const container = row.parentElement;
        if (!container) {
            showToast('Error: Could not find container.', 'error');
            return;
        }
        
        if (container.children.length > 1) {
            row.remove();
            showToast('Detail row removed successfully.', 'info');
        } else {
            showToast('At least one detail is required.', 'warning');
        }
    }

    // ================================================================
    // ADD DETAIL ROW
    // ================================================================
    function addDetailRow(parentIndex) {
        let container = document.querySelector(`.medicine-details-wrapper[data-parent-index="${parentIndex}"]`);
        
        if (!container) {
            const allWrappers = document.querySelectorAll('.medicine-details-wrapper');
            for (let i = 0; i < allWrappers.length; i++) {
                if (allWrappers[i].getAttribute('data-parent-index') == parentIndex) {
                    container = allWrappers[i];
                    break;
                }
            }
        }
        
        if (!container) {
            showToast('Error: Could not find detail container.', 'error');
            return;
        }
        
        const detailIndex = container.children.length;
        const row = createMedicineDetailRow(parentIndex, detailIndex, '', '', '');
        container.appendChild(row);
        showToast('Detail row added successfully!', 'success');
    }

    // ================================================================
    // REMOVE MEDICINE ROW
    // ================================================================
    function removeMedicineRow(button) {
        const medicineItem = button.closest('.medicine-item');
        if (!medicineItem) {
            showToast('Error: Could not find medicine item.', 'error');
            return;
        }
        
        const container = document.getElementById('medicinesContainer');
        if (!container) return;
        
        if (container.children.length > 1) {
            const drugIndex = parseInt(medicineItem.dataset.drugIndex);
            const idx = usedDrugIndices.indexOf(drugIndex);
            if (idx > -1) {
                usedDrugIndices.splice(idx, 1);
            }
            medicineItem.remove();
            updateMedicineCount();
            showToast('Medicine removed successfully.', 'info');
        } else {
            showToast('At least one medicine is required.', 'warning');
        }
    }

    // ================================================================
    // GET NEXT AVAILABLE DRUG
    // ================================================================
    function getNextAvailableDrug() {
        for (let i = 0; i < SAMPLE_DRUGS.length; i++) {
            if (!usedDrugIndices.includes(i)) {
                return { index: i, drug: SAMPLE_DRUGS[i] };
            }
        }
        return null;
    }

    // ================================================================
    // CREATE MEDICINE ITEM
    // ================================================================
    function createMedicineItem(drugData, index) {
        const drug = drugData.drug;
        const drugIndex = drugData.index;
        const container = document.getElementById('medicinesContainer');
        if (!container) return null;
        
        const row = document.createElement('div');
        row.className = 'medicine-item';
        row.setAttribute('data-medicine-index', index);
        row.setAttribute('data-drug-index', drugIndex);
        
        let displayName = drug.name;
        if (drug.strength && !drug.name.includes(drug.strength)) {
            displayName = drug.name + ' ' + drug.strength;
        }
        
        let detailsHtml = '';
        const defaultDetails = [
            { freq: 'Once daily', dur: '5 days', inst: 'After meal' },
            { freq: 'Twice daily', dur: '7 days', inst: 'Before meal' },
            { freq: 'Three times daily', dur: '10 days', inst: 'After meal' }
        ];
        
        for (let d = 0; d < 3; d++) {
            const detail = defaultDetails[d];
            const rowHtml = createMedicineDetailRow(index, d, detail.freq, detail.dur, detail.inst);
            detailsHtml += rowHtml.outerHTML;
        }
        
        row.innerHTML = `
            <div class="row">
                <div class="col-md-4 mb-1">
                    <label class="form-label">Drug <span class="text-danger">*</span></label>
                    <div class="drug-select-wrapper">
                        <input type="text" class="drug-search-input form-control-simple" 
                               placeholder="Search medicine..." 
                               value="${displayName}"
                               data-medicine-index="${index}"
                               autocomplete="off">
                        <input type="hidden" name="medicines[${index}][drug_id]" value="${drug.id}">
                        <div class="drug-dropdown" id="drugDropdown_${index}"></div>
                    </div>
                </div>
                <div class="col-md-3 mb-1">
                    <label class="form-label">Dosage Form</label>
                    ${getDosageFormHtml(`medicines[${index}]`, drug.dosage_form)}
                </div>
                <div class="col-md-4 mb-1">
                    <label class="form-label">Relation to Food</label>
                    ${getFoodRelationHtml(`medicines[${index}]`, '')}
                </div>
                <div class="col-md-1 mb-1 text-end">
                    <button type="button" class="btn-remove-row" onclick="removeMedicineRow(this)" style="margin-top:18px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <label class="form-label" style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:0.3px;">Frequency & Duration Details</label>
                    <div class="medicine-details-wrapper" data-parent-index="${index}">
                        ${detailsHtml}
                    </div>
                    <button type="button" class="btn-add-row" onclick="addDetailRow('${index}')" style="margin-top:4px;height:22px;font-size:10px;padding:0 8px;">
                        <i class="fas fa-plus"></i> Add Detail
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(row);
        
        const drugInput = row.querySelector('.drug-search-input');
        if (drugInput) {
            drugInput.dataset.medicineIndex = index;
            initDrugSearch(drugInput);
        }
        
        if (drugIndex >= 0 && !usedDrugIndices.includes(drugIndex)) {
            usedDrugIndices.push(drugIndex);
        }
        return row;
    }

    // ================================================================
    // INITIALIZE MEDICINES
    // ================================================================
    function initializeMedicines() {
        const container = document.getElementById('medicinesContainer');
        if (!container) return;
        
        container.innerHTML = '';
        usedDrugIndices = [];
        medicineCounter = 0;
        
        const firstDrug = getNextAvailableDrug();
        if (firstDrug) {
            createMedicineItem(firstDrug, medicineCounter);
            medicineCounter++;
        }
        
        updateMedicineCount();
    }

    // ================================================================
    // ADD NEXT MEDICINE
    // ================================================================
    function addNextMedicine() {
        const nextDrug = getNextAvailableDrug();
        if (!nextDrug) {
            showToast('All 20 sample drugs have been added!', 'warning');
            return;
        }
        
        createMedicineItem(nextDrug, medicineCounter);
        medicineCounter++;
        updateMedicineCount();
        showToast(`Added ${nextDrug.drug.name} ${nextDrug.drug.strength}`, 'success');
    }

    // ================================================================
    // UPDATE MEDICINE COUNT
    // ================================================================
    function updateMedicineCount() {
        const display = document.getElementById('medicineCountDisplay');
        if (display) {
            display.textContent = `${usedDrugIndices.length} / ${SAMPLE_DRUGS.length}`;
        }
    }

    // ================================================================
    // FETCH DRUGS FROM DATABASE
    // ================================================================
    function fetchDrugs(query, dropdown, input, hiddenInput) {
        if (!dropdown) return;
        
        dropdown.innerHTML = '<div class="no-results">Loading...</div>';
        dropdown.classList.add('show');
        
        const cacheKey = query || 'all';
        if (drugCache[cacheKey]) {
            renderDrugDropdown(dropdown, drugCache[cacheKey], input, hiddenInput);
            dropdown.classList.add('show');
            return;
        }
        
        var apiUrl = BASE_URL + '/prescriptions/api-medicines';
        if (query && query.length > 0) {
            apiUrl += '?q=' + encodeURIComponent(query);
        } else {
            apiUrl += '?q=';
        }
        
        $.ajax({
            url: apiUrl,
            type: 'GET',
            dataType: 'json',
            timeout: 15000,
            success: function(response) {
                if (response && Array.isArray(response) && response.length > 0) {
                    drugCache[cacheKey] = response;
                    renderDrugDropdown(dropdown, response, input, hiddenInput);
                    dropdown.classList.add('show');
                } else if (response && response.data && Array.isArray(response.data) && response.data.length > 0) {
                    drugCache[cacheKey] = response.data;
                    renderDrugDropdown(dropdown, response.data, input, hiddenInput);
                    dropdown.classList.add('show');
                } else {
                    if (query && query.length > 0) {
                        fetchDrugs('', dropdown, input, hiddenInput);
                    } else {
                        dropdown.innerHTML = '<div class="no-results">No medicines found</div>';
                        dropdown.classList.add('show');
                    }
                }
            },
            error: function() {
                const results = SAMPLE_DRUGS.filter(d => 
                    !query || d.name.toLowerCase().includes(query.toLowerCase()) || 
                    d.strength.toLowerCase().includes(query.toLowerCase())
                );
                if (results.length > 0) {
                    drugCache['fallback'] = results;
                    renderDrugDropdown(dropdown, results, input, hiddenInput);
                    dropdown.classList.add('show');
                } else {
                    dropdown.innerHTML = '<div class="no-results">Unable to load medicines. Please try again.</div>';
                    dropdown.classList.add('show');
                }
            }
        });
    }

    // ================================================================
    // DRUG SEARCH DROPDOWN
    // ================================================================
    function initDrugSearch(input) {
        const wrapper = input.closest('.drug-select-wrapper');
        const dropdown = wrapper.querySelector('.drug-dropdown');
        const hiddenInput = wrapper.querySelector('input[type="hidden"]');
        
        if (!dropdown) return;
        
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        input.addEventListener('focus', function() {
            const query = this.value.trim();
            fetchDrugs(query, dropdown, input, hiddenInput);
        });

        input.addEventListener('input', function() {
            const query = this.value.trim();
            fetchDrugs(query, dropdown, input, hiddenInput);
        });

        input.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.drug-item');
            if (items.length === 0) return;
            
            let currentIndex = -1;
            items.forEach((item, idx) => {
                if (item.classList.contains('active')) {
                    currentIndex = idx;
                    item.classList.remove('active');
                }
            });

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = Math.min(currentIndex + 1, items.length - 1);
                items[nextIndex].classList.add('active');
                items[nextIndex].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = Math.max(currentIndex - 1, 0);
                items[prevIndex].classList.add('active');
                items[prevIndex].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const activeItem = dropdown.querySelector('.drug-item.active');
                if (activeItem) {
                    activeItem.click();
                }
            } else if (e.key === 'Escape') {
                dropdown.classList.remove('show');
            }
        });
    }

    // ================================================================
    // RENDER DRUG DROPDOWN
    // ================================================================
    function renderDrugDropdown(dropdown, data, input, hiddenInput) {
        if (!dropdown) return;
        
        if (!data || data.length === 0) {
            dropdown.innerHTML = '<div class="no-results">No medicines found</div>';
            return;
        }

        let html = '';
        data.forEach(function(item) {
            const id = item.id || item.medicine_id || 0;
            const name = item.medicine_name || item.name || 'Unknown';
            const strength = item.strength || '';
            const dosageForm = item.dosage_form || '';
            const code = item.medicine_code || item.code || '';
            
            let displayName = name;
            if (strength && !name.includes(strength)) {
                displayName = name + ' ' + strength;
            }
            
            html += `
                <div class="drug-item" data-id="${id}" data-name="${name}" data-strength="${strength}" data-dosage-form="${dosageForm}" data-code="${code}">
                    <span>
                        <span class="drug-name">${displayName}</span>
                        ${dosageForm ? `<span class="drug-dosage-form">(${dosageForm})</span>` : ''}
                        ${code ? `<span class="drug-code">${code}</span>` : ''}
                    </span>
                    <span class="drug-strength">${strength || ''}</span>
                </div>
            `;
        });
        dropdown.innerHTML = html;

        dropdown.querySelectorAll('.drug-item').forEach(function(item) {
            item.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const strength = this.dataset.strength || '';
                const dosageForm = this.dataset.dosageForm || '';
                
                let displayName = name;
                if (strength && !name.includes(strength)) {
                    displayName = name + ' ' + strength;
                }
                
                input.value = displayName;
                hiddenInput.value = id;
                dropdown.classList.remove('show');
                
                const parentRow = input.closest('.medicine-item');
                if (parentRow && dosageForm) {
                    const dosageSelect = parentRow.querySelector('select[name*="[dosage]"]');
                    if (dosageSelect) {
                        const options = dosageSelect.options;
                        for (let i = 0; i < options.length; i++) {
                            if (options[i].value.toLowerCase() === dosageForm.toLowerCase()) {
                                dosageSelect.value = options[i].value;
                                break;
                            }
                        }
                    }
                }
                
                input.dispatchEvent(new Event('change'));
            });

            item.addEventListener('mouseenter', function() {
                dropdown.querySelectorAll('.drug-item').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }

    // ================================================================
    // TREATMENT HISTORY FUNCTIONS
    // ================================================================
    let treatmentHistoryCounter = 1;

    function addTreatmentHistory() {
        const container = document.getElementById('treatmentHistoryContainer');
        if (!container) return;
        
        const row = document.createElement('div');
        row.className = 'history-item';
        row.innerHTML = `
            <span class="bullet">•</span>
            <input type="text" class="form-control-simple" name="treatment_history[${treatmentHistoryCounter}][name]" placeholder="Enter treatment..." style="flex:1;">
            <input type="text" class="form-control-simple" name="treatment_history[${treatmentHistoryCounter}][duration]" placeholder="Duration" style="min-width:80px;max-width:120px;">
            <input type="text" class="form-control-simple" name="treatment_history[${treatmentHistoryCounter}][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
            <button type="button" class="btn-remove-row" onclick="removeRow(this, 'treatmentHistoryContainer')">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(row);
        treatmentHistoryCounter++;
    }

    // ================================================================
    // REMOVE ROW (for complaints, history, etc.)
    // ================================================================
    function removeRow(button, containerId) {
        const container = document.getElementById(containerId);
        if (container.children.length > 1) {
            const row = button.closest('.complaint-item') || button.closest('.history-item');
            if (row) row.remove();
        } else {
            showToast('At least one row is required.', 'warning');
        }
    }

    // ================================================================
    // ADD FUNCTIONS - Complaints, History, etc.
    // ================================================================
    function addComplaint() {
        const container = document.getElementById('complaintsContainer');
        const row = document.createElement('div');
        row.className = 'complaint-item';
        row.innerHTML = `
            <span class="bullet">•</span>
            <input type="text" class="form-control-simple" name="complaints[${complaintCounter}][text]" placeholder="Enter complaint..." style="flex:1;">
            <input type="text" class="form-control-simple" name="complaints[${complaintCounter}][duration]" placeholder="Duration" style="min-width:80px;max-width:120px;">
            <input type="text" class="form-control-simple" name="complaints[${complaintCounter}][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
            <button type="button" class="btn-remove-row" onclick="removeRow(this, 'complaintsContainer')">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(row);
        complaintCounter++;
    }

    function addDrugHistory() {
        const container = document.getElementById('drugHistoryContainer');
        const row = document.createElement('div');
        row.className = 'history-item';
        row.innerHTML = `
            <span class="bullet">•</span>
            <input type="text" class="form-control-simple" name="drug_history[${drugHistoryCounter}][name]" placeholder="Drug name..." style="flex:1;">
            <input type="text" class="form-control-simple" name="drug_history[${drugHistoryCounter}][duration]" placeholder="Duration" style="min-width:80px;max-width:120px;">
            <input type="text" class="form-control-simple" name="drug_history[${drugHistoryCounter}][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
            <button type="button" class="btn-remove-row" onclick="removeRow(this, 'drugHistoryContainer')">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(row);
        drugHistoryCounter++;
    }

    function addDiseaseHistory() {
        const container = document.getElementById('diseaseHistoryContainer');
        const row = document.createElement('div');
        row.className = 'history-item';
        row.innerHTML = `
            <span class="bullet">•</span>
            <input type="text" class="form-control-simple" name="disease_history[${diseaseHistoryCounter}][name]" placeholder="Disease..." style="flex:1;">
            <input type="text" class="form-control-simple" name="disease_history[${diseaseHistoryCounter}][duration]" placeholder="Duration" style="min-width:80px;max-width:120px;">
            <input type="text" class="form-control-simple" name="disease_history[${diseaseHistoryCounter}][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
            <button type="button" class="btn-remove-row" onclick="removeRow(this, 'diseaseHistoryContainer')">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(row);
        diseaseHistoryCounter++;
    }

    function addInvestigation() {
        const container = document.getElementById('investigationsContainer');
        const row = document.createElement('div');
        row.className = 'history-item';
        row.innerHTML = `
            <span class="bullet">•</span>
            <input type="text" class="form-control-simple" name="investigations[${investigationCounter}][name]" placeholder="Investigation..." style="flex:1;">
            <input type="text" class="form-control-simple" name="investigations[${investigationCounter}][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
            <button type="button" class="btn-remove-row" onclick="removeRow(this, 'investigationsContainer')">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(row);
        investigationCounter++;
    }

    // ================================================================
    // VACCINATION FUNCTIONS
    // ================================================================
    function addVaccinationRow() {
        const container = document.getElementById('newVaccineContainer');
        const row = document.createElement('div');
        row.className = 'vaccination-item row g-2 align-items-end mb-2';
        row.setAttribute('data-vac-index', vaccinationCounter);
        row.innerHTML = `
            <div class="col-md-3">
                <label class="form-label" style="font-size:10px;color:#64748b;">Vaccine Name</label>
                <input type="text" class="form-control-simple" name="vaccinations[${vaccinationCounter}][vaccine_name]" placeholder="e.g. Hepatitis B">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:10px;color:#64748b;">Dose</label>
                <select class="form-control-simple" name="vaccinations[${vaccinationCounter}][dose]" style="width:100%;background:transparent;">
                    <option value="">Select Dose</option>
                    <option value="Dose 1">Dose 1</option>
                    <option value="Dose 2">Dose 2</option>
                    <option value="Dose 3">Dose 3</option>
                    <option value="Dose 4">Dose 4</option>
                    <option value="Dose 5">Dose 5</option>
                    <option value="Dose 6">Dose 6</option>
                    <option value="Dose 7">Dose 7</option>
                    <option value="Dose 8">Dose 8</option>
                    <option value="Booster Dose">Booster Dose</option>
                    <option value="Advance Dose">Advance Dose</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:10px;color:#64748b;">Date Given</label>
                <input type="date" class="form-control-simple" name="vaccinations[${vaccinationCounter}][date_given]">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:10px;color:#64748b;">Next Due</label>
                <input type="date" class="form-control-simple" name="vaccinations[${vaccinationCounter}][next_due]">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:10px;color:#64748b;">Batch / Lot</label>
                <input type="text" class="form-control-simple" name="vaccinations[${vaccinationCounter}][batch_number]" placeholder="Batch">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:10px;color:#64748b;">Site</label>
                <input type="text" class="form-control-simple" name="vaccinations[${vaccinationCounter}][site]" placeholder="Left deltoid">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:10px;color:#64748b;">Administered By</label>
                <input type="text" class="form-control-simple" name="vaccinations[${vaccinationCounter}][administered_by]" placeholder="Dr. Name">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:10px;color:#64748b;">Notes</label>
                <input type="text" class="form-control-simple" name="vaccinations[${vaccinationCounter}][notes]" placeholder="Notes">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn-remove-row" onclick="removeVaccinationRow(this)" style="margin-top:18px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
        vaccinationCounter++;
    }

    function removeVaccinationRow(button) {
        const container = document.getElementById('newVaccineContainer');
        if (container.children.length > 1) {
            button.closest('.vaccination-item').remove();
        } else {
            showToast('At least one vaccination row is required.', 'warning');
        }
    }

    // ================================================================
    // LAB TEST FUNCTIONS
    // ================================================================
    function initLabTestSearch(input) {
        const wrapper = input.closest('.lab-test-select-wrapper');
        const dropdown = wrapper.querySelector('.lab-test-dropdown');
        const hiddenInput = wrapper.querySelector('input[type="hidden"]');
        const infoDiv = document.getElementById('labTestInfo_' + input.dataset.testIndex);
        
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                if (dropdown) dropdown.classList.remove('show');
            }
        });

        input.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 1) {
                if (dropdown) dropdown.classList.remove('show');
                if (hiddenInput) hiddenInput.value = '';
                if (infoDiv) {
                    infoDiv.innerHTML = '<span class="text-muted">Select a test to see details</span>';
                }
                return;
            }

            if (labTestCache[query]) {
                renderLabTestDropdown(dropdown, labTestCache[query], input, hiddenInput, infoDiv);
                if (dropdown) dropdown.classList.add('show');
                return;
            }

            if (dropdown) {
                dropdown.innerHTML = '<div class="no-results">Searching...</div>';
                dropdown.classList.add('show');
            }

            $.ajax({
                url: BASE_URL + '/api/lab-tests',
                type: 'GET',
                data: { q: query },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    if (response && Array.isArray(response) && response.length > 0) {
                        labTestCache[query] = response;
                        renderLabTestDropdown(dropdown, response, input, hiddenInput, infoDiv);
                        if (dropdown) dropdown.classList.add('show');
                    } else {
                        if (dropdown) dropdown.innerHTML = '<div class="no-results">No lab tests found</div>';
                        if (dropdown) dropdown.classList.add('show');
                    }
                },
                error: function() {
                    if (dropdown) dropdown.innerHTML = '<div class="no-results">Unable to load tests. Please try again.</div>';
                    if (dropdown) dropdown.classList.add('show');
                }
            });
        });

        input.addEventListener('keydown', function(e) {
            if (!dropdown) return;
            const items = dropdown.querySelectorAll('.lab-test-item');
            if (items.length === 0) return;
            
            let currentIndex = -1;
            items.forEach((item, idx) => {
                if (item.classList.contains('active')) {
                    currentIndex = idx;
                    item.classList.remove('active');
                }
            });

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = Math.min(currentIndex + 1, items.length - 1);
                items[nextIndex].classList.add('active');
                items[nextIndex].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = Math.max(currentIndex - 1, 0);
                items[prevIndex].classList.add('active');
                items[prevIndex].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const activeItem = dropdown.querySelector('.lab-test-item.active');
                if (activeItem) {
                    activeItem.click();
                }
            } else if (e.key === 'Escape') {
                dropdown.classList.remove('show');
            }
        });
    }

    function renderLabTestDropdown(dropdown, data, input, hiddenInput, infoDiv) {
        if (!dropdown || !data || data.length === 0) {
            if (dropdown) dropdown.innerHTML = '<div class="no-results">No lab tests found</div>';
            return;
        }

        let html = '';
        data.forEach(function(item) {
            const displayName = item.test_name || item.name || 'Unknown Test';
            const category = item.category_name || item.category || '';
            const price = item.price || item.test_price || 0;
            const code = item.test_code || item.code || '';
            
            html += `
                <div class="lab-test-item" data-id="${item.id}" data-name="${displayName}" data-price="${price}" data-category="${category}" data-code="${code}">
                    <span>
                        <span class="test-name">${displayName}</span>
                        ${category ? `<span class="test-category">(${category})</span>` : ''}
                        ${code ? `<span class="test-code">${code}</span>` : ''}
                    </span>
                    <span class="test-price">৳${parseFloat(price).toFixed(2)}</span>
                </div>
            `;
        });
        dropdown.innerHTML = html;

        dropdown.querySelectorAll('.lab-test-item').forEach(function(item) {
            item.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const price = this.dataset.price;
                const category = this.dataset.category;
                
                if (input) input.value = name;
                if (hiddenInput) hiddenInput.value = id;
                if (dropdown) dropdown.classList.remove('show');
                
                if (infoDiv) {
                    infoDiv.innerHTML = `
                        <span class="text-success"><i class="fas fa-check-circle me-1"></i> ${name}</span>
                        <span class="text-muted ms-2">| Category: ${category || 'N/A'}</span>
                        <span class="text-muted ms-2">| Price: ৳${parseFloat(price).toFixed(2)}</span>
                    `;
                }
                if (input) input.dispatchEvent(new Event('change'));
            });

            item.addEventListener('mouseenter', function() {
                dropdown.querySelectorAll('.lab-test-item').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }

    function addLabTestRow() {
        const container = document.getElementById('labTestsContainer');
        if (!container) return;
        
        const idx = labTestCounter;
        const row = document.createElement('div');
        row.className = 'lab-test-item';
        row.setAttribute('data-test-index', idx);
        row.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" style="font-size:10px;color:#64748b;">Test Name <span class="text-danger">*</span></label>
                    <div class="lab-test-select-wrapper">
                        <input type="text" class="lab-test-search-input form-control-simple" 
                               placeholder="Search lab test..." 
                               data-test-index="${idx}"
                               autocomplete="off"
                               style="width:100%;">
                        <input type="hidden" name="lab_tests[${idx}][test_id]" value="">
                        <div class="lab-test-dropdown" id="labTestDropdown_${idx}"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size:10px;color:#64748b;">Priority</label>
                    <select class="form-control-simple" name="lab_tests[${idx}][priority]" style="width:100%;background:transparent;">
                        <option value="routine" selected>Routine</option>
                        <option value="urgent">Urgent</option>
                        <option value="stat">STAT</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size:10px;color:#64748b;">Notes</label>
                    <input type="text" class="form-control-simple" name="lab_tests[${idx}][notes]" placeholder="Special instructions..." style="width:100%;">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn-remove-row" onclick="removeLabTestRow(this)" style="margin-top:18px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-1">
                <div class="col-md-12">
                    <small class="text-muted" id="labTestInfo_${idx}" style="font-size:11px;">
                        <span class="text-muted">Select a test to see details</span>
                    </small>
                </div>
            </div>
        `;
        container.appendChild(row);
        
        const testInput = row.querySelector('.lab-test-search-input');
        if (testInput) {
            testInput.dataset.testIndex = idx;
            initLabTestSearch(testInput);
        }
        labTestCounter++;
    }

    function removeLabTestRow(button) {
        const container = document.getElementById('labTestsContainer');
        if (!container) return;
        if (container.children.length > 1) {
            button.closest('.lab-test-item').remove();
        } else {
            showToast('At least one lab test row is required.', 'warning');
        }
    }

    // ================================================================
    // ADVICE FUNCTIONS
    // ================================================================
    function insertAdvice(text) {
        const textarea = document.getElementById('adviceText');
        if (textarea) {
            const currentText = textarea.value;
            textarea.value = currentText ? currentText + '\n' + text : text;
            textarea.focus();
        }
    }

    function setNextAppointment(days, button) {
        const date = new Date();
        date.setDate(date.getDate() + days);
        document.getElementById('appointment_date').value = date.toISOString().split('T')[0];
        document.getElementById('follow_up_days').value = days;
        document.querySelectorAll('.btn-next-appt').forEach(btn => {
            btn.classList.remove('active');
        });
        if (button) {
            button.classList.add('active');
        }
    }

    // ================================================================
    // TABS NAVIGATION - FIXED
    // ================================================================
    function switchTab(tabId, skipSave = false) {
        console.log('Switching to tab:', tabId);
        currentTab = tabId;
        
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
        
        const targetContent = document.getElementById(tabId);
        const targetTab = document.querySelector(`.tab-item[data-tab="${tabId}"]`);
        
        if (targetContent) {
            targetContent.classList.add('active');
        } else {
            console.error('Tab content not found:', tabId);
        }
        
        if (targetTab) {
            targetTab.classList.add('active');
        } else {
            console.error('Tab button not found:', tabId);
        }
        
        // Only save if not called from AJAX success and prescription ID exists
        if (!skipSave) {
            saveCurrentTab(tabId);
        }
    }

    // Update click handlers to not auto-save
    document.querySelectorAll('.tab-item').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            // Check if we have a prescription ID before switching
            const presId = document.getElementById('prescriptionId').value;
            if (presId) {
                switchTab(tabId);
            } else {
                // Just switch visually without saving
                switchTab(tabId, true);
            }
        });
    });

    function saveCurrentTab(tabId) {
        const patientId = document.querySelector('input[name="patient_id"]')?.value;
        const presId = document.getElementById('prescriptionId').value;
        
        if (!patientId) return;
        if (!presId) {
            // If no prescription ID, just return without trying to save
            console.log('No prescription ID yet, skipping tab save');
            return;
        }
        
        const formData = new FormData(document.getElementById('prescriptionForm'));
        formData.append('tab', tabId);
        formData.append('patient_id', patientId);
        formData.append('prescription_id', presId);
        
        $.ajax({
            url: BASE_URL + '/prescriptions/save-tab',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const json = JSON.parse(response);
                    if (json.success && json.prescription_id) {
                        document.getElementById('prescriptionId').value = json.prescription_id;
                        currentPrescriptionId = json.prescription_id;
                        loadedPrescriptionId = json.prescription_id;
                    }
                } catch(e) {}
            },
            error: function() {}
        });
    }

    // ================================================================
    // SAVE AND GO NEXT - FIXED VERSION
    // ================================================================
    function saveAndGoNext() {
        console.log('=== SAVE AND GO NEXT CALLED ===');
        console.log('Current Tab:', currentTab);
        
        if (isSaving) {
            showToast('Please wait, saving in progress...', 'warning');
            return;
        }
        
        const btn = document.querySelector('.tab-footer .btn-next');
        if (!btn) {
            showToast('Error: Save button not found.', 'error');
            return;
        }
        
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
        btn.disabled = true;
        isSaving = true;
        
        const form = document.getElementById('prescriptionForm');
        if (!form) {
            showToast('Error: Form not found.', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
            isSaving = false;
            return;
        }
        
        // Collect ALL form data
        const formData = new FormData(form);
        const nextTabIndex = parseInt(currentTab.replace('tab', '')) + 1;
        const nextTab = 'tab' + nextTabIndex;
        
        // Add required parameters
        formData.append('next_tab', nextTab);
        formData.append('final_save', '0');
        formData.append('complete', '0');
        formData.append('action_type', 'save');
        formData.append('ajax', '1');
        
        // Get current prescription ID
        let presId = document.getElementById('prescriptionId').value;
        if (!presId && loadedPrescriptionId) {
            presId = loadedPrescriptionId;
        }
        
        // Always use the save endpoint
        let url = BASE_URL + '/prescriptions/save';
        
        // If we have a prescription ID, add it to form data
        if (presId) {
            formData.append('prescription_id', presId);
        }
        
        console.log('=== REQUEST DETAILS ===');
        console.log('URL:', url);
        console.log('Prescription ID:', presId);
        console.log('Next Tab:', nextTab);
        
        // DEBUG: Log all form data
        console.log('=== FORM DATA ===');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 60000,
            dataType: 'json',
            success: function(response) {
                console.log('=== AJAX SUCCESS ===');
                console.log('Response:', response);
                
                try {
                    if (response && response.success === true) {
                        if (response.prescription_id) {
                            document.getElementById('prescriptionId').value = response.prescription_id;
                            currentPrescriptionId = response.prescription_id;
                            loadedPrescriptionId = response.prescription_id;
                            console.log('Updated Prescription ID:', response.prescription_id);
                        }
                        
                        const targetTab = response.next_tab || nextTab;
                        console.log('Switching to tab:', targetTab);
                        
                        if (document.getElementById(targetTab)) {
                            // Skip save when switching via AJAX to prevent duplicate saves
                            switchTab(targetTab, true);
                            showToast('Saved successfully!', 'success');
                        } else {
                            showToast('Saved successfully!', 'success');
                        }
                    } else {
                        console.error('Save failed:', response);
                        showToast(response.message || 'Error saving data', 'error');
                    }
                } catch(e) {
                    console.error('Error processing response:', e);
                    showToast('Error processing server response', 'error');
                }
                
                btn.innerHTML = originalText;
                btn.disabled = false;
                isSaving = false;
            },
            error: function(xhr, status, error) {
                console.error('=== AJAX ERROR ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Status Code:', xhr.status);
                
                // If 404, try using the save endpoint without update
                if (xhr.status === 404) {
                    showToast('Route not found. Please check your routes configuration.', 'error');
                } else {
                    let errorMessage = 'Error saving data: ' + error;
                    try {
                        const jsonResponse = JSON.parse(xhr.responseText);
                        if (jsonResponse && jsonResponse.message) {
                            errorMessage = jsonResponse.message;
                        }
                    } catch(e) {
                        if (xhr.responseText && xhr.responseText.includes('<!DOCTYPE')) {
                            errorMessage = 'Server error. Please check your connection and try again.';
                        }
                    }
                    showToast(errorMessage, 'error');
                }
                
                btn.innerHTML = originalText;
                btn.disabled = false;
                isSaving = false;
            }
        });
    }

    // ================================================================
    // FINAL SAVE & COMPLETE
    // ================================================================
    function finalSave() {
        document.getElementById('finalSave').value = '1';
        document.getElementById('complete').value = '0';
        document.getElementById('actionType').value = 'save';
        
        // Ensure prescription ID is set
        let presId = document.getElementById('prescriptionId').value;
        if (!presId && loadedPrescriptionId) {
            presId = loadedPrescriptionId;
            document.getElementById('prescriptionId').value = presId;
        }
        
        document.getElementById('prescriptionForm').submit();
    }

    function completePrescription() {
        if (!confirm('Are you sure you want to complete this prescription?')) {
            return;
        }
        
        document.getElementById('finalSave').value = '1';
        document.getElementById('complete').value = '1';
        document.getElementById('actionType').value = 'complete';
        
        // Ensure prescription ID is set
        let presId = document.getElementById('prescriptionId').value;
        if (!presId && loadedPrescriptionId) {
            presId = loadedPrescriptionId;
            document.getElementById('prescriptionId').value = presId;
        }
        
        document.getElementById('prescriptionForm').submit();
    }

    // ================================================================
    // LOAD PREVIOUS PRESCRIPTIONS
    // ================================================================
    function loadPreviousPrescriptions(tab) {
        var patientId = document.querySelector('input[name="patient_id"]')?.value;
        if (!patientId) {
            showToast('No patient selected.', 'warning');
            return;
        }
        
        var modal = document.getElementById('loadPreviousModal');
        var list = document.getElementById('prescriptionList');
        list.innerHTML = '<div class="text-center py-3">Loading...</div>';
        modal.classList.add('active');
        
        // Store the tab for use in load operations
        list.dataset.currentTab = tab;
        
        $.ajax({
            url: BASE_URL + '/prescriptions/get-previous-prescriptions',
            type: 'GET',
            data: { patient_id: patientId },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var html = `<div class="mb-3">
                        <button class="btn btn-primary btn-sm" onclick="loadAllPrescriptionData(${response.data.map(p => p.id).join(',')}, '${tab}')">
                            <i class="fas fa-download"></i> Load All (All Tabs)
                        </button>
                        <small class="text-muted ms-2">Click "Load" next to a prescription to load only the current tab (${tab})</small>
                    </div>
                    <div class="list-group">`;
                    response.data.forEach(function(p) {
                        var statusClass = 'secondary';
                        if (p.status === 'issued') statusClass = 'primary';
                        else if (p.status === 'dispensed') statusClass = 'info';
                        else if (p.status === 'completed') statusClass = 'success';
                        
                        html += `
                            <div class="list-item">
                                <div class="item-info" onclick="loadPrescriptionData(${p.id}, '${tab}')" style="cursor:pointer;flex:1;">
                                    <span class="item-title">${p.prescription_number}</span>
                                    <span class="item-sub">${p.prescription_date} | ${p.doctor_name || 'N/A'}</span>
                                    ${p.item_count ? '<span class="item-sub">Items: ' + p.item_count + '</span>' : ''}
                                </div>
                                <div>
                                    <span class="badge bg-${statusClass} me-2">${p.status}</span>
                                    <button class="btn-load-selected" onclick="loadPrescriptionData(${p.id}, '${tab}')">
                                        <i class="fas fa-arrow-right"></i> Load
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    list.innerHTML = html;
                } else {
                    list.innerHTML = '<div class="text-center py-3 text-muted">No previous prescriptions found.</div>';
                }
            },
            error: function() {
                list.innerHTML = '<div class="text-center py-3 text-danger">Error loading prescriptions. Please try again.</div>';
            }
        });
    }

    // ================================================================
    // LOAD ALL PRESCRIPTION DATA - LOADS ALL TABS
    // ================================================================
    function loadAllPrescriptionData(ids, tab) {
        if (typeof ids === 'string') {
            ids = ids.split(',').map(Number);
        }
        if (!Array.isArray(ids)) {
            ids = [ids];
        }
        
        // Get the latest prescription ID
        const latestId = ids[0];
        if (!latestId) {
            showToast('No prescription selected to load.', 'warning');
            return;
        }
        
        closeModal('loadPreviousModal');
        showToast('Loading all data from prescription...', 'info');
        
        // Load all tabs data from the selected prescription
        const tabs = ['tab1', 'tab2', 'tab3', 'tab4', 'tab5'];
        let loadedCount = 0;
        let hasError = false;
        
        tabs.forEach(function(tabName) {
            $.ajax({
                url: BASE_URL + '/prescriptions/load-tab-data',
                type: 'GET',
                data: { prescription_id: latestId, tab: tabName },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    loadedCount++;
                    if (response.success) {
                        populateTabData(tabName, response.data);
                    } else {
                        hasError = true;
                    }
                    if (loadedCount === tabs.length) {
                        document.getElementById('prescriptionId').value = latestId;
                        currentPrescriptionId = latestId;
                        loadedPrescriptionId = latestId;
                        if (!hasError) {
                            showToast('All data loaded successfully from prescription #' + latestId, 'success');
                        } else {
                            showToast('Data loaded with some errors. Please verify.', 'warning');
                        }
                        // Switch to the tab that was originally requested
                        if (tab) {
                            switchTab(tab);
                        }
                    }
                },
                error: function() {
                    loadedCount++;
                    hasError = true;
                    if (loadedCount === tabs.length) {
                        showToast('Error loading some data. Please try again.', 'error');
                    }
                }
            });
        });
    }

    // ================================================================
    // LOAD PRESCRIPTION DATA - LOADS ONLY CURRENT TAB
    // ================================================================
    function loadPrescriptionData(prescriptionId, tabToLoad) {
        closeModal('loadPreviousModal');
        showToast('Loading prescription data for ' + tabToLoad + '...', 'info');
        
        $.ajax({
            url: BASE_URL + '/prescriptions/load-tab-data',
            type: 'GET',
            data: { prescription_id: prescriptionId, tab: tabToLoad },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                if (response.success) {
                    populateTabData(tabToLoad, response.data);
                    document.getElementById('prescriptionId').value = prescriptionId;
                    currentPrescriptionId = prescriptionId;
                    loadedPrescriptionId = prescriptionId;
                    showToast('Data loaded successfully for ' + tabToLoad + '!', 'success');
                } else {
                    showToast('Error loading data: ' + (response.error || 'Unknown error'), 'error');
                }
            },
            error: function() {
                showToast('Error loading data.', 'error');
            }
        });
    }

    // ================================================================
    // POPULATE TAB DATA - UPDATED TO HANDLE ALL DATA TYPES
    // ================================================================
    function populateTabData(tab, data) {
        try {
            // This function is already defined above with full implementation
            // It handles all tab data types including Drug History, Disease History, and Special Note
            console.log('Populating tab data for:', tab);
            
            switch(tab) {
                case 'tab1':
                    // Handle Drug History (drug_history)
                    if (data.drug_history && Array.isArray(data.drug_history)) {
                        const container = document.getElementById('drugHistoryContainer');
                        if (container) {
                            container.innerHTML = '';
                            data.drug_history.forEach(function(d, i) {
                                const row = document.createElement('div');
                                row.className = 'history-item';
                                row.innerHTML = `
                                    <span class="bullet">•</span>
                                    <input type="text" class="form-control-simple" name="drug_history[${i}][name]" value="${escapeHtml(d.drug_name || '')}" placeholder="Drug name..." style="flex:1;">
                                    <input type="text" class="form-control-simple" name="drug_history[${i}][duration]" value="${escapeHtml(d.duration || '')}" placeholder="Duration" style="min-width:80px;max-width:120px;">
                                    <input type="text" class="form-control-simple" name="drug_history[${i}][remarks]" value="${escapeHtml(d.remarks || '')}" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                                    <button type="button" class="btn-remove-row" onclick="removeRow(this, 'drugHistoryContainer')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `;
                                container.appendChild(row);
                            });
                            if (typeof drugHistoryCounter !== 'undefined') {
                                drugHistoryCounter = data.drug_history.length || 1;
                            }
                        }
                    }
                    
                    // Handle Disease History (disease_history)
                    if (data.disease_history && Array.isArray(data.disease_history)) {
                        const container = document.getElementById('diseaseHistoryContainer');
                        if (container) {
                            container.innerHTML = '';
                            data.disease_history.forEach(function(d, i) {
                                const row = document.createElement('div');
                                row.className = 'history-item';
                                row.innerHTML = `
                                    <span class="bullet">•</span>
                                    <input type="text" class="form-control-simple" name="disease_history[${i}][name]" value="${escapeHtml(d.disease || '')}" placeholder="Disease..." style="flex:1;">
                                    <input type="text" class="form-control-simple" name="disease_history[${i}][duration]" value="${escapeHtml(d.duration || '')}" placeholder="Duration" style="min-width:80px;max-width:120px;">
                                    <input type="text" class="form-control-simple" name="disease_history[${i}][remarks]" value="${escapeHtml(d.remarks || '')}" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                                    <button type="button" class="btn-remove-row" onclick="removeRow(this, 'diseaseHistoryContainer')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `;
                                container.appendChild(row);
                            });
                            if (typeof diseaseHistoryCounter !== 'undefined') {
                                diseaseHistoryCounter = data.disease_history.length || 1;
                            }
                        }
                    }
                    
                    // Handle Special Note
                    if (data.special_note !== undefined) {
                        const el = document.querySelector('textarea[name="special_note"]');
                        if (el) {
                            el.value = data.special_note || '';
                        }
                    }
                    
                    // Handle other tab1 data (complaints, treatment history, etc.)
                    if (data.complaints && Array.isArray(data.complaints)) {
                        const container = document.getElementById('complaintsContainer');
                        if (container) {
                            container.innerHTML = '';
                            data.complaints.forEach(function(c, i) {
                                const row = document.createElement('div');
                                row.className = 'complaint-item';
                                row.innerHTML = `
                                    <span class="bullet">•</span>
                                    <input type="text" class="form-control-simple" name="complaints[${i}][text]" value="${escapeHtml(c.complaint || '')}" placeholder="Enter complaint..." style="flex:1;">
                                    <input type="text" class="form-control-simple" name="complaints[${i}][duration]" value="${escapeHtml(c.duration || '')}" placeholder="Duration" style="min-width:80px;max-width:120px;">
                                    <input type="text" class="form-control-simple" name="complaints[${i}][remarks]" value="${escapeHtml(c.remarks || '')}" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                                    <button type="button" class="btn-remove-row" onclick="removeRow(this, 'complaintsContainer')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `;
                                container.appendChild(row);
                            });
                            if (typeof complaintCounter !== 'undefined') {
                                complaintCounter = data.complaints.length || 1;
                            }
                        }
                    }
                    
                    if (data.treatment_history && Array.isArray(data.treatment_history)) {
                        const container = document.getElementById('treatmentHistoryContainer');
                        if (container) {
                            container.innerHTML = '';
                            data.treatment_history.forEach(function(t, i) {
                                const row = document.createElement('div');
                                row.className = 'history-item';
                                row.innerHTML = `
                                    <span class="bullet">•</span>
                                    <input type="text" class="form-control-simple" name="treatment_history[${i}][name]" value="${escapeHtml(t.treatment_name || '')}" placeholder="Enter treatment..." style="flex:1;">
                                    <input type="text" class="form-control-simple" name="treatment_history[${i}][duration]" value="${escapeHtml(t.duration || '')}" placeholder="Duration" style="min-width:80px;max-width:120px;">
                                    <input type="text" class="form-control-simple" name="treatment_history[${i}][remarks]" value="${escapeHtml(t.remarks || '')}" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                                    <button type="button" class="btn-remove-row" onclick="removeRow(this, 'treatmentHistoryContainer')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `;
                                container.appendChild(row);
                            });
                            if (typeof treatmentHistoryCounter !== 'undefined') {
                                treatmentHistoryCounter = data.treatment_history.length || 1;
                            }
                        }
                    }
                    break;
                    
                case 'tab2':
                    // Handle tab2 data
                    if (data.physical_exam) {
                        const pe = data.physical_exam;
                        ['anaemia', 'jaundice', 'cyanosis', 'oedema', 'dehydration'].forEach(field => {
                            const el = document.querySelector(`input[name="physical_exam[${field}]"]`);
                            if (el) el.checked = pe[field] || false;
                        });
                        ['abdomen', 'cvs', 'respiratory', 'lymphoreticular'].forEach(field => {
                            const el = document.querySelector(`input[name="physical_exam[${field}]"]`);
                            if (el && pe[field]) el.value = pe[field];
                        });
                    }
                    if (data.vital_signs) {
                        const vs = data.vital_signs;
                        ['pulse', 'weight', 'respiratory_rate', 'length', 'bp_systolic', 'bp_diastolic', 'temperature', 'oxygen_saturation', 'bmi', 'others'].forEach(field => {
                            const el = document.querySelector(`input[name="vital_signs[${field}]"]`);
                            if (el && vs[field] !== undefined && vs[field] !== null) {
                                el.value = vs[field];
                            }
                        });
                        calculateBMI();
                    }
                    if (data.investigations && Array.isArray(data.investigations)) {
                        const container = document.getElementById('investigationsContainer');
                        if (container) {
                            container.innerHTML = '';
                            data.investigations.forEach(function(inv, i) {
                                const row = document.createElement('div');
                                row.className = 'history-item';
                                row.innerHTML = `
                                    <span class="bullet">•</span>
                                    <input type="text" class="form-control-simple" name="investigations[${i}][name]" value="${escapeHtml(inv.investigation_name || '')}" placeholder="Investigation..." style="flex:1;">
                                    <input type="text" class="form-control-simple" name="investigations[${i}][remarks]" value="${escapeHtml(inv.remarks || '')}" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                                    <button type="button" class="btn-remove-row" onclick="removeRow(this, 'investigationsContainer')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `;
                                container.appendChild(row);
                            });
                            if (typeof investigationCounter !== 'undefined') {
                                investigationCounter = data.investigations.length || 1;
                            }
                        }
                    }
                    if (data.lab_tests && Array.isArray(data.lab_tests)) {
                        const container = document.getElementById('labTestsContainer');
                        if (container) {
                            container.innerHTML = '';
                            data.lab_tests.forEach(function(test, i) {
                                const row = document.createElement('div');
                                row.className = 'lab-test-item';
                                row.setAttribute('data-test-index', i);
                                row.innerHTML = `
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Test Name <span class="text-danger">*</span></label>
                                            <div class="lab-test-select-wrapper">
                                                <input type="text" class="lab-test-search-input form-control-simple" 
                                                       placeholder="Search lab test..." 
                                                       data-test-index="${i}"
                                                       autocomplete="off"
                                                       value="${escapeHtml(test.test_name || '')}"
                                                       style="width:100%;">
                                                <input type="hidden" name="lab_tests[${i}][test_id]" value="${test.test_id || ''}">
                                                <div class="lab-test-dropdown" id="labTestDropdown_${i}"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Priority</label>
                                            <select class="form-control-simple" name="lab_tests[${i}][priority]" style="width:100%;background:transparent;">
                                                <option value="routine" ${test.priority === 'routine' ? 'selected' : ''}>Routine</option>
                                                <option value="urgent" ${test.priority === 'urgent' ? 'selected' : ''}>Urgent</option>
                                                <option value="stat" ${test.priority === 'stat' ? 'selected' : ''}>STAT</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Notes</label>
                                            <input type="text" class="form-control-simple" name="lab_tests[${i}][notes]" value="${escapeHtml(test.notes || '')}" placeholder="Special instructions..." style="width:100%;">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn-remove-row" onclick="removeLabTestRow(this)" style="margin-top:18px;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col-md-12">
                                            <small class="text-muted" id="labTestInfo_${i}" style="font-size:11px;">
                                                <span class="text-muted">${test.test_name ? 'Test selected: ' + test.test_name : 'Select a test to see details'}</span>
                                            </small>
                                        </div>
                                    </div>
                                `;
                                container.appendChild(row);
                                
                                const testInput = row.querySelector('.lab-test-search-input');
                                if (testInput) {
                                    testInput.dataset.testIndex = i;
                                    if (typeof initLabTestSearch === 'function') {
                                        initLabTestSearch(testInput);
                                    }
                                }
                            });
                            if (typeof labTestCounter !== 'undefined') {
                                labTestCounter = data.lab_tests.length || 1;
                            }
                        }
                    }
                    if (data.diagnosis) {
                        const el = document.querySelector('textarea[name="diagnosis"]');
                        if (el) el.value = data.diagnosis;
                    }
                    break;
                    
                case 'tab3':
                    // Handle tab3 data (medicines)
                    if (data.medicines && Array.isArray(data.medicines) && data.medicines.length > 0) {
                        const container = document.getElementById('medicinesContainer');
                        if (container) {
                            container.innerHTML = '';
                            if (typeof usedDrugIndices !== 'undefined') {
                                usedDrugIndices = [];
                            }
                            if (typeof medicineCounter !== 'undefined') {
                                medicineCounter = 0;
                            }
                            
                            data.medicines.forEach(function(med) {
                                let drugData = null;
                                let drugIndex = -1;
                                
                                if (med.drug_id) {
                                    const found = SAMPLE_DRUGS.find(d => d.id == med.drug_id);
                                    if (found) {
                                        drugIndex = SAMPLE_DRUGS.indexOf(found);
                                        drugData = { index: drugIndex, drug: found };
                                    }
                                }
                                
                                if (!drugData) {
                                    const found = SAMPLE_DRUGS.find(d => 
                                        d.name.toLowerCase() === (med.drug_name || '').toLowerCase()
                                    );
                                    if (found) {
                                        drugIndex = SAMPLE_DRUGS.indexOf(found);
                                        drugData = { index: drugIndex, drug: found };
                                    }
                                }
                                
                                if (!drugData) {
                                    const customDrug = {
                                        id: 0,
                                        name: med.drug_name || 'Custom Drug',
                                        strength: '',
                                        dosage_form: med.dosage || 'Tablet',
                                        code: ''
                                    };
                                    drugData = { index: -1, drug: customDrug };
                                }
                                
                                if (drugData.index >= 0 && usedDrugIndices.includes(drugData.index)) {
                                    for (let i = 0; i < SAMPLE_DRUGS.length; i++) {
                                        if (!usedDrugIndices.includes(i)) {
                                            drugData.index = i;
                                            drugData.drug = SAMPLE_DRUGS[i];
                                            break;
                                        }
                                    }
                                }
                                
                                const row = createMedicineItem(drugData, medicineCounter);
                                if (row) {
                                    const dosageSelect = row.querySelector(`select[name*="[dosage]"]`);
                                    if (dosageSelect && med.dosage) {
                                        dosageSelect.value = med.dosage;
                                    }
                                    const foodSelect = row.querySelector(`select[name*="[relation_to_food]"]`);
                                    if (foodSelect && med.relation_to_food) {
                                        foodSelect.value = med.relation_to_food;
                                    }
                                    
                                    if (med.details && Array.isArray(med.details)) {
                                        const wrapper = row.querySelector('.medicine-details-wrapper');
                                        if (wrapper) {
                                            wrapper.innerHTML = '';
                                            med.details.forEach(function(d, di) {
                                                const detailRow = createMedicineDetailRow(
                                                    medicineCounter, 
                                                    di, 
                                                    d.frequency || '',
                                                    d.duration || '',
                                                    d.instruction || ''
                                                );
                                                wrapper.appendChild(detailRow);
                                            });
                                        }
                                    }
                                    
                                    medicineCounter++;
                                    if (drugData.index >= 0 && !usedDrugIndices.includes(drugData.index)) {
                                        usedDrugIndices.push(drugData.index);
                                    }
                                }
                            });
                            
                            if (medicineCounter === 0) {
                                const firstDrug = getNextAvailableDrug();
                                if (firstDrug) {
                                    createMedicineItem(firstDrug, medicineCounter);
                                    medicineCounter++;
                                }
                            }
                            
                            updateMedicineCount();
                        }
                    }
                    break;
                    
                case 'tab4':
                    // Handle tab4 data (advice)
                    if (data.advice) {
                        const el = document.getElementById('adviceText');
                        if (el) el.value = data.advice.advice_text || '';
                        const followUpDays = document.getElementById('follow_up_days');
                        if (followUpDays && data.advice.follow_up_days) {
                            followUpDays.value = data.advice.follow_up_days;
                        }
                        const appointmentDate = document.getElementById('appointment_date');
                        if (appointmentDate && data.advice.appointment_date) {
                            appointmentDate.value = data.advice.appointment_date;
                        }
                    }
                    break;
                    
                case 'tab5':
                    // Handle tab5 data (vaccinations)
                    if (data.vaccinations && Array.isArray(data.vaccinations)) {
                        const container = document.getElementById('newVaccineContainer');
                        if (container) {
                            container.innerHTML = '';
                            if (data.vaccinations.length > 0) {
                                data.vaccinations.forEach(function(vac, idx) {
                                    const row = document.createElement('div');
                                    row.className = 'vaccination-item row g-2 align-items-end mb-2';
                                    row.setAttribute('data-vac-index', idx);
                                    row.innerHTML = `
                                        <div class="col-md-3">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Vaccine Name</label>
                                            <input type="text" class="form-control-simple" name="vaccinations[${idx}][vaccine_name]" value="${escapeHtml(vac.vaccine_name || '')}" placeholder="e.g. Hepatitis B">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Dose</label>
                                            <select class="form-control-simple" name="vaccinations[${idx}][dose]" style="width:100%;background:transparent;">
                                                <option value="">Select Dose</option>
                                                <option value="Dose 1" ${vac.dose === 'Dose 1' ? 'selected' : ''}>Dose 1</option>
                                                <option value="Dose 2" ${vac.dose === 'Dose 2' ? 'selected' : ''}>Dose 2</option>
                                                <option value="Dose 3" ${vac.dose === 'Dose 3' ? 'selected' : ''}>Dose 3</option>
                                                <option value="Dose 4" ${vac.dose === 'Dose 4' ? 'selected' : ''}>Dose 4</option>
                                                <option value="Dose 5" ${vac.dose === 'Dose 5' ? 'selected' : ''}>Dose 5</option>
                                                <option value="Dose 6" ${vac.dose === 'Dose 6' ? 'selected' : ''}>Dose 6</option>
                                                <option value="Dose 7" ${vac.dose === 'Dose 7' ? 'selected' : ''}>Dose 7</option>
                                                <option value="Dose 8" ${vac.dose === 'Dose 8' ? 'selected' : ''}>Dose 8</option>
                                                <option value="Booster Dose" ${vac.dose === 'Booster Dose' ? 'selected' : ''}>Booster Dose</option>
                                                <option value="Advance Dose" ${vac.dose === 'Advance Dose' ? 'selected' : ''}>Advance Dose</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Date Given</label>
                                            <input type="date" class="form-control-simple" name="vaccinations[${idx}][date_given]" value="${vac.date_given || ''}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Next Due</label>
                                            <input type="date" class="form-control-simple" name="vaccinations[${idx}][next_due]" value="${vac.next_due || ''}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Batch / Lot</label>
                                            <input type="text" class="form-control-simple" name="vaccinations[${idx}][batch_number]" value="${escapeHtml(vac.batch_number || '')}" placeholder="Batch">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Site</label>
                                            <input type="text" class="form-control-simple" name="vaccinations[${idx}][site]" value="${escapeHtml(vac.site || '')}" placeholder="Left deltoid">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Administered By</label>
                                            <input type="text" class="form-control-simple" name="vaccinations[${idx}][administered_by]" value="${escapeHtml(vac.administered_by || '')}" placeholder="Dr. Name">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" style="font-size:10px;color:#64748b;">Notes</label>
                                            <input type="text" class="form-control-simple" name="vaccinations[${idx}][notes]" value="${escapeHtml(vac.notes || '')}" placeholder="Notes">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn-remove-row" onclick="removeVaccinationRow(this)" style="margin-top:18px;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    `;
                                    container.appendChild(row);
                                });
                                if (typeof vaccinationCounter !== 'undefined') {
                                    vaccinationCounter = data.vaccinations.length;
                                }
                            }
                        }
                    }
                    break;
            }
        } catch (e) {
            console.error('Error in populateTabData:', e);
        }
    }

    // ================================================================
    // TEMPLATE FUNCTIONS
    // ================================================================
    function saveTemplate(type) {
        let data = '';
        let name = prompt('Enter template name:');
        if (!name) return;
        
        switch(type) {
            case 'investigation':
                const invItems = document.querySelectorAll('#investigationsContainer .history-item');
                let invList = [];
                invItems.forEach(function(item) {
                    const nameInput = item.querySelector('input[name*="[name]"]');
                    if (nameInput && nameInput.value.trim()) {
                        invList.push(nameInput.value.trim());
                    }
                });
                data = JSON.stringify(invList);
                break;
            case 'diagnosis':
                data = document.querySelector('textarea[name="diagnosis"]').value;
                break;
            case 'medicine':
                const medItems = document.querySelectorAll('#medicinesContainer .medicine-item');
                let medList = [];
                medItems.forEach(function(item) {
                    const drugInput = item.querySelector('.drug-search-input');
                    const hiddenInput = item.querySelector('input[type="hidden"]');
                    const dosageSelect = item.querySelector('select[name*="[dosage]"]');
                    const foodSelect = item.querySelector('select[name*="[relation_to_food]"]');
                    
                    const detailRows = item.querySelectorAll('.medicine-detail-row');
                    let details = [];
                    detailRows.forEach(function(row) {
                        const freq = row.querySelector('select[name*="[frequency]"]');
                        const dur = row.querySelector('select[name*="[duration]"]');
                        const inst = row.querySelector('input[name*="[instruction]"]');
                        if (freq || dur) {
                            details.push({
                                frequency: freq ? freq.value : '',
                                duration: dur ? dur.value : '',
                                instruction: inst ? inst.value : ''
                            });
                        }
                    });
                    
                    if (drugInput && drugInput.value.trim()) {
                        medList.push({
                            drug_name: drugInput.value.trim(),
                            drug_id: hiddenInput ? hiddenInput.value : '',
                            dosage: dosageSelect ? dosageSelect.value : '',
                            relation_to_food: foodSelect ? foodSelect.value : '',
                            details: details
                        });
                    }
                });
                data = JSON.stringify(medList);
                break;
            case 'advice':
                data = document.getElementById('adviceText').value;
                break;
        }
        
        if (!data || data.length < 2) {
            showToast('Please enter some data first.', 'warning');
            return;
        }
        
        try {
            let templates = JSON.parse(localStorage.getItem('prescription_templates_' + type) || '[]');
            templates.push({ name: name, data: data, date: new Date().toISOString() });
            localStorage.setItem('prescription_templates_' + type, JSON.stringify(templates));
            showToast('Template "' + name + '" saved successfully!', 'success');
        } catch(e) {
            showToast('Error saving template.', 'error');
        }
    }

    function showTemplateModal(type) {
        const modal = document.getElementById('templateModal');
        const list = document.getElementById('templateList');
        const title = document.getElementById('templateModalTitle');
        const titles = {
            'investigation': 'Investigation Templates',
            'diagnosis': 'Diagnosis Templates',
            'medicine': 'Medicine Templates',
            'advice': 'Advice Templates'
        };
        title.textContent = titles[type] || 'Templates';
        modal.classList.add('active');
        
        // Store the type for use in load operations
        list.dataset.templateType = type;
        
        try {
            let templates = JSON.parse(localStorage.getItem('prescription_templates_' + type) || '[]');
            if (templates.length > 0) {
                let html = '';
                templates.forEach(function(t, index) {
                    html += `
                        <div class="list-item">
                            <div class="item-info" onclick="applyTemplate('${type}', ${index})">
                                <span class="item-title">${t.name}</span>
                                <span class="item-sub">${new Date(t.date).toLocaleDateString()}</span>
                            </div>
                            <div>
                                <button class="btn-load-selected" onclick="applyTemplate('${type}', ${index})">
                                    <i class="fas fa-arrow-right"></i> Load
                                </button>
                                <button class="btn btn-sm btn-danger ms-1" onclick="event.stopPropagation(); deleteTemplate('${type}', ${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                list.innerHTML = html;
            } else {
                list.innerHTML = '<div class="text-center py-3 text-muted">No templates found. Create one by saving current data as template.</div>';
            }
        } catch(e) {
            list.innerHTML = '<div class="text-center py-3 text-danger">Error loading templates.</div>';
        }
    }

    function applyTemplate(type, index) {
        try {
            let templates = JSON.parse(localStorage.getItem('prescription_templates_' + type) || '[]');
            if (templates[index]) {
                const data = templates[index].data;
                applyTemplateData(type, data);
                closeModal('templateModal');
                showToast('Template "' + templates[index].name + '" applied successfully!', 'success');
            }
        } catch(e) {
            showToast('Error applying template.', 'error');
            console.error(e);
        }
    }

    function applyTemplateData(type, data) {
        try {
            switch(type) {
                case 'investigation':
                    const invContainer = document.getElementById('investigationsContainer');
                    let invData = JSON.parse(data);
                    invContainer.innerHTML = '';
                    invData.forEach(function(item, i) {
                        const row = document.createElement('div');
                        row.className = 'history-item';
                        row.innerHTML = `
                            <span class="bullet">•</span>
                            <input type="text" class="form-control-simple" name="investigations[${i}][name]" value="${escapeHtml(item)}" placeholder="Investigation..." style="flex:1;">
                            <input type="text" class="form-control-simple" name="investigations[${i}][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">
                            <button type="button" class="btn-remove-row" onclick="removeRow(this, 'investigationsContainer')">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        invContainer.appendChild(row);
                    });
                    if (typeof investigationCounter !== 'undefined') {
                        investigationCounter = invData.length || 1;
                    }
                    break;
                case 'diagnosis':
                    const diagEl = document.querySelector('textarea[name="diagnosis"]');
                    if (diagEl) diagEl.value = data;
                    break;
                case 'medicine':
                    const medContainer = document.getElementById('medicinesContainer');
                    let medData = JSON.parse(data);
                    medContainer.innerHTML = '';
                    if (typeof usedDrugIndices !== 'undefined') {
                        usedDrugIndices = [];
                    }
                    if (typeof medicineCounter !== 'undefined') {
                        medicineCounter = 0;
                    }
                    
                    medData.forEach(function(med) {
                        let drugData = null;
                        let drugIndex = -1;
                        
                        if (med.drug_id) {
                            const found = SAMPLE_DRUGS.find(d => d.id == med.drug_id);
                            if (found) {
                                drugIndex = SAMPLE_DRUGS.indexOf(found);
                                drugData = { index: drugIndex, drug: found };
                            }
                        }
                        
                        if (!drugData) {
                            const found = SAMPLE_DRUGS.find(d => 
                                d.name.toLowerCase() === (med.drug_name || '').toLowerCase()
                            );
                            if (found) {
                                drugIndex = SAMPLE_DRUGS.indexOf(found);
                                drugData = { index: drugIndex, drug: found };
                            }
                        }
                        
                        if (!drugData) {
                            drugData = { index: -1, drug: { id: 0, name: med.drug_name || 'Custom Drug', strength: '', dosage_form: med.dosage || 'Tablet', code: '' } };
                        }
                        
                        const row = createMedicineItem(drugData, medicineCounter);
                        if (row) {
                            const dosageSelect = row.querySelector(`select[name*="[dosage]"]`);
                            if (dosageSelect && med.dosage) {
                                dosageSelect.value = med.dosage;
                            }
                            const foodSelect = row.querySelector(`select[name*="[relation_to_food]"]`);
                            if (foodSelect && med.relation_to_food) {
                                foodSelect.value = med.relation_to_food;
                            }
                            
                            if (med.details && Array.isArray(med.details)) {
                                const wrapper = row.querySelector('.medicine-details-wrapper');
                                if (wrapper) {
                                    wrapper.innerHTML = '';
                                    med.details.forEach(function(d, di) {
                                        const detailRow = createMedicineDetailRow(
                                            medicineCounter, 
                                            di, 
                                            d.frequency || '',
                                            d.duration || '',
                                            d.instruction || ''
                                        );
                                        wrapper.appendChild(detailRow);
                                    });
                                }
                            }
                            
                            medicineCounter++;
                            if (drugIndex >= 0 && usedDrugIndices.indexOf(drugIndex) === -1) {
                                usedDrugIndices.push(drugIndex);
                            }
                        }
                    });
                    
                    if (medicineCounter === 0) {
                        const firstDrug = getNextAvailableDrug();
                        if (firstDrug) {
                            createMedicineItem(firstDrug, medicineCounter);
                            medicineCounter++;
                        }
                    }
                    updateMedicineCount();
                    break;
                case 'advice':
                    const adviceEl = document.getElementById('adviceText');
                    if (adviceEl) adviceEl.value = data;
                    break;
            }
        } catch(e) {
            showToast('Error applying template data.', 'error');
            console.error(e);
        }
    }

    function deleteTemplate(type, index) {
        if (confirm('Delete this template?')) {
            try {
                let templates = JSON.parse(localStorage.getItem('prescription_templates_' + type) || '[]');
                templates.splice(index, 1);
                localStorage.setItem('prescription_templates_' + type, JSON.stringify(templates));
                showToast('Template deleted.', 'info');
                showTemplateModal(type);
            } catch(e) {
                showToast('Error deleting template.', 'error');
            }
        }
    }

    // ================================================================
    // MODAL FUNCTIONS
    // ================================================================
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('active');
    }

    // ================================================================
    // FORM SUBMIT HANDLER
    // ================================================================
    document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
        console.log('Form submitting...');
        const finalSave = document.getElementById('finalSave').value;
        const complete = document.getElementById('complete').value;
        const actionType = document.getElementById('actionType').value;
        console.log('finalSave:', finalSave, 'complete:', complete, 'actionType:', actionType);
    });

    // ================================================================
    // DOM READY - INITIALIZATION
    // ================================================================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing...');
        
        // Set current prescription ID from hidden field
        currentPrescriptionId = document.getElementById('prescriptionId').value || null;
        if (currentPrescriptionId) {
            loadedPrescriptionId = currentPrescriptionId;
            console.log('Loaded Prescription ID:', loadedPrescriptionId);
        }
        
        initializeMedicines();
        
        document.querySelectorAll('.drug-search-input').forEach(function(input) {
            initDrugSearch(input);
        });

        document.querySelectorAll('.lab-test-search-input').forEach(function(input) {
            initLabTestSearch(input);
        });
        
        // Add weight/height listeners for BMI
        const weightInput = document.getElementById('vitalWeight');
        const heightInput = document.getElementById('vitalHeight');
        if (weightInput) weightInput.addEventListener('input', calculateBMI);
        if (heightInput) heightInput.addEventListener('input', calculateBMI);
        
        var saveBtn = document.getElementById('finalSaveBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                finalSave();
            });
        }
        
        // Fix: Update form action dynamically if editing
        const form = document.getElementById('prescriptionForm');
        if (form) {
            const presId = document.getElementById('prescriptionId').value;
            if (presId) {
                console.log('Edit mode - Prescription ID:', presId);
            }
        }
        
        console.log('Initialization complete. Current Prescription ID:', currentPrescriptionId);
    });

    // ================================================================
    // GLOBAL EXPOSURE
    // ================================================================
    window.loadPreviousPrescriptions = loadPreviousPrescriptions;
    window.loadPrescriptionData = loadPrescriptionData;
    window.loadAllPrescriptionData = loadAllPrescriptionData;
    window.populateTabData = populateTabData;
    window.closeModal = closeModal;
    window.showToast = showToast;
    window.switchTab = switchTab;
    window.saveCurrentTab = saveCurrentTab;
    window.saveAndGoNext = saveAndGoNext;
    window.finalSave = finalSave;
    window.completePrescription = completePrescription;
    window.initDrugSearch = initDrugSearch;
    window.renderDrugDropdown = renderDrugDropdown;
    window.addComplaint = addComplaint;
    window.addDrugHistory = addDrugHistory;
    window.addDiseaseHistory = addDiseaseHistory;
    window.addInvestigation = addInvestigation;
    window.addNextMedicine = addNextMedicine;
    window.removeRow = removeRow;
    window.removeDetailRow = removeDetailRow;
    window.addDetailRow = addDetailRow;
    window.removeMedicineRow = removeMedicineRow;
    window.insertAdvice = insertAdvice;
    window.setNextAppointment = setNextAppointment;
    window.saveTemplate = saveTemplate;
    window.showTemplateModal = showTemplateModal;
    window.applyTemplate = applyTemplate;
    window.applyTemplateData = applyTemplateData;
    window.deleteTemplate = deleteTemplate;
    window.addVaccinationRow = addVaccinationRow;
    window.removeVaccinationRow = removeVaccinationRow;
    window.initLabTestSearch = initLabTestSearch;
    window.renderLabTestDropdown = renderLabTestDropdown;
    window.addLabTestRow = addLabTestRow;
    window.removeLabTestRow = removeLabTestRow;
    window.initializeMedicines = initializeMedicines;
    window.getNextAvailableDrug = getNextAvailableDrug;
    window.createMedicineItem = createMedicineItem;
    window.createMedicineDetailRow = createMedicineDetailRow;
    window.getDosageFormHtml = getDosageFormHtml;
    window.getFoodRelationHtml = getFoodRelationHtml;
    window.getFrequencyHtml = getFrequencyHtml;
    window.getDurationHtml = getDurationHtml;
    window.fetchDrugs = fetchDrugs;
    window.calculateBMI = calculateBMI;
    window.addTreatmentHistory = addTreatmentHistory;
    window.escapeHtml = escapeHtml;

    console.log('All functions registered successfully!');
    console.log('Sample Drugs:', SAMPLE_DRUGS.length);
    console.log('Dropdown options loaded from database:');
    console.log('- Food Relation:', FOOD_RELATION_OPTIONS.length);
    console.log('- Frequency:', FREQUENCY_OPTIONS.length);
    console.log('- Duration:', DURATION_OPTIONS.length);
    console.log('- Instruction:', INSTRUCTION_OPTIONS.length);
    console.log('- Dosage Form:', DOSAGE_FORM_OPTIONS.length);
    console.log('Save and Go Next function is now globally accessible:', typeof window.saveAndGoNext);
    </script>
</body>
</html>