<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";
?>

<section class="content">
  <div class="container-fluid">

    <div class="row align-items-center mb-3">
      <div class="col-lg-8">
        <h1 class="h3 mb-1"><i class="fas fa-shopping-cart text-success mr-2"></i>Trade Tickets · <span id="page-intent-label" class="text-success">Buy</span></h1>
        <p class="text-muted mb-0">Track the market, review performance, and stage a buy order without leaving this workspace.</p>
      </div>
      <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
        <div class="btn-group btn-group-toggle" data-role="intent-group" data-toggle="buttons">
          <label class="btn btn-sm btn-outline-success active" data-intent="buy">
            <input type="radio" name="ticket-mode" autocomplete="off" value="buy" checked> Buy
          </label>
          <label class="btn btn-sm btn-outline-danger" data-intent="sell">
            <input type="radio" name="ticket-mode" autocomplete="off" value="sell"> Sell
          </label>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-4">
        <div class="card card-outline card-success mb-4">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Selected Stock</h3>
            <span class="badge badge-success">Live</span>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h2 class="mb-0" id="selected-symbol">AAPL</h2>
                <p class="text-muted mb-3" id="selected-company">Apple Inc. · NASDAQ</p>
                <div class="badge badge-light text-muted">Tech · Large Cap</div>
              </div>
              <div class="text-right">
                <small class="text-muted d-block">Last Price</small>
                <h2 class="mb-0" id="selected-price">$191.56</h2>
                <span class="text-success" id="selected-change">+2.48 (1.31%)</span>
              </div>
            </div>
            <div class="row pt-3">
              <div class="col-6">
                <small class="text-muted">Open</small>
                <p class="font-weight-bold mb-3" id="selected-open">$189.20</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Previous Close</small>
                <p class="font-weight-bold mb-3" id="selected-prev-close">$189.08</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Day Range</small>
                <p class="font-weight-bold mb-3" id="selected-day-range">$187.42 - $193.05</p>
              </div>
              <div class="col-6">
                <small class="text-muted">52W Range</small>
                <p class="font-weight-bold mb-3" id="selected-52w-range">$129.04 - $199.62</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Volume</small>
                <p class="font-weight-bold mb-3" id="selected-volume">54.2M</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Avg Volume</small>
                <p class="font-weight-bold mb-3" id="selected-avg-volume">62.8M</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Market Cap</small>
                <p class="font-weight-bold mb-0" id="selected-market-cap">$2.99T</p>
              </div>
              <div class="col-6">
                <small class="text-muted">P/E Ratio</small>
                <p class="font-weight-bold mb-0" id="selected-pe">28.3x</p>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white">
            <small class="text-muted">Updated just now · next earnings Oct 29, 2024</small>
          </div>
        </div>
      </div>
      <div class="col-xl-8">
        <div class="card card-outline card-primary mb-4 h-100">
          <div class="card-header border-0">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
              <div class="d-flex flex-column flex-md-row align-items-md-center">
                <h3 class="card-title mb-0 mr-md-3">Price Action</h3>
                <div class="btn-group btn-group-sm mt-3 mt-md-0" role="group" aria-label="Chart type">
                  <button type="button" class="btn btn-outline-primary active" data-chart-type="line">Line</button>
                  <button type="button" class="btn btn-outline-primary" data-chart-type="candlestick">Candlesticks</button>
                </div>
              </div>
              <div class="btn-group btn-group-sm mt-3 mt-lg-0" role="group" aria-label="Chart range">
                <button type="button" class="btn btn-outline-primary active" data-range="1D">1D</button>
                <button type="button" class="btn btn-outline-primary" data-range="5D">5D</button>
                <button type="button" class="btn btn-outline-primary" data-range="1M">1M</button>
                <button type="button" class="btn btn-outline-primary" data-range="1Y">1Y</button>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="position-relative chart-wrapper" style="height:320px;">
              <canvas id="selectedStockLineChart"></canvas>
              <div id="selectedStockCandleChart" class="candlestick-placeholder d-none">
                <div class="chart-placeholder-candles">
                  <div class="candle candle-up" style="height:55%;"><span class="wick" style="top:-25%; bottom:-20%;"></span></div>
                  <div class="candle candle-down" style="height:35%;"><span class="wick" style="top:-35%; bottom:-10%;"></span></div>
                  <div class="candle candle-up" style="height:68%;"><span class="wick" style="top:-20%; bottom:-25%;"></span></div>
                  <div class="candle candle-up" style="height:72%;"><span class="wick" style="top:-18%; bottom:-15%;"></span></div>
                  <div class="candle candle-down" style="height:48%;"><span class="wick" style="top:-28%; bottom:-18%;"></span></div>
                  <div class="candle candle-up" style="height:76%;"><span class="wick" style="top:-22%; bottom:-30%;"></span></div>
                  <div class="candle candle-up" style="height:66%;"><span class="wick" style="top:-30%; bottom:-25%;"></span></div>
                  <div class="candle candle-down" style="height:42%;"><span class="wick" style="top:-26%; bottom:-12%;"></span></div>
                  <div class="candle candle-up" style="height:78%;"><span class="wick" style="top:-24%; bottom:-35%;"></span></div>
                  <div class="candle candle-up" style="height:84%;"><span class="wick" style="top:-20%; bottom:-28%;"></span></div>
                  <div class="candle candle-up" style="height:88%;"><span class="wick" style="top:-15%; bottom:-32%;"></span></div>
                </div>
                <div class="chart-placeholder-axis text-muted small">
                  <span>09:30</span>
                  <span>11:00</span>
                  <span>12:30</span>
                  <span>14:00</span>
                  <span>15:30</span>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white">
            <div class="row">
              <div class="col-md-4 col-12 mb-2 mb-md-0">
                <small class="text-muted d-block">Volume (1D)</small>
                <span class="font-weight-bold">41.6M</span>
              </div>
              <div class="col-md-4 col-6">
                <small class="text-muted d-block">VWAP</small>
                <span class="font-weight-bold">$190.84</span>
              </div>
              <div class="col-md-4 col-6">
                <small class="text-muted d-block">RSI</small>
                <span class="font-weight-bold">58.2</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-4">
        <div class="card card-outline card-dark mb-4">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Available Stocks</h3>
            <div class="card-tools">
              <button class="btn btn-tool" title="Refresh"><i class="fas fa-sync-alt"></i></button>
            </div>
          </div>
          <div class="card-body pb-0">
            <div class="input-group mb-3">
              <input type="text" class="form-control" placeholder="Search ticker or company">
              <div class="input-group-append">
                <button class="btn btn-dark" type="button"><i class="fas fa-search"></i></button>
              </div>
            </div>
          </div>
          <div class="list-group list-group-flush" id="available-stocks">
            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0">AAPL <small class="text-muted">Apple Inc.</small></h5>
                <small class="text-muted">NASDAQ · Technology</small>
              </div>
              <div class="text-right">
                <span class="d-block font-weight-bold">$191.56</span>
                <span class="text-success">+1.31%</span>
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
          <div class="card-footer bg-white">
            <small class="text-muted">Screeners refreshed 5 minutes ago</small>
          </div>
        </div>
        <div class="card card-outline card-info mb-4">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Pinned Watchlist</h3>
            <span class="badge badge-info">Alerts · 3</span>
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

      <div class="col-xl-8">
        <div class="card card-outline card-success mb-4">
          <div class="card-header border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
              <div>
                <h3 class="card-title mb-0">Order Ticket</h3>
                <small class="text-muted">AAPL · Long Equity · USD · Intent <span id="order-intent-label" class="text-success">Buy</span></small>
              </div>
              <div class="btn-group btn-group-toggle mt-3 mt-md-0" data-role="intent-group" data-toggle="buttons">
                <label class="btn btn-sm btn-outline-success active" data-intent="buy">
                  <input type="radio" name="ticket-direction" autocomplete="off" value="buy" checked> Buy
                </label>
                <label class="btn btn-sm btn-outline-danger" data-intent="sell">
                  <input type="radio" name="ticket-direction" autocomplete="off" value="sell"> Sell
                </label>
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
              <li class="nav-item">
                <a class="nav-link" id="by-units-tab" data-toggle="tab" href="#ticket-by-units" role="tab">Buy by Units</a>
              </li>
            </ul>
            <div class="tab-content" id="ticket-mode-content">
              <div class="tab-pane fade show active" id="ticket-by-shares" role="tabpanel">
                <div class="row">
                  <div class="col-lg-7">
                    <form>
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
                          <select id="shares-order-type" class="form-control">
                            <option>Market</option>
                            <option>Limit</option>
                            <option>Stop</option>
                            <option>Stop Limit</option>
                          </select>
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="shares-time-in-force">Time in Force</label>
                          <select id="shares-time-in-force" class="form-control">
                            <option>Day</option>
                            <option>GTC</option>
                            <option>IOC</option>
                            <option>FOK</option>
                          </select>
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
                        <dd class="col-6 text-right">Shares</dd>
                        <dt class="col-6">Quantity</dt>
                        <dd class="col-6 text-right">10</dd>
                        <dt class="col-6">Est. Cost</dt>
                        <dd class="col-6 text-right">$1,915.60</dd>
                        <dt class="col-6">Fees</dt>
                        <dd class="col-6 text-right">$1.00</dd>
                        <dt class="col-6">Buying Power After</dt>
                        <dd class="col-6 text-right">$58,221.40</dd>
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
                    <form>
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="dollar-amount">Amount (USD)</label>
                          <input type="number" step="0.01" class="form-control" id="dollar-amount" placeholder="e.g. 2,500">
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="dollar-estimated">Estimated Shares</label>
                          <input type="text" class="form-control" id="dollar-estimated" value="13.05" readonly>
                        </div>
                      </div>
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="dollar-order-type">Order Type</label>
                          <select id="dollar-order-type" class="form-control">
                            <option>Market</option>
                            <option>Limit</option>
                          </select>
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="dollar-time-in-force">Time in Force</label>
                          <select id="dollar-time-in-force" class="form-control">
                            <option>Day</option>
                            <option>GTC</option>
                          </select>
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
                        <dd class="col-6 text-right">Amount</dd>
                        <dt class="col-6">Investing</dt>
                        <dd class="col-6 text-right">$2,500.00</dd>
                        <dt class="col-6">Est. Shares</dt>
                        <dd class="col-6 text-right">13.05</dd>
                        <dt class="col-6">Est. Avg Price</dt>
                        <dd class="col-6 text-right">$191.50</dd>
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

              <div class="tab-pane fade" id="ticket-by-units" role="tabpanel">
                <div class="row">
                  <div class="col-lg-7">
                    <form>
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="unit-quantity">Units</label>
                          <input type="number" class="form-control" id="unit-quantity" placeholder="e.g. 1">
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="unit-size">Unit Size</label>
                          <select id="unit-size" class="form-control">
                            <option>Standard Lot</option>
                            <option>Mini Lot</option>
                            <option>Micro Lot</option>
                            <option>Custom</option>
                          </select>
                        </div>
                      </div>
                      <div class="form-row">
                        <div class="form-group col-sm-6">
                          <label for="unit-order-type">Order Type</label>
                          <select id="unit-order-type" class="form-control">
                            <option>Market</option>
                            <option>Limit</option>
                            <option>Stop</option>
                          </select>
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="unit-stop-loss">Stop Loss</label>
                          <input type="number" step="0.01" class="form-control" id="unit-stop-loss" placeholder="$188.00">
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="unit-notes">Strategy Notes</label>
                        <textarea id="unit-notes" class="form-control" rows="2" placeholder="Entry catalyst, risk levels, etc."></textarea>
                      </div>
                    </form>
                  </div>
                  <div class="col-lg-5">
                    <div class="border rounded p-3 bg-light">
                      <h6 class="text-uppercase text-muted mb-3">Order Summary</h6>
                      <dl class="row mb-0 small">
                        <dt class="col-6">Mode</dt>
                        <dd class="col-6 text-right">Units</dd>
                        <dt class="col-6">Units</dt>
                        <dd class="col-6 text-right">1 Standard</dd>
                        <dt class="col-6">Notional</dt>
                        <dd class="col-6 text-right">$19,156.00</dd>
                        <dt class="col-6">Leverage</dt>
                        <dd class="col-6 text-right">5×</dd>
                      </dl>
                    </div>
                    <div class="mt-3">
                      <h6 class="text-uppercase text-muted mb-2 small">Risk Controls</h6>
                      <ul class="list-unstyled small mb-0">
                        <li class="mb-1"><i class="fas fa-bell text-warning mr-2"></i>Price alert at $196.00</li>
                        <li class="mb-1"><i class="fas fa-shield-alt text-success mr-2"></i>Hedging enabled</li>
                        <li><i class="fas fa-balance-scale text-secondary mr-2"></i>Portfolio weight &lt; 8%</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-column flex-md-row">
            <div class="text-muted small mb-3 mb-md-0">
              <span class="mr-3"><i class="fas fa-wallet mr-1"></i>Available cash: $60,137.00</span>
              <span><i class="fas fa-percentage mr-1"></i>Portfolio risk: 4.2%</span>
            </div>
            <div>
              <button type="button" class="btn btn-outline-secondary mr-2" id="clear-trade-btn"><i class="fas fa-undo"></i> Clear Ticket</button>
              <button type="button" class="btn btn-success" id="submit-trade-btn"><i class="fas fa-check"></i> Submit Buy Order</button>
            </div>
          </div>
        </div>

        <div class="card card-outline card-primary">
          <div class="card-header border-0">
            <h3 class="card-title mb-0">Recent Buy Orders</h3>
            <span class="text-muted small">Past 24 hours</span>
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
                    <td>Amount</td>
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

<style>
  .chart-wrapper canvas {
    width: 100% !important;
    height: 100% !important;
  }
  .candlestick-placeholder {
    position: absolute;
    inset: 0;
    padding: 1rem 1.5rem;
    display: flex;
    flex-direction: column;
  }
  .candlestick-placeholder::before {
    content: "";
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(to top, rgba(108, 117, 125, 0.08), rgba(108, 117, 125, 0.08) 1px, transparent 1px, transparent 48px);
    z-index: 0;
    pointer-events: none;
  }
  .chart-placeholder-candles {
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    flex: 1;
    gap: 0.4rem;
    z-index: 1;
  }
  .chart-placeholder-candles .candle {
    flex: 1;
    border-radius: 4px;
    position: relative;
    min-height: 12%;
  }
  .chart-placeholder-candles .candle .wick {
    position: absolute;
    left: 50%;
    width: 2px;
    transform: translateX(-50%);
    background-color: currentColor;
  }
  .chart-placeholder-candles .candle-up {
    background: rgba(40, 167, 69, 0.6);
    color: rgba(40, 167, 69, 0.9);
  }
  .chart-placeholder-candles .candle-down {
    background: rgba(220, 53, 69, 0.6);
    color: rgba(220, 53, 69, 0.9);
  }
  .chart-placeholder-axis {
    display: flex;
    justify-content: space-between;
    margin-top: 0.75rem;
    z-index: 1;
    position: relative;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var lineCanvas = document.getElementById('selectedStockLineChart');
    if (!lineCanvas) { return; }

    var lineCtx = lineCanvas.getContext('2d');
    var gradientHeight = lineCanvas.parentElement ? lineCanvas.parentElement.offsetHeight : 320;
    var lineGradient = lineCtx.createLinearGradient(0, 0, 0, gradientHeight);
    lineGradient.addColorStop(0, 'rgba(40, 167, 69, 0.4)');
    lineGradient.addColorStop(1, 'rgba(40, 167, 69, 0)');

    var selectedLineChart = new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: ['09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30'],
        datasets: [{
          label: 'Price',
          data: [187.8, 188.5, 189.1, 188.6, 189.9, 190.3, 191.4, 192.1, 191.8, 192.4, 192.9, 191.7, 191.5],
          borderColor: '#28a745',
          backgroundColor: lineGradient,
          borderWidth: 2,
          pointRadius: 0,
          tension: 0.3,
          fill: true
        }]
      },
      options: {
        maintainAspectRatio: false,
        legend: { display: false },
        scales: {
          xAxes: [{
            gridLines: { display: false },
            ticks: { fontColor: '#6c757d' }
          }],
          yAxes: [{
            gridLines: { color: 'rgba(108, 117, 125, 0.1)' },
            ticks: { fontColor: '#6c757d', callback: function (value) { return '$' + value.toFixed(2); } }
          }]
        },
        tooltips: {
          backgroundColor: '#1e1e2d',
          cornerRadius: 4,
          callbacks: {
            label: function (tooltipItem) {
              return 'Price: $' + tooltipItem.yLabel.toFixed(2);
            }
          }
        }
      }
    });

    var candlestickPlaceholder = document.getElementById('selectedStockCandleChart');
    var chartTypeButtons = document.querySelectorAll('[data-chart-type]');
    var activeChartType = 'line';

    function setChartType(type) {
      activeChartType = type;
      if (type === 'line') {
        lineCanvas.classList.remove('d-none');
        candlestickPlaceholder.classList.add('d-none');
        selectedLineChart.resize();
      } else {
        lineCanvas.classList.add('d-none');
        candlestickPlaceholder.classList.remove('d-none');
      }
    }

    chartTypeButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var targetType = btn.getAttribute('data-chart-type');
        if (targetType === activeChartType) { return; }
        chartTypeButtons.forEach(function (item) { item.classList.remove('active'); });
        btn.classList.add('active');
        setChartType(targetType);
      });
    });

    setChartType(activeChartType);

    var rangeButtons = document.querySelectorAll('[data-range]');
    rangeButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        rangeButtons.forEach(function (item) { item.classList.remove('active'); });
        btn.classList.add('active');
      });
    });

    var intentGroups = document.querySelectorAll('[data-role="intent-group"]');
    var submitButton = document.getElementById('submit-trade-btn');
    var orderIntentLabel = document.getElementById('order-intent-label');
    var pageIntentLabel = document.getElementById('page-intent-label');
    var intentState = 'buy';

    function applyIntent(intent) {
      intentState = intent;
      intentGroups.forEach(function (group) {
        group.querySelectorAll('label[data-intent]').forEach(function (label) {
          var labelIntent = label.getAttribute('data-intent');
          var isActive = labelIntent === intent;
          label.classList.toggle('active', isActive);
          if (labelIntent === 'buy') {
            label.classList.toggle('btn-success', isActive);
            label.classList.toggle('btn-outline-success', !isActive);
          } else {
            label.classList.toggle('btn-danger', isActive);
            label.classList.toggle('btn-outline-danger', !isActive);
          }
          var input = label.querySelector('input');
          if (input) { input.checked = isActive; }
        });
      });

      if (submitButton) {
        submitButton.textContent = intent === 'buy' ? 'Submit Buy Order' : 'Submit Sell Order';
        submitButton.classList.toggle('btn-success', intent === 'buy');
        submitButton.classList.toggle('btn-danger', intent === 'sell');
      }

      if (orderIntentLabel) {
        orderIntentLabel.textContent = intent === 'buy' ? 'Buy' : 'Sell';
        orderIntentLabel.classList.toggle('text-success', intent === 'buy');
        orderIntentLabel.classList.toggle('text-danger', intent === 'sell');
      }

      if (pageIntentLabel) {
        pageIntentLabel.textContent = intent === 'buy' ? 'Buy' : 'Sell';
        pageIntentLabel.classList.toggle('text-success', intent === 'buy');
        pageIntentLabel.classList.toggle('text-danger', intent === 'sell');
      }
    }

    intentGroups.forEach(function (group) {
      group.addEventListener('click', function (event) {
        var label = event.target.closest('label[data-intent]');
        if (!label) { return; }
        var intent = label.getAttribute('data-intent');
        applyIntent(intent);
      });
    });

    applyIntent(intentState);
  });
</script>

<?php require "../lib/PAGE-bottom.php"; ?>
