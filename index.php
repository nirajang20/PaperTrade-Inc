<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>PaperTrade — Practice Trading with Virtual Money</title>
    <meta name="description" content="Practice trading stocks with real-time simulated data and virtual money. Learn, experiment, and build confidence risk‑free." />
    <link rel="stylesheet" href="styles.css" />
    <link rel="icon" href="favicon.png" />
  </head>
  <body>
    <header class="site-header">
      <div class="container nav">
        <a class="brand" href="index.php" aria-label="PaperTrade home">
          <!-- LOGO ADDED HERE -->
          <img src="logo-horizontal.png" alt="PaperTrade Logo" class="logo-img" />
        </a>
        <button class="menu-toggle" aria-controls="primary-nav" aria-expanded="false">☰</button>
        <nav id="primary-nav" class="nav-links">
          <a href="#features">Features</a>
          <a href="#how">How it Works</a>
          <a href="#learn">Learn</a>
          <a href="dashboard/index.php" class="btn btn-ghost">Log In</a>
          <a href="#cta" class="btn btn-primary">Start Trading</a>
        </nav>
      </div>
    </header>

    <main id="home">
      <!-- Hero -->
      <section class="hero container reveal" aria-labelledby="hero-title">
        <div class="hero-content">
          <h1 id="hero-title">Trade smarter. Risk‑free. Fun.</h1>
          <p class="subhead">Practice buying and selling stocks with live‑like market movement — all with virtual money. Learn by doing, build confidence, and level up your strategy.</p>
          <div class="hero-ctas">
            <a href="#cta" class="btn btn-primary btn-lg">Start Trading Now</a>
            <a href="#how" class="btn btn-ghost btn-lg">Practice with Virtual Money</a>
          </div>
        </div>
        <div class="hero-visual" aria-hidden="true">
          <div class="device">
            <canvas id="hero-chart" width="360" height="220" aria-hidden="true"></canvas>
            <div class="device-glow"></div>
          </div>
        </div>
      </section>

      <!-- Ticker -->
      <section class="ticker" aria-label="Market ticker">
        <div class="ticker-track" id="ticker-track"></div>
      </section>

      <!-- Features -->
      <section id="features" class="container section reveal" aria-labelledby="features-title">
        <h2 id="features-title">Everything you need to learn by doing</h2>
        <p class="section-sub">PaperTrade combines a playful interface with powerful simulated data so you can experiment safely.</p>
        <div class="grid features-grid">
          <article class="card">
            <div class="icon" aria-hidden="true">📈</div>
            <h3>Live‑Like Prices</h3>
            <p>Watch prices update continuously with realistic market motion so your practice feels authentic.</p>
          </article>
          <article class="card">
            <div class="icon" aria-hidden="true">🎯</div>
            <h3>Strategy Sandbox</h3>
            <p>Test entries, stops, and scaling without risking a cent. Iterate quickly and learn faster.</p>
          </article>
          <article class="card">
            <div class="icon" aria-hidden="true">🧭</div>
            <h3>Guided Journeys</h3>
            <p>Follow bite‑sized missions that teach core concepts from order types to portfolio balance.</p>
          </article>
          <article class="card">
            <div class="icon" aria-hidden="true">🏆</div>
            <h3>Progress & Badges</h3>
            <p>Track your returns, streaks, and milestones. Share wins, compare with friends, stay motivated.</p>
          </article>
        </div>
      </section>

      <!-- How it Works -->
      <section id="how" class="section muted reveal" aria-labelledby="how-title">
        <div class="container">
          <h2 id="how-title">How it works</h2>
          <div class="steps">
            <div class="step">
              <div class="step-num">1</div>
              <h3>Create your account</h3>
              <p>Sign up in seconds and get instant access on any device.</p>
            </div>
            <div class="step">
              <div class="step-num">2</div>
              <h3>Get virtual cash</h3>
              <p>Start with $100,000 in paper money to explore and learn.</p>
            </div>
            <div class="step">
              <div class="step-num">3</div>
              <h3>Trade in real‑time</h3>
              <p>Place market or limit orders and track performance over time.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Learn Section with Animated Chart -->
      <section id="learn" class="container section reveal" aria-labelledby="learn-title">
        <div class="learn-wrap">
          <div>
            <h2 id="learn-title">See your decisions play out</h2>
            <p class="section-sub">Our animated charts bring your trades to life. Watch trends form, adjust your plan, and understand the why behind every move.</p>
            <ul class="bullets">
              <li><strong>Readable charts:</strong> Clean visuals with helpful context.</li>
              <li><strong>Simulated fills:</strong> Instant feedback on orders.</li>
              <li><strong>Performance view:</strong> Equity, P&L, drawdown and more.</li>
            </ul>
            <div class="inline-ctas">
              <a href="#features" class="btn btn-ghost">Explore Features</a>
            </div>
          </div>
          <div class="learn-chart">
            <canvas id="learn-chart" width="520" height="280" aria-hidden="true"></canvas>
          </div>
        </div>
      </section>

      <!-- Call to Action / Sign Up -->
      <section id="cta" class="container section reveal">
        <div class="cta-card">
          <h2>Ready to practice with virtual money?</h2>
          <p>Start trading in minutes — no credit card required.</p>
            <form id="signup" class="signup" action="dashboard/login.php" method="get" onsubmit="event.preventDefault(); window.location.href='dashboard/login.php?email=' + encodeURIComponent(document.getElementById('email').value);">
            <label class="sr-only" for="email">Email</label>
            <input id="email" type="email" placeholder="Enter your email" autocomplete="email" required />
            <button class="btn btn-primary" type="submit">Create Free Account</button>
            </form>
          <small class="disclaimer">This is a learning tool. No real currency is used. Prices are simulated and for educational purposes only.</small>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <div class="container footer-grid">
        <div>
          <a class="brand" href="index.php">
            <!-- LOGO ADDED HERE -->
            <img src="logo.png" alt="PaperTrade Logo" class="logo-img-foot" />
          </a>
          <p class="foot-note">Practice stock trading with virtual money. Learn by doing in a safe, playful environment.</p>
        </div>
        <div>
          <h4>Company</h4>
          <a href="index.php#features">Features</a>
          <a href="index.php#how">How it Works</a>
          <a href="index.php#learn">Learn</a>
        </div>
        <div>
          <h4>Resources</h4>
          <a href="#">Guides</a>
          <a href="#">Tutorials</a>
          <a href="#">FAQ</a>
        </div>
        <div>
          <h4>Legal</h4>
          <!-- LINKS UPDATED -->
          <a href="terms.php">Terms</a>
          <a href="privacy.php">Privacy</a>
        </div>
      </div>
      <div class="container tiny">© <span id="year"></span> PaperTrade — For learning purposes only.</div>
    </footer>

    <script src="script.js"></script>
    <script src="script/dashboard-stocks.js"></script>
  </body>
</html>