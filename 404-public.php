<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>PaperTrade | Page Not Found</title>

<style>
:root {
  --primary: #00aaff;
  --secondary: #2563eb;
  --text-light: #e2e8f0;
  --text-muted: #94a3b8;
  --bg-dark: #0b1220;
  --glass-bg: rgba(255,255,255,0.08);
  --glass-border: rgba(255,255,255,0.15);
}

/* ===== BASE ===== */
body {
  margin: 0;
  font-family: "Poppins", system-ui, sans-serif;
  color: var(--text-light);
  background: var(--bg-dark);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  position: relative;
}

/* Grid background */
body::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image: linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px),
                    linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px);
  background-size: 80px 80px;
  transition: filter 0.4s ease;
  animation: slowMove 25s linear infinite;
  z-index: 0;
}

/* Blur grid when hovering card */
body.hover-blur::before {
  filter: blur(3px) brightness(0.7);
}

/* Market pulse glow */
body::after {
  content: "";
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(0,170,255,0.15) 0%, transparent 70%);
  border-radius: 50%;
  filter: blur(120px);
  animation: pulseGlow 6s ease-in-out infinite;
  z-index: 1;
}

/* Glass card */
.glass-card {
  position: relative;
  background: var(--glass-bg);
  border: 1px solid var(--glass-border);
  backdrop-filter: blur(25px);
  border-radius: 18px;
  padding: 50px 60px;
  box-shadow: 0 8px 40px rgba(0,0,0,0.4);
  z-index: 3;
  text-align: center;
  opacity: 0;
  transform: translateY(30px) scale(0.95);
  animation: cardEntrance 1s ease forwards;
}
/* === INTERNAL GRID LINES INSIDE THE GLASS CARD === */
.glass-card::before {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: inherit;
  z-index: -1;

  background-image:
    linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px),
    linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);

  background-size: 60px 60px;
  opacity: 0.65;
  pointer-events: none;
}

/* 404 title */
h1 {
  font-size: clamp(3.5rem, 7vw, 6rem);
  margin: 0;
  background: linear-gradient(90deg, var(--primary), var(--secondary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 0 25px rgba(0,170,255,0.6);
  animation: pulse 3s ease-in-out infinite;
  cursor: pointer;
}

/* Text */
p {
  font-size: 1.1rem;
  color: var(--text-muted);
  margin: 1.2rem 0 2rem;
}

/* Button */
a.btn {
  display: inline-block;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  color: white;
  text-decoration: none;
  padding: 0.9rem 2rem;
  border-radius: 10px;
  font-weight: 600;
  transition: all 0.25s ease;
  box-shadow: 0 0 20px rgba(0,170,255,0.4);
}
a.btn:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 0 30px rgba(0,170,255,0.7);
}

/* Footer text */
.subtext {
  font-size: 0.9rem;
  color: var(--text-muted);
  margin-top: 1.2rem;
}
.tagline {
  font-size: 0.85rem;
  color: var(--text-muted);
  margin-top: 0.8rem;
  letter-spacing: 0.3px;
}

/* Canvas layers */
canvas#candles,
canvas#particles {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  z-index: 2;
  pointer-events: none;
}

@keyframes slowMove {
  from { background-position: 0 0, 0 0; }
  to { background-position: 80px 80px, 80px 80px; }
}
@keyframes pulseGlow {
  0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
  50% { opacity: 0.9; transform: translate(-50%, -50%) scale(1.1); }
}
@keyframes pulse {
  0%, 100% { opacity: 1; text-shadow: 0 0 25px rgba(0,170,255,0.6); }
  50% { opacity: 0.9; text-shadow: 0 0 40px rgba(0,170,255,0.9); }
}
@keyframes cardEntrance {
  to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Easter egg message */
.easter-msg {
  position: absolute;
  top: 0%;
  left: 50%;
  transform: translate(-50%, 50%);
  color: var(--text-light);
  background: rgba(0,0,0,0.5);
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 1rem;
  opacity: 0;
  animation: floatUp 2s ease forwards;
  z-index: 10;
}
@keyframes floatUp {
  0% { transform: translate(-50%, 100%); opacity: 0; }
  50% { opacity: 1; }
  100% { transform: translate(-50%, 60%); opacity: 0; }
}
/* === GLASS TICKER BAR (60% opacity, angled box) === */
.glass-ticker {
  position: absolute;
  bottom: 0;
  width: 100%;
  height: 55px;
  display: flex;
  align-items: center;
  overflow: hidden;
  z-index: 10;

  background: rgba(255, 255, 255, 0.08);
  border-top: 1px solid rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(12px);
}

/* angled box wrapper */
.ticker-box {
  display: flex;
  white-space: nowrap;
  animation: tickerSlide 50s linear infinite;
}
/* Pause ticker on hover */
.glass-ticker:hover .ticker-box {
  animation-play-state: paused;
}

/* angled box style */
.ticker-item {
  display: inline-flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.06); /* 60% glass look */
  border: 1px solid rgba(255,255,255,0.2);
  padding: 8px 18px;
  margin-right: 25px;
  font-size: 0.9rem;
  color: #dce9ff;
  border-radius: 6px;
  
  /* angled shape */
  clip-path: polygon(8% 0, 100% 0, 92% 100%, 0 100%);
  backdrop-filter: blur(8px);
}

.up {
  color: #4cffba;
  text-shadow: 0 0 6px rgba(76,255,186,0.5);
}

.down {
  color: #ff6b6b;
  text-shadow: 0 0 6px rgba(255,107,107,0.4);
}

@keyframes tickerSlide {
  0%   { transform: translateX(0); }         /* starts already visible */
  100% { transform: translateX(-50%); }      
}

@media (prefers-reduced-motion: reduce) {
  * { animation: none !important; transition: none !important; }
}
/* Light mode (auto-switch) */
@media (prefers-color-scheme: light) {
  body {
    background: #f5f7fa;
    color: #1e293b;
  }

  .glass-card {
    background: rgba(255,255,255,0.6);
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 6px 30px rgba(0,0,0,0.1);
  }

  .glass-ticker {
    background: rgba(255,255,255,0.7);
    border-top: 1px solid rgba(0,0,0,0.1);
  }

  .ticker-item {
    background: rgba(255,255,255,0.5);
    border-color: rgba(0,0,0,0.15);
    color: #1e293b;
  }

  h1 {
    -webkit-text-fill-color: #2563eb;
    text-shadow: none;
  }
}
/* --- BOMB EXPLOSION ANIMATIONS --- */

@keyframes screenShake {
  0% { transform: translate(2px, 2px); }
  25% { transform: translate(-2px, -3px); }
  50% { transform: translate(-3px, 2px); }
  75% { transform: translate(3px, -2px); }
  100% { transform: translate(0,0); }
}

body.shake {
  animation: screenShake 0.08s infinite;
}

@keyframes glitchFlash {
  0% { opacity: 0; }
  50% { opacity: 0.4; }
  100% { opacity: 0; }
}

/* ==========================================
   ORB EXPLOSION SYSTEM (NEW EFFECT)
   ========================================== */

#orbBomb {
  position: absolute;
  top: -100px;
  left: 50%;
  transform: translateX(-50%);
  width: 45px;
  height: 45px;
  background: radial-gradient(circle, #00d4ff 0%, #007cff 60%, #002244 100%);
  border-radius: 50%;
  box-shadow: 0 0 25px #00aaff, 0 0 45px rgba(0,170,255,0.7);
  opacity: 0;
  z-index: 9999;
}

#explosionFlash {
  position: fixed;
  inset: 0;
  background: radial-gradient(circle, rgba(0,200,255,0.5), transparent 70%);
  opacity: 0;
  pointer-events: none;
  z-index: 9998;
}

.fireParticle {
  position: absolute;
  width: 6px;
  height: 6px;
  background: #00c8ff;
  border-radius: 50%;
  box-shadow: 0 0 12px #00aaff;
  z-index: 9999;
}

@keyframes orbFall {
  0% { top: -100px; opacity: 1; }
  100% { top: 87.4%; opacity: 1; }
}

@keyframes orbFlash {
  0% { opacity: 0; }
  50% { opacity: 1; }
  100% { opacity: 0; }
}
/* =========================
   GRADIENT FOG LAYER
   ========================= */
#fogLayer {
  position: fixed;
  inset: 0;
  pointer-events: none;
  opacity: 0;
  z-index: 9995;

  /* Soft fog gradient */
  background: radial-gradient(
      circle at 50% 90%,
      rgba(0, 200, 255, 0.25),
      rgba(0, 0, 0, 0) 60%
    ),
    linear-gradient(
      to bottom,
      rgba(0, 170, 255, 0.1),
      rgba(0, 170, 255, 0.0)
    );

  transition: opacity 2s ease-out;
}
/* =========================
   SCREEN CRACK RAYS
   ========================= */
.crackRay {
  position: absolute;
  width: 2px;
  height: 0;
  background: linear-gradient(
    to bottom,
    rgba(0, 200, 255, 0.9),
    rgba(0, 200, 255, 0.0)
  );
  box-shadow: 0 0 8px rgba(0,200,255,0.7);
  opacity: 0;
  z-index: 9997;
  transform-origin: top center;
}

</style>

</head>
  <!-- NEW GLASS TICKER HERE -->
  <div class="glass-ticker">
    <div class="ticker-box" id="tickerBox"></div>
  </div>
  <!-- END GLASS TICKER -->

<body>
  <body>

  <!-- ==== ORB EXPLOSION ELEMENTS ==== -->
  <div id="orbBomb"></div>
  <div id="explosionFlash"></div>
  <div id="screenCrack"></div>
  <div id="fogLayer"></div>

  <!-- Animation layers -->
  <canvas id="particles"></canvas>
  <canvas id="candles"></canvas>

  <!-- Glass Card Content -->
  <div class="glass-card">
    <h1 id="easterEgg">404</h1>
    <p>Oops, the page you're looking for doesn't exist or is under construction.</p>
    <a class="btn" href="/trade/index.php">Return to Homepage</a>

    <div class="subtext">
      You'll be redirected automatically in <strong><span id="countdown">10</span> seconds</strong>.<br>
      If you believe this is a mistake, please check the URL or return to your homepage.
    </div>
    <div class="tagline">PaperTrade - Learn. Practice. Trade smarter.</div>
  </div>

  <!-- Auto redirect -->
  <script>

// ======================================
// ORB EXPLOSION SYSTEM (NEW EFFECT)
// ======================================

function startOrbExplosion() {
    const orb = document.getElementById("orbBomb");
    const flash = document.getElementById("explosionFlash");
    const crack = document.getElementById("screenCrack");

    // Drop orb
    orb.style.opacity = 1;
    orb.style.animation = "orbFall 1.5s ease-in forwards";

    // Flash after drop
    setTimeout(() => {
        flash.style.opacity = 1;
        flash.style.animation = "orbFlash 0.4s ease-out forwards";
        document.getElementById("fogLayer").style.opacity = 1;
// Hide orb after explosion
orb.style.transition = "opacity 0.6s ease-out";
orb.style.opacity = 0;

// 🔥 Remove orb from screen completely after fade
    setTimeout(() => {
        orb.remove();
    }, 700);  // wait for fade-out animation to complete
        // Crack screen
        crack.style.opacity = 0.7;

        createCracks(32);  // 32 crack rays spreading outward

        // Screen shake
        document.body.classList.add("shake");

        // Remove shake after 2 sec
        setTimeout(() => {
            document.body.classList.remove("shake");
        }, 2000);

    }, 1300);

    // Fire particles burst
    setTimeout(() => {
        createFireBurst(25);

    }, 1350);
}


// Make fire burst
function createFireBurst(count) {
    for (let i = 0; i < count; i++) {
        const p = document.createElement("div");
        p.classList.add("fireParticle");

        p.style.left = "50%";
        p.style.top = "75%"; // explosion origin

        document.body.appendChild(p);

        const angle = Math.random() * Math.PI * 2;
        const distance = 80 + Math.random() * 120;

        const x = Math.cos(angle) * distance;
        const y = Math.sin(angle) * distance;

        p.animate([
            { transform: "translate(0, 0)", opacity: 1 },
            { transform: `translate(${x}px, ${y}px)`, opacity: 0 }
        ],
        { duration: 1200 + Math.random()*400, easing: "ease-out" });

        setTimeout(() => p.remove(), 2000);
    }
}
// ===============================
// CREATE CRACK RAYS
// ===============================
function createCracks(count = 28) {
    const originX = window.innerWidth / 2;
    const originY = window.innerHeight * 0.82;

    for (let i = 0; i < count; i++) {
        const ray = document.createElement("div");
        ray.classList.add("crackRay");

        ray.style.left = originX + "px";
        ray.style.top = originY + "px";

        document.body.appendChild(ray);

        const angle = Math.random() * 360;
        const distance = 150 + Math.random() * 350;

        ray.style.transform = `rotate(${angle}deg)`;

        ray.animate(
            [
                { height: "0px", opacity: 0 },
                { height: distance + "px", opacity: 1 }
            ],
            { duration: 700 + Math.random() * 400, easing: "ease-out" }
        );

        setTimeout(() => {
            ray.animate(
                [{ opacity: 1 }, { opacity: 0 }],
                { duration: 2000, easing: "ease-out" }
            );
        }, 1200);

        setTimeout(() => ray.remove(), 3200);
    }
}


    const redirectUrl = "/trade/index.php";
    let timeLeft = 10;
    const countdownEl = document.getElementById("countdown");
    countdownEl.textContent = timeLeft;

   const timer = setInterval(() => {
  timeLeft--;

if (timeLeft === 5) {
    startOrbExplosion();
}

  // Redirect at 0
  if (timeLeft <= 0) {
      clearInterval(timer);
      window.location.href = redirectUrl;
  } else {
      countdownEl.textContent = timeLeft;
  }
}, 1000);  // <-- MUST be 1000ms (1 second) not 10,000,000ms

    // ===== Candle Animation =====
    const canvas = document.getElementById("candles");
    const ctx = canvas.getContext("2d");
    let w, h, candles = [];

    function resize() {
      w = canvas.width = window.innerWidth;
      h = canvas.height = window.innerHeight;
    }
    window.addEventListener("resize", resize);
    resize();

    function spawnCandle() {
      if (candles.length < 40) {
        candles.push({
          x: Math.random() * w,
          y: -20,
          height: 30 + Math.random() * 120,
          color: Math.random() > 0.5 ? "rgba(0,255,100,0.35)" : "rgba(255,60,60,0.35)",
          speed: 1 + Math.random() * 1.5
        });
      }
    }

    function drawCandles() {
      ctx.clearRect(0, 0, w, h);
      candles.forEach((c, i) => {
        ctx.beginPath();
        ctx.strokeStyle = c.color;
        ctx.lineWidth = 3;
        ctx.moveTo(c.x, c.y);
        ctx.lineTo(c.x, c.y + c.height);
        ctx.stroke();
        c.y += c.speed;
        if (c.y > h) candles.splice(i, 1);
      });
    }

    function animateCandles() {
      drawCandles();
      if (Math.random() < 0.4) spawnCandle();
      requestAnimationFrame(animateCandles);
    }
    animateCandles();

    // ===== Floating Particles =====
    const pCanvas = document.getElementById("particles");
    const pCtx = pCanvas.getContext("2d");
    let pw, ph, particles = [];

    function pResize() {
      pw = pCanvas.width = window.innerWidth;
      ph = pCanvas.height = window.innerHeight;
    }
    window.addEventListener("resize", pResize);
    pResize();

    for (let i = 0; i < 50; i++) {
      particles.push({
        x: Math.random() * pw,
        y: Math.random() * ph,
        size: Math.random() * 2,
        speedX: (Math.random() - 0.5) * 0.3,
        speedY: (Math.random() - 0.5) * 0.3,
        opacity: Math.random() * 0.4 + 0.1
      });
    }

    function animateParticles() {
      pCtx.clearRect(0, 0, pw, ph);
      particles.forEach(p => {
        pCtx.beginPath();
        pCtx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        pCtx.fillStyle = "rgba(255,255,255," + p.opacity + ")";
        pCtx.fill();
        p.x += p.speedX;
        p.y += p.speedY;
        if (p.x < 0) p.x = pw;
        if (p.x > pw) p.x = 0;
        if (p.y < 0) p.y = ph;
        if (p.y > ph) p.y = 0;
      });
      requestAnimationFrame(animateParticles);
    }
    animateParticles();

    // ===== Blur effect on hover =====
    const card = document.querySelector('.glass-card');
    card.addEventListener('mouseenter', () => document.body.classList.add('hover-blur'));
    card.addEventListener('mouseleave', () => document.body.classList.remove('hover-blur'));

    // ===== Easter Egg (click 404 three times) =====
    
    let clickCount = 0;
    const h1 = document.getElementById('easterEgg');
    h1.addEventListener('click', () => {
      clickCount++;
      if (clickCount === 3) {
        clickCount = 0;
        const msg = document.createElement('div');
        msg.className = 'easter-msg';
        msg.textContent = 'Even lost traders find their way home.';
        document.body.appendChild(msg);
        setTimeout(() => msg.remove(), 2500);
        // Simple confetti burst
        for (let i = 0; i < 20; i++) {
          const c = document.createElement('div');
          c.style.position = 'absolute';
          c.style.width = '6px';
          c.style.height = '6px';
          c.style.background = ['#00aaff','#ff5f5f','#00ff99'][Math.floor(Math.random()*3)];
          c.style.top = '50%';
          c.style.left = '50%';
          c.style.borderRadius = '2px';
          c.style.opacity = 1;
          document.body.appendChild(c);
          const angle = Math.random() * Math.PI * 2;
          const distance = 100 + Math.random() * 150;
          const x = Math.cos(angle) * distance;
          const y = Math.sin(angle) * distance;
          c.animate([
            { transform: 'translate(0,0)', opacity: 1 },
            { transform: `translate(${x}px, ${y}px)`, opacity: 0 }
          ], { duration: 1200 + Math.random()*600, easing: 'ease-out' });
          setTimeout(() => c.remove(), 2000);
        }
      }
    });
    
  </script>
  <script>
async function loadTicker() {
    try {
        const symbols = [
            "NVDA", "MSFT", "AAPL", "GOOGL", "AMZN",
            "META", "TSLA", "JPM"
        ];

        // Backend API
        const apiUrl = `http://74.208.158.229/stock-api/landing?symbols=${symbols.join(',')}`;
        
        const res = await fetch(apiUrl, { cache: "no-store" });
        const data = await res.json();

        const box = document.getElementById("tickerBox");
        box.innerHTML = "";

        function makeTicker(stock) {
            const div = document.createElement("div");
            div.className = "ticker-item";

            //const pct = stock.changePercent;
            const pct = stock.change_pct;

            const direction = pct >= 0 ? "up" : "down";

            div.innerHTML = `
                ${stock.symbol}
                $${Number(stock.price).toFixed(2)}
                <span class="${direction}">
                    ${pct >= 0 ? "+" : ""}${pct.toFixed(2)}%
                </span>
            `;
            return div;
        }

        // loop once
        data.forEach(stock => box.appendChild(makeTicker(stock)));

        // loop twice (for infinite scroll)
        data.forEach(stock => box.appendChild(makeTicker(stock)));

    } catch (err) {
        console.error("Ticker load error:", err);
    }
}

loadTicker();
// frontend calling backend every 60 seconds
setInterval(loadTicker, 60000); // refresh every 1 minute
</script>

</body>
</html>

