<?php
require __DIR__ . '/_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Activity Log - Shelton Admin</title>
  <link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../template/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../template/assets/css/style.css" rel="stylesheet">
  <link href="../css/theme-overrides.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
  <style>
    #main .table td,#main .table th{padding:.5rem .5rem}
    .filters-bar{position:sticky;top:64px;background:#fff;border:1px solid #eee;border-radius:.5rem;padding:.5rem .75rem;z-index:2}
    .filters-controls{flex-wrap:wrap;row-gap:.5rem}
    .filters-controls > *{flex:0 1 auto}
    .badge-evt{border-radius:.4rem;padding:.25rem .5rem;font-weight:600}
    .badge-actor{border-radius:999px;padding:.15rem .5rem}
    .badge-evt.payment{background:var(--color-aqua, #7ab4a1);color:#fff}
    .badge-evt.booking{background:var(--color-orange, #e08f5f);color:#fff}
    .badge-evt.email{background:#0d6efd;color:#fff}
    .badge-evt.otp{background:#6c757d;color:#fff}
    .badge-evt.test{background:#6610f2;color:#fff}
    .badge-actor.admin{background:#dc3545;color:#fff}
    .badge-actor.customer{background:#198754;color:#fff}
    .badge-actor.system{background:#6c757d;color:#fff}
    .table-hover tbody tr:hover{background:rgba(0,0,0,.025)}
    .truncate{max-width:360px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .dt-buttons .dt-button{border:1px solid #ced4da !important}
  </style>
  <script>
    window.__csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>';
  </script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    (function(){ try{ if(window.Swal && !window.__sbhAlertWrapped){ window.__sbhAlertWrapped=true; const native=window.alert; window.alert=function(m){ try{ Swal.fire({icon:'info',title:String(m||''),confirmButtonText:'OK'});}catch(e){ try{ native.call(window,m);}catch(_){} } }; } }catch(e){} })();
  </script>
  <link rel="icon" href="../template/assets/img/favicon.png">
  <link rel="apple-touch-icon" href="../template/assets/img/apple-touch-icon.png">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Nunito:300,400,600,700|Poppins:300,400,500,600,700" rel="stylesheet">
  <link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../template/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../template/assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="../template/assets/vendor/simple-datatables/style.css" rel="stylesheet">
  <link href="../template/assets/css/style.css" rel="stylesheet">
  <link href="../css/theme-overrides.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../template/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../template/assets/css/style.css" rel="stylesheet">
  <link href="../css/theme-overrides.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
</head>
<body>
  <!-- Header -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="admindash.php" class="logo d-flex align-items-center">
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
          <i class="bi bi-house"></i><span>Home</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="admindash.php">
          <i class="bi bi-grid"></i><span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="activity_log.php">
          <i class="bi bi-geo-alt"></i><span>Activity Log</span>
        </a>
      </li>
    </ul>
  </aside>
  <!-- End Sidebar -->

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Activity Log</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="admindash.php">Dashboard</a></li>
          <li class="breadcrumb-item active">Activity Log</li>
        </ol>
      </nav>
    </div>
    <section class="section">
      <div class="card">
        <div class="card-body">
          <div class="filters-bar d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
            <h5 class="card-title mb-0 d-flex align-items-center gap-2"><i class="bi bi-funnel"></i><span>Filters</span></h5>
            <div class="d-flex gap-2 filters-controls">
              <select id="actEvent" class="form-select form-select-sm" style="width: 240px;" multiple>
                <option value="PAYMENT_UPDATED">PAYMENT_UPDATED</option>
                <option value="BOOKING_CONFIRMED">BOOKING_CONFIRMED</option>
                <option value="EMAIL_SENT">EMAIL_SENT</option>
                <option value="OTP_SENT">OTP_SENT</option>
                <option value="TEST_EVENT">TEST_EVENT</option>
              </select>
              <select id="actActor" class="form-select form-select-sm" style="width: 140px;">
                <option value="">Any actor</option>
                <option value="admin">admin</option>
                <option value="customer">customer</option>
                <option value="system">system</option>
              </select>
              <input id="actStart" type="date" class="form-control form-control-sm" style="width: 160px;">
              <input id="actEnd" type="date" class="form-control form-control-sm" style="width: 160px;">
              <input id="actQ" type="text" class="form-control form-control-sm" placeholder="Search message/refs" style="width: 220px;">
              <select id="actLimit" class="form-select form-select-sm" style="width: 120px;">
                <option value="100">Last 100</option>
                <option value="250">Last 250</option>
                <option value="500" selected>Last 500</option>
                <option value="1000">Last 1000</option>
              </select>
              <button class="btn btn-sm btn-outline-primary" onclick="loadActivityLog()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
              <button class="btn btn-sm btn-outline-secondary" onclick="quickRange('today')">Today</button>
              <button class="btn btn-sm btn-outline-secondary" onclick="quickRange('7d')">7d</button>
              <button class="btn btn-sm btn-outline-secondary" onclick="quickRange('30d')">30d</button>
              <div class="form-check form-switch d-flex align-items-center ms-2">
                <input class="form-check-input" type="checkbox" id="actAuto" onchange="toggleAutoRefresh(this.checked)">
                <label class="form-check-label small ms-1" for="actAuto">Auto-refresh</label>
              </div>
              <button class="btn btn-sm btn-outline-danger" onclick="clearActivityFilters()"><i class="bi bi-x-circle"></i> Clear</button>
            </div>
          </div>
          <div class="table-responsive mt-2">
            <table class="table table-borderless table-hover align-middle" id="activityTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Time</th>
                  <th>Event</th>
                  <th>Actor</th>
                  <th>User</th>
                  <th>Message</th>
                  <th>TX</th>
                  <th>Reservation</th>
                  <th>Reservee</th>
                  <th>Facility</th>
                  <th>Amount</th>
                  <th></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
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

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../template/assets/js/main.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script>
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
          {extend:'copy', className:'btn btn-sm btn-outline-secondary'},
          {extend:'excel', className:'btn btn-sm btn-outline-success'},
          {extend:'csv', className:'btn btn-sm btn-outline-primary'}
        ],
        language: { search: "", searchPlaceholder: "Search..." },
        order: initialOrder || []
      });
    }

    function num(n){ return Number(n||0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    function escapeHtml(s){ return (s+"").replace(/[&<>"]+/g, m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[m])); }
    function eventBadge(ev){
      const e = String(ev||'');
      const m = {
        PAYMENT_UPDATED: '<span class="badge-evt payment badge">PAYMENT_UPDATED</span>',
        BOOKING_CONFIRMED: '<span class="badge-evt booking badge">BOOKING_CONFIRMED</span>',
        EMAIL_SENT: '<span class="badge-evt email badge">EMAIL_SENT</span>',
        OTP_SENT: '<span class="badge-evt otp badge">OTP_SENT</span>',
        TEST_EVENT: '<span class="badge-evt test badge">TEST_EVENT</span>'
      };
      return m[e] || `<span class="badge bg-secondary">${escapeHtml(e)}</span>`;
    }
    function actorBadge(a){
      const s = String(a||'');
      if (s==='admin') return '<span class="badge-actor admin badge">admin</span>';
      if (s==='customer') return '<span class="badge-actor customer badge">customer</span>';
      if (s==='system') return '<span class="badge-actor system badge">system</span>';
      return `<span class="badge bg-secondary">${escapeHtml(s)}</span>`;
    }

    function getSelectedMulti(selectId){
      const el = document.getElementById(selectId);
      if (!el) return [];
      return Array.from(el.options).filter(o=>o.selected).map(o=>o.value);
    }

    function loadActivityLog(){
      const el = $('#activityTable');
      if ($.fn.dataTable.isDataTable(el)) {
        el.DataTable().destroy();
      }
      const params = new URLSearchParams();
      const evList = getSelectedMulti('actEvent');
      const ev = evList.join(',');
      const ac = document.getElementById('actActor')?.value || '';
      const qs = document.getElementById('actQ')?.value || '';
      const sd = document.getElementById('actStart')?.value || '';
      const ed = document.getElementById('actEnd')?.value || '';
      const lm = document.getElementById('actLimit')?.value || '500';
      if (ev) params.set('event_type', ev);
      if (ac) params.set('actor', ac);
      if (qs) params.set('q', qs);
      if (sd) params.set('start_date', sd);
      if (ed) params.set('end_date', ed);
      params.set('limit', lm);
      fetch('fetch_activity_log.php?' + params.toString())
        .then(r=>r.json()).then(j=>{
          const rows = j.rows||[];
          const tbody = document.querySelector('#activityTable tbody');
          tbody.innerHTML = rows.map(r=>`
            <tr>
              <td>${r.id}</td>
              <td>${escapeHtml(r.created_at||'')}</td>
              <td>${eventBadge(r.event_type)}</td>
              <td>${actorBadge(r.actor)}</td>
              <td>${r.user_id??''}</td>
              <td class="truncate" title="${escapeHtml(r.message||'')}">${escapeHtml(r.message||'')}</td>
              <td>${escapeHtml(r.ref_transaction_id||'')}</td>
              <td>${r.ref_reservation_id??''}</td>
              <td>${escapeHtml(r.reservee||'')}</td>
              <td>${escapeHtml(r.facility_name||'')}</td>
              <td>${r.amount!=null?('₱'+num(r.amount)):'-'}</td>
              <td><button class="btn btn-sm btn-outline-secondary" onclick="viewLogDetails(${r.id})"><i class="bi bi-search"></i></button></td>
            </tr>
          `).join('');
          initOrRefreshDT('#activityTable', [[0,'desc']]);
        });
    }

    function quickRange(type){
      const s = document.getElementById('actStart');
      const e = document.getElementById('actEnd');
      const today = new Date();
      const pad = (n)=> String(n).padStart(2,'0');
      const fmt = (d)=> `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
      if (type==='today'){
        const t = fmt(today); s.value=t; e.value=t;
      } else if (type==='7d'){
        const d = new Date(today); d.setDate(d.getDate()-6); s.value=fmt(d); e.value=fmt(today);
      } else if (type==='30d'){
        const d = new Date(today); d.setDate(d.getDate()-29); s.value=fmt(d); e.value=fmt(today);
      }
      loadActivityLog();
    }

    function viewLogDetails(id){
      fetch('fetch_activity_log.php?limit=1000')
        .then(r=>r.json()).then(j=>{
          const rows = j.rows||[];
          const r = rows.find(x=>x.id===id);
          if (!r) { alert('Not found'); return; }
          const md = typeof r.metadata === 'string' ? r.metadata : JSON.stringify(r.metadata||{}, null, 2);
          const html = `
            <div class="table-responsive">
              <table class="table table-sm">
                <tr><th>ID</th><td>${r.id}</td></tr>
                <tr><th>Time</th><td>${escapeHtml(r.created_at||'')}</td></tr>
                <tr><th>Event</th><td>${escapeHtml(r.event_type||'')}</td></tr>
                <tr><th>Actor</th><td>${escapeHtml(r.actor||'')}</td></tr>
                <tr><th>User</th><td>${r.user_id??''}</td></tr>
                <tr><th>Message</th><td>${escapeHtml(r.message||'')}</td></tr>
                <tr><th>TX</th><td>${escapeHtml(r.ref_transaction_id||'')}</td></tr>
                <tr><th>Reservation</th><td>${r.ref_reservation_id??''}</td></tr>
                <tr><th>Reservee</th><td>${escapeHtml(r.reservee||'')}</td></tr>
                <tr><th>Facility</th><td>${escapeHtml(r.facility_name||'')}</td></tr>
                <tr><th>Amount</th><td>${r.amount!=null?('₱'+num(r.amount)):'-'}</td></tr>
                <tr><th>IP</th><td>${escapeHtml(r.ip||'')}</td></tr>
                <tr><th>User Agent</th><td><small>${escapeHtml(r.user_agent||'')}</small></td></tr>
                <tr><th>Metadata</th><td><pre class="mb-0" style="white-space:pre-wrap">${escapeHtml(md||'')}</pre></td></tr>
              </table>
            </div>`;
          if (window.Swal) {
            Swal.fire({ title: 'Activity Details', html, width: 760, confirmButtonText: 'Close' });
          } else {
            const w = window.open('', '_blank');
            w.document.write(html);
            w.document.close();
          }
        });
    }

    let __actAutoTimer = null;
    function toggleAutoRefresh(on){
      if (on) {
        if (__actAutoTimer) clearInterval(__actAutoTimer);
        __actAutoTimer = setInterval(loadActivityLog, 15000);
      } else {
        if (__actAutoTimer) { clearInterval(__actAutoTimer); __actAutoTimer = null; }
      }
    }

    function clearActivityFilters(){
      try{ Array.from(document.getElementById('actEvent').options).forEach(o=>o.selected=false); }catch(e){}
      try{ document.getElementById('actActor').value=''; }catch(e){}
      try{ document.getElementById('actStart').value=''; }catch(e){}
      try{ document.getElementById('actEnd').value=''; }catch(e){}
      try{ document.getElementById('actQ').value=''; }catch(e){}
      try{ document.getElementById('actLimit').value='500'; }catch(e){}
      loadActivityLog();
    }

    document.addEventListener('DOMContentLoaded', loadActivityLog);
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
    // Mark Activity menu active
    (function(){
      try{
        const a = document.getElementById('menu-activity');
        if (a){ a.classList.remove('collapsed'); a.classList.add('active'); }
      }catch(e){}
    })();
  </script>
</body>
</html>


