<?php
require __DIR__ . '/_auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes" />
	<link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
	<link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
	<link href="../template/assets/css/style.css" rel="stylesheet" />
	<link href="../css/theme-overrides.css" rel="stylesheet" />
	<style>
		/* Align modal buttons and SweetAlert with theme */
		.swal2-popup .swal2-actions .swal2-styled { min-width: 120px; }
		.swal2-popup .swal2-actions { gap: .5rem; }
		.modal .btn-primary { background: var(--sbh-primary); border-color: var(--sbh-primary); }
		.modal .btn-primary:hover { filter: brightness(.95); }
	</style>
	<meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>" />
	<title>Book Now</title>
	<style>
		.svg-container{width:100%;margin:0;border:none;background:transparent;overflow:visible;-webkit-overflow-scrolling:touch}
		#facility-map{width:100%;height:100%;min-height:clamp(280px, 60vh, 520px);display:block;touch-action:pan-x pan-y pinch-zoom}
		.facility-pin{transition:all .2s ease;cursor:pointer;stroke:#fff;stroke-width:1.5}
		.facility-pin:hover{r:12;filter:drop-shadow(0 0 4px rgba(0,0,0,.2))}
		/* Segmented toggles */
		.seg-toggle{border:1px solid rgba(122,180,161,.35);border-radius:999px;overflow:hidden;background:#f0f7f5}
		.seg-toggle .btn{border:0 !important;padding:.35rem .9rem;font-weight:600;color:#1c2c5b}
		.seg-toggle .btn.active{background:var(--sbh-primary,#7ab4a1);color:#fff}
		.seg-toggle .btn:not(.active):hover{background:rgba(122,180,161,.15)}
		/* Theme tweaks */
		.card .card-title{margin-bottom:.5rem;display:flex;justify-content:space-between;align-items:center}
		.btn-group .btn{min-width: 44px}
		.badge-availability{background:var(--color-aqua);color:#fff}
		.badge-unavailable{background:var(--color-pink);color:#fff}
		.badge-info-blue{background: var(--sbh-primary); color: #fff}
		.list-group-item{border: 1px solid #e9ecef; border-radius: .5rem; margin-bottom: .5rem}
		.list-group-item .bi{opacity:.85}
		.price-chip{background:#f6f9ff;border:1px solid #dce3f1;color:#2c3e50;font-weight:600;border-radius:999px;padding:.1rem .5rem}
		#listWrap .list-group-item{border:1px solid #e9ecef;border-radius:.75rem;margin-bottom:.5rem}
		#listWrap .list-group-item:hover{background:#f8f9fc}
		#listWrap .price-chip{background:#eef3ff;border:1px solid #d6e2ff;color:#1c2c5b;font-weight:600;border-radius:999px;padding:.1rem .5rem}
		/* Enhancements */
		.svg-container{position:relative}
		.map-loading-overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.6);z-index:5}
		/* Pin hover animation (hover and touch via JS class) */
		.facility-pin image{transition:transform .18s ease;transform-box:fill-box;transform-origin:50% 100%;}
		.facility-pin:hover image,.facility-pin.pin-hover image{transform:translateY(-10px) scale(1.25);}
		/* Sticky filter bar */
		.sticky-filter{position:sticky;top:72px;z-index:8;background:#fff;border-radius:.75rem;box-shadow:0 2px 12px rgba(0,0,0,.04);padding-bottom:.25rem;margin-bottom:.5rem}
		@media (max-width: 576px){ .sticky-filter{top:60px;} }
		/* Mobile-focused tweaks: reduce container chrome and allow more map space */
		@media (max-width: 576px){
			.card{background:transparent;border:0;box-shadow:none}
			.card .card-body{padding:.5rem}
			.sticky-filter{top:56px;border-radius:.5rem;box-shadow:none;margin-bottom:.5rem;padding:.5rem}
			.card .card-title{flex-direction:column; align-items:flex-start; gap:.5rem}
			.seg-toggle .btn{padding:.35rem .75rem}
			#facility-map{min-height:70vh}
		}
	</style>
</head>
<body>
	<header id="header" class="header fixed-top d-flex align-items-center">
		<div class="d-flex align-items-center justify-content-between">
			<a href="cusdash.php" class="logo d-flex align-items-center"><img src="../pics/logo2.png" alt=""><span class="d-none d-lg-block">Shelton Customer</span></a>
			<i class="bi bi-list toggle-sidebar-btn"></i>
		</div>
		<nav class="header-nav ms-auto">
			<ul class="d-flex align-items-center">
				<li class="nav-item pe-3">
					<div class="nav-link nav-profile d-flex align-items-center pe-0">
						<?php 
							$__cusUser = htmlspecialchars($_SESSION['user']['username'] ?? '');
							$__sessGender = strtolower($_SESSION['user']['gender'] ?? '');
							$__avatarSrc = '../pics/profile.png';
							if ($__sessGender === 'female') { $__avatarSrc = '../pics/avatar-female.png'; }
							else if ($__sessGender === 'male') { $__avatarSrc = '../pics/avatar-male.png'; }
						?>
						<img src="<?php echo $__avatarSrc; ?>" alt="Profile" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" onerror="this.onerror=null;this.src='../template/assets/img/profile-img.jpg';">
						<span class="d-none d-md-block ps-2"><?php echo ($__cusUser !== '' ? $__cusUser : 'Customer'); ?></span>
					</div>
				</li>
			</ul>
		</nav>
	</header>
	<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item">
        <a class="nav-link collapsed" href="../index.php">
          <i class="bi bi-house"></i><span>Home</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="cusdash.php">
          <i class="bi bi-grid"></i><span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="book_now.php">
          <i class="bi bi-geo-alt"></i><span>Book Now	</span>
        </a>
      </li>
    </ul>
  </aside>
	<main id="main" class="main">
		<div class="pagetitle"><h1>Book Now</h1></div>
		<section class="section">
			<div class="card"><div class="card-body sticky-filter">
				<h5 class="card-title d-flex justify-content-between align-items-center">
					<span>Select a facility</span>
					<div class="d-flex gap-2">
						<span class="seg-toggle btn-group btn-group-sm" role="group" aria-label="View toggle">
							<button type="button" class="btn active" id="btnMap" data-bs-toggle="tooltip" title="View interactive map" aria-pressed="true">Map</button>
							<button type="button" class="btn" id="btnList" data-bs-toggle="tooltip" title="View as a list" aria-pressed="false">List</button>
						</span>
						<span class="seg-toggle btn-group btn-group-sm" role="group" aria-label="Map color toggle">
							<button type="button" class="btn active" id="btnMap1" data-bs-toggle="tooltip" title="High-contrast color map (1)" aria-pressed="true">1</button>
							<button type="button" class="btn" id="btnMap2" data-bs-toggle="tooltip" title="Classic color map (2)" aria-pressed="false">2</button>
						</span>
					</div>
				</h5>
				<div class="text-muted small mb-2"><i class="bi bi-info-circle"></i> Tip: Use <strong>Map/List</strong> to switch views. Use <strong>1/2</strong> to change the map colors.</div>
				<div class="row g-2 align-items-end mb-3" id="dateFilter">
					<div class="col-12 col-md-4">
						<label class="form-label">Filter Start</label>
						<input type="datetime-local" id="flt_start" class="form-control" placeholder="Select start date/time" />
					</div>
					<div class="col-12 col-md-4">
						<label class="form-label">Filter End</label>
						<input type="datetime-local" id="flt_end" class="form-control" placeholder="Select end date/time" />
					</div>
					<div class="col-12 col-md-4 d-flex flex-wrap gap-3 align-items-center justify-content-md-end">
						<div class="form-check form-switch ms-1">
							<input class="form-check-input" type="checkbox" role="switch" id="flt_avail_only" disabled>
							<label class="form-check-label" for="flt_avail_only">Show available only</label>
						</div>
						<button type="button" id="btnResetAvail" class="btn btn-outline-secondary btn-sm">
							<i class="bi bi-arrow-counterclockwise"></i> Reset
						</button>
					</div>
				</div>
				<div class="alert alert-info py-2 small" role="alert">
					<i class="bi bi-exclamation-circle"></i> Select a start and end date to automatically check availability, then click a facility on the map to proceed with booking.
				</div>
				<div class="text-muted small mb-2" id="rangeSummary"></div>
				<div id="mapWrap">
					<div class="svg-container">
						<svg id="facility-map" viewBox="0 0 1200 600" preserveAspectRatio="xMidYMid meet">
							<defs>
								<filter id="pinGlowGreen" x="-90%" y="-90%" width="280%" height="280%">
									<feDropShadow dx="0" dy="0" stdDeviation="10" flood-color="#28a745" flood-opacity="1" />
								</filter>
								<filter id="pinGlowRed" x="-90%" y="-90%" width="280%" height="280%">
									<feDropShadow dx="0" dy="0" stdDeviation="10" flood-color="#dc3545" flood-opacity="1" />
								</filter>
							</defs>
							<image id="mapImage1" href="../pics/2.svg" width="1200" height="600" />
						</svg>
						<div id="mapLoading" class="map-loading-overlay d-none"><div class="spinner-border text-primary" role="status" aria-label="Loading"></div></div>
					</div>
				</div>
				<div class="small text-muted mb-2">
					<span class="me-3"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:green;margin-right:6px"></span>Available</span>
					<span class="me-3"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:red;margin-right:6px"></span>Unavailable</span>
					<span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:gray;margin-right:6px"></span>Unknown</span>
				</div>
				<div id="listWrap" class="d-none">
					<div id="facility-list" class="list-group"></div>
				</div>
			</div></div>
		</section>
	</main>

	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="../js/notify.js?v=<?php echo filemtime(__DIR__ . '/../js/notify.js'); ?>"></script>
	<script src="../template/assets/js/main.js"></script>
	<script>
	(function(){
		// Expose current user's fullname to JS for reservation payloads
		const CURRENT_FULLNAME = <?php echo json_encode($_SESSION['user']['fullname'] ?? ''); ?>;
		const svg = document.getElementById('facility-map');
		let facilities = [];
		const ICON_AVAIL = '../pics/marker-available.svg';
		const ICON_UNAVAIL = '../pics/marker-unavailable.svg';
		const PIN_W = 24, PIN_H = 28; // intrinsic sizes based on SVG viewBox
		const PIN_SCALE_MOBILE = 3.8; // even bigger on small screens
		const PIN_SCALE_DESKTOP = 4.2; // even bigger on desktop
		function attachPinHandlers(node, facility){
			try{
				node.addEventListener('pointerdown', function(){ node.classList.add('pin-hover'); setTimeout(()=> node.classList.remove('pin-hover'), 220); });
				node.addEventListener('touchstart', function(){ node.classList.add('pin-hover'); setTimeout(()=> node.classList.remove('pin-hover'), 220); }, { passive:true });
				node.addEventListener('click', function(){ try{ node.classList.add('pin-hover'); setTimeout(()=> node.classList.remove('pin-hover'), 220); }catch(_){} setTimeout(()=> openReservationModal(facility), 120); });
			}catch(e){ node.addEventListener('click', ()=> openReservationModal(facility)); }
		}
		fetch('retrieve_facility.php').then(r=>r.json()).then(data=>{
			facilities = Array.isArray(data) ? data : [];
			renderMapPins(facilities);
			renderList(facilities);
			// Keep map as default on all screen sizes
			// Prevent selecting past dates in filter inputs and auto-fill defaults on first load
			try {
				const fs = document.getElementById('flt_start');
				const fe = document.getElementById('flt_end');
				const toLocal = (d)=>{ const tzOff = d.getTimezoneOffset(); const local = new Date(d.getTime() - tzOff*60*1000); return local.toISOString().slice(0,16); };
				let defaultsApplied = false;
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
					// Auto-fill once if both are empty
					if (!defaultsApplied && fs && fe && !fs.value && !fe.value) {
						const now = new Date(); now.setMinutes(0,0,0); now.setHours(now.getHours()+1);
						const endDefault = new Date(now.getTime() + 24*60*60*1000);
						fs.value = toLocal(now);
						fe.value = toLocal(endDefault);
						defaultsApplied = true;
						try { colorPinsForRange(fs.value, fe.value); } catch(_) {}
					}
				};
				setFilterMins();
				fs?.addEventListener('input', setFilterMins);
				fe?.addEventListener('input', setFilterMins);
			} catch(e) {}
		});

		function renderMapPins(data){
			const scale = (window.innerWidth < 576 ? PIN_SCALE_MOBILE : PIN_SCALE_DESKTOP);
			data.forEach(f=>{
				const s = String(f.status||'').toLowerCase();
				const href = (s === 'available') ? ICON_AVAIL : ICON_UNAVAIL;
				const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
				g.classList.add('facility-pin');
				g.setAttribute('transform', `translate(${f.pin_x}, ${f.pin_y})`);
				const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
				title.textContent = `${f.name||''} — ${s||'unknown'}`;
				g.appendChild(title);
				const img = document.createElementNS('http://www.w3.org/2000/svg', 'image');
				img.setAttribute('href', href);
				img.setAttribute('x', String(-(PIN_W/2) * scale));
				img.setAttribute('y', String(-PIN_H * scale));
				img.setAttribute('width', String(PIN_W * scale));
				img.setAttribute('height', String(PIN_H * scale));
				img.setAttribute('filter', href===ICON_AVAIL ? 'url(#pinGlowGreen)' : 'url(#pinGlowRed)');
				g.appendChild(img);
				attachPinHandlers(g, f);
				svg.appendChild(g);
			});
		}

		function clearPins(){
			try { Array.from(document.querySelectorAll('#facility-map .facility-pin')).forEach(p=>p.remove()); } catch(e) {}
		}

		function rerenderUI(){
			clearPins();
			renderMapPins(facilities);
			renderList(facilities);
		}

		function renderList(data){
			const list = document.getElementById('facility-list');
			// Optional: search + filter by availability if range selected and toggle on
			let filtered = data;
			// search removed
			try {
				const s = document.getElementById('flt_start')?.value;
				const e = document.getElementById('flt_end')?.value;
				const only = document.getElementById('flt_avail_only')?.checked;
				if (s && e && only) {
					filtered = data.filter(f => f.__rangeAvailable === true);
				}
			} catch(e) {}
			if (!Array.isArray(filtered) || filtered.length === 0) {
				list.innerHTML = '<div class="text-muted small px-2 py-3">No facilities to show.</div>';
				return;
			}
			list.innerHTML = filtered.map((f,idx)=>{
				const status = String(f.status||'');
				const badgeCls = status.toLowerCase()==='available' ? 'badge-availability' : 'badge-unavailable';
				const price = Number(f.price||0).toLocaleString('en-PH');
				return `
					<a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-idx="${data.indexOf(f)}">
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

		// View toggle
		document.getElementById('btnMap').addEventListener('click', function(){ setPressed(this, true); setPressed(document.getElementById('btnList'), false); showMap(); });
		document.getElementById('btnList').addEventListener('click', function(){ setPressed(this, true); setPressed(document.getElementById('btnMap'), false); showList(); });
		// Base image toggle buttons (force SVG 1.svg / 2.svg)
		document.getElementById('btnMap1').addEventListener('click', function(){ switchMapImage('../pics/2.svg', this, document.getElementById('btnMap2')); setPressed(this,true); setPressed(document.getElementById('btnMap2'), false); });
		document.getElementById('btnMap2').addEventListener('click', function(){ switchMapImage('../pics/1.svg', this, document.getElementById('btnMap1')); setPressed(this,true); setPressed(document.getElementById('btnMap1'), false); });
		function showMap(){
			document.getElementById('mapWrap').classList.remove('d-none');
			document.getElementById('listWrap').classList.add('d-none');
			document.getElementById('btnMap').classList.add('active');
			document.getElementById('btnList').classList.remove('active');
		}
		function showList(){
			document.getElementById('mapWrap').classList.add('d-none');
			document.getElementById('listWrap').classList.remove('d-none');
			document.getElementById('btnMap').classList.remove('active');
			document.getElementById('btnList').classList.add('active');
		}

		function switchMapImage(src, activeBtn, otherBtn){
			// Backward compat: keep function but just set directly
			var img = document.getElementById('mapImage1'); if (!img) return;
			img.setAttribute('href', src);
			if (activeBtn && otherBtn){ activeBtn.classList.add('active'); otherBtn.classList.remove('active'); }
			try { clearPins(); renderMapPins(facilities); } catch(e) {}
		}

		function setPressed(btn, val){ if (!btn) return; btn.setAttribute('aria-pressed', val ? 'true' : 'false'); }

		function openReservationModal(facility){
			const name = facility.name; const price = facility.price; const details = facility.details; const status = String(facility.status||'');
			const modal = `
			<div class="modal fade" id="custReserveModal" tabindex="-1">
				<div class="modal-dialog modal-lg"><div class="modal-content">
					<div class="modal-header"><div><h5 class="modal-title">${name}</h5><div class="small text-muted">Select your dates and payment option</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
					<div class="modal-body">
						<p class="mb-2"><strong>Status:</strong> ${status}</p>
						<p>${details||''}</p>
						
						<!-- Capacity Information -->
						<div class="row g-2 mb-3">
							<div class="col-12">
								<label class="form-label">Number of People</label>
								<input type="number" id="bk_capacity" class="form-control" min="1" max="${facility.facility_capacity || 4}" value="1" required>
								<div class="form-text">
									<strong>Maximum Capacity:</strong> ${facility.facility_capacity || 4} people for this ${facility.facility_type || 'facility'}
								</div>
							</div>
						</div>
						
						<!-- New: Booking Type Selector -->
						<div class="row g-2 mb-3">
							<div class="col-12">
								<label class="form-label">Booking Type</label>
								<div class="d-flex flex-wrap align-items-center gap-3">
									<div class="form-check">
										<input class="form-check-input" type="radio" name="bk_type" id="bk_type_day" value="day" checked>
										<label class="form-check-label" for="bk_type_day">Day-use (Hourly)</label>
									</div>
									<div class="form-check">
										<input class="form-check-input" type="radio" name="bk_type" id="bk_type_overnight" value="overnight">
										<label class="form-check-label" for="bk_type_overnight">Overnight (Daily)</label>
									</div>
								</div>
								<div class="form-text">Day-use: Pay per hour. Overnight: Pay per day.</div>
							</div>
						</div>
						
						<div class="row g-2">
							<div class="col-12 col-md-6"><label class="form-label">Start</label><input id="bk_start" type="datetime-local" class="form-control"></div>
							<div class="col-12 col-md-6"><label class="form-label">End</label><input id="bk_end" type="datetime-local" class="form-control"></div>
							<div class="col-12 col-md-6"><label class="form-label">Payment Type</label>
								<select id="bk_payment" class="form-select">
									<option value="Cash">Cash</option>
									<option value="GCash">GCash</option>
									<option value="PayMaya">PayMaya</option>
									<option value="Visa">Visa</option>
									<option value="MasterCard">MasterCard</option>
								</select>
							</div>
							<div class="col-12 col-md-6"><label class="form-label">Amount to Pay Now</label><input id="bk_amount" class="form-control" readonly><div class="form-text">This is the amount you will pay now.</div></div>
							<div class="col-12">
								<label class="form-label">Payment plan</label>
								<div class="d-flex flex-wrap align-items-center gap-3">
									<div class="form-check">
										<input class="form-check-input" type="radio" name="bk_plan" id="bk_plan_full" value="full" checked>
										<label class="form-check-label" for="bk_plan_full">Full payment</label>
									</div>
									<div class="form-check d-flex align-items-center gap-2">
										<input class="form-check-input" type="radio" name="bk_plan" id="bk_plan_dp" value="down">
										<label class="form-check-label" for="bk_plan_dp">Down payment</label>
										<select id="bk_dp_percent" class="form-select form-select-sm" style="width:auto" disabled>
											<option value="20">20%</option>
											<option value="30" selected>30%</option>
											<option value="50">50%</option>
										</select>
									</div>
									<span class="text-muted small">Remaining balance will be payable later.</span>
								</div>
							</div>
							<div class="col-12"><div id="bk_avail" class="small"></div></div>
						</div>
						<div class="form-check mt-3">
							<input class="form-check-input" type="checkbox" id="bk_terms">
							<label class="form-check-label" for="bk_terms">I agree to the <a href="../terms-booking.php" target="_blank" rel="noopener">Booking Terms</a></label>
						</div>
					</div>
					<div class="modal-footer">
						<button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button class="btn btn-primary" id="bk_submit" style="min-width:120px" disabled>Book</button>
					</div>
				</div></div>
			</div>`;
			document.getElementById('custReserveModal')?.remove();
			document.body.insertAdjacentHTML('beforeend', modal);
			const reserveModal = new bootstrap.Modal(document.getElementById('custReserveModal'));
			reserveModal.show();

			// Modal logic: defaults and availability checks
			const bkStart = document.getElementById('bk_start');
			const bkEnd = document.getElementById('bk_end');
			const bkAmount = document.getElementById('bk_amount');
			const bkAvail = document.getElementById('bk_avail');
			const bkSubmit = document.getElementById('bk_submit');
			const bkTypeDay = document.getElementById('bk_type_day');
			const bkTypeOvernight = document.getElementById('bk_type_overnight');
			let totalForStay = 0;

			try{
				const fltStartEl = document.getElementById('flt_start');
				const fltEndEl = document.getElementById('flt_end');
				const hasFilter = !!(fltStartEl && fltEndEl && fltStartEl.value && fltEndEl.value);
				if (hasFilter) {
					const toLocal = (d)=>{ const tzOff = d.getTimezoneOffset(); const local = new Date(d.getTime() - tzOff*60*1000); return local.toISOString().slice(0,16); };
					const nowLocal = toLocal(new Date());
					bkStart.value = fltStartEl.value;
					bkEnd.value = fltEndEl.value;
					bkStart.min = nowLocal;
					if (bkStart.value < nowLocal) bkStart.value = nowLocal;
					bkEnd.min = bkStart.value;
					if (bkEnd.value < bkStart.value) bkEnd.value = bkStart.value;
				} else {
					const now = new Date(); now.setMinutes(0,0,0); now.setHours(now.getHours()+1);
					const endDefault = new Date(now.getTime() + 24*60*60*1000);
					const toLocal = (d)=>{ const tzOff = d.getTimezoneOffset(); const local = new Date(d.getTime() - tzOff*60*1000); return local.toISOString().slice(0,16); };
					bkStart.value = toLocal(now); bkEnd.value = toLocal(endDefault); bkStart.min = toLocal(new Date()); bkEnd.min = bkStart.value;
				}
			}catch(e){}

			bkStart.addEventListener('input', ()=>{ try { const toLocal = (d)=>{ const tzOff = d.getTimezoneOffset(); const local = new Date(d.getTime() - tzOff*60*1000); return local.toISOString().slice(0,16); }; const nowLocal = toLocal(new Date()); if (bkStart.value && bkStart.value < nowLocal) bkStart.value = nowLocal; bkEnd.min = bkStart.value || ''; if (bkEnd.value && bkStart.value && bkEnd.value < bkStart.value) { bkEnd.value = bkStart.value; } } catch(e) {} refreshAvailability(); });
			bkEnd.addEventListener('input', refreshAvailability);
			document.getElementById('bk_payment').addEventListener('change', refreshAvailability);
			const bkPlanFull = document.getElementById('bk_plan_full');
			const bkPlanDp = document.getElementById('bk_plan_dp');
			const bkDpPercent = document.getElementById('bk_dp_percent');
			function handlePlanChange(){
				if (!bkPlanFull || !bkPlanDp || !bkDpPercent) return;
				bkDpPercent.disabled = bkPlanDp.checked ? false : true;
				refreshAvailability();
			}
			bkPlanFull?.addEventListener('change', handlePlanChange);
			bkPlanDp?.addEventListener('change', handlePlanChange);
			bkDpPercent?.addEventListener('change', handlePlanChange);
			
			// Add event listeners for booking type changes
			bkTypeDay?.addEventListener('change', refreshAvailability);
			bkTypeOvernight?.addEventListener('change', refreshAvailability);
			
			document.getElementById('bk_terms').addEventListener('change', function(){ try { if (this.checked && typeof window.showBookingTerms === 'function') { window.showBookingTerms(); } } catch(e) {} updateSubmitButton(); });
			document.getElementById('bk_submit').addEventListener('click', submitReservation);

			async function refreshAvailability(){
				bkAvail.textContent = ''; bkSubmit.disabled = true; bkAmount.value = '';
				const sVal = bkStart.value; const eVal = bkEnd.value; if(!sVal || !eVal) return;
				
				// Get booking type
				const bookingType = bkTypeDay.checked ? 'day' : 'overnight';
				
				try {
					const params = new URLSearchParams({ 
						facility_name: name, 
						date_start: sVal, 
						date_end: eVal,
						booking_type: bookingType
					});
					const r = await fetch('check_availability.php?' + params.toString(), { credentials:'same-origin', headers:{ 'Accept':'application/json' } });
					const respText = await r.text();
					let j = null; try { j = JSON.parse(respText); } catch(_) {}
					if (!j || typeof j !== 'object') { throw new Error('Server returned an invalid response'); }
					if (j.success) {
						// Auto-set booking type based on facility type
						if (j.facility_type === 'room') {
							// Rooms can be both day-use and overnight
							bkTypeDay.disabled = false;
							bkTypeOvernight.disabled = false;
							document.querySelector('.form-text').textContent = 'Day-use: Pay per hour. Overnight: Pay per day.';
						} else {
							// Tables and cottages are day-use only
							bkTypeDay.checked = true;
							bkTypeDay.disabled = true;
							bkTypeOvernight.disabled = true;
							document.querySelector('.form-text').textContent = 'Day-use only: Pay per hour.';
						}
						
						totalForStay = Number(j.amount)||0;
						let computed = totalForStay;
						try{
							const isDp = document.getElementById('bk_plan_dp')?.checked;
							const pctSel = document.getElementById('bk_dp_percent');
							const pct = Number(pctSel?.value||'30');
							if (isDp && pct>0 && pct<=100) computed = Math.max(0, Math.round((computed * pct/100) * 100) / 100);
						}catch(e){}
						bkAmount.value = computed.toFixed(2);
						if (j.available) { bkAvail.textContent = 'Available'; bkAvail.className='small text-success'; bkSubmit.disabled = false; }
						else { bkAvail.textContent = 'Not available for the selected dates'; bkAvail.className='small text-danger'; bkSubmit.disabled = true; }
					} else {
						bkAvail.textContent = j.message || 'Unable to check availability'; bkAvail.className='small text-warning';
					}
				} catch(e) {
					bkAvail.textContent = (e && e.message) ? e.message : 'Network error while checking availability'; bkAvail.className='small text-warning';
				}
			}

			function updateSubmitButton(){ refreshAvailability(); }

			async function submitReservation(){
				const start = bkStart.value;
				const end = bkEnd.value;
				const payment = document.getElementById('bk_payment').value;
				const terms = document.getElementById('bk_terms').checked;
				const amount = bkAmount.value;
				if (!start || !end || !payment || !terms) {
					Swal.fire({ title: 'Error!', text: 'Please select all options and agree to terms.', icon: 'error', confirmButtonText: 'OK' });
					return;
				}
				const plan = (document.getElementById('bk_plan_dp')?.checked?'Down payment ('+(document.getElementById('bk_dp_percent')?.value||'30')+'%)':'Full payment');
				const confirmHtml = `Book <strong>${name}</strong><br>From ${new Date(start).toLocaleString()}<br>To ${new Date(end).toLocaleString()}<br><em>${plan}</em><br>Pay now: ₱${amount}`;
				const ok = await Swal.fire({ title:'Confirm Booking', html:confirmHtml, icon:'question', showCancelButton:true, confirmButtonText:'Yes, Book It!', cancelButtonText:'Cancel' }).then(r=>r.isConfirmed);
				if (!ok) return;
				try{
					const csrf = (document.querySelector('meta[name="csrf-token"]')?.content)||'';
					const planVal = (document.getElementById('bk_plan_dp')?.checked?'down':'full');
					const downPct = (document.getElementById('bk_dp_percent')?.value||'30');
					const payNow = parseFloat(amount||'0');
					
					// Auto-determine booking type based on facility type
					let bookingType = 'day'; // Default to day-use
					if (bkTypeOvernight && !bkTypeOvernight.disabled && bkTypeOvernight.checked) {
						bookingType = 'overnight';
					}
					
					// Record reservation directly (disabling PayMongo for now)
					const payload = { 
						reservee: CURRENT_FULLNAME, 
						facility_name:name, 
						date_start:start, 
						date_end:end, 
						payment_type:payment, 
						amount: payNow, 
						agree_booking_terms:true, 
						csrf_token: csrf, 
						payment_plan: planVal, 
						down_percent: downPct,
						booking_type: bookingType
					};
					const r = await fetch('submit_reservation.php', { method:'POST', headers:{ 'Content-Type':'application/json', 'Accept':'application/json' }, credentials:'same-origin', body: JSON.stringify(payload) });
					const respText = await r.text(); let j=null; try{ j=JSON.parse(respText);}catch(_){ }
					if (!j || typeof j !== 'object') throw new Error('Server returned an invalid response');
					if (!j.success) throw new Error(j.message||'Failed to save');
					await Swal.fire({ icon:'success', title:'Booking Successful!', timer:1800, showConfirmButton:false });
					window.location.href = 'cusdash.php';
				}catch(e){ Swal.fire({ icon:'error', title: (e&&e.message)?e.message:'Network error' }); }
			}

			refreshAvailability();
		}

		// Removed SweetAlert terms modal; link in label opens terms page in new tab

		// Date filter functionality: auto color pins when both dates are filled
		(function(){
			const fltStart = document.getElementById('flt_start');
			const fltEnd = document.getElementById('flt_end');
			const fltAvailOnly = document.getElementById('flt_avail_only');
			function handleFilterInput(){
				const s = fltStart?.value || '';
				const e = fltEnd?.value || '';
				if (s && e) { colorPinsForRange(s, e); } else { rerenderUI(); }
			}
			fltStart?.addEventListener('input', handleFilterInput);
			fltEnd?.addEventListener('input', handleFilterInput);
			fltAvailOnly?.addEventListener('change', ()=>{
				const s = fltStart?.value || '';
				const e = fltEnd?.value || '';
				if (s && e) colorPinsForRange(s, e);
			});
			// search removed
			handleFilterInput();
		})();
		document.getElementById('btnResetAvail').addEventListener('click', ()=>{
			try{ document.getElementById('flt_start').value=''; document.getElementById('flt_end').value=''; document.getElementById('flt_avail_only').checked=false; document.getElementById('flt_avail_only').disabled=true; }catch(e){}
			rerenderUI();
		});

		async function colorPinsForRange(startVal, endVal){
			if(!startVal || !endVal){ rerenderUI(); return; }
			try{ document.getElementById('mapLoading')?.classList.remove('d-none'); }catch(e){}
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
			const scale = (window.innerWidth < 576 ? PIN_SCALE_MOBILE : PIN_SCALE_DESKTOP);
			const onlyAvail = !!(document.getElementById('flt_avail_only')?.checked);
			facilities.forEach(f=>{
				const isAvail = (f.__rangeAvailable === true);
				if (onlyAvail && !isAvail) return;
				const href = isAvail ? ICON_AVAIL : ICON_UNAVAIL;
				const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
				g.classList.add('facility-pin');
				g.setAttribute('transform', `translate(${f.pin_x}, ${f.pin_y})`);
				const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
				title.textContent = `${f.name||''} — ${isAvail?'available':'unavailable'}`;
				g.appendChild(title);
				const img = document.createElementNS('http://www.w3.org/2000/svg', 'image');
				img.setAttribute('href', href);
				img.setAttribute('x', String(-(PIN_W/2) * scale));
				img.setAttribute('y', String(-PIN_H * scale));
				img.setAttribute('width', String(PIN_W * scale));
				img.setAttribute('height', String(PIN_H * scale));
				img.setAttribute('filter', href===ICON_AVAIL ? 'url(#pinGlowGreen)' : 'url(#pinGlowRed)');
				g.appendChild(img);
				attachPinHandlers(g, f);
				svg.appendChild(g);
			});
			try{
				const s = document.getElementById('flt_start')?.value;
				const e = document.getElementById('flt_end')?.value;
				const toggle = document.getElementById('flt_avail_only');
				if (toggle) toggle.disabled = !(s && e);
				renderList(facilities);
				// Update summary
				const tot = facilities.length;
				const yes = facilities.filter(x=> x.__rangeAvailable===true).length;
				const no = facilities.filter(x=> x.__rangeAvailable===false).length;
				const un = tot - yes - no;
				const sumEl = document.getElementById('rangeSummary');
				if (sumEl) sumEl.innerHTML = `In range: <strong>${yes}</strong> available, <strong>${no}</strong> unavailable, <strong>${un}</strong> unknown`;
			} catch(e){}
			try{ document.getElementById('mapLoading')?.classList.add('d-none'); }catch(e){}
		}

	})();
	</script>
</body>
</html>