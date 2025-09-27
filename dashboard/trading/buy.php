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
        <p class="text-muted">Browse available symbols, review key stats, and stage a buy ticket in just a few clicks.</p>
      </div>
    </div>

    <div class="row">

      <!-- Stock Selector Sidebar -->
      <div class="col-lg-4">
        <div class="card card-outline card-primary mb-3">
          <div class="card-header">
            <h3 class="card-title mb-0">Find a Stock</h3>
          </div>
          <div class="card-body">
            <div class="input-group mb-3">
              <input type="text" class="form-control" placeholder="Search by symbol or company name...">
              <div class="input-group-append">
                <button class="btn btn-primary" type="button"><i class="fas fa-search"></i></button>
              </div>
            </div>
            <div class="d-flex flex-wrap gap-1">
              <span class="badge badge-secondary mr-1 mb-1">Top Movers</span>
              <span class="badge badge-secondary mr-1 mb-1">Tech</span>
              <span class="badge badge-secondary mr-1 mb-1">Finance</span>
              <span class="badge badge-secondary mr-1 mb-1">Energy</span>
              <span class="badge badge-secondary mr-1 mb-1">Healthcare</span>
            </div>
          </div>
        </div>

        <div class="card card-dark mb-3">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Available Stocks</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" title="Refresh"><i class="fas fa-sync-alt"></i></button>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush" id="available-stocks">
              <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="mb-0">AAPL <small class="text-muted">Apple Inc.</small></h5>
                  <small class="text-muted">NASDAQ · Technology</small>
                </div>
                <div class="text-right">
                  <span class="d-block font-weight-bold">$191.45</span>
                  <span class="text-success">+1.23%</span>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="mb-0">NVDA <small class="text-muted">NVIDIA Corp.</small></h5>
                  <small class="text-muted">NASDAQ · Technology</small>
                </div>
                <div class="text-right">
                  <span class="d-block font-weight-bold">$789.16</span>
                  <span class="text-success">+2.87%</span>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="mb-0">MSFT <small class="text-muted">Microsoft Corp.</small></h5>
                  <small class="text-muted">NASDAQ · Technology</small>
                </div>
                <div class="text-right">
                  <span class="d-block font-weight-bold">$412.59</span>
                  <span class="text-success">+0.54%</span>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="mb-0">TSLA <small class="text-muted">Tesla Inc.</small></h5>
                  <small class="text-muted">NASDAQ · Consumer</small>
                </div>
                <div class="text-right">
                  <span class="d-block font-weight-bold">$231.40</span>
                  <span class="text-danger">-0.78%</span>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="mb-0">AMZN <small class="text-muted">Amazon.com Inc.</small></h5>
                  <small class="text-muted">NASDAQ · Consumer</small>
                </div>
                <div class="text-right">
                  <span class="d-block font-weight-bold">$148.32</span>
                  <span class="text-success">+1.10%</span>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="mb-0">JPM <small class="text-muted">JPMorgan Chase</small></h5>
                  <small class="text-muted">NYSE · Financial</small>
                </div>
                <div class="text-right">
                  <span class="d-block font-weight-bold">$172.08</span>
                  <span class="text-danger">-0.22%</span>
                </div>
              </a>
            </div>
          </div>
        </div>

        <div class="card card-outline card-info">
          <div class="card-header">
            <h3 class="card-title mb-0">Pinned Watchlist</h3>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>BTC/USD</span>
                <span class="badge badge-success">+2.6%</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>SPY</span>
                <span class="badge badge-warning text-dark">+0.4%</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>VIX</span>
                <span class="badge badge-danger">-1.9%</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Trade Ticket & Details -->
      <div class="col-lg-8">
        <div class="card card-outline card-success mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Selected Stock</h3>
            <div class="text-muted small">Last updated <span id="stock-last-updated">2 mins ago</span></div>
          </div>
          <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between pb-3 border-bottom">
              <div>
                <h2 class="mb-0" id="selected-symbol">AAPL</h2>
                <p class="mb-0 text-muted" id="selected-company">Apple Inc. · NASDAQ</p>
              </div>
              <div class="text-right">
                <h2 class="mb-0" id="selected-price">$191.45</h2>
                <p class="mb-0">
                  <span id="selected-change" class="text-success">+2.32 (1.23%)</span>
                </p>
              </div>
            </div>

            <div class="row pt-3">
              <div class="col-md-3 col-6">
                <p class="text-muted mb-1">Day Range</p>
                <p class="mb-0" id="selected-day-range">$187.40 - $194.20</p>
              </div>
              <div class="col-md-3 col-6">
                <p class="text-muted mb-1">52W Range</p>
                <p class="mb-0" id="selected-52w-range">$129.04 - $199.62</p>
              </div>
              <div class="col-md-3 col-6">
                <p class="text-muted mb-1">Volume</p>
                <p class="mb-0" id="selected-volume">54.2M</p>
              </div>
              <div class="col-md-3 col-6">
                <p class="text-muted mb-1">Market Cap</p>
                <p class="mb-0" id="selected-market-cap">$2.9T</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card card-outline card-success mb-3">
          <div class="card-header">
            <h3 class="card-title mb-0">Place Buy Order</h3>
          </div>
          <div class="card-body">
            <ul class="nav nav-pills mb-3" id="buy-modes" role="tablist">
              <li class="nav-item"><a class="nav-link active" id="shares-tab" data-toggle="tab" href="#buy-by-shares" role="tab">Buy by Shares</a></li>
              <li class="nav-item"><a class="nav-link" id="dollars-tab" data-toggle="tab" href="#buy-by-dollars" role="tab">Buy by Dollars</a></li>
              <li class="nav-item"><a class="nav-link" id="units-tab" data-toggle="tab" href="#buy-by-units" role="tab">Buy by Units</a></li>
            </ul>
            <div class="tab-content" id="buy-mode-content">
              <div class="tab-pane fade show active" id="buy-by-shares" role="tabpanel">
                <form>
                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="shares-quantity">Shares</label>
                      <input type="number" class="form-control" id="shares-quantity" placeholder="e.g. 10">
                    </div>
                    <div class="form-group col-md-4">
                      <label for="shares-order-type">Order Type</label>
                      <select id="shares-order-type" class="form-control">
                        <option>Market</option>
                        <option>Limit</option>
                        <option>Stop</option>
                        <option>Stop Limit</option>
                      </select>
                    </div>
                    <div class="form-group col-md-4">
                      <label for="shares-time-in-force">Time in Force</label>
                      <select id="shares-time-in-force" class="form-control">
                        <option>Day</option>
                        <option>GTC</option>
                        <option>IOC</option>
                        <option>FOK</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="shares-limit-price">Limit / Stop Price (if applicable)</label>
                      <input type="number" step="0.01" class="form-control" id="shares-limit-price" placeholder="Enter price">
                    </div>
                    <div class="form-group col-md-6">
                      <label for="shares-notes">Trader Notes</label>
                      <input type="text" class="form-control" id="shares-notes" placeholder="Add an optional note">
                    </div>
                  </div>
                  <div class="d-flex justify-content-end">
                    <button type="reset" class="btn btn-outline-secondary mr-2"><i class="fas fa-undo"></i> Reset</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Submit Buy Order</button>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade" id="buy-by-dollars" role="tabpanel">
                <form>
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="dollar-amount">Amount (USD)</label>
                      <input type="number" step="0.01" class="form-control" id="dollar-amount" placeholder="Enter amount, e.g. 500">
                    </div>
                    <div class="form-group col-md-6">
                      <label>Estimated Shares</label>
                      <input type="text" class="form-control" id="dollar-estimated-shares" placeholder="Auto-calculated" readonly>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="dollar-order-type">Order Type</label>
                      <select id="dollar-order-type" class="form-control">
                        <option>Market</option>
                        <option>Limit</option>
                      </select>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="dollar-time-in-force">Time in Force</label>
                      <select id="dollar-time-in-force" class="form-control">
                        <option>Day</option>
                        <option>GTC</option>
                      </select>
                    </div>
                  </div>
                  <div class="d-flex justify-content-end">
                    <button type="reset" class="btn btn-outline-secondary mr-2"><i class="fas fa-undo"></i> Reset</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Submit Buy Order</button>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade" id="buy-by-units" role="tabpanel">
                <form>
                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="unit-quantity">Units</label>
                      <input type="number" class="form-control" id="unit-quantity" placeholder="e.g. 1">
                    </div>
                    <div class="form-group col-md-4">
                      <label for="unit-size">Unit Size</label>
                      <select id="unit-size" class="form-control">
                        <option>Standard Lot</option>
                        <option>Mini Lot</option>
                        <option>Micro Lot</option>
                        <option>Custom</option>
                      </select>
                    </div>
                    <div class="form-group col-md-4">
                      <label for="unit-order-type">Order Type</label>
                      <select id="unit-order-type" class="form-control">
                        <option>Market</option>
                        <option>Limit</option>
                        <option>Stop</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="unit-notes">Notes</label>
                    <textarea id="unit-notes" class="form-control" rows="3" placeholder="Describe the strategy or trigger"></textarea>
                  </div>
                  <div class="d-flex justify-content-end">
                    <button type="reset" class="btn btn-outline-secondary mr-2"><i class="fas fa-undo"></i> Reset</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Submit Buy Order</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="card card-outline card-info mb-3">
              <div class="card-header">
                <h3 class="card-title mb-0">Order Summary</h3>
              </div>
              <div class="card-body">
                <dl class="row mb-0" id="order-summary">
                  <dt class="col-6">Selected Mode</dt>
                  <dd class="col-6 text-right">Shares</dd>
                  <dt class="col-6">Quantity</dt>
                  <dd class="col-6 text-right">10</dd>
                  <dt class="col-6">Est. Cost</dt>
                  <dd class="col-6 text-right">$1,914.50</dd>
                  <dt class="col-6">Fees</dt>
                  <dd class="col-6 text-right">$1.00</dd>
                  <dt class="col-6">Total</dt>
                  <dd class="col-6 text-right font-weight-bold">$1,915.50</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card card-outline card-warning mb-3">
              <div class="card-header">
                <h3 class="card-title mb-0">Execution Checklist</h3>
              </div>
              <div class="card-body">
                <ul class="list-unstyled mb-0 small">
                  <li class="mb-2"><i class="fas fa-check-circle text-success mr-2"></i>Buying power available</li>
                  <li class="mb-2"><i class="fas fa-check-circle text-success mr-2"></i>Compliance review passed</li>
                  <li class="mb-2"><i class="fas fa-clock text-warning mr-2"></i>Extended hours disabled</li>
                  <li><i class="fas fa-info-circle text-info mr-2"></i>Confirm order details before submit</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title mb-0">Recent Buy Orders</h3>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Symbol</th>
                    <th>Mode</th>
                    <th>Quantity</th>
                    <th>Avg Price</th>
                    <th>Status</th>
                    <th>Time</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>AAPL</td>
                    <td>Shares</td>
                    <td>25</td>
                    <td>$190.80</td>
                    <td><span class="badge badge-success">Filled</span></td>
                    <td>Today · 09:45</td>
                  </tr>
                  <tr>
                    <td>NVDA</td>
                    <td>Dollars</td>
                    <td>$1,000</td>
                    <td>$782.10</td>
                    <td><span class="badge badge-warning">Partial</span></td>
                    <td>Today · 08:30</td>
                  </tr>
                  <tr>
                    <td>MSFT</td>
                    <td>Units</td>
                    <td>1 Mini Lot</td>
                    <td>$410.22</td>
                    <td><span class="badge badge-secondary">Queued</span></td>
                    <td>Yesterday · 15:12</td>
                  </tr>
                  <tr>
                    <td>TSLA</td>
                    <td>Shares</td>
                    <td>12</td>
                    <td>$228.55</td>
                    <td><span class="badge badge-danger">Rejected</span></td>
                    <td>Yesterday · 10:05</td>
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
