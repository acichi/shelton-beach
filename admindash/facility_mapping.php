<?php
require __DIR__ . '/_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Facility Mapping - Shelton Beach Resort</title>

  <link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../template/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../template/assets/css/style.css" rel="stylesheet">
  <link href="../css/theme-overrides.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../js/notify.js?v=<?php echo filemtime(__DIR__ . '/../js/notify.js'); ?>"></script>

  <style>
    .svg-container{width:100%;max-width:none;margin:0;border:none;background:transparent;overflow:hidden}
    #facility-map{width:100%;height:100%;min-height:520px;display:block;cursor:crosshair}
    .facility-pin{transition:all .2s ease;cursor:pointer}
    .facility-pin:hover{r:10;stroke-width:3}
    .facility-stats{background:#fff;padding:10px;margin-bottom:10px;border-radius:8px;box-shadow:0 2px 10px rgba(1,41,112,.08)}
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-top:5px}
    .stat-card{text-align:center;padding:10px;border-radius:8px;background:linear-gradient(135deg,#7ab4a1,#e08f5f);color:#fff}
    .map-layer{opacity:0;transition:opacity .5s ease,transform .5s ease;transform:translateY(0)}
    .map-layer.active{opacity:1}
    .map-layer.slide-up{transform:translateY(-20px)}
    .map-layer.slide-down{transform:translateY(20px)}
    .image-container{position:relative;min-height:150px;display:flex;align-items:center;justify-content:center;background:#f8f9fa;border-radius:4px;overflow:hidden}
    .image-container img{max-width:100%;max-height:240px;object-fit:contain}
    
    /* Enhanced modal styles */
    .sub-unit-section {
      border: 1px solid #e9ecef;
      border-radius: 8px;
      padding: 1rem;
      margin-top: 1rem;
      background: #f8f9fa;
      transition: all 0.3s ease;
    }
    .sub-unit-section.show {
      background: #fff;
      border-color: #7ab4a1;
      box-shadow: 0 2px 8px rgba(122,180,161,0.2);
    }
    .sub-unit-form {
      display: none;
    }
    .sub-unit-form.show {
      display: block;
    }
    .sub-unit-preview {
      background: #e8f5e8;
      border: 1px solid #28a745;
      border-radius: 6px;
      padding: 0.75rem;
      margin-top: 1rem;
      display: none;
    }
    .sub-unit-preview.show {
      display: block;
    }
    .sub-unit-item {
      background: #fff;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      padding: 0.5rem;
      margin: 0.25rem 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .sub-unit-item .badge {
      font-size: 0.75rem;
    }
    .facility-type-selector {
      margin-bottom: 1rem;
    }
    .facility-type-selector .btn {
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
    }
    .facility-type-selector .btn.active {
      background: #7ab4a1;
      border-color: #7ab4a1;
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
        <a class="nav-link" href="facility_mapping.php">
          <i class="bi bi-geo-alt"></i><span>Facility Mapping</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="sub_units_management.php">
          <i class="bi bi-grid-3x3-gap"></i><span>Sub-Units</span>
        </a>
      </li>
    </ul>
  </aside>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Facility Mapping</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="admindash.php">Home</a></li>
          <li class="breadcrumb-item active">Facility Mapping</li>
        </ol>
      </nav>
    </div>

    <section class="section">
      <div class="row">
        <div class="col-12">
          <div class="facility-stats">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0"><i class='bx bx-map'></i> Interactive Map</h5>
              <div>
                <button class="btn btn-success btn-sm me-2" onclick="testFunction()">
                  <i class='bx bx-check'></i> Test JS
                </button>
                <button class="btn btn-primary btn-sm" onclick="showAddFacilityModal()">
                  <i class='bx bx-plus'></i> Add Facility
                </button>
              </div>
            </div>
            <div class="stats-grid">
              <div class="stat-card">
                <div class="stat-number" id="totalFacilities">0</div>
                <div class="stat-label">Total Facilities</div>
              </div>
              <div class="stat-card">
                <div class="stat-number" id="availableFacilities">0</div>
                <div class="stat-label">Available</div>
              </div>
              <div class="stat-card">
                <div class="stat-number" id="occupiedFacilities">0</div>
                <div class="stat-label">Occupied</div>
              </div>
              <div class="stat-card">
                <div class="stat-number" id="totalRevenue">₱0</div>
                <div class="stat-label">Total Revenue</div>
              </div>
            </div>
          </div>

          <div class="svg-container">
            <div class="map-switch-container mb-2 text-center">
              <div class="form-check form-switch d-inline-flex align-items-center">
                <input class="form-check-input" type="checkbox" id="mapSwitch" onchange="switchMap()">
                <label class="form-check-label ms-2" for="mapSwitch">Switch Map View</label>
              </div>
            </div>

            <svg id="facility-map" viewBox="0 0 1200 600" preserveAspectRatio="xMidYMid slice">
              <image id="mapImage1" href="../pics/1.svg" width="1200" height="600" class="map-layer active" />
              <image id="mapImage2" href="../pics/2.svg" width="1200" height="600" class="map-layer" />
            </svg>
          </div>

          <div class="mt-2 text-center text-muted">
            <small><i class='bx bx-info-circle'></i> Click map to add a facility. Drag pins to reposition.</small>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer id="footer" class="footer">
    <div class="copyright">&copy; <strong><span>Shelton Beach Resort</span></strong> All Rights Reserved</div>
  </footer>

  <!-- Enhanced Add Facility Modal with Sub-Units Integration -->
  <div class="modal fade" id="addFacilityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Facility</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addFacilityForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" id="pin_x" name="pin_x">
            <input type="hidden" id="pin_y" name="pin_y">
            
            <!-- Main Facility Details -->
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Facility Name</label>
                  <input type="text" class="form-control" id="facilityName" name="name" required placeholder="e.g., Restaurant Complex, Cottage Area">
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Facility Type</label>
                  <select class="form-select" id="facilityType" name="facility_type" required>
                    <option value="">Select Type</option>
                    <option value="restaurant">Restaurant</option>
                    <option value="cottage">Cottage</option>
                    <option value="room">Room</option>
                    <option value="area">Area</option>
                    <option value="pavilion">Pavilion</option>
                    <option value="other">Other</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Details</label>
              <textarea class="form-control" id="facilityDetails" name="details" rows="3" required placeholder="Describe the facility, location, features..."></textarea>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Main Facility Price (₱)</label>
                  <input type="number" step="0.01" class="form-control" id="facilityPrice" name="price" required placeholder="Price for renting entire facility">
                  <small class="text-muted">Price when customers want to rent the whole place</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Status</label>
                  <select class="form-select" id="facilityStatus" name="status" required>
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Facility Image</label>
              <input type="file" class="form-control" id="facilityImage" name="image" accept="image/*" required>
            </div>

            <!-- Sub-Units Integration -->
            <div class="sub-unit-section" id="subUnitSection">
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="addSubUnits" onchange="toggleSubUnitForm()">
                <label class="form-check-label" for="addSubUnits">
                  <strong>Add Sub-Units to this Facility</strong>
                </label>
                <small class="text-muted d-block">Check this if you want customers to book individual units (tables, rooms, etc.) instead of the entire facility</small>
              </div>

              <!-- Sub-Unit Form (Hidden by default) -->
              <div class="sub-unit-form" id="subUnitForm">
                <h6 class="mb-3"><i class="bi bi-grid-3x3-gap"></i> Sub-Unit Configuration</h6>
                
                <!-- Facility Type Selector -->
                <div class="facility-type-selector">
                  <label class="form-label">What type of sub-units does this facility have?</label>
                  <div class="mt-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-type="table" onclick="selectSubUnitType('table')">Tables</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-type="room" onclick="selectSubUnitType('room')">Rooms</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-type="cottage" onclick="selectSubUnitType('cottage')">Cottages</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-type="area" onclick="selectSubUnitType('area')">Areas</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-type="cabana" onclick="selectSubUnitType('cabana')">Cabanas</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-type="pavilion" onclick="selectSubUnitType('pavilion')">Pavilions</button>
                  </div>
                </div>

                <!-- Sub-Unit Template -->
                <div class="row mb-3">
                  <div class="col-md-3">
                    <label class="form-label">Unit Name</label>
                    <input type="text" class="form-control" id="subUnitName" placeholder="e.g., Table 1, Room 101">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="subUnitCapacity" min="1" max="50" value="4">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Price (₱)</label>
                    <input type="number" step="0.01" class="form-control" id="subUnitPrice" placeholder="Individual unit price">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Details</label>
                    <input type="text" class="form-control" id="subUnitDetails" placeholder="e.g., Window seat, Ocean view">
                  </div>
                  <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-success btn-sm" onclick="addSubUnitToList()">
                      <i class="bi bi-plus"></i>
                    </button>
                  </div>
                </div>

                <!-- Sub-Unit List -->
                <div class="sub-unit-preview" id="subUnitPreview">
                  <h6 class="mb-2">Sub-Units to be created:</h6>
                  <div id="subUnitList"></div>
                </div>

                <!-- Quick Add Multiple Units -->
                <div class="mt-3 p-3 bg-light rounded">
                  <h6 class="mb-2">Quick Add Multiple Units</h6>
                  <div class="row">
                    <div class="col-md-3">
                      <label class="form-label">Starting Number</label>
                      <input type="number" class="form-control" id="startNumber" value="1" min="1">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">How Many</label>
                      <input type="number" class="form-control" id="unitCount" value="4" min="1" max="20">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Base Price (₱)</label>
                      <input type="number" step="0.01" class="form-control" id="basePrice" placeholder="500">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">&nbsp;</label>
                      <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickAddUnits()">
                        <i class="bi bi-magic"></i> Quick Add
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveFacilityWithSubUnits()" id="saveFacilityBtn">
            <span class="spinner-border spinner-border-sm d-none" role="status"></span> Save Facility
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- View/Edit Facility Modal -->
  <div class="modal fade" id="facilityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Facility Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="facilityModalBody"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" onclick="editFacility()" id="editFacilityBtn">Edit</button>
          <button type="button" class="btn btn-danger" onclick="deleteFacility()" id="deleteFacilityBtn">Delete</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Facility Modal -->
  <div class="modal fade" id="editFacilityModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Facility</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="editFacilityForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" id="edit_facility_id">
            <input type="hidden" id="edit_pin_x">
            <input type="hidden" id="edit_pin_y">
            <div class="mb-3">
              <label class="form-label">Facility Name</label>
              <input type="text" class="form-control" id="editFacilityName" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Details</label>
              <textarea class="form-control" id="editFacilityDetails" rows="3" required></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" id="editFacilityStatus" required>
                <option value="Available">Available</option>
                <option value="Unavailable">Unavailable</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Price (₱)</label>
              <input type="number" step="0.01" class="form-control" id="editFacilityPrice" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Facility Image (optional)</label>
              <input type="file" class="form-control" id="editFacilityImage" accept="image/*">
              <small class="text-muted">Leave empty to keep current image</small>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="updateFacility()" id="updateFacilityBtn"><span class="spinner-border spinner-border-sm d-none" role="status"></span> Update Facility</button>
        </div>
      </div>
    </div>
  </div>

  <script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../template/assets/js/main.js"></script>
  <script>
    window.__csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>';
    
    // Test if JavaScript is working
    console.log('🔍 Testing JavaScript functionality...');
    window.testFunction = function() {
      alert('JavaScript is working!');
      console.log('✅ JavaScript test successful');
    };

    // Sub-units management
    let selectedSubUnitType = 'table';
    let subUnitsToCreate = [];
    let currentFacilityData = null;
    let currentFacilityPin = null;

    function toggleSubUnitForm() {
      const checkbox = document.getElementById('addSubUnits');
      const form = document.getElementById('subUnitForm');
      const section = document.getElementById('subUnitSection');
      
      if (checkbox.checked) {
        form.classList.add('show');
        section.classList.add('show');
        // Auto-select facility type based on name
        autoSelectFacilityType();
      } else {
        form.classList.remove('show');
        section.classList.remove('show');
        subUnitsToCreate = [];
        updateSubUnitPreview();
      }
    }

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

    function autoSelectFacilityType() {
      const facilityName = document.getElementById('facilityName').value.toLowerCase();
      const facilityType = document.getElementById('facilityType').value;
      
      if (facilityName.includes('restaurant') || facilityName.includes('dining')) {
        selectSubUnitType('table');
      } else if (facilityName.includes('cottage')) {
        selectSubUnitType('cottage');
      } else if (facilityName.includes('room')) {
        selectSubUnitType('room');
      }
    }

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

    function removeSubUnit(index) {
      subUnitsToCreate.splice(index, 1);
      updateSubUnitPreview();
    }

    // Enhanced save function
    window.saveFacilityWithSubUnits = function() {
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
    };

    // Existing functions (keep them as is)
    window.showAddFacilityModal = function() {
      const form = document.getElementById('addFacilityForm');
      if (form) form.reset();
      subUnitsToCreate = [];
      updateSubUnitPreview();
      document.getElementById('addSubUnits').checked = false;
      toggleSubUnitForm();
      const modal = new bootstrap.Modal(document.getElementById('addFacilityModal'));
      modal.show();
    };

    // ... existing code ...
  </script>
  <script src="js/facility_mapping.js"></script>
  <script>
    // Ensure sidebar toggle works if template JS didn't bind it yet
    (function(){ try { const t=document.querySelector('.toggle-sidebar-btn'); if (t && !t.__bound) { t.addEventListener('click', function(e){ e.preventDefault(); document.body.classList.toggle('toggle-sidebar'); }); t.__bound=true; } } catch(e) {} })();
    let currentMapIndex = 0;
    const mapImages = [
      { id: 'mapImage1', name: 'Map 1', href: '../pics/1.svg' },
      { id: 'mapImage2', name: 'Map 2', href: '../pics/2.svg' }
    ];
    function switchMap() {
      const switchInput = document.getElementById('mapSwitch');
      const currentLayer = document.querySelector('.map-layer.active');
      if (!currentLayer) return;
      currentLayer.classList.add('slide-up');
      setTimeout(() => {
        currentLayer.classList.remove('active','slide-up');
        currentMapIndex = switchInput.checked ? 1 : 0;
        const nextLayer = document.getElementById(mapImages[currentMapIndex].id);
        if (nextLayer) {
          nextLayer.classList.add('active','slide-down');
          setTimeout(() => nextLayer.classList.remove('slide-down'), 500);
        }
      }, 500);
    }
    document.addEventListener('DOMContentLoaded', () => {
      const firstLayer = document.getElementById('mapImage1');
      if (firstLayer) firstLayer.classList.add('active');
    });
  </script>
</body>
</html>


