<?php require __DIR__ . '/_auth.php'; ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inquiries - Admin</title>
    <link rel="stylesheet" href="../template/assets/vendor/bootstrap/css/bootstrap.min.css">
    <style>
        body { background: #f7f9fb; }
        .card { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
        pre { white-space: pre-wrap; word-break: break-word; }
        .status-badge { text-transform: uppercase; letter-spacing: .3px; }
    </style>
</head>
<body class="p-3">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0">Customer Inquiries</h3>
            <div>
                <select id="filterStatus" class="form-select form-select-sm" style="width: 180px; display: inline-block;">
                    <option value="">All</option>
                    <option value="new">New</option>
                    <option value="read">Read</option>
                </select>
                <button class="btn btn-sm btn-primary ms-2" onclick="loadInquiries()">Refresh</button>
            </div>
        </div>

        <div id="list"></div>
    </div>

    <script>
    function htmlesc(s){ return (s||'').toString().replace(/[&<>\"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c])); }

    async function loadInquiries(){
        const status = document.getElementById('filterStatus').value;
        const res = await fetch('fetch_inquiries.php' + (status? ('?status=' + encodeURIComponent(status)) : ''));
        const data = await res.json();
        const box = document.getElementById('list');
        if (!data.success) { box.innerHTML = '<div class="alert alert-danger">Failed to load inquiries</div>'; return; }
        if (!data.inquiries || data.inquiries.length === 0) { box.innerHTML = '<div class="alert alert-info">No inquiries found.</div>'; return; }
        box.innerHTML = data.inquiries.map(q => renderInquiry(q)).join('');
    }

    function renderInquiry(q){
        const badge = q.status === 'read' ? '<span class="badge bg-secondary status-badge">Read</span>' : '<span class="badge bg-success status-badge">New</span>';
        return `
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5 class="card-title mb-1">${htmlesc(q.name)} ${badge}</h5>
                <div class="text-muted mb-2"><a href="mailto:${htmlesc(q.email)}">${htmlesc(q.email)}</a> • <small>${htmlesc(q.created_at)}</small></div>
              </div>
              <div class="btn-group">
                ${q.status === 'read' ? `<button class="btn btn-sm btn-outline-primary" onclick="markInquiry(${q.id},'unread')">Mark unread</button>` : `<button class="btn btn-sm btn-primary" onclick="markInquiry(${q.id},'read')">Mark read</button>`}
                <button class="btn btn-sm btn-outline-danger" onclick="markInquiry(${q.id},'delete')">Delete</button>
              </div>
            </div>
            <hr/>
            <pre class="mb-0">${htmlesc(q.message)}</pre>
          </div>
        </div>`;
    }

    async function markInquiry(id, action){
        const form = new URLSearchParams();
        form.set('id', id);
        form.set('action', action);
        const res = await fetch('update_inquiry.php', { method:'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: form.toString() });
        const data = await res.json();
        if (!data.success) { alert('Action failed'); return; }
        loadInquiries();
    }

    loadInquiries();
    </script>
    <script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>


