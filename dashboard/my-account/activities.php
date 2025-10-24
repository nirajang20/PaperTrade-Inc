<?php
require "../lib/Core.php";
require PATH_LIB . "PAGE-top.php"; ?>

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

            <!-- Profile -->
            <li class="tl-item alt-left"
                data-module="account" data-type="profile" data-status="success"
                data-action="profile_update" data-symbol=""
                data-device="Chrome · Windows" data-ip=""
                data-date="2025-10-23T11:00:00" data-exact="Oct 23, 2025 · 11:00 AM"
                data-title="User Profile Action" data-notes="Updated profile picture; Changed display name">
              <div class="tl-time"></div>
              <div class="tl-node"></div>
              <div class="tl-card" role="button" tabindex="0">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="tl-title">
                    <i class="fas fa-user-circle mr-2"></i>User Profile Action
                    <span class="badge badge-light text-muted ml-2">My Account</span>
                  </div>
                  <span class="status-badge status-success">Success</span>
                </div>
                <ul class="tl-list">
                  <li>Updated profile picture</li>
                  <li>Changed display name</li>
                </ul>
              </div>
            </li>

            <!-- Trading -->
            <li class="tl-item alt-right"
                data-module="trading" data-type="trading" data-status="success"
                data-action="order_filled" data-symbol="AAPL"
                data-device="iOS App" data-ip=""
                data-date="2025-10-22T18:30:00" data-exact="Oct 22, 2025 · 6:30 PM"
                data-title="Trading Activity Logs" data-notes="Buy/Sell & order changes">
              <div class="tl-card" role="button" tabindex="0">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="tl-title">
                    <i class="fas fa-chart-line mr-2"></i>Trading Activity Logs
                    <span class="badge badge-light text-muted ml-2">Trading</span>
                  </div>
                  <span class="status-badge status-success">Filled</span>
                </div>
                <ul class="tl-list">
                  <li>Buy/Sell & order changes</li>
                  <li>Filled order: <strong>AAPL</strong> 5 @ $176.20 <span class="muted-detail d-none">• order #8743</span></li>
                </ul>
              </div>
              <div class="tl-node"></div>
              <div class="tl-time"></div>
            </li>

            <!-- Achievements -->
            <li class="tl-item alt-left"
                data-module="dashboard" data-type="achievement" data-status="info"
                data-action="milestone" data-symbol=""
                data-device="" data-ip=""
                data-date="2025-10-20T09:10:00" data-exact="Oct 20, 2025 · 9:10 AM"
                data-title="Achievements / Milestones" data-notes="Reached $100k virtual portfolio; 7-day login streak">
              <div class="tl-time"></div>
              <div class="tl-node"></div>
              <div class="tl-card" role="button" tabindex="0">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="tl-title">
                    <i class="fas fa-medal mr-2"></i>Achievements / Milestones
                    <span class="badge badge-light text-muted ml-2">Dashboard</span>
                  </div>
                  <span class="status-badge status-info">Info</span>
                </div>
                <ul class="tl-list">
                  <li>Reached <strong>$100k virtual portfolio</strong></li>
                  <li>7-day login streak</li>
                </ul>
              </div>
            </li>

            <!-- Portfolio -->
            <li class="tl-item alt-right"
                data-module="portfolio" data-type="portfolio" data-status="success"
                data-action="watchlist_add" data-symbol="TSLA"
                data-device="Chrome · macOS" data-ip=""
                data-date="2025-10-16T15:00:00" data-exact="Oct 16, 2025 · 3:00 PM"
                data-title="Portfolio Events" data-notes="Added TSLA to watchlist; Rebalanced holdings (Tech 35% → 28%)">
              <div class="tl-card" role="button" tabindex="0">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="tl-title">
                    <i class="fas fa-chart-pie mr-2"></i>Portfolio Events
                    <span class="badge badge-light text-muted ml-2">Portfolio</span>
                  </div>
                  <span class="status-badge status-success">Success</span>
                </div>
                <ul class="tl-list">
                  <li>Added <strong>TSLA</strong> to watchlist</li>
                  <li>Rebalanced holdings (Tech 35% → 28%)</li>
                </ul>
              </div>
              <div class="tl-node"></div>
              <div class="tl-time"></div>
            </li>

            <!-- Security -->
            <li class="tl-item alt-left"
                data-module="account" data-type="security" data-status="warning"
                data-action="password_change" data-symbol=""
                data-device="Chrome · Windows" data-ip="192.0.2.45"
                data-date="2025-08-12T08:00:00" data-exact="Aug 12, 2025 · 8:00 AM"
                data-title="Security" data-notes="Password updated; New device signed in">
              <div class="tl-time"></div>
              <div class="tl-node"></div>
              <div class="tl-card" role="button" tabindex="0">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="tl-title">
                    <i class="fas fa-shield-alt mr-2"></i>Security
                    <span class="badge badge-light text-muted ml-2">My Account</span>
                  </div>
                  <span class="status-badge status-warning">Important</span>
                </div>
                <ul class="tl-list">
                  <li>Password updated</li>
                  <li>New device signed in <span class="muted-detail">(Chrome · Windows · IP 192.0.2.45)</span></li>
                </ul>
              </div>
            </li>

          </ul>

          <div id="emptyState" class="text-center py-4 d-none">
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
          <dt class="col-4 text-muted">Symbol</dt><dd class="col-8" id="mdSymbol"></dd>
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
  /* connector lines (same both sides) */
  --connector: 96px;
  --thickness: 1.25px;
  --line-color: #cbd5e1;

  /* dot metrics */
  --dot: 22px;
  --dot-border: 3px;
  --dot-outline: 3px;

  /* GAP from spine center to the connector start so it kisses the ring
     OUTER RADIUS = dot/2 + border + outline */
  --spine-gap: calc(var(--dot)/2 + var(--dot-border) + var(--dot-outline));

  --card-bg:#ffffff;
  --card-border:#e5e7eb;
  --card-shadow:rgba(0,0,0,.06);

  /* tints (RGB) */
  --tint-dashboard:37,99,235;
  --tint-trading:22,163,74;
  --tint-portfolio:124,58,237;
  --tint-account:239,68,68;
}
.dark-mode{
  --card-bg:#0f172a;
  --card-border:#273042;
  --card-shadow:rgba(0,0,0,.45);
}

/* Containers */
.activity-container{max-width:100%;padding:0 14px}
.activity-card{background:var(--card-bg);border-color:var(--card-border);box-shadow:0 2px 6px var(--card-shadow)}
.filters-sticky{position:sticky;top:64px;z-index:8}
.activity-viewport{height:calc(100vh - 220px);overflow-y:auto;overflow-x:hidden;padding:16px 16px 24px}

/* Spine */
.activity-timeline-alt{position:relative;list-style:none;margin:0;padding:8px 0 16px}
.activity-timeline-alt::before{
  content:"";position:absolute;left:50%;top:0;transform:translateX(-50%);
  width:80px;height:100%;pointer-events:none;z-index:0;background-repeat:no-repeat;background-position:center top;background-size:100% 100%;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 100' preserveAspectRatio='none'><path d='M40 0 C 15 10, 65 20, 40 30 S 15 45, 40 55 S 65 70, 40 80 S 15 90, 40 100' stroke='%23cbd5e1' stroke-width='3.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/><path d='M40 -2 C 12 8, 68 18, 40 30 S 12 45, 40 55 S 68 70, 40 82 S 12 92, 40 104' stroke='white' stroke-opacity='0.6' stroke-width='1.4' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>");
}

/* Items: time | node | card */
.tl-item{--time-offset:120px;--card-offset:120px;display:grid;grid-template-columns:1fr 28px 1fr;align-items:center;position:relative;margin:44px 0;z-index:1}

/* Time + connector (left side default) */
.tl-time{
  grid-column:1;font-size:.95rem;color:#9aa0a6;text-align:right;padding-right:var(--time-offset);white-space:nowrap;
}
.tl-time::after{
  content:"";position:absolute;top:50%;transform:translateY(-50%);width:var(--connector);height:0;
  border-top:var(--thickness) solid var(--line-color);
  right:calc(50% + var(--spine-gap));
}

/* Node */
.tl-node{
  grid-column:2;width:var(--dot);height:var(--dot);border-radius:50%;
  border:var(--dot-border) solid #fff;box-shadow:0 0 0 var(--dot-outline) rgba(0,0,0,.12);
  justify-self:center;background:#2b6cb0;
}
/* Module node colors */
.tl-item[data-module="dashboard"] .tl-node{background:#2563eb}
.tl-item[data-module="trading"]   .tl-node{background:#16a34a}
.tl-item[data-module="portfolio"] .tl-node{background:#7c3aed}
.tl-item[data-module="account"]   .tl-node{background:#ef4444}

/* Card + connector (right side default) */
.tl-card{
  grid-column:3;background:var(--card-bg);border:1px solid var(--card-border);border-radius:12px;
  padding:10px 12px;max-width:clamp(460px,44vw,640px);
  transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease,background-color .2s ease;
}
.tl-card::before{
  content:"";position:absolute;top:50%;transform:translateY(-50%);width:var(--connector);height:0;
  border-top:var(--thickness) solid var(--line-color);
  left:calc(50% + var(--spine-gap));
}

/* Alternation */
.tl-item.alt-right .tl-card{grid-column:1}
.tl-item.alt-right .tl-time{grid-column:3;text-align:left;padding-right:0;padding-left:var(--time-offset)}
.tl-item.alt-right .tl-time::after{right:auto;left:calc(50% + var(--spine-gap))}
.tl-item.alt-right .tl-card::before{left:auto;right:calc(50% + var(--spine-gap))}
.tl-item.alt-left  .tl-card{margin-left:var(--card-offset)}
.tl-item.alt-right .tl-card{margin-right:var(--card-offset)}

/* Titles & lists */
.tl-title{font-weight:600;color:#111827;margin-bottom:6px}
.dark-mode .tl-title{color:#e5e7eb}
.tl-list{margin:0;padding-left:18px;color:#374151}
.dark-mode .tl-list{color:#cbd5e1}

/* Status badges */
.status-badge{font-size:.72rem;border-radius:999px;padding:.25rem .5rem}
.status-success{background:#eaf7ee;color:#207245}
.status-warning{background:#fff2db;color:#8a5a00}
.status-info{background:#edf2ff;color:#3949ab}
.dark-mode .status-success{background:rgba(34,197,94,.15);color:#86efac}
.dark-mode .status-warning{background:rgba(245,158,11,.15);color:#fbbf24}
.dark-mode .status-info{background:rgba(59,130,246,.15);color:#93c5fd}
.badge.text-muted{border:1px solid var(--card-border)}
.muted-detail{color:#6b7280}.dark-mode .muted-detail{color:#9ca3af}

/* Hover/Active tints */
.tl-card:hover{transform:translateY(-2px)}
.tl-item[data-module="dashboard"] .tl-card:hover{border-color:rgba(var(--tint-dashboard),.35);box-shadow:0 8px 18px rgba(var(--tint-dashboard),.12)}
.tl-item[data-module="trading"]   .tl-card:hover{border-color:rgba(var(--tint-trading),.35);box-shadow:0 8px 18px rgba(var(--tint-trading),.12)}
.tl-item[data-module="portfolio"] .tl-card:hover{border-color:rgba(var(--tint-portfolio),.35);box-shadow:0 8px 18px rgba(var(--tint-portfolio),.12)}
.tl-item[data-module="account"]   .tl-card:hover{border-color:rgba(var(--tint-account),.35);box-shadow:0 8px 18px rgba(var(--tint-account),.12)}
.tl-card.is-active{transform:translateY(-2px)}
.tl-item[data-module="dashboard"] .tl-card.is-active{border-color:rgba(var(--tint-dashboard),.55);box-shadow:0 10px 22px rgba(var(--tint-dashboard),.18)}
.tl-item[data-module="trading"]   .tl-card.is-active{border-color:rgba(var(--tint-trading),.55);box-shadow:0 10px 22px rgba(var(--tint-trading),.18)}
.tl-item[data-module="portfolio"] .tl-card.is-active{border-color:rgba(var(--tint-portfolio),.55);box-shadow:0 10px 22px rgba(var(--tint-portfolio),.18)}
.tl-item[data-module="account"]   .tl-card.is-active{border-color:rgba(var(--tint-account),.55);box-shadow:0 10px 22px rgba(var(--tint-account),.18)}
.tl-card[tabindex="0"]:focus-visible{outline:0;border-color:rgba(var(--tint-dashboard),.45);box-shadow:0 0 0 3px rgba(var(--tint-dashboard),.25)}

/* Mobile */
@media (max-width:768px){
  .activity-viewport{height:calc(100vh - 260px)}
  .tl-item{grid-template-columns:24px 1fr;margin:28px 0}
  .tl-time{grid-column:1 / span 2;order:-1;text-align:left;padding:0 0 6px}
  .tl-node{grid-column:1;justify-self:start}
  .tl-card{grid-column:2;max-width:100%}
  .tl-time::after,.tl-card::before{display:none}
}

/* Filter UI */
.date-range-control{display:flex;align-items:center;height:38px;border:1px solid var(--card-border);border-radius:.25rem;background:var(--card-bg);overflow:hidden;margin-bottom:0}
.dr-icon{width:40px;background:rgba(0,0,0,.04);border-right:1px solid var(--card-border);color:#6b7280;display:flex;align-items:center;justify-content:center}
.dr-input{border:0!important;height:100%;padding:.375rem .5rem;background:transparent;color:inherit}
.dr-sep{padding:0 .5rem;color:#6b7280;border-left:1px solid var(--card-border);border-right:1px solid var(--card-border);display:flex;align-items:center;height:100%}
</style>

<script>
(function(){
  // Relative time (yesterday, 4 days ago, etc.)
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

  // Mask sensitive info
  function applyMask(){
    const on=document.getElementById('maskSensitive').checked;
    document.querySelectorAll('.muted-detail').forEach(e=>e.classList.toggle('d-none',on));
  }

  // Date presets
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

  // Active highlight helpers
  function clearActiveCards(){
    document.querySelectorAll('#activityList .tl-card.is-active').forEach(c=>c.classList.remove('is-active'));
  }
  function setActiveCard(li){
    clearActiveCards();
    const card=li.querySelector('.tl-card'); if(card) card.classList.add('is-active');
  }

  // Filters
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
  }

  document.getElementById('btnApply').addEventListener('click', applyFilters);
  document.getElementById('btnReset').addEventListener('click', ()=>{
    document.getElementById('fltStatus').value='all';
    document.getElementById('fltSearch').value='';
    document.getElementById('fltStart').value='';
    document.getElementById('fltEnd').value='';
    document.querySelectorAll('#activityList .tl-item').forEach(el=>el.classList.remove('d-none'));
    document.getElementById('emptyState').classList.add('d-none');
    applyMask(); clearActiveCards();
  });
  document.getElementById('maskSensitive').addEventListener('change', applyMask);

  // Card → Details modal + highlight + keyboard
  document.querySelectorAll('#activityList .tl-card').forEach(card=>{
    const activate = ()=>{
      const li = card.closest('.tl-item');
      setActiveCard(li);

      const get = (k)=> li.dataset[k] || '—';
      const cap = s => s ? s.charAt(0).toUpperCase()+s.slice(1) : '—';
      document.getElementById('mdTitle').textContent  = get('title');
      
      document.getElementById('mdStatus').textContent = cap(get('status'));
      document.getElementById('mdSymbol').textContent = get('symbol') || '—';
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

  // Init
  renderTimes(); applyMask(); setInterval(renderTimes, 60000);

  // Ensure active tint clears after modal closes in ANY way + click-away
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
<!-- ui git push bnfnbcvhmn-->
<?php require PATH_LIB . "PAGE-bottom.php"; ?>
