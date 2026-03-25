document.addEventListener("DOMContentLoaded", () => {
  setupYear();
  setupMobileMenu();
  setupSmartCta();
  setupHeroMiniChart();
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

const HERO_BOARD_SYMBOLS = [
  "NVDA",
  "MSFT",
  "AAPL",
  "GOOGL",
  "AMZN",
  "META",
  "TSLA",
  "JPM"
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

function setupHeroMiniChart() {
  const canvas = document.getElementById("hero-mini-chart");
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  if (!ctx) return;

  let frameId = null;
  let phase = 0;
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function draw() {
    const width = canvas.width;
    const height = canvas.height;

    ctx.clearRect(0, 0, width, height);

    ctx.strokeStyle = "rgba(255,255,255,0.05)";
    ctx.lineWidth = 1;

    for (let i = 1; i < 5; i++) {
      const y = (height / 5) * i;
      ctx.beginPath();
      ctx.moveTo(0, y);
      ctx.lineTo(width, y);
      ctx.stroke();
    }

    ctx.beginPath();
    for (let x = 0; x <= width; x += 8) {
      const y =
        height * 0.58 +
        Math.sin(x * 0.02 + phase) * 12 +
        Math.sin(x * 0.009 + phase * 1.15) * 6 -
        x * 0.04;

      if (x === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }

    ctx.lineTo(width, height);
    ctx.lineTo(0, height);
    ctx.closePath();

    const fill = ctx.createLinearGradient(0, 0, 0, height);
    fill.addColorStop(0, "rgba(66,207,255,0.18)");
    fill.addColorStop(1, "rgba(66,207,255,0.02)");
    ctx.fillStyle = fill;
    ctx.fill();

    ctx.beginPath();
    for (let x = 0; x <= width; x += 8) {
      const y =
        height * 0.58 +
        Math.sin(x * 0.02 + phase) * 12 +
        Math.sin(x * 0.009 + phase * 1.15) * 6 -
        x * 0.04;

      if (x === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }

    ctx.strokeStyle = "rgba(97,233,186,0.95)";
    ctx.shadowBlur = 12;
    ctx.shadowColor = "rgba(97,233,186,0.35)";
    ctx.lineWidth = 2.5;
    ctx.stroke();
    ctx.shadowBlur = 0;
  }

  function animate() {
    phase += 0.02;
    draw();
    frameId = requestAnimationFrame(animate);
  }

  draw();

  if (!reduceMotion) {
    animate();
  }

  window.addEventListener("beforeunload", () => {
    if (frameId) cancelAnimationFrame(frameId);
  });
}

async function loadLandingMarketData() {
  const tickerRow = document.getElementById("ticker-row");
  const heroBoard = document.getElementById("hero-market-board");
  const boardStatus = document.getElementById("market-board-status");

  const symbolSet = [...new Set([...TICKER_SYMBOLS, ...HERO_BOARD_SYMBOLS])];
  const url = `dashboard/auth/ajax/landing-market.php?symbols=${encodeURIComponent(symbolSet.join(","))}`;

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
    renderHeroMarketBoard(heroBoard, cleaned, HERO_BOARD_SYMBOLS);

    if (boardStatus) {
      boardStatus.textContent = `${cleaned.length} symbols loaded`;
    }
  } catch (error) {
    if (tickerRow) {
      tickerRow.innerHTML = `<div class="ticker-loading">Unable to load market data right now.</div>`;
    }

    if (heroBoard) {
      heroBoard.innerHTML = `<div class="market-board-empty">Unable to load market data right now.</div>`;
    }

    if (boardStatus) {
      boardStatus.textContent = "Unavailable";
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

  container.innerHTML = ordered.map(item => {
    const isUp = Number(item.change_pct) >= 0;
    const changeClass = isUp ? "up" : "down";
    const changePrefix = isUp ? "▲" : "▼";

    return `
      <div class="ticker-item">
        <span class="symbol">${escapeHtml(item.symbol)}</span>
        <span class="price">${formatPrice(item.price, item.symbol)}</span>
        <span class="change ${changeClass}">${changePrefix} ${formatPercent(item.change_pct)}</span>
      </div>
    `;
  }).join("");
}

function renderHeroMarketBoard(container, data, orderedSymbols) {
  if (!container) return;

  const ordered = orderSymbols(data, orderedSymbols);

  if (!ordered.length) {
    container.innerHTML = `<div class="market-board-empty">No market data available.</div>`;
    return;
  }

  container.innerHTML = ordered.map(item => {
    const isUp = Number(item.change_pct) >= 0;
    const changeClass = isUp ? "up" : "down";
    const changePrefix = isUp ? "+" : "";

    return `
      <div class="market-board-item">
        <span class="market-board-symbol">${escapeHtml(item.symbol)}</span>
        <span class="market-board-price">${formatPrice(item.price, item.symbol)}</span>
        <span class="market-board-change ${changeClass}">${changePrefix}${formatPercent(item.change_pct)}</span>
      </div>
    `;
  }).join("");
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