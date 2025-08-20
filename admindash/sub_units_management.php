<?php
require __DIR__ . '/_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Sub-Units Management - Shelton Beach Resort</title>

  <link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../template/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../template/assets/css/style.css" rel="stylesheet">
  <link href="../css/theme-overrides.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../js/notify.js?v=<?php echo filemtime(__DIR__ . '/../js/notify.js'); ?>"></script>

  <style>
    .sub-unit-card {
      border: 1px solid #e9ecef;
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    .sub-unit-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }
    .sub-unit-type-badge {
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
    }
    .capacity-indicator {
      background: linear-gradient(135deg, #7ab4a1, #e08f5f);
      color: white;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 0.875rem;
    }
    .status-available { background-color: #28a745; }
    .status-unavailable { background-color: #dc3545; }
    .status-maintenance { background-color: #ffc107; color: #212529; }
    .status-reserved { background-color: #6f42c1; }
    .facility-selector {
      max-width: 300px;
    }
    .sub-unit-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1rem;
    }
    @media (max-width: 768px) {
      .sub-unit-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="admindash.php" class="logo d-flex align-items-center">
        <img src="../pics/logo2.png" alt="">
        <span class="d-none d-lg-block">Shelton Admin</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn" title="Toggle sidebar"></i>
    </div>
  </header>

  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item">
        <a class="nav-link collapsed" href="../index.php">
          <i class="bi bi-house"></i><span>Home</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="admindash.php">
          <i class="bi bi-grid"></i><span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="facility_mapping.php">
          <i class="bi bi-geo-alt"></i><span>Facility Mapping</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="sub_units_management.php">
          <i class="bi bi-grid-3x3-gap"></i><span>Sub-Units</span>
        </a>
      </li>
    </ul>
  </aside>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Sub-Units Management</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="admindash.php">Home</a></li>
          <li class="breadcrumb-item active">Sub-Units Management</li>
        </ol>
      </nav>
    </div>

    <section class="section">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                  <i class='bx bx-grid-3x3-gap'></i> Manage Sub-Units
                </h5>
                <div class="d-flex gap-2">
                  <select class="form-select facility-selector" id="facilityFilter">
                    <option value="">All Facilities</option>
                  </select>
                  <button class="btn btn-primary" onclick="showAddSubUnitModal()">
                    <i class="bi bi-plus"></i> Add Sub-Unit
                  </button>
                </div>
              </div>

              <div class="sub-unit-grid" id="subUnitsGrid">
                <!-- Sub-units will be loaded here -->
              </div>

              <div class="text-center mt-4" id="noSubUnits" style="display: none;">
                <i class="bi bi-grid-3x3-gap display-4 text-muted"></i>
                <h5 class="text-muted mt-3">No sub-units found</h5>
                <p class="text-muted">Create your first sub-unit to get started</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer id="footer" class="footer">
    <div class="copyright">&copy; <strong><span>Shelton Beach Resort</span></strong> All Rights Reserved</div>
  </footer>

  <!-- Add Sub-Unit Modal -->
  <div class="modal fade" id="addSubUnitModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Sub-Unit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addSubUnitForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            
            <div class="mb-3">
              <label class="form-label">Facility</label>
              <select class="form-select" name="facility_id" required>
                <option value="">Select Facility</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Sub-Unit Name</label>
              <input type="text" class="form-control" name="sub_unit_name" required placeholder="e.g., Table 1, Room 101">
            </div>

            <div class="mb-3">
              <label class="form-label">Type</label>
              <select class="form-select" name="sub_unit_type" required>
                <option value="table">Table</option>
                <option value="room">Room</option>
                <option value="cottage">Cottage</option>
                <option value="area">Area</option>
                <option value="cabana">Cabana</option>
                <option value="pavilion">Pavilion</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Capacity (People)</label>
              <input type="number" class="form-control" name="sub_unit_capacity" min="1" max="50" value="4" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Price (₱)</label>
              <input type="number" class="form-control" name="sub_unit_price" step="0.01" min="0" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Details</label>
              <textarea class="form-control" name="sub_unit_details" rows="3" placeholder="Description, features, location notes..."></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveSubUnit()" id="saveSubUnitBtn">
            <span class="spinner-border spinner-border-sm d-none" role="status"></span> Save Sub-Unit
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Sub-Unit Modal -->
  <div class="modal fade" id="editSubUnitModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Sub-Unit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="editSubUnitForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" name="sub_unit_id" id="edit_sub_unit_id">
            
            <div class="mb-3">
              <label class="form-label">Sub-Unit Name</label>
              <input type="text" class="form-control" name="sub_unit_name" id="edit_sub_unit_name" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Type</label>
              <select class="form-select" name="sub_unit_type" id="edit_sub_unit_type" required>
                <option value="table">Table</option>
                <option value="room">Room</option>
                <option value="cottage">Cottage</option>
                <option value="area">Area</option>
                <option value="cabana">Cabana</option>
                <option value="pavilion">Pavilion</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Capacity (People)</label>
              <input type="number" class="form-control" name="sub_unit_capacity" id="edit_sub_unit_capacity" min="1" max="50" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Price (₱)</label>
              <input type="number" class="form-control" name="sub_unit_price" id="edit_sub_unit_price" step="0.01" min="0" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="sub_unit_status" id="edit_sub_unit_status" required>
                <option value="Available">Available</option>
                <option value="Unavailable">Unavailable</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Reserved">Reserved</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Available for Booking</label>
              <select class="form-select" name="is_available" id="edit_is_available" required>
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Details</label>
              <textarea class="form-control" name="sub_unit_details" id="edit_sub_unit_details" rows="3"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="updateSubUnit()" id="updateSubUnitBtn">
            <span class="spinner-border spinner-border-sm d-none" role="status"></span> Update Sub-Unit
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../template/assets/js/main.js"></script>
  <script>
    window.__csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>';
    
    let facilities = [];
    let subUnits = [];
    let currentSubUnit = null;

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
      loadFacilities();
      loadSubUnits();
      
      // Facility filter change
      document.getElementById('facilityFilter').addEventListener('change', function() {
        filterSubUnits();
      });
    });

    // Load facilities for dropdown
    function loadFacilities() {
      fetch('fetch_facilities.php')
        .then(r => r.json())
        .then(data => {
          facilities = data;
          const facilityFilter = document.getElementById('facilityFilter');
          const addFormSelect = document.querySelector('#addSubUnitForm select[name="facility_id"]');
          
          // Populate filter dropdown
          facilityFilter.innerHTML = '<option value="">All Facilities</option>';
          facilities.forEach(f => {
            facilityFilter.innerHTML += `<option value="${f.facility_id}">${f.facility_name}</option>`;
          });
          
          // Populate add form dropdown
          addFormSelect.innerHTML = '<option value="">Select Facility</option>';
          facilities.forEach(f => {
            addFormSelect.innerHTML += `<option value="${f.facility_id}">${f.facility_name}</option>`;
          });
        })
        .catch(err => console.error('Failed to load facilities:', err));
    }

    // Load all sub-units
    function loadSubUnits() {
      fetch('fetch_sub_units.php')
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            subUnits = data.sub_units;
            renderSubUnits();
          } else {
            console.error('Failed to load sub-units:', data.message);
          }
        })
        .catch(err => console.error('Failed to load sub-units:', err));
    }

    // Render sub-units in grid
    function renderSubUnits() {
      const grid = document.getElementById('subUnitsGrid');
      const noSubUnits = document.getElementById('noSubUnits');
      
      if (subUnits.length === 0) {
        grid.style.display = 'none';
        noSubUnits.style.display = 'block';
        return;
      }
      
      grid.style.display = 'grid';
      noSubUnits.style.display = 'none';
      
      grid.innerHTML = subUnits.map(subUnit => {
        const facility = facilities.find(f => f.facility_id == subUnit.facility_id);
        const facilityName = facility ? facility.facility_name : 'Unknown Facility';
        
        return `
          <div class="card sub-unit-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="card-title mb-0">${subUnit.sub_unit_name}</h6>
                <span class="badge sub-unit-type-badge bg-primary">${subUnit.sub_unit_type}</span>
              </div>
              
              <p class="text-muted small mb-2">
                <i class="bi bi-building"></i> ${facilityName}
              </p>
              
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="capacity-indicator">
                  <i class="bi bi-people"></i>
                  <span class="ms-1">${subUnit.sub_unit_capacity}</span>
                </div>
                <div class="text-end">
                  <div class="fw-bold text-primary">₱${parseFloat(subUnit.sub_unit_price).toFixed(2)}</div>
                  <span class="badge status-${subUnit.sub_unit_status.toLowerCase()}">${subUnit.sub_unit_status}</span>
                </div>
              </div>
              
              ${subUnit.sub_unit_details ? `<p class="text-muted small mb-3">${subUnit.sub_unit_details}</p>` : ''}
              
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="editSubUnit(${subUnit.sub_unit_id})">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteSubUnit(${subUnit.sub_unit_id})">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </div>
            </div>
          </div>
        `;
      }).join('');
    }

    // Filter sub-units by facility
    function filterSubUnits() {
      const facilityId = document.getElementById('facilityFilter').value;
      const filtered = facilityId ? subUnits.filter(s => s.facility_id == facilityId) : subUnits;
      
      const grid = document.getElementById('subUnitsGrid');
      const noSubUnits = document.getElementById('noSubUnits');
      
      if (filtered.length === 0) {
        grid.style.display = 'none';
        noSubUnits.style.display = 'block';
        noSubUnits.innerHTML = `
          <i class="bi bi-grid-3x3-gap display-4 text-muted"></i>
          <h5 class="text-muted mt-3">No sub-units found</h5>
          <p class="text-muted">No sub-units found for the selected facility</p>
        `;
        return;
      }
      
      // Temporarily replace subUnits array for rendering
      const originalSubUnits = subUnits;
      subUnits = filtered;
      renderSubUnits();
      subUnits = originalSubUnits;
    }

    // Show add sub-unit modal
    window.showAddSubUnitModal = function() {
      document.getElementById('addSubUnitForm').reset();
      const modal = new bootstrap.Modal(document.getElementById('addSubUnitModal'));
      modal.show();
    };

    // Save new sub-unit
    window.saveSubUnit = function() {
      const saveBtn = document.getElementById('saveSubUnitBtn');
      const spinner = saveBtn.querySelector('.spinner-border');
      
      saveBtn.disabled = true;
      spinner.classList.remove('d-none');
      
      const formData = new FormData(document.getElementById('addSubUnitForm'));
      
      fetch('save_sub_unit.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Sub-unit created!',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          });
          
          const modal = bootstrap.Modal.getInstance(document.getElementById('addSubUnitModal'));
          modal.hide();
          
          loadSubUnits();
        } else {
          throw new Error(data.message || 'Failed to create sub-unit');
        }
      })
      .catch(err => {
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: err.message || 'Failed to create sub-unit',
          timer: 3000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        });
      })
      .finally(() => {
        saveBtn.disabled = false;
        spinner.classList.add('d-none');
      });
    };

    // Edit sub-unit
    window.editSubUnit = function(subUnitId) {
      const subUnit = subUnits.find(s => s.sub_unit_id == subUnitId);
      if (!subUnit) return;
      
      currentSubUnit = subUnit;
      
      // Populate edit form
      document.getElementById('edit_sub_unit_id').value = subUnit.sub_unit_id;
      document.getElementById('edit_sub_unit_name').value = subUnit.sub_unit_name;
      document.getElementById('edit_sub_unit_type').value = subUnit.sub_unit_type;
      document.getElementById('edit_sub_unit_capacity').value = subUnit.sub_unit_capacity;
      document.getElementById('edit_sub_unit_price').value = subUnit.sub_unit_price;
      document.getElementById('edit_sub_unit_status').value = subUnit.sub_unit_status;
      document.getElementById('edit_is_available').value = subUnit.is_available ? '1' : '0';
      document.getElementById('edit_sub_unit_details').value = subUnit.sub_unit_details || '';
      
      const modal = new bootstrap.Modal(document.getElementById('editSubUnitModal'));
      modal.show();
    };

    // Update sub-unit
    window.updateSubUnit = function() {
      const updateBtn = document.getElementById('updateSubUnitBtn');
      const spinner = updateBtn.querySelector('.spinner-border');
      
      updateBtn.disabled = true;
      spinner.classList.remove('d-none');
      
      const formData = new FormData(document.getElementById('editSubUnitForm'));
      
      fetch('update_sub_unit.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Sub-unit updated!',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          });
          
          const modal = bootstrap.Modal.getInstance(document.getElementById('editSubUnitModal'));
          modal.hide();
          
          loadSubUnits();
        } else {
          throw new Error(data.message || 'Failed to update sub-unit');
        }
      })
      .catch(err => {
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: err.message || 'Failed to update sub-unit',
          timer: 3000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        });
      })
      .finally(() => {
        updateBtn.disabled = false;
        spinner.classList.add('d-none');
      });
    };

    // Delete sub-unit
    window.deleteSubUnit = function(subUnitId) {
      const subUnit = subUnits.find(s => s.sub_unit_id == subUnitId);
      if (!subUnit) return;
      
      Swal.fire({
        title: 'Delete Sub-Unit?',
        text: `Are you sure you want to delete "${subUnit.sub_unit_name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        confirmButtonColor: '#dc3545'
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append('sub_unit_id', subUnitId);
          formData.append('csrf_token', window.__csrfToken);
          
          fetch('delete_sub_unit.php', {
            method: 'POST',
            body: formData
          })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
              });
              
              loadSubUnits();
            } else {
              throw new Error(data.message || 'Failed to delete sub-unit');
            }
          })
          .catch(err => {
            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: err.message || 'Failed to delete sub-unit',
              timer: 3000,
              showConfirmButton: false,
              toast: true,
              position: 'top-end'
            });
          });
        }
      });
    };

    // Ensure sidebar toggle works
    (function(){
      try {
        const t = document.querySelector('.toggle-sidebar-btn');
        if (t && !t.__bound) {
          t.addEventListener('click', function(e){
            e.preventDefault();
            document.body.classList.toggle('toggle-sidebar');
          });
          t.__bound = true;
        }
      } catch(e) {}
    })();
  </script>
</body>
</html>
