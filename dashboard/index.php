<?php
require "lib/Core.php";
require PATH_LIB . "PAGE-top.php"; ?>

<section class="content">
  <div class="container-fluid">

    <!-- Row: Key Metrics -->
    <div class="row">
      <!-- Total Portfolio -->
      <div class="col-md-4 col-sm-6 mb-3">
        <div class="small-box bg-primary">
          <div class="inner">
            <h3 id="portfolio-value">$100,500</h3>
            <p>Total Portfolio Value</p>
          </div>
          <div class="icon"><i class="fas fa-chart-line"></i></div>
        </div>
      </div>

      <!-- Available Cash -->
      <div class="col-md-4 col-sm-6 mb-3">
        <div class="small-box bg-success">
          <div class="inner">
            <h3 id="available-cash">$25,100</h3>
            <p>Available Cash</p>
          </div>
          <div class="icon"><i class="fas fa-wallet"></i></div>
        </div>
      </div>

      <!-- Today’s P/L -->
      <div class="col-md-4 col-sm-12 mb-3">
        <div class="small-box bg-warning">
          <div class="inner">
            <h3 id="todays-pl" class="text-dark">+$1,040</h3>
            <p>Today's P&amp;L</p>
          </div>
          <div class="icon"><i class="fas fa-percentage"></i></div>
        </div>
      </div>
    </div>

    <!-- Row: Portfolio & Watchlist -->
    <div class="row">
      <!-- Left Column -->
      <div class="col-lg-8">

        <!-- Buying Power -->
        <div class="card card-dark mb-3">
          <div class="card-header">
            <h3 class="card-title">Buying Power</h3>
          </div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-8">Margin Buying Power</dt>
              <dd class="col-4 text-right" id="margin-power">$180,000</dd>
              <dt class="col-8">Options Buying Power</dt>
              <dd class="col-4 text-right" id="options-power">$60,000</dd>
              <dt class="col-8">Pending Orders Hold</dt>
              <dd class="col-4 text-right" id="orders-hold">$2,750</dd>
            </dl>
          </div>
        </div>

        <!-- Portfolio Chart -->
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-area mr-1"></i> Live Portfolio</h3>
          </div>
          <div class="card-body">
            <canvas id="equityChart" height="260"></canvas>
          </div>
        </div>

        <!-- Open Positions -->
        <div class="card">
          <div class="card-header border-transparent">
            <h3 class="card-title">Open Positions</h3>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table m-0">
                <thead class="thead-light">
                  <tr>
                    <th>Symbol</th>
                    <th>Qty</th>
                    <th>Avg. Price</th>
                    <th>Last Price</th>
                    <th>P&amp;L</th>
                  </tr>
                </thead>
                <tbody id="positions-body">
                  <tr>
                    <td>AAPL</td>
                    <td>120</td>
                    <td>$147.35</td>
                    <td>$151.20</td>
                    <td class="text-success">+$462.00</td>
                  </tr>
                  <tr>
                    <td>TSLA</td>
                    <td>45</td>
                    <td>$221.80</td>
                    <td>$219.15</td>
                    <td class="text-danger">-$119.25</td>
                  </tr>
                  <tr>
                    <td>MSFT</td>
                    <td>80</td>
                    <td>$308.42</td>
                    <td>$313.11</td>
                    <td class="text-success">+$375.20</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column -->
      <div class="col-lg-4">
        <div class="card card-outline card-success">
          <div class="card-header">
            <h3 class="card-title">Watchlist</h3>
          </div>
          <div class="card-body p-0">
            <ul class="products-list product-list-in-card pl-2 pr-2" id="watchlist">
              <li class="item">
                <div class="product-info">
                  <a href="#" class="product-title">BTC/USD
                    <span class="badge badge-success float-right">+2.6%</span></a>
                  <span class="product-description">$30,250.45</span>
                </div>
              </li>
              <li class="item">
                <div class="product-info">
                  <a href="#" class="product-title">ETH/USD
                    <span class="badge badge-danger float-right">-1.3%</span></a>
                  <span class="product-description">$1,862.90</span>
                </div>
              </li>
              <li class="item">
                <div class="product-info">
                  <a href="#" class="product-title">GOLD
                    <span class="badge badge-success float-right">+0.9%</span></a>
                  <span class="product-description">$1,948.10</span>
                </div>
              </li>
            </ul>
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
  const ctx = document.getElementById("equityChart").getContext("2d");

  const labels = [];
  const dataPoints = [];
  for (let i = 19; i >= 0; i--) {
    const t = new Date(Date.now() - i * 60000);
    labels.push(t.toLocaleTimeString([], {hour: "2-digit", minute: "2-digit"}));
    dataPoints.push(99000 + Math.random() * 1500);
  }

  new Chart(ctx, {
    type: "line",
    data: {
      labels,
      datasets: [{
        label: "Equity",
        data: dataPoints,
        borderColor: "#28a745",
        backgroundColor: "rgba(40,167,69,0.2)",
        fill: true,
        tension: 0.3,
        borderWidth: 2,
        pointRadius: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: { grid: { display: false }, ticks: { color: "#6c757d" } },
        y: { ticks: { color: "#6c757d" } }
      },
      plugins: { legend: { display: false } }
    }
  });
});
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>
