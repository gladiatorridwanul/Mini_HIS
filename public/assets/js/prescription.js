// public/assets/js/prescription.js
// COMPLETE WORKING VERSION - Fixed Tab1 to Tab2 navigation with Drug History, Disease History & Special Note

// Check if BASE_URL is already defined, if not, define it
if (typeof BASE_URL === 'undefined') {
    var BASE_URL = window.BASE_URL || '/unidia/public';
}

// ================================================================
// TOAST NOTIFICATIONS
// ================================================================
function showToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toastContainer');
    if (!container) {
        console.log('Toast:', message);
        alert(message);
        return;
    }
    
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        setTimeout(function() { toast.remove(); }, 300);
    }, 3000);
}

// ================================================================
// MODAL CONTROLLER - SINGLE SOURCE OF TRUTH
// ================================================================

// Track which modal is currently active - ONLY ONE at a time
var activeModalId = null;

/**
 * Open ONLY the specified modal - closes all others first
 */
function openModal(modalId) {
    // Get the modal element
    var modal = document.getElementById(modalId);
    if (!modal) {
        console.error('Modal not found:', modalId);
        return;
    }
    
    // STEP 1: Close ALL modals - remove active class from ALL
    var allModals = document.querySelectorAll('.modal-overlay');
    allModals.forEach(function(m) {
        m.classList.remove('active');
        m.style.display = 'none';
        m.style.visibility = 'hidden';
        m.style.opacity = '0';
        m.style.pointerEvents = 'none';
        m.style.zIndex = '1';
    });
    
    // STEP 2: Remove body class
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // STEP 3: Open ONLY the target modal
    modal.classList.add('active');
    modal.style.display = 'flex';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
    modal.style.zIndex = '9999';
    
    // STEP 4: Update active modal tracker
    activeModalId = modalId;
    
    // STEP 5: Add body class for scroll lock
    document.body.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = '0';
    
    console.log('✅ Modal opened:', modalId);
}

/**
 * Close a specific modal
 */
function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        modal.style.zIndex = '1';
    }
    
    if (activeModalId === modalId) {
        activeModalId = null;
    }
    
    // Check if any modal is still active
    var allModals = document.querySelectorAll('.modal-overlay');
    var hasVisibleModal = false;
    allModals.forEach(function(m) {
        if (m.classList.contains('active') && m.style.display === 'flex') {
            hasVisibleModal = true;
        }
    });
    
    if (!hasVisibleModal) {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        activeModalId = null;
    }
    
    console.log('✅ Modal closed:', modalId);
}

/**
 * Close ALL modals
 */
function closeAllModals() {
    var allModals = document.querySelectorAll('.modal-overlay');
    allModals.forEach(function(m) {
        m.classList.remove('active');
        m.style.display = 'none';
        m.style.visibility = 'hidden';
        m.style.opacity = '0';
        m.style.pointerEvents = 'none';
        m.style.zIndex = '1';
    });
    
    activeModalId = null;
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    console.log('✅ All modals closed');
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    var overlay = e.target.closest('.modal-overlay');
    if (overlay) {
        if (e.target === overlay) {
            var modalId = overlay.id;
            if (activeModalId === modalId) {
                closeModal(modalId);
            }
        }
    }
});

// ESC key closes the active modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && activeModalId) {
        closeModal(activeModalId);
        e.preventDefault();
    }
});

// ================================================================
// LOAD PREVIOUS PRESCRIPTIONS
// ================================================================

function loadPreviousPrescriptions(tab) {
    // CRITICAL: Stop event propagation
    if (window.event) {
        window.event.stopPropagation();
        window.event.cancelBubble = true;
    }
    
    var patientId = document.querySelector('input[name="patient_id"]')?.value;
    if (!patientId) {
        showToast('No patient selected.', 'warning');
        return;
    }
    
    var modal = document.getElementById('loadPreviousModal');
    var list = document.getElementById('prescriptionList');
    
    if (!modal || !list) {
        showToast('Modal not found.', 'error');
        return;
    }
    
    list.innerHTML = '<div class="text-center py-3">Loading...</div>';
    
    // Open ONLY the load previous modal
    openModal('loadPreviousModal');
    
    $.ajax({
        url: BASE_URL + '/prescriptions/get-previous-prescriptions',
        type: 'GET',
        data: { patient_id: patientId },
        dataType: 'json',
        timeout: 15000,
        success: function(response) {
            if (response.success && response.data.length > 0) {
                var html = '<div class="list-group">';
                response.data.forEach(function(p) {
                    var statusClass = 'secondary';
                    if (p.status === 'issued') statusClass = 'primary';
                    else if (p.status === 'dispensed') statusClass = 'info';
                    else if (p.status === 'completed') statusClass = 'success';
                    
                    html += 
                        '<div class="list-item" onclick="loadPrescriptionData(' + p.id + ', \'' + tab + '\')">' +
                            '<div class="item-info">' +
                                '<span class="item-title">' + p.prescription_number + '</span>' +
                                '<span class="item-sub">' + p.prescription_date + ' | ' + (p.doctor_name || 'N/A') + '</span>' +
                                (p.item_count ? '<span class="item-sub">Items: ' + p.item_count + '</span>' : '') +
                            '</div>' +
                            '<div>' +
                                '<span class="badge bg-' + statusClass + '">' + p.status + '</span>' +
                                '<i class="fas fa-chevron-right ms-2 text-muted"></i>' +
                            '</div>' +
                        '</div>';
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

function loadPrescriptionData(prescriptionId, tabToLoad) {
    closeModal('loadPreviousModal');
    showToast('Loading prescription data...', 'info');
    
    document.getElementById('prescriptionId').value = prescriptionId;
    
    // Store the loaded prescription ID globally
    if (typeof loadedPrescriptionId !== 'undefined') {
        loadedPrescriptionId = prescriptionId;
    }
    if (typeof currentPrescriptionId !== 'undefined') {
        currentPrescriptionId = prescriptionId;
    }
    
    var tabs = ['tab1', 'tab2', 'tab3', 'tab4', 'tab5'];
    var loadedTabs = 0;
    
    tabs.forEach(function(tab) {
        $.ajax({
            url: BASE_URL + '/prescriptions/load-tab-data',
            type: 'GET',
            data: { prescription_id: prescriptionId, tab: tab },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                if (response.success) {
                    populateTabData(tab, response.data);
                }
                loadedTabs++;
                if (loadedTabs === tabs.length) {
                    switchTab(tabToLoad);
                    showToast('Prescription loaded successfully!', 'success');
                }
            },
            error: function() {
                loadedTabs++;
                if (loadedTabs === tabs.length) {
                    switchTab(tabToLoad);
                    showToast('Prescription loaded with some errors.', 'warning');
                }
            }
        });
    });
}

// ================================================================
// POPULATE TAB DATA - COMPLETE WITH DRUG HISTORY, DISEASE HISTORY & SPECIAL NOTE
// ================================================================
function populateTabData(tab, data) {
    try {
        console.log('Populating tab data for:', tab);
        
        switch(tab) {
            case 'tab1':
                // ============================================================
                // DRUG HISTORY - FIXED: Properly populates drug history
                // ============================================================
                if (data.drug_history && Array.isArray(data.drug_history)) {
                    var container = document.getElementById('drugHistoryContainer');
                    if (container) {
                        container.innerHTML = '';
                        data.drug_history.forEach(function(d, i) {
                            var row = document.createElement('div');
                            row.className = 'history-item';
                            row.innerHTML = 
                                '<span class="bullet">•</span>' +
                                '<input type="text" class="form-control-simple" name="drug_history[' + i + '][name]" value="' + escapeHtml(d.drug_name || '') + '" placeholder="Drug name..." style="flex:1;">' +
                                '<input type="text" class="form-control-simple" name="drug_history[' + i + '][duration]" value="' + escapeHtml(d.duration || '') + '" placeholder="Duration" style="min-width:80px;max-width:120px;">' +
                                '<input type="text" class="form-control-simple" name="drug_history[' + i + '][remarks]" value="' + escapeHtml(d.remarks || '') + '" placeholder="Remarks" style="min-width:100px;max-width:150px;">' +
                                '<button type="button" class="btn-remove-row" onclick="removeRow(this, \'drugHistoryContainer\')">' +
                                    '<i class="fas fa-times"></i>' +
                                '</button>';
                            container.appendChild(row);
                        });
                        if (typeof drugHistoryCounter !== 'undefined') {
                            drugHistoryCounter = data.drug_history.length || 1;
                        }
                    }
                }
                
                // ============================================================
                // DISEASE HISTORY - FIXED: Properly populates disease history
                // ============================================================
                if (data.disease_history && Array.isArray(data.disease_history)) {
                    var container = document.getElementById('diseaseHistoryContainer');
                    if (container) {
                        container.innerHTML = '';
                        data.disease_history.forEach(function(d, i) {
                            var row = document.createElement('div');
                            row.className = 'history-item';
                            row.innerHTML = 
                                '<span class="bullet">•</span>' +
                                '<input type="text" class="form-control-simple" name="disease_history[' + i + '][name]" value="' + escapeHtml(d.disease || '') + '" placeholder="Disease..." style="flex:1;">' +
                                '<input type="text" class="form-control-simple" name="disease_history[' + i + '][duration]" value="' + escapeHtml(d.duration || '') + '" placeholder="Duration" style="min-width:80px;max-width:120px;">' +
                                '<input type="text" class="form-control-simple" name="disease_history[' + i + '][remarks]" value="' + escapeHtml(d.remarks || '') + '" placeholder="Remarks" style="min-width:100px;max-width:150px;">' +
                                '<button type="button" class="btn-remove-row" onclick="removeRow(this, \'diseaseHistoryContainer\')">' +
                                    '<i class="fas fa-times"></i>' +
                                '</button>';
                            container.appendChild(row);
                        });
                        if (typeof diseaseHistoryCounter !== 'undefined') {
                            diseaseHistoryCounter = data.disease_history.length || 1;
                        }
                    }
                }
                
                // ============================================================
                // SPECIAL NOTE - FIXED: Properly populates special note
                // ============================================================
                if (data.special_note !== undefined) {
                    var el = document.querySelector('textarea[name="special_note"]');
                    if (el) {
                        el.value = data.special_note || '';
                    }
                }
                
                // ============================================================
                // CHIEF COMPLAINTS
                // ============================================================
                if (data.complaints && Array.isArray(data.complaints)) {
                    var container = document.getElementById('complaintsContainer');
                    if (container) {
                        container.innerHTML = '';
                        data.complaints.forEach(function(c, i) {
                            var row = document.createElement('div');
                            row.className = 'complaint-item';
                            row.innerHTML = 
                                '<span class="bullet">•</span>' +
                                '<input type="text" class="form-control-simple" name="complaints[' + i + '][text]" value="' + escapeHtml(c.complaint || '') + '" placeholder="Enter complaint..." style="flex:1;">' +
                                '<input type="text" class="form-control-simple" name="complaints[' + i + '][duration]" value="' + escapeHtml(c.duration || '') + '" placeholder="Duration" style="min-width:80px;max-width:120px;">' +
                                '<input type="text" class="form-control-simple" name="complaints[' + i + '][remarks]" value="' + escapeHtml(c.remarks || '') + '" placeholder="Remarks" style="min-width:100px;max-width:150px;">' +
                                '<button type="button" class="btn-remove-row" onclick="removeRow(this, \'complaintsContainer\')">' +
                                    '<i class="fas fa-times"></i>' +
                                '</button>';
                            container.appendChild(row);
                        });
                        if (typeof complaintCounter !== 'undefined') {
                            complaintCounter = data.complaints.length || 1;
                        }
                    }
                }
                
                // ============================================================
                // TREATMENT HISTORY
                // ============================================================
                if (data.treatment_history && Array.isArray(data.treatment_history)) {
                    var container = document.getElementById('treatmentHistoryContainer');
                    if (container) {
                        container.innerHTML = '';
                        data.treatment_history.forEach(function(t, i) {
                            var row = document.createElement('div');
                            row.className = 'history-item';
                            row.innerHTML = 
                                '<span class="bullet">•</span>' +
                                '<input type="text" class="form-control-simple" name="treatment_history[' + i + '][name]" value="' + escapeHtml(t.treatment_name || '') + '" placeholder="Enter treatment..." style="flex:1;">' +
                                '<input type="text" class="form-control-simple" name="treatment_history[' + i + '][duration]" value="' + escapeHtml(t.duration || '') + '" placeholder="Duration" style="min-width:80px;max-width:120px;">' +
                                '<input type="text" class="form-control-simple" name="treatment_history[' + i + '][remarks]" value="' + escapeHtml(t.remarks || '') + '" placeholder="Remarks" style="min-width:100px;max-width:150px;">' +
                                '<button type="button" class="btn-remove-row" onclick="removeRow(this, \'treatmentHistoryContainer\')">' +
                                    '<i class="fas fa-times"></i>' +
                                '</button>';
                            container.appendChild(row);
                        });
                        if (typeof treatmentHistoryCounter !== 'undefined') {
                            treatmentHistoryCounter = data.treatment_history.length || 1;
                        }
                    }
                }
                
                // Marital Status
                if (data.marital_status) {
                    var el = document.querySelector('select[name="marital_status"]');
                    if (el) el.value = data.marital_status;
                }
                break;
                
            case 'tab2':
                // Physical Exam
                if (data.physical_exam) {
                    var pe = data.physical_exam;
                    ['anaemia', 'jaundice', 'cyanosis', 'oedema', 'dehydration'].forEach(function(field) {
                        var el = document.querySelector('input[name="physical_exam[' + field + ']"]');
                        if (el) el.checked = pe[field] || false;
                    });
                    ['abdomen', 'cvs', 'respiratory', 'lymphoreticular'].forEach(function(field) {
                        var el = document.querySelector('input[name="physical_exam[' + field + ']"]');
                        if (el && pe[field]) el.value = pe[field];
                    });
                }
                
                // Vital Signs
                if (data.vital_signs) {
                    var vs = data.vital_signs;
                    ['pulse', 'weight', 'respiratory_rate', 'length', 'bp_systolic', 'bp_diastolic', 'temperature', 'oxygen_saturation', 'bmi', 'others'].forEach(function(field) {
                        var el = document.querySelector('input[name="vital_signs[' + field + ']"]');
                        if (el && vs[field] !== undefined && vs[field] !== null) {
                            el.value = vs[field];
                        }
                    });
                    if (typeof calculateBMI === 'function') {
                        calculateBMI();
                    }
                }
                
                // Investigations
                if (data.investigations && Array.isArray(data.investigations)) {
                    var container = document.getElementById('investigationsContainer');
                    if (container) {
                        container.innerHTML = '';
                        data.investigations.forEach(function(inv, i) {
                            var row = document.createElement('div');
                            row.className = 'history-item';
                            row.innerHTML = 
                                '<span class="bullet">•</span>' +
                                '<input type="text" class="form-control-simple" name="investigations[' + i + '][name]" value="' + escapeHtml(inv.investigation_name || '') + '" placeholder="Investigation..." style="flex:1;">' +
                                '<input type="text" class="form-control-simple" name="investigations[' + i + '][remarks]" value="' + escapeHtml(inv.remarks || '') + '" placeholder="Remarks" style="min-width:100px;max-width:150px;">' +
                                '<button type="button" class="btn-remove-row" onclick="removeRow(this, \'investigationsContainer\')">' +
                                    '<i class="fas fa-times"></i>' +
                                '</button>';
                            container.appendChild(row);
                        });
                        if (typeof investigationCounter !== 'undefined') {
                            investigationCounter = data.investigations.length || 1;
                        }
                    }
                }
                
                // Lab Tests
                if (data.lab_tests && Array.isArray(data.lab_tests)) {
                    var container = document.getElementById('labTestsContainer');
                    if (container) {
                        container.innerHTML = '';
                        data.lab_tests.forEach(function(test, i) {
                            var row = document.createElement('div');
                            row.className = 'lab-test-item';
                            row.setAttribute('data-test-index', i);
                            row.innerHTML = 
                                '<div class="row g-2 align-items-end">' +
                                    '<div class="col-md-5">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Test Name <span class="text-danger">*</span></label>' +
                                        '<div class="lab-test-select-wrapper">' +
                                            '<input type="text" class="lab-test-search-input form-control-simple" placeholder="Search lab test..." data-test-index="' + i + '" autocomplete="off" value="' + escapeHtml(test.test_name || '') + '" style="width:100%;">' +
                                            '<input type="hidden" name="lab_tests[' + i + '][test_id]" value="' + (test.test_id || '') + '">' +
                                            '<div class="lab-test-dropdown" id="labTestDropdown_' + i + '"></div>' +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="col-md-3">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Priority</label>' +
                                        '<select class="form-control-simple" name="lab_tests[' + i + '][priority]" style="width:100%;background:transparent;">' +
                                            '<option value="routine" ' + (test.priority === 'routine' ? 'selected' : '') + '>Routine</option>' +
                                            '<option value="urgent" ' + (test.priority === 'urgent' ? 'selected' : '') + '>Urgent</option>' +
                                            '<option value="stat" ' + (test.priority === 'stat' ? 'selected' : '') + '>STAT</option>' +
                                        '</select>' +
                                    '</div>' +
                                    '<div class="col-md-3">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Notes</label>' +
                                        '<input type="text" class="form-control-simple" name="lab_tests[' + i + '][notes]" value="' + escapeHtml(test.notes || '') + '" placeholder="Special instructions..." style="width:100%;">' +
                                    '</div>' +
                                    '<div class="col-md-1">' +
                                        '<button type="button" class="btn-remove-row" onclick="removeLabTestRow(this)" style="margin-top:18px;">' +
                                            '<i class="fas fa-times"></i>' +
                                        '</button>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="row mt-1">' +
                                    '<div class="col-md-12">' +
                                        '<small class="text-muted" id="labTestInfo_' + i + '" style="font-size:11px;">' +
                                            '<span class="text-muted">' + (test.test_name ? 'Test selected: ' + test.test_name : 'Select a test to see details') + '</span>' +
                                        '</small>' +
                                    '</div>' +
                                '</div>';
                            container.appendChild(row);
                            
                            var testInput = row.querySelector('.lab-test-search-input');
                            if (testInput && typeof initLabTestSearch === 'function') {
                                testInput.dataset.testIndex = i;
                                initLabTestSearch(testInput);
                            }
                        });
                        if (typeof labTestCounter !== 'undefined') {
                            labTestCounter = data.lab_tests.length || 1;
                        }
                    }
                }
                
                // Diagnosis
                if (data.diagnosis) {
                    var el = document.querySelector('textarea[name="diagnosis"]');
                    if (el) el.value = data.diagnosis;
                }
                break;
                
            case 'tab3':
                // Medicines
                if (data.medicines && Array.isArray(data.medicines) && data.medicines.length > 0) {
                    var container = document.getElementById('medicinesContainer');
                    if (container) {
                        container.innerHTML = '';
                        if (typeof usedDrugIndices !== 'undefined') {
                            usedDrugIndices = [];
                        }
                        if (typeof medicineCounter !== 'undefined') {
                            medicineCounter = 0;
                        }
                        
                        data.medicines.forEach(function(med) {
                            var drugData = null;
                            var drugIndex = -1;
                            
                            if (med.drug_id) {
                                var found = SAMPLE_DRUGS.find(function(d) { return d.id == med.drug_id; });
                                if (found) {
                                    drugIndex = SAMPLE_DRUGS.indexOf(found);
                                    drugData = { index: drugIndex, drug: found };
                                }
                            }
                            
                            if (!drugData) {
                                var found = SAMPLE_DRUGS.find(function(d) { 
                                    return d.name.toLowerCase() === (med.drug_name || '').toLowerCase();
                                });
                                if (found) {
                                    drugIndex = SAMPLE_DRUGS.indexOf(found);
                                    drugData = { index: drugIndex, drug: found };
                                }
                            }
                            
                            if (!drugData) {
                                drugData = { index: -1, drug: { id: 0, name: med.drug_name || 'Custom Drug', strength: '', dosage_form: med.dosage || 'Tablet', code: '' } };
                            }
                            
                            if (drugData.index >= 0 && usedDrugIndices.indexOf(drugData.index) !== -1) {
                                for (var i = 0; i < SAMPLE_DRUGS.length; i++) {
                                    if (usedDrugIndices.indexOf(i) === -1) {
                                        drugData.index = i;
                                        drugData.drug = SAMPLE_DRUGS[i];
                                        break;
                                    }
                                }
                            }
                            
                            var row = createMedicineItem(drugData, medicineCounter);
                            if (row) {
                                var dosageSelect = row.querySelector('select[name*="[dosage]"]');
                                if (dosageSelect && med.dosage) {
                                    dosageSelect.value = med.dosage;
                                }
                                var foodSelect = row.querySelector('select[name*="[relation_to_food]"]');
                                if (foodSelect && med.relation_to_food) {
                                    foodSelect.value = med.relation_to_food;
                                }
                                
                                if (med.details && Array.isArray(med.details)) {
                                    var wrapper = row.querySelector('.medicine-details-wrapper');
                                    if (wrapper) {
                                        wrapper.innerHTML = '';
                                        med.details.forEach(function(d, di) {
                                            var detailRow = createMedicineDetailRow(
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
                                if (drugData.index >= 0 && usedDrugIndices.indexOf(drugData.index) === -1) {
                                    usedDrugIndices.push(drugData.index);
                                }
                            }
                        });
                        
                        if (medicineCounter === 0) {
                            var firstDrug = getNextAvailableDrug();
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
                // Advice
                if (data.advice) {
                    var el = document.getElementById('adviceText');
                    if (el) el.value = data.advice.advice_text || '';
                    var followUpDays = document.getElementById('follow_up_days');
                    if (followUpDays && data.advice.follow_up_days) {
                        followUpDays.value = data.advice.follow_up_days;
                    }
                    var appointmentDate = document.getElementById('appointment_date');
                    if (appointmentDate && data.advice.appointment_date) {
                        appointmentDate.value = data.advice.appointment_date;
                    }
                }
                break;
                
            case 'tab5':
                // Vaccinations
                if (data.vaccinations && Array.isArray(data.vaccinations)) {
                    var container = document.getElementById('newVaccineContainer');
                    if (container) {
                        container.innerHTML = '';
                        if (data.vaccinations.length > 0) {
                            data.vaccinations.forEach(function(vac, idx) {
                                var row = document.createElement('div');
                                row.className = 'vaccination-item row g-2 align-items-end mb-2';
                                row.setAttribute('data-vac-index', idx);
                                row.innerHTML = 
                                    '<div class="col-md-3">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Vaccine Name</label>' +
                                        '<input type="text" class="form-control-simple" name="vaccinations[' + idx + '][vaccine_name]" value="' + escapeHtml(vac.vaccine_name || '') + '" placeholder="e.g. Hepatitis B">' +
                                    '</div>' +
                                    '<div class="col-md-2">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Dose</label>' +
                                        '<select class="form-control-simple" name="vaccinations[' + idx + '][dose]" style="width:100%;background:transparent;">' +
                                            '<option value="">Select Dose</option>' +
                                            '<option value="Dose 1" ' + (vac.dose === 'Dose 1' ? 'selected' : '') + '>Dose 1</option>' +
                                            '<option value="Dose 2" ' + (vac.dose === 'Dose 2' ? 'selected' : '') + '>Dose 2</option>' +
                                            '<option value="Dose 3" ' + (vac.dose === 'Dose 3' ? 'selected' : '') + '>Dose 3</option>' +
                                            '<option value="Dose 4" ' + (vac.dose === 'Dose 4' ? 'selected' : '') + '>Dose 4</option>' +
                                            '<option value="Dose 5" ' + (vac.dose === 'Dose 5' ? 'selected' : '') + '>Dose 5</option>' +
                                            '<option value="Dose 6" ' + (vac.dose === 'Dose 6' ? 'selected' : '') + '>Dose 6</option>' +
                                            '<option value="Dose 7" ' + (vac.dose === 'Dose 7' ? 'selected' : '') + '>Dose 7</option>' +
                                            '<option value="Dose 8" ' + (vac.dose === 'Dose 8' ? 'selected' : '') + '>Dose 8</option>' +
                                            '<option value="Booster Dose" ' + (vac.dose === 'Booster Dose' ? 'selected' : '') + '>Booster Dose</option>' +
                                            '<option value="Advance Dose" ' + (vac.dose === 'Advance Dose' ? 'selected' : '') + '>Advance Dose</option>' +
                                        '</select>' +
                                    '</div>' +
                                    '<div class="col-md-2">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Date Given</label>' +
                                        '<input type="date" class="form-control-simple" name="vaccinations[' + idx + '][date_given]" value="' + (vac.date_given || '') + '">' +
                                    '</div>' +
                                    '<div class="col-md-2">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Next Due</label>' +
                                        '<input type="date" class="form-control-simple" name="vaccinations[' + idx + '][next_due]" value="' + (vac.next_due || '') + '">' +
                                    '</div>' +
                                    '<div class="col-md-2">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Batch / Lot</label>' +
                                        '<input type="text" class="form-control-simple" name="vaccinations[' + idx + '][batch_number]" value="' + escapeHtml(vac.batch_number || '') + '" placeholder="Batch">' +
                                    '</div>' +
                                    '<div class="col-md-2">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Site</label>' +
                                        '<input type="text" class="form-control-simple" name="vaccinations[' + idx + '][site]" value="' + escapeHtml(vac.site || '') + '" placeholder="Left deltoid">' +
                                    '</div>' +
                                    '<div class="col-md-2">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Administered By</label>' +
                                        '<input type="text" class="form-control-simple" name="vaccinations[' + idx + '][administered_by]" value="' + escapeHtml(vac.administered_by || '') + '" placeholder="Dr. Name">' +
                                    '</div>' +
                                    '<div class="col-md-2">' +
                                        '<label class="form-label" style="font-size:10px;color:#64748b;">Notes</label>' +
                                        '<input type="text" class="form-control-simple" name="vaccinations[' + idx + '][notes]" value="' + escapeHtml(vac.notes || '') + '" placeholder="Notes">' +
                                    '</div>' +
                                    '<div class="col-md-1">' +
                                        '<button type="button" class="btn-remove-row" onclick="removeVaccinationRow(this)" style="margin-top:18px;">' +
                                            '<i class="fas fa-times"></i>' +
                                        '</button>' +
                                    '</div>';
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
// HELPER: Escape HTML to prevent XSS
// ================================================================
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
    
    var btn = document.querySelector('.tab-footer .btn-next');
    if (!btn) {
        showToast('Error: Save button not found.', 'error');
        return;
    }
    
    var originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
    btn.disabled = true;
    isSaving = true;
    
    var form = document.getElementById('prescriptionForm');
    if (!form) {
        showToast('Error: Form not found.', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
        isSaving = false;
        return;
    }
    
    // Collect ALL form data
    var formData = new FormData(form);
    var nextTabIndex = parseInt(currentTab.replace('tab', '')) + 1;
    var nextTab = 'tab' + nextTabIndex;
    
    // Add required parameters
    formData.append('next_tab', nextTab);
    formData.append('final_save', '0');
    formData.append('complete', '0');
    formData.append('action_type', 'save');
    formData.append('ajax', '1');
    
    // Get current prescription ID
    var presId = document.getElementById('prescriptionId').value;
    if (!presId && loadedPrescriptionId) {
        presId = loadedPrescriptionId;
    }
    
    // Always use the save endpoint
    var url = BASE_URL + '/prescriptions/save';
    
    // If we have a prescription ID, add it to form data
    if (presId) {
        formData.append('prescription_id', presId);
    }
    
    console.log('=== REQUEST DETAILS ===');
    console.log('URL:', url);
    console.log('Prescription ID:', presId);
    console.log('Next Tab:', nextTab);
    
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
                    
                    var targetTab = response.next_tab || nextTab;
                    console.log('Switching to tab:', targetTab);
                    
                    if (document.getElementById(targetTab)) {
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
            
            var errorMessage = 'Error saving data: ' + error;
            try {
                var jsonResponse = JSON.parse(xhr.responseText);
                if (jsonResponse && jsonResponse.message) {
                    errorMessage = jsonResponse.message;
                }
            } catch(e) {
                if (xhr.responseText && xhr.responseText.includes('<!DOCTYPE')) {
                    errorMessage = 'Server error. Please check your connection and try again.';
                }
            }
            showToast(errorMessage, 'error');
            
            btn.innerHTML = originalText;
            btn.disabled = false;
            isSaving = false;
        }
    });
}

// ================================================================
// TEMPLATE MANAGEMENT
// ================================================================

function saveTemplate(type) {
    // CRITICAL: Stop event propagation
    if (window.event) {
        window.event.stopPropagation();
        window.event.cancelBubble = true;
    }
    
    // Close ALL modals before showing prompt
    closeAllModals();
    
    var data = '';
    var name = prompt('Enter template name:');
    if (!name) return;
    
    switch(type) {
        case 'investigation':
            var invItems = document.querySelectorAll('#investigationsContainer .history-item');
            var invList = [];
            invItems.forEach(function(item) {
                var nameInput = item.querySelector('input[name*="[name]"]');
                if (nameInput && nameInput.value.trim()) {
                    invList.push(nameInput.value.trim());
                }
            });
            data = JSON.stringify(invList);
            break;
            
        case 'diagnosis':
            data = document.querySelector('textarea[name="diagnosis"]')?.value || '';
            break;
            
        case 'medicine':
            var medItems = document.querySelectorAll('#medicinesContainer .medicine-item');
            var medList = [];
            medItems.forEach(function(item) {
                var drugInput = item.querySelector('.drug-search-input');
                var hiddenInput = item.querySelector('input[type="hidden"]');
                var dosageSelect = item.querySelector('select[name*="[dosage]"]');
                var foodSelect = item.querySelector('select[name*="[relation_to_food]"]');
                
                var detailRows = item.querySelectorAll('.medicine-detail-row');
                var details = [];
                detailRows.forEach(function(row) {
                    var freq = row.querySelector('select[name*="[frequency]"]');
                    var dur = row.querySelector('select[name*="[duration]"]');
                    var inst = row.querySelector('select[name*="[instruction]"]');
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
            data = document.getElementById('adviceText')?.value || '';
            break;
    }
    
    if (!data || data.length < 2) {
        showToast('Please enter some data first.', 'warning');
        return;
    }
    
    try {
        var templates = JSON.parse(localStorage.getItem('prescription_templates_' + type) || '[]');
        templates.push({ name: name, data: data, date: new Date().toISOString() });
        localStorage.setItem('prescription_templates_' + type, JSON.stringify(templates));
        showToast('Template "' + name + '" saved successfully!', 'success');
    } catch(e) {
        showToast('Error saving template.', 'error');
    }
}

function showTemplateModal(type) {
    // CRITICAL: Stop event propagation
    if (window.event) {
        window.event.stopPropagation();
        window.event.cancelBubble = true;
    }
    
    var templateModal = document.getElementById('templateModal');
    var list = document.getElementById('templateList');
    var title = document.getElementById('templateModalTitle');
    
    if (!templateModal || !list || !title) {
        showToast('Template modal not found.', 'error');
        return;
    }
    
    var titles = {
        'investigation': 'Investigation Templates',
        'diagnosis': 'Diagnosis Templates',
        'medicine': 'Medicine Templates',
        'advice': 'Advice Templates'
    };
    title.textContent = titles[type] || 'Templates';
    
    list.innerHTML = '<div class="text-center py-3">Loading...</div>';
    
    // Open ONLY the template modal
    openModal('templateModal');
    
    try {
        var templates = JSON.parse(localStorage.getItem('prescription_templates_' + type) || '[]');
        if (templates.length > 0) {
            var html = '';
            templates.forEach(function(t, index) {
                html += 
                    '<div class="list-item">' +
                        '<div class="item-info" style="flex:1;cursor:pointer;" onclick="applyTemplate(\'' + type + '\', ' + index + ')">' +
                            '<span class="item-title">' + t.name + ' <span class="badge bg-secondary">Local</span></span>' +
                            '<span class="item-sub">' + new Date(t.date).toLocaleDateString() + '</span>' +
                        '</div>' +
                        '<div>' +
                            '<button class="btn-load-selected" onclick="applyTemplate(\'' + type + '\', ' + index + ')">' +
                                '<i class="fas fa-arrow-right"></i> Load' +
                            '</button>' +
                            '<button class="btn btn-sm btn-danger ms-1" onclick="event.stopPropagation(); deleteTemplate(\'' + type + '\', ' + index + ')">' +
                                '<i class="fas fa-trash"></i>' +
                            '</button>' +
                        '</div>' +
                    '</div>';
            });
            list.innerHTML = html;
        } else {
            list.innerHTML = '<div class="text-center py-3 text-muted">No templates found. Create one by saving current data as template.</div>';
        }
    } catch(e) {
        list.innerHTML = '<div class="text-center py-3 text-danger">Error loading templates.</div>';
        console.error(e);
    }
}

function applyTemplate(type, index) {
    try {
        var templates = JSON.parse(localStorage.getItem('prescription_templates_' + type) || '[]');
        if (templates[index]) {
            applyTemplateData(type, templates[index].data);
            closeModal('templateModal');
            showToast('Template applied successfully!', 'success');
        } else {
            showToast('Template not found.', 'error');
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
                var invContainer = document.getElementById('investigationsContainer');
                var invData = JSON.parse(data);
                invContainer.innerHTML = '';
                invData.forEach(function(item, i) {
                    var row = document.createElement('div');
                    row.className = 'history-item';
                    row.innerHTML = 
                        '<span class="bullet">•</span>' +
                        '<input type="text" class="form-control-simple" name="investigations[' + i + '][name]" value="' + escapeHtml(item) + '" placeholder="Investigation..." style="flex:1;">' +
                        '<input type="text" class="form-control-simple" name="investigations[' + i + '][remarks]" placeholder="Remarks" style="min-width:100px;max-width:150px;">' +
                        '<button type="button" class="btn-remove-row" onclick="removeRow(this, \'investigationsContainer\')">' +
                            '<i class="fas fa-times"></i>' +
                        '</button>';
                    invContainer.appendChild(row);
                });
                if (typeof investigationCounter !== 'undefined') {
                    investigationCounter = invData.length || 1;
                }
                break;
                
            case 'diagnosis':
                var diagEl = document.querySelector('textarea[name="diagnosis"]');
                if (diagEl) diagEl.value = data;
                break;
                
            case 'medicine':
                var medContainer = document.getElementById('medicinesContainer');
                var medData = JSON.parse(data);
                medContainer.innerHTML = '';
                if (typeof usedDrugIndices !== 'undefined') {
                    usedDrugIndices = [];
                }
                if (typeof medicineCounter !== 'undefined') {
                    medicineCounter = 0;
                }
                
                medData.forEach(function(med) {
                    var drugData = null;
                    var drugIndex = -1;
                    
                    if (med.drug_id) {
                        var found = SAMPLE_DRUGS.find(function(d) { return d.id == med.drug_id; });
                        if (found) {
                            drugIndex = SAMPLE_DRUGS.indexOf(found);
                            drugData = { index: drugIndex, drug: found };
                        }
                    }
                    
                    if (!drugData) {
                        var found = SAMPLE_DRUGS.find(function(d) { 
                            return d.name.toLowerCase() === (med.drug_name || '').toLowerCase();
                        });
                        if (found) {
                            drugIndex = SAMPLE_DRUGS.indexOf(found);
                            drugData = { index: drugIndex, drug: found };
                        }
                    }
                    
                    if (!drugData) {
                        drugData = { index: -1, drug: { id: 0, name: med.drug_name || 'Custom Drug', strength: '', dosage_form: med.dosage || 'Tablet', code: '' } };
                    }
                    
                    var row = createMedicineItem(drugData, medicineCounter);
                    if (row) {
                        var dosageSelect = row.querySelector('select[name*="[dosage]"]');
                        if (dosageSelect && med.dosage) {
                            dosageSelect.value = med.dosage;
                        }
                        var foodSelect = row.querySelector('select[name*="[relation_to_food]"]');
                        if (foodSelect && med.relation_to_food) {
                            foodSelect.value = med.relation_to_food;
                        }
                        
                        if (med.details && Array.isArray(med.details)) {
                            var wrapper = row.querySelector('.medicine-details-wrapper');
                            if (wrapper) {
                                wrapper.innerHTML = '';
                                med.details.forEach(function(d, di) {
                                    var detailRow = createMedicineDetailRow(
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
                    var firstDrug = getNextAvailableDrug();
                    if (firstDrug) {
                        createMedicineItem(firstDrug, medicineCounter);
                        medicineCounter++;
                    }
                }
                updateMedicineCount();
                break;
                
            case 'advice':
                var adviceEl = document.getElementById('adviceText');
                if (adviceEl) adviceEl.value = data;
                break;
        }
    } catch(e) {
        showToast('Error applying template data.', 'error');
        console.error(e);
    }
}

function deleteTemplate(type, index) {
    if (!confirm('Delete this template?')) return;
    
    try {
        var templates = JSON.parse(localStorage.getItem('prescription_templates_' + type) || '[]');
        templates.splice(index, 1);
        localStorage.setItem('prescription_templates_' + type, JSON.stringify(templates));
        showToast('Template deleted.', 'info');
        showTemplateModal(type);
    } catch(e) {
        showToast('Error deleting template.', 'error');
    }
}

// ================================================================
// REMOVE ROW FUNCTIONS
// ================================================================
function removeRow(button, containerId) {
    var container = document.getElementById(containerId);
    if (container && container.children.length > 1) {
        var row = button.closest('.complaint-item') || button.closest('.history-item');
        if (row) row.remove();
    } else {
        showToast('At least one row is required.', 'warning');
    }
}

function removeLabTestRow(button) {
    var container = document.getElementById('labTestsContainer');
    if (container && container.children.length > 1) {
        button.closest('.lab-test-item').remove();
    } else {
        showToast('At least one lab test row is required.', 'warning');
    }
}

function removeVaccinationRow(button) {
    var container = document.getElementById('newVaccineContainer');
    if (container && container.children.length > 1) {
        button.closest('.vaccination-item').remove();
    } else {
        showToast('At least one vaccination row is required.', 'warning');
    }
}

// ================================================================
// SWITCH TAB - FIXED
// ================================================================
function switchTab(tabId, skipSave) {
    if (skipSave === undefined) skipSave = false;
    
    console.log('Switching to tab:', tabId);
    currentTab = tabId;
    
    document.querySelectorAll('.tab-content').forEach(function(el) {
        el.classList.remove('active');
    });
    document.querySelectorAll('.tab-item').forEach(function(el) {
        el.classList.remove('active');
    });
    
    var targetContent = document.getElementById(tabId);
    var targetTab = document.querySelector('.tab-item[data-tab="' + tabId + '"]');
    
    if (targetContent) {
        targetContent.classList.add('active');
    }
    if (targetTab) {
        targetTab.classList.add('active');
    }
    
    // Only save if not skipped and prescription ID exists
    if (!skipSave) {
        var presId = document.getElementById('prescriptionId').value;
        if (presId) {
            saveCurrentTab(tabId);
        }
    }
}

function saveCurrentTab(tabId) {
    var patientId = document.querySelector('input[name="patient_id"]')?.value;
    var presId = document.getElementById('prescriptionId').value;
    
    if (!patientId) return;
    if (!presId) {
        console.log('No prescription ID yet, skipping tab save');
        return;
    }
    
    var formData = new FormData(document.getElementById('prescriptionForm'));
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
                var json = JSON.parse(response);
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
// EXPOSE FUNCTIONS TO GLOBAL SCOPE
// ================================================================
window.loadPreviousPrescriptions = loadPreviousPrescriptions;
window.loadPrescriptionData = loadPrescriptionData;
window.populateTabData = populateTabData;
window.saveTemplate = saveTemplate;
window.showTemplateModal = showTemplateModal;
window.applyTemplate = applyTemplate;
window.applyTemplateData = applyTemplateData;
window.deleteTemplate = deleteTemplate;
window.closeModal = closeModal;
window.closeAllModals = closeAllModals;
window.openModal = openModal;
window.showToast = showToast;
window.removeRow = removeRow;
window.removeLabTestRow = removeLabTestRow;
window.removeVaccinationRow = removeVaccinationRow;
window.escapeHtml = escapeHtml;
window.switchTab = switchTab;
window.saveCurrentTab = saveCurrentTab;
window.saveAndGoNext = saveAndGoNext;

console.log('✅ Prescription.js loaded successfully.');
console.log('✅ BASE_URL:', BASE_URL);