## PaperTrade

PaperTrade is a PHP/MySQL web app for paper trading. It ships with a marketing landing page and a logged-in dashboard where users can practice buying and selling stocks with simulated, live-like prices, track holdings, watchlists, and see portfolio performance — all without using real money. GitHub repo: https://github.com/nirajang20/PaperTrade-Inc.

### Features
- Landing page with animated ticker, hero canvas chart, feature highlights, and email capture CTA.
- Authenticated dashboard built on AdminLTE showing portfolio value, cash, unrealized P&L, buying power, allocation pie, performance timeline, holdings table, watchlist, notifications, and recent trades.
- Buy/sell, watchlist, and performance views under `dashboard/trading` and `dashboard/portfolio`, with server-side MySQL storage for users, holdings, watchlists, transactions, and notifications.
- Live-ish data via `http://74.208.158.229/stock-api/*` endpoints for quotes, ticker, and dashboard polling; front end uses Chart.js, jQuery, SweetAlert, and Toastr.
- PHPMailer wiring (see `mail.php`) for SMTP email examples.

### Requirements
- PHP 8.x (7.4+ should work) with mysqli extension
- MySQL/MariaDB
- Composer (for PHP dependencies)
- A web server (Apache/nginx) or PHP built-in server for local use

### Quick start with XAMPP + phpMyAdmin
1) Install XAMPP and start Apache + MySQL.  
2) Place the project folder inside `htdocs` (e.g., `C:/xampp/htdocs/trade` or `/Applications/XAMPP/xamppfiles/htdocs/trade`).  
3) Run `composer install` in the project directory to pull PHP deps.  
4) Open `http://localhost/phpmyadmin`, create a database (e.g., `trade`), and import the included SQL dump (`*.sql` in the repo) to create tables and sample data.  
5) Update `dashboard/lib/Core.php` with your DB credentials/port and adjust `URL_HOST` if needed.  
6) Visit `http://localhost/trade/` (landing) and `http://localhost/trade/dashboard/login.php` (app) to log in with a user from the imported SQL or a user you insert manually.

### Getting started
1) Install PHP, MySQL, and Composer.  
2) Clone the repo into your web root (e.g., XAMPP `htdocs`):  
   ```bash
   git clone https://github.com/nirajang20/PaperTrade-Inc.git trade
   cd trade
   composer install
   ```
3) Configure database access in `dashboard/lib/Core.php`:
   - Update `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.
   - Adjust `URL_HOST` if you serve the dashboard from a different base URL.
   - There is an SSH tunnel hint in the file if you need to forward MySQL to port `3307`.
4) Create the database and tables (schema not committed). Based on the code, you will need tables such as `users`, `holding`, `available_trade`, `transaction`, `watchlist`, and `notifications`. Seed at least one user row you can log in with.
5) Start the app:
   - Apache/nginx: point the virtual host to the repository root.
   - PHP built-in (for quick checks): `php -S localhost:8000 -t .`
6) Visit `http://localhost/trade/` for the landing page and `http://localhost/trade/dashboard/login.php` to sign in.

### Configuration notes
- Market data: endpoints are hard-coded to `http://74.208.158.229/stock-api/` in `js/dashboard-stocks.js`, `dashboard/ajax-portfolio-worth.php`, and dashboard PHP pages. Swap these URLs if you host your own feed.
- Email: `mail.php` is an example PHPMailer script; replace the SMTP credentials with environment-safe values before using it.
- Assets: AdminLTE, Font Awesome, FullCalendar, Chart.js, jQuery, SweetAlert2, and Toastr are loaded via `dashboard/lib/PAGE-top.php`. Landing page styles/scripts live under `css/` and `js/`.

### Project structure (high level)
- `index.php` — public landing page.
- `dashboard/` — authenticated app (AdminLTE shell, metrics, tables, charts).
- `dashboard/lib/` — bootstrap, DB connection, layout includes.
- `dashboard/trading/`, `dashboard/portfolio/` — buy/sell flows, watchlist, performance.
- `js/`, `css/`, `img/` — front-end assets for the marketing page.
- `vendor/` — Composer dependencies (PHPMailer).

### Security and production checklist
- Store secrets (DB credentials, SMTP passwords) outside of VCS; `mail.php` contains placeholders you should replace with environment-managed values.
- Serve over HTTPS and configure proper session settings for production.
- Validate/escape all user input before writing to the database; the code already uses prepared statements in most DB calls — follow that pattern for new endpoints.
- Add rate limiting/CSRF protection around auth and trading actions if exposing publicly.

### Contributing
- Fork and branch from `main`.
- Keep PHP code PSR-12 compliant; prefer prepared statements and server-side validation.
- Add context to UI changes with screenshots/gifs when opening PRs.


- test