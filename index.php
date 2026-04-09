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
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet" />
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
        <div class="nav-links-group">
          <a href="#hero">Home</a>
          <a href="#how">How It Works</a>
          <a href="#cta">Get Started</a>
        </div>
        <div class="nav-links-auth">
          <a href="dashboard/auth/login.php" class="nav-login">Log In</a>
          <a href="#cta" class="btn btn-primary nav-cta">Get Started</a>
        </div>
      </nav>
    </div>
  </header>

  <main>

    <section id="hero" class="hero">
      <div class="hero-bg-overlay" aria-hidden="true"></div>

      <div class="hero-glow hero-glow-a"></div>
      <div class="hero-glow hero-glow-b"></div>

      <div class="container hero-center">
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

        <div class="hero-stats">
          <div class="hero-stat">
            <span class="hsc-val">$100K</span>
            <span class="hsc-label">Virtual buying power</span>
          </div>
          <span class="stat-div" aria-hidden="true"></span>
          <div class="hero-stat">
            <span class="hsc-val up">Live Data</span>
            <span class="hsc-label">Real market prices</span>
          </div>
          <span class="stat-div" aria-hidden="true"></span>
          <div class="hero-stat">
            <span class="hsc-val">Zero Risk</span>
            <span class="hsc-label">No real money</span>
          </div>
        </div>

      </div>

      <div class="market-strip" aria-label="Live market ticker">
        <div class="marquee-track-wrap">
          <div id="ticker-row" class="marquee-track">
            <div class="ticker-loading">Loading market data…</div>
          </div>
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
            <div class="step-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
              </svg>
            </div>
            <h3>Create your account</h3>
            <p>Sign up quickly and access your PaperTrade workspace from any device.</p>
          </article>

          <article class="step-card">
            <div class="step-number">2</div>
            <div class="step-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/>
              </svg>
            </div>
            <h3>Receive virtual cash</h3>
            <p>Start with <strong>$100,000</strong> in simulated buying power to explore safely.</p>
          </article>

          <article class="step-card">
            <div class="step-number">3</div>
            <div class="step-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
              </svg>
            </div>
            <h3>Practice and review</h3>
            <p>Track symbols, test decisions, and learn from your portfolio performance over time.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section features-section">
      <div class="container">
        <div class="section-heading">
          <span class="eyebrow">Why PaperTrade</span>
          <h2>Built to help you learn by doing</h2>
          <p class="section-sub">All the tools you need to practice like a real trader, without any real risk.</p>
        </div>
        <div class="features-grid">

          <div class="feature-card">
            <div class="feature-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
              </svg>
            </div>
            <h3>Live market data</h3>
            <p>Track real stock prices and market movements as they happen, sourced from live market feeds.</p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              </svg>
            </div>
            <h3>Zero financial risk</h3>
            <p>Practice with virtual money — no real funds, no real losses, no stress about making mistakes.</p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
              </svg>
            </div>
            <h3>Portfolio tracking</h3>
            <p>Monitor your holdings, profit &amp; loss, and total portfolio value updated in real time.</p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
              </svg>
            </div>
            <h3>Performance review</h3>
            <p>Review your full trade history and gain insight from every buy and sell decision you make.</p>
          </div>

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

          <form id="signup" class="cta-form" method="GET" action="dashboard/auth/registration.php" novalidate>
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