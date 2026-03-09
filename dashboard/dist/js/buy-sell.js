document.addEventListener('DOMContentLoaded', () => {
  const apiBase = "https://api.paper-trade.org";
  const availableList = document.getElementById("available-stocks");

  if (!availableList) return;

  const symbols = ['NVDA','MSFT','AAPL','GOOGL','AMZN','META','TSLA','JPM','^GSPC','BTC-USD','ETH-USD'];

  // --- Elements for Selected Stock ---
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
    pe: document.getElementById("selected-pe"),
  };

  // --- Utility ---
  const fmtNum = n => {
    if (!n && n !== 0) return "—";
    if (n > 1e12) return (n / 1e12).toFixed(2) + "T";
    if (n > 1e9) return (n / 1e9).toFixed(2) + "B";
    if (n > 1e6) return (n / 1e6).toFixed(1) + "M";
    return n.toLocaleString();
  };

  // --- Populate Available Stocks dynamically ---
  async function loadAvailableStocks() {
    try {
      const res = await fetch(`${apiBase}/buy-sell-info?symbols=${symbols.join(',')}`);
      const data = await res.json();
      availableList.innerHTML = "";

      data.forEach(stock => {
        if (stock.error) return;

        const isUp = stock.change_pct >= 0;
        const changeClass = isUp ? 'text-success' : 'text-danger';
        const changeText = `${isUp ? '+' : ''}${stock.change_pct.toFixed(2)}%`;
        const tags = stock.tags.join(', ');

        const a = document.createElement('a');
        a.href = 'javascript:void(0)';
        a.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
        a.dataset.symbol = stock.symbol;
        a.innerHTML = `
          <div>
            <h5 class="mb-0">${stock.symbol} <small class="text-muted">${stock.company_name}</small></h5>
            <small class="text-muted">${stock.exchange} · ${tags}</small>
          </div>
          <div class="text-right">
            <span class="d-block font-weight-bold">$${stock.current_price.toFixed(2)}</span>
            <span class="${changeClass}">${changeText}</span>
          </div>
        `;
        availableList.appendChild(a);
      });
    } catch (err) {
      console.error("Failed to load stock list:", err);
      availableList.innerHTML = "<div class='p-3 text-danger'>API error loading stock list.</div>";
    }
  }

  // --- Load Detailed Stock Card ---
  async function loadStock(symbol) {
    try {
      const res = await fetch(`${apiBase}/buy-sell?symbol=${encodeURIComponent(symbol)}`);
      const data = await res.json();
      if (!data.selected_stock) throw new Error("Invalid API response");

      const s = data.selected_stock;
      const m = s.metrics;

      els.symbol.textContent = s.ticker;
      els.company.textContent = `${s.company_name} · ${s.exchange}`;
      els.price.textContent = `$${s.last_price.toFixed(2)}`;

      const isUp = s.price_change.amount >= 0;
      els.change.className = isUp ? 'text-success' : 'text-danger';
      els.change.textContent = `${isUp ? '+' : ''}${s.price_change.amount.toFixed(2)} (${s.price_change.percentage})`;

      els.open.textContent = `$${m.open.toFixed(2)}`;
      els.prevClose.textContent = `$${m.previous_close.toFixed(2)}`;
      els.dayRange.textContent = `$${m.day_range.low.toFixed(2)} - $${m.day_range.high.toFixed(2)}`;
      els.range52.textContent = `$${m["52w_range"].low.toFixed(2)} - $${m["52w_range"].high.toFixed(2)}`;
      els.volume.textContent = fmtNum(m.volume);
      els.avgVolume.textContent = fmtNum(m.avg_volume);
      els.marketCap.textContent = m.market_cap;
      els.pe.textContent = m.pe_ratio;

      // Highlight active stock
      document.querySelectorAll("#available-stocks a").forEach(a => a.classList.remove("active"));
      const active = document.querySelector(`#available-stocks a[data-symbol='${symbol}']`);
      if (active) active.classList.add("active");

    } catch (err) {
      console.error("Error loading stock:", err);
      alert("Failed to load stock info.");
    }
  }

  // --- Event listener for clicking available stocks ---
  availableList.addEventListener("click", (e) => {
    const a = e.target.closest("a[data-symbol]");
    if (!a) return;
    const symbol = a.dataset.symbol;
    loadStock(symbol);
  });

  // --- Initial load ---
  loadAvailableStocks().then(() => loadStock('AAPL')); // auto-load AAPL at start
});
