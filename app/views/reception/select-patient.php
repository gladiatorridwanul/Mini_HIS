<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
    
    .select-patient-container {
        max-width: 700px;
        margin: 20px auto;
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    
    .select-patient-container h4 {
        color: #1f2937;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .select-patient-container .subtitle {
        color: #94a3b8;
        font-size: 13px;
        margin-bottom: 20px;
    }
    
    .search-box {
        position: relative;
        margin-bottom: 20px;
    }
    
    .search-box input {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.3s;
        background: #f8fafc;
    }
    
    .search-box input:focus {
        border-color: #10b981;
        outline: none;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        background: white;
    }
    
    .search-box .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    
    .patient-list {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fafafa;
    }
    
    .patient-list::-webkit-scrollbar {
        width: 6px;
    }
    .patient-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .patient-list::-webkit-scrollbar-thumb {
        background: #c1c7cd;
        border-radius: 3px;
    }
    
    .patient-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 18px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: all 0.2s;
        background: white;
    }
    
    .patient-item:hover {
        background: #f0fdf4;
        transform: translateX(3px);
    }
    
    .patient-item:last-child {
        border-bottom: none;
    }
    
    .patient-item .info {
        display: flex;
        flex-direction: column;
    }
    
    .patient-item .info .name {
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
    .patient-item .info .details {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 2px;
    }
    
    .patient-item .info .details span {
        margin-right: 12px;
    }
    
    .patient-item .select-btn {
        padding: 6px 16px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    
    .patient-item .select-btn:hover {
        background: #059669;
        transform: scale(1.05);
    }
    
    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 10px;
    }
    
    .empty-state h6 {
        color: #475569;
        margin-bottom: 5px;
    }
    
    .back-link {
        display: inline-block;
        margin-top: 15px;
        color: #64748b;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s;
    }
    
    .back-link:hover {
        color: #10b981;
        text-decoration: underline;
    }
    
    .loading-spinner {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 30px;
        gap: 10px;
        color: #64748b;
    }
    
    .loading-spinner .spinner {
        width: 30px;
        height: 30px;
        border: 3px solid #e5e7eb;
        border-top-color: #10b981;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .result-count {
        font-size: 12px;
        color: #94a3b8;
        margin-bottom: 10px;
        padding: 0 5px;
    }
    
    @media (max-width: 600px) {
        .select-patient-container {
            padding: 20px;
            margin: 10px;
        }
        .patient-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .patient-item .select-btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="select-patient-container">
    <h4><i class="fas fa-user-plus text-success me-2"></i>Select Patient</h4>
    <p class="subtitle">Search and select a patient to book a new appointment</p>
    
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="patientSearch" placeholder="Search by name, phone, or patient ID..." autofocus>
    </div>
    
    <div id="searchResults">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <span>Type to search for patients...</span>
        </div>
    </div>
    
    <a href="<?php echo BASE_URL; ?>/reception/appointments" class="back-link">
        <i class="fas fa-arrow-left me-1"></i> Back to Appointments
    </a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let searchTimeout;

$(document).ready(function() {
    $('#patientSearch').focus();
    
    $('#patientSearch').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();
        
        if(query.length < 2) {
            $('#searchResults').html(`
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h6>Start typing to search</h6>
                    <p class="small">Enter at least 2 characters to search for patients</p>
                </div>
            `);
            return;
        }
        
        searchTimeout = setTimeout(function() {
            searchPatients(query);
        }, 300);
    });
    
    // Enter key to search
    $('#patientSearch').on('keypress', function(e) {
        if(e.which === 13) {
            e.preventDefault();
            const query = $(this).val().trim();
            if(query.length >= 2) {
                clearTimeout(searchTimeout);
                searchPatients(query);
            }
        }
    });
});

function searchPatients(query) {
    $('#searchResults').html(`
        <div class="loading-spinner">
            <div class="spinner"></div>
            <span>Searching patients...</span>
        </div>
    `);
    
    $.ajax({
        url: BASE_URL + '/api/search-patients',
        method: 'GET',
        data: { search: query },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if(response.length === 0) {
                $('#searchResults').html(`
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h6>No patients found</h6>
                        <p class="small">Try a different search term</p>
                        <a href="${BASE_URL}/patient/register" class="btn btn-success btn-sm mt-2">
                            <i class="fas fa-user-plus me-1"></i> Register New Patient
                        </a>
                    </div>
                `);
                return;
            }
            
            let html = `<div class="result-count">Found <strong>${response.length}</strong> patient(s)</div>`;
            html += `<div class="patient-list">`;
            
            response.forEach(function(patient) {
                const fullName = patient.first_name + ' ' + patient.last_name;
                html += `
                    <div class="patient-item" onclick="selectPatient(${patient.id})">
                        <div class="info">
                            <div class="name">${escapeHtml(fullName)}</div>
                            <div class="details">
                                <span><i class="fas fa-id-card me-1"></i> ${escapeHtml(patient.patient_code)}</span>
                                <span><i class="fas fa-phone me-1"></i> ${escapeHtml(patient.phone)}</span>
                                <span><i class="fas fa-envelope me-1"></i> ${escapeHtml(patient.email || 'N/A')}</span>
                            </div>
                        </div>
                        <button class="select-btn" onclick="event.stopPropagation(); selectPatient(${patient.id})">
                            <i class="fas fa-arrow-right me-1"></i> Select
                        </button>
                    </div>
                `;
            });
            
            html += `</div>`;
            $('#searchResults').html(html);
        },
        error: function(xhr, status, error) {
            console.error('Search error:', status, error);
            $('#searchResults').html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                    <h6>Error searching patients</h6>
                    <p class="small">Please try again</p>
                </div>
            `);
        }
    });
}

function selectPatient(patientId) {
    // Redirect to booking page with patient ID
    window.location.href = BASE_URL + '/appointments/book?patient_id=' + patientId;
}

function escapeHtml(str) {
    if(!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

// Allow keyboard navigation
$(document).keydown(function(e) {
    if(e.key === 'Escape') {
        window.location.href = BASE_URL + '/reception/appointments';
    }
});
</script>