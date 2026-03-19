<?php
declare(strict_types=1);

// (A) INIT
require_once __DIR__ . "/../lib/Core.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION["user"]) && is_array($_SESSION["user"])) {
    header("Location: ../index.php");
    exit;
}

$_ISLOGIN = 1;
require PATH_LIB . "PAGE-top.php";
?>

<script>
function login() {
  const email = document.getElementById("user_email").value.trim();
  const password = document.getElementById("user_password").value;

  if (!email || !password) {
    if (typeof swal === "function") {
      swal("Missing fields", "Please enter both your email and password.");
    } else {
      alert("Please enter both your email and password.");
    }
    return false;
  }

  admin.ajax({
    url: "ajax/session.php",
    data: {
      req: "in",
      email: email,
      password: password
    },
    ok: function () {
      window.location.href = "../index.php";
    },
    fail: function (message) {
      if (typeof swal === "function") {
        swal("Login failed", message || "Unable to sign in.");
      } else {
        alert(message || "Unable to sign in.");
      }
    }
  });

  return false;
}

document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  body.classList.add("login-page-enhanced");
  body.classList.remove("sidebar-mini", "layout-fixed");

  const passwordField = document.getElementById("user_password");
  const togglePassword = document.getElementById("toggle-password");

  if (togglePassword && passwordField) {
    togglePassword.addEventListener("click", () => {
      const isHidden = passwordField.type === "password";
      passwordField.type = isHidden ? "text" : "password";
      togglePassword.setAttribute("aria-pressed", String(isHidden));

      const icon = togglePassword.querySelector("i");
      const label = togglePassword.querySelector("span");

      if (icon) {
        icon.classList.toggle("fa-eye", !isHidden);
        icon.classList.toggle("fa-eye-slash", isHidden);
      }

      if (label) {
        label.textContent = isHidden ? "Hide" : "Show";
      }
    });
  }

  const inputs = document.querySelectorAll(".field input");
  inputs.forEach((input) => {
    const field = input.closest(".field");
    if (!field) return;

    const setFilled = () => {
      if (input.value.trim().length) field.classList.add("filled");
      else field.classList.remove("filled");
    };

    input.addEventListener("focus", () => field.classList.add("focused"));
    input.addEventListener("blur", () => {
      field.classList.remove("focused");
      setFilled();
    });
    input.addEventListener("input", setFilled);
    setFilled();
  });
});
</script>

<style>
:root {
  --login-primary: #6c5ce7;
  --login-accent: #00d4ff;
  --login-surface: rgba(15, 23, 42, 0.55);
  --login-border: rgba(148, 163, 184, 0.3);
  --login-text-strong: #f8fafc;
  --login-text-muted: rgba(248, 250, 252, 0.78);
}

body.login-page-enhanced {
  font-family: "Source Sans Pro", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  background:
    radial-gradient(circle at 0% 0%, rgba(108, 92, 231, 0.55), transparent 55%),
    radial-gradient(circle at 100% 0%, rgba(0, 212, 255, 0.45), transparent 60%),
    linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #312e81 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4vh 2vw;
  position: relative;
  overflow: hidden;
}

body.login-page-enhanced::before,
body.login-page-enhanced::after {
  content: "";
  position: absolute;
  width: 520px;
  height: 520px;
  border-radius: 50%;
  filter: blur(120px);
  opacity: 0.7;
  animation: float 16s ease-in-out infinite;
  pointer-events: none;
}

body.login-page-enhanced::before {
  background: rgba(108, 92, 231, 0.45);
  top: -160px;
  left: -220px;
}

body.login-page-enhanced::after {
  background: rgba(0, 212, 255, 0.38);
  bottom: -220px;
  right: -260px;
  animation-delay: 6s;
}

body.login-page-enhanced .wrapper {
  width: 100%;
  max-width: 1100px;
  min-height: clamp(520px, 70vh, 760px);
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  padding: 0;
}

.login-shell {
  position: relative;
  width: min(100%, 1000px);
  background: rgba(15, 23, 42, 0.65);
  border-radius: 32px;
  backdrop-filter: blur(26px);
  box-shadow: 0 30px 70px rgba(15, 23, 42, 0.55);
  padding: clamp(2.5rem, 2vw + 1.5rem, 3.5rem);
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: clamp(2rem, 4vw, 3.5rem);
  overflow: hidden;
  isolation: isolate;
  animation: fade-up 0.6s ease-out both;
}

.login-shell::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, rgba(108, 92, 231, 0.25), rgba(0, 212, 255, 0.1));
  opacity: 0.65;
  mix-blend-mode: screen;
  pointer-events: none;
  z-index: -1;
}

.login-brand {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 1.6rem;
  color: var(--login-text-strong);
}

.login-logo {
  display: block;
  width: clamp(140px, 18vw, 240px);
  max-width: 260px;
  filter: drop-shadow(0 18px 36px rgba(15, 23, 42, 0.55));
  transition: transform 0.35s ease, filter 0.35s ease;
}

.login-logo:hover {
  transform: translateY(-4px);
  filter: drop-shadow(0 26px 52px rgba(15, 23, 42, 0.65));
}

.login-brand h1 {
  font-size: clamp(2.25rem, 1.5vw + 1.85rem, 3.1rem);
  font-weight: 700;
  letter-spacing: 0.02em;
  margin: 0;
}

.login-brand p {
  margin: 0;
  font-size: 1rem;
  line-height: 1.6;
  color: var(--login-text-muted);
}

.login-highlights {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.9rem;
  color: var(--login-text-muted);
}

.login-highlights li {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 0.95rem;
}

.login-highlights i {
  color: var(--login-accent);
}

#login-form {
  position: relative;
  background: var(--login-surface);
  border-radius: 24px;
  padding: clamp(2rem, 2vw + 1.5rem, 2.75rem);
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 1.4rem;
  border: 1px solid var(--login-border);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
}

.login-form-header h2 {
  margin: 0 0 0.35rem;
  color: var(--login-text-strong);
  font-size: clamp(1.8rem, 1vw + 1.4rem, 2.35rem);
}

.login-form-header p {
  margin: 0;
  color: var(--login-text-muted);
  line-height: 1.55;
}

.field {
  position: relative;
  display: grid;
  gap: 0.55rem;
}

.field-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: rgba(226, 232, 240, 0.95);
}

.field-control {
  position: relative;
  display: flex;
  align-items: center;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.3);
  background: rgba(15, 23, 42, 0.55);
  transition: border-color 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
}

.field.focused .field-control,
.field-control:focus-within {
  border-color: rgba(0, 212, 255, 0.75);
  box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.16);
  background: rgba(15, 23, 42, 0.72);
}

.field-icon {
  width: 52px;
  display: grid;
  place-items: center;
  color: rgba(148, 163, 184, 0.9);
  font-size: 1rem;
}

.field input {
  flex: 1;
  border: 0;
  outline: 0;
  background: transparent;
  color: var(--login-text-strong);
  font-size: 1rem;
  padding: 1rem 0.35rem 1rem 0;
}

.field input::placeholder {
  color: rgba(148, 163, 184, 0.72);
}

.password-toggle {
  border: 0;
  background: transparent;
  color: rgba(226, 232, 240, 0.85);
  padding: 0 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  cursor: pointer;
  font-size: 0.9rem;
}

.password-toggle:hover {
  color: #fff;
}

.login-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.login-meta a {
  color: #c4b5fd;
  text-decoration: none;
}

.login-meta a:hover {
  color: #fff;
  text-decoration: underline;
}

.login-submit {
  border: 0;
  border-radius: 18px;
  background: linear-gradient(135deg, var(--login-primary), var(--login-accent));
  color: #fff;
  font-weight: 700;
  letter-spacing: 0.01em;
  padding: 1rem 1.2rem;
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease, filter 0.25s ease;
  box-shadow: 0 18px 30px rgba(76, 29, 149, 0.35);
}

.login-submit:hover {
  transform: translateY(-2px);
  filter: brightness(1.04);
}

.login-submit:active {
  transform: translateY(0);
}

.login-footer {
  text-align: center;
  color: var(--login-text-muted);
  font-size: 0.95rem;
}

.login-footer a {
  color: #bfdbfe;
  font-weight: 600;
  text-decoration: none;
}

.login-footer a:hover {
  color: #fff;
  text-decoration: underline;
}

@keyframes fade-up {
  from {
    opacity: 0;
    transform: translateY(28px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes float {
  0%, 100% {
    transform: translate3d(0, 0, 0);
  }
  50% {
    transform: translate3d(16px, 22px, 0);
  }
}

@media (max-width: 767px) {
  body.login-page-enhanced {
    padding: 1.4rem;
  }

  .login-shell {
    border-radius: 24px;
    padding: 1.4rem;
  }

  #login-form {
    padding: 1.5rem;
    border-radius: 20px;
  }
}
</style>

<div class="login-shell">
  <section class="login-brand">
    <img src="<?= URL_HOST ?>dist/img/logo-horizontal.png" alt="PaperTrade Logo" class="login-logo">
    <div>
      <h1>Welcome back</h1>
      <p>Sign in to monitor your portfolio, review market activity, and continue trading with confidence.</p>
    </div>
    <ul class="login-highlights">
      <li><i class="fas fa-chart-line"></i> Track live portfolio performance</li>
      <li><i class="fas fa-shield-alt"></i> Secure account access with encrypted credentials</li>
      <li><i class="fas fa-bell"></i> Stay updated with reminders and notifications</li>
    </ul>
  </section>

  <form id="login-form" onsubmit="return login();">
    <div class="login-form-header">
      <h2>Sign in</h2>
      <p>Use your PaperTrade account credentials to continue.</p>
    </div>

    <div class="field">
      <label class="field-label" for="user_email">Email address</label>
      <div class="field-control">
        <span class="field-icon"><i class="fas fa-envelope"></i></span>
        <input type="email" id="user_email" name="user_email" placeholder="you@example.com" autocomplete="email" required>
      </div>
    </div>

    <div class="field">
      <label class="field-label" for="user_password">Password</label>
      <div class="field-control">
        <span class="field-icon"><i class="fas fa-lock"></i></span>
        <input type="password" id="user_password" name="user_password" placeholder="Enter your password" autocomplete="current-password" required>
        <button type="button" class="password-toggle" id="toggle-password" aria-pressed="false">
          <i class="fas fa-eye"></i>
          <span>Show</span>
        </button>
      </div>
    </div>

    <div class="login-meta">
      <a href="forgot-password.php">Forgot password?</a>
      <a href="registration.php">Create account</a>
    </div>

    <button type="submit" class="login-submit">Sign In</button>

    <div class="login-footer">
      Need an account? <a href="registration.php">Register here</a>
    </div>
  </form>
</div>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>