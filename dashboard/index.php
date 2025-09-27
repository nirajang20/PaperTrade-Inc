<?php
require "lib/Core.php";
require PATH_LIB . "PAGE-top.php"; ?>

<section class="content">
  <div class="container-fluid">

    <!-- Summary Metrics -->
    <div class="row">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="small-box bg-primary">
          <div class="inner">
            <h3 id="portfolio-value">$100,500</h3>
            <p>Total Portfolio Value</p>
          </div>
          <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="small-box bg-success">
          <div class="inner">
            <h3 id="available-cash">$25,106</h3>
            <p>Available Cash</p>
          </div>
          <div class="icon"><i class="fas fa-wallet"></i></div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="small-box bg-warning">
          <div class="inner">
            <h3 id="profit-loss" class="text-dark">+$4,860</h3>
            <p>Profit &amp; Loss</p>
          </div>
          <div class="icon"><i class="fas fa-percentage"></i></div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="small-box bg-info">
          <div class="inner">
            <h3 id="buying-power">$185,000</h3>
            <p>Buying Power</p>
          </div>
          <div class="icon"><i class="fas fa-bolt"></i></div>
        </div>
      </div>
    </div>

    <!-- Performance & Allocation -->
    <div class="row">
      <div class="col-lg-8">
        <div class="card card-outline card-dark mb-3">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Performance</h3>
            <div class="btn-group btn-group-sm" role="group" aria-label="time range">
              <button type="button" class="btn btn-outline-secondary active">1D</button>
              <button type="button" class="btn btn-outline-secondary">1W</button>
              <button type="button" class="btn btn-outline-secondary">1M</button>
              <button type="button" class="btn btn-outline-secondary d-none d-sm-inline">YTD</button>
              <button type="button" class="btn btn-outline-secondary d-none d-sm-inline">1Yr</button>
              <button type="button" class="btn btn-outline-secondary d-none d-lg-inline">3Yr</button>
              <button type="button" class="btn btn-outline-secondary d-none d-lg-inline">5Yr</button>
            </div>
          </div>
          <div class="card-body">
            <div class="position-relative" style="height: 320px;">
              <canvas id="performanceChart"></canvas>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="card card-outline card-info mb-3">
              <div class="card-header">
                <h3 class="card-title mb-0">Holdings</h3>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="thead-light">
                      <tr>
                        <th>Symbol</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Avg Price</th>
                        <th class="text-right">P&amp;L</th>
                      </tr>
                    </thead>
                    <tbody id="holdings-body">
                      <tr>
                        <td>AAPL</td>
                        <td class="text-right">120</td>
                        <td class="text-right">$147.35</td>
                        <td class="text-right text-success">+$462.00</td>
                      </tr>
                      <tr>
                        <td>TSLA</td>
                        <td class="text-right">45</td>
                        <td class="text-right">$221.80</td>
                        <td class="text-right text-danger">-$119.25</td>
                      </tr>
                      <tr>
                        <td>MSFT</td>
                        <td class="text-right">80</td>
                        <td class="text-right">$308.42</td>
                        <td class="text-right text-success">+$375.20</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card card-outline card-success mb-3">
              <div class="card-header">
                <h3 class="card-title mb-0">Watchlist</h3>
              </div>
              <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="watchlist">
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>BTC/USD</span>
                    <span class="badge badge-success">+2.6%</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>ETH/USD</span>
                    <span class="badge badge-danger">-1.3%</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>NVDA</span>
                    <span class="badge badge-success">+3.1%</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>EUR/USD</span>
                    <span class="badge badge-warning text-dark">+0.4%</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card card-outline card-primary mb-3">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Allocation Snapshot</h3>
          </div>
          <div class="card-body">
            <div class="position-relative mb-3" style="height: 250px;">
              <canvas id="allocationChart"></canvas>
            </div>
            <ul class="list-unstyled mb-0 small">
              <li class="mb-2"><i class="fas fa-circle text-success mr-2"></i>Long Positions</li>
              <li class="mb-2"><i class="fas fa-circle text-danger mr-2"></i>Short Positions</li>
              <li class="mb-2"><i class="fas fa-circle text-info mr-2"></i>Crypto Holdings</li>
              <li class="mb-2"><i class="fas fa-circle text-warning mr-2"></i>Cash Reserves</li>
              <li><i class="fas fa-circle text-secondary mr-2"></i>Other Assets</li>
            </ul>
          </div>
        </div>

        <div class="card card-outline card-danger mb-3">
          <div class="card-header">
            <h3 class="card-title mb-0">Trade Reminders</h3>
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0" id="trade-reminders">
              <li class="mb-3">
                <h5 class="mb-1">Rebalance Tech Exposure</h5>
                <p class="text-muted mb-0">Trim positions if NVDA exceeds 25% portfolio weight.</p>
              </li>
              <li class="mb-3">
                <h5 class="mb-1">Set Stop for BTC</h5>
                <p class="text-muted mb-0">Place trailing stop at $28,900 before market close.</p>
              </li>
              <li>
                <h5 class="mb-1">Review Earnings Plays</h5>
                <p class="text-muted mb-0">AAPL &amp; TSLA report tomorrow aftermarket.</p>
              </li>
            </ul>
          </div>
        </div>

        <div class="card card-outline card-warning">
          <div class="card-header">
            <h3 class="card-title mb-0">News &amp; Insights</h3>
          </div>
          <div class="card-body">
            <div class="media mb-3">
              <div class="media-body">
                <h5 class="mt-0 mb-1">Fed Signals Gradual Rate Cuts</h5>
                <p class="text-muted mb-0">Bond yields slip as markets price in two cuts by year-end.</p>
              </div>
            </div>
            <div class="media mb-3">
              <div class="media-body">
                <h5 class="mt-0 mb-1">Crypto Rally Continues</h5>
                <p class="text-muted mb-0">Bitcoin extends gains amid institutional inflows.</p>
              </div>
            </div>
            <div class="media">
              <div class="media-body">
                <h5 class="mt-0 mb-1">Energy Stocks Under Pressure</h5>
                <p class="text-muted mb-0">Crude oil dips below $70 as supply concerns ease.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const lineCtx = document.getElementById("performanceChart");
  const donutCtx = document.getElementById("allocationChart");

  const timeLabels = ["9:30", "10:30", "11:30", "12:30", "13:30", "14:30", "15:30"];
  const equityPoints = [98500, 99300, 100250, 99800, 100980, 101400, 102360];

  new Chart(lineCtx, {
    type: "line",
    data: {
      labels: timeLabels,
      datasets: [{
        label: "Equity",
        data: equityPoints,
        borderColor: "#ff6384",
        backgroundColor: "rgba(255,99,132,0.12)",
        borderWidth: 2,
        pointBackgroundColor: "#ff6384",
        pointRadius: 3,
        tension: 0.4
      }]
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      scales: {
        x: { grid: { display: false }, ticks: { color: "#adb5bd" } },
        y: { ticks: { color: "#adb5bd" }, grid: { color: "rgba(255,255,255,0.05)" } }
      },
      plugins: {
        legend: { display: false },
        tooltip: { mode: "index", intersect: false }
      }
    }
  });

  new Chart(donutCtx, {
    type: "doughnut",
    data: {
      labels: ["Long", "Short", "Crypto", "Cash", "Other"],
      datasets: [{
        data: [45, 15, 20, 12, 8],
        backgroundColor: ["#28a745", "#dc3545", "#17a2b8", "#ffc107", "#6c757d"],
        borderWidth: 0
      }]
    },
    options: {
      maintainAspectRatio: false,
      cutout: "65%",
      plugins: {
        legend: { display: false }
      }
    }
  });
});
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>
