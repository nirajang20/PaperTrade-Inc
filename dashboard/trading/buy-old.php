<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";
?>

<section class="content">
  <div class="container-fluid">

    <!-- HEADER -->
    <div class="row align-items-center mb-3">
      <div class="col-lg-8">
        <h1 class="h3 mb-1"><i class="fas fa-shopping-cart text-success mr-2"></i>Trade Tickets · <span id="page-intent-label" class="text-success">Buy</span></h1>
        <p class="text-muted mb-0">Track the market, review performance, and stage a buy order without leaving this workspace.</p>
      </div>
    </div>
    <hr>
    <div class="row">
      <!-- LEFT COLUMN: Selected Stock -->
      <div class="col-xl-4">
<div class="card card-outline card-success mb-4">
  <div class="card-header border-0 d-flex justify-content-between align-items-center">
    <h3 class="card-title mb-0">Selected Stock</h3>
    <span class="badge badge-success">Live</span>
    </div>
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h2 class="mb-0" id="selected-symbol">Loading...</h2>
        <p class="text-muted mb-3" id="selected-company">Loading company name</p>
        <!-- <div class="badge badge-light text-muted">Loading stock exchange...</div> -->
      </div>
      <div class="text-right">
        <small class="text-muted d-block">Last Price</small>
        <h2 class="mb-0" id="selected-price">--.--</h2>
        <span class="text-muted" id="selected-change">Loading change...</span>
      </div>
    </div>

    <div class="row pt-3">
      <div class="col-6"><small class="text-muted">Open</small><p id="selected-open" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">Previous Close</small><p id="selected-prev-close" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">Day Range</small><p id="selected-day-range" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">52W Range</small><p id="selected-52w-range" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">Volume</small><p id="selected-volume" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">Avg Volume</small><p id="selected-avg-volume" class="font-weight-bold mb-3">Loading...</p></div>
      <div class="col-6"><small class="text-muted">Market Cap</small><p id="selected-market-cap" class="font-weight-bold mb-0">Loading...</p></div>
      <div class="col-6"><small class="text-muted">P/E Ratio</small><p id="selected-pe" class="font-weight-bold mb-0">Loading...</p></div>
    </div>
  </div>
  <div class="card-footer bg-white">
    <small class="text-muted">Updated at <?=date('Y-m-d H:i:s')?> CST</small>
  </div>
</div>
        <!-- AVAILABLE STOCKS -->
        <div class="card card-outline card-primary mb-4">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Available Stocks</h3>
            <button class="btn btn-tool d-none" type="button" id="refresh-stocks" aria-label="Refresh stock list"><i class="fas fa-sync-alt"></i></button>
          </div>
          <div id="available-stocks" class="list-group list-group-flush">
            <div class="p-3 text-center text-muted">Loading data...</div>
          </div>
          <div class="card-footer bg-white">
            <small class="text-muted">Screeners refreshed at <?=date('Y-m-d H:i:s')?> CST</small>
          </div>
        </div>

        <!-- WATCHLIST -->
        <div class="card card-outline card-info mb-4">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Pinned Watchlist</h3>
            <span class="badge badge-info">Alerts · 3</span>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>BTC/USD</span><span class="badge badge-success">+2.6%</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>SPY</span><span class="badge badge-warning text-dark">+0.4%</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>VIX</span><span class="badge badge-danger">-1.9%</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="col-xl-8">
        <!-- CHART -->
        <div class="card card-outline card-success mb-4">
          <h3 class="card-title mb-0 text-center mt-2" id="selected-symbol-chart">Loading....</h3>
          <div class="card-body">
            <div id="chart"></div>
            <div class="card-header d-flex justify-content-center align-items-center flex-wrap gap-2">
              <div class="btn-group btn-group-sm" role="group" aria-label="Range selector" id="chart-range-buttons">
                <button type="button" class="btn btn-outline-primary active" data-range="1D">1D</button>
                <button type="button" class="btn btn-outline-primary" data-range="1W">1W</button>
                <button type="button" class="btn btn-outline-primary" data-range="1M">1M</button>
                <button type="button" class="btn btn-outline-primary" data-range="YTD">YTD</button>
                <button type="button" class="btn btn-outline-primary" data-range="1Yr">1Yr</button>
                <button type="button" class="btn btn-outline-primary" data-range="3Yr">3Yr</button>
                <button type="button" class="btn btn-outline-primary" data-range="5Yr">5Yr</button>
              </div>
            </div>
  </div>
</div>

       

        <!-- ORDER TICKET -->
        <div class="card card-outline card-success mb-4 mt-4">
          <div class="card-header border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
              <div>
                <h3 class="card-title mb-0">Order Ticket</h3>
                <small class="text-muted">Long Equity · USD · Intent <span id="order-intent-label" class="text-success">Buy</span></small>
              </div>
            </div>
          </div>
          <div class="card-body">
            <ul class="nav nav-pills mb-3" id="ticket-mode-tabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="by-shares-tab" data-toggle="tab" href="#ticket-by-shares" role="tab">Buy by Shares</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="by-dollars-tab" data-toggle="tab" href="#ticket-by-dollars" role="tab">Buy by Amount</a>
              </li>
            </ul>
            <div class="tab-content" id="ticket-mode-content">
              <div class="tab-pane fade show active" id="ticket-by-shares" role="tabpanel">
                <div class="row">
                  <div class="col-lg-7">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="shares-quantity">Shares</label>
                          <input type="number" min="0" class="form-control" id="shares-quantity" placeholder="e.g. 10">
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="shares-limit">Limit Price</label>
                          <input type="number" step="0.01" class="form-control" id="shares-limit" placeholder="$191.50">
                        </div>
                      </div>
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="shares-order-type">Order Type</label>
                          <select id="shares-order-type" class="form-control" aria-readonly="true">
                            <option selected>Market</option>
                          </select>
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="shares-symbol-name">Symbol</label>
                          <input id="shares-symbol-name" name="shares-symbol-name" class="form-control" value="Loading..." readonly/>
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="shares-notes">Trader Notes</label>
                        <textarea id="shares-notes" class="form-control" rows="2" placeholder="Remind yourself why you're entering"></textarea>
                      </div>
                    </form>
                  </div>
                  <div class="col-lg-5">
                    <div class="border rounded p-3 bg-light">
                      <h6 class="text-uppercase text-muted mb-3">Order Summary</h6>
                      <dl class="row mb-0 small">
                        <dt class="col-6">Mode</dt>
                        <dd class="col-6 text-right" id="shares-summary-mode">Shares</dd>
                        <dt class="col-6">Quantity</dt>
                        <dd class="col-6 text-right" id="shares-summary-quantity">—</dd>
                        <dt class="col-6">Est. Cost</dt>
                        <dd class="col-6 text-right" id="shares-summary-cost">—</dd>
                        <dt class="col-6">Fees</dt>
                        <dd class="col-6 text-right" id="shares-summary-fees">$1.00</dd>
                        <dt class="col-6">Buying Power After</dt>
                        <dd class="col-6 text-right" id="shares-summary-buying-power">—</dd>
                      </dl>
                    </div>
                    <div class="mt-3">
                      <h6 class="text-uppercase text-muted mb-2 small">Execution Checklist</h6>
                      <ul class="list-unstyled small mb-0">
                        <li class="mb-1"><i class="fas fa-check-circle text-success mr-2"></i>Buying power available</li>
                        <li class="mb-1"><i class="fas fa-check-circle text-success mr-2"></i>Compliance checks passed</li>
                        <li class="mb-1"><i class="fas fa-clock text-warning mr-2"></i>Extended hours disabled</li>
                        <li><i class="fas fa-info-circle text-info mr-2"></i>Submit before 4:00 PM ET</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="ticket-by-dollars" role="tabpanel">
                <div class="row">
                  <div class="col-lg-7">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="dollar-amount">Amount (USD)</label>
                          <input type="number" step="0.01" class="form-control" id="dollar-amount" name="dollar-amount" placeholder="e.g. 2,500">
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="dollar-estimated">Estimated Shares</label>
                          <input type="text" class="form-control" id="dollar-estimated" placeholder="13.05" name="dollar-estimated" readonly="">
                        </div>
                      </div>
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="dollar-order-type">Order Type</label>
                          <select id="dollar-order-type" class="form-control" aria-readonly="true">
                            <option selected>Market</option>
                          </select>
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="dollar-symbol-name">Symbol</label>
                          <input id="dollar-symbol-name" name="dollar-symbol-name" class="form-control" value="Loading..." readonly/>
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="dollar-note">Instruction</label>
                        <input type="text" class="form-control" id="dollar-note" placeholder="Optional trade note">
                      </div>
                    </form>
                  </div>
                  <div class="col-lg-5">
                    <div class="border rounded p-3 bg-light">
                      <h6 class="text-uppercase text-muted mb-3">Order Summary</h6>
                      <dl class="row mb-0 small">
                        <dt class="col-6">Mode</dt>
                        <dd class="col-6 text-right" id="amount-summary-mode">Amount</dd>
                        <dt class="col-6">Investing</dt>
                        <dd class="col-6 text-right" id="amount-summary-investing">—</dd>
                        <dt class="col-6">Est. Shares</dt>
                        <dd class="col-6 text-right" id="amount-summary-est-shares">—</dd>
                        <dt class="col-6">Est. Avg Price</dt>
                        <dd class="col-6 text-right" id="amount-summary-avg-price">—</dd>
                      </dl>
                    </div>
                    <div class="mt-3">
                      <h6 class="text-uppercase text-muted mb-2 small">Checklist</h6>
                      <ul class="list-unstyled small mb-0">
                        <li class="mb-1"><i class="fas fa-dot-circle text-primary mr-2"></i>Fractional trade enabled</li>
                        <li class="mb-1"><i class="fas fa-check-circle text-success mr-2"></i>Availability confirmed</li>
                        <li><i class="fas fa-lock text-secondary mr-2"></i>Trade window closes in 1h 12m</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-column flex-md-row">
            <div class="text-muted small mb-3 mb-md-0" data-available-cash="60137" id="available-cash-wrapper">
              <span class="mr-3" id="available-cash-display"><i class="fas fa-wallet mr-1"></i>Available cash: $60,137.00</span>
            </div>
            <div>
              <button type="button" class="btn btn-outline-secondary mr-2" id="clear-trade-btn"><i class="fas fa-undo"></i> Clear Ticket</button>
              <button type="button" class="btn btn-success" id="submit-trade-btn">Submit Buy Order</button>
            </div>
          </div>
        </div>

        <!-- RECENT ORDERS -->
        <div class="card card-outline card-primary">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Recent Buy Orders</h3>
            <span class="text-muted small">Past 24 hours</span>
          </div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead><tr><th>Symbol</th><th>Mode</th><th>Qty</th><th>Price</th><th>Status</th><th>Time</th></tr></thead>
              <tbody>
                <tr><td>AAPL</td><td>Shares</td><td>25</td><td>$190.8</td><td><span class="badge badge-success">Filled</span></td><td>09:45</td></tr>
                <tr><td>NVDA</td><td>Amount</td><td>$1,000</td><td>$782.1</td><td><span class="badge badge-warning">Partial</span></td><td>08:30</td></tr>
                <tr><td>MSFT</td><td>Units</td><td>1 Lot</td><td>$410.22</td><td><span class="badge badge-secondary">Queued</span></td><td>15:12</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>
<style>
  #available-stocks a.active {
    background-color: var(--success-bg, rgba(40,167,69,0.1));
    border-left: 4px solid #28a745;
    font-weight: 600;
    transition: all 0.2s ease-in-out;
  }
  #available-stocks a:hover {
    background-color: rgba(40,167,69,0.15);
  }
  #available-stocks a.active #selected-symbol-lists,
  #available-stocks a.active .font-weight-bold {
    color: #000;
  }
  #available-stocks a.active #selected-symbol-lists {
    font-weight: bold;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const apiBase = "http://74.208.158.229/stock-api";
  const symbols = ['NVDA','MSFT','AAPL','GOOGL','AMZN','META','TSLA','JPM','^GSPC','BTC-USD','ETH-USD'];
  const DEFAULT_SYMBOL = 'NVDA';

  const availableList = document.getElementById("available-stocks");
  const refreshBtn = document.getElementById("refresh-stocks");
  const refreshIcon = refreshBtn ? refreshBtn.querySelector('i') : null;
  const rangeButtonsWrapper = document.getElementById("chart-range-buttons");
  const chartTitle = document.getElementById("selected-symbol-chart");
  const chartEl = document.getElementById("chart");
  const availableCashWrapper = document.getElementById("available-cash-wrapper");
  const availableCash = availableCashWrapper ? Number(availableCashWrapper.getAttribute('data-available-cash') || 0) : 0;

  const TRADE_FEE = 1;
  const CACHE_DURATION_MS = 60000;
  const CHART_CACHE_DURATION_MS = 120000;

  const stockDataCache = new Map();
  const chartDataCache = new Map();

  const state = {
    currentSymbol: null,
    currentRange: '1D',
    livePrice: 0
  };

  let chartInstance = null;
  let stockAbortController = null;
  let chartAbortController = null;

  const els = {
    symbol: document.getElementById("selected-symbol"),
    company: document.getElementById("selected-company"),
    price: document.getElementById("selected-price"),
    change: document.getElementById("selected-change"),
    open: document.getElementById("selected-open"),
    prevClose: document.getElementById("selected-prev-close"),
    dayRange: document.getElementById("selected-day-range"),
    range52: document.getElementById("selected-52w-range"),
    volume: document.getElementById("selected-volume"),
    avgVolume: document.getElementById("selected-avg-volume"),
    marketCap: document.getElementById("selected-market-cap"),
    pe: document.getElementById("selected-pe")
  };

  const summaryEls = {
    sharesQuantity: document.getElementById("shares-summary-quantity"),
    sharesCost: document.getElementById("shares-summary-cost"),
    sharesFees: document.getElementById("shares-summary-fees"),
    sharesBuyingPower: document.getElementById("shares-summary-buying-power"),
    amountInvesting: document.getElementById("amount-summary-investing"),
    amountEstShares: document.getElementById("amount-summary-est-shares"),
    amountAvgPrice: document.getElementById("amount-summary-avg-price")
  };

  const priceInputs = {
    sharesQty: document.getElementById('shares-quantity'),
    sharesLimit: document.getElementById('shares-limit'),
    dollarAmount: document.getElementById('dollar-amount'),
    dollarEstimated: document.getElementById('dollar-estimated'),
    sharesSymbol: document.getElementById('shares-symbol-name'),
    dollarSymbol: document.getElementById('dollar-symbol-name')
  };

  const chartRangeConfig = {
    '1D': { period: '1d', interval: '15m' },
    '1W': { period: '5d', interval: '30m' },
    '1M': { period: '1mo', interval: '1d' },
    'YTD': { period: '6mo', interval: '1d' },
    '1Yr': { period: '1y', interval: '1d' },
    '3Yr': { period: '3y', interval: '1wk' },
    '5Yr': { period: '5y', interval: '1mo' }
  };

  const rangeButtons = rangeButtonsWrapper ? Array.prototype.slice.call(rangeButtonsWrapper.querySelectorAll('button[data-range]')) : [];

  function formatCurrency(value) {
    const num = Number(value);
    if (!isFinite(num)) return '—';
    return num.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
  }

  function formatDecimal(value, digits) {
    const num = Number(value);
    if (!isFinite(num)) return '—';
    return num.toFixed(typeof digits === 'number' ? digits : 2);
  }

  function fmtNum(value) {
    const num = Number(value);
    if (!isFinite(num)) return '—';
    const abs = Math.abs(num);
    const sign = num < 0 ? '-' : '';
    if (abs >= 1e12) return sign + (abs / 1e12).toFixed(2) + 'T';
    if (abs >= 1e9) return sign + (abs / 1e9).toFixed(2) + 'B';
    if (abs >= 1e6) return sign + (abs / 1e6).toFixed(1) + 'M';
    return sign + abs.toLocaleString();
  }

  function clampNumber(value) {
    return isFinite(value) ? value : 0;
  }

  function debounce(func, delay) {
    let timeout;
    return function () {
      const args = arguments;
      clearTimeout(timeout);
      timeout = setTimeout(function () {
        func.apply(null, args);
      }, typeof delay === 'number' ? delay : 250);
    };
  }

  function escapeSelector(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(value);
    }
    return String(value).replace(/([ !"#$%&'()*+,./:;<=>?@\[\\\]^`{|}~])/g, '\\$1');
  }

  function setActiveRangeButton(range) {
    for (let i = 0; i < rangeButtons.length; i++) {
      const btn = rangeButtons[i];
      btn.classList.toggle('active', btn.getAttribute('data-range') === range);
    }
  }

  function setActiveSymbol(symbol) {
    if (!availableList) return;
    const items = availableList.querySelectorAll('a[data-symbol]');
    for (let i = 0; i < items.length; i++) {
      items[i].classList.remove('active');
    }
    if (!symbol) return;
    const selector = 'a[data-symbol="' + escapeSelector(symbol) + '"]';
    const target = availableList.querySelector(selector);
    if (target) target.classList.add('active');
  }

  const symbolsQuery = symbols.map(function (sym) { return encodeURIComponent(sym); }).join(',');

  function setRefreshState(loading) {
    if (!refreshBtn) return;
    if (loading) {
      refreshBtn.disabled = true;
      refreshBtn.classList.add('opacity-50');
      if (refreshIcon) refreshIcon.classList.add('fa-spin');
    } else {
      refreshBtn.disabled = false;
      refreshBtn.classList.remove('opacity-50');
      if (refreshIcon) refreshIcon.classList.remove('fa-spin');
      refreshBtn.classList.remove('d-none');
    }
  }

  function renderChart(ohlcData) {
    if (!chartEl) return;
    if (!Array.isArray(ohlcData) || ohlcData.length === 0) {
      if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
      }
      chartEl.innerHTML = "<div class='text-center text-muted py-5'>No chart data available.</div>";
      return;
    }

    const options = {
      series: [{ data: ohlcData }],
      chart: { type: 'candlestick', height: 350, animations: { enabled: false }, toolbar: { show: false } },
      xaxis: { type: 'datetime' },
      yaxis: { tooltip: { enabled: true } }
    };

    if (!chartInstance) {
      chartEl.innerHTML = '';
      chartInstance = new ApexCharts(chartEl, options);
      chartInstance.render();
    } else {
      chartInstance.updateOptions(options, false, true);
    }
  }

  async function loadChart(symbol, range, options) {
    if (!symbol || !chartEl) return;
    const cfg = chartRangeConfig[range] || chartRangeConfig['1D'];
    const cacheKey = symbol + "|" + cfg.period + "|" + cfg.interval;
    const now = Date.now();
    const forceRefresh = options && options.forceRefresh;

    if (!forceRefresh && chartDataCache.has(cacheKey)) {
      const cached = chartDataCache.get(cacheKey);
      if (now - cached.timestamp < CHART_CACHE_DURATION_MS) {
        renderChart(cached.data);
        if (chartTitle) chartTitle.textContent = symbol + " Price Chart · " + range;
        return;
      }
    }

    if (chartAbortController) chartAbortController.abort();
    chartAbortController = new AbortController();

    try {
      const url = apiBase + "/chart-history?symbol=" + encodeURIComponent(symbol) + "&period=" + cfg.period + "&interval=" + cfg.interval;
      const response = await fetch(url, { signal: chartAbortController.signal });
      if (!response.ok) throw new Error("Chart API responded with " + response.status);
      const json = await response.json();
      const raw = json && Array.isArray(json.data) ? json.data : [];
      const data = raw.map(function (item) {
        return { x: new Date(item.x).getTime(), y: item.y };
      });
      chartDataCache.set(cacheKey, { data: data, timestamp: now });
      renderChart(data);
      if (chartTitle) chartTitle.textContent = symbol + " Price Chart · " + range;
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.error('Chart load failed:', error);
      chartEl.innerHTML = "<div class='text-center text-danger py-5'>Unable to load chart data.</div>";
    } finally {
      chartAbortController = null;
    }
  }

  function updateOrderEstimates() {
    const quantity = priceInputs.sharesQty ? Number(priceInputs.sharesQty.value) : 0;
    const limitPriceInput = priceInputs.sharesLimit ? Number(priceInputs.sharesLimit.value) : NaN;
    const limitPrice = limitPriceInput || clampNumber(state.livePrice);
    const amount = priceInputs.dollarAmount ? Number(priceInputs.dollarAmount.value) : 0;

    const estCost = quantity > 0 && limitPrice > 0 ? quantity * limitPrice : 0;
    const estFees = TRADE_FEE;
    const projectedSpend = estCost > 0 ? estCost + estFees : 0;
    const buyingPowerAfter = availableCash ? Math.max(availableCash - projectedSpend, 0) : 0;
    const estShares = state.livePrice > 0 && amount > 0 ? amount / state.livePrice : 0;

    if (summaryEls.sharesQuantity) summaryEls.sharesQuantity.textContent = quantity > 0 ? quantity.toLocaleString('en-US', { maximumFractionDigits: 4 }) : '—';
    if (summaryEls.sharesCost) summaryEls.sharesCost.textContent = estCost > 0 ? formatCurrency(estCost) : '—';
    if (summaryEls.sharesFees) summaryEls.sharesFees.textContent = formatCurrency(estFees);
    if (summaryEls.sharesBuyingPower) summaryEls.sharesBuyingPower.textContent = availableCash ? formatCurrency(buyingPowerAfter) : '—';

    if (summaryEls.amountInvesting) summaryEls.amountInvesting.textContent = amount > 0 ? formatCurrency(amount) : '—';
    if (summaryEls.amountEstShares) summaryEls.amountEstShares.textContent = estShares > 0 ? estShares.toFixed(2) : '—';
    if (summaryEls.amountAvgPrice) summaryEls.amountAvgPrice.textContent = state.livePrice > 0 ? formatCurrency(state.livePrice) : '—';

    if (priceInputs.dollarEstimated) priceInputs.dollarEstimated.value = estShares > 0 ? estShares.toFixed(2) : '';
  }

  function updateUIWithStockData(data) {
    const stock = data && data.selected_stock ? data.selected_stock : null;
    if (!stock) return;

    const metrics = stock.metrics || {};
    const dayRange = metrics.day_range || {};
    const range52 = metrics["52w_range"] || {};
    const priceChange = stock.price_change || {};
    const priceChangeAmount = Number(priceChange.amount);
    const priceChangePct = priceChange.percentage;

    state.currentSymbol = stock.ticker;
    state.livePrice = Number(stock.last_price) || 0;

    if (els.symbol) els.symbol.textContent = stock.ticker || '—';
    if (els.company) els.company.textContent = (stock.company_name || 'Unknown') + " · " + (stock.exchange || '—');
    if (els.price) els.price.textContent = state.livePrice ? formatCurrency(state.livePrice) : '—';

    if (els.change) {
      els.change.classList.remove('text-success', 'text-danger');
      if (isFinite(priceChangeAmount)) {
        els.change.classList.add(priceChangeAmount >= 0 ? 'text-success' : 'text-danger');
        let pctText;
        if (typeof priceChangePct === 'string') {
          pctText = priceChangePct;
        } else if (isFinite(Number(priceChangePct))) {
          pctText = Number(priceChangePct).toFixed(2) + "%";
        } else {
          pctText = '—';
        }
        const formattedAmount = (priceChangeAmount >= 0 ? '+' : '') + formatDecimal(priceChangeAmount);
        els.change.textContent = formattedAmount + " (" + pctText + ")";
      } else {
        els.change.textContent = '—';
      }
    }

    if (els.open) els.open.textContent = metrics.open ? formatCurrency(metrics.open) : '—';
    if (els.prevClose) els.prevClose.textContent = metrics.previous_close ? formatCurrency(metrics.previous_close) : '—';
    if (els.dayRange) {
      if (isFinite(dayRange.low) && isFinite(dayRange.high)) {
        els.dayRange.textContent = formatCurrency(dayRange.low) + " - " + formatCurrency(dayRange.high);
      } else {
        els.dayRange.textContent = '—';
      }
    }
    if (els.range52) {
      if (isFinite(range52.low) && isFinite(range52.high)) {
        els.range52.textContent = formatCurrency(range52.low) + " - " + formatCurrency(range52.high);
      } else {
        els.range52.textContent = '—';
      }
    }
    if (els.volume) els.volume.textContent = fmtNum(metrics.volume);
    if (els.avgVolume) els.avgVolume.textContent = fmtNum(metrics.avg_volume);
    if (els.marketCap) els.marketCap.textContent = metrics.market_cap || '—';
    if (els.pe) els.pe.textContent = metrics.pe_ratio || '—';

    if (priceInputs.sharesSymbol) priceInputs.sharesSymbol.value = stock.ticker || '';
    if (priceInputs.dollarSymbol) priceInputs.dollarSymbol.value = stock.ticker || '';
    if (priceInputs.sharesLimit) priceInputs.sharesLimit.value = state.livePrice ? state.livePrice.toFixed(2) : '';

    updateOrderEstimates();
    setActiveSymbol(stock.ticker);
    loadChart(stock.ticker, state.currentRange);
  }

  async function fetchStock(symbol, options) {
    const forceRefresh = options && options.forceRefresh;
    const now = Date.now();

    if (!forceRefresh && stockDataCache.has(symbol)) {
      const cached = stockDataCache.get(symbol);
      if (now - cached.timestamp < CACHE_DURATION_MS) {
        updateUIWithStockData(cached.data);
        return;
      }
    }

    if (stockAbortController) stockAbortController.abort();
    stockAbortController = new AbortController();

    try {
      const response = await fetch(apiBase + "/buy-sell?symbol=" + encodeURIComponent(symbol), { signal: stockAbortController.signal });
      if (!response.ok) throw new Error("Stock API responded with " + response.status);
      const data = await response.json();
      if (!data || !data.selected_stock) throw new Error("Invalid stock response");
      stockDataCache.set(symbol, { data: data, timestamp: now });
      updateUIWithStockData(data);
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.error('Stock load failed:', error);
      if (els.symbol) els.symbol.textContent = symbol;
      if (els.company) els.company.textContent = 'Unable to load data.';
    } finally {
      stockAbortController = null;
    }
  }

  async function loadAvailableStocks(options) {
    if (!availableList) return;
    const forceRefresh = options && options.forceRefresh;

    if (forceRefresh || !availableList.getAttribute('data-loaded')) {
      availableList.innerHTML = "<div class='p-3 text-center text-muted'>Loading data...</div>";
    }

    setRefreshState(true);

    try {
      const response = await fetch(apiBase + "/buy-sell-info?symbols=" + symbolsQuery);
      if (!response.ok) throw new Error("List API responded with " + response.status);
      const data = await response.json();
      availableList.innerHTML = "";
      const fragment = document.createDocumentFragment();

      data.forEach(function (stock) {
        if (!stock || stock.error) return;
        const changePctValue = Number(stock.change_pct);
        const isUp = isFinite(changePctValue) ? changePctValue >= 0 : false;
        const changeClass = isUp ? 'text-success' : 'text-danger';
        const changePct = isFinite(changePctValue) ? (isUp ? '+' : '') + changePctValue.toFixed(2) + '%' : '—';
        const currentPrice = Number(stock.current_price);
        let tagsText;
        if (Array.isArray(stock.tags)) {
          tagsText = stock.tags.filter(Boolean).join(', ');
        } else {
          tagsText = stock.tags || '';
        }

        const item = document.createElement('a');
        item.href = '#';
        item.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
        item.setAttribute('data-symbol', stock.symbol);

        item.innerHTML = `
          <div>
            <h5 class="mb-0"><span class="mr-3">${stock.symbol}</span> <small class="text-muted">${stock.company_name || ''}</small></h5>
            <small class="text-muted">${stock.exchange || ''}${tagsText ? ' · ' + tagsText : ''}</small>
          </div>
          <div class="text-right">
            <span class="d-block font-weight-bold">${isFinite(currentPrice) ? formatCurrency(currentPrice) : '—'}</span>
            <span class="${changeClass}">${changePct}</span>
          </div>
        `;
        fragment.appendChild(item);
      });

      if (!fragment.childNodes.length) {
        availableList.innerHTML = "<div class='p-3 text-center text-muted'>No stocks available.</div>";
      } else {
        availableList.appendChild(fragment);
        availableList.setAttribute('data-loaded', '1');
        setActiveSymbol(state.currentSymbol);
      }
    } catch (error) {
      console.error('Failed to load stock list:', error);
      availableList.innerHTML = "<div class='p-3 text-center text-danger'>Unable to load stock list.</div>";
    } finally {
      setRefreshState(false);
    }
  }

  function loadStock(symbol, options) {
    if (!symbol) return;
    state.currentSymbol = symbol;
    fetchStock(symbol, options);
  }

  const debouncedLoadStock = debounce(function (symbol) {
    loadStock(symbol);
  }, 250);

  if (availableList) {
    availableList.addEventListener('click', function (event) {
      const link = event.target.closest('a[data-symbol]');
      if (!link) return;
      event.preventDefault();
      const symbol = link.getAttribute('data-symbol');
      debouncedLoadStock(symbol);
    });
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      stockDataCache.clear();
      chartDataCache.clear();
      if (availableList) availableList.removeAttribute('data-loaded');
      loadAvailableStocks({ forceRefresh: true });
      if (state.currentSymbol) {
        loadStock(state.currentSymbol, { forceRefresh: true });
        loadChart(state.currentSymbol, state.currentRange, { forceRefresh: true });
      }
    });
  }

  if (rangeButtonsWrapper) {
    rangeButtonsWrapper.addEventListener('click', function (event) {
      const button = event.target.closest('button[data-range]');
      if (!button) return;
      const range = button.getAttribute('data-range');
      if (!range) return;
      state.currentRange = range;
      setActiveRangeButton(range);
      if (state.currentSymbol) {
        loadChart(state.currentSymbol, range);
      }
    });
  }

  ['input', 'change'].forEach(function (evt) {
    if (priceInputs.sharesQty) priceInputs.sharesQty.addEventListener(evt, updateOrderEstimates);
    if (priceInputs.sharesLimit) priceInputs.sharesLimit.addEventListener(evt, updateOrderEstimates);
    if (priceInputs.dollarAmount) priceInputs.dollarAmount.addEventListener(evt, updateOrderEstimates);
  });

  const clearButton = document.getElementById('clear-trade-btn');
  if (clearButton) {
    clearButton.addEventListener('click', function () {
      if (priceInputs.sharesQty) priceInputs.sharesQty.value = '';
      if (priceInputs.sharesLimit) priceInputs.sharesLimit.value = state.livePrice ? state.livePrice.toFixed(2) : '';
      if (priceInputs.dollarAmount) priceInputs.dollarAmount.value = '';
      if (priceInputs.dollarEstimated) priceInputs.dollarEstimated.value = '';
      updateOrderEstimates();
    });
  }

  const submitButton = document.getElementById('submit-trade-btn');
  if (submitButton) {
    submitButton.addEventListener('click', function () {
      const activeTab = document.querySelector('#ticket-mode-tabs .nav-link.active');
      const orderMode = activeTab ? activeTab.textContent.trim() : 'Shares';
      const orderDetails = {
        symbol: state.currentSymbol || '—',
        mode: orderMode,
        quantity: priceInputs.sharesQty ? Number(priceInputs.sharesQty.value) || '—' : '—',
        limitPrice: priceInputs.sharesLimit ? Number(priceInputs.sharesLimit.value) : NaN,
        amount: priceInputs.dollarAmount ? Number(priceInputs.dollarAmount.value) || '—' : '—',
        estimatedShares: priceInputs.dollarEstimated ? Number(priceInputs.dollarEstimated.value) || '—' : '—'
      };

      const detailsText =
        "Symbol: " + orderDetails.symbol + "\n" +
        "Mode: " + orderDetails.mode + "\n" +
        "Quantity: " + (orderDetails.quantity === '—' ? '—' : orderDetails.quantity) + "\n" +
        "Limit Price: " + (isFinite(orderDetails.limitPrice) ? formatCurrency(orderDetails.limitPrice) : '—') + "\n" +
        "Amount: " + (orderDetails.amount === '—' ? '—' : formatCurrency(orderDetails.amount)) + "\n" +
        "Estimated Shares: " + (orderDetails.estimatedShares === '—' ? '—' : orderDetails.estimatedShares);

      if (window.Swal && typeof window.Swal.fire === 'function') {
        Swal.fire({
          icon: 'info',
          title: 'Preview Order',
          html: "<pre class='text-left mb-0'>" + detailsText + "</pre>",
          showCancelButton: true,
          confirmButtonText: 'Confirm Order',
          cancelButtonText: 'Cancel'
        });
      } else {
        alert(detailsText);
      }
    });
  }

  setActiveRangeButton(state.currentRange);
  updateOrderEstimates();

  loadAvailableStocks().finally(function () {
    loadStock(DEFAULT_SYMBOL);
  });
});
</script>




<?php require "../lib/PAGE-bottom.php"; ?>
