<?php
require "../lib/Core.php";
require PATH_LIB . "PAGE-top.php";

/*
  1) GET CURRENT USER
     - if your login stores email instead, change this line
     - example in your other file: $active_email = $_SESSION['active_email'];
*/
$current_user_id = $_SESSION['user_id'] ?? null; // <-- change if needed

/*
  2) FETCH ACTIVITIES FROM DB
     Right now: show ALL so you can verify.
     Later: add "WHERE user_id = ?" to show only that user's data.
*/
$sql = "
  SELECT
    id,
    action_title,
    page_name,
    notes,
    status,
    device,
    ipv4,
    point_color,
    user_id,
    created_at
  FROM activities
  ORDER BY created_at DESC, id DESC
  LIMIT 150
";
$res = $conn->query($sql);

$activities = [];
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $activities[] = $row;
  }
}

// status map for badges (db → css)
$statusBadgeMap = [
  'Success' => ['class' => 'status-success', 'label' => 'Success'],
  'Pending' => ['class' => 'status-warning', 'label' => 'Pending'],
  'Failed'  => ['class' => 'status-warning', 'label' => 'Failed'],
  'Warning' => ['class' => 'status-warning', 'label' => 'Warning'],
];

// helper so we don’t get XSS
if (!function_exists('activity_escape')) {
  function activity_escape($value)
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}
?>

<section class="content-header">
  <div class="container activity-container"></div>
</section>

<section class="content">
  <div class="container activity-container">

    <!-- ===== Filters ===== -->
    <div class="card mb-3 shadow-sm filters-sticky">
      <div class="card-body">
        <form id="filtersForm">
          <div class="form-row align-items-start">
            <!-- Date Range -->
            <div class="form-group col-12 col-md-6 col-lg-4">
              <label class="small text-muted d-block">Date range</label>
              <div class="date-range-control">
                <span class="dr-icon"><i class="far fa-calendar-alt"></i></span>
                <input type="date" class="form-control dr-input" id="fltStart" placeholder="mm/dd/yyyy">
                <span class="dr-sep">to</span>
                <input type="date" class="form-control dr-input" id="fltEnd" placeholder="mm/dd/yyyy">
              </div>
              <div class="preset-row small mt-1">
                <a href="#" data-preset="7d"  class="mr-2 preset-link">Last 7d</a>
                <a href="#" data-preset="30d" class="mr-2 preset-link">30d</a>
                <a href="#" data-preset="ytd" class="preset-link">YTD</a>
              </div>
            </div>

            <!-- Status -->
            <div class="form-group col-12 col-md-6 col-lg-4">
              <label class="small text-muted d-block">Status</label>
              <select id="fltStatus" class="form-control">
                <option value="all">All</option>
                <option value="success">Success</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
                <option value="warning">Warning</option>
                <option value="info">Info</option>
              </select>
            </div>

            <!-- Search -->
            <div class="form-group col-12 col-md-6 col-lg-4">
              <label class="small text-muted d-block">Search</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input id="fltSearch" type="text" class="form-control" placeholder="Symbol, action, device, etc.">
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex flex-wrap justify-content-end">
            <button type="button" class="btn btn-primary btn-sm mr-2" id="btnApply">
              <i class="fas fa-filter mr-1"></i>Apply
            </button>
            <div class="d-flex align-items-center">
              <button type="button" class="btn btn-outline-secondary btn-sm mr-3" id="btnReset">Reset</button>
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="maskSensitive" checked>
                <label class="custom-control-label" for="maskSensitive">Mask sensitive details</label>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
    <!-- ===== /Filters ===== -->

    <!-- ===== Timeline ===== -->
    <div class="card card-outline activity-card">
      <div class="card-body p-0">
        <div class="activity-viewport">

          <ul class="activity-timeline-alt" id="activityList">
            <?php if (count($activities) === 0): ?>
              <!-- we’ll also show empty state below via JS -->
            <?php else: ?>
              <?php foreach ($activities as $index => $row): ?>
                <?php
                  // alternate left/right
                  $alignClass = ($index % 2 === 0) ? 'alt-left' : 'alt-right';

                  // status badge
                  $dbStatus = $row['status'] ?? 'Success';
                  $map = $statusBadgeMap[$dbStatus] ?? ['class' => 'status-info', 'label' => $dbStatus];

                  // dates
                  $createdRaw = $row['created_at'] ?? '';
                  $dateIso    = $createdRaw ? date('c', strtotime($createdRaw)) : '';
                  $dateExact  = $createdRaw ? date('M j, Y - g:i A', strtotime($createdRaw)) : '';

                  // guess module → for dot color
                  $module = 'dashboard';
                  $page   = strtolower($row['page_name'] ?? '');
                  $title  = strtolower($row['action_title'] ?? '');

                  if (strpos($page, 'trade') !== false || strpos($title, 'trade') !== false) {
                    $module = 'trading';
                  } elseif (strpos($page, 'portfolio') !== false) {
                    $module = 'portfolio';
                  } elseif (
                    strpos($page, 'setting') !== false ||
                    strpos($page, 'login') !== false ||
                    strpos($page, 'security') !== false
                  ) {
                    $module = 'account';
                  }

                  // dot color from db or default
                  $dotColor = $row['point_color'] ?: '#4a90e2';
                ?>

                <li class="tl-item <?= activity_escape($alignClass); ?>"
                    data-module="<?= activity_escape($module); ?>"
                    data-type="<?= activity_escape($module); ?>"
                    data-status="<?= activity_escape(strtolower($dbStatus)); ?>"
                    data-action="<?= activity_escape($row['action_title']); ?>"
                    data-device="<?= activity_escape($row['device']); ?>"
                    data-ip="<?= activity_escape($row['ipv4']); ?>"
                    data-date="<?= activity_escape($dateIso); ?>"
                    data-exact="<?= activity_escape($dateExact); ?>"
                    data-title="<?= activity_escape($row['action_title']); ?>"
                    data-notes="<?= activity_escape($row['notes']); ?>">

                  <?php if ($alignClass === 'alt-left'): ?>
                    <div class="tl-time"></div>
                    <div class="tl-node" style="background: <?= activity_escape($dotColor); ?>;"></div>
                    <div class="tl-card" role="button" tabindex="0">
                      <div class="d-flex align-items-center justify-content-between">
                        <div class="tl-title">
                          <?= activity_escape($row['action_title']); ?>
                          <span class="badge badge-light text-muted ml-2">
                            <?= activity_escape($row['page_name']); ?>
                          </span>
                        </div>
                        <span class="status-badge <?= activity_escape($map['class']); ?>"><?= activity_escape($map['label']); ?></span>
                      </div>
                      <?php if (!empty($row['notes'])): ?>
                        <ul class="tl-list">
                          <li><?= activity_escape($row['notes']); ?></li>
                        </ul>
                      <?php endif; ?>
                      <p class="muted-detail small mb-0 mt-2">
                        User #<?= (int)$row['user_id']; ?> · <?= activity_escape($row['device'] ?: 'Unknown'); ?> · <?= activity_escape($row['ipv4'] ?: '—'); ?>
                      </p>
                    </div>
                  <?php else: ?>
                    <div class="tl-card" role="button" tabindex="0">
                      <div class="d-flex align-items-center justify-content-between">
                        <div class="tl-title">
                          <?= activity_escape($row['action_title']); ?>
                          <span class="badge badge-light text-muted ml-2">
                            <?= activity_escape($row['page_name']); ?>
                          </span>
                        </div>
                        <span class="status-badge <?= activity_escape($map['class']); ?>"><?= activity_escape($map['label']); ?></span>
                      </div>
                      <?php if (!empty($row['notes'])): ?>
                        <ul class="tl-list">
                          <li><?= activity_escape($row['notes']); ?></li>
                        </ul>
                      <?php endif; ?>
                      <p class="muted-detail small mb-0 mt-2">
                        User #<?= (int)$row['user_id']; ?> · <?= activity_escape($row['device'] ?: 'Unknown'); ?> · <?= activity_escape($row['ipv4'] ?: '—'); ?>
                      </p>
                    </div>
                    <div class="tl-node" style="background: <?= activity_escape($dotColor); ?>;"></div>
                    <div class="tl-time"></div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>

          <div id="emptyState" class="text-center py-4 <?= count($activities) ? 'd-none' : ''; ?>">
            <p class="mb-1">No activity matches your filters.</p>
            <small class="text-muted">Try widening the date range or adjusting your search.</small>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ===== Event Details Modal ===== -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventModalLabel">Event details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0 small">
          <dt class="col-4 text-muted">Title</dt><dd class="col-8" id="mdTitle"></dd>
          <dt class="col-4 text-muted">Status</dt><dd class="col-8" id="mdStatus"></dd>
          <dt class="col-4 text-muted">When</dt><dd class="col-8" id="mdWhen"></dd>
          <dt class="col-4 text-muted">Device</dt><dd class="col-8" id="mdDevice"></dd>
          <dt class="col-4 text-muted">IP</dt><dd class="col-8" id="mdIP"></dd>
          <dt class="col-4 text-muted">Notes</dt><dd class="col-8" id="mdNotes"></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<style>
/* ===== Theme tokens & alignment math ===== */
:root{
  --connector: 96px;
  --thickness: 1.25px;
  --line-color: #cbd5e1;
  --dot: 22px;
  --dot-border: 3px;
  --dot-outline: 3px;
  --spine-gap: calc(var(--dot)/2 + var(--dot-border) + var(--dot-outline));
  --card-bg:#ffffff;
  --card-border:#e5e7eb;
  --card-shadow:rgba(0,0,0,.06);
  --tint-dashboard:34,197,94;
  --tint-trading:251,191,36;
  --tint-portfolio:14,165,233;
  --tint-account:239,68,68;
}
.dark-mode{
  --card-bg:#0f172a;
  --card-border:#273042;
  --card-shadow:rgba(0,0,0,.45);
}
.activity-container{max-width:100%;padding:0 14px}
.activity-card{background:var(--card-bg);border-color:var(--card-border);box-shadow:0 2px 6px var(--card-shadow)}
.filters-sticky{position:relative;top:auto;z-index:1}
.activity-viewport{height:calc(100vh - 220px);overflow-y:auto;overflow-x:hidden;padding:16px 16px 24px}
.activity-timeline-alt{position:relative;list-style:none;margin:0;padding:8px 0 16px}
.activity-timeline-alt::before{
  content:"";position:absolute;left:50%;top:0;transform:translateX(-50%);
  width:80px;height:100%;pointer-events:none;z-index:0;background-repeat:repeat-y;background-position:center top;background-size:100% 260px;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 100' preserveAspectRatio='none'><path d='M40 0 C 15 10, 65 20, 40 30 S 15 45, 40 55 S 65 70, 40 80 S 15 90, 40 100' stroke='%23cbd5e1' stroke-width='3.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/><path d='M40 -2 C 12 8, 68 18, 40 30 S 12 45, 40 55 S 68 70, 40 82 S 12 92, 40 104' stroke='white' stroke-opacity='0.6' stroke-width='1.4' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>");
}
.tl-item{--time-offset:120px;--card-offset:120px;--node-offset-x:0px;--node-offset-y:0px;display:grid;grid-template-columns:1fr 28px 1fr;align-items:center;position:relative;margin:44px 0;z-index:1}
.tl-time{grid-column:1;font-size:.95rem;color:#9aa0a6;text-align:right;padding-right:var(--time-offset);white-space:nowrap;}
.tl-time::after{
  content:"";position:absolute;top:calc(50% + var(--node-offset-y,0px));transform:translateY(-50%);width:var(--connector);height:0;
  border-top:var(--thickness) solid var(--line-color);
  right:calc(50% + var(--spine-gap) - var(--node-offset-x,0px));
}
.tl-node{
  grid-column:2;width:var(--dot);height:var(--dot);border-radius:50%;
  border:var(--dot-border) solid #fff;box-shadow:0 0 0 var(--dot-outline) rgba(0,0,0,.12);
  justify-self:center;background:#2b6cb0;transform:translate(var(--node-offset-x,0px),var(--node-offset-y,0px));
}
.tl-item[data-module="dashboard"] .tl-node{background:#22c55e}
.tl-item[data-module="trading"]   .tl-node{background:#fbbf24}
.tl-item[data-module="portfolio"] .tl-node{background:#0ea5e9}
.tl-item[data-module="account"]   .tl-node{background:#ef4444}
.tl-card{
  grid-column:3;background:var(--card-bg);border:1px solid var(--card-border);border-radius:12px;
  padding:10px 12px;max-width:clamp(460px,44vw,640px);
  transition:box-shadow .2s ease,border-color .2s ease,background-color .2s ease;
}
.tl-card::before{
  content:"";position:absolute;top:calc(50% + var(--node-offset-y,0px));transform:translateY(-50%);width:var(--connector);height:0;
  border-top:var(--thickness) solid var(--line-color);
  left:calc(50% + var(--spine-gap) + var(--node-offset-x,0px));
}
.tl-item.alt-right .tl-card{grid-column:1}
.tl-item.alt-right .tl-time{grid-column:3;text-align:left;padding-right:0;padding-left:var(--time-offset)}
.tl-item.alt-right .tl-time::after{right:auto;left:calc(50% + var(--spine-gap) + var(--node-offset-x,0px))}
.tl-item.alt-right .tl-card::before{left:auto;right:calc(50% + var(--spine-gap) - var(--node-offset-x,0px))}
.tl-item.alt-left  .tl-card{margin-left:var(--card-offset)}
.tl-item.alt-right .tl-card{margin-right:var(--card-offset)}
.tl-title{font-weight:600;color:#111827;margin-bottom:6px}
.dark-mode .tl-title{color:#e5e7eb}
.tl-list{margin:0;padding-left:18px;color:#374151}
.dark-mode .tl-list{color:#cbd5e1}
.status-badge{font-size:.72rem;border-radius:999px;padding:.25rem .5rem}
.status-success{background:#eaf7ee;color:#207245}
.status-warning{background:#fff2db;color:#8a5a00}
.status-info{background:#edf2ff;color:#3949ab}
.dark-mode .status-success{background:rgba(34,197,94,.15);color:#86efac}
.dark-mode .status-warning{background:rgba(245,158,11,.15);color:#fbbf24}
.dark-mode .status-info{background:rgba(59,130,246,.15);color:#93c5fd}
.badge.text-muted{border:1px solid var(--card-border)}
.muted-detail{color:#6b7280}.dark-mode .muted-detail{color:#9ca3af}
.tl-card:hover{transform:none}
.tl-item[data-module="dashboard"] .tl-card:hover{border-color:rgba(var(--tint-dashboard),.35);box-shadow:0 8px 18px rgba(var(--tint-dashboard),.12)}
.tl-item[data-module="trading"]   .tl-card:hover{border-color:rgba(var(--tint-trading),.35);box-shadow:0 8px 18px rgba(var(--tint-trading),.12)}
.tl-item[data-module="portfolio"] .tl-card:hover{border-color:rgba(var(--tint-portfolio),.35);box-shadow:0 8px 18px rgba(var(--tint-portfolio),.12)}
.tl-item[data-module="account"]   .tl-card:hover{border-color:rgba(var(--tint-account),.35);box-shadow:0 8px 18px rgba(var(--tint-account),.12)}
.tl-card.is-active{transform:none}
.tl-item[data-module="dashboard"] .tl-card.is-active{border-color:rgba(var(--tint-dashboard),.55);box-shadow:0 10px 22px rgba(var(--tint-dashboard),.18)}
.tl-item[data-module="trading"]   .tl-card.is-active{border-color:rgba(var(--tint-trading),.55);box-shadow:0 10px 22px rgba(var(--tint-trading),.18)}
.tl-item[data-module="portfolio"] .tl-card.is-active{border-color:rgba(var(--tint-portfolio),.55);box-shadow:0 10px 22px rgba(var(--tint-portfolio),.18)}
.tl-item[data-module="account"]   .tl-card.is-active{border-color:rgba(var(--tint-account),.55);box-shadow:0 10px 22px rgba(var(--tint-account),.18)}
.tl-card[tabindex="0"]:focus-visible{outline:0;border-color:rgba(var(--tint-dashboard),.45);box-shadow:0 0 0 3px rgba(var(--tint-dashboard),.25)}
@media (max-width:768px){
  .activity-viewport{height:calc(100vh - 260px);overflow-y:auto}
  .tl-item{grid-template-columns:24px 1fr;margin:28px 0}
  .tl-time{grid-column:1 / span 2;order:-1;text-align:left;padding:0 0 6px}
  .tl-node{grid-column:1;justify-self:start}
  .tl-card{grid-column:2;max-width:100%}
  .tl-time::after,.tl-card::before{display:none}
}
.date-range-control{display:flex;align-items:center;height:38px;border:1px solid var(--card-border);border-radius:.25rem;background:var(--card-bg);overflow:hidden;margin-bottom:0}
.dr-icon{width:40px;background:rgba(0,0,0,.04);border-right:1px solid var(--card-border);color:#6b7280;display:flex;align-items:center;justify-content:center}
.dr-input{border:0!important;height:100%;padding:.375rem .5rem;background:transparent;color:inherit}
.dr-sep{padding:0 .5rem;color:#6b7280;border-left:1px solid var(--card-border);border-right:1px solid var(--card-border);display:flex;align-items:center;height:100%}
</style>

<script>
(function(){
  const raf = window.requestAnimationFrame || (cb => setTimeout(cb,0));
  const caf = window.cancelAnimationFrame || (id => clearTimeout(id));
  const rtf = new Intl.RelativeTimeFormat(undefined,{numeric:'auto'});
  const UNITS=[['year',31536e6],['month',2592e6],['week',6048e5],['day',864e5],['hour',36e5],['minute',6e4],['second',1e3]];
  function ago(ms){const d=Date.now()-ms;for(const [u,s] of UNITS){if(Math.abs(d)>=s||u==='second')return rtf.format(Math.trunc(-d/s),u)}}
  function renderTimes(){
    document.querySelectorAll('#activityList .tl-item').forEach(li=>{
      const el=li.querySelector('.tl-time'); const iso=li.dataset.date;
      if(!iso||!el) return; const ms=Date.parse(iso);
      el.textContent = isNaN(ms)?'':ago(ms);
      el.title = li.dataset.exact || new Date(iso).toLocaleString();
    });
  }
  function applyMask(){
    const on=document.getElementById('maskSensitive').checked;
    document.querySelectorAll('.muted-detail').forEach(e=>e.classList.toggle('d-none',on));
  }
  document.querySelectorAll('.preset-link').forEach(a=>{
    a.addEventListener('click',e=>{
      e.preventDefault(); const t=new Date(); let s;
      if(a.dataset.preset==='7d') s=new Date(t.getFullYear(),t.getMonth(),t.getDate()-7);
      if(a.dataset.preset==='30d') s=new Date(t.getFullYear(),t.getMonth(),t.getDate()-30);
      if(a.dataset.preset==='ytd') s=new Date(t.getFullYear(),0,1);
      document.getElementById('fltStart').value=s.toISOString().slice(0,10);
      document.getElementById('fltEnd').value=t.toISOString().slice(0,10);
    });
  });
  function clearActiveCards(){
    document.querySelectorAll('#activityList .tl-card.is-active').forEach(c=>c.classList.remove('is-active'));
  }
  function setActiveCard(li){
    clearActiveCards();
    const card=li.querySelector('.tl-card'); if(card) card.classList.add('is-active');
  }
  function reflowAlternation(){
    caf(reflowAlternation._raf || 0);
    reflowAlternation._raf = raf(()=>{
      const items = Array.from(document.querySelectorAll('#activityList .tl-item'));
      items.forEach(li=>li.classList.remove('alt-left','alt-right'));
      let idx=0;
      items.forEach(li=>{
        if(li.classList.contains('d-none')) return;
        const alignLeft = idx % 2 === 0;
        li.classList.add(alignLeft ? 'alt-left' : 'alt-right');
        const timeEl = li.querySelector('.tl-time');
        const nodeEl = li.querySelector('.tl-node');
        const cardEl = li.querySelector('.tl-card');
        if (timeEl && nodeEl && cardEl){
          const order = alignLeft ? [timeEl, nodeEl, cardEl] : [cardEl, nodeEl, timeEl];
          order.forEach(child => li.appendChild(child));
        }
        idx++;
      });
    });
  }
  function applyFilters(){
    const status = document.getElementById('fltStatus').value;
    const q      = (document.getElementById('fltSearch').value||'').toLowerCase();
    const sVal   = document.getElementById('fltStart').value;
    const eVal   = document.getElementById('fltEnd').value;
    const sMs = sVal? Date.parse(sVal+'T00:00:00'):null;
    const eMs = eVal? Date.parse(eVal+'T23:59:59'):null;
    let shown=0;
    document.querySelectorAll('#activityList .tl-item').forEach(li=>{
      const liStatus=li.dataset.status;
      const text=li.innerText.toLowerCase(); const liMs=Date.parse(li.dataset.date);
      let ok=true;
      if(status!=='all' && liStatus!==status) ok=false;
      if(sMs && !(liMs>=sMs)) ok=false;
      if(eMs && !(liMs<=eMs)) ok=false;
      if(q && !text.includes(q)) ok=false;
      li.classList.toggle('d-none', !ok);
      if(ok) shown++;
    });
    document.getElementById('emptyState').classList.toggle('d-none', shown!==0);
    clearActiveCards();
    reflowAlternation();
  }
  document.getElementById('btnApply').addEventListener('click', applyFilters);
  document.getElementById('fltStatus').addEventListener('change', applyFilters);
  document.getElementById('btnReset').addEventListener('click', ()=>{
    document.getElementById('fltStatus').value='all';
    document.getElementById('fltSearch').value='';
    document.getElementById('fltStart').value='';
    document.getElementById('fltEnd').value='';
    document.querySelectorAll('#activityList .tl-item').forEach(el=>el.classList.remove('d-none'));
    document.getElementById('emptyState').classList.add('d-none');
    reflowAlternation();
    applyMask(); clearActiveCards();
  });
  document.getElementById('maskSensitive').addEventListener('change', applyMask);

  document.querySelectorAll('#activityList .tl-card').forEach(card=>{
    const activate = ()=>{
      const li = card.closest('.tl-item');
      setActiveCard(li);
      const get = (k)=> li.dataset[k] || '—';
      const cap = s => s ? s.charAt(0).toUpperCase()+s.slice(1) : '—';
      document.getElementById('mdTitle').textContent  = get('title');
      document.getElementById('mdStatus').textContent = cap(get('status'));
      document.getElementById('mdWhen').textContent   = li.dataset.exact || new Date(li.dataset.date).toLocaleString();
      document.getElementById('mdDevice').textContent = get('device');
      document.getElementById('mdIP').textContent     = (document.getElementById('maskSensitive').checked ? '• • •' : (get('ip') || '—'));
      document.getElementById('mdNotes').textContent  = get('notes');
      $('#eventModal').modal('show');
    };
    card.addEventListener('click', activate);
    card.addEventListener('keydown', (e)=>{
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(); }
    });
  });

  reflowAlternation();
  renderTimes(); applyMask(); setInterval(renderTimes, 60000);

  (function(){
    const clearActive = () => {
      document.querySelectorAll('#activityList .tl-card.is-active')
        .forEach(el => el.classList.remove('is-active'));
    };
    $('#eventModal').on('hide.bs.modal hidden.bs.modal', clearActive);
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.tl-card') && !e.target.closest('.modal')) clearActive();
    });
  })();
})();
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>


