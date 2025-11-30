<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";

$curatedUniverse = [
  ["symbol" => "AAPL", "sector" => "Technology", "industry" => "Consumer Electronics", "risk" => "Low"],
  ["symbol" => "MSFT", "sector" => "Technology", "industry" => "Software—Infrastructure", "risk" => "Low"],
  ["symbol" => "NVDA", "sector" => "Technology", "industry" => "Semiconductors", "risk" => "Medium"],
  ["symbol" => "TSLA", "sector" => "Consumer Discretionary", "industry" => "Auto Manufacturers", "risk" => "High"],
  ["symbol" => "AMZN", "sector" => "Consumer Discretionary", "industry" => "Internet Retail", "risk" => "Medium"],
  ["symbol" => "GOOGL", "sector" => "Communication Services", "industry" => "Internet Content & Information", "risk" => "Low"],
  ["symbol" => "META", "sector" => "Communication Services", "industry" => "Internet Content & Information", "risk" => "Medium"],
  ["symbol" => "NFLX", "sector" => "Communication Services", "industry" => "Entertainment", "risk" => "Medium"],
  ["symbol" => "AMD", "sector" => "Technology", "industry" => "Semiconductors", "risk" => "High"],
  ["symbol" => "JPM", "sector" => "Financial Services", "industry" => "Banks—Diversified", "risk" => "Low"],
  ["symbol" => "V", "sector" => "Financial Services", "industry" => "Credit Services", "risk" => "Low"],
  ["symbol" => "UNH", "sector" => "Healthcare", "industry" => "Healthcare Plans", "risk" => "Low"],
  ["symbol" => "ABBV", "sector" => "Healthcare", "industry" => "Drug Manufacturers—General", "risk" => "Medium"],
  ["symbol" => "KO", "sector" => "Consumer Defensive", "industry" => "Beverages—Non-Alcoholic", "risk" => "Low"],
  ["symbol" => "XOM", "sector" => "Energy", "industry" => "Oil & Gas Integrated", "risk" => "Medium"],
  ["symbol" => "BA", "sector" => "Industrials", "industry" => "Aerospace & Defense", "risk" => "High"],
  ["symbol" => "SHOP", "sector" => "Technology", "industry" => "Software—Application", "risk" => "High"],
  ["symbol" => "NIO", "sector" => "Consumer Discretionary", "industry" => "Auto Manufacturers", "risk" => "High"],
  ["symbol" => "AAPL", "sector" => "Technology", "industry" => "Consumer Electronics", "risk" => "Low"],
  ["symbol" => "ORCL", "sector" => "Technology", "industry" => "Software—Infrastructure", "risk" => "Medium"],
  ["symbol" => "INTC", "sector" => "Technology", "industry" => "Semiconductors", "risk" => "Medium"],
  ["symbol" => "COST", "sector" => "Consumer Defensive", "industry" => "Discount Stores", "risk" => "Low"]
];

$uniqueCurated = [];
foreach ($curatedUniverse as $item) {
  $symbol = strtoupper($item["symbol"]);
  if (!isset($uniqueCurated[$symbol])) {
    $item["symbol"] = $symbol;
    $uniqueCurated[$symbol] = $item;
  }
}
$curatedUniverse = array_values($uniqueCurated);

$userId = isset($active_user["user_id"]) ? (int) $active_user["user_id"] : 0;
?>

<style>
  .screener-hero {
    border: none;
    background: linear-gradient(135deg, #0f172a, #1e293b 60%, #312e81);
    color: #fff;
    position: relative;
    overflow: hidden;
  }
  .screener-hero::after {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 45%);
    pointer-events: none;
  }
  .screener-hero .card-body {
    position: relative;
    z-index: 1;
  }
  .screener-stat-stack > * {
    border-radius: 0.75rem;
    background: rgba(15, 23, 42, 0.35);
    padding: 1rem 1.25rem;
    min-width: 180px;
    margin-bottom: 1rem;
  }
  .screener-stat-stack > *:last-child {
    margin-bottom: 0;
  }
  @media (min-width: 576px) {
    .screener-stat-stack {
      display: flex;
      flex-direction: row;
    }
    .screener-stat-stack > * {
      margin-bottom: 0;
      margin-right: 1rem;
    }
    .screener-stat-stack > *:last-child {
      margin-right: 0;
    }
  }
  @media (min-width: 992px) {
    .screener-stat-stack {
      flex-direction: column;
    }
    .screener-stat-stack > * {
      margin-right: 0;
      margin-bottom: 1rem;
    }
    .screener-stat-stack > *:last-child {
      margin-bottom: 0;
    }
  }
  .screener-chip {
    border-radius: 999px;
    transition: all 0.2s ease;
  }
  .screener-chip.active,
  .screener-chip:hover {
    color: #fff !important;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    border-color: transparent;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
  }
  .screener-score {
    height: 6px;
    border-radius: 3px;
    background-color: #edf2f7;
    overflow: hidden;
  }
  .screener-score .progress-bar {
    border-radius: 3px;
  }
  .screener-table tbody tr {
    transition: all 0.18s ease;
  }
  .screener-table tbody tr:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(79, 70, 229, 0.08);
  }
  .filter-summary button {
    border-radius: 999px;
  }
  .screener-empty-state {
    border: 2px dashed #cbd5f5;
    border-radius: 1rem;
    background: #f8faff;
  }
  .screener-loading {
    border-radius: 0.75rem;
    border: 1px dashed rgba(79, 70, 229, 0.35);
    background: rgba(79, 70, 229, 0.08);
  }
  .screener-watch-success {
    background: rgba(16, 185, 129, 0.08);
    border-color: rgba(16, 185, 129, 0.2);
  }
</style>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card screener-hero shadow-sm mb-4">
          <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
            <div class="mb-4 mb-lg-0">
              <span class="badge badge-light text-uppercase mb-3 px-3 py-2">Market Toolkit</span>
              <h1 class="display-6 font-weight-bold mb-3">Live Stock Screener</h1>
              <p class="lead mb-0 text-light" style="max-width: 520px;">
                Scan the market with real-time fundamentals, curated presets, and quick shortcuts for building your watchlist.
              </p>
            </div>
            <div class="screener-stat-stack d-flex flex-column flex-sm-row flex-lg-column align-items-stretch">
              <div>
                <h3 class="mb-0" id="screener-stat-symbols">0</h3>
                <span class="text-uppercase small text-white-50">Live symbols</span>
              </div>
              <div>
                <h3 class="mb-0" id="screener-stat-score">0</h3>
                <span class="text-uppercase small text-white-50">Avg. Smart Score</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card card-outline card-light shadow-sm mb-4 border">
          <div class="card-body py-3 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
            <div class="mb-2 mb-lg-0">
              <h6 class="text-uppercase font-weight-semibold text-muted mb-1">Popular Screens</h6>
              <span class="text-muted small">Tap a preset to auto-tune the filters for a specific strategy.</span>
            </div>
            <div class="d-flex flex-wrap">
              <button type="button" class="btn btn-sm btn-outline-primary screener-chip mr-2 mb-2" data-filter-tag="growth">
                <i class="fas fa-rocket mr-1"></i>Growth Momentum
              </button>
              <button type="button" class="btn btn-sm btn-outline-primary screener-chip mr-2 mb-2" data-filter-tag="dividend">
                <i class="fas fa-piggy-bank mr-1"></i>Income Seekers
              </button>
              <button type="button" class="btn btn-sm btn-outline-primary screener-chip mr-2 mb-2" data-filter-tag="stability">
                <i class="fas fa-shield-alt mr-1"></i>Low Volatility
              </button>
              <button type="button" class="btn btn-sm btn-outline-primary screener-chip mb-2" data-filter-tag="value">
                <i class="fas fa-balance-scale mr-1"></i>Value & Quality
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card card-outline card-primary shadow-sm mb-4 border">
          <div class="card-body">
            <form id="screener-filter-form">
              <div class="form-row">
                <div class="form-group col-lg-4 col-md-6">
                  <label for="screener-search" class="font-weight-semibold text-muted text-uppercase small">Symbol or company</label>
                  <div class="input-group input-group-lg">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="search" class="form-control border-0 shadow-none" id="screener-search" placeholder="Type to filter or press Enter to add a symbol">
                  </div>
                </div>
                <div class="form-group col-lg-3 col-md-6">
                  <label for="screener-sector" class="font-weight-semibold text-muted text-uppercase small">Sector</label>
                  <select class="form-control form-control-lg border-0 bg-light" id="screener-sector">
                    <option value="all">All sectors</option>
                  </select>
                </div>
                <div class="form-group col-lg-3 col-md-6">
                  <label for="screener-market-cap" class="font-weight-semibold text-muted text-uppercase small">Market Cap</label>
                  <select class="form-control form-control-lg border-0 bg-light" id="screener-market-cap">
                    <option value="all">Any size</option>
                    <option value="mega">Mega Cap ($200B+)</option>
                    <option value="large">Large Cap ($10B - $200B)</option>
                    <option value="mid">Mid Cap ($2B - $10B)</option>
                    <option value="small">Small Cap ($300M - $2B)</option>
                    <option value="micro">Micro Cap (&lt;$300M)</option>
                  </select>
                </div>
                <div class="form-group col-lg-2 col-md-6">
                  <label for="screener-risk" class="font-weight-semibold text-muted text-uppercase small">Risk Profile</label>
                  <select class="form-control form-control-lg border-0 bg-light" id="screener-risk">
                    <option value="all">Any</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                  </select>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-lg-3 col-md-6">
                  <label for="screener-price-min" class="font-weight-semibold text-muted text-uppercase small">Price Min ($)</label>
                  <input type="number" class="form-control form-control-lg border-0 bg-light" id="screener-price-min" step="0.01">
                </div>
                <div class="form-group col-lg-3 col-md-6">
                  <label for="screener-price-max" class="font-weight-semibold text-muted text-uppercase small">Price Max ($)</label>
                  <input type="number" class="form-control form-control-lg border-0 bg-light" id="screener-price-max" step="0.01">
                </div>
                <div class="form-group col-lg-3 col-md-6">
                  <label for="screener-dividend" class="font-weight-semibold text-muted text-uppercase small">Dividend Preference</label>
                  <select class="form-control form-control-lg border-0 bg-light" id="screener-dividend">
                    <option value="all">Any</option>
                    <option value="high">High Yield (≥ 2%)</option>
                    <option value="moderate">Income (1% - 2%)</option>
                    <option value="low">Growth (0% - 1%)</option>
                    <option value="none">No Dividend</option>
                  </select>
                </div>
                <div class="form-group col-lg-3 col-md-6">
                  <label for="screener-sort" class="font-weight-semibold text-muted text-uppercase small">Sort By</label>
                  <select class="form-control form-control-lg border-0 bg-light" id="screener-sort">
                    <option value="score">Smart Score (High → Low)</option>
                    <option value="momentum">Momentum (1W Change)</option>
                    <option value="priceAsc">Price (Low → High)</option>
                    <option value="priceDesc">Price (High → Low)</option>
                    <option value="peAsc">P/E (Low → High)</option>
                    <option value="peDesc">P/E (High → Low)</option>
                    <option value="dividendDesc">Dividend Yield (High → Low)</option>
                  </select>
                </div>
              </div>
              <div class="d-flex align-items-center justify-content-between flex-wrap filter-summary mt-3">
                <div id="screener-active-filters" class="mb-3 mb-md-0"></div>
                <button type="button" class="btn btn-outline-secondary btn-lg" id="screener-reset">
                  <i class="fas fa-undo mr-1"></i>Reset Filters
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8 col-xl-9">
        <div class="card shadow-sm border">
          <div class="card-header d-flex align-items-center justify-content-between">
            <div>
              <h3 class="card-title mb-0">Matching Equities</h3>
              <small class="text-muted d-block" id="screener-result-summary">Pulling live market data…</small>
            </div>
            <span class="badge badge-primary badge-pill screener-badge" id="screener-result-count">0</span>
          </div>
          <div class="card-body">
            <div class="table-responsive" id="screener-table-wrapper">
              <table class="table table-hover align-middle screener-table">
                <thead class="thead-light">
                  <tr class="text-uppercase text-muted small">
                    <th>Symbol</th>
                    <th>Price</th>
                    <th>Market Cap</th>
                    <th>P/E</th>
                    <th>Dividend</th>
                    <th>Momentum</th>
                    <th>Score</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="screener-results-body">
                  <tr id="screener-loading-row">
                    <td colspan="8" class="text-center py-5">
                      <div class="screener-loading d-inline-flex flex-column align-items-center px-4 py-3">
                        <div class="spinner-border text-primary mb-3" role="status">
                          <span class="sr-only">Loading…</span>
                        </div>
                        <h6 class="font-weight-semibold mb-1">Loading live data</h6>
                        <p class="text-muted mb-0 small">This can take a few seconds while we fetch fundamentals for each ticker.</p>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div id="screener-no-results" class="screener-empty-state text-center py-5 px-3 d-none">
              <div class="mb-3">
                <span class="badge badge-light px-3 py-2 text-uppercase font-weight-semibold">No Matches</span>
              </div>
              <h5 class="font-weight-bold mb-2">No companies match your current filters.</h5>
              <p class="text-muted mb-3">Try broadening your price range, adjusting sector preferences, or clearing a preset.</p>
              <button type="button" class="btn btn-primary" id="screener-empty-reset">
                <i class="fas fa-redo mr-1"></i>Reset Filters
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-xl-3">
        <div class="card shadow-sm border mb-4">
          <div class="card-header border-0 pb-0">
            <h3 class="card-title mb-0">Market Snapshot</h3>
            <small class="text-muted">Distribution across tracked symbols</small>
          </div>
          <div class="card-body pt-3" id="screener-sector-distribution">
            <p class="text-muted small mb-0">Loading sector mix…</p>
          </div>
        </div>

        <div class="card shadow-sm border">
          <div class="card-header border-0 pb-0">
            <h3 class="card-title mb-0">Spotlight Lists</h3>
            <small class="text-muted">Auto-curated from live metrics</small>
          </div>
          <div class="card-body pt-3">
            <h6 class="text-uppercase text-muted small font-weight-semibold mb-2">Dividend Strength</h6>
            <ul class="list-group list-group-flush mb-3" id="screener-dividend-leaders">
              <li class="list-group-item px-0 text-muted small">Loading…</li>
            </ul>

            <h6 class="text-uppercase text-muted small font-weight-semibold mb-2">Momentum Movers</h6>
            <ul class="list-group list-group-flush mb-3" id="screener-momentum-leaders">
              <li class="list-group-item px-0 text-muted small">Loading…</li>
            </ul>

            <h6 class="text-uppercase text-muted small font-weight-semibold mb-2">Value Spotlight</h6>
            <ul class="list-group list-group-flush" id="screener-value-leaders">
              <li class="list-group-item px-0 text-muted small">Loading…</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  const USER_ID = <?= $userId ?>;
  const SCREENER_CONFIG = <?=
    json_encode(
      [
        "universe" => $curatedUniverse,
        "apiBase" => "http://74.208.158.229/stock-api",
        "watchlistEndpoint" => "screener-actions.php"
      ],
      JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );
  ?>;
</script>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const apiBase = SCREENER_CONFIG.apiBase;
    const universeMeta = new Map(
      (SCREENER_CONFIG.universe || []).map((item) => [item.symbol, item])
    );

    const elements = {
      form: document.getElementById("screener-filter-form"),
      search: document.getElementById("screener-search"),
      sector: document.getElementById("screener-sector"),
      marketCap: document.getElementById("screener-market-cap"),
      risk: document.getElementById("screener-risk"),
      priceMin: document.getElementById("screener-price-min"),
      priceMax: document.getElementById("screener-price-max"),
      dividend: document.getElementById("screener-dividend"),
      sort: document.getElementById("screener-sort"),
      reset: document.getElementById("screener-reset"),
      emptyReset: document.getElementById("screener-empty-reset"),
      filterTags: document.getElementById("screener-active-filters"),
      resultCount: document.getElementById("screener-result-count"),
      resultSummary: document.getElementById("screener-result-summary"),
      tableBody: document.getElementById("screener-results-body"),
      tableWrapper: document.getElementById("screener-table-wrapper"),
      noResults: document.getElementById("screener-no-results"),
      loadingRow: document.getElementById("screener-loading-row"),
      sectorDistribution: document.getElementById("screener-sector-distribution"),
      dividendLeaders: document.getElementById("screener-dividend-leaders"),
      momentumLeaders: document.getElementById("screener-momentum-leaders"),
      valueLeaders: document.getElementById("screener-value-leaders"),
      statSymbols: document.getElementById("screener-stat-symbols"),
      statScore: document.getElementById("screener-stat-score")
    };

    const quickFilterButtons = document.querySelectorAll("[data-filter-tag]");
    const dataStore = new Map();
    const detailCache = new Map();

    const defaultState = {
      search: "",
      sector: "all",
      marketCap: "all",
      risk: "all",
      dividend: "all",
      sortBy: "score",
      priceMin: null,
      priceMax: null,
      quickFilter: null
    };

    const state = { ...defaultState };
    const PRICE_RANGE = { min: null, max: null };

    const QUICK_FILTER_LABELS = {
      growth: "Growth Momentum",
      dividend: "Income Seekers",
      stability: "Low Volatility",
      value: "Value & Quality"
    };

    const notify = (type, message) => {
      if (window.toastr && typeof window.toastr[type] === "function") {
        window.toastr[type](message);
      } else {
        console.info(type.toUpperCase() + ": " + message);
      }
    };

    const parseNumber = (value) => {
      if (value === null || value === undefined) return null;
      if (typeof value === "number" && Number.isFinite(value)) return value;
      const clean = String(value).replace(/[^0-9.\-]+/g, "");
      const parsed = Number.parseFloat(clean);
      return Number.isFinite(parsed) ? parsed : null;
    };

    const parsePercentValue = (value) => {
      if (value === null || value === undefined) return null;
      if (typeof value === "number" && Number.isFinite(value)) return value;
      const clean = String(value).replace(/[^0-9.\-]+/g, "");
      const parsed = Number.parseFloat(clean);
      return Number.isFinite(parsed) ? parsed : null;
    };

    const parseMarketCap = (value) => {
      if (Number.isFinite(value)) return value;
      if (!value) return null;
      const clean = String(value).trim().toUpperCase();
      const multiplier = clean.endsWith("T") ? 1e12 :
        clean.endsWith("B") ? 1e9 :
          clean.endsWith("M") ? 1e6 :
            clean.endsWith("K") ? 1e3 : 1;
      const numberPart = clean.replace(/[^\d.+-]/g, "");
      const parsed = Number.parseFloat(numberPart);
      return Number.isFinite(parsed) ? parsed * multiplier : null;
    };

    const marketCapBucket = (value) => {
      if (!Number.isFinite(value)) return "unknown";
      if (value >= 200_000_000_000) return "mega";
      if (value >= 10_000_000_000) return "large";
      if (value >= 2_000_000_000) return "mid";
      if (value >= 300_000_000) return "small";
      return "micro";
    };

    const formatCurrency = (value) => {
      if (!Number.isFinite(value)) return "—";
      return "$" + value.toFixed(2);
    };

    const formatMarketCap = (value) => {
      if (!Number.isFinite(value)) return "—";
      if (value >= 1e12) return (value / 1e12).toFixed(2) + "T";
      if (value >= 1e9) return (value / 1e9).toFixed(2) + "B";
      if (value >= 1e6) return (value / 1e6).toFixed(2) + "M";
      if (value >= 1e3) return (value / 1e3).toFixed(2) + "K";
      return value.toLocaleString();
    };

    const formatPercentHTML = (value) => {
      if (!Number.isFinite(value)) {
        return `<span class="text-muted">—</span>`;
      }
      const cls = value > 0 ? "text-success" : value < 0 ? "text-danger" : "text-muted";
      const sign = value > 0 ? "+" : value < 0 ? "" : "";
      return `<span class="${cls}">${sign}${value.toFixed(1)}%</span>`;
    };

    const formatDividend = (value) => {
      if (value === null || value === undefined) return "—";
      if (!Number.isFinite(value)) {
        const parsed = parsePercentValue(value);
        return Number.isFinite(parsed) ? parsed.toFixed(1) + "%" : "—";
      }
      return value.toFixed(1) + "%";
    };

    const escapeHtml = (value) => {
      return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    };

    const computeScore = (stock) => {
      let score = 50;
      if (Number.isFinite(stock.changePct)) {
        score += Math.max(Math.min(stock.changePct * 3, 12), -12);
      }
      if (Number.isFinite(stock.weekChange)) {
        score += Math.max(Math.min(stock.weekChange * 2, 20), -20);
      }
      if (Number.isFinite(stock.pe)) {
        if (stock.pe >= 10 && stock.pe <= 30) score += 8;
        else if (stock.pe < 0 || stock.pe > 60) score -= 6;
      }
      if (Number.isFinite(stock.dividendYield)) {
        if (stock.dividendYield >= 2) score += 6;
        else if (stock.dividendYield === 0) score -= 2;
      }
      if (stock.risk === "Low") score += 6;
      if (stock.risk === "High") score -= 6;
      if (stock.marketCapBucket === "mega" || stock.marketCapBucket === "large") score += 4;
      if (stock.marketCapBucket === "micro") score -= 4;
      score = Math.max(20, Math.min(95, Math.round(score)));
      return score;
    };

    const deriveRisk = (metaRisk, bucket, volatility) => {
      if (metaRisk && ["Low", "Medium", "High"].includes(metaRisk)) {
        return metaRisk;
      }
      if (["mega", "large"].includes(bucket)) return volatility > 3 ? "Medium" : "Low";
      if (bucket === "mid") return volatility > 4 ? "High" : "Medium";
      return "High";
    };

    const ensureSectorOptions = () => {
      const sectors = Array.from(dataStore.values()).reduce((acc, stock) => {
        if (stock.sector) acc.add(stock.sector);
        return acc;
      }, new Set());
      const existing = new Set(Array.from(elements.sector.options).map((opt) => opt.value));
      sectors.forEach((sector) => {
        if (!existing.has(sector)) {
          const opt = document.createElement("option");
          opt.value = sector;
          opt.textContent = sector;
          elements.sector.appendChild(opt);
        }
      });
    };

    const updateHeroStats = () => {
      const stocks = Array.from(dataStore.values());
      const total = stocks.length;
      const avgScore = total
        ? (stocks.reduce((sum, item) => sum + (Number.isFinite(item.score) ? item.score : 0), 0) / total)
        : 0;
      elements.statSymbols.textContent = total;
      elements.statScore.textContent = avgScore ? avgScore.toFixed(1) : "0";
    };

    const renderSectorDistribution = () => {
      const counts = {};
      let total = 0;
      dataStore.forEach((stock) => {
        const sector = stock.sector || "Unclassified";
        counts[sector] = (counts[sector] || 0) + 1;
        total += 1;
      });
      if (!total) {
        elements.sectorDistribution.innerHTML = '<p class="text-muted small mb-0">No data available yet.</p>';
        return;
      }
      const rows = Object.entries(counts)
        .sort((a, b) => b[1] - a[1])
        .map(([sector, count]) => {
          const percent = Math.round((count / total) * 100);
          return `
            <div class="mb-2">
              <div class="d-flex justify-content-between">
                <span class="font-weight-semibold">${escapeHtml(sector)}</span>
                <span class="text-muted small">${percent}%</span>
              </div>
              <div class="progress progress-xs">
                <div class="progress-bar bg-gradient-primary" role="progressbar" style="width: ${percent}%" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
          `;
        });
      elements.sectorDistribution.innerHTML = rows.join("");
    };

    const renderLeaders = () => {
      const stocks = Array.from(dataStore.values());
      const dividend = stocks
        .filter((stock) => Number.isFinite(stock.dividendYield) && stock.dividendYield > 0)
        .sort((a, b) => b.dividendYield - a.dividendYield)
        .slice(0, 3);
      elements.dividendLeaders.innerHTML = dividend.length
        ? dividend.map((stock) => `
          <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
            <div>
              <div class="font-weight-semibold">${escapeHtml(stock.symbol)} <span class="text-muted">• ${escapeHtml(stock.name)}</span></div>
              <small class="text-muted">${escapeHtml(stock.sector || "—")}</small>
            </div>
            <span class="badge badge-success badge-pill">${stock.dividendYield.toFixed(1)}%</span>
          </li>
        `).join("")
        : '<li class="list-group-item px-0 text-muted small">No dividend payers loaded yet.</li>';

      const momentum = stocks
        .filter((stock) => Number.isFinite(stock.weekChange))
        .sort((a, b) => b.weekChange - a.weekChange)
        .slice(0, 3);
      elements.momentumLeaders.innerHTML = momentum.length
        ? momentum.map((stock) => `
          <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
            <div>
              <div class="font-weight-semibold">${escapeHtml(stock.symbol)} <span class="text-muted">• ${escapeHtml(stock.name)}</span></div>
              <small class="text-muted">1W ${stock.weekChange.toFixed(1)}% · 1D ${Number.isFinite(stock.changePct) ? stock.changePct.toFixed(1) + "%" : "—"}</small>
            </div>
            <span class="badge badge-info badge-pill">${stock.score}</span>
          </li>
        `).join("")
        : '<li class="list-group-item px-0 text-muted small">Momentum data not available.</li>';

      const value = stocks
        .filter((stock) => Number.isFinite(stock.pe) && stock.pe > 0)
        .sort((a, b) => a.pe - b.pe)
        .slice(0, 3);
      elements.valueLeaders.innerHTML = value.length
        ? value.map((stock) => `
          <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
            <div>
              <div class="font-weight-semibold">${escapeHtml(stock.symbol)} <span class="text-muted">• ${escapeHtml(stock.name)}</span></div>
              <small class="text-muted">${escapeHtml(stock.sector || "—")}</small>
            </div>
            <span class="badge badge-warning badge-pill">P/E ${stock.pe.toFixed(1)}</span>
          </li>
        `).join("")
        : '<li class="list-group-item px-0 text-muted small">Insufficient valuation data.</li>';
    };

    const buildRow = (stock) => {
      const riskBadgeClass = stock.risk === "Low"
        ? "badge-success"
        : stock.risk === "Medium"
          ? "badge-warning"
          : "badge-danger";
      const scoreColor = stock.score >= 85
        ? "bg-success"
        : stock.score >= 70
          ? "bg-info"
          : "bg-warning";
      const watchDisabled = USER_ID ? "" : "disabled";
      return `
        <tr>
          <td>
            <div class="font-weight-bold text-dark mb-1">${escapeHtml(stock.symbol)}</div>
            <div class="text-muted small">${escapeHtml(stock.name || "—")}</div>
            <span class="badge badge-light border mt-2">${escapeHtml(stock.sector || "Unclassified")}</span>
          </td>
          <td>
            <div class="font-weight-semibold">${formatCurrency(stock.price)}</div>
            <small class="text-muted d-block">Exchange ${escapeHtml(stock.exchange || "—")}</small>
          </td>
          <td>
            <div class="font-weight-semibold">${formatMarketCap(stock.marketCap)}</div>
            <small class="text-muted">${escapeHtml(stock.industry || "—")}</small>
          </td>
          <td>
            <div class="font-weight-semibold">${Number.isFinite(stock.pe) && stock.pe > 0 ? stock.pe.toFixed(1) : "—"}</div>
            <small class="text-muted">Forward outlook</small>
          </td>
          <td>
            <div class="font-weight-semibold">${formatDividend(stock.dividendYield)}</div>
            <small class="text-muted">Yield annualized</small>
          </td>
          <td>
            <div class="font-weight-semibold">1W ${formatPercentHTML(stock.weekChange)}</div>
            <small class="d-block">1D ${formatPercentHTML(stock.changePct)}</small>
          </td>
          <td>
            <div class="d-flex align-items-center">
              <span class="font-weight-bold mr-2">${stock.score}</span>
              <div class="progress flex-grow-1 screener-score">
                <div class="progress-bar ${scoreColor}" role="progressbar" style="width: ${Math.min(stock.score, 100)}%;" aria-valuenow="${stock.score}" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
            <span class="badge ${riskBadgeClass} badge-pill mt-2">${escapeHtml(stock.risk)} Risk</span>
          </td>
          <td class="text-right">
            <button type="button" class="btn btn-sm btn-outline-primary" data-screener-watch="${escapeHtml(stock.symbol)}" ${watchDisabled}>
              <i class="fas fa-star mr-1"></i>Watch
            </button>
          </td>
        </tr>
      `;
    };

    const updateFilterTags = () => {
      const tags = [];
      if (state.search) tags.push({ key: "search", label: `Search: “${state.search}”` });
      if (state.sector !== "all") tags.push({ key: "sector", label: `Sector: ${state.sector}` });
      if (state.marketCap !== "all") {
        const labels = {
          mega: "Mega cap",
          large: "Large cap",
          mid: "Mid cap",
          small: "Small cap",
          micro: "Micro cap"
        };
        tags.push({ key: "marketCap", label: labels[state.marketCap] || state.marketCap });
      }
      if (state.risk !== "all") tags.push({ key: "risk", label: `${state.risk} risk` });
      if (state.dividend !== "all") {
        const labels = {
          high: "High yield",
          moderate: "Income 1-2%",
          low: "Growth dividend",
          none: "No dividend"
        };
        tags.push({ key: "dividend", label: labels[state.dividend] || "Dividend" });
      }
      if (state.quickFilter) {
        tags.push({ key: "quickFilter", label: QUICK_FILTER_LABELS[state.quickFilter] || "Preset" });
      }
      if (Number.isFinite(state.priceMin) || Number.isFinite(state.priceMax)) {
        const min = Number.isFinite(state.priceMin) ? state.priceMin.toFixed(2) : PRICE_RANGE.min?.toFixed(2) || "0.00";
        const max = Number.isFinite(state.priceMax) ? state.priceMax.toFixed(2) : PRICE_RANGE.max?.toFixed(2) || "0.00";
        tags.push({ key: "price", label: `Price $${min} - $${max}` });
      }

      if (!tags.length) {
        elements.filterTags.innerHTML = '<span class="text-muted small">No advanced filters applied.</span>';
        return;
      }

      elements.filterTags.innerHTML = tags.map((tag) => `
        <button type="button" class="btn btn-outline-secondary btn-sm mr-2 mb-2" data-remove-filter="${tag.key}">
          ${escapeHtml(tag.label)} <i class="fas fa-times ml-1"></i>
        </button>
      `).join("");
    };

    const matchesDividendPreference = (stock) => {
      const yieldValue = stock.dividendYield;
      switch (state.dividend) {
        case "high":
          return Number.isFinite(yieldValue) && yieldValue >= 2;
        case "moderate":
          return Number.isFinite(yieldValue) && yieldValue >= 1 && yieldValue < 2;
        case "low":
          return Number.isFinite(yieldValue) && yieldValue > 0 && yieldValue < 1;
        case "none":
          return yieldValue === 0 || !Number.isFinite(yieldValue);
        default:
          return true;
      }
    };

    const matchesQuickFilter = (stock) => {
      switch (state.quickFilter) {
        case "growth":
          return Number.isFinite(stock.weekChange) && stock.weekChange >= 4 && Number.isFinite(stock.pe) && stock.pe >= 25;
        case "dividend":
          return Number.isFinite(stock.dividendYield) && stock.dividendYield >= 2 && (stock.risk === "Low" || stock.risk === "Medium");
        case "stability":
          return stock.risk === "Low" && Number.isFinite(stock.changePct) && stock.changePct > -1 && Number.isFinite(stock.weekChange) && stock.weekChange >= 0;
        case "value":
          return Number.isFinite(stock.pe) && stock.pe > 0 && stock.pe <= 18 && ["mega", "large"].includes(stock.marketCapBucket);
        default:
          return true;
      }
    };

    const comparators = {
      score: (a, b) => b.score - a.score,
      momentum: (a, b) => {
        const aMomentum = Number.isFinite(a.weekChange) ? a.weekChange : -Infinity;
        const bMomentum = Number.isFinite(b.weekChange) ? b.weekChange : -Infinity;
        return bMomentum - aMomentum;
      },
      priceAsc: (a, b) => a.price - b.price,
      priceDesc: (a, b) => b.price - a.price,
      peAsc: (a, b) => {
        const aVal = Number.isFinite(a.pe) && a.pe > 0 ? a.pe : Number.POSITIVE_INFINITY;
        const bVal = Number.isFinite(b.pe) && b.pe > 0 ? b.pe : Number.POSITIVE_INFINITY;
        return aVal - bVal;
      },
      peDesc: (a, b) => {
        const aVal = Number.isFinite(a.pe) && a.pe > 0 ? a.pe : Number.NEGATIVE_INFINITY;
        const bVal = Number.isFinite(b.pe) && b.pe > 0 ? b.pe : Number.NEGATIVE_INFINITY;
        return bVal - aVal;
      },
      dividendDesc: (a, b) => {
        const aVal = Number.isFinite(a.dividendYield) ? a.dividendYield : -Infinity;
        const bVal = Number.isFinite(b.dividendYield) ? b.dividendYield : -Infinity;
        return bVal - aVal;
      }
    };

    const applyFilters = () => {
      const rows = Array.from(dataStore.values()).filter((stock) => {
        if (state.search) {
          const query = state.search.toLowerCase();
          const haystack = `${stock.symbol} ${stock.name || ""} ${stock.industry || ""}`.toLowerCase();
          if (!haystack.includes(query)) return false;
        }
        if (state.sector !== "all" && stock.sector !== state.sector) return false;
        if (state.marketCap !== "all" && stock.marketCapBucket !== state.marketCap) return false;
        if (state.risk !== "all" && stock.risk !== state.risk) return false;
        if (!matchesDividendPreference(stock)) return false;
        if (Number.isFinite(state.priceMin) && (!Number.isFinite(stock.price) || stock.price < state.priceMin)) return false;
        if (Number.isFinite(state.priceMax) && (!Number.isFinite(stock.price) || stock.price > state.priceMax)) return false;
        if (!matchesQuickFilter(stock)) return false;
        return true;
      });

      const comparator = comparators[state.sortBy] || comparators.score;
      rows.sort(comparator);

      if (elements.loadingRow) {
        elements.loadingRow.remove();
        elements.loadingRow = null;
      }

      elements.tableBody.innerHTML = rows.map((stock) => buildRow(stock)).join("");
      elements.resultCount.textContent = rows.length;
      if (rows.length) {
        const quickLabel = state.quickFilter ? ` · ${QUICK_FILTER_LABELS[state.quickFilter]}` : "";
        elements.resultSummary.textContent = `Showing ${rows.length} of ${dataStore.size} equities${quickLabel}`;
      } else {
        elements.resultSummary.textContent = "Adjust the filters to find matching equities.";
      }

      const hasResults = rows.length > 0;
      elements.noResults.classList.toggle("d-none", hasResults);
      elements.tableWrapper.classList.toggle("d-none", !hasResults);
    };

    const updateInputsFromState = () => {
      elements.search.value = state.search;
      elements.sector.value = elements.sector.querySelector(`option[value="${state.sector}"]`) ? state.sector : "all";
      elements.marketCap.value = state.marketCap;
      elements.risk.value = state.risk;
      elements.dividend.value = state.dividend;
      elements.sort.value = state.sortBy;
      elements.priceMin.value = Number.isFinite(state.priceMin) ? state.priceMin : "";
      elements.priceMax.value = Number.isFinite(state.priceMax) ? state.priceMax : "";
    };

    const resetFilters = () => {
      Object.assign(state, defaultState);
      state.priceMin = PRICE_RANGE.min;
      state.priceMax = PRICE_RANGE.max;
      quickFilterButtons.forEach((button) => button.classList.remove("active"));
      updateInputsFromState();
      applyFilters();
      updateFilterTags();
    };

    const fetchSummaryBatch = async (symbols) => {
      const chunks = [];
      const chunkSize = 10;
      for (let i = 0; i < symbols.length; i += chunkSize) {
        chunks.push(symbols.slice(i, i + chunkSize));
      }
      const result = new Map();
      for (const chunk of chunks) {
        try {
          const response = await fetch(`${apiBase}/buy-sell-info?symbols=${encodeURIComponent(chunk.join(","))}`);
          if (!response.ok) throw new Error(`Summary API responded with ${response.status}`);
          const data = await response.json();
          if (Array.isArray(data)) {
            data.forEach((item) => {
              if (item && item.symbol) {
                result.set(item.symbol.toUpperCase(), item);
              }
            });
          }
        } catch (error) {
          console.error("Summary load failed:", error);
          notify("error", "Unable to load summary data for " + chunk.join(", "));
        }
      }
      return result;
    };

    const fetchDetail = async (symbol) => {
      const upper = symbol.toUpperCase();
      if (detailCache.has(upper)) {
        return detailCache.get(upper);
      }
      try {
        const response = await fetch(`${apiBase}/buy-sell?symbol=${encodeURIComponent(upper)}`);
        if (!response.ok) throw new Error(`Detail API responded with ${response.status}`);
        const data = await response.json();
        detailCache.set(upper, data);
        return data;
      } catch (error) {
        console.error("Detail load failed:", error);
        notify("error", `Unable to load fundamentals for ${upper}.`);
        return null;
      }
    };

    const enrichStock = (symbol, summary, detail) => {
      const meta = universeMeta.get(symbol) || { symbol };
      const selected = detail && detail.selected_stock ? detail.selected_stock : {};
      const metrics = selected.metrics || {};
      const priceChange = selected.price_change || {};
      const dayRange = metrics.day_range || {};
      const weekRange = metrics["52w_range"] || metrics.week_range || {};

      const marketCapValue = parseMarketCap(metrics.market_cap);
      const peValue = parseNumber(metrics.pe_ratio);
      const dividendValue = parsePercentValue(metrics.dividend_yield);
      const weekChangeValue = parsePercentValue(metrics.week_change_pct || metrics.change_week || null);
      const dayChangeValue = summary && Number.isFinite(summary.change_pct)
        ? summary.change_pct
        : parsePercentValue(priceChange.percentage);
      const priceValue = summary && Number.isFinite(summary.current_price)
        ? summary.current_price
        : parseNumber(selected.last_price);

      const stock = {
        symbol: symbol,
        name: summary && summary.company_name ? summary.company_name : selected.company_name || symbol,
        sector: meta.sector || (Array.isArray(selected.tags) ? selected.tags[0] : selected.sector) || "Unclassified",
        industry: meta.industry || selected.industry || (selected.tags && selected.tags[1]) || "—",
        risk: deriveRisk(meta.risk, marketCapBucket(marketCapValue), Number.isFinite(dayChangeValue) ? Math.abs(dayChangeValue) : 3),
        marketCap: marketCapValue,
        marketCapBucket: marketCapBucket(marketCapValue),
        pe: peValue,
        dividendYield: dividendValue !== null ? dividendValue : 0,
        weekChange: Number.isFinite(weekChangeValue) ? weekChangeValue : dayChangeValue,
        changePct: Number.isFinite(dayChangeValue) ? dayChangeValue : null,
        price: Number.isFinite(priceValue) ? priceValue : null,
        exchange: summary && summary.exchange ? summary.exchange : selected.exchange || "—",
        tags: Array.isArray(selected.tags) ? selected.tags : [],
        volume: parseNumber(metrics.volume),
        dayRange: [parseNumber(dayRange.low), parseNumber(dayRange.high)],
        weekRange: [parseNumber(weekRange.low), parseNumber(weekRange.high)],
        score: 0
      };

      stock.score = computeScore(stock);
      return stock;
    };

    const loadSymbols = async (symbols) => {
      const upperSymbols = symbols.map((symbol) => symbol.toUpperCase());
      const summaryMap = await fetchSummaryBatch(upperSymbols);
      const detailResults = await Promise.all(upperSymbols.map((symbol) => fetchDetail(symbol)));

      upperSymbols.forEach((symbol, index) => {
        const summary = summaryMap.get(symbol) || null;
        const detail = detailResults[index];
        if (!summary && !detail) {
          return;
        }
        const stock = enrichStock(symbol, summary, detail);
        dataStore.set(symbol, stock);
      });

      ensureSectorOptions();
      updateHeroStats();
      renderSectorDistribution();
      renderLeaders();
      updateUniversePriceRange();
      applyFilters();
      updateFilterTags();
    };

    const updateUniversePriceRange = () => {
      const prices = Array.from(dataStore.values())
        .map((stock) => stock.price)
        .filter((value) => Number.isFinite(value));
      if (!prices.length) {
        PRICE_RANGE.min = 0;
        PRICE_RANGE.max = 0;
        return;
      }
      PRICE_RANGE.min = Math.floor(Math.min(...prices));
      PRICE_RANGE.max = Math.ceil(Math.max(...prices));
      if (!Number.isFinite(state.priceMin)) state.priceMin = PRICE_RANGE.min;
      if (!Number.isFinite(state.priceMax)) state.priceMax = PRICE_RANGE.max;
      updateInputsFromState();
    };

    const addNewSymbol = async (rawSymbol) => {
      const symbol = (rawSymbol || "").trim().toUpperCase();
      if (!symbol) return;
      if (dataStore.has(symbol)) {
        notify("info", `${symbol} is already in the screener.`);
        state.search = symbol;
        updateInputsFromState();
        applyFilters();
        return;
      }
      notify("info", `Fetching live data for ${symbol}…`);
      await loadSymbols([symbol]);
      if (dataStore.has(symbol)) {
        universeMeta.set(symbol, { symbol, sector: dataStore.get(symbol).sector, industry: dataStore.get(symbol).industry, risk: dataStore.get(symbol).risk });
        notify("success", `${symbol} has been added to the screener.`);
        state.search = symbol;
        updateInputsFromState();
        applyFilters();
      } else {
        notify("error", `Unable to add ${symbol}. Please verify the ticker.`);
      }
    };

    const addToWatchlist = async (symbol, button) => {
      if (!USER_ID) {
        notify("error", "Log in to add symbols to your watchlist.");
        return;
      }
      const payload = {
        action: "watchlist:add",
        symbol: symbol,
        note: null
      };
      button.disabled = true;
      button.classList.remove("btn-outline-primary");
      button.classList.add("btn-secondary");
      try {
        const response = await fetch(SCREENER_CONFIG.watchlistEndpoint, {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(payload)
        });
        if (!response.ok) {
          throw new Error(`Watchlist endpoint responded with ${response.status}`);
        }
        const result = await response.json();
        if (result.status === "success") {
          notify("success", `${symbol} added to your watchlist.`);
          button.innerHTML = '<i class="fas fa-check mr-1"></i>Tracked';
          button.classList.add("screener-watch-success");
        } else {
          notify("error", result.message || "Unable to add to watchlist.");
          button.disabled = false;
          button.classList.add("btn-outline-primary");
          button.classList.remove("btn-secondary");
        }
      } catch (error) {
        console.error("Watchlist add failed:", error);
        notify("error", "Unable to add symbol to watchlist right now.");
        button.disabled = false;
        button.classList.add("btn-outline-primary");
        button.classList.remove("btn-secondary");
      }
    };

    elements.form.addEventListener("submit", (event) => event.preventDefault());

    elements.search.addEventListener("input", (event) => {
      state.search = event.target.value.trim();
      applyFilters();
      updateFilterTags();
    });

    elements.search.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        const symbol = event.target.value.trim();
        if (symbol) {
          addNewSymbol(symbol);
        }
      }
    });

    elements.sector.addEventListener("change", (event) => {
      state.sector = event.target.value;
      applyFilters();
      updateFilterTags();
    });

    elements.marketCap.addEventListener("change", (event) => {
      state.marketCap = event.target.value;
      applyFilters();
      updateFilterTags();
    });

    elements.risk.addEventListener("change", (event) => {
      state.risk = event.target.value;
      applyFilters();
      updateFilterTags();
    });

    elements.dividend.addEventListener("change", (event) => {
      state.dividend = event.target.value;
      applyFilters();
      updateFilterTags();
    });

    elements.priceMin.addEventListener("change", (event) => {
      const value = Number.parseFloat(event.target.value);
      state.priceMin = Number.isFinite(value) ? value : null;
      applyFilters();
      updateFilterTags();
    });

    elements.priceMax.addEventListener("change", (event) => {
      const value = Number.parseFloat(event.target.value);
      state.priceMax = Number.isFinite(value) ? value : null;
      applyFilters();
      updateFilterTags();
    });

    elements.sort.addEventListener("change", (event) => {
      state.sortBy = event.target.value;
      applyFilters();
    });

    elements.filterTags.addEventListener("click", (event) => {
      const button = event.target.closest("[data-remove-filter]");
      if (!button) return;
      const key = button.getAttribute("data-remove-filter");
      switch (key) {
        case "search":
          state.search = "";
          break;
        case "sector":
          state.sector = "all";
          break;
        case "marketCap":
          state.marketCap = "all";
          break;
        case "risk":
          state.risk = "all";
          break;
        case "dividend":
          state.dividend = "all";
          break;
        case "quickFilter":
          state.quickFilter = null;
          quickFilterButtons.forEach((btn) => btn.classList.remove("active"));
          break;
        case "price":
          state.priceMin = PRICE_RANGE.min;
          state.priceMax = PRICE_RANGE.max;
          break;
        default:
          break;
      }
      updateInputsFromState();
      applyFilters();
      updateFilterTags();
    });

    elements.reset.addEventListener("click", () => resetFilters());
    elements.emptyReset.addEventListener("click", () => resetFilters());

    quickFilterButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const tag = button.getAttribute("data-filter-tag");
        const isActive = button.classList.contains("active");
        quickFilterButtons.forEach((btn) => btn.classList.remove("active"));
        if (isActive) {
          state.quickFilter = null;
        } else {
          button.classList.add("active");
          state.quickFilter = tag;
        }
        applyFilters();
        updateFilterTags();
      });
    });

    elements.tableBody.addEventListener("click", (event) => {
      const button = event.target.closest("[data-screener-watch]");
      if (!button) return;
      const symbol = button.getAttribute("data-screener-watch");
      addToWatchlist(symbol, button);
    });

    const initialSymbols = Array.from(universeMeta.keys());
    loadSymbols(initialSymbols).then(() => {
      if (!dataStore.size) {
        elements.resultSummary.textContent = "Unable to load live data. Try refreshing the page.";
      }
    });
  });
</script>

<?php require "../lib/PAGE-bottom.php"; ?>
<?php
/*
Base Score
Every stock starts with 50 points (out of 100)

Then points are added/subtracted based on:
1. Daily Price Change (changePct)
Add/subtract up to 12 points

Formula: daily change % × 3

Example: +4% daily change = +12 points, -3% daily change = -9 points

2. Weekly Momentum (weekChange)
Add/subtract up to 20 points

Formula: weekly change % × 2

Example: +8% weekly = +16 points, -5% weekly = -10 points

3. P/E Ratio (pe)
Good P/E (10-30): +8 points

Bad P/E (negative or >60): -6 points

Other P/E values: no change

4. Dividend Yield (dividendYield)
High yield (≥2%): +6 points

No dividend: -2 points

Low dividend (0-2%): no change

5. Risk Level
Low risk: +6 points

High risk: -6 points

Medium risk: no change

6. Company Size (marketCapBucket)
Mega/Large cap: +4 points

Micro cap: -4 points

Mid/Small cap: no change

Final Adjustments
Minimum score: 20 (can't go lower)

Maximum score: 95 (can't go higher)

Rounded to whole number

Example Calculation:
A large cap, low-risk stock with:

+2% daily change = +6 points

+5% weekly change = +10 points

P/E of 15 = +8 points

2.5% dividend = +6 points

Low risk = +6 points

Large cap = +4 points

Total = 50 + 6 + 10 + 8 + 6 + 6 + 4 = 90 points
*/
?>