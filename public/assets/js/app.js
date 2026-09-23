// UniDia Application - Main JavaScript File

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-calculate billing
    $('#billForm').on('input', '.quantity, .unit-price, .discount', function() {
        calculateBillTotals();
    });
    
    // Search patient for billing
    $('#patientSearch').on('keyup', function() {
        const searchTerm = $(this).val();
        if(searchTerm.length > 2) {
            $.get(BASE_URL + '/api/search-patients', {term: searchTerm}, function(data) {
                let html = '';
                data.forEach(function(patient) {
                    html += `<div class="patient-item p-2 border-bottom" data-id="${patient.id}">
                        <strong>${patient.first_name} ${patient.last_name}</strong>
                        <small class="text-muted">${patient.patient_code}</small>
                    </div>`;
                });
                $('#patientResults').html(html).show();
            });
        } else {
            $('#patientResults').hide();
        }
    });
    
    // Select patient
    $(document).on('click', '.patient-item', function() {
        const patientId = $(this).data('id');
        const patientName = $(this).find('strong').text();
        $('#patientSearch').val(patientName);
        $('#patientId').val(patientId);
        $('#patientResults').hide();
    });
    
    // Drug interaction checker
    $('#prescriptionForm').on('change', '.drug-select', function() {
        const selectedDrugs = [];
        $('.drug-select').each(function() {
            if($(this).val()) {
                selectedDrugs.push($(this).val());
            }
        });
        
        if(selectedDrugs.length > 1) {
            $.post(BASE_URL + '/doctor/check-interactions', 
                {drug_ids: selectedDrugs}, 
                function(response) {
                    if(response.length > 0) {
                        showInteractionWarnings(response);
                    }
                }
            );
        }
    });
    
    // Barcode scanner integration
    $('#barcodeInput').on('keypress', function(e) {
        if(e.which === 13) {
            const barcode = $(this).val();
            $.post(BASE_URL + '/inventory/barcode-scan', 
                {barcode: barcode}, 
                function(response) {
                    if(response.success) {
                        addItemToCart(response.item);
                    } else {
                        Swal.fire('Error', 'Item not found', 'error');
                    }
                }
            );
            $(this).val('');
        }
    });
    
    // Print invoice
    $('#printInvoice').click(function() {
        const billId = $(this).data('bill-id');
        window.open(BASE_URL + '/account/print/' + billId, '_blank');
    });
    
    // Refresh queue display
    function refreshQueue() {
        $.get(BASE_URL + '/api/queue-status', function(data) {
            $('#queueDisplay').html(data);
        });
    }
    
    if($('#queueDisplay').length) {
        setInterval(refreshQueue, 30000); // Refresh every 30 seconds
    }
});

// Calculate bill totals
function calculateBillTotals() {
    let subtotal = 0;
    let totalDiscount = 0;
    
    $('.bill-item').each(function() {
        const quantity = parseFloat($(this).find('.quantity').val()) || 0;
        const unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;
        const discount = parseFloat($(this).find('.discount').val()) || 0;
        
        const itemTotal = quantity * unitPrice;
        const itemDiscount = (itemTotal * discount) / 100;
        
        subtotal += itemTotal;
        totalDiscount += itemDiscount;
        
        $(this).find('.item-total').text((itemTotal - itemDiscount).toFixed(2));
    });
    
    const tax = (subtotal - totalDiscount) * 0.05; // 5% tax
    const grandTotal = subtotal - totalDiscount + tax;
    
    $('#subtotal').text(subtotal.toFixed(2));
    $('#discount').text(totalDiscount.toFixed(2));
    $('#tax').text(tax.toFixed(2));
    $('#grandTotal').text(grandTotal.toFixed(2));
}

// Show drug interaction warnings
function showInteractionWarnings(interactions) {
    let html = '<div class="alert alert-warning"><h6><i class="fas fa-exclamation-triangle"></i> Drug Interactions Detected:</h6><ul>';
    
    interactions.forEach(function(interaction) {
        html += `<li><strong>${interaction.severity.toUpperCase()}:</strong> ${interaction.description}</li>`;
    });
    
    html += '</ul></div>';
    
    $('#interactionWarnings').html(html);
    Swal.fire({
        title: 'Drug Interaction Warning!',
        html: html,
        icon: 'warning',
        confirmButtonText: 'I Understand'
    });
}

// Add item to cart (Pharmacy POS)
function addItemToCart(item) {
    $.post(BASE_URL + '/pharmacy/add-to-cart', 
        {item_id: item.id, quantity: 1}, 
        function(response) {
            if(response.success) {
                updateCartDisplay(response.cart);
            }
        }
    );
}

// Update cart display
function updateCartDisplay(cart) {
    let html = '';
    let total = 0;
    
    $.each(cart, function(id, item) {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        html += `<tr>
            <td>${item.name}</td>
            <td>${item.quantity}</td>
            <td>$${item.price}</td>
            <td>$${itemTotal.toFixed(2)}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeFromCart(${id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    });
    
    $('#cartItems').html(html);
    $('#cartTotal').text(total.toFixed(2));
}

// Remove from cart
function removeFromCart(itemId) {
    $.post(BASE_URL + '/pharmacy/remove-from-cart', 
        {item_id: itemId}, 
        function(response) {
            if(response.success) {
                updateCartDisplay(response.cart);
            }
        }
    );
}

// Print barcode label
function printBarcode(barcode) {
    const canvas = document.createElement('canvas');
    JsBarcode(canvas, barcode, {
        format: "CODE128",
        lineColor: "#000",
        width: 2,
        height: 100,
        displayValue: true
    });
    
    const win = window.open('', '_blank');
    win.document.write('<img src="' + canvas.toDataURL() + '"/>');
    win.print();
}