// Enhanced Facility Mapping with Sub-Units Integration
let facilities = [];
let currentMapIndex = 0;
let selectedSubUnitType = 'table';
let subUnitsToCreate = [];

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Facility Mapping System Initialized');
    loadFacilities();
    updateStats();
    setupMapInteraction();
});

// Load facilities from database
function loadFacilities() {
    fetch('fetch_facilities.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                facilities = data.facilities || [];
                renderFacilities();
                console.log(`✅ Loaded ${facilities.length} facilities`);
            } else {
                console.error('❌ Failed to load facilities:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Error loading facilities:', error);
        });
}

// Render facilities on the map
function renderFacilities() {
    const svg = document.getElementById('facility-map');
    if (!svg) return;

    // Clear existing pins
    const existingPins = svg.querySelectorAll('.facility-pin');
    existingPins.forEach(pin => pin.remove());

    facilities.forEach(facility => {
        createFacilityPin(facility);
    });
}

// Create a facility pin on the map
function createFacilityPin(facility) {
    const svg = document.getElementById('facility-map');
    if (!svg) return;

    const pin = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    pin.setAttribute('class', 'facility-pin');
    pin.setAttribute('cx', facility.facility_pin_x);
    pin.setAttribute('cy', facility.facility_pin_y);
    pin.setAttribute('r', '8');
    pin.setAttribute('fill', getStatusColor(facility.facility_status));
    pin.setAttribute('stroke', '#fff');
    pin.setAttribute('stroke-width', '2');
    pin.setAttribute('data-facility-id', facility.facility_id);
    pin.setAttribute('title', `${facility.facility_name} - ${facility.facility_status}`);

    // Add click event
    pin.addEventListener('click', () => showFacilityDetails(facility));

    svg.appendChild(pin);
}

// Get color based on facility status
function getStatusColor(status) {
    switch(status) {
        case 'Available': return '#28a745';
        case 'Unavailable': return '#dc3545';
        case 'Maintenance': return '#ffc107';
        case 'Reserved': return '#17a2b8';
        default: return '#6c757d';
    }
}

// Show facility details modal
function showFacilityDetails(facility) {
    const modalBody = document.getElementById('facilityModalBody');
    if (!modalBody) return;

    // Fetch sub-units for this facility
    fetch(`fetch_sub_units.php?facility_id=${facility.facility_id}`)
        .then(response => response.json())
        .then(data => {
            const subUnits = data.success ? data.sub_units : [];
            
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bi bi-building"></i> Facility Information</h6>
                        <p><strong>Name:</strong> ${facility.facility_name}</p>
                        <p><strong>Type:</strong> ${facility.facility_type || 'N/A'}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${getStatusBadgeColor(facility.facility_status)}">${facility.facility_status}</span></p>
                        <p><strong>Price:</strong> ₱${parseFloat(facility.facility_price).toFixed(2)}</p>
                        <p><strong>Details:</strong> ${facility.facility_details}</p>
                        <p><strong>Added:</strong> ${new Date(facility.facility_added).toLocaleDateString()}</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-image"></i> Facility Image</h6>
                        <div class="image-container">
                            <img src="../${facility.facility_image}" alt="${facility.facility_name}" class="img-fluid">
                        </div>
                    </div>
                </div>
                ${subUnits.length > 0 ? `
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6><i class="bi bi-grid-3x3-gap"></i> Sub-Units (${subUnits.length})</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Capacity</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${subUnits.map(unit => `
                                            <tr>
                                                <td>${unit.sub_unit_name}</td>
                                                <td><span class="badge bg-info">${unit.sub_unit_type}</span></td>
                                                <td>${unit.sub_unit_capacity} people</td>
                                                <td>₱${parseFloat(unit.sub_unit_price).toFixed(2)}</td>
                                                <td><span class="badge bg-${getStatusBadgeColor(unit.sub_unit_status)}">${unit.sub_unit_status}</span></td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                ` : `
                    <hr>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> This facility has no sub-units. Customers can only rent the entire facility.
                    </div>
                `}
            `;

            // Store current facility data
            currentFacilityData = facility;
            currentFacilityPin = facility;

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('facilityModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching sub-units:', error);
            modalBody.innerHTML = `<div class="alert alert-danger">Error loading facility details.</div>`;
        });
}

// Get badge color for status
function getStatusBadgeColor(status) {
    switch(status) {
        case 'Available': return 'success';
        case 'Unavailable': return 'danger';
        case 'Maintenance': return 'warning';
        case 'Reserved': return 'info';
        default: return 'secondary';
    }
}

// Setup map interaction
function setupMapInteraction() {
    const svg = document.getElementById('facility-map');
    if (!svg) return;

    svg.addEventListener('click', function(e) {
        if (e.target.tagName === 'svg') {
            const rect = svg.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Convert to SVG coordinates
            const pt = svg.createSVGPoint();
            pt.x = x;
            pt.y = y;
            const svgPt = pt.matrixTransform(svg.getScreenCTM().inverse());
            
            showAddFacilityModal(svgPt.x, svgPt.y);
        }
    });
}

// Show add facility modal with coordinates
function showAddFacilityModal(x, y) {
    // Set coordinates
    document.getElementById('pin_x').value = x;
    document.getElementById('pin_y').value = y;
    
    // Reset form
    const form = document.getElementById('addFacilityForm');
    if (form) form.reset();
    
    // Reset sub-units
    subUnitsToCreate = [];
    updateSubUnitPreview();
    document.getElementById('addSubUnits').checked = false;
    toggleSubUnitForm();
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('addFacilityModal'));
    modal.show();
}

// Toggle sub-unit form visibility
function toggleSubUnitForm() {
    const checkbox = document.getElementById('addSubUnits');
    const form = document.getElementById('subUnitForm');
    const section = document.getElementById('subUnitSection');
    
    if (checkbox.checked) {
        form.classList.add('show');
        section.classList.add('show');
        autoSelectFacilityType();
    } else {
        form.classList.remove('show');
        section.classList.remove('show');
        subUnitsToCreate = [];
        updateSubUnitPreview();
    }
}

// Auto-select facility type based on name
function autoSelectFacilityType() {
    const facilityName = document.getElementById('facilityName').value.toLowerCase();
    
    if (facilityName.includes('restaurant') || facilityName.includes('dining')) {
        selectSubUnitType('table');
    } else if (facilityName.includes('cottage')) {
        selectSubUnitType('cottage');
    } else if (facilityName.includes('room')) {
        selectSubUnitType('room');
    }
}

// Select sub-unit type
function selectSubUnitType(type) {
    selectedSubUnitType = type;
    
    // Update button states
    document.querySelectorAll('[data-type]').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update placeholders
    updatePlaceholders();
}

// Update form placeholders based on type
function updatePlaceholders() {
    const nameInput = document.getElementById('subUnitName');
    const priceInput = document.getElementById('subUnitPrice');
    
    switch(selectedSubUnitType) {
        case 'table':
            nameInput.placeholder = 'e.g., Table 1, Table 2';
            priceInput.placeholder = 'e.g., 500, 750';
            break;
        case 'room':
            nameInput.placeholder = 'e.g., Room 101, Room 102';
            priceInput.placeholder = 'e.g., 1200, 1500';
            break;
        case 'cottage':
            nameInput.placeholder = 'e.g., Cottage A, Cottage B';
            priceInput.placeholder = 'e.g., 2000, 2500';
            break;
        default:
            nameInput.placeholder = 'e.g., Unit 1, Unit 2';
            priceInput.placeholder = 'e.g., 1000, 1500';
    }
}

// Add sub-unit to list
function addSubUnitToList() {
    const name = document.getElementById('subUnitName').value.trim();
    const capacity = parseInt(document.getElementById('subUnitCapacity').value);
    const price = parseFloat(document.getElementById('subUnitPrice').value);
    const details = document.getElementById('subUnitDetails').value.trim();
    
    if (!name || !price) {
        Swal.fire('Error', 'Please fill in unit name and price', 'error');
        return;
    }
    
    const subUnit = {
        name: name,
        type: selectedSubUnitType,
        capacity: capacity,
        price: price,
        details: details
    };
    
    subUnitsToCreate.push(subUnit);
    updateSubUnitPreview();
    
    // Clear form
    document.getElementById('subUnitName').value = '';
    document.getElementById('subUnitPrice').value = '';
    document.getElementById('subUnitDetails').value = '';
}

// Quick add multiple units
function quickAddUnits() {
    const startNum = parseInt(document.getElementById('startNumber').value);
    const count = parseInt(document.getElementById('unitCount').value);
    const basePrice = parseFloat(document.getElementById('basePrice').value);
    
    if (!basePrice) {
        Swal.fire('Error', 'Please enter a base price', 'error');
        return;
    }
    
    for (let i = 0; i < count; i++) {
        const unitNum = startNum + i;
        const unitName = `${selectedSubUnitType.charAt(0).toUpperCase() + selectedSubUnitType.slice(1)} ${unitNum}`;
        const price = basePrice + (i * 50); // Slight price variation
        const capacity = selectedSubUnitType === 'table' ? 4 + (i % 3) * 2 : 2 + (i % 3) * 2;
        
        const subUnit = {
            name: unitName,
            type: selectedSubUnitType,
            capacity: capacity,
            price: price,
            details: `${unitName} details`
        };
        
        subUnitsToCreate.push(subUnit);
    }
    
    updateSubUnitPreview();
}

// Update sub-unit preview
function updateSubUnitPreview() {
    const preview = document.getElementById('subUnitPreview');
    const list = document.getElementById('subUnitList');
    
    if (subUnitsToCreate.length === 0) {
        preview.classList.remove('show');
        return;
    }
    
    preview.classList.add('show');
    list.innerHTML = subUnitsToCreate.map((unit, index) => `
        <div class="sub-unit-item">
            <div>
                <strong>${unit.name}</strong>
                <span class="badge bg-primary ms-2">${unit.type}</span>
                <span class="badge bg-info ms-1">${unit.capacity} people</span>
                <span class="badge bg-success ms-1">₱${unit.price}</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSubUnit(${index})">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `).join('');
}

// Remove sub-unit from list
function removeSubUnit(index) {
    subUnitsToCreate.splice(index, 1);
    updateSubUnitPreview();
}

// Save facility with sub-units
function saveFacilityWithSubUnits() {
    const saveBtn = document.getElementById('saveFacilityBtn');
    const spinner = saveBtn.querySelector('.spinner-border');
    
    saveBtn.disabled = true;
    spinner.classList.remove('d-none');
    
    const formData = new FormData(document.getElementById('addFacilityForm'));
    const addSubUnits = document.getElementById('addSubUnits').checked;
    
    // Add sub-units data if enabled
    if (addSubUnits && subUnitsToCreate.length > 0) {
        formData.append('sub_units', JSON.stringify(subUnitsToCreate));
    }
    
    fetch('save_facility_with_sub_units.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Facility created successfully!',
                text: addSubUnits ? `Facility with ${subUnitsToCreate.length} sub-units created` : 'Facility created',
                timer: 2000,
                showConfirmButton: false
            });
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('addFacilityModal'));
            modal.hide();
            
            // Reset form
            document.getElementById('addFacilityForm').reset();
            subUnitsToCreate = [];
            updateSubUnitPreview();
            document.getElementById('addSubUnits').checked = false;
            toggleSubUnitForm();
            
            // Reload facilities
            loadFacilities();
            updateStats();
        } else {
            throw new Error(data.message || 'Failed to create facility');
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: err.message || 'Failed to create facility',
            timer: 3000,
            showConfirmButton: false
        });
    })
    .finally(() => {
        saveBtn.disabled = false;
        spinner.classList.add('d-none');
    });
}

// Update statistics
function updateStats() {
    const totalFacilities = facilities.length;
    const availableFacilities = facilities.filter(f => f.facility_status === 'Available').length;
    const occupiedFacilities = totalFacilities - availableFacilities;
    const totalRevenue = facilities.reduce((sum, f) => sum + parseFloat(f.facility_price || 0), 0);
    
    document.getElementById('totalFacilities').textContent = totalFacilities;
    document.getElementById('availableFacilities').textContent = availableFacilities;
    document.getElementById('occupiedFacilities').textContent = occupiedFacilities;
    document.getElementById('totalRevenue').textContent = `₱${totalRevenue.toFixed(2)}`;
}

// Edit facility
function editFacility() {
    if (!currentFacilityData) return;
    
    // Populate edit form
    document.getElementById('edit_facility_id').value = currentFacilityData.facility_id;
    document.getElementById('edit_pin_x').value = currentFacilityData.facility_pin_x;
    document.getElementById('edit_pin_y').value = currentFacilityData.facility_pin_y;
    document.getElementById('editFacilityName').value = currentFacilityData.facility_name;
    document.getElementById('editFacilityDetails').value = currentFacilityData.facility_details;
    document.getElementById('editFacilityStatus').value = currentFacilityData.facility_status;
    document.getElementById('editFacilityPrice').value = currentFacilityData.facility_price;
    
    // Hide current modal and show edit modal
    const currentModal = bootstrap.Modal.getInstance(document.getElementById('facilityModal'));
    currentModal.hide();
    
    const editModal = new bootstrap.Modal(document.getElementById('editFacilityModal'));
    editModal.show();
}

// Update facility
function updateFacility() {
    const updateBtn = document.getElementById('updateFacilityBtn');
    const spinner = updateBtn.querySelector('.spinner-border');
    
    updateBtn.disabled = true;
    spinner.classList.remove('d-none');
    
    const formData = new FormData(document.getElementById('editFacilityForm'));
    
    fetch('update_facility.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Facility updated successfully!',
                timer: 2000,
                showConfirmButton: false
            });
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('editFacilityModal'));
            modal.hide();
            
            // Reload facilities
            loadFacilities();
            updateStats();
        } else {
            throw new Error(data.message || 'Failed to update facility');
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: err.message || 'Failed to update facility',
            timer: 3000,
            showConfirmButton: false
        });
    })
    .finally(() => {
        updateBtn.disabled = false;
        spinner.classList.add('d-none');
    });
}

// Delete facility
function deleteFacility() {
    if (!currentFacilityData) return;
    
    Swal.fire({
        title: 'Are you sure?',
        text: `This will permanently delete "${currentFacilityData.facility_name}" and all its sub-units!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('facility_id', currentFacilityData.facility_id);
            formData.append('csrf_token', window.__csrfToken);
            
            fetch('delete_facility.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Facility has been deleted.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('facilityModal'));
                    modal.hide();
                    
                    // Reload facilities
                    loadFacilities();
                    updateStats();
                } else {
                    throw new Error(data.message || 'Failed to delete facility');
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: err.message || 'Failed to delete facility',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        }
    });
}

// Switch map view
function switchMap() {
    const switchInput = document.getElementById('mapSwitch');
    const currentLayer = document.querySelector('.map-layer.active');
    if (!currentLayer) return;
    
    currentLayer.classList.add('slide-up');
    
    setTimeout(() => {
        currentLayer.classList.remove('active', 'slide-up');
        currentMapIndex = switchInput.checked ? 1 : 0;
        const nextLayer = document.getElementById(`mapImage${currentMapIndex + 1}`);
        if (nextLayer) {
            nextLayer.classList.add('active', 'slide-down');
            setTimeout(() => nextLayer.classList.remove('slide-down'), 500);
        }
    }, 500);
}

// Make functions globally available
window.showAddFacilityModal = showAddFacilityModal;
window.toggleSubUnitForm = toggleSubUnitForm;
window.selectSubUnitType = selectSubUnitType;
window.addSubUnitToList = addSubUnitToList;
window.quickAddUnits = quickAddUnits;
window.removeSubUnit = removeSubUnit;
window.saveFacilityWithSubUnits = saveFacilityWithSubUnits;
window.editFacility = editFacility;
window.updateFacility = updateFacility;
window.deleteFacility = deleteFacility;
window.switchMap = switchMap;


