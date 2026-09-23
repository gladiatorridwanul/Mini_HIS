<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .doctor-card { border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: white; }
    .doctor-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 12px 12px 0 0; padding: 15px 20px; }
    .serial-badge { font-size: 16px; font-weight: bold; background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; display: inline-block; }
    .print-btn { background: none; border: none; color: #10b981; cursor: pointer; }
    .status-scheduled { background: #f59e0b; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .status-completed { background: #10b981; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .status-in_progress { background: #3b82f6; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .table th { background: #f8fafc; }
    @media print { .no-print { display: none; } }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2><i class="fas fa-user-md text-success me-2"></i> Doctor Wise Patient List</h2>
    <div>
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print All</button>
        <a href="<?php echo BASE_URL; ?>/patient/book-appointment" class="btn btn-success"><i class="fas fa-plus"></i> New Appointment</a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <label class="form-label fw-bold">Select Doctor</label>
                <select id="doctor_id" class="form-select">
                    <option value="">-- Select Doctor --</option>
                    <?php foreach($doctors as $d): ?>
                    <option value="<?php echo $d['id']; ?>">
                        Dr. <?php echo $d['first_name'] . ' ' . $d['last_name']; ?> - <?php echo $d['specialization']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Select Date</label>
                <input type="date" id="filter_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">&nbsp;</label>
                <button id="filter_btn" class="btn btn-success w-100"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
    </div>
</div>

<div id="loadingSpinner" class="text-center py-5" style="display: none;">
    <div class="spinner-border text-success" role="status"></div>
    <p class="mt-2">Loading patients...</p>
</div>

<div id="results_container"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function loadDoctorPatients() {
    const doctorId = $('#doctor_id').val();
    const date = $('#filter_date').val();
    
    if(!doctorId) {
        $('#results_container').html(`
            <div class="alert alert-info text-center py-4">
                <i class="fas fa-info-circle fa-2x mb-2"></i>
                <p>Please select a doctor to view patient list</p>
            </div>
        `);
        return;
    }
    
    $('#loadingSpinner').show();
    $('#results_container').html('');
    
    $.ajax({
        url: BASE_URL + '/api/doctor-daily-patients',  // ← This URL must exist
        method: 'GET',
        data: { doctor_id: doctorId, date: date },
        dataType: 'json',
        timeout: 15000,
        success: function(response) {
            $('#loadingSpinner').hide();
            
            if(response.error) {
                showError(response.error);
                return;
            }
            
            const patients = response;
            
            if(patients && patients.length > 0) {
                const morningPatients = patients.filter(p => p.session_type === 'morning');
                const eveningPatients = patients.filter(p => p.session_type === 'evening');
                
                let html = '';
                html += renderDoctorSection('Morning Session', '🌅', morningPatients);
                html += renderDoctorSection('Evening Session', '🌙', eveningPatients);
                
                $('#results_container').html(html);
            } else {
                $('#results_container').html(`
                    <div class="alert alert-warning text-center py-4">
                        <i class="fas fa-calendar-day fa-2x mb-2"></i>
                        <p>No patients found for this doctor on selected date</p>
                        <a href="<?php echo BASE_URL; ?>/patient/book-appointment" class="btn btn-sm btn-success">Book Appointment</a>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            $('#loadingSpinner').hide();
            console.error('Error:', xhr.responseText);
            console.error('Status:', status);
            console.error('Error:', error);
            showError('Failed to load patients. Please try again. Error: ' + (xhr.responseText || error));
        }
    });
}

function renderDoctorSection(title, icon, patients) {
    if(!patients || patients.length === 0) {
        return `
            <div class="doctor-card">
                <div class="doctor-header">
                    <h5 class="mb-0">${icon} ${title}</h5>
                </div>
                <div class="card-body text-center text-muted py-4">
                    <p class="mb-0">No patients for ${title.toLowerCase()}</p>
                </div>
            </div>
        `;
    }
    
    let html = `
        <div class="doctor-card">
            <div class="doctor-header">
                <h5 class="mb-0">${icon} ${title} <span class="badge bg-light text-dark ms-2">${patients.length} patients</span></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:80px">Serial</th>
                                <th>Patient Name</th>
                                <th>Phone</th>
                                <th>Time</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    patients.forEach(p => {
        const statusClass = `status-${p.status || 'scheduled'}`;
        const statusText = (p.status || 'scheduled').toUpperCase().replace('_', ' ');
        
        html += `
            <tr>
                <td><span class="serial-badge">${p.serial_number || 'N/A'}</span></td>
                <td><strong>${p.first_name} ${p.last_name}</strong><br><small class="text-muted">${p.patient_code || ''}</small></td>
                <td>${p.phone}</td>
                <td>${p.start_time ? p.start_time.substring(0,5) : 'N/A'} - ${p.end_time ? p.end_time.substring(0,5) : 'N/A'}</td>
                <td>৳ ${parseFloat(p.total_amount || 0).toFixed(2)}</td>
                <td><span class="${statusClass}">${statusText}</span></td>
                <td class="no-print">
                    <button class="btn btn-sm btn-outline-success" onclick="printSlip(${p.id})">
                        <i class="fas fa-print"></i> Print
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    return html;
}

function showError(message) {
    $('#results_container').html(`
        <div class="alert alert-danger text-center py-4">
            <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
            <p>${message}</p>
            <button onclick="loadDoctorPatients()" class="btn btn-sm btn-danger">Retry</button>
        </div>
    `);
}

function printSlip(appointmentId) {
    window.open(BASE_URL + '/api/print-serial?appointment_id=' + appointmentId, '_blank');
}

$('#filter_btn').click(loadDoctorPatients);
$('#doctor_id').change(loadDoctorPatients);
$('#filter_date').change(loadDoctorPatients);

$(document).ready(function() {
    $('#results_container').html(`
        <div class="alert alert-info text-center py-4">
            <i class="fas fa-info-circle fa-2x mb-2"></i>
            <p>Please select a doctor to view patient list</p>
        </div>
    `);
});
</script>