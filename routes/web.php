<?php
// routes/web.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define BASE_URL if not defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '/unidia/public');
}

// ==================== ROUTE DEFINITIONS ====================

// ===== AUTH ROUTES =====
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@authenticate');
$router->get('/logout', 'AuthController@logout');

// ===== DASHBOARD ROUTES =====
$router->get('/admin/dashboard', 'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// ===== PATIENT ROUTES =====
$router->get('/patient/list', 'PatientController@index');
$router->get('/patient/register', 'PatientController@create');
$router->post('/patient/store', 'PatientController@store');
$router->get('/patient/show/{id}', 'PatientController@show');
$router->get('/patient/edit/{id}', 'PatientController@edit');
$router->post('/patient/update/{id}', 'PatientController@update');
$router->get('/patient/delete/{id}', 'PatientController@delete');
$router->get('/patient/id-card', 'PatientController@idCard');
$router->get('/patient/portal-login', 'PatientController@portalLogin');

// Barcode routes
$router->addRoute('GET', '/barcode/view', 'BarcodeController@view');
$router->addRoute('GET', '/barcode/print', 'BarcodeController@print');
$router->addRoute('GET', '/barcode/download', 'BarcodeController@download');

// Patient ID Card routes
$router->addRoute('GET', '/patient/id-card-front', 'PatientController@idCardFront');
$router->addRoute('GET', '/patient/id-card-back', 'PatientController@idCardBack');
$router->addRoute('GET', '/patient/print-id-front', 'PatientController@printIdCardFront');
$router->addRoute('GET', '/patient/print-id-back', 'PatientController@printIdCardBack');

// ===== DOCTOR ROUTES =====
$router->get('/doctor/list', 'DoctorController@index');
$router->get('/doctor/create', 'DoctorController@create');
$router->post('/doctor/store', 'DoctorController@store');
$router->get('/doctor/edit/{id}', 'DoctorController@edit');
$router->post('/doctor/update/{id}', 'DoctorController@update');
$router->get('/doctor/delete/{id}', 'DoctorController@delete');
$router->get('/doctor/schedule-list', 'DoctorController@scheduleList');
$router->get('/doctor/schedule', 'DoctorController@schedule');
$router->post('/doctor/schedule/store', 'DoctorController@storeSchedule');
$router->get('/doctor/commissions', 'DoctorController@commissions');

// ===== APPOINTMENT ROUTES =====
$router->get('/reception/appointments', 'AppointmentController@index');
$router->get('/reception/appointments/create', 'AppointmentController@create');
$router->post('/reception/appointments/store', 'AppointmentController@store');
$router->get('/reception/appointments/edit/{id}', 'AppointmentController@edit');
$router->post('/reception/appointments/update/{id}', 'AppointmentController@update');
$router->get('/reception/appointments/delete/{id}', 'AppointmentController@delete');
$router->get('/reception/daily-list', 'AppointmentController@dailyList');
$router->get('/reception/doctor-list', 'AppointmentController@doctorList');
$router->get('/reception/check-in', 'AppointmentController@checkIn');
$router->post('/reception/check-in', 'AppointmentController@processCheckIn');
$router->get('/reception/queue', 'AppointmentController@queue');
$router->get('/patient/book-appointment', 'AppointmentController@book');

// User routes
$router->get('/admin/users', 'UserController@index');
$router->get('/admin/users/create', 'UserController@create');
$router->post('/admin/users/store', 'UserController@store');
$router->get('/admin/users/edit/{id}', 'UserController@edit');
$router->post('/admin/users/update/{id}', 'UserController@update');
$router->get('/admin/users/delete/{id}', 'UserController@delete');

// Roles & Permissions
$router->get('/admin/users/roles', 'UserController@roles');
$router->post('/admin/users/roles/update/{id}', 'UserController@updateRolePermissions');

// Attendance
$router->get('/admin/users/attendance', 'UserController@attendance');
$router->post('/admin/users/api/save-attendance', 'UserController@apiSaveAttendance');
$router->post('/admin/users/api/save-all-attendance', 'UserController@apiSaveAllAttendance');
$router->get('/admin/users/api/export-monthly-attendance', 'UserController@apiExportMonthlyAttendance');

// Payroll
$router->get('/admin/users/payroll', 'UserController@payroll');
$router->post('/admin/users/generate-payroll', 'UserController@generatePayroll');

// ===== ATTENDANCE API ROUTES =====
$router->post('/api/attendance/save', 'UserController@apiSaveAttendance');
$router->post('/api/attendance/save-all', 'UserController@apiSaveAllAttendance');
$router->get('/api/attendance/export-monthly', 'UserController@apiExportMonthlyAttendance');

// ===== PAYROLL API ROUTES =====
$router->post('/api/payroll/generate-all', 'UserController@apiGenerateAllPayroll');
$router->get('/api/payroll/get-details', 'UserController@apiGetPayrollDetails');
$router->post('/api/payroll/update-salary', 'UserController@apiUpdateSalary');
$router->post('/api/payroll/mark-paid', 'UserController@apiMarkPaid');
$router->get('/api/payroll/print-slip', 'UserController@apiPrintSalarySlip');
$router->get('/api/payroll/export', 'UserController@exportPayroll');

// ==================== PRESCRIPTION ROUTES ====================
$router->get('/prescriptions', 'PrescriptionController@index');
$router->get('/prescriptions/list', 'PrescriptionController@index');
$router->get('/prescriptions/create', 'PrescriptionController@create');
$router->post('/prescriptions/save', 'PrescriptionController@save');
$router->post('/prescriptions/save-tab', 'PrescriptionController@saveTab');
$router->get('/prescriptions/previous-list', 'PrescriptionController@getPreviousPrescriptions');
$router->get('/prescriptions/load-tab', 'PrescriptionController@loadTabData');
$router->get('/prescriptions/search-drugs', 'PrescriptionController@searchDrugs');
$router->get('/api/medicines', 'PrescriptionController@apiMedicines');
$router->get('/prescriptions/show/{id}', 'PrescriptionController@show');
$router->get('/prescriptions/view/{id}', 'PrescriptionController@show');
$router->get('/prescriptions/edit/{id}', 'PrescriptionController@edit');
$router->post('/prescriptions/update/{id}', 'PrescriptionController@update');
$router->get('/prescriptions/print/{id}', 'PrescriptionController@printView');
$router->get('/prescriptions/print-pad/{id}', 'PrescriptionController@printPad');
$router->get('/prescriptions/export-pdf/{id}', 'PrescriptionController@exportPdf');
$router->get('/prescriptions/duplicate/{id}', 'PrescriptionController@duplicate');
$router->post('/prescriptions/cancel/{id}', 'PrescriptionController@cancel');
$router->get('/prescriptions/patient/{id}', 'PrescriptionController@patientPrescriptions');
$router->get('/prescriptions/delete/{id}', 'PrescriptionController@delete');

// ===== PHARMACY API ROUTES =====
$router->post('/pharmacy/add-to-cart', 'PharmacyController@addToCart');
$router->get('/pharmacy/get-cart', 'PharmacyController@getCart');
$router->post('/pharmacy/clear-cart', 'PharmacyController@clearCart');
$router->post('/pharmacy/remove-from-cart', 'PharmacyController@removeFromCart');
$router->post('/pharmacy/update-cart', 'PharmacyController@updateCart');
$router->post('/pharmacy/process-sale', 'PharmacyController@processSale');
$router->post('/pharmacy/add-stock', 'PharmacyController@addStock');
$router->post('/pharmacy/add-medicine', 'PharmacyController@addMedicine');
$router->get('/pharmacy/get-medicine', 'PharmacyController@getMedicine');
$router->get('/pharmacy/stock-history', 'PharmacyController@stockHistory');
$router->post('/pharmacy/delete-stock', 'PharmacyController@deleteStock');
$router->post('/pharmacy/add-category', 'PharmacyController@addCategory');
$router->post('/pharmacy/update-category', 'PharmacyController@updateCategory');
$router->post('/pharmacy/delete-category', 'PharmacyController@deleteCategory');
$router->post('/pharmacy/update-medicine', 'PharmacyController@updateMedicine');
$router->post('/pharmacy/dispense-process', 'PharmacyController@dispenseProcess');

// ===== LAB ROUTES =====
$router->get('/lab/dashboard', 'LabController@dashboard');
$router->get('/lab/orders', 'LabController@orders');
$router->get('/lab/orders/pending', 'LabController@pendingOrders');
$router->get('/lab/orders/in-progress', 'LabController@inProgressOrders');
$router->get('/lab/orders/completed', 'LabController@completedOrders');
$router->get('/lab/create-order', 'LabController@createOrder');
$router->post('/lab/orders/store', 'LabController@storeOrder');
$router->get('/lab/results', 'LabController@results');
$router->post('/lab/results/enter', 'LabController@enterResults');
$router->get('/lab/reports', 'LabController@reports');
$router->get('/lab/test-categories', 'LabController@testCategories');
$router->get('/lab/sample-collection', 'LabController@sampleCollection');
$router->post('/lab/update-sample-status', 'LabController@updateSampleStatus');
$router->get('/lab/generate-single-barcode', 'LabController@generateSingleBarcode');
$router->get('/lab/print-barcode', 'LabController@printBarcode');
$router->post('/lab/bulk-collect-samples', 'LabController@bulkCollectSamples');
$router->get('/lab/get-sample-details', 'LabController@getSampleDetails');
$router->get('/lab/view-report-ajax', 'LabController@viewReportAjax');
$router->get('/lab/print-report', 'LabController@printReport');
$router->get('/lab/export-reports', 'LabController@exportReports');

// ===== LAB API ROUTES =====
$router->get('/api/lab-orders', 'LabController@apiLabOrders');
$router->get('/api/stat-orders', 'LabController@apiStatOrders');
$router->get('/api/validate-barcode', 'LabController@apiValidateBarcode');
$router->post('/api/collect-by-barcode', 'LabController@apiCollectByBarcode');
$router->get('/api/doctors', 'LabController@apiDoctors');
$router->get('/api/lab-reports-list', 'LabController@apiLabReportsList');
$router->get('/api/lab-orders-list', 'LabController@apiLabOrdersList');
$router->get('/api/lab-order-details/{id}', 'LabController@apiLabOrderDetails');

// ===== INVENTORY ROUTES =====
$router->get('/inventory/dashboard', 'InventoryController@dashboard');
$router->get('/inventory/items', 'InventoryController@items');
$router->get('/inventory/items/add', 'InventoryController@addItemForm');
$router->post('/inventory/add-item', 'InventoryController@addItem');
$router->get('/inventory/stock', 'InventoryController@stock');
$router->post('/inventory/add-stock', 'InventoryController@addStock');
$router->post('/inventory/delete-stock', 'InventoryController@deleteStock');
$router->get('/inventory/expiry-alerts', 'InventoryController@expiryAlerts');
$router->get('/inventory/reorder-alerts', 'InventoryController@reorderAlerts');
$router->get('/inventory/suppliers', 'InventoryController@suppliers');
$router->get('/inventory/stores', 'InventoryController@stores');
$router->get('/inventory/stock-transfers', 'InventoryController@stockTransfers');
$router->get('/inventory/purchase-orders', 'InventoryController@purchaseOrders');
$router->get('/inventory/reports', 'InventoryController@reports');
$router->get('/inventory/view-item', 'InventoryController@viewItem');
$router->get('/inventory/recent-activity', 'InventoryController@recentActivity');
$router->post('/inventory/create-po', 'InventoryController@createPurchaseOrder');
$router->post('/inventory/update-po', 'InventoryController@updatePurchaseOrder');
$router->post('/inventory/approve-po', 'InventoryController@approvePurchaseOrder');
$router->post('/inventory/receive-po', 'InventoryController@receivePurchaseOrder');
$router->post('/inventory/cancel-po', 'InventoryController@cancelPurchaseOrder');
$router->post('/inventory/ignore-reorder-alert', 'InventoryController@ignoreReorderAlert');

// ===== BILLING ROUTES =====
$router->get('/bills/bills', 'BillingController@index');
$router->get('/bills/create', 'BillingController@create');
$router->post('/bills/store', 'BillingController@store');
$router->get('/bills/edit/{id}', 'BillingController@edit');
$router->post('/bills/update/{id}', 'BillingController@update');
$router->get('/bills/delete/{id}', 'BillingController@delete');
$router->get('/bills/show/{id}', 'BillingController@show');
$router->get('/bills/invoice/{id}', 'BillingController@invoice');
$router->get('/bills/payments', 'BillingController@payments');
$router->post('/bills/payments/store', 'BillingController@storePayment');
$router->get('/bills/pending', 'BillingController@pending');
$router->get('/bills/insurance', 'BillingController@insurance');
$router->post('/bills/insurance/submit', 'BillingController@submitInsurance');
$router->get('/account/dashboard', 'BillingController@accountDashboard');
$router->get('/account/reports', 'BillingController@financialReports');
$router->get('/account/reports/daily', 'BillingController@dailyReport');
$router->get('/account/reports/monthly', 'BillingController@monthlyReport');
$router->get('/account/reports/yearly', 'BillingController@yearlyReport');
$router->get('/account/reports/revenue', 'BillingController@revenueReport');
$router->get('/bills/process-payment', 'BillingController@processPayment');
$router->get('/bills/get-payment-history', 'BillingController@getPaymentHistory');
$router->get('/bills/export', 'BillingController@exportBills');

// ===== BILLING API ROUTES =====
$router->get('/api/bill-details', 'BillingController@getBillDetailsAjax');
$router->get('/api/get-bill-patient', 'BillingController@getBillPatient');
$router->get('/api/get-payment-history', 'BillingController@getPaymentHistory');

// ===== PRESCRIPTION ROUTES =====
$router->get('/prescriptions', 'PrescriptionController@index');
$router->get('/prescriptions/list', 'PrescriptionController@index');
$router->get('/prescriptions/create', 'PrescriptionController@create');
$router->post('/prescriptions/save', 'PrescriptionController@save');
$router->post('/prescriptions/save-tab', 'PrescriptionController@saveTab');
$router->get('/prescriptions/previous-list', 'PrescriptionController@getPreviousPrescriptions');
$router->get('/prescriptions/load-tab', 'PrescriptionController@loadTabData');
$router->get('/prescriptions/search-drugs', 'PrescriptionController@searchDrugs');
$router->get('/api/medicines', 'PrescriptionController@apiMedicines');
$router->get('/prescriptions/show/{id}', 'PrescriptionController@show');
$router->get('/prescriptions/view/{id}', 'PrescriptionController@show');
$router->get('/prescriptions/edit/{id}', 'PrescriptionController@edit');
$router->post('/prescriptions/update/{id}', 'PrescriptionController@update');
$router->get('/prescriptions/print/{id}', 'PrescriptionController@printView');
$router->get('/prescriptions/print-pad/{id}', 'PrescriptionController@printPad');
$router->get('/prescriptions/export-pdf/{id}', 'PrescriptionController@exportPdf');
$router->get('/prescriptions/duplicate/{id}', 'PrescriptionController@duplicate');
$router->post('/prescriptions/cancel/{id}', 'PrescriptionController@cancel');
$router->get('/prescriptions/patient/{id}', 'PrescriptionController@patientPrescriptions');
$router->get('/prescriptions/delete/{id}', 'PrescriptionController@delete');

// ===== PRESCRIPTION API ROUTES =====
$router->get('/prescriptions/api-medicines', 'PrescriptionController@apiMedicines');
$router->get('/prescriptions/get-previous-prescriptions', 'PrescriptionController@getPreviousPrescriptions');
$router->get('/prescriptions/load-tab-data', 'PrescriptionController@loadTabData');

// ===== ADMIN ROUTES =====
$router->get('/admin/audit-logs', 'AdminController@auditLogs');
$router->get('/admin/settings', 'SettingsController@index');
$router->post('/admin/settings/update', 'SettingsController@update');
$router->get('/admin/settings/backup-history', 'SettingsController@backupHistory');
$router->post('/admin/settings/backup', 'SettingsController@backup');
$router->get('/admin/clear-cache', 'AdminController@clearCache');
$router->get('/admin/system-info', 'AdminController@systemInfo');
$router->get('/api/dashboard/stats', 'AdminController@getDashboardStats');
$router->get('/api/activity-log', 'AdminController@activityLog');

// ===== COMMISSION ROUTES =====
$router->post('/doctor/commission/approve', 'DoctorController@apiApproveCommission');
$router->post('/doctor/commission/pay', 'DoctorController@apiPayCommission');
$router->get('/doctor/commission/details/{id}', 'DoctorController@apiCommissionDetails');
$router->get('/doctor/commission/export', 'DoctorController@apiExportCommissions');
$router->get('/doctor/commission/report', 'DoctorController@apiGenerateCommissionReport');
$router->get('/doctor/commission/print/{id}', 'DoctorController@apiPrintCommission');

// ===== RECEPTION API ROUTES =====
$router->post('/reception/do-checkin', 'ReceptionController@doCheckIn');
$router->post('/reception/call-patient', 'ReceptionController@callPatient');
$router->post('/reception/complete-consultation', 'ReceptionController@completeConsultation');
$router->get('/reception/search', 'ReceptionController@searchPatient');
$router->get('/reception/get-patient-appointments', 'ReceptionController@getPatientAppointments');
$router->get('/reception/get-queue-status', 'ReceptionController@getQueueStatus');
$router->get('/reception/get-filtered-queue', 'ReceptionController@getFilteredQueue');
$router->get('/reception/get-queue-stats', 'ReceptionController@getQueueStats');

// ===== APPOINTMENT API ROUTES =====
$router->get('/api/get-doctor-services', 'AppointmentController@getDoctorServices');
$router->get('/api/get-available-slots', 'AppointmentController@getAvailableSlots');
$router->post('/api/book-appointment', 'AppointmentController@apiBookAppointment');
$router->post('/api/update-appointment-status', 'AppointmentController@updateAppointmentStatus');
$router->get('/api/get-appointment-details', 'AppointmentController@getAppointmentDetails');
$router->post('/api/cancel-appointment', 'AppointmentController@cancelAppointment');
$router->post('/api/process-payment', 'AppointmentController@processPayment');

// ===== GENERAL API ROUTES =====
//$router->get('/api/search-patients', 'AppointmentController@searchPatients');
$router->get('/api/search-doctors', 'AppointmentController@searchDoctors');
$router->get('/api/get-patient-details', 'AppointmentController@getPatientDetails');
$router->get('/api/get-doctors', 'AppointmentController@getDoctors');
$router->get('/api/get-doctor-availability', 'AppointmentController@getDoctorAvailability');
$router->get('/api/get-districts', 'AppointmentController@getDistricts');
$router->get('/api/get-thanas', 'AppointmentController@getThanas');
$router->get('/api/get-filtered-appointments', 'AppointmentController@getFilteredAppointments');

// ===== PROFILE ROUTES =====
$router->get('/profile', 'ProfileController@index');
$router->post('/profile/update', 'ProfileController@update');
$router->get('/change-password', 'ProfileController@changePassword');
$router->post('/change-password', 'ProfileController@updatePassword');

// Add this route for backup download - use the standalone script
$router->get('/admin/settings/download-backup/(:num)', function($id) {
    // Redirect to standalone download script
    header('Location: /unidia/public/download_backup.php?id=' . $id);
    exit;
});

// Reception Routes
$router->get('/reception/appointments', 'AppointmentController@receptionIndex');
$router->get('/reception/daily-list', 'AppointmentController@dailyList');
$router->get('/reception/check-in', 'AppointmentController@checkIn');

// Roles & Permissions
$router->get('/admin/users/roles', 'UserController@roles');
$router->post('/admin/users/roles/update/{id}', 'UserController@updateRolePermissions');

$router = new Router();

// ==================== PATIENT ROUTES ====================
$router->get('/patient/list', 'PatientController@list');
$router->get('/patient/register', 'PatientController@register');
$router->post('/patient/store', 'PatientController@store');
$router->get('/patient/show', 'PatientController@show');
$router->get('/patient/edit', 'PatientController@edit');
$router->post('/patient/update', 'PatientController@update');
$router->get('/patient/delete', 'PatientController@delete');
$router->get('/patient/id-card', 'PatientController@idCard');

$router->addRoute('GET', '/patient/list', 'PatientController', 'list');

// ===== THIS IS THE IMPORTANT ROUTE - ADD THIS =====
$router->post('/patient/quick-store', 'PatientController@quickStore');

// Vaccine routes
$router->addRoute('GET', '/vaccines', 'VaccineController@index');
$router->addRoute('GET', '/vaccines/create', 'VaccineController@create');
$router->addRoute('POST', '/vaccines/save', 'VaccineController@save');
$router->addRoute('GET', '/vaccines/edit/(\d+)', 'VaccineController@edit');
$router->addRoute('POST', '/vaccines/update/(\d+)', 'VaccineController@update');
$router->addRoute('GET', '/vaccines/delete/(\d+)', 'VaccineController@delete');
$router->addRoute('GET', '/vaccines/print-card/(\d+)', 'VaccineController@printCard');
$router->addRoute('GET', '/vaccines/api-get-vaccines', 'VaccineController@apiGetVaccines');

// API Routes for Prescriptions
$router->addRoute('GET', '/api/get-patient-prescriptions', 'PrescriptionController@apiGetPatientPrescriptions');
$router->addRoute('GET', '/api/get-patient-vaccines', 'PrescriptionController@apiGetPatientVaccines');
$router->addRoute('GET', '/api/get-patient-appointments', 'PrescriptionController@apiGetPatientAppointments');

$router->addRoute('GET', '/laboratory/api-lab-tests', 'LaboratoryController', 'apiLabTests');

// ================================================================
// LAB TEST MANAGEMENT ROUTES
// ================================================================
$router->addRoute('GET', '/lab/manage-tests', 'LaboratoryController', 'manageTests');
$router->addRoute('GET', '/api/lab-tests', 'LaboratoryController', 'apiLabTests');
$router->addRoute('GET', '/api/lab-tests/{id}', 'LaboratoryController', 'apiGetLabTest');
$router->addRoute('POST', '/api/lab-tests', 'LaboratoryController', 'apiSaveLabTest');
$router->addRoute('POST', '/api/lab-tests/{id}', 'LaboratoryController', 'apiSaveLabTest');
$router->addRoute('POST', '/api/lab-tests/toggle/{id}', 'LaboratoryController', 'apiToggleLabTest');
$router->addRoute('POST', '/api/lab-tests/delete/{id}', 'LaboratoryController', 'apiDeleteLabTest');

// Lab Test Management - Separate Pages
$router->get('/lab/add-test', 'LaboratoryController@addTest');
$router->get('/lab/edit-test/{id}', 'LaboratoryController@editTest');
$router->post('/lab/api/save-test', 'LaboratoryController@apiSaveTest');

// Lab-Test Prescriptions
$router->add('/lab/prescriptions', 'LaboratoryController@prescriptions');
$router->add('/lab/view-prescription-tests/:id', 'LaboratoryController@viewPrescriptionTests');
$router->add('/lab/create-order-from-prescription', 'LaboratoryController@createOrderFromPrescription', 'POST');
$router->add('/lab/api-get-prescription-lab-tests', 'LaboratoryController@apiGetPrescriptionLabTests');

// Laboratory Routes
$router->add('/lab/dashboard', 'LaboratoryController@dashboard');
$router->add('/lab/orders', 'LaboratoryController@orders');
$router->add('/lab/create-order', 'LaboratoryController@createOrder');
$router->add('/lab/store-order', 'LaboratoryController@storeOrder', 'POST');
$router->add('/lab/view-order/:id', 'LaboratoryController@viewOrder');
$router->add('/lab/orders/:id', 'LaboratoryController@viewOrder');

// Lab Order API
$router->add('/lab/collect-sample', 'LaboratoryController@collectSample', 'POST');
$router->add('/lab/print-barcode', 'LaboratoryController@printBarcode');
$router->add('/lab/enter-results', 'LaboratoryController@enterResults');
$router->add('/lab/print-report', 'LaboratoryController@printReport');
$router->add('/lab/invoice/:id', 'LaboratoryController@generateInvoice');

// API Routes - MUST be defined before any wildcard routes
$router->get('/api/get-doctor-availability', 'AppointmentController@getDoctorAvailability');
$router->get('/api/get-doctor-services', 'AppointmentController@getDoctorServices');
$router->get('/api/get-available-slots', 'AppointmentController@getAvailableSlots');
$router->post('/api/book-appointment', 'AppointmentController@apiBookAppointment');
$router->get('/api/search-patients', 'AppointmentController@searchPatient');


$router->get('/api/search-patients', 'AppointmentController@searchPatient');
$router->get('/api/get-doctor-services', 'AppointmentController@getDoctorServices');
$router->get('/api/get-doctor-availability', 'AppointmentController@getDoctorAvailability');
$router->get('/api/get-available-slots', 'AppointmentController@getAvailableSlots');
$router->post('/api/book-appointment', 'AppointmentController@apiBookAppointment');
$router->post('/patient/quick-store', 'AppointmentController@quickStore');


// Appointment API routes
$router->get('/api/get-doctor-availability', 'AppointmentController@getDoctorAvailability');
$router->get('/api/get-doctor-services', 'AppointmentController@getDoctorServices');
$router->get('/api/get-available-slots', 'AppointmentController@getAvailableSlots');
$router->post('/api/book-appointment', 'AppointmentController@apiBookAppointment');
$router->get('/api/search-patients', 'AppointmentController@searchPatient');

$router->get('/appointments/book', 'AppointmentController@book');
$router->get('/appointments', 'AppointmentController@index');
$router->get('/reception/appointments', 'AppointmentController@receptionIndex');
$router->get('/reception/daily-list', 'AppointmentController@dailyList');
$router->get('/reception/check-in', 'AppointmentController@checkIn');

$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@doLogin');
$router->get('/logout', 'AuthController@logout');
$router->get('/', 'AuthController@login');

// Add this route for dashboard API refresh
$router->addRoute('GET', '/dashboard/api-stats', 'DashboardController', 'apiStats');

// ================================================================
// APPOINTMENT ROUTES
// ================================================================

// View Appointment Details
$router->addRoute('GET', '/appointments/view/:id', 'AppointmentController', 'view');
$router->addRoute('GET', '/appointments/show/:id', 'AppointmentController', 'view'); // Alias

// Book Appointment
$router->addRoute('GET', '/appointments/book', 'AppointmentController', 'book');
$router->addRoute('POST', '/appointments/book', 'AppointmentController', 'store');

// Appointment List
$router->addRoute('GET', '/appointments', 'AppointmentController', 'index');
$router->addRoute('GET', '/appointments/today', 'AppointmentController', 'today');

// API Routes
$router->addRoute('GET', '/api/appointments/:id', 'AppointmentController', 'getDetails');
$router->addRoute('POST', '/api/appointments/update-status', 'AppointmentController', 'updateStatus');
$router->addRoute('POST', '/api/appointments/cancel', 'AppointmentController', 'cancel');
$router->addRoute('POST', '/api/appointments/reschedule', 'AppointmentController', 'reschedule');

// Bill Edit Routes
$router->add('GET', '/bills/edit/(\d+)', 'BillingController@editBill');
$router->add('POST', '/bills/update', 'BillingController@updateBill');
$router->add('POST', '/bills/delete', 'BillingController@deleteBill');

// ===== 403/404 ROUTES =====
$router->get('/403', 'ErrorController@error403');
$router->get('/404', 'ErrorController@error404');