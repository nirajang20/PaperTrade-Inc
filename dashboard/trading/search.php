<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";

$defaultSymbol = "AAPL";
$spotlightSymbols = [
  "AAPL",
  "NVDA",
  "MSFT",
  "TSLA",
  "META",
  "AMZN",
  "BTC-USD",
  "ETH-USD",
  "^GSPC"
];

$curatedCollections = [
  "US Tech Leaders" => ["AAPL", "MSFT", "NVDA", "GOOGL", "META"],
  "Growth & Disruptors" => ["TSLA", "AMD", "SHOP", "ROKU", "SQ"],
  "Global Indices" => ["^GSPC", "^DJI", "^IXIC", "^FTSE", "^N225"],
  "Crypto Majors" => ["BTC-USD", "ETH-USD", "SOL-USD", "XRP-USD", "DOGE-USD"]
];
?>

<section class="content">
  <div class="container-fluid">

    <!-- SEARCH HERO -->
    <div class="row">
      <div class="col-12">
        <div class="card card-outline card-danger market-search-hero">
          <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center">
              <div class="mb-4 mb-lg-0">
                <h1 class="display-6 font-weight-bold mb-1 text-danger">Market Explorer</h1>
                <p class="text-muted mb-0">Search any global symbol to reveal real-time quotes, deep metrics, and adaptive charts.</p>
              </div>
              <form id="market-search-form" class="w-100 w-lg-50">
                <div class="input-group shadow-sm">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-danger text-white border-danger"><i class="fas fa-search"></i></span>
                  </div>
                  <input type="search"
                         class="form-control form-control-lg border-danger"
                         id="market-search-input"
                         placeholder="Try NVDA, MSFT, BTC-USD, ^GSPC…"
                         aria-label="Search by ticker symbol"
                         autocomplete="off"
                         required>
                  <div class="input-group-append">
                    <button class="btn btn-danger btn-lg font-weight-semibold" type="submit">Go</button>
                  </div>
                </div>
                <small id="search-feedback" class="form-text mt-2 text-muted d-none"></small>
              </form>
            </div>

            <div class="mt-4">
              <span class="text-uppercase text-muted small font-weight-bold mr-3">Spotlight</span>
              <?php foreach ($spotlightSymbols as $idx => $symbol) { ?>
                <button class="btn btn-outline-danger btn-sm mb-2 mr-2 quick-symbol-pill"
                        data-search-symbol="<?=htmlspecialchars($symbol, ENT_QUOTES); ?>">
                  <?=htmlspecialchars($symbol); ?>
                </button>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- LEFT COLUMN -->
      <div class="col-xl-4 col-lg-5">
        <div class="card card-outline card-danger" id="symbol-overview-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
              <div class="mb-3">
                <div class="d-flex align-items-center">
                  <h2 class="mb-0 font-weight-bold" id="overview-symbol">—</h2>
                  <span class="badge badge-pill badge-light ml-2" id="overview-exchange">—</span>
                </div>
                <p class="text-muted mb-2" id="overview-company">Search a symbol to get started.</p>
                <div id="overview-tags" class="mb-3"></div>
              </div>
              <div class="text-right">
                <p class="text-muted mb-1 small">Last Price</p>
                <div class="display-4 font-weight-bold" id="overview-price">—</div>
                <div id="overview-change" class="small font-weight-semibold">—</div>
              </div>
            </div>

            <div class="market-summary-grid mt-3">
              <div>
                <span class="summary-label">Open</span>
                <span class="summary-value" id="metric-open">—</span>
              </div>
              <div>
                <span class="summary-label">Previous Close</span>
                <span class="summary-value" id="metric-previous-close">—</span>
              </div>
              <div>
                <span class="summary-label">Day Range</span>
                <span class="summary-value" id="metric-day-range">—</span>
              </div>
              <div>
                <span class="summary-label">52 Week Range</span>
                <span class="summary-value" id="metric-52w-range">—</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card card-outline card-primary">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Key Stats</h3>
          </div>
          <div class="card-body">
            <dl class="row mb-0 market-stats-list">
              <dt class="col-6">Volume</dt>
              <dd class="col-6 text-right" id="metric-volume">—</dd>
              <dt class="col-6">Avg Volume</dt>
              <dd class="col-6 text-right" id="metric-avg-volume">—</dd>
              <dt class="col-6">Market Cap</dt>
              <dd class="col-6 text-right" id="metric-market-cap">—</dd>
              <dt class="col-6">P/E Ratio</dt>
              <dd class="col-6 text-right" id="metric-pe">—</dd>
              <dt class="col-6">VWAP</dt>
              <dd class="col-6 text-right" id="metric-vwap">—</dd>
              <dt class="col-6">RSI</dt>
              <dd class="col-6 text-right" id="metric-rsi">—</dd>
            </dl>
          </div>
        </div>

        <div class="card card-outline card-secondary">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Curated Collections</h3>
          </div>
          <div class="card-body pb-2">
            <?php foreach ($curatedCollections as $label => $symbols) { ?>
              <div class="mb-3">
                <div class="d-flex align-items-center mb-2">
                  <span class="badge badge-secondary badge-soft mr-2"><i class="fas fa-layer-group mr-1"></i><?=htmlspecialchars($label); ?></span>
                  <hr class="flex-grow-1 my-0 border-secondary" />
                </div>
                <?php foreach ($symbols as $symbol) { ?>
                  <button class="btn btn-outline-secondary btn-xs mr-2 mb-2 collection-symbol-pill"
                          data-search-symbol="<?=htmlspecialchars($symbol, ENT_QUOTES); ?>">
                    <?=htmlspecialchars($symbol); ?>
                  </button>
                <?php } ?>
              </div>
            <?php } ?>
          </div>
        </div>

        <div class="card card-outline card-info">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Live Movers</h3>
            <span class="text-muted small" id="movers-timestamp">Updated —</span>
          </div>
          <div class="card-body p-0">
            <div id="trending-movers" class="list-group list-group-flush">
              <div class="list-group-item text-center text-muted py-4">Fetching market snapshots…</div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="col-xl-8 col-lg-7">
        <div class="card card-outline card-dark">
          <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
              <h3 class="card-title mb-0" id="chart-title">Price &amp; Volume</h3>
              <small class="text-muted" id="chart-subtitle">Search to load a chart.</small>
            </div>
            <div class="btn-group btn-group-sm mt-3 mt-md-0" role="group" aria-label="Chart mode" id="chart-mode-group">
              <button type="button" class="btn btn-outline-dark active" data-chart-mode="candles"><i class="far fa-chart-bar mr-1"></i>Candles</button>
              <button type="button" class="btn btn-outline-dark" data-chart-mode="line"><i class="fas fa-wave-square mr-1"></i>Line</button>
            </div>
          </div>
          <div class="card-body">
            <div id="market-chart" class="market-chart"></div>
          </div>
          <div class="card-footer bg-white">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
              <div class="flex-grow-1 d-flex justify-content-center mb-3 mb-md-0">
                <div class="btn-group btn-group-sm" role="group" aria-label="Range selector" id="chart-range-buttons">
                  <button type="button" class="btn btn-outline-secondary active" data-range="1D">1D</button>
                  <button type="button" class="btn btn-outline-secondary" data-range="5D">5D</button>
                  <button type="button" class="btn btn-outline-secondary" data-range="1M">1M</button>
                  <button type="button" class="btn btn-outline-secondary" data-range="3M">3M</button>
                  <button type="button" class="btn btn-outline-secondary" data-range="6M">6M</button>
                  <button type="button" class="btn btn-outline-secondary" data-range="1Y">1Y</button>
                  <button type="button" class="btn btn-outline-secondary" data-range="MAX">MAX</button>
                </div>
              </div>
              <div class="text-muted small text-center text-md-right ml-md-3" id="last-updated-label"><i class="far fa-clock mr-1"></i>Updated —</div>
            </div>
          </div>
        </div>

        <div class="card card-outline card-primary">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Momentum Insights</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="insight-chip">
                  <span class="insight-label">RSI Signal</span>
                  <span class="insight-value" id="insight-rsi">—</span>
                  <small class="text-muted d-block">Relative Strength Index over the last 14 sessions.</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="insight-chip">
                  <span class="insight-label">VWAP</span>
                  <span class="insight-value" id="insight-vwap">—</span>
                  <small class="text-muted d-block">Volume-weighted average price for the current session.</small>
                </div>
              </div>
              <div class="col-md-12 mt-3">
                <div class="insight-chip">
                  <span class="insight-label">Session Commentary</span>
                  <span class="insight-commentary" id="insight-commentary">Add a symbol to generate context based on live metrics.</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</section>

<style>
  .market-search-hero {
    background: radial-gradient(circle at top right, rgba(220,53,69,0.08), transparent 60%);
  }
  .market-summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }
  .market-summary-grid .summary-label {
    display: block;
    font-size: 0.75rem;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #6c757d;
  }
  .market-summary-grid .summary-value {
    font-size: 1.1rem;
    font-weight: 600;
  }
  .market-stats-list dt {
    font-size: 0.85rem;
    color: #6c757d;
  }
  .market-stats-list dd {
    font-size: 1rem;
    font-weight: 600;
  }
  .market-chart {
    height: 420px;
  }
  .quick-symbol-pill,
  .collection-symbol-pill {
    border-radius: 999px;
    font-weight: 600;
    letter-spacing: 0.03em;
  }
  .collection-symbol-pill {
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
  }
  .collection-symbol-pill.active,
  .quick-symbol-pill.active {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
  }
  .badge-soft {
    background: rgba(108,117,125,0.12);
    color: #343a40;
  }
  .market-mover-symbol {
    font-weight: 700;
    letter-spacing: 0.02em;
  }
  .insight-chip {
    background: rgba(0,123,255,0.05);
    border-radius: 16px;
    padding: 16px;
  }
  .insight-label {
    display: block;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.06em;
    color: #6c757d;
  }
  .insight-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: #0056b3;
  }
  .insight-commentary {
    display: block;
    font-size: 0.95rem;
    color: #343a40;
    font-weight: 500;
  }
  #chart-range-buttons {
    flex-wrap: wrap;
    justify-content: center;
  }
  #chart-range-buttons .btn {
    margin: 0.15rem;
    min-width: 3.25rem;
  }
  .price-rolling {
    display: inline-block;
    animation: priceRoll 3s ease-in-out infinite;
    transform-origin: center;
  }
  @keyframes priceRoll {
    0%, 100% {
      transform: translateY(0);
    }
    25% {
      transform: translateY(-12%);
    }
    50% {
      transform: translateY(6%);
    }
    75% {
      transform: translateY(-4%);
    }
  }
  @media (max-width: 991.98px) {
    .market-chart {
      height: 360px;
    }
    .market-summary-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const apiBase = "http://74.208.158.229/stock-api";
  const DEFAULT_SYMBOL = <?=json_encode($defaultSymbol); ?>;
  const spotlightSymbols = <?=json_encode($spotlightSymbols, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

  const searchForm = document.getElementById('market-search-form');
  const searchInput = document.getElementById('market-search-input');
  const searchFeedback = document.getElementById('search-feedback');

  const quickSymbolButtons = Array.prototype.slice.call(document.querySelectorAll('[data-search-symbol]'));
  const overviewSymbolEl = document.getElementById('overview-symbol');
  const overviewExchangeEl = document.getElementById('overview-exchange');
  const overviewCompanyEl = document.getElementById('overview-company');
  const overviewTagsEl = document.getElementById('overview-tags');
  const overviewPriceEl = document.getElementById('overview-price');
  const overviewChangeEl = document.getElementById('overview-change');

  const metricEls = {
    open: document.getElementById('metric-open'),
    previousClose: document.getElementById('metric-previous-close'),
    dayRange: document.getElementById('metric-day-range'),
    range52w: document.getElementById('metric-52w-range'),
    volume: document.getElementById('metric-volume'),
    avgVolume: document.getElementById('metric-avg-volume'),
    marketCap: document.getElementById('metric-market-cap'),
    pe: document.getElementById('metric-pe'),
    vwap: document.getElementById('metric-vwap'),
    rsi: document.getElementById('metric-rsi')
  };

  const insightEls = {
    rsi: document.getElementById('insight-rsi'),
    vwap: document.getElementById('insight-vwap'),
    commentary: document.getElementById('insight-commentary')
  };

  const chartEl = document.getElementById('market-chart');
  const chartTitle = document.getElementById('chart-title');
  const chartSubtitle = document.getElementById('chart-subtitle');
  const lastUpdatedLabel = document.getElementById('last-updated-label');
  const chartModeGroup = document.getElementById('chart-mode-group');
  const chartRangeButtonsWrapper = document.getElementById('chart-range-buttons');
  const chartRangeButtons = chartRangeButtonsWrapper ? Array.prototype.slice.call(chartRangeButtonsWrapper.querySelectorAll('button[data-range]')) : [];

  const moversContainer = document.getElementById('trending-movers');
  const moversTimestamp = document.getElementById('movers-timestamp');

  const chartRangeConfig = {
    '1D': { period: '5d', interval: '15m' },
    '5D': { period: '5d', interval: '1h' },
    '1M': { period: '1mo', interval: '1d' },
    '3M': { period: '3mo', interval: '1d' },
    '6M': { period: '6mo', interval: '1d' },
    '1Y': { period: '1y', interval: '1wk' },
    'MAX': { period: 'max', interval: '1mo' }
  };

  const state = {
    currentSymbol: DEFAULT_SYMBOL,
    chartMode: 'candles',
    currentRange: '1D',
    livePrice: 0,
    lastChartData: [],
    loadingCount: 0
  };

  let chartInstance = null;

  function toCurrency(value) {
    const num = Number(value);
    if (!isFinite(num)) return '—';
    return num.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
  }

  function toNumber(value, digits) {
    const num = Number(value);
    if (!isFinite(num)) return '—';
    return typeof digits === 'number' ? num.toFixed(digits) : num.toLocaleString();
  }

  function formatRange(low, high) {
    if (!isFinite(low) || !isFinite(high)) return '—';
    return `${toCurrency(low)} – ${toCurrency(high)}`;
  }

  function clampNumber(value) {
    return isFinite(value) ? value : 0;
  }

  function setLoading(message) {
    state.loadingCount += 1;
    if (!window.Swal || state.loadingCount > 1) return;
    Swal.fire({
      title: message || "Loading…",
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
  }

  function clearLoading() {
    if (state.loadingCount > 0) {
      state.loadingCount -= 1;
    }
    if (state.loadingCount === 0 && window.Swal) {
      Swal.close();
    }
  }

  function showError(message) {
    if (window.Swal) {
      Swal.fire({
        icon: 'error',
        title: 'Unable to load symbol',
        text: message || 'Try a different ticker symbol.'
      });
    } else {
      alert(message || 'Unable to load symbol.');
    }
  }

  function updateQuickSymbolActive(symbol) {
    const upper = (symbol || '').toUpperCase();
    quickSymbolButtons.forEach(function (btn) {
      const btnSymbol = (btn.getAttribute('data-search-symbol') || '').toUpperCase();
      btn.classList.toggle('active', btnSymbol === upper);
    });
  }

  function renderTags(tags) {
    overviewTagsEl.innerHTML = '';
    if (!tags || !tags.length) {
      const badge = document.createElement('span');
      badge.className = 'badge badge-light';
      badge.textContent = 'No tags available';
      overviewTagsEl.appendChild(badge);
      return;
    }
    tags.forEach(function (tag) {
      if (!tag) return;
      const badge = document.createElement('span');
      badge.className = 'badge badge-danger badge-pill mr-1 mb-1';
      badge.textContent = tag;
      overviewTagsEl.appendChild(badge);
    });
  }

  function deriveCommentary(metrics) {
    const pieces = [];
    const rsi = clampNumber(metrics && metrics.rsi);
    const vwap = metrics ? metrics.vwap : null;
    if (isFinite(rsi)) {
      if (rsi > 70) {
        pieces.push('RSI indicates an overbought setup—momentum may be stretched.');
      } else if (rsi < 30) {
        pieces.push('RSI is in oversold territory, watch for a potential rebound.');
      } else {
        pieces.push('RSI is balanced, signaling neutral momentum.');
      }
    }
    if (vwap) {
      pieces.push(`Price relative to VWAP offers insight into intraday positioning (${vwap}).`);
    }
    return pieces.length ? pieces.join(' ') : 'Metrics will populate once live data is available.';
  }

  function updateInsight(metrics) {
    const vwap = metrics && metrics.vwap ? metrics.vwap : null;
    const rsi = metrics && isFinite(Number(metrics.rsi)) ? Number(metrics.rsi) : null;
    insightEls.vwap.textContent = vwap || '—';
    insightEls.rsi.textContent = rsi !== null ? Number(rsi).toFixed(2) : '—';
    insightEls.commentary.textContent = deriveCommentary(metrics);
  }

  function updateOverview(data) {
    const stock = data && data.selected_stock ? data.selected_stock : null;
    if (!stock) {
      overviewSymbolEl.textContent = '—';
      overviewExchangeEl.textContent = '—';
      overviewCompanyEl.textContent = 'Symbol not found.';
      overviewPriceEl.textContent = '—';
      overviewPriceEl.classList.remove('price-rolling');
      overviewChangeEl.textContent = '—';
      return;
    }

    const metrics = stock.metrics || {};
    const dayRange = metrics.day_range || {};
    const range52 = metrics["52w_range"] || {};
    const priceChange = stock.price_change || {};
    const changeAmount = Number(priceChange.amount);
    let changePercent = priceChange.percentage;
    if (typeof changePercent !== 'string' && isFinite(Number(changePercent))) {
      changePercent = Number(changePercent).toFixed(2) + '%';
    }

    const numericPrice = Number(stock.last_price);
    const hasPrice = isFinite(numericPrice);
    state.livePrice = hasPrice ? numericPrice : 0;

    overviewSymbolEl.textContent = stock.ticker || '—';
    overviewExchangeEl.textContent = stock.exchange || '—';
    overviewCompanyEl.textContent = stock.company_name || '—';
    overviewPriceEl.textContent = hasPrice ? toCurrency(numericPrice) : '—';
    overviewPriceEl.classList.toggle('price-rolling', hasPrice);
    overviewChangeEl.textContent = (isFinite(changeAmount) ? (changeAmount >= 0 ? '+' : '') + changeAmount.toFixed(2) : '—') + (changePercent ? ` (${changePercent})` : '');
    overviewChangeEl.classList.remove('text-success', 'text-danger');
    if (isFinite(changeAmount)) {
      overviewChangeEl.classList.add(changeAmount >= 0 ? 'text-success' : 'text-danger');
    }

    renderTags(stock.tags);

    metricEls.open.textContent = metrics.open ? toCurrency(metrics.open) : '—';
    metricEls.previousClose.textContent = metrics.previous_close ? toCurrency(metrics.previous_close) : '—';
    metricEls.dayRange.textContent = formatRange(dayRange.low, dayRange.high);
    metricEls.range52w.textContent = formatRange(range52.low, range52.high);
    metricEls.volume.textContent = metrics.volume || '—';
    metricEls.avgVolume.textContent = metrics.avg_volume || '—';
    metricEls.marketCap.textContent = metrics.market_cap || '—';
    metricEls.pe.textContent = metrics.pe_ratio || '—';
    metricEls.vwap.textContent = metrics.vwap || '—';
    metricEls.rsi.textContent = metrics.rsi ? Number(metrics.rsi).toFixed(2) : '—';

    updateInsight(metrics);
  }

  function destroyChart() {
    if (chartInstance) {
      chartInstance.destroy();
      chartInstance = null;
    }
  }

  function renderChart(data) {
    if (!chartEl) return;
    state.lastChartData = Array.isArray(data) ? data : [];

    if (!state.lastChartData.length) {
      destroyChart();
      chartEl.innerHTML = "<div class='text-center text-muted py-5'>No chart data available.</div>";
      return;
    }

    const chartOptions = {};
    destroyChart();

    if (state.chartMode === 'line') {
      const lineSeries = state.lastChartData.map(function (point) {
        const y = Array.isArray(point.y) ? point.y[3] : null;
        return { x: point.x, y: y };
      });
      chartInstance = new ApexCharts(chartEl, {
        series: [{ name: state.currentSymbol, data: lineSeries }],
        chart: { type: 'area', height: 380, toolbar: { show: false }, zoom: { enabled: false } },
        stroke: { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0, stops: [0, 90, 100] } },
        xaxis: { type: 'datetime' },
        yaxis: { labels: { formatter: function (val) { return toCurrency(val); } } },
        tooltip: { shared: true, x: { format: 'MMM dd, yyyy HH:mm' } },
        grid: { borderColor: '#f0f0f0' }
      });
    } else {
      chartInstance = new ApexCharts(chartEl, {
        series: [{ name: state.currentSymbol, data: state.lastChartData }],
        chart: { type: 'candlestick', height: 380, toolbar: { show: false } },
        xaxis: { type: 'datetime' },
        yaxis: {
          tooltip: { enabled: true },
          labels: { formatter: function (val) { return toCurrency(val); } }
        },
        plotOptions: {
          candlestick: {
            colors: { upward: '#28a745', downward: '#dc3545' },
            wick: { useFillColor: true }
          }
        },
        grid: { borderColor: '#f0f0f0' }
      });
    }
    chartInstance.render();
  }

  async function loadChart(symbol, range, options) {
    if (!symbol) return;
    const config = chartRangeConfig[range] || chartRangeConfig['1M'];
    const cacheKey = `${symbol}|${config.period}|${config.interval}`;
    const forceRefresh = options && options.forceRefresh;
    const now = Date.now();

    if (!state.chartCache) {
      state.chartCache = new Map();
    }

    if (!forceRefresh && state.chartCache.has(cacheKey)) {
      const cached = state.chartCache.get(cacheKey);
      if (now - cached.timestamp < 60_000) {
        renderChart(cached.data);
        return;
      }
    }

    setLoading("Updating chart…");
    try {
      const url = apiBase + "/chart-history?symbol=" + encodeURIComponent(symbol) +
        "&period=" + encodeURIComponent(config.period) +
        "&interval=" + encodeURIComponent(config.interval);
      const response = await fetch(url);
      if (!response.ok) throw new Error("Chart API responded with " + response.status);
      const json = await response.json();
      const payload = json && Array.isArray(json.data) ? json.data : [];
      const data = payload.map(function (item) {
        return {
          x: new Date(item.x).getTime(),
          y: item.y
        };
      });
      state.chartCache.set(cacheKey, { data: data, timestamp: now });
      renderChart(data);
    } catch (error) {
      console.error('Chart load failed:', error);
      chartEl.innerHTML = "<div class='text-center text-danger py-5'>Unable to load chart data.</div>";
    } finally {
      clearLoading();
    }
  }

  async function fetchSymbol(symbol, options) {
    if (!symbol) return;
    const forceRefresh = options && options.forceRefresh;
    const cacheKey = symbol.toUpperCase();
    const now = Date.now();

    if (!state.symbolCache) state.symbolCache = new Map();

    if (!forceRefresh && state.symbolCache.has(cacheKey)) {
      const cached = state.symbolCache.get(cacheKey);
      if (now - cached.timestamp < 60_000) {
        return cached.data;
      }
    }

    setLoading("Loading " + symbol.toUpperCase() + "…");
    try {
      const response = await fetch(apiBase + "/buy-sell?symbol=" + encodeURIComponent(symbol));
      if (!response.ok) throw new Error("API responded with " + response.status);
      const data = await response.json();
      if (!data || !data.selected_stock) throw new Error("Symbol data not available.");
      state.symbolCache.set(cacheKey, { data: data, timestamp: now });
      return data;
    } catch (error) {
      console.error('Symbol load failed:', error);
      throw error;
    } finally {
      clearLoading();
    }
  }

  async function loadSymbol(symbol, options) {
    const cleaned = (symbol || '').trim().toUpperCase();
    if (!cleaned) {
      searchFeedback.textContent = 'Enter a ticker symbol, e.g. NVDA or BTC-USD.';
      searchFeedback.classList.remove('d-none');
      return;
    }
    searchFeedback.classList.add('d-none');
    searchInput.value = cleaned;

    try {
      const data = await fetchSymbol(cleaned, options);
      state.currentSymbol = cleaned;
      const now = new Date();
      lastUpdatedLabel.innerHTML = `<i class="far fa-clock mr-1"></i>Updated ${now.toLocaleString()}`;
      chartTitle.textContent = `${cleaned} · Price & Momentum`;
      chartSubtitle.textContent = data && data.selected_stock ? `${data.selected_stock.company_name || ''} (${cleaned})` : cleaned;
      updateQuickSymbolActive(cleaned);
      updateOverview(data);
      await loadChart(cleaned, state.currentRange, options);
    } catch (error) {
      showError(error.message || 'Unable to load the requested symbol.');
    }
  }

  async function loadTrendingMovers() {
    if (!moversContainer) return;
    try {
      const symbols = spotlightSymbols.join(',');
      const response = await fetch(apiBase + "/buy-sell-info?symbols=" + encodeURIComponent(symbols));
      if (!response.ok) throw new Error("Snapshot API responded with " + response.status);
      const data = await response.json();
      if (!Array.isArray(data) || !data.length) throw new Error("No snapshot data available.");

      moversContainer.innerHTML = "";
      data.forEach(function (item) {
        if (!item || item.error) return;
        const listItem = document.createElement('button');
        listItem.type = "button";
        listItem.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
        listItem.setAttribute('data-search-symbol', item.symbol);
        listItem.innerHTML = `
          <div>
            <div class="market-mover-symbol">${item.symbol}</div>
            <small class="text-muted">${item.company_name || ''}</small>
          </div>
          <div class="text-right">
            <div class="font-weight-bold">${isFinite(Number(item.current_price)) ? toCurrency(item.current_price) : '—'}</div>
            <div class="${Number(item.change_pct) >= 0 ? 'text-success' : 'text-danger'}">${Number(item.change_pct).toFixed(2)}%</div>
          </div>
        `;
        listItem.addEventListener('click', function () {
          loadSymbol(item.symbol);
        });
        moversContainer.appendChild(listItem);
      });

      moversTimestamp.textContent = "Updated " + new Date().toLocaleTimeString();
    } catch (error) {
      console.error('Trending movers failed:', error);
      moversContainer.innerHTML = "<div class='list-group-item text-danger text-center py-4'>Unable to load movers.</div>";
      moversTimestamp.textContent = "Update failed";
    }
  }

  if (searchForm) {
    searchForm.addEventListener('submit', function (event) {
      event.preventDefault();
      loadSymbol(searchInput.value);
    });
  }

  quickSymbolButtons.forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();
      const symbol = button.getAttribute('data-search-symbol');
      loadSymbol(symbol);
    });
  });

  if (chartModeGroup) {
    chartModeGroup.addEventListener('click', function (event) {
      const button = event.target.closest('button[data-chart-mode]');
      if (!button) return;
      const mode = button.getAttribute('data-chart-mode');
      if (!mode || mode === state.chartMode) return;
      state.chartMode = mode;
      Array.prototype.slice.call(chartModeGroup.querySelectorAll('button[data-chart-mode]')).forEach(function (btn) {
        btn.classList.toggle('active', btn === button);
      });
      renderChart(state.lastChartData);
    });
  }

  if (chartRangeButtonsWrapper) {
    chartRangeButtonsWrapper.addEventListener('click', function (event) {
      const button = event.target.closest('button[data-range]');
      if (!button) return;
      const range = button.getAttribute('data-range');
      if (!range || range === state.currentRange) return;
      state.currentRange = range;
      chartRangeButtons.forEach(function (btn) {
        btn.classList.toggle('active', btn === button);
      });
      loadChart(state.currentSymbol, range, { forceRefresh: true });
    });
  }

  loadSymbol(DEFAULT_SYMBOL);
  loadTrendingMovers();
  setInterval(loadTrendingMovers, 120000);
});
</script>

<?php require "../lib/PAGE-bottom.php"; ?>
