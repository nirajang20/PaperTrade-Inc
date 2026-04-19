<?php
declare(strict_types=1);

require __DIR__ . "/../lib/Core.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["reset_email"])) {
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
<title>Paper Trade | Verify OTP</title>
<style>
:root {
  --auth-primary: #6c5ce7;
  --auth-accent: #00d4ff;
  --auth-surface: rgba(15, 23, 42, 0.86);
  --auth-surface-soft: rgba(15, 23, 42, 0.62);
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

.otp-code {
  text-align: center;
  letter-spacing: 0.35rem;
  font-size: 1.2rem;
  font-weight: 700;
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

.auth-links a,
.auth-links button {
  color: var(--auth-accent);
  text-decoration: none;
  font-weight: 600;
  background: none;
  border: none;
  padding: 0;
  text-align: left;
  cursor: pointer;
  font: inherit;
}

.auth-links a:hover,
.auth-links button:hover {
  text-decoration: underline;
}

.small-note {
  color: var(--auth-text-muted);
  font-size: 0.9rem;
  line-height: 1.65;
  margin-top: 0.4rem;
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
          <h1>Verify your reset code</h1>
          <p>
            Enter the one-time password sent to your email to continue the secure
            password reset process for your PaperTrade account.
          </p>
        </div>

        <div class="auth-feature-grid">
          <div class="auth-feature">
            <strong>Email verification</strong>
            <span>Use the OTP sent to your registered email address to confirm identity.</span>
          </div>
          <div class="auth-feature">
            <strong>Short-lived code</strong>
            <span>Your reset code expires quickly to help protect account access.</span>
          </div>
          <div class="auth-feature">
            <strong>Secure reset flow</strong>
            <span>Password changes are allowed only after your OTP is verified successfully.</span>
          </div>
          <div class="auth-feature">
            <strong>Fast recovery</strong>
            <span>Continue to the password reset page immediately after successful verification.</span>
          </div>
        </div>
      </div>

      <div class="auth-panel">
        <div class="auth-panel-header">
          <h2>Verify OTP</h2>
          <p>Enter the 6-digit code sent to your email address.</p>
          <div class="email-badge"><?php echo htmlspecialchars($resetEmail, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <form id="otpForm" novalidate>
          <div class="field">
            <label for="otp">6-digit OTP *</label>
            <input
              class="field-control otp-code"
              type="text"
              id="otp"
              name="otp"
              placeholder="000000"
              inputmode="numeric"
              maxlength="6"
              required
            >
          </div>

          <p class="small-note">
            Make sure you enter the latest code sent to your email.
          </p>

          <div class="btn-row">
            <button type="submit" class="btn primary" id="verifyOtpBtn">Verify OTP</button>
          </div>
        </form>

        <div id="msg" class="feedback-box"></div>

        <div class="auth-links">
          <button type="button" id="resendOtpBtn">Resend OTP</button>
          <a href="forgot-password.php">Back to forgot password</a>
          <a href="login.php">Back to login</a>
        </div>
      </div>

    </section>
  </main>

<script>
const otpForm = document.getElementById("otpForm");
const msgBox = document.getElementById("msg");
const verifyOtpBtn = document.getElementById("verifyOtpBtn");
const resendOtpBtn = document.getElementById("resendOtpBtn");

otpForm.addEventListener("submit", async function(e) {
  e.preventDefault();

  const otp = document.getElementById("otp").value.trim();

  msgBox.className = "feedback-box";
  msgBox.textContent = "";

  if (!otp) {
    msgBox.className = "feedback-box error";
    msgBox.textContent = "Please enter your 6-digit OTP.";
    return;
  }

  verifyOtpBtn.disabled = true;
  verifyOtpBtn.textContent = "Verifying...";

  try {
    const form = new FormData(otpForm);

    const res = await fetch("ajax/verify-reset-otp.php", {
      method: "POST",
      body: form
    });

    const data = await res.json();

    if (data.status === "success") {
      msgBox.className = "feedback-box success";
      msgBox.textContent = data.message || "OTP verified successfully.";

      setTimeout(() => {
        window.location = "reset-password.php";
      }, 700);
    } else {
      msgBox.className = "feedback-box error";
      msgBox.textContent = data.message || "Unable to verify OTP.";
    }
  } catch (err) {
    msgBox.className = "feedback-box error";
    msgBox.textContent = "A network error occurred. Please try again.";
  } finally {
    verifyOtpBtn.disabled = false;
    verifyOtpBtn.textContent = "Verify OTP";
  }
});

resendOtpBtn.addEventListener("click", async function() {
  msgBox.className = "feedback-box";
  msgBox.textContent = "";

  resendOtpBtn.disabled = true;
  resendOtpBtn.textContent = "Sending...";

  try {
    const form = new FormData();
    form.append("email", <?php echo json_encode($resetEmail); ?>);

    const res = await fetch("ajax/send-reset-otp.php", {
      method: "POST",
      body: form
    });

    const data = await res.json();

    if (data.status === "success") {
      msgBox.className = "feedback-box success";
      msgBox.textContent = data.message || "OTP sent successfully.";
    } else {
      msgBox.className = "feedback-box error";
      msgBox.textContent = data.message || "Unable to resend OTP.";
    }
  } catch (err) {
    msgBox.className = "feedback-box error";
    msgBox.textContent = "A network error occurred. Please try again.";
  } finally {
    resendOtpBtn.disabled = false;
    resendOtpBtn.textContent = "Resend OTP";
  }
});
</script>
</body>
</html>