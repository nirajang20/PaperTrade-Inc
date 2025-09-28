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

// Build ticker content from live quotes


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

  // MODIFIED: Generate points with a stronger upward trend
  const points = new Array(60).fill(0).map((_, i) => {
    const x = pad + (i / 59) * w;
    // Reduced noise for a smoother, clearer trend
    const noise = Math.sin(i * 0.4) * 4 + Math.cos(i * 0.2) * 3;
    // Increased the trend multiplier for a steeper upward slope
    const trend = i * 1.6;
    // Start the chart lower (h * 0.2) to give it more room to grow
    const y = pad + h - clamp(h * 0.2 + trend + noise, 0, h);
    return { x, y };
  });

  let t = 0;
  const dur = options.duration || 1800;
  const color = options.color || '#00f6ff';

  let pulseSize = 0;
  let pulseSpeed = 0.2;

  function draw(progress) {
    ctx.clearRect(0, 0, width, height);

    // grid
    ctx.globalAlpha = 0.25;
    ctx.strokeStyle = 'rgba(126, 48, 225, 0.2)';
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
    ctx.shadowColor = color;
    ctx.shadowBlur = 10;
    
    let maxIndex = Math.floor(points.length * progress);
    maxIndex = clamp(maxIndex, 2, points.length);
    ctx.moveTo(points[0].x, points[0].y);
    for (let i = 1; i < maxIndex; i++) {
      const p = points[i];
      ctx.lineTo(p.x, p.y);
    }
    ctx.stroke();
    ctx.shadowBlur = 0; // reset shadow

    // fill under
    ctx.lineTo(points[maxIndex - 1].x, pad + h);
    ctx.lineTo(points[0].x, pad + h);
    ctx.closePath();
    const grd = ctx.createLinearGradient(0, pad, 0, pad + h);
    grd.addColorStop(0, 'rgba(0, 246, 255, 0.25)');
    grd.addColorStop(1, 'rgba(0, 246, 255, 0.01)');
    ctx.fillStyle = grd;
    ctx.fill();

    // last point pulse
    const last = points[maxIndex - 1];
    pulseSize += pulseSpeed;
    if (pulseSize > 8 || pulseSize < 2) pulseSpeed *= -1;

    ctx.beginPath();
    ctx.arc(last.x, last.y, pulseSize, 0, Math.PI * 2);
    ctx.fillStyle = color;
    ctx.shadowColor = color;
    ctx.shadowBlur = 15;
    ctx.fill();
    ctx.shadowBlur = 0;
  }

  function tick(now) {
    if (!t) t = now;
    const progress = clamp((now - t) / dur, 0, 1);
    draw(progress);
    if (progress < 1) requestAnimationFrame(tick);
    else requestAnimationFrame(liveTick); // Start live updates after initial draw
  }
  
  function liveTick() {
      draw(1);
      requestAnimationFrame(liveTick);
  }

  requestAnimationFrame(tick);

  // MODIFIED: Live updates also trend upwards
  setInterval(() => {
    // shift left
    points.shift();
    const i = points.length;
    const lastX = pad + w;
    const prevY = points[points.length - 1].y;
    // Add small random volatility
    const noise = (Math.random() - 0.5) * 7;
    // Add a consistent upward push (a negative value since Y is calculated from the top)
    const upwardPush = 2.5 + Math.random() * 2;
    // Calculate the next point, ensuring it trends upwards
    const nextY = clamp(prevY - upwardPush + noise, pad + 6, pad + h - 6);
    points.push({ x: lastX, y: nextY });
    // move all points left
    for (const p of points) p.x -= w / 59;
  }, 1400);
}

animateChart(document.getElementById('hero-chart'));
animateChart(document.getElementById('learn-chart'), { duration: 1600, color: '#7e30e1' });

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
  e.preventDefault();
  const email = document.getElementById('email');
  const val = email.value.trim();
  if (!val) return;
  const btn = e.target.querySelector('button');
  btn.disabled = true;
  btn.textContent = 'Initializing…';
  setTimeout(() => {
    btn.textContent = 'Welcome Aboard!';
    btn.style.background = 'linear-gradient(135deg, #00f6ff, #00f6ff)';
    setTimeout(() => {
      btn.disabled = false;
      btn.textContent = 'Create Free Account';
      btn.style.background = '';
      email.value = '';
    }, 1800);
  }, 900);
});
