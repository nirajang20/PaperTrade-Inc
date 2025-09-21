// Utility: clamp
const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

// Header year
document.getElementById('year').textContent = new Date().getFullYear();

// Mobile nav toggle
const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('.nav-links');
if (toggle && nav) {
  toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open);
  });
}

// Intersection-based reveal animations
const io = new IntersectionObserver((entries) => {
  entries.forEach((e) => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      io.unobserve(e.target);
    }
  });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach((el) => io.observe(el));

// Build ticker content (fake but lively)
const symbols = [
  'AAPL','MSFT','NVDA','AMZN','GOOGL','TSLA','META','AMD','NFLX','SHOP',
  'COIN','PLTR','UBER','DIS','SPOT','SNOW','SQ','ABNB','ADBE','CRM'
];
function makeTick(sym) {
  const base = 20 + Math.random() * 600;
  const price = (base + Math.random() * 5).toFixed(2);
  const delta = (Math.random() * 2 - 1).toFixed(2);
  const pos = Number(delta) >= 0;
  const el = document.createElement('div');
  el.className = 'tick';
  el.innerHTML = `<span class="sym">${sym}</span><span class="price">$${price}</span><span class="delta ${pos ? 'pos' : 'neg'}">${pos ? '▲' : '▼'} ${Math.abs(delta)}%</span>`;
  return el;
}
const track = document.getElementById('ticker-track');
if (track) {
  const fill = () => {
    track.innerHTML = '';
    const row = [...symbols, ...symbols, ...symbols];
    row.forEach((s) => track.appendChild(makeTick(s)));
  };
  fill();
  // Periodically nudge numbers so it feels live
  setInterval(() => {
    track.querySelectorAll('.tick').forEach((el) => {
      const priceEl = el.querySelector('.price');
      const deltaEl = el.querySelector('.delta');
      const cur = parseFloat(priceEl.textContent.replace(/[$,]/g, ''));
      const diff = (Math.random() - 0.5) * 0.6;
      const next = clamp(cur + diff, 1, 2000);
      const pct = ((next - cur) / cur) * 100;
      const pos = pct >= 0;
      priceEl.textContent = `$${next.toFixed(2)}`;
      deltaEl.classList.toggle('pos', pos);
      deltaEl.classList.toggle('neg', !pos);
      deltaEl.textContent = `${pos ? '▲' : '▼'} ${Math.abs(pct).toFixed(2)}%`;
    });
  }, 1800);
}

// Simple animated line charts for hero and learn sections
function animateChart(canvas, options = {}) {
  if (!canvas) return;
  const dpr = Math.max(1, window.devicePixelRatio || 1);
  const ctx = canvas.getContext('2d');
  const width = canvas.width;
  const height = canvas.height;
  canvas.style.width = width + 'px';
  canvas.style.height = height + 'px';
  canvas.width = Math.floor(width * dpr);
  canvas.height = Math.floor(height * dpr);
  ctx.scale(dpr, dpr);

  const pad = 18;
  const w = width - pad * 2;
  const h = height - pad * 2;

  const points = new Array(60).fill(0).map((_, i) => {
    const x = pad + (i / 59) * w;
    const noise = Math.sin(i * 0.35) * 6 + Math.cos(i * 0.18) * 4 + (Math.random() - 0.5) * 2;
    const trend = i * 0.7;
    const y = pad + h - clamp(h * 0.45 + trend + noise, 0, h);
    return { x, y };
  });

  let t = 0;
  const dur = options.duration || 1800;
  const color = options.color || '#FFB703';

  function draw(progress) {
    ctx.clearRect(0, 0, width, height);

    // grid
    ctx.globalAlpha = 0.25;
    ctx.strokeStyle = 'rgba(255,255,255,0.06)';
    ctx.lineWidth = 1;
    for (let i = 0; i < 5; i++) {
      const y = pad + (i / 4) * h;
      ctx.beginPath();
      ctx.moveTo(pad, y);
      ctx.lineTo(pad + w, y);
      ctx.stroke();
    }
    ctx.globalAlpha = 1;

    // line
    ctx.beginPath();
    ctx.lineWidth = 2.5;
    ctx.strokeStyle = color;
    let maxIndex = Math.floor(points.length * progress);
    maxIndex = clamp(maxIndex, 2, points.length);
    ctx.moveTo(points[0].x, points[0].y);
    for (let i = 1; i < maxIndex; i++) {
      const p = points[i];
      ctx.lineTo(p.x, p.y);
    }
    ctx.stroke();

    // fill under
    ctx.lineTo(points[maxIndex - 1].x, pad + h);
    ctx.lineTo(points[0].x, pad + h);
    ctx.closePath();
    const grd = ctx.createLinearGradient(0, pad, 0, pad + h);
    grd.addColorStop(0, 'rgba(255,183,3,0.28)');
    grd.addColorStop(1, 'rgba(255,183,3,0.03)');
    ctx.fillStyle = grd;
    ctx.fill();

    // last point glow
    const last = points[maxIndex - 1];
    ctx.beginPath();
    ctx.arc(last.x, last.y, 3.6, 0, Math.PI * 2);
    ctx.fillStyle = '#FB8500';
    ctx.fill();
  }

  function tick(now) {
    if (!t) t = now;
    const progress = clamp((now - t) / dur, 0, 1);
    draw(progress);
    if (progress < 1) requestAnimationFrame(tick);
  }

  requestAnimationFrame(tick);

  // gentle live updates
  setInterval(() => {
    // shift left
    points.shift();
    const i = points.length;
    const lastX = pad + w;
    const prevY = points[points.length - 1].y;
    const noise = Math.sin(i * 0.35) * 6 + Math.cos(i * 0.18) * 4 + (Math.random() - 0.5) * 4;
    const nextY = clamp(prevY + noise, pad + 6, pad + h - 6);
    points.push({ x: lastX, y: nextY });
    // move all points left
    for (const p of points) p.x -= w / 59;
    draw(1);
  }, 1400);
}

animateChart(document.getElementById('hero-chart'));
animateChart(document.getElementById('learn-chart'), { duration: 1600 });

// Smooth scroll for same-page anchors
document.querySelectorAll('a[href^="#"]').forEach((a) => {
  a.addEventListener('click', (e) => {
    const id = a.getAttribute('href');
    if (!id || id === '#') return;
    const el = document.querySelector(id);
    if (el) {
      e.preventDefault();
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      // close mobile nav
      nav?.classList.remove('open');
      toggle?.setAttribute('aria-expanded', 'false');
    }
  });
});

// Fake signup interaction
document.querySelector('#signup')?.addEventListener('submit', (e) => {
  const email = document.getElementById('email');
  const val = email.value.trim();
  if (!val) return;
  const btn = e.target.querySelector('button');
  btn.disabled = true;
  btn.textContent = 'Creating…';
  setTimeout(() => {
    btn.textContent = 'Welcome aboard!';
    setTimeout(() => {
      btn.disabled = false;
      btn.textContent = 'Create Free Account';
      email.value = '';
    }, 1800);
  }, 900);
});
