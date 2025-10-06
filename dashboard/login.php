<?php
// (A) INIT
require "lib/Core.php";
$_ISLOGIN = 1;
require PATH_LIB . "PAGE-top.php"; ?>

<!-- (B) JAVASCRIPT -->
<script>
function login () {
  admin.ajax({
    url : url.host + "ajax-session.php",
    data : {
      req : "in",
      email : document.getElementById("user_email").value,
      password : document.getElementById("user_password").value
    },
    ok : () => { location.reload(); }
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
      if (label) { label.textContent = isHidden ? "Hide" : "Show"; }
    });
  }
  const inputs = document.querySelectorAll(".field input");
  inputs.forEach((input) => {
    const field = input.closest(".field");
    if (!field) { return; }
    const setFilled = () => {
      if (input.value.trim().length) { field.classList.add("filled"); }
      else { field.classList.remove("filled"); }
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
<!-- (C) CSS -->
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
  background: radial-gradient(circle at 0% 0%, rgba(108, 92, 231, 0.55), transparent 55%),
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
  width: clamp(90px, 12vw, 140px);
  filter: drop-shadow(0 12px 24px rgba(15, 23, 42, 0.45));
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
  gap: 1.6rem;
  color: var(--login-text-strong);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08),
              0 25px 50px rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(20px);
  overflow: hidden;
}
#login-form::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top, rgba(108, 92, 231, 0.28), transparent 55%);
  opacity: 0;
  transition: opacity 0.6s ease;
  pointer-events: none;
}
#login-form:hover::after {
  opacity: 1;
}
#login-form h2 {
  margin: 0;
  font-size: clamp(1.6rem, 1vw + 1.35rem, 2.1rem);
  font-weight: 700;
}
#login-form p.subtitle {
  margin: 0;
  color: var(--login-text-muted);
  font-size: 0.96rem;
}
.field {
  position: relative;
}
.field label {
  position: absolute;
  top: 1.1rem;
  left: 1.2rem;
  font-size: 0.9rem;
  color: var(--login-text-muted);
  pointer-events: none;
  transform-origin: left top;
  transition: transform 0.22s ease, color 0.22s ease, opacity 0.22s ease;
}
.field input {
  width: 100%;
  padding: 1.2rem 3.2rem 1.1rem 1.2rem;
  border-radius: 18px;
  border: 1px solid var(--login-border);
  background: rgba(15, 23, 42, 0.75);
  color: var(--login-text-strong);
  font-size: 1rem;
  transition: border-color 0.28s ease, box-shadow 0.28s ease, background 0.28s ease;
  outline: none;
}
.field .focus-ring {
  position: absolute;
  inset: 0;
  border-radius: 18px;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.28s ease;
  border: 1px solid transparent;
  background: linear-gradient(140deg, rgba(108, 92, 231, 0.6), rgba(0, 212, 255, 0.45));
  filter: blur(12px);
}
.field.focused .focus-ring {
  opacity: 0.6;
}
.field.focused input {
  border-color: rgba(108, 92, 231, 0.65);
  box-shadow: 0 18px 35px rgba(15, 23, 42, 0.35);
}
.field.filled label,
.field.focused label {
  transform: translateY(-1.65rem) scale(0.9);
  color: var(--login-text-strong);
}
.password-toggle {
  position: absolute;
  top: 50%;
  right: 1.1rem;
  transform: translateY(-50%);
  padding: 0;
  background: none;
  border: none;
  color: var(--login-text-muted);
  font-size: 0.85rem;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  cursor: pointer;
  transition: color 0.25s ease, transform 0.25s ease;
}
.password-toggle:hover {
  color: var(--login-text-strong);
  transform: translateY(-50%) scale(1.05);
}
.login-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.login-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  border: none;
  border-radius: 18px;
  padding: 0.95rem 2.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: #0b1120;
  background: linear-gradient(135deg, var(--login-primary), var(--login-accent));
  box-shadow: 0 18px 35px rgba(108, 92, 231, 0.45);
  cursor: pointer;
  transition: transform 0.22s ease, box-shadow 0.22s ease;
}
.login-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 25px 45px rgba(108, 92, 231, 0.5);
}
.login-submit:active {
  transform: translateY(1px);
}
.login-links {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  font-size: 0.9rem;
  color: var(--login-text-muted);
}
.login-links a {
  color: var(--login-accent);
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s ease;
}
.login-links a:hover {
  color: var(--login-text-strong);
}
.login-footer {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: var(--login-text-muted);
}
@media (max-width: 820px) {
  body.login-page-enhanced {
    padding: 6vh 1.5rem;
  }
  .login-shell {
    border-radius: 24px;
    padding: 2.4rem;
  }
  .login-brand {
    order: 2;
    text-align: center;
  }
  #login-form {
    order: 1;
  }
}
@media (max-width: 480px) {
  .login-shell {
    padding: 2rem;
  }
  #login-form {
    padding: 1.75rem;
  }
  .password-toggle {
    right: 0.85rem;
  }
}
@keyframes float {
  0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
  50% { transform: translate3d(30px, -20px, 0) scale(1.05); }
}
@keyframes fade-up {
  from {
    opacity: 0;
    transform: translate3d(0, 24px, 0);
  }
  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}
@media (prefers-reduced-motion: reduce) {
  body.login-page-enhanced,
  body.login-page-enhanced::before,
  body.login-page-enhanced::after,
  .login-shell {
    animation: none;
  }
  .login-submit,
  .password-toggle,
  .field input {
    transition: none;
  }
}
</style>
<!-- (D) LOGIN FORM -->
<div class="login-shell">
  <div class="login-brand">
    <img src="dist/img/logo-horizontal.png" class="login-logo" alt="Paper Trade" />
    <h1>Trade Dashboard</h1>
    <p>Access real-time insights, execute trades, and monitor performance with a workspace designed for speed and clarity.</p>
    <ul class="login-highlights">
      <li><i class="fas fa-bolt"></i> Fast trade execution with smart routing</li>
      <li><i class="fas fa-chart-line"></i> Live analytics tailored to your strategies</li>
      <li><i class="fas fa-bell"></i> Intelligent alerts when the market moves</li>
    </ul>
  </div>
  <form id="login-form" onsubmit="return login();" autocomplete="on">
    <h2>Welcome back</h2>
    <p class="subtitle">Sign in to continue to your trading desk.</p>
    <div class="field">
      <label for="user_email">Email address</label>
      <input type="email" id="user_email" name="email" autocomplete="email" required />
      <span class="focus-ring"></span>
    </div>
    <div class="field">
      <label for="user_password">Password</label>
      <input type="password" id="user_password" name="password" autocomplete="current-password" required />
      <button type="button" class="password-toggle" id="toggle-password" aria-label="Toggle password visibility" aria-pressed="false">
        <i class="fas fa-eye"></i>
        <span>Show</span>
      </button>
      <span class="focus-ring"></span>
    </div>
    <div class="login-actions">
      <div class="login-links">
        <a href="#" onclick="swal('Reset password', 'Please contact support to reset your password.'); return false;">Forgot password?</a>
        <span>Need an account? <a href="registration.php">Request access</a></span>
      </div>
      <button type="submit" class="login-submit">
        <i class="fas fa-sign-in-alt"></i>
        Sign in
      </button>
    </div>
    <div class="login-footer">
      <span>Session secured with 256-bit encryption.</span>
      <span>Questions? Reach out to your trading desk administrator.</span>
    </div>
  </form>
</div>
<?php require PATH_LIB . "PAGE-bottom.php"; ?>