document.addEventListener("DOMContentLoaded", () => {
  setupYear();
  setupMobileMenu();
  setupSmartCta();
  loadLandingMarketData();
});

const TICKER_SYMBOLS = [
  "NVDA",
  "MSFT",
  "AAPL",
  "GOOGL",
  "AMZN",
  "META",
  "TSLA",
  "JPM",
  "^GSPC",
  "BTC-USD",
  "ETH-USD"
];


function setupYear() {
  const yearEl = document.getElementById("year");
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }
}

function setupMobileMenu() {
  const menuToggle = document.querySelector(".menu-toggle");
  const navLinks = document.getElementById("primary-nav");

  if (!menuToggle || !navLinks) return;

  menuToggle.addEventListener("click", () => {
    const expanded = menuToggle.getAttribute("aria-expanded") === "true";
    menuToggle.setAttribute("aria-expanded", String(!expanded));
    navLinks.classList.toggle("show");
  });

  navLinks.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      navLinks.classList.remove("show");
      menuToggle.setAttribute("aria-expanded", "false");
    });
  });
}

function setupSmartCta() {
  const form = document.getElementById("signup");
  const emailInput = document.getElementById("cta-email");
  const feedback = document.getElementById("cta-feedback");
  const submitBtn = document.getElementById("cta-submit");

  if (!form || !emailInput || !feedback || !submitBtn) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = emailInput.value.trim();
    feedback.textContent = "";

    if (!email) {
      feedback.textContent = "Please enter your email address.";
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = "Checking...";

    try {
      const formData = new FormData();
      formData.append("email", email);

      const response = await fetch("dashboard/auth/ajax/check-email.php", {
        method: "POST",
        body: formData
      });

      const data = await response.json();

      if (data.status === "exists") {
        window.location.href = "dashboard/auth/login.php?email=" + encodeURIComponent(email);
        return;
      }

      if (data.status === "new") {
        window.location.href = "dashboard/auth/registration.php?email=" + encodeURIComponent(email);
        return;
      }

      feedback.textContent = data.message || "Something went wrong. Please try again.";
    } catch (error) {
      feedback.textContent = "A network error occurred. Please try again.";
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = "Continue";
    }
  });
}


// Floating price labels scattered across the right half of the hero
const FLOAT_POSITIONS = [
  { top: "12%", left: "58%" },
  { top: "8%",  left: "76%" },
  { top: "38%", left: "64%" },
  { top: "26%", left: "86%" },
  { top: "58%", left: "74%" },
  { top: "68%", left: "88%" },
];

const FLOAT_SYMBOLS = ["NVDA", "AAPL", "TSLA", "META", "MSFT", "JPM"];

function renderHeroFloatPrices(data) {
  const container = document.getElementById("hero-float-prices");
  if (!container) return;

  const map   = new Map(data.map(item => [item.symbol, item]));
  const chips = FLOAT_SYMBOLS.map(sym => map.get(sym)).filter(Boolean);

  container.innerHTML = chips.map((item, i) => {
    const pos = FLOAT_POSITIONS[i];
    if (!pos) return "";
    return `<div class="hero-price-float" style="top:${pos.top};left:${pos.left};animation-delay:${(i * 0.13).toFixed(2)}s">
      <span class="fpf-sym">${escapeHtml(item.symbol)}</span>
      <span class="fpf-price">${formatPrice(item.price, item.symbol)}</span>
    </div>`;
  }).join("");
}

// ─── Market data loader ─────────────────────────────────────────────────────

async function loadLandingMarketData() {
  const tickerRow = document.getElementById("ticker-row");
  const url = `dashboard/auth/ajax/landing-market.php?symbols=${encodeURIComponent(TICKER_SYMBOLS.join(","))}`;

  try {
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error("Failed to load market data.");
    }

    const data = await response.json();
    if (!Array.isArray(data)) {
      throw new Error("Invalid market data format.");
    }

    const cleaned = data.filter(item =>
      item &&
      typeof item.symbol === "string" &&
      item.error == null &&
      item.price != null &&
      item.change_pct != null
    );

    renderTickerRow(tickerRow, cleaned, TICKER_SYMBOLS);
  } catch (error) {
    if (tickerRow) {
      tickerRow.innerHTML = `<div class="ticker-loading">Unable to load market data right now.</div>`;
    }
  }
}

function renderTickerRow(container, data, orderedSymbols) {
  if (!container) return;

  const ordered = orderSymbols(data, orderedSymbols);

  if (!ordered.length) {
    container.innerHTML = `<div class="ticker-loading">No market data available.</div>`;
    return;
  }

  const buildItem = (item) => {
    const isUp = Number(item.change_pct) >= 0;
    const changeClass = isUp ? "up" : "down";
    const changePrefix = isUp ? "▲" : "▼";

    return `<div class="ticker-item">
        <span class="symbol">${escapeHtml(item.symbol)}</span>
        <span class="dot-sep" aria-hidden="true"></span>
        <span class="price">${formatPrice(item.price, item.symbol)}</span>
        <span class="change ${changeClass}">${changePrefix} ${formatPercent(item.change_pct)}</span>
      </div>`;
  };

  // Duplicate items so the animation loops seamlessly
  const html = ordered.map(buildItem).join("");
  container.innerHTML = html + html;
}


function orderSymbols(data, orderedSymbols) {
  const map = new Map(data.map(item => [item.symbol, item]));
  return orderedSymbols
    .map(symbol => map.get(symbol))
    .filter(Boolean);
}

function formatPrice(price, symbol) {
  const numeric = Number(price);

  if (Number.isNaN(numeric)) return "--";

  if (symbol === "BTC-USD" || symbol === "ETH-USD") {
    return `$${numeric.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    })}`;
  }

  if (symbol === "^GSPC") {
    return numeric.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  return `$${numeric.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })}`;
}

function formatPercent(value) {
  const numeric = Math.abs(Number(value));

  if (Number.isNaN(numeric)) return "--";
  return `${numeric.toFixed(2)}%`;
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}