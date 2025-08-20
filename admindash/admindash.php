<?php
require __DIR__ . '/_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Admin Dashboard - Shelton Beach Resort</title>

  <!-- Vendor CSS Files (NiceAdmin) -->
  <link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../template/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  
  
  
  

  <!-- Template Main CSS File -->
  <link href="../template/assets/css/style.css" rel="stylesheet">
  <link href="../css/theme-overrides.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <style>
    .chart-fixed{position:relative;height:clamp(200px, 35vh, 320px);width:100%;}
    .chart-fixed.tall{height:clamp(220px, 40vh, 360px)}
    #salesChart,#topFacilitiesChart,#statusChart,#occupancyChart{width:100% !important;height:100% !important;}
    /* Sticky table headers */
    .table thead th{position:sticky;top:0;background:#f6f9ff;z-index:1}
    /* Recent reservations compact & fit */
    .recent-sales table{table-layout:fixed;width:100%;}
    .recent-sales th:nth-child(1), .recent-sales td:nth-child(1){width:64px}
    .recent-sales th:nth-child(4), .recent-sales td:nth-child(4){width:96px}
    .recent-sales td{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    /* Themed export buttons */
    .btn-theme-aqua{color:#fff;background:var(--color-aqua, #7ab4a1);border-color:var(--color-aqua, #7ab4a1)}
    .btn-theme-aqua:hover{color:#fff;filter:brightness(0.92)}
    .btn-theme-aqua:focus,.btn-theme-aqua:active{box-shadow:0 0 0 .2rem rgba(122,180,161,.25)}
    .btn-theme-orange{color:#fff;background:var(--color-orange, #e08f5f);border-color:var(--color-orange, #e08f5f)}
    .btn-theme-orange:hover{color:#fff;filter:brightness(0.92)}
    .btn-theme-orange:focus,.btn-theme-orange:active{box-shadow:0 0 0 .2rem rgba(224,143,95,.25)}
    .btn-theme-pink{color:#fff;background:var(--color-pink, #e19985);border-color:var(--color-pink, #e19985)}
    /* Also target DataTables default button classes */
    .dt-buttons .dt-button.buttons-copy{color:#fff !important;background:var(--color-aqua, #7ab4a1) !important;border-color:var(--color-aqua, #7ab4a1) !important}
    .dt-buttons .dt-button.buttons-copy:hover{filter:brightness(0.92)}
    .dt-buttons .dt-button.buttons-excel{color:#fff !important;background:var(--color-orange, #e08f5f) !important;border-color:var(--color-orange, #e08f5f) !important}
    .dt-buttons .dt-button.buttons-excel:hover{filter:brightness(0.92)}
    .dt-buttons .dt-button.buttons-csv{color:#fff !important;background:var(--color-pink, #e19985) !important;border-color:var(--color-pink, #e19985) !important}
    .dt-buttons .dt-button.buttons-csv:hover{filter:brightness(0.92)}
    .btn-theme-pink:hover{color:#fff;filter:brightness(0.92)}
    .btn-theme-pink:focus,.btn-theme-pink:active{box-shadow:0 0 0 .2rem rgba(225,153,133,.25)}
    /* Compact card title spacing */
    .card-body>.card-title{margin-bottom:1rem}
    /* Compact layout overrides for admin cards */
    #main .row.g-4{--bs-gutter-x:1rem;--bs-gutter-y:1rem}
    @media (max-width: 1199.98px){
      /* Ensure the four stat cards stay on a single row with scroll on narrow screens */
      #page-dashboard .row.flex-nowrap{overflow-x:auto}
      #page-dashboard .row.flex-nowrap > [class^="col-"]{flex:0 0 auto}
      #page-dashboard .row.flex-nowrap > .col-3{width: 260px}
    }
    #main .card .card-body{padding:.75rem .9rem}
    #main .card .card-title{font-size:1rem;margin-bottom:.5rem}
    #main .info-card .card-icon{width:42px;height:42px}
    #main .info-card .card-icon i{font-size:18px}
    #main .info-card h6{font-size:1.125rem;margin:0}
    #main .table td,#main .table th{padding:.5rem .5rem}
    #main .btn-sm{padding:.25rem .5rem;font-size:.875rem}
  </style>
</head>
<body>

  <!-- Header -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="#" class="logo d-flex align-items-center">
        <img src="../pics/logo2.png" alt="">
        <span class="d-none d-lg-block">Shelton Admin</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn" onclick="document.body.classList.toggle('toggle-sidebar'); return false;" title="Toggle sidebar"></i>
    </div>
    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
        <li class="nav-item pe-3">
          <div class="nav-link nav-profile d-flex align-items-center pe-0">
            <img src="../pics/profile.png" alt="Profile" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" onerror="this.onerror=null;this.src='../template/assets/img/profile-img.jpg';">
            <?php 
              $__admFull = htmlspecialchars($_SESSION['user']['fullname'] ?? 'Admin');
              $__admUser = htmlspecialchars($_SESSION['user']['username'] ?? '');
            ?>
            <span class="d-none d-md-block ps-2"><?php echo $__admFull . ($__admUser !== '' ? " (@$__admUser)" : ''); ?></span>
          </div>
        </li>
      </ul>
    </nav>
  </header>
  <!-- End Header -->

  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item">
        <a class="nav-link collapsed" href="../index.php">
          <i class="bi bi-house"></i>
          <span>Home</span>
        </a>
      </li>
      <li class="nav-heading">Overview</li>
      <li class="nav-item">
        <a class="nav-link" id="menu-dashboard" href="#main" onclick="showPage('dashboard')">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" id="menu-analytics" href="#analytics" onclick="showPage('analytics')">
          <i class="bi bi-bar-chart"></i>
          <span>Analytics</span>
        </a>
      </li>
      <li class="nav-heading">Manage</li>
      <li class="nav-item">
        <a class="nav-link collapsed" id="menu-reservation" href="#reservations" onclick="showPage('reservations')">
          <i class="bi bi-calendar-check"></i>
          <span>Reservations</span>
          <span class="badge rounded-pill bg-warning text-dark ms-2" id="badgePending" style="display:none">0</span>
          <span class="badge rounded-pill bg-danger ms-1" id="badgeCancels" style="display:none">0</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" id="menu-customers" href="#customers" onclick="showPage('customers')">
          <i class="bi bi-people"></i>
          <span>Customers</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" id="menu-facilities" href="#facilities" onclick="showPage('facilities')">
          <i class="bi bi-buildings"></i>
          <span>Facilities</span>
        </a>
      </li>
      <li class="nav-heading">Communication</li>
      <li class="nav-item">
        <a class="nav-link collapsed" id="menu-inquiries" href="#page-inquiries" onclick="showPage('inquiries')">
          <i class="bi bi-envelope"></i>
          <span>Inquiries</span>
          <span class="badge rounded-pill bg-danger ms-2" id="badgeInquiries" style="display:none">0</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" id="menu-feedback" href="#feedback" onclick="showPage('feedback')">
          <i class="bi bi-chat-left-dots"></i>
          <span>Feedback</span>
        </a>
      </li>
      <li class="nav-heading">Content</li>
      <li class="nav-item">
        <a class="nav-link collapsed" id="menu-gallery" href="#gallery" onclick="showPage('gallery')">
          <i class="bi bi-images"></i>
          <span>Gallery</span>
        </a>
      </li>
      <li class="nav-heading">Tools</li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="walkin_guest.php">
          <i class="bi bi-person-plus"></i>
          <span>Walk-in Booking</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="facility_mapping.php">
          <i class="bi bi-geo-alt"></i>
          <span>Facility Mapping</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="sub_units_management.php">
          <i class="bi bi-grid-3x3-gap"></i>
          <span>Sub-Units Management</span>
        </a>
      </li>
      <li class="nav-heading">System</li>
      <li class="nav-item">
        <a class="nav-link collapsed" id="menu-activity" href="activity_log.php">
          <i class="bi bi-clipboard-data"></i>
          <span>Activity Log</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" id="menu-settings" href="settings.php" onclick="showPage('settings')">
          <i class="bi bi-gear"></i>
          <span>Settings</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed text-danger" id="menu-logout" href="#" onclick="confirmLogout(); return false;">
          <i class="bi bi-box-arrow-right"></i>
          <span>Logout</span>
        </a>
      </li>
    </ul>
  </aside>
  <!-- End Sidebar -->

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Admin Dashboard</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </nav>
    </div>
    <div id="alertPlaceholder"></div>

    <section class="section dashboard">
      <div class="content-page active" id="page-dashboard">
        <div class="row g-4 flex-nowrap">
          <div class="col-3">
            <div class="card info-card sales-card">
              <div class="card-body">
                <h5 class="card-title">Reservations <span>| Total</span></h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-calendar-check"></i>
                  </div>
                  <div class="ps-3">
                    <h6 id="statReservations">0</h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-3">
            <div class="card info-card customers-card">
              <div class="card-body">
                <h5 class="card-title">Facilities <span>| Total</span></h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-buildings"></i>
                  </div>
                  <div class="ps-3">
                    <h6 id="statFacilities">0</h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-3">
            <div class="card info-card customers-card">
              <div class="card-body">
                <h5 class="card-title">Customers <span>| Total</span></h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-people"></i>
                  </div>
                  <div class="ps-3">
                    <h6 id="statCustomers">0</h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-3">
            <div class="card info-card revenue-card">
              <div class="card-body">
                <h5 class="card-title">Revenue <span>| All-time</span></h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-currency-dollar"></i>
                  </div>
                  <div class="ps-3">
                    <h6 id="statRevenue">₱0</h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-lg-8">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title" >Revenue Analytics
                  <select id="timeRangeSelect" class="form-select form-select-sm d-inline-block ms-2" style="width: auto;">
                    <option value="daily">Today</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly" selected>Monthly</option>
                    <option value="yearly">Yearly</option>
                  </select>
                  <span id="totalSales" class="ms-3 fw-semibold">Total Sales: ₱0</span>
                </h5>
                <div class="chart-fixed position-relative">
                  <canvas id="salesChart"></canvas>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card recent-sales overflow-auto">
              <div class="card-body">
                <h5 class="card-title d-flex align-items-center gap-2"><span class="badge" style="background:var(--color-pink)">New</span> Recent Reservations</h5>
                <div class="table-responsive">
                  <table class="table table-borderless table-sm">
                    <thead>
                      <tr>
                        <th scope="col">#</th>
                        <th scope="col">Reservee</th>
                        <th scope="col">Facility</th>
                        <th scope="col">Amount</th>
                      </tr>
                    </thead>
                    <tbody id="recentReservationsTbody"></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div class="content-page" id="analytics">
        <div class="row g-4">
          <div class="col-lg-6">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title mb-0">Status Distribution</h5>
                <div class="chart-fixed position-relative">
                  <canvas id="statusChart" class="w-100 h-100"></canvas>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title mb-0">Top Facilities (Bookings)</h5>
                <div class="chart-fixed position-relative">
                  <canvas id="topFacilitiesChart" class="w-100 h-100"></canvas>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title mb-0">Occupancy Trend</h5>
                <div class="chart-fixed tall position-relative">
                  <canvas id="occupancyChart" class="w-100 h-100"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="content-page" id="page-reservations">
        <div class="d-flex justify-content-end mb-2">
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshReservationsCharts()" data-bs-toggle="tooltip" data-bs-placement="top" title="Reload reservation charts"><i class="bi bi-arrow-clockwise"></i> Refresh Charts</button>
        </div>
        
        <div class="card">
          <div class="card-body">
            <h5 class="card-title" id="reservations">All Reservations</h5>
            <div class="d-flex justify-content-end gap-2 mb-3">
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="openCreateReservation()" data-bs-toggle="tooltip" data-bs-placement="top" title="Add a reservation manually"><i class="bi bi-pencil-square"></i> Manual (OTC)</button>
              <a href="walkin_guest.php" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Open map to book like customers"><i class="bi bi-geo-alt"></i> Walk-in (via Map)</a>
            </div>
            <div class="table-responsive">
              <table class="table table-borderless" id="reservationsTable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Reservee</th>
                    <th>Facility</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Amount</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="row g-4 mt-1">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 class="card-title mb-0">Pending Cash Receipts</h5>
                </div>
                <div class="table-responsive">
                  <table class="table table-borderless align-middle" id="pendingReceiptsTable">
                    <thead>
                      <tr>
                        <th>TX</th>
                        <th>Reservee</th>
                        <th>Facility</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Payment</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4 mt-1">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 class="card-title mb-0">Cancellation Requests</h5>
                </div>
                <div class="table-responsive">
                  <table class="table table-borderless align-middle" id="cancellationRequestsTable">
                    <thead>
                      <tr>
                        <th>Req ID</th>
                        <th>TX</th>
                        <th>Reservee</th>
                        <th>Facility</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Requested</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="content-page" id="page-customers">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <h5 id="customers" class="card-title mb-0">Customers</h5>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="filterBanned" onchange="loadCustomers()">
                <label class="form-check-label" for="filterBanned">Show banned only</label>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-borderless" id="customersTable">
                <thead>
                  <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    <th>Address</th>
                    <th>Date Joined</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      

      <div class="content-page" id="page-facilities">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0" id="facilities">Facilities</h5>
          <a href="facility_mapping.php" class="btn btn-primary btn-sm"><i class="bi bi-geo-alt"></i> Open Mapping</a>
        </div>
        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-borderless" id="facilitiesTable">
                <thead>
                  <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Details</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Date Added</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="content-page" id="page-inquiries">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="card-title mb-0">Inquiries</h5>
              <div class="d-flex align-items-center gap-2">
                <select id="inqFilter" class="form-select form-select-sm" style="width: 180px;">
                  <option value="">All</option>
                  <option value="new">New</option>
                  <option value="read">Read</option>
                </select>
                <button class="btn btn-sm btn-outline-primary" onclick="loadInquiriesTable()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-borderless" id="inquiriesTable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      

      <div class="content-page" id="page-feedback">
        <div class="card">
          <div id="feedback" class="card-body">
            <h5 class="card-title">Feedback</h5>
            <div class="d-flex justify-content-end mb-2">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="filterHidden" onchange="loadFeedback()">
                <label class="form-check-label" for="filterHidden">Show hidden only</label>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-borderless" id="feedbackTable">
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Facility</th>
                    <th>Rating</th>
                    <th>Feedback</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="content-page" id="page-gallery">
        <div class="card">
          <div id="gallery" class="card-body">
            <h5 class="card-title">Gallery</h5>
            <form id="galleryForm" class="row g-2 align-items-end">
              <div class="col-md-6">
                <input type="text" id="galDesc" class="form-control" placeholder="Description">
              </div>
              <div class="col-md-4">
                <input type="file" id="galImage" class="form-control" accept="image/*">
              </div>
              <div class="col-md-2">
                <button type="button" class="btn btn-primary w-100" onclick="addGallery()"><i class="bi bi-plus-circle"></i> Add</button>
              </div>
            </form>
            <div class="table-responsive mt-3">
              <table class="table table-borderless" id="galleryTable">
                <thead>
                  <tr>
                    <th>Image</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; <strong><span>Shelton Beach Resort</span></strong> All Rights Reserved
    </div>
  </footer>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="../template/assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../template/assets/vendor/chart.js/chart.umd.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../js/notify.js?v=<?php echo filemtime(__DIR__ . '/../js/notify.js'); ?>"></script>
  <!-- DataTables JS -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

  <!-- Template Main JS File -->
  <script src="../template/assets/js/main.js"></script>
  <script>
    // Replace native alert with themed Swal dialog for consistency
    (function(){ try{ if(window.Swal && !window.__sbhAlertWrapped){ window.__sbhAlertWrapped=true; const native=window.alert; window.alert=function(m){ try{ Swal.fire({icon:'warning',title:String(m||''),confirmButtonText:'OK'});}catch(e){ try{ native.call(window,m);}catch(_){} } }; } }catch(e){} })();

    window.__csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>';
  </script>

  <script>
    let dashboardData = null;
    let salesChart = null;
    let dtReservations = null, dtFacilities = null, dtCustomers = null, dtFeedback = null;
    let resStatusChart = null, resTopFacilitiesChart = null, resOccupancyChart = null;
    const THEME = {
      aqua: getComputedStyle(document.documentElement).getPropertyValue('--color-aqua').trim() || '#7ab4a1',
      orange: getComputedStyle(document.documentElement).getPropertyValue('--color-orange').trim() || '#e08f5f',
      pink: getComputedStyle(document.documentElement).getPropertyValue('--color-pink').trim() || '#e19985'
    };

    document.addEventListener('DOMContentLoaded', () => {
      // Lock canvas CSS height and clear any inline height leaks
      const canvas = document.getElementById('salesChart');
      if (canvas) { canvas.removeAttribute('height'); canvas.removeAttribute('width'); canvas.style.height = '240px'; canvas.style.width = '100%'; }
      // Restore saved range
      const rangeSelect = document.getElementById('timeRangeSelect');
      if (rangeSelect) {
        const savedRange = localStorage.getItem('adm_time_range');
        if (savedRange) rangeSelect.value = savedRange;
      }
      ensureChartJsLoaded().then(() => fetch('api_dashboard_data.php'))
        .then(r => r.json())
        .then(data => {
          dashboardData = data || {};
          document.getElementById('statReservations').textContent = dashboardData.stats?.total_reservations ?? 0;
          document.getElementById('statFacilities').textContent = dashboardData.stats?.total_facilities ?? 0;
          document.getElementById('statCustomers').textContent = dashboardData.stats?.total_customers ?? 0;
          document.getElementById('statRevenue').textContent = '₱' + Number(dashboardData.stats?.total_revenue ?? 0).toLocaleString('en-PH');

          const tbody = document.getElementById('recentReservationsTbody');
          tbody.innerHTML = (dashboardData.recent_reservations||[]).map(r => `
            <tr>
              <td><a href="#" onclick="openReservation(${r.id}); return false;">#${r.id}</a></td>
              <td>${r.reservee||''}</td>
              <td>${r.facility_name||''}</td>
              <td>₱${Number(r.amount||0).toLocaleString('en-PH')}</td>
            </tr>
          `).join('');

          const initialRange = (document.getElementById('timeRangeSelect')?.value) || 'monthly';
          initSalesChart(initialRange);
          const trs = document.getElementById('timeRangeSelect');
          if (trs) trs.addEventListener('change', (e) => { localStorage.setItem('adm_time_range', e.target.value); initSalesChart(e.target.value); initAnalytics(e.target.value); });
          // Preload page data
          loadReservations();
          loadFacilities();
          loadCustomers();
          loadFeedback();
          loadGallery();
          initAnalytics(initialRange);
          afterDashboardLoaded(); // Call the new function here
        })
        .catch(err => console.error('Failed to load dashboard data', err));
      // Enable Bootstrap tooltips globally
      try {
        const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
      } catch (e) {}
      // Auto-refresh core charts every 2 minutes
      setInterval(() => {
        const curRange = (document.getElementById('timeRangeSelect')?.value) || 'monthly';
        initSalesChart(curRange);
        initAnalytics(curRange);
      }, 120000);

      // Sidebar toggle: bind once to avoid double-toggling with template main.js
      if (!window.__adm_sidebar_toggle_bound) {
        window.__adm_sidebar_toggle_bound = true;
        const toggle = document.querySelector('.toggle-sidebar-btn');
        if (toggle) {
          toggle.addEventListener('click', (e)=>{
            e.preventDefault();
            document.body.classList.toggle('toggle-sidebar');
          });
        }
      }

      // Route to correct page based on hash on first load
      const initialHash = (location.hash || '').replace('#','').trim();
      const validPages = ['main','dashboard','analytics','reservations','facilities','customers','feedback','gallery','settings','inquiries','activity'];
      if (initialHash && validPages.includes(initialHash)) {
        showPage(initialHash === 'main' ? 'dashboard' : initialHash);
      }
      // Keep sidebar active and content in sync when hash changes
      window.addEventListener('hashchange', ()=>{
        const h = (location.hash || '').replace('#','').trim();
        if (h && validPages.includes(h)) {
          showPage(h === 'main' ? 'dashboard' : h);
        }
      });

      function refreshPendingBadge(){
        fetch('pending_receipts_count.php?_='+Date.now()).then(r=>r.json()).then(j=>{
          const n = Number(j.count||0);
          const badge = document.getElementById('badgePending');
          if (!badge) return;
          if (n > 0) { badge.style.display='inline-block'; badge.textContent = n; }
          else { badge.style.display='none'; }
        }).catch(()=>{});
        // Inquiries badge
        fetch('inquiries_count.php?_='+Date.now()).then(r=>r.json()).then(j=>{
          const n = Number(j.count||0);
          const badge = document.getElementById('badgeInquiries');
          if (!badge) return;
          if (n > 0) { badge.style.display='inline-block'; badge.textContent = n; }
          else { badge.style.display='none'; }
        }).catch(()=>{});
        // Pending cancellations badge
        fetch('pending_cancellations_count.php?_='+Date.now()).then(r=>r.json()).then(j=>{
          const n = Number(j.count||0);
          const badge = document.getElementById('badgeCancels');
          if (!badge) return;
          if (n > 0) { badge.style.display='inline-block'; badge.textContent = n; }
          else { badge.style.display='none'; }
        }).catch(()=>{});
      }

      // Auto-refresh badge and pending receipts every 30s
      setInterval(()=>{ refreshPendingBadge(); try{ loadPendingReceipts(); }catch(e){} }, 30000);

      // First paint
      refreshPendingBadge();
    });

    function initSalesChart(range) {
      const canvas = document.getElementById('salesChart');
      if (!canvas) return;
      fetch('fetch_sales_chart.php?range=' + encodeURIComponent(range))
        .then(r => r.json())
        .then(j => {
          const labels = j.labels || [];
          const dataReg = j.registered || [];
          const dataWalk = j.walkin || [];
          const ctx = canvas.getContext('2d');
          if (salesChart) salesChart.destroy();
          salesChart = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets: [
              { label: 'Customers', data: dataReg, fill: true, backgroundColor: 'rgba(122, 180, 161, 0.15)', borderColor: getComputedStyle(document.documentElement).getPropertyValue('--color-aqua').trim() || '#7ab4a1', borderWidth: 2, tension: 0.35 },
              { label: 'Walk-ins', data: dataWalk, fill: true, backgroundColor: 'rgba(224, 143, 95, 0.15)', borderColor: getComputedStyle(document.documentElement).getPropertyValue('--color-orange').trim() || '#e08f5f', borderWidth: 2, tension: 0.35 }
            ] },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              layout: { padding: { top: 8, right: 8, bottom: 8, left: 8 } },
              plugins: { legend: { display: true } },
              scales: {
                y: { ticks: { callback: v => '₱' + Number(v).toLocaleString('en-PH') } },
                x: { ticks: { maxRotation: 0 } }
              }
            }
          });
          const sum = arr => (arr||[]).reduce((a,b)=>a+(Number(b)||0),0);
          const total = sum(dataReg) + sum(dataWalk);
          document.getElementById('totalSales').textContent = `Total Sales: ₱${total.toLocaleString('en-PH')}`;
        })
        .catch(err => console.error('Failed to load sales chart', err));
    }

    function initAnalytics(range){
      const r = range || (document.getElementById('timeRangeSelect')?.value) || 'monthly';
      // Top Facilities (template style). Supports both Canvas (Chart.js) and DIV (ApexCharts)
      const tfNode = document.getElementById('topFacilitiesChart');
      if (tfNode) {
        fetch('fetch_top_facilities.php').then(r=>r.json()).then(rows=>{
          const top = (rows||[]).slice(0, 5);
          const fullLabels = top.map(r=>r.name||'');
          const labels = fullLabels.map(s => s.length > 16 ? s.slice(0, 16) + '…' : s);
          const data = top.map(r=>Number(r.bookings)||0);
          if (tfNode.tagName === 'CANVAS') {
            new Chart(tfNode.getContext('2d'), {
              type: 'bar',
              data: { labels, datasets: [{ label: 'Bookings', data, backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--color-aqua').trim() || '#7ab4a1' }] },
              options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display:false }, tooltip: { callbacks: { title: (items)=> items && items.length ? fullLabels[items[0].dataIndex] : '' } } }, scales: { x: { beginAtZero: true } } }
            });
          } else if (window.ApexCharts) {
            const options = {
              series: [{ name: 'Bookings', data }],
              chart: { type: 'bar', height: 260, toolbar: { show: false } },
              plotOptions: { bar: { borderRadius: 6, horizontal: true, dataLabels: { position: 'right' } } },
              dataLabels: { enabled: true },
              xaxis: { categories: fullLabels },
              colors: [getComputedStyle(document.documentElement).getPropertyValue('--color-aqua').trim() || '#7ab4a1'],
              grid: { strokeDashArray: 4 }
            };
            new ApexCharts(tfNode, options).render();
          }
        });
      }

      // Status Distribution (doughnut)
      const sc = document.getElementById('statusChart');
      if (sc) {
        fetch('fetch_status_distribution.php').then(r=>r.json()).then(rows=>{
          const labels = (rows||[]).map(r=>String(r.status).toUpperCase());
          const data = (rows||[]).map(r=>Number(r.count)||0);
          const colors = [
            getComputedStyle(document.documentElement).getPropertyValue('--color-aqua').trim() || '#7ab4a1',
            getComputedStyle(document.documentElement).getPropertyValue('--color-orange').trim() || '#e08f5f',
            getComputedStyle(document.documentElement).getPropertyValue('--color-pink').trim() || '#e19985',
            '#0d6efd','#6c757d'
          ];
          if (sc.tagName === 'CANVAS') {
            new Chart(sc.getContext('2d'), {
              type: 'doughnut',
              data: { labels, datasets: [{ data, backgroundColor: colors.slice(0, data.length), borderWidth: 0 }] },
              options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
            });
          } else if (window.ApexCharts) {
            const options = { series: data, labels, chart: { type: 'donut', height: 260 }, colors: colors.slice(0, data.length), legend: { position: 'bottom' }, dataLabels: { enabled: true }, stroke: { width: 0 } };
            new ApexCharts(sc, options).render();
          }
        });
      }

      // Occupancy Trend (line)
      const oc = document.getElementById('occupancyChart');
      if (oc) {
        fetch('fetch_occupancy_trend.php?range=' + encodeURIComponent(r)).then(rsp=>rsp.json()).then(j=>{
          const labels = j.labels||[];
          const counts = j.counts||[];
          const percent = j.percent||[];
          new Chart(oc.getContext('2d'), {
            type: 'line',
            data: { labels, datasets: [
              { label: 'Occupied Facilities', data: counts, borderColor:getComputedStyle(document.documentElement).getPropertyValue('--color-aqua').trim() || '#7ab4a1', backgroundColor:'rgba(122,180,161,.15)', tension:.35, yAxisID:'y' },
              { label: 'Occupancy %', data: percent, borderColor:getComputedStyle(document.documentElement).getPropertyValue('--color-orange').trim() || '#e08f5f', backgroundColor:'rgba(224,143,95,.15)', tension:.35, yAxisID:'y1' }
            ]},
            options: { responsive:true, maintainAspectRatio:false, scales:{
              y: { beginAtZero:true, ticks:{ callback:(v)=>v } },
              y1: { position:'right', beginAtZero:true, ticks:{ callback:(v)=> v+'%' } }
            }, plugins:{ legend:{ position:'bottom' } } }
          });
        });
      }

      // Reservations page mini-charts (reuse analytics data)
      const rsc = document.getElementById('resStatusChart');
      if (rsc) {
        fetch('fetch_status_distribution.php').then(r=>r.json()).then(rows=>{
          const labels = (rows||[]).map(r=>String(r.status).toUpperCase());
          const data = (rows||[]).map(r=>Number(r.count)||0);
          const palette = [THEME.aqua, THEME.orange, THEME.pink, '#0d6efd', '#6c757d'];
          new Chart(rsc.getContext('2d'), { type:'doughnut', data:{ labels, datasets:[{ data, backgroundColor:palette.slice(0,data.length), borderWidth:0 }]}, options:{ responsive:true, maintainAspectRatio:false, cutout:'70%', plugins:{ legend:{ position:'bottom' } } } });
          removeSpinner(rsc);
        }).catch(()=>removeSpinner(rsc));
      }
      const rtf = document.getElementById('resTopFacilitiesChart');
      if (rtf) {
        fetch('fetch_top_facilities.php').then(r=>r.json()).then(rows=>{
          const top = (rows||[]).slice(0,5);
          const labels = top.map(r=>r.name||'');
          const data = top.map(r=>Number(r.bookings)||0);
          new Chart(rtf.getContext('2d'), { type:'bar', data:{ labels, datasets:[{ label:'Bookings', data, backgroundColor:getComputedStyle(document.documentElement).getPropertyValue('--color-aqua').trim() || '#7ab4a1' }]}, options:{ responsive:true, maintainAspectRatio:false, indexAxis:'y', plugins:{ legend:{ display:false } }, scales:{ x:{ beginAtZero:true } } } });
          removeSpinner(rtf);
        }).catch(()=>removeSpinner(rtf));
      }
      const roc = document.getElementById('resOccupancyChart');
      if (roc) {
        fetch('fetch_occupancy_trend.php?range=' + encodeURIComponent(r)).then(rsp=>rsp.json()).then(j=>{
          const labels = j.labels||[];
          const percent = j.percent||[];
          new Chart(roc.getContext('2d'), {
            type:'line',
            data:{ labels, datasets:[{ label:'Occupancy %', data:percent, borderColor:getComputedStyle(document.documentElement).getPropertyValue('--color-orange').trim() || '#e08f5f', backgroundColor:'rgba(224,143,95,.15)', tension:.35 }]},
            options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } }, scales:{ y:{ beginAtZero:true, ticks:{ callback:(v)=> v+'%' } } } }
          });
          removeSpinner(roc);
        }).catch(()=>removeSpinner(roc));
      }
    }

    function refreshReservationsCharts(){ const r = (document.getElementById('timeRangeSelect')?.value) || 'monthly'; initAnalytics(r); }
    function removeSpinner(canvas){ const wrap=canvas?.parentElement; const sp=wrap?.querySelector('.spinner-border'); if(sp) sp.remove(); }
    
    // Modal helpers to ensure proper lifecycle and cleanup
    function cleanupModalArtifacts(){
      try {
        document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
      } catch(e){}
    }
    function removeExistingModalById(id){
      const el = document.getElementById(id);
      if (!el) return;
      try { const inst = bootstrap.Modal.getInstance(el); if (inst) inst.hide(); } catch(e){}
      const wrapper = el.parentElement;
      try { el.remove(); } catch(e){}
      if (wrapper && wrapper.childElementCount === 0) { try { wrapper.remove(); } catch(e){} }
      cleanupModalArtifacts();
    }


    function ensureChartJsLoaded(){
      return new Promise((resolve) => {
        if (window.Chart) return resolve();
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
        s.onload = () => resolve();
        document.head.appendChild(s);
      });
    }

    function openWalkinMap(){ window.location.href = 'facility_mapping.php?walkin=1'; }

    function confirmLogout(){
      if (window.Swal) {
        Swal.fire({
          title: 'Logout?',
          text: 'Are you sure you want to end your admin session?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, logout',
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d'
        }).then(res => { if (res.isConfirmed) window.location.href = 'logout.php'; });
        return;
      }
      if (confirm('Are you sure you want to logout?')) { window.location.href = 'logout.php'; }
    }

    // UI helpers: breadcrumbs, alerts, badges
    const pageTitles = {
      dashboard: 'Admin Dashboard',
      analytics: 'Analytics',
      reservations: 'Reservations',
      facilities: 'Facilities',
      customers: 'Customers',
      feedback: 'Feedback',
      gallery: 'Gallery',
      inquiries: 'Inquiries',
      activity: 'Activity Log',
      settings: 'Settings'
    };

    function updateBreadcrumb(pageId) {
      const title = pageTitles[pageId] || 'Dashboard';
      const h1 = document.querySelector('.pagetitle h1');
      if (h1) h1.textContent = title;
      const ol = document.querySelector('.breadcrumb');
      if (ol) {
        ol.innerHTML = `
          <li class="breadcrumb-item"><a href="#" onclick="showPage('dashboard');return false;">Home</a></li>
          <li class="breadcrumb-item active">${title}</li>
        `;
      }
    }

    function showAlert(type, message) {
      // Prefer SweetAlert2 if available, fall back to bootstrap alerts
      if (window.Swal) {
        const map = { success: 'success', danger: 'error', warning: 'warning', info: 'info', primary: 'info', secondary: 'question' };
        const icon = map[type] || 'info';
        Swal.fire({ icon, title: message, timer: 3000, showConfirmButton: false, timerProgressBar: true, toast: true, position: 'top-end' });
        return;
      }
      const wrap = document.getElementById('alertPlaceholder');
      if (!wrap) return;
      const id = 'alert-' + Date.now();
      wrap.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="alert alert-${type} alert-dismissible fade show" role="alert">
          ${message}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      `);
      setTimeout(() => {
        const el = document.getElementById(id);
        if (el) {
          const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
          bsAlert.close();
        }
      }, 5000);
    }

    function statusBadgeClass(status){
      const s = String(status||'').toLowerCase();
      if (s === 'confirmed' || s === 'approved' || s === 'active') return 'bg-success';
      if (s === 'pending' || s === 'in review') return 'bg-warning text-dark';
      if (s === 'cancelled' || s === 'denied' || s === 'inactive') return 'bg-danger';
      return 'bg-secondary';
    }
    function statusBadgeHtml(status){
      const s = String(status||'').toLowerCase();
      const txt = String(status||'').toUpperCase();
      if (s === 'confirmed' || s === 'pending' || s === 'cancelled') {
        const color = s==='confirmed' ? THEME.aqua : (s==='pending' ? THEME.orange : THEME.pink);
        return `<span class="badge" style="background:${color};color:#fff">${txt}</span>`;
      }
      const cls = statusBadgeClass(status);
      return `<span class="badge ${cls}">${txt}</span>`;
    }

    function showPage(pageId) {
      // Switch content
      document.querySelectorAll('.content-page').forEach(p => p.classList.remove('active'));
      const el = document.getElementById('page-' + pageId);
      if (el) el.classList.add('active');
      // Update sidebar active state
      document.querySelectorAll('#sidebar .nav-link').forEach(a => {
        a.classList.add('collapsed');
        a.classList.remove('active');
      });
      const activeLink = document.getElementById('menu-' + pageId);
      if (activeLink) {
        activeLink.classList.remove('collapsed');
        activeLink.classList.add('active');
      }
      // Close sidebar on small screens for better UX
      if (window.innerWidth < 1200) {
        document.body.classList.remove('toggle-sidebar');
      }
      // Update page title and breadcrumbs
      updateBreadcrumb(pageId);
      // Load page-specific data on navigation
      try {
        if (pageId === 'inquiries') { loadInquiriesTable(); }
        if (pageId === 'activity') { loadActivityLog(); }
      } catch(e) { console.warn('Failed to load page data for', pageId, e); }
    }

    // Data loaders for pages
    function loadReservations() {
      // Filters removed; just init or refresh the server-side DataTable
      try { initOrRefreshReservationsDT(); } catch(e) { console.error('Failed to init reservations table', e); }
    }

    function resetReservationFilters(){ /* no-op: filters removed */ }

    function loadFacilities() {
      fetch('fetch_facilities.php').then(r=>r.json()).then(rows=>{
        const tbody = document.querySelector('#facilitiesTable tbody');
        tbody.innerHTML = (rows||[]).map(f=>`
          <tr>
            <td>${f.image?`<img src="${escapeAttr(resolveImagePath(f.image))}" alt="${escapeAttr(f.name)}" style="width:44px;height:44px;object-fit:cover;border-radius:6px;">`:''}</td>
            <td>${escapeHtml(f.name||'')}</td>
            <td>${escapeHtml(f.details||'')}</td>
            <td>₱${num(f.price)}</td>
            <td>${statusBadgeHtml(f.status)}</td>
            <td>${formatDate(f.date_added)}</td>
          </tr>`).join('');
        initOrRefreshDT('#facilitiesTable', [[5, 'desc']]);
      });
    }

    function loadCustomers() {
      const onlyBanned = document.getElementById('filterBanned')?.checked ? 1 : 0;
      fetch('fetch_customers.php?only_banned='+onlyBanned).then(r=>r.json()).then(rows=>{
        const tbody = document.querySelector('#customersTable tbody');
        tbody.innerHTML = (rows||[]).map(c=>{
          const banned = Number(c.is_banned||0)===1;
          const btn = banned
            ? `<button class="btn btn-sm btn-success" onclick="unbanUser(${c.id})"><i class=\"bi bi-unlock\"></i> Unban</button>`
            : `<button class="btn btn-sm btn-danger" onclick="banUser(${c.id})"><i class=\"bi bi-lock\"></i> Ban</button>`;
          return `
          <tr>
            <td>${escapeHtml(c.fullname||'')}</td>
            <td>${escapeHtml(c.email||'')}</td>
            <td>${escapeHtml(c.number||'')}</td>
            <td>${escapeHtml(c.gender||'')}</td>
            <td>${escapeHtml(c.address||'')}</td>
            <td>${formatDate(c.date_added)}</td>
            <td>${btn}</td>
          </tr>`;
        }).join('');
        initOrRefreshDT('#customersTable', [[5, 'desc']]);
      });
    }

    function loadFeedback() {
      const showHiddenOnly = document.getElementById('filterHidden')?.checked ? 1 : 0;
      const q = showHiddenOnly ? 'hidden=1' : 'visible=1';
      const tableEl = $('#feedbackTable');
      if ($.fn.dataTable.isDataTable(tableEl)) {
        tableEl.DataTable().destroy();
      }
      // Cache-bust and disable caching to ensure fresh results after toggle
      fetch('fetch_feedback.php?' + q + '&_=' + Date.now(), { cache: 'no-store' }).then(r=>r.json()).then(rows=>{
        const tbody = document.querySelector('#feedbackTable tbody');
        tbody.innerHTML = (rows||[]).map(f=>{
          const hidden = Number(f.is_hidden||0)===1;
          const btn = hidden
            ? `<button class="btn btn-sm btn-success" onclick="toggleFeedback(${f.id}, 'unhide', this)"><i class="bi bi-eye"></i> Unhide</button>`
            : `<button class="btn btn-sm btn-outline-secondary" onclick="toggleFeedback(${f.id}, 'hide', this)"><i class="bi bi-eye-slash"></i> Hide</button>`;
          return `
          <tr>
            <td>${escapeHtml(f.fullname||'')}</td>
            <td>${escapeHtml(f.facility_name||'')}</td>
            <td>${stars(f.rate)}</td>
            <td>${escapeHtml((f.feedback||'').slice(0,80))}${(f.feedback||'').length>80?'…':''}</td>
            <td>${formatDate(f.timestamp)}</td>
            <td>${btn}</td>
          </tr>`;
        }).join('');
        initOrRefreshDT('#feedbackTable', [[4, 'desc']]);
      });
    }
    function toggleFeedback(id, action, el){
      const btn = el || null;
      if (btn) { btn.disabled = true; btn.dataset.originalHtml = btn.innerHTML; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
      const fd = new FormData();
      fd.append('id', id);
      fd.append('action', action);
      fetch('hide_feedback.php', { method:'POST', body: fd })
        .then(r=>r.json())
        .then(j=>{
          if(!j || j.success !== true){ throw new Error(j && j.message ? j.message : 'Server error'); }
          // Reload feedback respecting the current toggle filter
          loadFeedback();
          showAlert('success', action==='hide'?'Feedback hidden':'Feedback visible');
        })
        .catch(err=>{
          showAlert('danger', err && err.message ? err.message : 'Failed to update feedback visibility');
        })
        .finally(()=>{ if (btn) { btn.disabled=false; btn.innerHTML = btn.dataset.originalHtml || 'Toggle'; delete btn.dataset.originalHtml; }});
    }

    // DataTable helper
    function initOrRefreshDT(selector, initialOrder){
      const el = $(selector);
      if ($.fn.dataTable.isDataTable(el)) {
        el.DataTable().destroy();
      }
      el.DataTable({
        responsive: { details: { type: 'inline', target: 'tr' } },
        autoWidth: false,
        pageLength: 10,
        pagingType: 'simple_numbers',
        lengthMenu: [10, 25, 50, 100],
        dom: '<"d-flex justify-content-between align-items-center mb-2"Bfl>rt<"d-flex justify-content-between align-items-center"ip>',
        buttons: [
          {extend:'copy', className:'btn btn-sm btn-theme-aqua'},
          {extend:'excel', className:'btn btn-sm btn-theme-orange'},
          {extend:'csv', className:'btn btn-sm btn-theme-pink'}
        ],
        language: { search: "", searchPlaceholder: "Search...", paginate: { first: '<i class="bi bi-chevron-double-left"></i>', last: '<i class="bi bi-chevron-double-right"></i>', next: '<i class="bi bi-chevron-right"></i>', previous: '<i class="bi bi-chevron-left"></i>' } },
        order: initialOrder || []
      });
      el.on('draw.dt', () => {
        try {
          const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
          tooltipTriggerList.forEach(t => new bootstrap.Tooltip(t));
        } catch(e) {}
      });
    }

    function initOrRefreshReservationsDT(){
      const el = $('#reservationsTable');
      if ($.fn.dataTable.isDataTable(el)) {
        el.DataTable().ajax.reload();
        return;
      }
      const params = () => ({});
      el.DataTable({
        processing: true,
        serverSide: true,
        ajax: function(data, callback){
          const q = new URLSearchParams({
            draw: data.draw,
            start: data.start,
            length: data.length,
            'search[value]': data.search.value || '',
            'order[0][column]': data.order?.[0]?.column ?? 0,
            'order[0][dir]': data.order?.[0]?.dir ?? 'desc',
            ...params()
          }).toString();
          fetch('datatables_reservations.php?'+q).then(r=>r.json()).then(callback);
        },
        responsive: { details: { type: 'inline', target: 'tr' } },
        autoWidth: false,
        pageLength: 10,
        pagingType: 'simple_numbers',
        lengthMenu: [10, 25, 50, 100],
        dom: '<"d-flex justify-content-between align-items-center mb-2"Bfl>rt<"d-flex justify-content-between align-items-center"ip>',
        buttons: [
          {extend:'copy', className:'btn btn-sm btn-theme-aqua'},
          {extend:'excel', className:'btn btn-sm btn-theme-orange'},
          {extend:'csv', className:'btn btn-sm btn-theme-pink'}
        ],
        language: { search: "", searchPlaceholder: "Search..." },
        order: [[3, 'desc']],
        columns: [
          { title: 'ID' },
          { title: 'Reservee' },
          { title: 'Facility' },
          { title: 'Check-in' },
          { title: 'Check-out' },
          { title: 'Status', render: (d)=> statusBadgeHtml(d) },
          { title: 'Amount', render: (d)=>'₱'+num(d) }
        ]
      });
      // Inline status changes removed; pending items are handled via Pending Cash Receipts
    }

    function loadInquiriesTable(){
      const el = $('#inquiriesTable');
      if ($.fn.dataTable.isDataTable(el)) {
        el.DataTable().destroy();
      }
      const status = document.getElementById('inqFilter')?.value || '';
      fetch('fetch_inquiries.php' + (status ? ('?status=' + encodeURIComponent(status)) : ''))
        .then(r=>r.json()).then(j=>{
          const rows = j.inquiries || [];
          const tbody = document.querySelector('#inquiriesTable tbody');
          tbody.innerHTML = rows.map(q=>{
            const act = `
              <div class="btn-group btn-group-sm">
                ${q.status==='read' ? `<button class="btn btn-outline-primary" onclick="updateInquiry(${q.id},'unread')">Unread</button>` : `<button class="btn btn-primary" onclick="updateInquiry(${q.id},'read')">Read</button>`}
                <button class="btn btn-outline-danger" onclick="updateInquiry(${q.id},'delete')">Delete</button>
              </div>`;
            return `
              <tr>
                <td>${q.id}</td>
                <td>${escapeHtml(q.name||'')}</td>
                <td><a href="mailto:${escapeAttr(q.email||'')}">${escapeHtml(q.email||'')}</a></td>
                <td>${escapeHtml((q.message||'').slice(0,80))}${(q.message||'').length>80?'…':''}</td>
                <td>${q.status==='read' ? '<span class="badge bg-secondary">Read</span>' : '<span class="badge bg-success">New</span>'}</td>
                <td>${formatDate(q.created_at)}</td>
                <td>${act}</td>
              </tr>`;
          }).join('');
          initOrRefreshDT('#inquiriesTable', [[5,'desc']]);
        });
    }
    function updateInquiry(id, action){
      const fd = new FormData(); fd.append('id', id); fd.append('action', action);
      fetch('update_inquiry.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(j=>{ if(!j.success) throw 0; loadInquiriesTable(); refreshPendingBadge(); showAlert('success','Updated'); })
        .catch(()=>showAlert('danger','Failed to update inquiry'));
    }


    // Reservation details modal with notes timeline
    function openReservation(id){
      removeExistingModalById('resModal');
      fetch('get_reservation.php?id='+encodeURIComponent(id)).then(r=>r.json()).then(j=>{
        if(!j.success) throw new Error('Not found');
        const r = j.reservation; const notes = j.notes||[];
        const notesHtml = notes.map(n=>`
          <div class="border rounded p-2 mb-2">
            <div class="small text-muted">${escapeHtml(n.created_at||'')}</div>
            <div><strong>${escapeHtml(n.action||'')}</strong> - ${escapeHtml(n.note||'')}</div>
          </div>`).join('') || '<div class="text-muted">No notes yet</div>';
        const modal = document.createElement('div');
        modal.innerHTML = `
          <div class="modal fade" id="resModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Reservation #${r.id}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div><strong>Reservee:</strong> ${escapeHtml(r.reservee||'')}</div>
                      <div><strong>Facility:</strong> ${escapeHtml(r.facility_name||'')}</div>
                      <div><strong>Status:</strong> ${escapeHtml(r.status||'')}</div>
                      <div><strong>Check-in:</strong> ${formatDate(r.date_start)}</div>
                      <div><strong>Check-out:</strong> ${formatDate(r.date_end)}</div>
                      <div><strong>Amount:</strong> ₱${num(r.amount)}</div>
                      <div><strong>Payment:</strong> ${escapeHtml(r.payment_type||'')}</div>
                    </div>
                    <div class="col-md-6">
                      <h6>Notes</h6>
                      <div id="notesWrap">${notesHtml}</div>
                      <div class="input-group mt-2">
                        <input id="noteInput" type="text" class="form-control" placeholder="Add a note...">
                        <button class="btn btn-outline-primary" onclick="addNote(${r.id})">Add</button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-success" onclick="approveReservation(${r.id})">Approve</button>
                  <button class="btn btn-danger" onclick="denyReservation(${r.id})">Deny</button>
                </div>
              </div>
            </div>
          </div>`;
        document.body.appendChild(modal);
        const m = new bootstrap.Modal(document.getElementById('resModal'));
        m.show();
        document.getElementById('resModal').addEventListener('hidden.bs.modal', ()=>modal.remove());
      }).catch(()=>alert('Failed to load reservation'));
    }
    function addNote(id){
      const input = document.getElementById('noteInput');
      const val = (input?.value||'').trim(); if (!val) return;
      const fd = new FormData(); fd.append('id', id); fd.append('note', val);
      fetch('add_reservation_note.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(j=>{ if(!j.success) throw new Error('Failed'); input.value=''; openReservation(id); showAlert('success','Note added'); })
        .catch(()=>showAlert('danger','Failed to add note'));
    }

    // Utils
    function num(n){ return Number(n||0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    function formatDate(d){ if(!d) return ''; const t = new Date(d); return isNaN(t)?'':t.toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'}); }
    function escapeHtml(s){ return (s+"").replace(/[&<>"]+/g, m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[m])); }
    function escapeAttr(s){ return escapeHtml(s).replace(/'/g,'&#39;'); }
    function stars(r){ r=Number(r)||0; let out=''; for(let i=1;i<=5;i++){ out+= i<=r?'<i class="bi bi-star-fill text-warning"></i>':'<i class="bi bi-star text-secondary"></i>'; } return out; }
    function resolveImagePath(p){
      if(!p) return '';
      const s=String(p);
      if(s.startsWith('http')||s.startsWith('/')) return s;
      if(s.startsWith('admindash2/')) return '../'+s; // legacy fallback
      if(s.startsWith('admindash/')) return '../'+s;
      if(s.startsWith('images/')) return '../admindash/'+s;
      if(s.startsWith('uploads/')) return '../'+s;
      if(!s.includes('/')) return '../uploads/gallery/'+s; // bare filename -> gallery upload
      return '../'+s;
    }
    function banUser(id){ const fd=new FormData(); fd.append('id', id); fetch('ban_user.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{ if(!j.success) throw 0; loadCustomers(); showAlert('success','User banned');}).catch(()=>showAlert('danger','Failed to ban')); }
    function unbanUser(id){ const fd=new FormData(); fd.append('id', id); fetch('unban_user.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{ if(!j.success) throw 0; loadCustomers(); showAlert('success','User unbanned');}).catch(()=>showAlert('danger','Failed to unban')); }

    // Calendar removed per request

    // Gallery
    function loadGallery(){
      fetch('fetch_gallery.php').then(r=>r.json()).then(rows=>{
        const tbody = document.querySelector('#galleryTable tbody');
        tbody.innerHTML = (rows||[]).map(g=>`
          <tr>
            <td>${g.location?`<img src="${escapeAttr(resolveImagePath(g.location))}" alt="" style="width:72px;height:56px;object-fit:cover;border-radius:6px;">`:''}</td>
            <td>${escapeHtml(g.description||'')}</td>
            <td>${formatDate(g.date_added)}</td>
            <td>
              <button class="btn btn-sm btn-outline-secondary me-1" onclick="editGalleryDescription(this)" data-id="${g.id}" data-desc="${escapeAttr(g.description||'')}" data-loc="${escapeAttr(resolveImagePath(g.location))}"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteGallery(${g.id})"><i class="bi bi-trash"></i></button>
            </td>
          </tr>`).join('');
        initOrRefreshDT('#galleryTable', [[2,'desc']]);
      });
    }
    function addGallery(){
      const desc = document.getElementById('galDesc').value.trim();
      const file = document.getElementById('galImage').files[0];
      if (!desc || !file) { alert('Provide description and image'); return; }
      const fd = new FormData(); if (window.__csrfToken) fd.append('csrf_token', window.__csrfToken); fd.append('description', desc); fd.append('image', file);
      fetch('gallery_add.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(j=>{ if(!j.success) throw 0; document.getElementById('galDesc').value=''; document.getElementById('galImage').value=''; loadGallery(); showAlert('success','Image added'); })
        .catch(()=>showAlert('danger','Failed to add image'));
    }
    function deleteGallery(id){
      if (window.Swal) {
        Swal.fire({ icon:'warning', title:'Delete this image?', showCancelButton:true, confirmButtonText:'Delete', confirmButtonColor:'#d33' }).then(res=>{
          if (!res.isConfirmed) return;
          const fd = new FormData(); fd.append('id', id);
          fetch('gallery_delete.php', { method:'POST', body: fd })
            .then(r=>r.json()).then(j=>{ if(!j.success) throw 0; loadGallery(); showAlert('success','Image deleted'); })
            .catch(()=>showAlert('danger','Failed to delete image'));
        });
        return;
      }
      if (!confirm('Delete this image?')) return;
      const fd = new FormData(); fd.append('id', id);
      fetch('gallery_delete.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(j=>{ if(!j.success) throw 0; loadGallery(); showAlert('success','Image deleted'); })
        .catch(()=>showAlert('danger','Failed to delete image'));
    }
    function editGalleryDescription(btn){
      let id = 0, curDesc = '', curLoc = '';
      if (btn && btn.dataset) {
        id = Number(btn.dataset.id||0);
        curDesc = btn.dataset.desc||'';
        curLoc = btn.dataset.loc||'';
      } else {
        id = Number(btn)||0;
      }
      if (!id) return;
      removeExistingModalById('editGalleryModal');
      const modal = document.createElement('div');
      modal.innerHTML = `
      <div class="modal fade" id="editGalleryModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Gallery Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
              <div class="mb-2 text-center">${curLoc?`<img src="${curLoc}" alt="preview" style="max-width:100%;max-height:180px;object-fit:cover;border-radius:6px;">`:''}</div>
              <div class="mb-2">
                <label class="form-label">Description</label>
                <textarea id="eg_desc" class="form-control" rows="3" placeholder="Enter description">${escapeHtml(curDesc)}</textarea>
              </div>
              <div class="mb-2">
                <label class="form-label">Replace Image (optional)</label>
                <input id="eg_file" type="file" class="form-control" accept="image/*">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" id="eg_save">Save</button>
            </div>
          </div>
        </div>
      </div>`;
      document.body.appendChild(modal);
      const m = new bootstrap.Modal(document.getElementById('editGalleryModal'));
      m.show();
      document.getElementById('eg_save').addEventListener('click', ()=>{
        const desc = (document.getElementById('eg_desc').value||'').trim();
        const file = document.getElementById('eg_file').files[0];
        const fd = new FormData(); if (window.__csrfToken) fd.append('csrf_token', window.__csrfToken);
        fd.append('id', String(id));
        if (desc !== '') fd.append('description', desc);
        if (file) fd.append('image', file);
        fetch('gallery_update.php', { method:'POST', body: fd })
          .then(r=>r.json()).then(j=>{
            if(!j || j.success !== true) throw new Error('Update failed');
            m.hide(); loadGallery(); showAlert('success','Gallery updated');
          })
          .catch(()=>showAlert('danger','Failed to update gallery'));
      });
      document.getElementById('editGalleryModal').addEventListener('hidden.bs.modal', ()=>modal.remove());
    }

    // OTC Reservation Modal
    function openCreateReservation(){
      removeExistingModalById('createResModal');
      const modal = document.createElement('div');
      modal.innerHTML = `
      <div class="modal fade" id="createResModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Reservation (OTC)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
              <div class="mb-2"><label class="form-label">Reservee</label><input id="cr_reservee" class="form-control" placeholder="Full name"></div>
              <div class="mb-2"><label class="form-label">Facility</label><input id="cr_facility" class="form-control" placeholder="Facility name"></div>
              <div class="mb-2"><label class="form-label">Status</label>
                <select id="cr_status" class="form-select">
                  <option value="pending">Pending</option>
                  <option value="confirmed">Confirmed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>
              <div class="row g-2">
                <div class="col">
                  <label class="form-label">Check-in</label>
                  <input type="datetime-local" id="cr_start" class="form-control">
                </div>
                <div class="col">
                  <label class="form-label">Check-out</label>
                  <input type="datetime-local" id="cr_end" class="form-control">
                </div>
              </div>
              <div class="row g-2 mt-2">
                <div class="col">
                  <label class="form-label">Payment Type</label>
                  <select id="cr_payment" class="form-select">
                    <option value="cash">Cash</option>
                    <option value="gcash">GCash</option>
                    <option value="card">Card</option>
                  </select>
                </div>
                <div class="col">
                  <label class="form-label">Amount</label>
                  <input type="number" step="0.01" id="cr_amount" class="form-control" placeholder="0.00">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-primary" onclick="submitCreateReservation()">Save</button>
            </div>
          </div>
        </div>
      </div>`;
      document.body.appendChild(modal);
      const m = new bootstrap.Modal(document.getElementById('createResModal'));
      m.show();
      document.getElementById('createResModal').addEventListener('hidden.bs.modal', ()=>modal.remove());
    }
    function submitCreateReservation(){
      const fd = new FormData(); if (window.__csrfToken) fd.append('csrf_token', window.__csrfToken);
      fd.append('reservee', (document.getElementById('cr_reservee').value||'').trim());
      fd.append('facility_name', (document.getElementById('cr_facility').value||'').trim());
      fd.append('status', (document.getElementById('cr_status').value||'').trim());
      fd.append('date_start', document.getElementById('cr_start').value||'');
      fd.append('date_end', document.getElementById('cr_end').value||'');
      fd.append('payment_type', (document.getElementById('cr_payment').value||'').trim());
      fd.append('amount', document.getElementById('cr_amount').value||'0');
      fetch('create_reservation.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(j=>{ if(!j.success) throw new Error(j.message||'Failed');
          const el = document.getElementById('createResModal'); if (el) bootstrap.Modal.getInstance(el).hide();
          initOrRefreshReservationsDT(); showAlert('success','Reservation added');
        })
        .catch(err=>showAlert('danger', err.message||'Failed to add reservation'));
    }

    function loadPendingReceipts(){
      const table = document.querySelector('#pendingReceiptsTable tbody');
      if (!table) return; // reservations page not active
      fetch('fetch_pending_receipts.php').then(r=>r.json()).then(j=>{
        const rows = j.rows||[];
        const tbody = table;
        tbody.innerHTML = rows.map(r=>{
          const tx = r.transaction_id||'';
          const paid = Number(r.amount_paid||0);
          const bal = Number(r.balance||0);
          const btnFull = `<button class="btn btn-sm btn-success me-1" onclick="confirmReceipt('${tx}', ${bal})"><i class=\"bi bi-check2-circle\"></i> Confirm</button>`;
          const btnPartial = `<button class="btn btn-sm btn-outline-primary" onclick="partialConfirmReceipt('${tx}', ${bal})"><i class=\"bi bi-percent\"></i> Partial</button>`;
          return `
            <tr>
              <td>${tx}</td>
              <td>${escapeHtml(r.reservee||'')}</td>
              <td>${escapeHtml(r.facility_name||'')}</td>
              <td>${formatDate(r.date_checkin)}</td>
              <td>${formatDate(r.date_checkout)}</td>
              <td>${escapeHtml(r.payment_type||'')}</td>
              <td>₱${num(paid)}</td>
              <td><span class="badge bg-warning text-dark">₱${num(bal)}</span></td>
              <td>${btnFull}${btnPartial}</td>
            </tr>`;
        }).join('');
        initOrRefreshDT('#pendingReceiptsTable', [[3,'desc']]);
      });
    }

    function loadCancellationRequests(){
      const table = document.querySelector('#cancellationRequestsTable tbody');
      if (!table) return; // reservations page not active
      fetch('fetch_cancellation_requests.php').then(r=>r.json()).then(j=>{
        const rows = j.rows||[];
        const tbody = table;
        tbody.innerHTML = rows.map(r=>{
          const tx = r.transaction_id||'';
          const paid = Number(r.amount_paid||0);
          const bal = Number(r.balance||0);
          const reqId = Number(r.req_id||0);
          const btnApprove = `<button class="btn btn-sm btn-danger me-1" onclick="approveCancellation(${reqId}, '${tx}')"><i class=\"bi bi-x-circle\"></i> Approve</button>`;
          const btnDeny = `<button class=\"btn btn-sm btn-outline-secondary\" onclick=\"denyCancellation(${reqId})\"><i class=\"bi bi-slash-circle\"></i> Deny</button>`;
          return `
            <tr>
              <td>${reqId}</td>
              <td>${tx}</td>
              <td>${escapeHtml(r.reservee||'')}</td>
              <td>${escapeHtml(r.facility_name||'')}</td>
              <td>${formatDate(r.date_checkin)}</td>
              <td>${formatDate(r.date_checkout)}</td>
              <td>₱${num(paid)}</td>
              <td><span class="badge bg-warning text-dark">₱${num(bal)}</span></td>
              <td>${escapeHtml(r.created_at||'')}</td>
              <td>${btnApprove}${btnDeny}</td>
            </tr>`;
        }).join('');
        // Add export buttons like other tables
        const el = $('#cancellationRequestsTable');
        if ($.fn.dataTable.isDataTable(el)) {
          el.DataTable().destroy();
        }
        el.DataTable({
          responsive: { details: { type: 'inline', target: 'tr' } },
          autoWidth: false,
          pageLength: 10,
          pagingType: 'simple_numbers',
          lengthMenu: [10, 25, 50, 100],
          dom: '<"d-flex justify-content-between align-items-center mb-2"Bfl>rt<"d-flex justify-content-between align-items-center"ip>',
          buttons: [
            {extend:'copy', className:'btn btn-sm btn-theme-aqua'},
            {extend:'excel', className:'btn btn-sm btn-theme-orange'},
            {extend:'csv', className:'btn btn-sm btn-theme-pink'}
          ],
          language: { search: "", searchPlaceholder: "Search..." },
          order: [[8,'desc']]
        });
      });
    }

    function approveCancellation(id, tx){
      const doSubmit = ()=>{
        const fd = new FormData();
        fd.append('id', String(id));
        fd.append('action', 'approve');
        fetch('handle_cancellation.php', { method:'POST', body: fd })
          .then(r=>r.json()).then(j=>{ if(!j.success) throw 0; loadCancellationRequests(); initOrRefreshReservationsDT(); showAlert('success','Cancellation approved (no refund)'); })
          .catch(()=>showAlert('danger','Failed to approve cancellation'));
      };
      if (window.Swal) {
        Swal.fire({
          icon:'warning',
          title:'Approve cancellation?',
          html:`Transaction <strong>${escapeHtml(tx||'')}</strong><br>No refund will be issued.`,
          showCancelButton:true,
          confirmButtonText:'Approve',
          confirmButtonColor:'#d33'
        }).then(res=>{ if(res.isConfirmed) doSubmit(); });
        return;
      }
      if (confirm('Approve this cancellation? No refund will be issued.')) doSubmit();
    }

    function denyCancellation(id){
      const fd = new FormData();
      fd.append('id', String(id));
      fd.append('action', 'deny');
      fetch('handle_cancellation.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(j=>{ if(!j.success) throw 0; loadCancellationRequests(); showAlert('success','Request denied'); })
        .catch(()=>showAlert('danger','Failed to deny request'));
    }

    // Pending Receipts Modal
    function openPendingReceiptsModal(){
      removeExistingModalById('pendingReceiptsModal');
      const modal = document.createElement('div');
      modal.innerHTML = `
      <div class="modal fade" id="pendingReceiptsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Pending Cash Receipts</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <table class="table table-sm align-middle" id="pendingReceiptsTableModal">
                  <thead>
                    <tr>
                      <th>TX</th>
                      <th>Reservee</th>
                      <th>Facility</th>
                      <th>Check-in</th>
                      <th>Check-out</th>
                      <th>Payment</th>
                      <th>Paid</th>
                      <th>Balance</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="button" class="btn btn-outline-primary" id="refreshReceiptsBtn">Refresh</button>
            </div>
          </div>
        </div>
      </div>`;
      document.body.appendChild(modal);
      const m = new bootstrap.Modal(document.getElementById('pendingReceiptsModal'));
      m.show();
      const tbodySel = '#pendingReceiptsTableModal tbody';
      function fillModal(){
        fetch('fetch_pending_receipts.php').then(r=>r.json()).then(j=>{
          const rows = j.rows||[];
          const tbody = document.querySelector(tbodySel);
          tbody.innerHTML = rows.map(r=>{
            const tx = r.transaction_id||'';
            const paid = Number(r.amount_paid||0);
            const bal = Number(r.balance||0);
            const btnFull = `<button class="btn btn-sm btn-success me-1" onclick="confirmReceipt('${tx}', ${bal}); setTimeout(fillModal, 400);"><i class=\"bi bi-check2-circle\"></i></button>`;
            const btnPartial = `<button class="btn btn-sm btn-outline-primary" onclick="partialConfirmReceipt('${tx}', ${bal}); setTimeout(fillModal, 400);"><i class=\"bi bi-percent\"></i></button>`;
            return `
              <tr>
                <td>${tx}</td>
                <td>${escapeHtml(r.reservee||'')}</td>
                <td>${escapeHtml(r.facility_name||'')}</td>
                <td>${formatDate(r.date_checkin)}</td>
                <td>${formatDate(r.date_checkout)}</td>
                <td>${escapeHtml(r.payment_type||'')}</td>
                <td>₱${num(paid)}</td>
                <td><span class="badge bg-warning text-dark">₱${num(bal)}</span></td>
                <td>${btnFull}${btnPartial}</td>
              </tr>`;
          }).join('');
        });
      }
      fillModal();
      document.getElementById('refreshReceiptsBtn').addEventListener('click', fillModal);
      document.getElementById('pendingReceiptsModal').addEventListener('hidden.bs.modal', ()=> modal.remove());
    }

    function confirmReceipt(tx, maxBalance){
      if (!tx) return;
      const fd = new FormData(); fd.append('transaction_id', tx); fd.append('amount_paid', maxBalance);
      fetch('confirm_receipt.php', { method:'POST', body: fd })
        .then(r=>r.json()).then(j=>{ if(!j.success) throw 0; loadPendingReceipts(); showAlert('success','Receipt confirmed'); })
        .catch(()=>showAlert('danger','Failed to confirm receipt'));
    }

    function partialConfirmReceipt(tx, maxBalance){
      if (!tx) return;
      removeExistingModalById('partialReceiptModal');
      const wrapper = document.createElement('div');
      wrapper.innerHTML = `
      <div class="modal fade" id="partialReceiptModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Partial Payment</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-2"><strong>Transaction:</strong> ${tx}</div>
              <div class="mb-3"><strong>Current Balance:</strong> ₱${num(maxBalance)}</div>
              <div class="mb-2">
                <label for="partialAmount" class="form-label">Amount received</label>
                <input type="number" class="form-control" id="partialAmount" min="0.01" step="0.01" max="${String(maxBalance)}" value="${String(maxBalance)}" placeholder="0.00">
                <div class="form-text">Must be greater than 0 and not exceed the current balance.</div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" id="partialConfirmBtn">Confirm</button>
            </div>
          </div>
        </div>
      </div>`;
      document.body.appendChild(wrapper);
      const modalEl = document.getElementById('partialReceiptModal');
      const m = new bootstrap.Modal(modalEl);
      m.show();
      const submit = () => {
        const val = document.getElementById('partialAmount')?.value || '0';
        const amt = parseFloat(val);
        if (!isFinite(amt) || amt <= 0) { showAlert('warning','Invalid amount'); return; }
        if (amt > maxBalance) { showAlert('warning','Amount exceeds balance'); return; }
        const fd = new FormData(); fd.append('transaction_id', tx); fd.append('amount_paid', String(amt));
        fetch('confirm_receipt.php', { method:'POST', body: fd })
          .then(r=>r.json()).then(j=>{ if(!j.success) throw 0; m.hide(); loadPendingReceipts(); showAlert('success','Receipt updated'); })
          .catch(()=>showAlert('danger','Failed to update receipt'));
      };
      document.getElementById('partialConfirmBtn').addEventListener('click', submit);
      modalEl.addEventListener('shown.bs.modal', ()=>{ document.getElementById('partialAmount')?.focus(); });
      modalEl.addEventListener('hidden.bs.modal', ()=> wrapper.remove());
      document.getElementById('partialAmount').addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ submit(); } });
    }

    // After dashboard data loads
    function afterDashboardLoaded(){
      loadPendingReceipts();
      loadCancellationRequests();
      // Refresh pending receipts table and count every 30 seconds
      setInterval(() => {
        loadPendingReceipts();
        loadCancellationRequests();
      }, 30000);
      // Hook inquiries filter if present and do initial load once
      const inqFilter = document.getElementById('inqFilter');
      if (inqFilter && !inqFilter.__bound) {
        inqFilter.addEventListener('change', () => loadInquiriesTable());
        inqFilter.__bound = true;
      }
      // Preload inquiries silently if the page is visible
      if (document.getElementById('page-inquiries')?.classList.contains('active')) {
        loadInquiriesTable();
      }
    }
  </script>

</body>
</html>


