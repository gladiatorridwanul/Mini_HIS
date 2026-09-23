<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 

// Set timezone to Dhaka (GMT+6)
date_default_timezone_set('Asia/Dhaka');

// Check if bill has referred_by and get the name
$referredByName = 'N/A';
if (isset($bill['referred_by']) && $bill['referred_by'] > 0) {
    $referredQuery = $this->db->query("SELECT name FROM referred_by WHERE id = " . (int)$bill['referred_by']);
    if ($referredQuery && $referredQuery->num_rows > 0) {
        $referredRow = $referredQuery->fetch_assoc();
        $referredByName = $referredRow['name'];
    }
}

// Format Bill Date & Time for Prepared By section
$billDateTime = isset($bill['created_at']) ? date('d-M-Y h:i A', strtotime($bill['created_at'])) : date('d-M-Y h:i A');

// Fetch payment history for this bill
$paymentHistory = [];
if (isset($bill['id']) && $bill['id'] > 0) {
    $paymentQuery = $this->db->query("
        SELECT p.*, u.first_name, u.last_name 
        FROM payments p 
        LEFT JOIN users u ON p.received_by = u.id 
        WHERE p.bill_id = " . (int)$bill['id'] . " 
        ORDER BY p.created_at DESC
    ");
    if ($paymentQuery && $paymentQuery->num_rows > 0) {
        while ($row = $paymentQuery->fetch_assoc()) {
            $paymentHistory[] = $row;
        }
    }
}

// Get current user name for Prepared By
$currentUserName = isset($_SESSION['user_name']) ? ucwords(strtolower($_SESSION['user_name'])) : 'System Admin';
?>

<style>
    /* ================================================================ */
    /* A5 PORTRAIT - 148mm × 210mm - PURE BLACK, CAMBRIA FONT         */
    /* ================================================================ */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body { 
        font-family: 'Cambria', 'Times New Roman', serif;
        background: #ffffff;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        padding: 20px;
        color: #000000;
    }
    
    .invoice-wrapper {
        width: 148mm;
        height: 210mm;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        border-radius: 4px;
        overflow: hidden;
        position: relative;
        padding: 5mm 5mm 5mm 5mm;
        display: flex;
        flex-direction: column;
        color: #000000;
    }
    
    .invoice-container {
        padding: 0;
        background: #ffffff;
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        flex: 1;
        color: #000000;
    }
    
    .invoice-wrapper * {
        font-family: 'Cambria', 'Times New Roman', serif;
        color: #000000;
        background-color: transparent;
    }
    
    .watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0.03;
        pointer-events: none;
        z-index: 0;
        width: 35%;
        text-align: center;
    }
    .watermark img {
        width: 100%;
        max-width: 120px;
        height: auto;
        filter: grayscale(1);
    }
    .watermark .watermark-text {
        font-size: 18pt;
        font-weight: 900;
        color: #000000;
        letter-spacing: 2px;
        text-transform: uppercase;
        line-height: 1.2;
        margin-top: 3px;
    }
    .watermark .watermark-sub {
        font-size: 8pt;
        color: #000000;
        letter-spacing: 1.5px;
        margin-top: 2px;
    }
    
    .invoice-header {
        background: #ffffff;
        padding: 0 0 2px 0;
        border-bottom: 2px solid #000000;
        min-height: 1.6cm;
        max-height: 1.8cm;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        overflow: visible;
    }
    .invoice-header .header-left {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 1px;
        flex: 1;
        min-width: 0;
    }
    .invoice-header .header-left .call-number {
        font-size: 9pt;
        font-weight: 700;
        color: #000000;
        letter-spacing: 0.5px;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .invoice-header .header-left .call-number .call-label {
        font-weight: 400;
        color: #000000;
    }
    .invoice-header .header-left .hospital-name {
        font-size: 10.5pt;
        font-weight: 700;
        color: #000000;
        text-transform: uppercase;
        letter-spacing: 0.2px;
        line-height: 1.1;
        font-family: 'Cambria', 'Times New Roman', serif;
        white-space: nowrap;
    }
    .invoice-header .header-left .address {
        font-size: 6.5pt;
        color: #000000;
        line-height: 1.2;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .invoice-header .header-left .address .web {
        color: #000000;
        font-weight: 500;
    }
    .invoice-header .header-right {
        flex-shrink: 0;
        margin-left: 6px;
        display: flex;
        align-items: center;
    }
    .invoice-header .header-right .logo-img {
        max-height: 1.4cm;
        width: auto;
        display: block;
        filter: grayscale(1);
    }
    
    .header-gap {
        height: 4px;
        flex-shrink: 0;
    }
    
    .print-time-bar {
        background: #ffffff;
        padding: 1px 4px;
        display: flex;
        justify-content: flex-end;
        font-size: 6pt;
        color: #000000;
        border-bottom: 1px solid #e0e0e0;
        position: relative;
        z-index: 1;
        margin: 0 0 2px 0;
        flex-shrink: 0;
        height: 12px;
    }
    .print-time-bar .label {
        color: #000000;
        margin-right: 3px;
    }
    
    .patient-info-section {
        padding: 3px 0;
        border-bottom: 1px solid #e0e0e0;
        font-size: 7.5pt;
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        margin: 2px 0 3px 0;
        flex-shrink: 0;
        color: #000000;
    }
    .patient-info-section .info-col {
        display: flex;
        flex-direction: column;
        width: 50%;
        padding-right: 4px;
    }
    .patient-info-section .info-col-right {
        display: flex;
        flex-direction: column;
        width: 50%;
        padding-left: 4px;
    }
    .patient-info-section .info-col .field,
    .patient-info-section .info-col-right .field {
        display: block;
        width: 100%;
        margin-right: 0;
        margin-bottom: 1px;
    }
    .patient-info-section .info-col .field .label,
    .patient-info-section .info-col-right .field .label {
        color: #000000;
        font-weight: 700;
        margin-right: 2px;
        font-size: 7.5pt;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .patient-info-section .info-col .field .value,
    .patient-info-section .info-col-right .field .value {
        color: #000000;
        font-weight: 400;
        font-size: 7.5pt;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .patient-info-section .info-col .field .value-strong,
    .patient-info-section .info-col-right .field .value-strong {
        color: #000000;
        font-weight: 700;
        font-size: 7.5pt;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .patient-info-section .info-col .field .value-phone,
    .patient-info-section .info-col-right .field .value-phone {
        color: #000000;
        font-weight: 600;
        font-size: 7.5pt;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    
    /* ITEMS TABLE - Deliv. column before Particulars */
    .items-table-wrapper {
        padding: 2px 0;
        position: relative;
        z-index: 1;
        margin: 1px 0 2px 0;
        overflow: hidden;
    }
    .items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.5pt;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .items-table thead th {
        background: #ffffff;
        color: #000000;
        font-weight: 700;
        font-size: 7.5pt;
        letter-spacing: 0.2px;
        padding: 2px 3px;
        border-bottom: 1.5px solid #000000;
        text-align: left;
        font-family: 'Cambria', 'Times New Roman', serif;
        text-transform: none;
    }
    .items-table thead th.text-end { text-align: right; }
    .items-table thead th.text-center { text-align: center; }
    .items-table tbody td {
        padding: 2px 3px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
        font-size: 7.5pt;
        font-weight: 400;
        font-family: 'Cambria', 'Times New Roman', serif;
        color: #000000;
        background: #ffffff;
    }
    .items-table tbody td.text-end { text-align: right; }
    .items-table tbody td.text-center { text-align: center; }
    .items-table tbody tr:last-child td { border-bottom: none; }
    
    .accessories-section {
        margin-top: 3px;
        padding-top: 3px;
        border-top: 1px dashed #ccc;
        font-size: 7pt;
        flex-shrink: 0;
        color: #000000;
        background: #ffffff;
    }
    .accessories-section .acc-title {
        font-weight: 700;
        color: #000000;
        font-size: 7.5pt;
        margin-bottom: 2px;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .accessories-section .acc-item {
        display: inline-block;
        background: #ffffff;
        padding: 1px 6px;
        border-radius: 0;
        margin: 1px 3px 1px 0;
        font-size: 6.5pt;
        color: #000000;
        font-family: 'Cambria', 'Times New Roman', serif;
        border: 1px solid #e0e0e0;
    }
    .accessories-section .acc-item .acc-name {
        font-weight: 500;
    }
    .accessories-section .acc-item .acc-qty {
        color: #000000;
        margin-left: 3px;
    }
    
    .summary-section {
        padding: 3px 0;
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: flex-end;
        border-top: 1px solid #e0e0e0;
        margin: 2px 0 2px 0;
        flex-shrink: 0;
        color: #000000;
        background: #ffffff;
    }
    .summary-section .totals {
        flex-shrink: 0;
        min-width: 160px;
        font-size: 7.5pt;
        font-family: 'Cambria', 'Times New Roman', serif;
        width: 100%;
        max-width: 240px;
        color: #000000;
    }
    .summary-section .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: nowrap;
        white-space: nowrap;
        padding: 1.5px 0;
        border-bottom: 1px solid #f0f0f0;
        line-height: 1.2;
        color: #000000;
    }
    .summary-section .summary-row:last-child {
        border-bottom: none;
    }
    .summary-section .summary-row .summary-label {
        color: #000000;
        font-size: 7.5pt;
        font-weight: 400;
        flex-shrink: 0;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .summary-section .summary-row .summary-value {
        font-weight: 500;
        font-size: 7.5pt;
        text-align: right;
        flex-shrink: 0;
        margin-left: 15px;
        color: #000000;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .summary-section .summary-row.discount-row .summary-value {
        color: #000000;
    }
    .summary-section .summary-row.net-payable {
        font-weight: 700;
        font-size: 8.5pt;
        padding-top: 2px;
        border-top: 1.5px solid #000000;
        border-bottom: 1.5px solid #000000;
        margin-top: 1px;
    }
    .summary-section .summary-row.net-payable .summary-label {
        color: #000000;
        font-weight: 700;
        font-size: 8.5pt;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .summary-section .summary-row.net-payable .summary-value {
        color: #000000;
        font-weight: 700;
        font-size: 8.5pt;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    
    .payment-status-section {
        padding: 2px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #e0e0e0;
        position: relative;
        z-index: 1;
        margin: 2px 0 2px 0;
        flex-shrink: 0;
        min-height: 18px;
        color: #000000;
        background: #ffffff;
    }
    .payment-status-section .status {
        font-size: 14pt;
        font-weight: 900;
        letter-spacing: 2px;
        color: #000000;
        text-transform: uppercase;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .payment-status-section .amount-word {
        font-size: 7pt;
        color: #000000;
        text-align: right;
        line-height: 1.2;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .payment-status-section .amount-word strong {
        color: #000000;
        font-weight: 700;
    }
    
    /* SIGNATURE & LAB NOTE - Same Line */
    .signature-lab-section {
        padding: 2px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #e0e0e0;
        margin: 2px 0 2px 0;
        flex-shrink: 0;
        color: #000000;
        background: #ffffff;
        min-height: 16px;
    }
    .signature-lab-section .lab-note-left {
        font-size: 7.5pt;
        font-weight: 600;
        color: #000000;
        font-family: 'Cambria', 'Times New Roman', serif;
        letter-spacing: 0.3px;
        text-align: left;
        flex: 1;
    }
    .signature-lab-section .signature-right {
        text-align: right;
        line-height: 1.3;
        flex-shrink: 0;
        padding-left: 10px;
    }
    .signature-lab-section .signature-right .prepared-by {
        font-size: 7pt;
        color: #000000;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .signature-lab-section .signature-right .prepared-by strong {
        font-weight: 700;
    }
    .signature-lab-section .signature-right .billing-date {
        font-size: 7pt;
        color: #000000;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    
    /* PAYMENT HISTORY TABLE - Compact, Small Font, 50% Width, Left Aligned */
    .payment-history-section {
        padding: 2px 0;
        border-top: 1px solid #e0e0e0;
        margin: 2px 0 2px 0;
        flex-shrink: 0;
        color: #000000;
        background: #ffffff;
        width: 50%;
    }
    .payment-history-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 5.5pt;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .payment-history-table thead th {
        background: #ffffff;
        color: #000000;
        font-weight: 700;
        font-size: 5.5pt;
        padding: 1px 3px;
        border-bottom: 1px solid #000000;
        text-align: left;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .payment-history-table tbody td {
        padding: 1px 3px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
        font-size: 5.5pt;
        font-weight: 400;
        font-family: 'Cambria', 'Times New Roman', serif;
        color: #000000;
        background: #ffffff;
        text-align: left;
    }
    .payment-history-table tbody tr:last-child td { border-bottom: none; }
    .no-payments {
        text-align: center;
        padding: 2px 0;
        font-size: 6pt;
        color: #888;
        font-style: italic;
    }
    
    .invoice-footer {
        background: #ffffff;
        padding: 2px 0 0 0;
        border-top: 2px solid #000000;
        min-height: 1.3cm;
        max-height: 1.5cm;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 7pt;
        color: #000000;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        margin-top: auto;
        overflow: hidden;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .invoice-footer .footer-left {
        text-align: left;
        line-height: 1.2;
        flex: 1;
        padding-right: 10px;
    }
    .invoice-footer .footer-left .footer-title {
        font-weight: 600;
        color: #000000;
        font-size: 8pt;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .invoice-footer .footer-right {
        text-align: right;
        line-height: 1.2;
        flex-shrink: 0;
        padding-left: 10px;
    }
    .invoice-footer .footer-right .footer-title {
        font-weight: 600;
        color: #000000;
        font-size: 8pt;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    
    .no-items {
        text-align: center;
        padding: 8px 0;
        color: #000000;
        font-size: 8pt;
    }
    
    .payment-btn-wrapper {
        text-align: center;
        padding: 3px 0;
        position: relative;
        z-index: 1;
        flex-shrink: 0;
        background: #ffffff;
    }
    .payment-btn-wrapper .btn-pay {
        background: #000000;
        color: white;
        border: none;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 8pt;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        font-family: 'Cambria', 'Times New Roman', serif;
    }
    .payment-btn-wrapper .btn-pay:hover {
        background: #333333;
    }
    
    .action-buttons {
        text-align: center;
        padding: 4px 0;
        border-top: 1px solid #e0e0e0;
        background: #ffffff;
        flex-shrink: 0;
    }
    .action-buttons .btn {
        padding: 3px 10px;
        font-size: 7pt;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 0 2px;
        font-weight: 500;
        font-family: 'Cambria', 'Times New Roman', serif;
        color: #000000;
        background: #e0e0e0;
    }
    .action-buttons .btn-primary {
        background: #000000;
        color: white;
    }
    .action-buttons .btn-primary:hover {
        background: #333333;
    }
    .action-buttons .btn-secondary {
        background: #999999;
        color: white;
    }
    .action-buttons .btn-secondary:hover {
        background: #777777;
    }
    .action-buttons .btn-outline {
        background: transparent;
        color: #000000;
        border: 1px solid #000000;
    }
    .action-buttons .btn-outline:hover {
        background: #000000;
        color: white;
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
        backdrop-filter: blur(2px);
    }
    .modal.show { display: block; }
    .modal-dialog {
        position: relative;
        width: auto;
        margin: 1.75rem auto;
        max-width: 500px;
        animation: slideDown 0.25s ease;
    }
    .modal-dialog.modal-lg { max-width: 800px; }
    @keyframes slideDown {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        border: none;
        overflow: hidden;
    }
    .modal-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #000;
        color: #fff;
    }
    .modal-header .modal-title {
        font-weight: 600;
        font-size: 1rem;
        margin: 0;
        color: #fff;
    }
    .modal-body { padding: 1.25rem; }
    .modal-footer {
        padding: 0.75rem 1.25rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    .btn-close {
        background: transparent;
        border: none;
        font-size: 1.25rem;
        line-height: 1;
        opacity: 0.5;
        cursor: pointer;
        padding: 0.25rem;
        color: #fff;
    }
    .btn-close:hover { opacity: 0.8; }
    
    @page {
        size: A5 portrait;
        margin: 5mm 5mm 5mm 5mm;
    }
    @media print {
        body {
            background: white;
            padding: 0;
            margin: 0;
            display: block;
            min-height: auto;
        }
        body * { visibility: hidden; }
        .invoice-wrapper, .invoice-wrapper * { visibility: visible; }
        .invoice-wrapper {
            position: absolute;
            left: 0;
            top: 0;
            width: 148mm;
            height: 210mm;
            box-shadow: none;
            border-radius: 0;
            margin: 0;
            padding: 5mm 5mm 5mm 5mm;
            page-break-after: avoid;
            page-break-inside: avoid;
            background: white;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .invoice-container {
            min-height: auto !important;
            height: 100% !important;
            max-height: 100% !important;
            overflow: hidden !important;
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        .invoice-header {
            min-height: 1.6cm !important;
            max-height: 1.8cm !important;
            flex-shrink: 0 !important;
        }
        .invoice-footer {
            position: static !important;
            page-break-after: avoid !important;
            min-height: 1.3cm !important;
            max-height: 1.5cm !important;
            flex-shrink: 0 !important;
            margin-top: auto;
        }
        .print-time-bar { display: none !important; }
        .no-print { display: none !important; }
        .watermark { opacity: 0.03; }
        .action-buttons { display: none !important; }
        .payment-btn-wrapper { display: none !important; }
        .modal { display: none !important; }
        .modal-backdrop { display: none !important; }
        .items-table-wrapper { overflow: hidden; }
        .summary-section { flex-shrink: 0; }
        .payment-status-section { flex-shrink: 0; min-height: 18px; }
        .signature-lab-section { flex-shrink: 0; min-height: 16px; }
        .patient-info-section { flex-shrink: 0; }
        .header-gap { display: none !important; }
        .payment-history-section { flex-shrink: 0; }
    }
    
    @media screen and (max-width: 576px) {
        .invoice-wrapper {
            width: 100%;
            height: auto;
            min-height: auto;
            padding: 3mm 2mm;
        }
        .invoice-header {
            flex-direction: column;
            height: auto;
            min-height: auto;
            max-height: none;
            text-align: center;
        }
        .invoice-header .header-left {
            align-items: center;
        }
        .invoice-header .header-right {
            margin-left: 0;
            margin-top: 2px;
        }
        .invoice-header .header-right .logo-img {
            max-height: 1.2cm;
        }
        .invoice-header .header-left .hospital-name {
            white-space: normal;
        }
        .patient-info-section .info-col,
        .patient-info-section .info-col-right {
            width: 100%;
            padding: 0;
        }
        .summary-section {
            justify-content: center;
        }
        .summary-section .totals {
            max-width: 100%;
        }
        .invoice-footer {
            flex-direction: column;
            height: auto;
            min-height: auto;
            max-height: none;
            text-align: center;
        }
        .invoice-footer .footer-left,
        .invoice-footer .footer-right {
            text-align: center;
            padding: 0;
        }
        .items-table {
            font-size: 6.5pt;
        }
        .items-table thead th,
        .items-table tbody td {
            padding: 1.5px 2px;
        }
        .payment-status-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
            min-height: auto;
        }
        .payment-status-section .status {
            font-size: 12pt;
        }
        .payment-status-section .amount-word {
            text-align: left;
        }
        .accessories-section .acc-item {
            display: block;
            margin: 2px 0;
        }
        .signature-lab-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        .signature-lab-section .signature-right {
            text-align: left;
            padding-left: 0;
        }
        .payment-history-section {
            width: 100%;
        }
        .payment-history-table {
            font-size: 5pt;
        }
        .payment-history-table thead th,
        .payment-history-table tbody td {
            padding: 1px 2px;
        }
    }
</style>

<!-- ================================================================ -->
<!-- INVOICE WRAPPER -->
<!-- ================================================================ -->
<div class="invoice-wrapper">
    <div class="invoice-container" id="invoicePrint">
        
        <!-- WATERMARK -->
        <div class="watermark">
            <img src="<?php echo BASE_URL; ?>/assets/images/bcpcc-PNG.png" alt="BCPCC">
            <div class="watermark-text">BCPCC</div>
            <div class="watermark-sub">Hope · Care · Dignity</div>
        </div>
        
        <!-- HEADER -->
        <div class="invoice-header">
            <div class="header-left">
                <div class="call-number">
                    <span class="call-label">Call Center</span> 01715-313313
                </div>
                <div class="hospital-name">
                    Bangladesh Cancer &amp; Palliative Care Center
                </div>
                <div class="address">
                    Green City Square, Lift- 5, 750 Shatmosjid Road, Dhanmondi, Dhaka- 1209<br>
                    <span class="web">www.bcpcc.net  |  info@bcpcc.net</span>
                </div>
            </div>
            <div class="header-right">
                <img src="<?php echo BASE_URL; ?>/assets/images/bcpcc-PNG.png" alt="BCPCC" class="logo-img">
            </div>
        </div>
        
        <div class="header-gap"></div>
        
        <!-- PRINT TIME -->
        <div class="print-time-bar">
            <span><span class="label">Print Time :</span> <?php echo date('d-M-Y h:i A'); ?></span>
        </div>
        
        <!-- ============================================================ -->
        <!-- PATIENT INFORMATION -->
        <!-- ============================================================ -->
        <div class="patient-info-section">
            <div class="info-col">
                <div class="field">
                    <span class="label">Patient Id :</span>
                    <span class="value-strong"><?php echo isset($bill['patient_code']) ? htmlspecialchars($bill['patient_code']) : 'N/A'; ?></span>
                </div>
                <div class="field">
                    <span class="label">Bill No. :</span>
                    <span class="value-strong"><?php echo isset($bill['bill_number']) ? htmlspecialchars($bill['bill_number']) : 'N/A'; ?></span>
                </div>
                <div class="field">
                    <span class="label">Bill Date :</span>
                    <span class="value"><?php echo $billDateTime; ?></span>
                </div>
                <div class="field">
                    <span class="label">Referred By :</span>
                    <span class="value"><?php echo htmlspecialchars($referredByName); ?></span>
                </div>
            </div>
            
            <div class="info-col-right">
                <div class="field">
                    <span class="label">Patient Name :</span>
                    <span class="value-strong"><?php echo isset($bill['patient_name']) ? ucwords(strtolower(htmlspecialchars($bill['patient_name']))) : 'N/A'; ?></span>
                </div>
                <div class="field">
                    <span class="label">Gender :</span>
                    <span class="value"><?php echo isset($bill['gender']) ? ucwords(strtolower($bill['gender'])) : 'N/A'; ?></span>
                </div>
                <div class="field">
                    <span class="label">Age :</span>
                    <span class="value"><?php 
                        $age = '';
                        $dob = isset($bill['date_of_birth']) ? $bill['date_of_birth'] : '';
                        if (!empty($dob) && $dob != '0000-00-00') {
                            $birthDate = new DateTime($dob);
                            $today = new DateTime('today');
                            $ageDiff = $birthDate->diff($today);
                            $age = $ageDiff->y . 'Y';
                        }
                        echo $age ?: 'N/A';
                    ?></span>
                </div>
                <div class="field" style="margin-right:0;">
                    <span class="label">Contact No. :</span>
                    <span class="value-phone"><?php echo isset($bill['phone']) ? htmlspecialchars($bill['phone']) : 'N/A'; ?></span>
                </div>
            </div>
        </div>
        
        <!-- ============================================================ -->
        <!-- ITEMS TABLE - Deliv. column before Particulars -->
        <!-- ============================================================ -->
        <?php 
        $mainItems = [];
        $accessories = [];
        if (!empty($items) && is_array($items)) {
            foreach ($items as $item) {
                if (isset($item['item_type']) && $item['item_type'] === 'lab_accessory') {
                    $accessories[] = $item;
                } else {
                    $mainItems[] = $item;
                }
            }
        }
        ?>
        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:4%;">Sno.</th>
                        <th style="width:10%;" class="text-center">Deliv.</th>
                        <th style="width:28%;">Particulars</th>
                        <th style="width:11%;" class="text-end">Rate</th>
                        <th style="width:7%;" class="text-center">Unit</th>
                        <th style="width:14%;" class="text-end">Total</th>
                        <th style="width:14%;" class="text-end">Disc. (%)</th>
                        <th style="width:12%;" class="text-end">Net Amt.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($mainItems)): ?>
                        <?php $counter = 1; foreach($mainItems as $item): 
                            $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                            $rate = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
                            $total = $qty * $rate;
                            $discount = isset($item['discount_amount']) ? (float)$item['discount_amount'] : 0;
                            $discountPercent = 0;
                            if ($total > 0 && $discount > 0) {
                                $discountPercent = round(($discount / $total) * 100, 2);
                            }
                            $net = $total - $discount;
                            $desc = isset($item['description']) ? ucwords(strtolower(htmlspecialchars($item['description']))) : 'N/A';
                            
                            // Get delivery date (turnaround time) for lab tests
                            $deliv = '';
                            if (isset($item['item_type']) && ($item['item_type'] === 'lab_test' || $item['item_type'] === 'lab')) {
                                $labTestId = isset($item['item_id']) ? (int)$item['item_id'] : 0;
                                if ($labTestId > 0) {
                                    $labQuery = $this->db->query("SELECT turnaround_time FROM lab_tests WHERE id = $labTestId");
                                    if ($labQuery && $labQuery->num_rows > 0) {
                                        $labData = $labQuery->fetch_assoc();
                                        if ($labData['turnaround_time'] && $labData['turnaround_time'] > 0) {
                                            $deliv = $labData['turnaround_time'] . 'h';
                                        }
                                    }
                                }
                            }
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td class="text-center"><?php echo $deliv ?: '-'; ?></td>
                            <td><?php echo $desc; ?></td>
                            <td class="text-end"><?php echo number_format($rate, 2); ?></td>
                            <td class="text-center"><?php echo $qty; ?></td>
                            <td class="text-end"><?php echo number_format($total, 2); ?></td>
                            <td class="text-end">
                                <?php if($discount > 0): ?>
                                    <?php echo number_format($discountPercent, 2); ?>% 
                                    <small style="font-size:6pt;">(<?php echo number_format($discount, 2); ?>)</small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><strong><?php echo number_format($net, 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center no-items">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- ============================================================ -->
        <!-- ACCESSORIES SECTION -->
        <!-- ============================================================ -->
        <?php if(!empty($accessories)): ?>
        <div class="accessories-section">
            <div class="acc-title">Accessories Used:</div>
            <?php foreach($accessories as $acc): 
                $accName = isset($acc['description']) ? ucwords(strtolower(htmlspecialchars($acc['description']))) : (isset($acc['item_name']) ? ucwords(strtolower(htmlspecialchars($acc['item_name']))) : 'Accessory');
                $accQty = isset($acc['quantity']) ? (int)$acc['quantity'] : 1;
            ?>
                <span class="acc-item">
                    <span class="acc-name"><?php echo $accName; ?></span>
                    <span class="acc-qty">x <?php echo $accQty; ?></span>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- SUMMARY -->
        <!-- ============================================================ -->
        <?php 
            // --- Compute values ---
            $grossAmount = isset($bill['subtotal']) ? (float)$bill['subtotal'] : 0;
            $totalDiscount = isset($bill['discount_amount']) ? (float)$bill['discount_amount'] : 0;
            $discountPercent = isset($bill['discount_percentage']) ? (float)$bill['discount_percentage'] : 0;
            
            // Calculate discount percentage based on gross amount
            if ($grossAmount > 0 && $totalDiscount > 0) {
                $discountPercent = round(($totalDiscount / $grossAmount) * 100, 2);
            }
            
            // Net Amount Raw = Gross - Discount
            $netAmountRaw = $grossAmount - $totalDiscount;
            if ($netAmountRaw < 0) $netAmountRaw = 0;
            
            // Round off to nearest whole number
            $netAmount = round($netAmountRaw);
            $roundOff = $netAmountRaw - $netAmount;
            
            // Amt Received from bill (Paid Amount)
            $amtReceived = isset($bill['paid_amount']) ? (float)$bill['paid_amount'] : 0;
            
            // Due = Net Amount - Paid Amount
            $dueAmount = $netAmount - $amtReceived;
            if ($dueAmount < 0) $dueAmount = 0;
            
            // If bill is fully paid, update amount received
            if ($dueAmount == 0 && $amtReceived > 0) {
                $amtReceived = $netAmount;
            }
            
            // Determine if there's any lab test
            $hasLabTest = false;
            if (!empty($items) && is_array($items)) {
                foreach ($items as $item) {
                    if (isset($item['item_type']) && ($item['item_type'] === 'lab_test' || $item['item_type'] === 'lab')) {
                        $hasLabTest = true;
                        break;
                    }
                }
            }
        ?>
        <div class="summary-section">
            <div class="totals">
                <!-- Gross Amount -->
                <div class="summary-row">
                    <span class="summary-label">Gross Amount</span>
                    <span class="summary-value"><?php echo number_format($grossAmount, 2); ?></span>
                </div>
                
                <!-- Discount(-) with Percentage -->
                <div class="summary-row discount-row">
                    <span class="summary-label">Discount(-) <?php if($discountPercent > 0): ?>(<?php echo number_format($discountPercent, 2); ?>%)<?php endif; ?></span>
                    <span class="summary-value">- <?php echo number_format($totalDiscount, 2); ?></span>
                </div>
                
                <!-- Round Off Amount -->
                <div class="summary-row">
                    <span class="summary-label">Round Off Amount</span>
                    <span class="summary-value"><?php 
                        if ($roundOff != 0) {
                            echo number_format($roundOff, 2);
                        } else {
                            echo '0.00';
                        }
                    ?></span>
                </div>
                
                <!-- Net Amount (rounded) -->
                <div class="summary-row net-payable">
                    <span class="summary-label">Net Amount</span>
                    <span class="summary-value"><?php echo number_format($netAmount, 2); ?></span>
                </div>
                
                <!-- Due Amount -->
                <div class="summary-row">
                    <span class="summary-label">Due</span>
                    <span class="summary-value"><?php echo number_format($dueAmount, 2); ?></span>
                </div>
                
                <!-- Amt Received (Taka) -->
                <div class="summary-row">
                    <span class="summary-label">Amt Received (Taka)</span>
                    <span class="summary-value"><?php echo number_format($amtReceived, 2); ?></span>
                </div>
            </div>
        </div>
        
        <!-- ============================================================ -->
        <!-- PAYMENT STATUS - Only "Paid" or "Unpaid" -->
        <!-- ============================================================ -->
        <div class="payment-status-section">
            <?php 
            // Determine payment status - ONLY "Paid" or "Unpaid"
            if ($dueAmount == 0 && $amtReceived > 0) {
                $paymentStatus = 'paid';
            } else {
                $paymentStatus = 'unpaid';
            }
            $statusDisplay = ucfirst($paymentStatus);
            ?>
            <div class="status">
                <?php echo strtoupper($statusDisplay); ?>
            </div>
            <div class="amount-word">
                <strong>In Word :</strong> <span id="amountInWords">Loading...</span>
            </div>
        </div>
        
        <!-- ============================================================ -->
        <!-- SIGNATURE & LAB NOTE - Same Line, No Blank Space, No Underline -->
        <!-- ============================================================ -->
        <div class="signature-lab-section">
            <div class="lab-note-left">
                <?php if($hasLabTest): ?>
                    📋 Report collect time 8AM to 10PM. <br>
                    📋 Please collect your report within 30days.
                <?php else: ?>
                    &nbsp;
                <?php endif; ?>
            </div>
            <div class="signature-right">
                <div class="prepared-by"><strong>Prepared By :</strong> <?php echo $currentUserName; ?></div>
                <div class="billing-date"><?php echo $billDateTime; ?></div>
            </div>
        </div>
        
        <!-- ============================================================ -->
        <!-- PAYMENT HISTORY - Last Section, Compact, 50% Width, Left Aligned -->
        <!-- ============================================================ -->
        <?php if(!empty($paymentHistory)): ?>
        <div class="payment-history-section">
            <table class="payment-history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>User Name</th>
                        <th>Amount</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($paymentHistory as $pay): 
                        // Use created_at for time as it has the full timestamp
                        // payment_date may only have date without time
                        $payTimestamp = isset($pay['created_at']) && !empty($pay['created_at']) 
                            ? $pay['created_at'] 
                            : (isset($pay['payment_date']) ? $pay['payment_date'] : date('Y-m-d H:i:s'));
                        
                        // Format date and time from the actual payment timestamp
                        $payDate = date('d-M-Y', strtotime($payTimestamp));
                        $payTime = date('h:i A', strtotime($payTimestamp));
                        
                        $receivedByName = isset($pay['first_name']) ? $pay['first_name'] . ' ' . $pay['last_name'] : 'System';
                        $methodDisplay = ucfirst($pay['payment_method'] ?? 'cash');
                    ?>
                    <tr>
                        <td><?php echo $payDate; ?></td>
                        <td><?php echo $payTime; ?></td>
                        <td><?php echo htmlspecialchars($receivedByName); ?></td>
                        <td><?php echo number_format($pay['amount'], 2); ?></td>
                        <td><?php echo $methodDisplay; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- PAYMENT BUTTON (if balance due) -->
        <?php if($dueAmount > 0): ?>
        <div class="payment-btn-wrapper no-print">
            <button class="btn-pay" onclick="openPaymentModal(<?php echo $bill['id']; ?>, <?php echo $dueAmount; ?>, <?php echo $bill['patient_id']; ?>)">
                <i class="fas fa-credit-card me-2"></i>Pay Due: <?php echo number_format($dueAmount, 2); ?>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- FOOTER -->
        <div class="invoice-footer">
            <div class="footer-left">
                <div class="footer-title">Bangladesh Cancer &amp; Palliative Care Center (BCPCC)</div>
                Green City Square, Lift- 5, 750 Shatmosjid Road, Dhanmondi, Dhaka- 1209
            </div>
            <div class="footer-right">
                <div class="footer-title">Thank You</div>
                <span style="display:block;">www.bcpcc.net</span>
                <?php if(isset($bill['reference_type']) && $bill['reference_type'] == 'lab_order'): ?>
                    <span style="display:block;">Lab Order: #<?php echo $bill['reference_id']; ?></span>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    
    <!-- ACTION BUTTONS - No Print -->
    <div class="action-buttons no-print">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Print Invoice
        </button>
        <a href="<?php echo BASE_URL; ?>/bills" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
        <?php if(isset($bill['reference_type']) && $bill['reference_type'] == 'lab_order' && isset($bill['reference_id'])): ?>
        <a href="<?php echo BASE_URL; ?>/lab/orders/<?php echo $bill['reference_id']; ?>" class="btn btn-outline">
            <i class="fas fa-flask me-2"></i>View Lab Order
        </a>
        <?php endif; ?>
    </div>
    
</div>

<!-- ================================================================ -->
<!-- PAYMENT MODAL -->
<!-- ================================================================ -->
<div id="paymentModal" class="modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-credit-card me-2"></i>Receive Payment</h6>
                <button type="button" class="btn-close" onclick="closePaymentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" name="bill_id" id="paymentBillId">
                    <input type="hidden" name="patient_id" id="paymentPatientId">
                    
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Due Amount</label>
                        <input type="text" id="dueAmount" class="form-control form-control-sm" readonly>
                    </div>
                    
                    <div class="mb-2">
                        <div class="form-check">
                            <input type="checkbox" id="fullDiscountCheck" class="form-check-input">
                            <label class="form-check-label small" for="fullDiscountCheck">
                                <strong>✅ Full Discount</strong> – Mark as Paid without receiving cash
                            </label>
                        </div>
                    </div>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Discount Applied (%)</label>
                            <input type="number" step="0.01" min="0" max="100" id="discountPercent" class="form-control form-control-sm" placeholder="e.g. 10">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Discount Amount (৳)</label>
                            <input type="number" step="0.01" min="0" id="discountAmount" class="form-control form-control-sm" placeholder="e.g. 50">
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Receive Amount (after discount) *</label>
                        <input type="number" step="0.01" name="amount" id="paymentAmount" class="form-control form-control-sm" required>
                        <small class="text-muted">Enter <strong>0</strong> if discount covers the full due amount</small>
                        <div id="discountCoversDue" class="alert alert-success mt-1" style="display:none; font-size:0.8rem;">
                            ✅ This discount covers the full due amount. Click <strong>Process Payment</strong> to mark as Paid.
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Payment Method *</label>
                        <select name="payment_method" id="paymentMethodSelect" class="form-select form-select-sm" required>
                            <option value="cash">💵 Cash</option>
                            <option value="card">💳 Card</option>
                            <option value="mobile_banking">📱 Mobile Banking</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Transaction ID</label>
                        <input type="text" name="transaction_id" id="transactionId" class="form-control form-control-sm" placeholder="Optional">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Notes</label>
                        <textarea name="notes" id="paymentNotes" class="form-control form-control-sm" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closePaymentModal()">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" onclick="submitPayment()">Process Payment</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

let currentBillId = null;
let currentDueAmount = null;
let currentPatientId = null;

function convertNumberToWords(amount) {
    $.ajax({
        url: BASE_URL + '/api/convert-number-to-words',
        method: 'POST',
        data: { amount: amount },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#amountInWords').text(response.words);
            } else {
                $('#amountInWords').text(numberToWordsFallback(amount));
            }
        },
        error: function() {
            $('#amountInWords').text(numberToWordsFallback(amount));
        }
    });
}

function numberToWordsFallback(num) {
    if (num === 0) return 'Zero';
    var words = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 
                 'Seventeen', 'Eighteen', 'Nineteen'];
    var tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    function convert(n) {
        if (n < 20) return words[n];
        if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? '-' + words[n % 10] : '');
        if (n < 1000) return words[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + convert(n % 100) : '');
        if (n < 100000) return convert(Math.floor(n / 1000)) + ' Thousand' + (n % 1000 ? ' ' + convert(n % 1000) : '');
        if (n < 10000000) return convert(Math.floor(n / 100000)) + ' Lakh' + (n % 100000 ? ' ' + convert(n % 100000) : '');
        return convert(Math.floor(n / 10000000)) + ' Crore' + (n % 10000000 ? ' ' + convert(n % 10000000) : '');
    }
    
    var numStr = num.toFixed(2);
    var parts = numStr.split('.');
    var integerPart = parseInt(parts[0]);
    var decimalPart = parseInt(parts[1]);
    var result = convert(integerPart);
    
    if (decimalPart > 0) {
        result += ' point ' + convert(decimalPart);
    }
    
    return result + ' only';
}

// ============================================================
// SHOW PAYMENT MODAL
// ============================================================
function openPaymentModal(billId, dueAmount, patientId) {
    currentBillId = billId;
    currentDueAmount = parseFloat(dueAmount) || 0;
    currentPatientId = patientId;
    
    $('#paymentBillId').val(billId);
    $('#paymentPatientId').val(patientId);
    $('#dueAmount').val('৳ ' + currentDueAmount.toFixed(2));
    $('#paymentAmount').val(currentDueAmount);
    $('#discountPercent').val('');
    $('#discountAmount').val('');
    $('#paymentMethodSelect').val('cash');
    $('#transactionId').val('');
    $('#paymentNotes').val('');
    $('#discountCoversDue').hide();
    $('#fullDiscountCheck').prop('checked', false);
    
    document.getElementById('paymentModal').style.display = 'block';
    document.getElementById('paymentModal').classList.add('show');
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    document.getElementById('paymentModal').classList.remove('show');
}

// ============================================================
// FULL DISCOUNT – set discount = due, receive = 0
// ============================================================
$(document).on('change', '#fullDiscountCheck', function() {
    if ($(this).is(':checked')) {
        let due = currentDueAmount;
        $('#discountAmount').val(due.toFixed(2)).trigger('input');
        $('#discountPercent').val('');
    } else {
        $('#discountAmount').val('').trigger('input');
        $('#discountPercent').val('');
    }
});

// ============================================================
// UPDATE RECEIVE AMOUNT on discount change
// ============================================================
$(document).on('input', '#discountPercent, #discountAmount', function() {
    let due = currentDueAmount;
    let discPercent = parseFloat($('#discountPercent').val()) || 0;
    let discAmount = parseFloat($('#discountAmount').val()) || 0;
    
    let discount = 0;
    if ($('#discountAmount').val() !== '') {
        discount = discAmount;
    } else if (discPercent > 0) {
        discount = due * (discPercent / 100);
    }
    if (discount > due) discount = due;
    
    let receiveAmount = due - discount;
    $('#paymentAmount').val(receiveAmount.toFixed(2));
    
    if (discount > 0 && Math.abs(discount - due) < 0.01) {
        $('#discountCoversDue').show();
    } else {
        $('#discountCoversDue').hide();
    }
});

// ============================================================
// SUBMIT PAYMENT
// ============================================================
function submitPayment() {
    let amount = parseFloat($('#paymentAmount').val());
    let dueAmount = currentDueAmount;
    let discountPercent = parseFloat($('#discountPercent').val()) || 0;
    let discountAmount = parseFloat($('#discountAmount').val()) || 0;
    let fullDiscount = $('#fullDiscountCheck').is(':checked') ? 1 : 0;
    
    if (isNaN(amount) || amount < 0) {
        Swal.fire('Error', 'Please enter a valid receive amount (0 or more)', 'error');
        return;
    }
    
    if (fullDiscount) {
        amount = 0;
        discountAmount = dueAmount;
        $('#paymentAmount').val(0);
        $('#discountAmount').val(dueAmount);
    }
    
    let totalApplied = discountAmount + amount;
    if (totalApplied > dueAmount + 0.01) {
        Swal.fire('Error', 'Total of discount and receive amount cannot exceed due amount of ৳ ' + dueAmount.toFixed(2), 'error');
        return;
    }
    
    let formData = $('#paymentForm').serialize();
    formData += '&discount_percent=' + discountPercent;
    formData += '&discount_amount=' + discountAmount;
    formData += '&full_discount=' + fullDiscount;
    
    Swal.fire({
        title: 'Processing Payment...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/api/process-bill-payment',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let message = response.message;
                if (response.new_balance == 0) {
                    message += ' Bill is now fully paid!';
                }
                Swal.fire({
                    title: 'Success!',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    closePaymentModal();
                    location.reload();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            let errorMsg = 'Payment processing failed. Please try again.';
            try {
                let response = JSON.parse(xhr.responseText);
                if(response.message) errorMsg = response.message;
            } catch(e) {}
            Swal.fire('Error', errorMsg, 'error');
        }
    });
}

// ============================================================
// CLOSE MODALS ON CLICK OUTSIDE
// ============================================================
window.onclick = function(event) {
    let paymentModal = document.getElementById('paymentModal');
    if (event.target == paymentModal) {
        closePaymentModal();
    }
}

$(document).ready(function() {
    var netPayable = <?php echo isset($netAmount) ? $netAmount : 0; ?>;
    if (netPayable > 0) {
        convertNumberToWords(netPayable);
    } else {
        var billTotal = <?php echo isset($bill['total_amount']) ? $bill['total_amount'] : 0; ?>;
        if (billTotal > 0) {
            convertNumberToWords(billTotal);
        } else {
            $('#amountInWords').text('Zero only.');
        }
    }
    
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>