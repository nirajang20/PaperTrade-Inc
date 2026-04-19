<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";

$nyseCalendarUrl = "https://www.nyse.com/trade/hours-calendars";
$marketCalendar = [
  "holidays" => [
    "2026-01-01",
    "2026-01-19",
    "2026-02-16",
    "2026-04-03",
    "2026-05-25",
    "2026-06-19",
    "2026-07-03",
    "2026-09-07",
    "2026-11-26",
    "2026-12-25"
  ],
  "early_closes" => [
    "2026-11-27",
    "2026-12-24"
  ],
  "source" => "fallback",
  "source_url" => $nyseCalendarUrl
];

$calendarContext = stream_context_create([
  "http" => [
    "method" => "GET",
    "timeout" => 5,
    "header" => "User-Agent: Mozilla/5.0\r\n"
  ]
]);
$nyseHtml = @file_get_contents($nyseCalendarUrl, false, $calendarContext);
if ($nyseHtml !== false && is_string($nyseHtml) && $nyseHtml !== "") {
  $plain = html_entity_decode(strip_tags($nyseHtml), ENT_QUOTES | ENT_HTML5, "UTF-8");
  $plain = preg_replace('/\s+/u', ' ', $plain);

  $holidayPatterns = [
    '/New Year[\'’]s Day\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2})/u',
    '/Martin Luther King,\s*Jr\.\s*Day\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2})/u',
    '/Washington[\'’]s Birthday\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2})/u',
    '/Good Friday\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2})/u',
    '/Memorial Day\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2})/u',
    '/Juneteenth National Independence Day\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2})/u',
    '/Independence Day\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2})/u',
    '/Labor Day\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2})/u',
    '/Thanksgiving Day\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2})/u',
    '/Christmas Day\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2})/u'
  ];

  $parsedHolidays = [];
  $easternTz = new DateTimeZone("America/New_York");
  foreach ($holidayPatterns as $pattern) {
    if (preg_match($pattern, $plain, $matches) && !empty($matches[1])) {
      $candidate = trim($matches[1]) . " 2026";
      $dt = DateTime::createFromFormat("l, F j Y", $candidate, $easternTz);
      if ($dt instanceof DateTime) {
        $parsedHolidays[] = $dt->format("Y-m-d");
      }
    }
  }

  $parsedEarly = [];
  if (preg_match('/close early at 1:00 p\.m\.[^\.]*on\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2},\s+2026)/iu', $plain, $m1)) {
    $dt = DateTime::createFromFormat("l, F j, Y", trim($m1[1]), $easternTz);
    if ($dt instanceof DateTime) {
      $parsedEarly[] = $dt->format("Y-m-d");
    }
  }
  if (preg_match('/close early at 1:00 p\.m\.[^\.]*on\s+([A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2},\s+2026)[^\.]*December/iu', $plain, $m2)) {
    $dt = DateTime::createFromFormat("l, F j, Y", trim($m2[1]), $easternTz);
    if ($dt instanceof DateTime) {
      $parsedEarly[] = $dt->format("Y-m-d");
    }
  }
  if (preg_match('/Thursday,\s+December\s+24,\s+2026/iu', $plain, $m3)) {
    $dt = DateTime::createFromFormat("l, F j, Y", "Thursday, December 24, 2026", $easternTz);
    if ($dt instanceof DateTime) {
      $parsedEarly[] = $dt->format("Y-m-d");
    }
  }

  $parsedHolidays = array_values(array_unique($parsedHolidays));
  $parsedEarly = array_values(array_unique($parsedEarly));
  if (count($parsedHolidays) >= 8) {
    $marketCalendar["holidays"] = $parsedHolidays;
    if (!empty($parsedEarly)) {
      $marketCalendar["early_closes"] = $parsedEarly;
    }
    $marketCalendar["source"] = "nyse";
  }
}
?>

<style>
  .limit-lost-card {
    border-radius: 0.75rem;
  }
  .limit-lost-label {
    font-weight: 600;
    margin-bottom: 0.35rem;
  }
  .limit-lost-result {
    border-radius: 0.6rem;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    padding: 0.85rem 1rem;
  }
  .limit-lost-result .value {
    font-size: 1.15rem;
    font-weight: 700;
    margin: 0;
    font-variant-numeric: tabular-nums;
  }
  .limit-lost-note {
    font-size: 0.88rem;
    color: #6c757d;
  }
  .market-status-badge {
    font-weight: 700;
    border-radius: 999px;
    padding: 0.3rem 0.75rem;
    display: inline-block;
  }
  .limit-lost-workspace {
    display: grid;
    grid-template-columns: minmax(260px, 340px) minmax(0, 1fr);
    gap: 1rem;
  }
  .stock-selector-list {
    border: 1px solid #dee2e6;
    border-radius: 0.85rem;
    overflow: hidden;
    background: #fff;
  }
  .stock-selector-item {
    border: 0;
    border-bottom: 1px solid #edf2f7;
    background: #fff;
    width: 100%;
    text-align: left;
    padding: 0.9rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    transition: background 0.15s ease, border-color 0.15s ease;
  }
  .stock-selector-item:last-child {
    border-bottom: 0;
  }
  .stock-selector-item:hover {
    background: #f8f9fa;
  }
  .stock-selector-item.active {
    background: rgba(0, 123, 255, 0.08);
    box-shadow: inset 4px 0 0 #007bff;
  }
  .stock-board-symbol,
  .selected-stock-symbol {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
  }
  .stock-board-company,
  .selected-stock-company {
    font-size: 0.88rem;
    color: #6c757d;
    margin: 0.2rem 0 0;
  }
  .stock-board-price,
  .selected-stock-price {
    text-align: right;
  }
  .stock-board-price strong,
  .selected-stock-price strong {
    display: block;
    font-size: 1.05rem;
  }
  .stock-board-price span,
  .selected-stock-price span {
    font-size: 0.88rem;
    font-weight: 600;
  }
  .selected-stock-panel {
    border: 1px solid #dee2e6;
    border-radius: 0.85rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    padding: 1rem 1.1rem;
  }
  .selected-stock-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
  }
  .stock-mini-result {
    border: 1px solid #e9ecef;
    border-radius: 0.65rem;
    background: #f8f9fa;
    padding: 0.75rem;
    margin-top: 0.8rem;
  }
  .stock-mini-result p:last-child {
    margin-bottom: 0;
  }
  .workspace-stack {
    display: grid;
    gap: 1rem;
  }
  .tool-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }
  .ll-receipt-modal .modal-content {
    border-radius: 1rem;
    overflow: hidden;
  }
  .ll-receipt-modal .modal-header {
    padding: 1rem 1.25rem;
  }
  .ll-receipt-modal .modal-title {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: 0.01em;
  }
  .ll-receipt-modal .modal-body {
    padding: 1.25rem;
    background: #fbfdff;
  }
  .ll-receipt-title {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1.2;
  }
  .ll-receipt-body {
    font-size: 1rem;
    line-height: 1.6;
    color: #1f2937;
  }
  .ll-receipt-row {
    display: grid;
    grid-template-columns: minmax(150px, 190px) 1fr;
    gap: 0.75rem;
    align-items: start;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
  }
  .ll-receipt-row:last-child {
    border-bottom: 0;
  }
  .ll-receipt-label {
    font-size: 1rem;
    font-weight: 800;
    color: #111827;
  }
  .ll-receipt-value {
    font-size: 1.05rem;
    font-weight: 600;
    color: #1f2937;
    word-break: break-word;
  }
  .ll-receipt-section {
    margin-top: 1rem;
    margin-bottom: 0.6rem;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  @media (max-width: 991.98px) {
    .limit-lost-workspace,
    .tool-grid {
      grid-template-columns: 1fr;
    }
    .ll-receipt-row {
      grid-template-columns: 1fr;
      gap: 0.2rem;
    }
  }
</style>

<section class="content pt-3">
  <div class="container-fluid">
    <div class="row mb-3">
      <div class="col-12">
        <div class="card card-outline card-warning limit-lost-card">
          <div class="card-body">
            <h3 class="mb-2">Limit Lost</h3>
            <p class="text-muted mb-0">
              Learn and test core trading terms: Buy, Sell, Gain, Loss, and Limit orders.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-6 mb-3">
        <div class="card card-outline card-info h-100 limit-lost-card">
          <div class="card-header">
            <h3 class="card-title mb-0">Quick Meaning</h3>
          </div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-4">Buy</dt>
              <dd class="col-sm-8">You purchase a stock.</dd>

              <dt class="col-sm-4">Sell</dt>
              <dd class="col-sm-8">You give up the stock for money.</dd>

              <dt class="col-sm-4">Gain</dt>
              <dd class="col-sm-8">Profit when selling price is higher than buying price.</dd>

              <dt class="col-sm-4">Loss</dt>
              <dd class="col-sm-8">Money lost when selling price is lower than buying price.</dd>

              <dt class="col-sm-4">Limit</dt>
              <dd class="col-sm-8">Set a specific target price for buy or sell.</dd>
            </dl>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-3">
        <div class="card card-outline card-success h-100 limit-lost-card">
          <div class="card-header">
            <h3 class="card-title mb-0">Formula</h3>
          </div>
          <div class="card-body">
            <p class="mb-2"><strong>Gain</strong> = Sell Price - Buy Price</p>
            <p class="mb-2"><strong>Loss</strong> = Buy Price - Sell Price</p>
            <hr>
            <p class="mb-2"><strong>Limit Buy</strong>: executes only at your price or lower.</p>
            <p class="mb-0"><strong>Limit Sell</strong>: executes only at your price or higher.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="limit-lost-workspace mb-3">
      <div class="workspace-stack">
        <div class="card card-outline card-primary limit-lost-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">10 Stocks</h3>
            <span class="text-muted small">select one</span>
          </div>
          <div class="card-body p-3">
            <div id="llStocksBoard" class="stock-selector-list">
              <div class="limit-lost-result m-3">
                <p class="value mb-1">Loading stock list...</p>
                <p class="limit-lost-note mb-0">Fetching the same symbols used in the trade workflow.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="workspace-stack">
        <div class="card card-outline card-warning limit-lost-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Market Hours Guard (US Core Session)</h3>
            <span id="llMarketBadge" class="market-status-badge badge-secondary">Checking...</span>
          </div>
          <div class="card-body">
            <p class="mb-1">
              Session used: <strong>Monday-Friday, 9:30 AM to 4:00 PM ET</strong> (holidays excluded).
            </p>
            <p id="llMarketDetail" class="mb-1 text-muted">Checking market clock...</p>
            <p id="llMarketNext" class="limit-lost-note mb-1">Next market event will appear here.</p>
            <p class="limit-lost-note mb-0">
              Source:
              <a href="<?= htmlspecialchars($marketCalendar['source_url'], ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer">
                NYSE Holidays & Trading Hours
              </a>
              (<?= htmlspecialchars(strtoupper($marketCalendar['source']), ENT_QUOTES); ?>)
            </p>
          </div>
        </div>

        <div class="selected-stock-panel">
          <div class="selected-stock-header">
            <div>
              <p id="llBoardSymbol" class="selected-stock-symbol">Loading...</p>
              <p id="llBoardCompany" class="selected-stock-company mb-0">Waiting for data...</p>
            </div>
            <div class="selected-stock-price">
              <strong id="llBoardPrice">—</strong>
              <span id="llBoardChange" class="text-muted">—</span>
            </div>
          </div>
          <div class="stock-mini-result">
            <p id="llSelectedStockName" class="value text-primary mb-1">Selected stock: Loading...</p>
            <p id="llSelectedStockMeta" class="limit-lost-note mb-0">Waiting for live stock data.</p>
          </div>
        </div>

        <div class="card card-outline card-primary h-100 limit-lost-card">
          <div class="card-header">
            <h3 class="card-title mb-0">Limit Lost Function</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-lg-4">
                <div class="form-group">
                  <label class="limit-lost-label" for="llBuyPrice">Buy Price ($)</label>
                  <input type="number" min="0" step="0.01" class="form-control" id="llBuyPrice" value="20">
                </div>
                <div class="form-group">
                  <label class="limit-lost-label" for="llSellPrice">Sell Price ($)</label>
                  <input type="number" min="0" step="0.01" class="form-control" id="llSellPrice" value="26">
                </div>
                <div class="form-group">
                  <label class="limit-lost-label" for="llShares">Shares</label>
                  <input type="number" min="1" step="1" class="form-control" id="llShares" value="1">
                </div>
              </div>

              <div class="col-lg-4">
                <div class="form-group">
                  <label class="limit-lost-label" for="llCurrentPrice">Current Price ($)</label>
                  <input type="number" min="0" step="0.01" class="form-control" id="llCurrentPrice" value="50">
                </div>
                <div class="form-group">
                  <label class="limit-lost-label" for="llOrderType">Order Type</label>
                  <select class="form-control" id="llOrderType">
                    <option value="buy">Limit Buy</option>
                    <option value="sell">Limit Sell</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="limit-lost-label" for="llLimitPrice">Limit Price ($)</label>
                  <input type="number" min="0" step="0.01" class="form-control" id="llLimitPrice" value="47">
                </div>
              </div>

              <div class="col-lg-4">
                <div class="form-group">
                  <label class="limit-lost-label" for="llCheckTime">Check Time</label>
                  <input type="datetime-local" class="form-control" id="llCheckTime">
                </div>
                <div class="form-group mb-3">
                  <label class="limit-lost-label" for="llTimePrice">Price At That Time ($)</label>
                  <input type="number" min="0" step="0.01" class="form-control" id="llTimePrice" value="50">
                </div>
              </div>
            </div>

            <div class="mb-3">
              <button type="button" id="llDoneBtn" class="btn btn-primary px-4">Done</button>
            </div>

            <div class="row">
              <div class="col-lg-4 mb-3 mb-lg-0">
                <div class="limit-lost-result h-100">
                  <p class="text-muted small mb-2">Gain/Loss</p>
                  <p id="llPerShare" class="value text-success mb-1">$6.00 gain per share</p>
                  <p id="llTotal" class="mb-1 font-weight-bold">$6.00 total</p>
                  <p id="llCalcHint" class="limit-lost-note mb-0">Gain = 26 - 20 = 6</p>
                </div>
              </div>
              <div class="col-lg-4 mb-3 mb-lg-0">
                <div class="limit-lost-result h-100">
                  <p class="text-muted small mb-2">Live Limit Check</p>
                  <p id="llLimitResult" class="value text-warning mb-1">Waiting</p>
                  <p id="llLimitHint" class="limit-lost-note mb-0">Limit Buy at $47 triggers only when current price is $47 or lower.</p>
                </div>
              </div>
              <div class="col-lg-4">
                <div class="limit-lost-result h-100">
                  <p class="text-muted small mb-2">Time-Based Limit Check</p>
                  <p id="llTimeResult" class="value text-warning mb-1">Waiting for time check</p>
                  <p id="llTimeHint" class="mb-1 font-weight-bold">Choose a date and time to evaluate the selected stock.</p>
                  <p id="llTimeMarket" class="limit-lost-note mb-0">This tool checks both the price rule and whether the chosen time falls inside the market session.</p>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12 mb-3">
        <div class="card card-outline card-dark limit-lost-card">
          <div class="card-header">
            <h3 class="card-title mb-0">Quick Summary</h3>
          </div>
          <div class="card-body">
            <ul class="mb-0">
              <li>Buy = purchase stock</li>
              <li>Sell = get rid of stock</li>
              <li>Gain = profit</li>
              <li>Loss = money lost</li>
              <li>Limit = set a specific buy or sell price</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modal fade ll-receipt-modal" id="llReceiptModal" tabindex="-1" role="dialog" aria-labelledby="llReceiptModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="llReceiptModalLabel">Limit Lost Receipt</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="llReceiptTitle" class="ll-receipt-title text-primary mb-3">No receipt yet</p>
        <div id="llReceiptBody" class="ll-receipt-body">
          Click <strong>Done</strong> to generate a receipt for the selected stock and all entered values.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const apiBase = "https://api.paper-trade.org";
  const trackedSymbols = ["NVDA", "MSFT", "AAPL", "GOOGL", "AMZN", "META", "TSLA", "JPM", "BTC-USD", "ETH-USD"];
  const buyPriceEl = document.getElementById("llBuyPrice");
  const sellPriceEl = document.getElementById("llSellPrice");
  const sharesEl = document.getElementById("llShares");
  const perShareEl = document.getElementById("llPerShare");
  const totalEl = document.getElementById("llTotal");
  const calcHintEl = document.getElementById("llCalcHint");

  const currentPriceEl = document.getElementById("llCurrentPrice");
  const orderTypeEl = document.getElementById("llOrderType");
  const limitPriceEl = document.getElementById("llLimitPrice");
  const limitResultEl = document.getElementById("llLimitResult");
  const limitHintEl = document.getElementById("llLimitHint");
  const marketBadgeEl = document.getElementById("llMarketBadge");
  const marketDetailEl = document.getElementById("llMarketDetail");
  const marketNextEl = document.getElementById("llMarketNext");
  const stocksBoardEl = document.getElementById("llStocksBoard");
  const selectedStockNameEl = document.getElementById("llSelectedStockName");
  const selectedStockMetaEl = document.getElementById("llSelectedStockMeta");
  const boardSymbolEl = document.getElementById("llBoardSymbol");
  const boardCompanyEl = document.getElementById("llBoardCompany");
  const boardPriceEl = document.getElementById("llBoardPrice");
  const boardChangeEl = document.getElementById("llBoardChange");
  const checkTimeEl = document.getElementById("llCheckTime");
  const timePriceEl = document.getElementById("llTimePrice");
  const timeResultEl = document.getElementById("llTimeResult");
  const timeHintEl = document.getElementById("llTimeHint");
  const timeMarketEl = document.getElementById("llTimeMarket");
  const doneBtnEl = document.getElementById("llDoneBtn");
  const receiptTitleEl = document.getElementById("llReceiptTitle");
  const receiptBodyEl = document.getElementById("llReceiptBody");
  const receiptModalEl = document.getElementById("llReceiptModal");

  const stockMap = new Map();
  let selectedSymbol = null;

  const marketCalendar = <?= json_encode($marketCalendar, JSON_UNESCAPED_SLASHES); ?>;
  const usMarketHolidays = Array.isArray(marketCalendar.holidays) ? marketCalendar.holidays : [];
  const usEarlyClose = Array.isArray(marketCalendar.early_closes) ? marketCalendar.early_closes : [];

  const currency = function (value) {
    return "$" + Number(value).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };

  const parseNumber = function (value) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
  };

  const formatSignedPercent = function (value) {
    const numeric = Number(value);
    if (!Number.isFinite(numeric)) {
      return "—";
    }
    return (numeric >= 0 ? "+" : "") + numeric.toFixed(2) + "%";
  };

  const escapeHtml = function (value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  };

  const getEasternParts = function (date) {
    const parts = new Intl.DateTimeFormat("en-US", {
      timeZone: "America/New_York",
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: false,
      weekday: "short"
    }).formatToParts(date);
    const map = {};
    parts.forEach(function (part) {
      if (part.type !== "literal") {
        map[part.type] = part.value;
      }
    });
    return map;
  };

  const getMarketState = function (inputDate) {
    const baseDate = inputDate instanceof Date && !Number.isNaN(inputDate.getTime()) ? inputDate : new Date();
    const et = getEasternParts(baseDate);
    const yyyyMmDd = et.year + "-" + et.month + "-" + et.day;
    const weekDayMap = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 };
    const dayOfWeek = weekDayMap[et.weekday];
    const minutes = Number(et.hour) * 60 + Number(et.minute);

    const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
    const isHoliday = usMarketHolidays.indexOf(yyyyMmDd) !== -1;
    const isEarlyClose = usEarlyClose.indexOf(yyyyMmDd) !== -1;

    const openMinute = 9 * 60 + 30;
    const closeMinute = isEarlyClose ? 13 * 60 : 16 * 60;
    const isOpenSession = !isWeekend && !isHoliday && minutes >= openMinute && minutes < closeMinute;

    return {
      isOpen: isOpenSession,
      isWeekend: isWeekend,
      isHoliday: isHoliday,
      isEarlyClose: isEarlyClose,
      etDate: yyyyMmDd,
      etTime: et.hour + ":" + et.minute + ":" + et.second + " ET",
      dateLabel: et.month + "/" + et.day + "/" + et.year,
      closeLabel: isEarlyClose ? "1:00 PM ET" : "4:00 PM ET"
    };
  };

  const updateMarketStatus = function () {
    const state = getMarketState();
    marketBadgeEl.classList.remove("badge-success", "badge-danger");
    if (state.isOpen) {
      marketBadgeEl.classList.add("badge-success");
      marketBadgeEl.textContent = "OPEN";
      marketDetailEl.textContent = "Market is open now (" + state.etTime + ").";
      marketNextEl.textContent = "Core session closes at " + state.closeLabel + ".";
      return state;
    }

    marketBadgeEl.classList.add("badge-danger");
    marketBadgeEl.textContent = "CLOSED";
    if (state.isHoliday) {
      marketDetailEl.textContent = "Market closed for holiday on " + state.etDate + ".";
    } else if (state.isWeekend) {
      marketDetailEl.textContent = "Market closed for weekend (" + state.etTime + ").";
    } else {
      marketDetailEl.textContent = "Market currently outside core session (" + state.etTime + ").";
    }
    marketNextEl.textContent = "Next regular open: 9:30 AM ET on the next trading day.";
    return state;
  };

  const updateGainLoss = function () {
    const buyPrice = parseNumber(buyPriceEl.value);
    const sellPrice = parseNumber(sellPriceEl.value);
    const shares = Math.max(1, parseNumber(sharesEl.value));
    const deltaPerShare = sellPrice - buyPrice;
    const total = deltaPerShare * shares;

    perShareEl.classList.remove("text-success", "text-danger", "text-secondary");
    totalEl.classList.remove("text-success", "text-danger", "text-secondary");

    if (deltaPerShare > 0) {
      perShareEl.classList.add("text-success");
      totalEl.classList.add("text-success");
      perShareEl.textContent = currency(deltaPerShare) + " gain per share";
      calcHintEl.textContent = "Gain = " + sellPrice + " - " + buyPrice + " = " + deltaPerShare.toFixed(2);
    } else if (deltaPerShare < 0) {
      perShareEl.classList.add("text-danger");
      totalEl.classList.add("text-danger");
      perShareEl.textContent = currency(Math.abs(deltaPerShare)) + " loss per share";
      calcHintEl.textContent = "Loss = " + buyPrice + " - " + sellPrice + " = " + Math.abs(deltaPerShare).toFixed(2);
    } else {
      perShareEl.classList.add("text-secondary");
      totalEl.classList.add("text-secondary");
      perShareEl.textContent = "$0.00 no gain or loss per share";
      calcHintEl.textContent = "No change: sell price equals buy price.";
    }

    totalEl.textContent = currency(Math.abs(total)) + (total > 0 ? " total gain" : (total < 0 ? " total loss" : " total"));
  };

  const updateLimitOrder = function () {
    const marketState = updateMarketStatus();
    const current = parseNumber(currentPriceEl.value);
    const limit = parseNumber(limitPriceEl.value);
    const type = orderTypeEl.value;

    limitResultEl.classList.remove("text-success", "text-warning");

    if (!marketState.isOpen) {
      limitResultEl.classList.add("text-warning");
      limitResultEl.textContent = "Blocked: Market is closed";
      limitHintEl.textContent = "Limit orders are evaluated in this tool only during the US core session.";
      return;
    }

    if (type === "buy") {
      if (current <= limit) {
        limitResultEl.classList.add("text-success");
        limitResultEl.textContent = "Triggered: Buy can execute";
      } else {
        limitResultEl.classList.add("text-warning");
        limitResultEl.textContent = "Waiting: Buy not reached";
      }
      limitHintEl.textContent = "Limit Buy at " + currency(limit) + " triggers only when current price is " + currency(limit) + " or lower.";
      return;
    }

    if (current >= limit) {
      limitResultEl.classList.add("text-success");
      limitResultEl.textContent = "Triggered: Sell can execute";
    } else {
      limitResultEl.classList.add("text-warning");
      limitResultEl.textContent = "Waiting: Sell target not reached";
    }
    limitHintEl.textContent = "Limit Sell at " + currency(limit) + " triggers only when current price is " + currency(limit) + " or higher.";
  };

  const updateTimeLimitCheck = function () {
    const selectedTimeValue = checkTimeEl ? checkTimeEl.value : "";
    const timePrice = parseNumber(timePriceEl ? timePriceEl.value : 0);
    const limit = parseNumber(limitPriceEl.value);
    const type = orderTypeEl.value;

    timeResultEl.classList.remove("text-success", "text-danger", "text-warning", "text-secondary");

    if (!selectedTimeValue) {
      timeResultEl.classList.add("text-secondary");
      timeResultEl.textContent = "Waiting for time check";
      timeHintEl.textContent = "Choose a date and time to evaluate the selected stock.";
      timeMarketEl.textContent = "This tool checks both the price rule and whether the chosen time falls inside the market session.";
      return;
    }

    const selectedDate = new Date(selectedTimeValue);
    if (Number.isNaN(selectedDate.getTime())) {
      timeResultEl.classList.add("text-danger");
      timeResultEl.textContent = "Invalid date/time";
      timeHintEl.textContent = "The chosen time could not be read by the browser.";
      timeMarketEl.textContent = "Pick a valid date and time, then try again.";
      return;
    }

    const marketState = getMarketState(selectedDate);
    const triggered = type === "buy" ? timePrice <= limit : timePrice >= limit;

    if (!marketState.isOpen) {
      timeResultEl.classList.add("text-warning");
      timeResultEl.textContent = "Blocked at selected time";
      timeHintEl.textContent = "The selected time lands outside the market session, so this limit order would not execute then.";
      timeMarketEl.textContent = "Checked against " + marketState.dateLabel + " " + marketState.etTime + ". Market status: closed.";
      return;
    }

    if (triggered) {
      timeResultEl.classList.add("text-success");
      timeResultEl.textContent = "Triggered at selected time";
      timeHintEl.textContent = (type === "buy" ? "Buy" : "Sell") + " condition is satisfied using " + currency(timePrice) + " at the chosen time.";
      timeMarketEl.textContent = "Checked against " + marketState.dateLabel + " " + marketState.etTime + ". Market status: open.";
      return;
    }

    timeResultEl.classList.add("text-danger");
    timeResultEl.textContent = "Not triggered at selected time";
    timeHintEl.textContent = (type === "buy" ? "Buy" : "Sell") + " condition is not satisfied using " + currency(timePrice) + " at the chosen time.";
    timeMarketEl.textContent = "Checked against " + marketState.dateLabel + " " + marketState.etTime + ". Market status: open.";
  };

  const buildReceipt = function () {
    if (!receiptTitleEl || !receiptBodyEl) {
      return;
    }

    const stock = selectedSymbol && stockMap.has(selectedSymbol) ? stockMap.get(selectedSymbol) : null;
    const receiptTime = new Date();
    const orderLabel = orderTypeEl.value === "buy" ? "Limit Buy" : "Limit Sell";
    const checkTimeValue = checkTimeEl && checkTimeEl.value ? checkTimeEl.value : "Not provided";

    const receiptRow = function (label, value) {
      return '<div class="ll-receipt-row"><div class="ll-receipt-label">' + escapeHtml(label) + '</div><div class="ll-receipt-value">' + escapeHtml(value) + '</div></div>';
    };

    receiptTitleEl.textContent = "Receipt for " + (stock && stock.symbol ? stock.symbol : "selected stock");
    receiptBodyEl.innerHTML = [
      '<div class="ll-receipt-section">Stock Details</div>',
      receiptRow("Stock", stock && stock.symbol ? stock.symbol : "—"),
      receiptRow("Company", stock && stock.company_name ? stock.company_name : "Unknown company"),
      receiptRow("Created", receiptTime.toLocaleString()),
      '<div class="ll-receipt-section">Inputs</div>',
      receiptRow("Buy Price", currency(parseNumber(buyPriceEl.value))),
      receiptRow("Sell Price", currency(parseNumber(sellPriceEl.value))),
      receiptRow("Shares", String(Math.max(1, parseNumber(sharesEl.value)))),
      receiptRow("Current Price", currency(parseNumber(currentPriceEl.value))),
      receiptRow("Order Type", orderLabel),
      receiptRow("Limit Price", currency(parseNumber(limitPriceEl.value))),
      receiptRow("Check Time", checkTimeValue),
      receiptRow("Price At That Time", currency(parseNumber(timePriceEl.value))),
      '<div class="ll-receipt-section">Results</div>',
      receiptRow("Gain/Loss", perShareEl.textContent + " | " + totalEl.textContent),
      receiptRow("Live Limit", limitResultEl.textContent),
      receiptRow("Time Limit", timeResultEl.textContent)
    ].join("");
  };

  const openReceiptModal = function () {
    if (window.jQuery && receiptModalEl) {
      window.jQuery(receiptModalEl).modal("show");
    }
  };

  const calculateLimitLost = function () {
    updateGainLoss();
    updateLimitOrder();
    updateTimeLimitCheck();
  };

  const runLimitLostFunction = function () {
    calculateLimitLost();
    buildReceipt();
    openReceiptModal();
  };

  const setSelectedStock = function (symbol, options) {
    if (!symbol || !stockMap.has(symbol)) {
      return;
    }

    const cfg = options || {};
    const stock = stockMap.get(symbol);
    const currentPrice = parseNumber(stock.current_price);
    const changePct = parseNumber(stock.change_pct);
    const isUp = changePct >= 0;

    selectedSymbol = symbol;

    stocksBoardEl.querySelectorAll("[data-symbol]").forEach(function (button) {
      button.classList.toggle("active", button.getAttribute("data-symbol") === symbol);
    });

    if (boardSymbolEl) {
      boardSymbolEl.textContent = stock.symbol || symbol;
    }
    if (boardCompanyEl) {
      boardCompanyEl.textContent = stock.company_name || "Unknown company";
    }
    if (boardPriceEl) {
      boardPriceEl.textContent = Number.isFinite(currentPrice) && currentPrice > 0 ? currency(currentPrice) : "—";
    }
    if (boardChangeEl) {
      boardChangeEl.className = isUp ? "text-success" : "text-danger";
      boardChangeEl.textContent = formatSignedPercent(changePct);
    }

    if (selectedStockNameEl) {
      selectedStockNameEl.textContent = "Selected stock: " + (stock.symbol || symbol);
    }
    if (selectedStockMetaEl) {
      selectedStockMetaEl.textContent = (stock.company_name || "Unknown company") + " | Live price: " + (Number.isFinite(currentPrice) && currentPrice > 0 ? currency(currentPrice) : "—");
    }

    if (!cfg.keepInputs) {
      buyPriceEl.value = currentPrice > 0 ? currentPrice.toFixed(2) : "0.00";
      sellPriceEl.value = currentPrice > 0 ? (currentPrice * 1.05).toFixed(2) : "0.00";
      currentPriceEl.value = currentPrice > 0 ? currentPrice.toFixed(2) : "0.00";
      limitPriceEl.value = currentPrice > 0 ? (currentPrice * 0.98).toFixed(2) : "0.00";
      sharesEl.value = "1";
      orderTypeEl.value = "buy";
    }

    updateGainLoss();
    updateLimitOrder();
    if (timePriceEl) {
      timePriceEl.value = currentPrice > 0 ? currentPrice.toFixed(2) : "0.00";
    }
  };

  const renderStockBoard = function (stocks) {
    if (!stocksBoardEl) {
      return;
    }

    if (!Array.isArray(stocks) || !stocks.length) {
      stocksBoardEl.innerHTML = '<div class="limit-lost-result m-3"><p class="value mb-1">No stocks loaded.</p><p class="limit-lost-note mb-0">The stock list could not be built from the trade symbol list.</p></div>';
      return;
    }

    stockMap.clear();
    stocks.forEach(function (stock) {
      stockMap.set(stock.symbol, stock);
    });

    stocksBoardEl.innerHTML = stocks.map(function (stock) {
      const currentPrice = parseNumber(stock.current_price);
      const changePct = parseNumber(stock.change_pct);
      const changeClass = changePct >= 0 ? "text-success" : "text-danger";

      return `
        <button type="button" class="stock-selector-item" data-symbol="${escapeHtml(stock.symbol)}">
          <div>
            <p class="stock-board-symbol">${escapeHtml(stock.symbol || "—")}</p>
            <p class="stock-board-company">${escapeHtml(stock.company_name || "Unknown company")}</p>
          </div>
          <div class="stock-board-price">
            <strong>${Number.isFinite(currentPrice) && currentPrice > 0 ? currency(currentPrice) : "—"}</strong>
            <span class="${changeClass}">${formatSignedPercent(changePct)}</span>
          </div>
        </button>
      `;
    }).join("");

    setSelectedStock(stocks[0].symbol);
  };

  const loadTrackedStocks = async function () {
    if (!stocksBoardEl) {
      return;
    }

    const query = trackedSymbols.map(function (symbol) {
      return encodeURIComponent(symbol);
    }).join(",");

    try {
      const response = await fetch(apiBase + "/buy-sell-info?symbols=" + query);
      if (!response.ok) {
        throw new Error("Stock API responded with " + response.status);
      }
      const payload = await response.json();
      const validStocks = Array.isArray(payload) ? payload.filter(function (item) {
        return item && item.symbol;
      }) : [];
      renderStockBoard(validStocks);
    } catch (error) {
      console.error("Unable to load tracked stocks", error);
      stocksBoardEl.innerHTML = '<div class="limit-lost-result"><p class="value mb-1">Unable to load stocks.</p><p class="limit-lost-note mb-0">Check the market API connection and refresh this page.</p></div>';
    }
  };

  if (doneBtnEl) {
    doneBtnEl.addEventListener("click", runLimitLostFunction);
  }

  if (stocksBoardEl) {
    stocksBoardEl.addEventListener("click", function (event) {
      const button = event.target.closest("[data-symbol]");
      if (button) {
        setSelectedStock(button.getAttribute("data-symbol"));
      }
    })
  }

  calculateLimitLost();
  updateMarketStatus();
  loadTrackedStocks();
  setInterval(updateMarketStatus, 1000);
});
</script>

<?php require "../lib/PAGE-bottom.php"; ?>
