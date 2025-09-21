<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";
?>

<section class="content">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="row mb-3">
      <div class="col-sm-12">
        <h2 class="mb-0"><i class="fas fa-shopping-cart mr-2"></i> Buy Stocks</h2>
        <p class="text-muted">Search for a stock and place a buy order.</p>
      </div>
    </div>

    <!-- Search Box -->
    <div class="row mb-3">
      <div class="col-md-6">
        <div class="input-group">
          <input type="text" class="form-control" placeholder="Search by symbol or company name...">
          <div class="input-group-append">
            <button class="btn btn-primary" type="button"><i class="fas fa-search"></i> Search</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Stock Info + Buy Form -->
    <div class="row">
      <!-- Stock Info -->
      <div class="col-lg-6">
        <div class="card card-info">
          <div class="card-header">
            <h3 class="card-title">Stock Details</h3>
          </div>
          <div class="card-body">
            <h4 id="stock-symbol">AAPL <small class="text-muted">Apple Inc.</small></h4>
            <p class="mb-1">Current Price: <strong id="stock-price">$151.20</strong></p>
            <p class="mb-1">Change: <span id="stock-change" class="text-success">+0.45 (0.30%)</span></p>
            <p class="mb-0">Market Cap: <strong>$2.4T</strong></p>
          </div>
        </div>
      </div>

      <!-- Buy Form -->
      <div class="col-lg-6">
        <div class="card card-success">
          <div class="card-header">
            <h3 class="card-title">Place Buy Order</h3>
          </div>
          <div class="card-body">
            <form>
              <div class="form-group">
                <label for="symbol">Symbol</label>
                <input type="text" id="symbol" class="form-control" placeholder="e.g. AAPL">
              </div>
              <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" class="form-control" placeholder="Enter quantity">
              </div>
              <div class="form-group">
                <label for="orderType">Order Type</label>
                <select id="orderType" class="form-control">
                  <option>Market Order</option>
                  <option>Limit Order</option>
                  <option>Stop Order</option>
                </select>
              </div>
              <div class="form-group">
                <label for="limitPrice">Limit/Stop Price (if applicable)</label>
                <input type="number" step="0.01" id="limitPrice" class="form-control" placeholder="Enter price">
              </div>
              <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Buy Now</button>
              <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="row mt-3">
      <div class="col-12">
        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title">Recent Buy Orders</h3>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Symbol</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Order Type</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>AAPL</td>
                    <td>10</td>
                    <td>$151.00</td>
                    <td>Market</td>
                    <td><span class="badge badge-success">Executed</span></td>
                  </tr>
                  <tr>
                    <td>TSLA</td>
                    <td>5</td>
                    <td>$220.50</td>
                    <td>Limit</td>
                    <td><span class="badge badge-warning">Pending</span></td>
                  </tr>
                  <tr>
                    <td>MSFT</td>
                    <td>8</td>
                    <td>$310.10</td>
                    <td>Stop</td>
                    <td><span class="badge badge-danger">Cancelled</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<?php require "../lib/PAGE-bottom.php"; ?>
