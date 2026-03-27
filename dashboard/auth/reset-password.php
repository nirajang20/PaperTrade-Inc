<?php
declare(strict_types=1);

require __DIR__ . "/../lib/Core.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["reset_verified"])) {
    header("Location: forgot-password.php");
    exit;
}

$resetEmail = (string)($_SESSION["reset_email"] ?? "");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Paper Trade | Reset Password</title>
<style>
:root {
  --auth-primary: #6c5ce7;
  --auth-accent: #00d4ff;
  --auth-surface: rgba(15, 23, 42, 0.86);
  --auth-border: rgba(148, 163, 184, 0.28);
  --auth-text-strong: #f8fafc;
  --auth-text-muted: rgba(248, 250, 252, 0.78);
  --auth-error: #f87171;
  --auth-success: #4ade80;
}

*, *::before, *::after { box-sizing: border-box; }

body {
  margin: 0;
  font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  background:
    radial-gradient(circle at 0% 0%, rgba(108, 92, 231, 0.45), transparent 55%),
    radial-gradient(circle at 100% 0%, rgba(0, 212, 255, 0.35), transparent 60%),
    linear-gradient(135deg, #0f172a 0%, #1e293b 52%, #312e81 100%);
  color: var(--auth-text-strong);
  min-height: 100vh;
}

body::before,
body::after {
  content: "";
  position: fixed;
  width: 480px;
  height: 480px;
  border-radius: 50%;
  filter: blur(120px);
  opacity: 0.55;
  pointer-events: none;
  z-index: 0;
}

body::before {
  top: -160px;
  left: -160px;
  background: rgba(108, 92, 231, 0.45);
}

body::after {
  right: -180px;
  bottom: -180px;
  background: rgba(0, 212, 255, 0.3);
}

main.auth-wrapper {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.25rem;
  min-height: 100vh;
}

.auth-shell {
  width: min(100%, 1320px);
  background: rgba(15, 23, 42, 0.68);
  border-radius: 30px;
  padding: clamp(1.5rem, 2vw, 2.2rem);
  box-shadow: 0 30px 80px rgba(0, 0, 0, 0.42);
  backdrop-filter: blur(24px);
  display: grid;
  grid-template-columns: minmax(320px, 1.08fr) minmax(420px, 1fr);
  gap: 1.5rem;
  overflow: hidden;
}

.auth-brand {
  position: relative;
  padding: clamp(1.4rem, 2vw, 2.25rem);
  border-radius: 24px;
  background:
    linear-gradient(135deg, rgba(108, 92, 231, 0.22), rgba(0, 212, 255, 0.08)),
    rgba(15, 23, 42, 0.44);
  border: 1px solid rgba(255,255,255,0.08);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 1.5rem;
}

.auth-brand img {
  width: min(220px, 58%);
  max-width: 220px;
}

.auth-brand h1 {
  font-size: clamp(2rem, 1vw + 1.6rem, 3rem);
  line-height: 1.15;
  margin: 0;
}

.auth-brand p {
  margin: 0;
  color: var(--auth-text-muted);
  line-height: 1.75;
  font-size: 1rem;
}

.auth-feature-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
}

.auth-feature {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px;
  padding: 1rem;
}

.auth-feature strong {
  display: block;
  margin-bottom: 0.3rem;
  font-size: 0.95rem;
}

.auth-feature span {
  color: var(--auth-text-muted);
  font-size: 0.9rem;
  line-height: 1.5;
}

.auth-panel {
  background: var(--auth-surface);
  border-radius: 24px;
  padding: clamp(1.4rem, 2vw, 2.2rem);
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.auth-panel-header {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  margin-bottom: 1.4rem;
}

.auth-panel-header h2 {
  margin: 0;
  font-size: 1.8rem;
}

.auth-panel-header p {
  margin: 0;
  color: var(--auth-text-muted);
  line-height: 1.6;
}

.email-badge {
  display: inline-block;
  padding: 0.65rem 0.85rem;
  border-radius: 12px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.08);
  color: var(--auth-text-strong);
  font-size: 0.92rem;
  word-break: break-word;
}

.field {
  margin-bottom: 1rem;
}

.field label {
  display: block;
  margin-bottom: 0.42rem;
  font-size: 0.92rem;
  color: var(--auth-text-muted);
  font-weight: 600;
}

.field-control {
  width: 100%;
  border-radius: 14px;
  border: 1px solid var(--auth-border);
  background: rgba(255, 255, 255, 0.045);
  color: var(--auth-text-strong);
  padding: 0.95rem 1rem;
  font-size: 1rem;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.field-control:focus {
  outline: none;
  border-color: rgba(108, 92, 231, 0.7);
  box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.16);
  background: rgba(255,255,255,0.07);
}

.password-wrap {
  position: relative;
}

.password-toggle {
  position: absolute;
  top: 50%;
  right: 0.9rem;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: var(--auth-text-muted);
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 600;
}

.password-meter {
  margin-top: 0.65rem;
}

.password-meter-track {
  width: 100%;
  height: 8px;
  border-radius: 999px;
  background: rgba(255,255,255,0.08);
  overflow: hidden;
}

.password-meter-fill {
  height: 100%;
  width: 0%;
  border-radius: 999px;
  transition: width 0.2s ease;
}

.password-meter-text {
  margin-top: 0.45rem;
  font-size: 0.88rem;
  color: var(--auth-text-muted);
}

.btn-row {
  display: flex;
  gap: 0.9rem;
  justify-content: space-between;
  flex-wrap: wrap;
  margin-top: 1rem;
}

.btn {
  width: 100%;
  padding: 0.92rem 1.5rem;
  border: none;
  border-radius: 14px;
  font-weight: 700;
  cursor: pointer;
  font-size: 0.96rem;
  transition: transform 0.18s ease, opacity 0.18s ease, box-shadow 0.18s ease;
}

.btn.primary {
  background: linear-gradient(135deg, var(--auth-primary), var(--auth-accent));
  color: #fff;
  box-shadow: 0 14px 28px rgba(108, 92, 231, 0.28);
}

.btn:hover {
  opacity: 0.96;
  transform: translateY(-1px);
}

.feedback-box {
  margin-top: 1rem;
  padding: 0.95rem 1rem;
  border-radius: 14px;
  display: none;
  font-size: 0.95rem;
}

.feedback-box.error {
  display: block;
  background: rgba(248, 113, 113, 0.1);
  border: 1px solid var(--auth-error);
  color: #ffd5d5;
}

.feedback-box.success {
  display: block;
  background: rgba(74, 222, 128, 0.1);
  border: 1px solid var(--auth-success);
  color: #d8ffe5;
}

.auth-links {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 1.2rem;
}

.auth-links a {
  color: var(--auth-accent);
  text-decoration: none;
  font-weight: 600;
}

.auth-links a:hover {
  text-decoration: underline;
}

.small-note {
  color: var(--auth-text-muted);
  font-size: 0.9rem;
  line-height: 1.65;
  margin-top: 0.4rem;
}

.guidelines {
  margin: 0.7rem 0 0 1rem;
  padding: 0;
  color: var(--auth-text-muted);
  font-size: 0.9rem;
  line-height: 1.7;
}

@media (max-width: 1100px) {
  .auth-shell {
    grid-template-columns: 1fr;
  }

  .auth-brand {
    order: 2;
  }

  .auth-panel {
    order: 1;
  }
}

@media (max-width: 720px) {
  .auth-feature-grid {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>
  <main class="auth-wrapper">
    <section class="auth-shell">

      <div class="auth-brand">
        <div>
          <img src="../../img/logo-horizontal.png" alt="Paper Trade Logo">
          <h1>Create a strong new password</h1>
          <p>
            Your reset code has been verified. Set a new password below to restore
            secure access to your PaperTrade account.
          </p>
        </div>

        <div class="auth-feature-grid">
          <div class="auth-feature">
            <strong>Verified reset flow</strong>
            <span>You can now safely update your password after successful OTP verification.</span>
          </div>
          <div class="auth-feature">
            <strong>Account protection</strong>
            <span>Choose a unique password that is harder to guess and not reused elsewhere.</span>
          </div>
          <div class="auth-feature">
            <strong>Immediate recovery</strong>
            <span>Once updated, you’ll be able to sign back in with your new credentials.</span>
          </div>
          <div class="auth-feature">
            <strong>Secure transition</strong>
            <span>Your reset authorization will expire after the password change is complete.</span>
          </div>
        </div>
      </div>

      <div class="auth-panel">
        <div class="auth-panel-header">
          <h2>Reset Password</h2>
          <p>Set a new password for the account below.</p>
          <div class="email-badge"><?php echo htmlspecialchars($resetEmail, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <form id="resetForm" novalidate>
          <div class="field">
            <label for="password">New Password *</label>
            <div class="password-wrap">
              <input
                class="field-control"
                type="password"
                id="password"
                name="password"
                placeholder="Enter new password"
                required
              >
              <button type="button" class="password-toggle" data-target="password">Show</button>
            </div>

            <div class="password-meter">
              <div class="password-meter-track">
                <div class="password-meter-fill" id="passwordMeterFill"></div>
              </div>
              <div class="password-meter-text" id="passwordMeterText">Strength: —</div>
            </div>
          </div>

          <div class="field">
            <label for="confirm">Confirm Password *</label>
            <div class="password-wrap">
              <input
                class="field-control"
                type="password"
                id="confirm"
                name="confirm"
                placeholder="Confirm new password"
                required
              >
              <button type="button" class="password-toggle" data-target="confirm">Show</button>
            </div>
          </div>

          <ul class="guidelines">
            <li>Use at least 8 characters.</li>
            <li>Include uppercase, lowercase, numbers, and symbols.</li>
            <li>Avoid reusing an old or common password.</li>
          </ul>

          <div class="btn-row">
            <button type="submit" class="btn primary" id="updatePasswordBtn">Update Password</button>
          </div>
        </form>

        <div id="msg" class="feedback-box"></div>

        <div class="auth-links">
          <a href="login.php">Back to login</a>
          <a href="forgot-password.php">Start reset again</a>
        </div>
      </div>

    </section>
  </main>

<script>
const resetForm = document.getElementById("resetForm");
const msgBox = document.getElementById("msg");
const updatePasswordBtn = document.getElementById("updatePasswordBtn");
const passwordField = document.getElementById("password");
const confirmField = document.getElementById("confirm");
const meterFill = document.getElementById("passwordMeterFill");
const meterText = document.getElementById("passwordMeterText");

document.querySelectorAll(".password-toggle").forEach((button) => {
  button.addEventListener("click", () => {
    const targetId = button.getAttribute("data-target");
    const target = document.getElementById(targetId);
    if (!target) return;

    const hidden = target.type === "password";
    target.type = hidden ? "text" : "password";
    button.textContent = hidden ? "Hide" : "Show";
  });
});

function updatePasswordStrength(password) {
  if (!password) {
    meterFill.style.width = "0%";
    meterFill.style.background = "transparent";
    meterText.textContent = "Strength: —";
    return;
  }

  const checks = [
    password.length >= 8,
    /[A-Z]/.test(password),
    /[a-z]/.test(password),
    /\d/.test(password),
    /[^A-Za-z0-9]/.test(password)
  ];

  const passed = checks.filter(Boolean).length;
  const percent = (passed / checks.length) * 100;

  meterFill.style.width = percent + "%";

  let label = "Weak";
  let color = "#f87171";

  if (passed >= 5) {
    label = "Strong";
    color = "#4ade80";
  } else if (passed === 4) {
    label = "Good";
    color = "#22c55e";
  } else if (passed === 3) {
    label = "Fair";
    color = "#facc15";
  }

  meterFill.style.background = color;
  meterText.textContent = "Strength: " + label;
}

passwordField.addEventListener("input", function () {
  updatePasswordStrength(this.value);
});

resetForm.addEventListener("submit", async function(e) {
  e.preventDefault();

  const password = passwordField.value;
  const confirm = confirmField.value;

  msgBox.className = "feedback-box";
  msgBox.textContent = "";

  if (!password || !confirm) {
    msgBox.className = "feedback-box error";
    msgBox.textContent = "Please complete both password fields.";
    return;
  }

  updatePasswordBtn.disabled = true;
  updatePasswordBtn.textContent = "Updating...";

  try {
    const form = new FormData(resetForm);

    const res = await fetch("ajax/update-password.php", {
      method: "POST",
      body: form
    });

    const data = await res.json();

    if (data.status === "success") {
      msgBox.className = "feedback-box success";
      msgBox.textContent = data.message || "Password updated successfully.";

      setTimeout(() => {
        window.location = "login.php";
      }, 900);
    } else {
      msgBox.className = "feedback-box error";
      msgBox.textContent = data.message || "Unable to update password.";
    }
  } catch (err) {
    msgBox.className = "feedback-box error";
    msgBox.textContent = "A network error occurred. Please try again.";
  } finally {
    updatePasswordBtn.disabled = false;
    updatePasswordBtn.textContent = "Update Password";
  }
});
</script>
</body>
</html>