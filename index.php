<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PaperTrade — Practice Trading with Virtual Money</title>
  <meta
    name="description"
    content="Practice trading stocks with virtual money. Learn by doing, test strategies, and build confidence in a realistic trading workspace."
  />
  <link rel="icon" href="img/favicon.png" />
  <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>" />
</head>
<body>

  <header class="site-header">
    <div class="container nav">
      <a class="brand" href="index.php" aria-label="PaperTrade home">
        <img src="img/logo-horizontal.png" alt="PaperTrade Logo" class="logo-img" />
      </a>

      <button class="menu-toggle" type="button" aria-controls="primary-nav" aria-expanded="false" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <nav id="primary-nav" class="nav-links">
        <a href="#hero">Home</a>
        <a href="#how">How It Works</a>
        <a href="#cta">Get Started</a>
        <a href="dashboard/auth/login.php" class="nav-login">Log In</a>
        <a href="#cta" class="btn btn-primary nav-cta">Get Started</a>
      </nav>
    </div>
  </header>

  <main>

    <section id="hero" class="hero">
      <div class="hero-glow hero-glow-a"></div>
      <div class="hero-glow hero-glow-b"></div>

      <div class="container hero-grid">
        <div class="hero-copy">
          <span class="eyebrow">Paper Trading Workspace</span>
          <h1>Practice trading with a real market feel.</h1>
          <p class="hero-text">
            Explore the market, track price movement, and build confidence in a
            trading environment designed for learning — without risking real money.
          </p>

          <div class="hero-actions">
            <a href="#cta" class="btn btn-primary btn-lg">Get Started Free</a>
            <a href="#how" class="btn btn-secondary btn-lg">How It Works</a>
          </div>

          <div class="hero-meta">
            <div class="meta-pill">
              <span class="meta-label">Starting Balance</span>
              <strong>$100,000 virtual cash</strong>
            </div>
            <div class="meta-pill">
              <span class="meta-label">Environment</span>
              <strong>Safe simulated trading</strong>
            </div>
            <div class="meta-pill">
              <span class="meta-label">Experience</span>
              <strong>Dashboard-style practice</strong>
            </div>
          </div>
        </div>

        <div class="hero-visual" aria-label="PaperTrade dashboard snapshot">
          <div class="hero-panel">
            <div class="hero-panel-header">
              <div class="panel-dots">
                <span></span><span></span><span></span>
              </div>
              <div class="panel-title">PaperTrade Dashboard Snapshot</div>
            </div>

            <div class="hero-panel-body">
              <div class="stats-row">
                <div class="stat-card">
                  <span>Portfolio Value</span>
                  <strong>$104,280.42</strong>
                  <em class="up">+1.84%</em>
                </div>

                <div class="stat-card">
                  <span>Buying Power</span>
                  <strong>$82,410.00</strong>
                  <em>Available</em>
                </div>

                <div class="stat-card">
                  <span>Open Positions</span>
                  <strong>8 Holdings</strong>
                  <em class="up">Active</em>
                </div>
              </div>

              <div class="performance-card">
                <div class="performance-head">
                  <div>
                    <span class="mini-label">Performance View</span>
                    <strong>Simulated Portfolio Trend</strong>
                  </div>
                  <span class="performance-badge up">+2.31%</span>
                </div>
                <canvas id="hero-mini-chart" width="520" height="140" aria-hidden="true"></canvas>
              </div>

              <div class="market-board-card">
                <div class="market-board-head">
                  <div>
                    <span class="mini-label">Live Market Board</span>
                    <strong>Tracked Symbols</strong>
                  </div>
                  <span class="market-board-status" id="market-board-status">Loading…</span>
                </div>

                <div id="hero-market-board" class="market-board-grid">
                  <div class="market-board-empty">Loading market data…</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="market-strip" aria-label="Live market ticker">
      <div class="container">
        <div id="ticker-row" class="market-row">
          <div class="ticker-loading">Loading market data…</div>
        </div>
      </div>
    </section>

    <section id="how" class="section">
      <div class="container">
        <div class="section-heading">
          <span class="eyebrow">How It Works</span>
          <h2>Start in three simple steps</h2>
          <p class="section-sub">
            Create your account, receive virtual buying power, and start practicing
            inside a market-like workspace built to help you learn by doing.
          </p>
        </div>

        <div class="steps-grid">
          <article class="step-card">
            <div class="step-number">1</div>
            <h3>Create your account</h3>
            <p>Sign up quickly and access your PaperTrade workspace from any device.</p>
          </article>

          <article class="step-card">
            <div class="step-number">2</div>
            <h3>Receive virtual cash</h3>
            <p>Start with <strong>$100,000</strong> in simulated buying power to explore safely.</p>
          </article>

          <article class="step-card">
            <div class="step-number">3</div>
            <h3>Practice and review</h3>
            <p>Track symbols, test decisions, and learn from your portfolio performance over time.</p>
          </article>
        </div>
      </div>
    </section>

    <section id="cta" class="section cta-section">
      <div class="container">
        <div class="cta-card">
          <span class="eyebrow">Get Started</span>
          <h2>Ready to try PaperTrade?</h2>
          <p>
            Enter your email to continue. Existing users will be sent to login,
            and new users will be sent to registration.
          </p>

          <form id="signup" class="cta-form" novalidate>
            <label class="sr-only" for="cta-email">Email</label>
            <input
              id="cta-email"
              name="email"
              type="email"
              placeholder="Enter your email"
              autocomplete="email"
              required
            />
            <button type="submit" class="btn btn-primary" id="cta-submit">Continue</button>
          </form>

          <div id="cta-feedback" class="cta-feedback" aria-live="polite"></div>

          <small class="disclaimer">
            This is a learning tool. No real currency is used. Prices are for educational and simulated practice purposes.
          </small>
        </div>
      </div>
    </section>

  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div class="footer-brand-block">
        <a class="brand" href="index.php" aria-label="PaperTrade home">
          <img src="img/logo-horizontal.png" alt="PaperTrade Logo" class="logo-img-foot" />
        </a>
        <p class="foot-note">
          Practice stock trading with virtual money in a clean, modern workspace built for learning.
        </p>
      </div>

      <div>
        <h4>Product</h4>
        <a href="#hero">Home</a>
        <a href="#how">How It Works</a>
        <a href="#cta">Get Started</a>
      </div>

      <div>
        <h4>Account</h4>
        <a href="dashboard/auth/login.php">Log In</a>
        <a href="dashboard/auth/registration.php">Register</a>
      </div>

      <div>
        <h4>Legal</h4>
        <a href="terms.php">Terms</a>
        <a href="privacy.php">Privacy</a>
      </div>
    </div>

    <div class="container tiny">
      © <span id="year"></span> PaperTrade — For learning purposes only | All Rights Reserved.
    </div>
  </footer>

  <script src="js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>