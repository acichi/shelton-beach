<?php
require __DIR__ . '/_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Walk-in Booking - Admin</title>
  <link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
  <link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
  <link href="../template/assets/css/style.css" rel="stylesheet" />
  <link href="../css/theme-overrides.css" rel="stylesheet" />
  <style>
    .svg-container{width:100%;margin:0;border:none;background:transparent;overflow:hidden;-webkit-overflow-scrolling:touch}
    #facility-map{width:100%;height:100%;min-height:clamp(320px, 60vh, 560px);display:block;touch-action:manipulation}
    .facility-pin{transition:all .2s ease;cursor:pointer;stroke:#fff;stroke-width:1.5}
    .facility-pin:hover{r:12;filter:drop-shadow(0 0 4px rgba(0,0,0,.2))}
    .card .card-title{margin-bottom:.5rem;display:flex;justify-content:space-between;align-items:center}
    .badge-availability{background:var(--sbh-primary,#7ab4a1);color:#fff}
    .badge-unavailable{background:var(--sbh-accent-pink,#e19985);color:#fff}
    .price-chip{background:#f6f9ff;border:1px solid #dce3f1;color:#2c3e50;font-weight:600;border-radius:999px;padding:.1rem .5rem}
    #listWrap .list-group-item{border:1px solid #e9ecef;border-radius:.75rem;margin-bottom:.5rem}
    #listWrap .list-group-item:hover{background:#f8f9fc}
    #listWrap .price-chip{background:#eef3ff;border:1px solid #d6e2ff;color:#1c2c5b;font-weight:600;border-radius:999px;padding:.1rem .5rem}
  </style>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../js/notify.js?v=<?php echo filemtime(__DIR__ . '/../js/notify.js'); ?>"></script>
  <script>
    // Protect this page to admins only (simple front-end hint; actual enforcement is in PHP _auth.php)
  </script>
  <style>
    /* compact header spacing for this page */
    #main .card .card-body{padding:1rem}
  </style>
  <script>
    // Optional: allow deep-linking to a specific facility via ?facility=Name
  </script>
  <meta name="robots" content="noindex,nofollow" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://code.jquery.com">
  <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
  <link rel="dns-prefetch" href="https://unpkg.com">
  <link rel="dns-prefetch" href="https://cdn.datatables.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://fonts.googleapis.com">
  <link rel="dns-prefetch" href="https://fonts.gstatic.com">
  <link rel="dns-prefetch" href="https://kit.fontawesome.com">
  <link rel="dns-prefetch" href="https://polyfill.io">
  <link rel="dns-prefetch" href="https://cdn.polyfill.io">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
  <link rel="dns-prefetch" href="https://stackpath.bootstrapcdn.com">
  <link rel="dns-prefetch" href="https://maxcdn.bootstrapcdn.com">
  <link rel="dns-prefetch" href="https://use.fontawesome.com">
  <link rel="dns-prefetch" href="https://cdn.rawgit.com">
  <link rel="dns-prefetch" href="https://raw.githubusercontent.com">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://ajax.googleapis.com">
  <link rel="dns-prefetch" href="https://code.jquery.com">
  <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
</head>
<body>
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="admindash.php" class="logo d-flex align-items-center"><img src="../pics/logo2.png" alt=""><span class="d-none d-lg-block">Shelton Admin</span></a>
      <i class="bi bi-list toggle-sidebar-btn" onclick="document.body.classList.toggle('toggle-sidebar'); return false;" title="Toggle sidebar"></i>
    </div>
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
        <a class="nav-link" href="walkin_guest.php">
          <i class="bi bi-geo-alt"></i><span>Walk-in Booking</span>
        </a>
      </li>
    </ul>
  </aside>
  </header>
  <main id="main" class="main">
    <div class="pagetitle"><h1>Walk-in Booking</h1></div>
    <section class="section">
      <div class="card"><div class="card-body">
        <h5 class="card-title d-flex justify-content-between align-items-center">
          <span>Select a facility</span>
          <div class="d-flex gap-2">
            <span class="btn-group btn-group-sm" role="group" aria-label="View toggle">
              <button type="button" class="btn btn-outline-primary active" id="btnMap" data-bs-toggle="tooltip" title="View interactive map" aria-pressed="true">Map</button>
              <button type="button" class="btn btn-outline-primary" id="btnList" data-bs-toggle="tooltip" title="View as a list" aria-pressed="false">List</button>
            </span>
            <span class="btn-group btn-group-sm" role="group" aria-label="Map color toggle">
              <button type="button" class="btn btn-outline-secondary active" id="btnMap1" data-bs-toggle="tooltip" title="High-contrast color map (1)" aria-pressed="true">1</button>
              <button type="button" class="btn btn-outline-secondary" id="btnMap2" data-bs-toggle="tooltip" title="Classic color map (2)" aria-pressed="false">2</button>
            </span>
          </div>
        </h5>
        <div class="text-muted small mb-2"><i class="bi bi-info-circle"></i> Click a pin or list item to book for a walk-in guest.</div>
        <div class="row g-2 align-items-end mb-3" id="dateFilter">
          <div class="col-12 col-md-4">
            <label class="form-label">Filter Start</label>
            <input type="datetime-local" id="flt_start" class="form-control" />
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Filter End</label>
            <input type="datetime-local" id="flt_end" class="form-control" />
          </div>
          <div class="col-12 col-md-4 d-flex gap-2">
            <button type="button" id="btnCheckAvail" class="btn btn-primary">Check Availability</button>
            <button type="button" id="btnResetAvail" class="btn btn-outline-secondary">Reset</button>
          </div>
        </div>
        <div class="alert alert-info py-2 small" role="alert">
          <i class="bi bi-exclamation-circle"></i> Please select dates and click <strong>Check Availability</strong> before clicking a facility on the map.
        </div>
        <div id="mapWrap">
          <div class="svg-container">
            <svg id="facility-map" viewBox="0 0 1200 600" preserveAspectRatio="xMidYMid meet">
              <image id="mapImage1" href="../pics/2.svg" width="1200" height="600" />
            </svg>
          </div>
        </div>
        <div id="listWrap" class="d-none">
          <div id="facility-list" class="list-group"></div>
        </div>
      </div></div>
    </section>
  </main>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../template/assets/js/main.js"></script>
  <script>
  (function(){
    const svg = document.getElementById('facility-map');
    // Ensure sidebar toggle works if template JS didn't bind it yet
    try {
      const t = document.querySelector('.toggle-sidebar-btn');
      if (t && !t.__bound) {
        t.addEventListener('click', function(e){ e.preventDefault(); document.body.classList.toggle('toggle-sidebar'); });
        t.__bound = true;
      }
    } catch(e) {}
    let facilities = [];
    let initialFacility = null;
    try { const p = new URLSearchParams(location.search).get('facility'); if (p) initialFacility = p; } catch(e) {}

    fetch('retrieve_facility.php').then(r=>r.json()).then(data=>{
      facilities = Array.isArray(data) ? data : [];
      renderMapPins(facilities);
      renderList(facilities);
      if (initialFacility) {
        const f = facilities.find(x=> String(x.name).toLowerCase() === String(initialFacility).toLowerCase());
        if (f) openReservationModal(f);
      }
      if (window.innerWidth < 576) { showList(); }
      // Ensure filter date inputs cannot select past dates
      try {
        const fs = document.getElementById('flt_start');
        const fe = document.getElementById('flt_end');
        const toLocal = (d)=>{ const tzOff = d.getTimezoneOffset(); const local = new Date(d.getTime() - tzOff*60*1000); return local.toISOString().slice(0,16); };
        const setFilterMins = ()=>{
          const nowLocal = toLocal(new Date());
          if (fs) {
            fs.min = nowLocal;
            if (fs.value && fs.value < nowLocal) fs.value = nowLocal;
          }
          if (fe) {
            fe.min = (fs && fs.value) ? fs.value : nowLocal;
            if (fe.value && fs && fs.value && fe.value < fs.value) fe.value = fs.value;
          }
        };
        setFilterMins();
        fs?.addEventListener('input', setFilterMins);
        fe?.addEventListener('input', setFilterMins);
      } catch(e) {}
    });

    function renderMapPins(data){
      const radius = window.innerWidth < 576 ? 12 : 10;
      data.forEach(f=>{
        const s = String(f.status||'').toLowerCase();
        const color = s === 'available' ? 'green' : 'red';
        const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        c.setAttribute('cx', f.pin_x); c.setAttribute('cy', f.pin_y); c.setAttribute('r', radius); c.setAttribute('fill', color);
        c.setAttribute('title', `${f.name||''} — ${s||'unknown'}`);
        c.classList.add('facility-pin');
        c.addEventListener('click', ()=> openReservationModal(f));
        svg.appendChild(c);
      });
    }

    function clearPins(){ try { Array.from(document.querySelectorAll('#facility-map .facility-pin')).forEach(p=>p.remove()); } catch(e) {} }

    function rerenderUI(){ clearPins(); renderMapPins(facilities); renderList(facilities); }

    function renderList(data){
      const list = document.getElementById('facility-list');
      list.innerHTML = data.map((f,idx)=>{
        const status = String(f.status||'');
        const badgeCls = status.toLowerCase()==='available' ? 'badge-availability' : 'badge-unavailable';
        const price = Number(f.price||0).toLocaleString('en-PH');
        return `
          <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-idx="${idx}">
            <span class="d-flex align-items-center gap-2">
              <i class="bi bi-geo-alt text-primary"></i>
              <span>
                <strong>${(f.name||'').replace(/&/g,'&amp;').replace(/</g,'&lt;')}</strong>
                <small class="d-block text-muted">Tap to book</small>
              </span>
            </span>
            <span class="d-flex align-items-center gap-2">
              <span class="price-chip">₱${price}</span>
              <span class="badge ${badgeCls}">${status}</span>
              <i class="bi bi-chevron-right text-muted"></i>
            </span>
          </a>`;
      }).join('');
      list.querySelectorAll('a').forEach(a=>{
        a.addEventListener('click', function(e){ e.preventDefault(); const i = Number(this.dataset.idx||0); const f = facilities[i]; if (f) openReservationModal(f); });
      });
    }

    document.getElementById('btnMap').addEventListener('click', function(){ setPressed(this, true); setPressed(document.getElementById('btnList'), false); showMap(); });
    document.getElementById('btnList').addEventListener('click', function(){ setPressed(this, true); setPressed(document.getElementById('btnMap'), false); showList(); });
    function showMap(){ document.getElementById('mapWrap').classList.remove('d-none'); document.getElementById('listWrap').classList.add('d-none'); this?.classList?.add('active'); }
    function showList(){ document.getElementById('mapWrap').classList.add('d-none'); document.getElementById('listWrap').classList.remove('d-none'); this?.classList?.add('active'); }
    function setPressed(btn, val){ if (!btn) return; btn.setAttribute('aria-pressed', val ? 'true' : 'false'); }
    // Base image toggle buttons (force SVG 1.svg / 2.svg)
    document.getElementById('btnMap1').addEventListener('click', function(){ switchMapImage('../pics/2.svg', this, document.getElementById('btnMap2')); setPressed(this,true); setPressed(document.getElementById('btnMap2'), false); });
    document.getElementById('btnMap2').addEventListener('click', function(){ switchMapImage('../pics/1.svg', this, document.getElementById('btnMap1')); setPressed(this,true); setPressed(document.getElementById('btnMap1'), false); });
    function switchMapImage(src, activeBtn, otherBtn){
      var img = document.getElementById('mapImage1'); if (!img) return;
      img.setAttribute('href', src);
      if (activeBtn && otherBtn){ activeBtn.classList.add('active'); otherBtn.classList.remove('active'); }
      try { clearPins(); renderMapPins(facilities); } catch(e) {}
    }

    function openReservationModal(facility){
      const name = facility.name; const details = facility.details; const status = String(facility.status||'');
      const modal = `
      <div class="modal fade" id="admWalkinModal" tabindex="-1">
        <div class="modal-dialog modal-lg"><div class="modal-content">
          <div class="modal-header"><div><h5 class="modal-title">Walk-in: ${name}</h5><div class="small text-muted">Select dates and payment option</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="row g-2 mb-2">
              <div class="col-12 col-md-6"><label class="form-label">Reservee</label><input id="wk_reservee" class="form-control" placeholder="Guest name" value="Walk-in Guest"></div>
              <div class="col-12 col-md-6"><label class="form-label">Payment Type</label>
                <select id="wk_payment" class="form-select">
                  <option value="Cash">Cash</option>
                  <option value="GCash">GCash</option>
                  <option value="Card">Card</option>
                </select>
              </div>
            </div>
            <p class="mb-2"><strong>Status:</strong> ${status}</p>
            <p>${details||''}</p>
            <div class="row g-2">
              <div class="col-12 col-md-6"><label class="form-label">Start</label><input id="wk_start" type="datetime-local" class="form-control"></div>
              <div class="col-12 col-md-6"><label class="form-label">End</label><input id="wk_end" type="datetime-local" class="form-control"></div>
              <div class="col-12 col-md-6"><label class="form-label">Amount</label><input id="wk_amount" class="form-control" readonly></div>
              <div class="col-12"><div id="wk_avail" class="small"></div></div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-primary" id="wk_submit" style="min-width:120px" disabled>Book</button>
          </div>
        </div></div>
      </div>`;
      document.getElementById('admWalkinModal')?.remove();
      document.body.insertAdjacentHTML('beforeend', modal);
      const m = new bootstrap.Modal(document.getElementById('admWalkinModal')); m.show();
      const start = document.getElementById('wk_start'); const end = document.getElementById('wk_end'); const amount = document.getElementById('wk_amount'); const avail = document.getElementById('wk_avail'); const btn = document.getElementById('wk_submit');
      try{
        const toLocal = (d)=>{ const tzOff = d.getTimezoneOffset(); const local = new Date(d.getTime() - tzOff*60*1000); return local.toISOString().slice(0,16); };
        const fltStartEl = document.getElementById('flt_start');
        const fltEndEl = document.getElementById('flt_end');
        const hasFilter = !!(fltStartEl && fltEndEl && fltStartEl.value && fltEndEl.value);
        if (hasFilter) {
          start.value = fltStartEl.value;
          end.value = fltEndEl.value;
          // Set mins to avoid invalid ranges
          const nowLocal = toLocal(new Date());
          start.min = nowLocal;
          if (start.value < nowLocal) start.value = nowLocal;
          end.min = start.value;
          if (end.value < start.value) end.value = start.value;
        } else {
          const now = new Date(); now.setMinutes(0,0,0); now.setHours(now.getHours()+1);
          const endDefault = new Date(now.getTime() + 24*60*60*1000);
          start.value = toLocal(now); end.value = toLocal(endDefault); start.min = toLocal(new Date()); end.min = start.value;
        }
      }catch(e){}
      async function refreshAvailability(){
        avail.textContent = ''; btn.disabled = true; amount.value='';
        const sVal = start.value; const eVal = end.value; if(!sVal || !eVal) return;
        try{
          const params = new URLSearchParams({ facility_name: name, date_start: sVal, date_end: eVal });
          const r = await fetch('check_availability.php?' + params.toString(), { credentials:'same-origin', headers:{ 'Accept':'application/json' } });
          const respText = await r.text();
          let j = null;
          try { j = JSON.parse(respText); } catch(_) {}
          if (!j || typeof j !== 'object') {
            const msg = (respText && respText.toLowerCase().includes('forbidden')) ? 'Session expired. Please log in again.' : 'Server returned an invalid response';
            throw new Error(msg);
          }
          if(j && j.success){
            amount.value = (Number(j.amount)||0).toFixed(2);
            if(j.available){ avail.textContent = 'Available'; avail.className='small text-success'; btn.disabled = false; }
            else { avail.textContent = 'Not available for the selected dates'; avail.className='small text-danger'; btn.disabled = true; }
          }else{ avail.textContent = j && j.message ? j.message : 'Unable to check availability'; avail.className='small text-warning'; }
        }catch(e){ avail.textContent = (e && e.message) ? e.message : 'Network error while checking availability'; avail.className='small text-warning'; }
      }
      // Keep end >= start and update end's min when start changes
      start.addEventListener('input', ()=>{
        try{
          const toLocal = (d)=>{ const tzOff = d.getTimezoneOffset(); const local = new Date(d.getTime() - tzOff*60*1000); return local.toISOString().slice(0,16); };
          const nowLocal = toLocal(new Date());
          if (start.value && start.value < nowLocal) start.value = nowLocal;
          end.min = start.value || '';
          if (end.value && start.value && end.value < start.value) {
            end.value = start.value;
          }
        }catch(e){}
        refreshAvailability();
      });
      end.addEventListener('input', refreshAvailability);
      document.getElementById('wk_submit').addEventListener('click', async ()=>{
        const payload = {
          reservee: (document.getElementById('wk_reservee').value||'').trim() || 'Walk-in Guest',
          facility_name: name,
          date_start: start.value,
          date_end: end.value,
          payment_type: document.getElementById('wk_payment').value,
          amount: parseFloat(amount.value||'0')
        };
        try{
          const r = await fetch('submit_walkin.php', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify(payload), credentials:'same-origin' });
          const respText = await r.text();
          let j = null;
          try { j = JSON.parse(respText); } catch(_) {}
          if (!j || typeof j !== 'object') {
            const msg = (respText && respText.toLowerCase().includes('forbidden')) ? 'Session expired. Please log in again.' : 'Server returned an invalid response';
            throw new Error(msg);
          }
          if(j && j.success){
            if (window.Swal) Swal.fire({ icon:'success', title:'Walk-in reservation created', timer:1800, showConfirmButton:false });
            m.hide();
            setTimeout(()=>{ window.location.href = 'admindash.php#reservations'; }, 600);
          }else{
            if (window.Swal) Swal.fire({ icon:'error', title: j.message||'Failed to create reservation' });
          }
        }catch(e){ if (window.Swal) Swal.fire({ icon:'error', title: (e && e.message) ? e.message : 'Network error' }); }
      });
      refreshAvailability();
    }

    async function colorPinsForRange(startVal, endVal){
      if(!startVal || !endVal){ rerenderUI(); return; }
      const tasks = facilities.map(async (f)=>{
        try{
          const params = new URLSearchParams({ facility_name: f.name, date_start: startVal, date_end: endVal });
          const r = await fetch('check_availability.php?' + params.toString(), { credentials:'same-origin' });
          const j = await r.json();
          f.__rangeAvailable = !!(j && j.success && j.available);
        }catch(e){ f.__rangeAvailable = null; }
      });
      await Promise.all(tasks);
      clearPins();
      const radius = window.innerWidth < 576 ? 12 : 10;
      facilities.forEach(f=>{
        let color = 'gray';
        if (f.__rangeAvailable === true) color = 'green';
        else if (f.__rangeAvailable === false) color = 'red';
        const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        c.setAttribute('cx', f.pin_x); c.setAttribute('cy', f.pin_y); c.setAttribute('r', radius); c.setAttribute('fill', color);
        c.setAttribute('title', `${f.name||''} — ${f.__rangeAvailable===true?'available':f.__rangeAvailable===false?'unavailable':'unknown'}`);
        c.classList.add('facility-pin');
        c.addEventListener('click', ()=> openReservationModal(f));
        svg.appendChild(c);
      });
    }

    document.getElementById('btnCheckAvail').addEventListener('click', ()=>{
      const s = document.getElementById('flt_start').value;
      const e = document.getElementById('flt_end').value;
      colorPinsForRange(s,e);
    });
    document.getElementById('btnResetAvail').addEventListener('click', ()=>{
      try{ document.getElementById('flt_start').value=''; document.getElementById('flt_end').value=''; }catch(e){}
      rerenderUI();
    });
  })();

  // Auto-collapse sidebar on small screens after navigation
  document.addEventListener('DOMContentLoaded', function(){
    var links = document.querySelectorAll('#sidebar .nav-link');
    links.forEach(function(a){
      a.addEventListener('click', function(){ if (window.innerWidth < 1200) { document.body.classList.remove('toggle-sidebar'); } });
    });
  });
  </script>
</body>
</html>


