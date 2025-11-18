<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Paper Trade | Registration</title>
<style>
:root {
  --signup-primary: #6c5ce7;
  --signup-accent: #00d4ff;
  --signup-surface: rgba(15, 23, 42, 0.85);
  --signup-border: rgba(148, 163, 184, 0.35);
  --signup-text-strong: #f8fafc;
  --signup-text-muted: rgba(248, 250, 252, 0.78);
  --signup-success: #4ade80;
  --signup-error: #f87171;
}
*, *::before, *::after { box-sizing: border-box; }
body {
  margin: 0;
  font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  background: linear-gradient(135deg, #0f172a, #1e293b 50%, #312e81);
  color: var(--signup-text-strong);
  overflow-y: auto;
}

main.signup-wrapper {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 4rem 1rem;
  min-height: 100vh;
}

.signup-shell {
  width: 100%;
  max-width: 1000px;
  background: var(--signup-surface);
  border-radius: 28px;
  padding: 2rem 2.5rem;
  box-shadow: 0 30px 70px rgba(0, 0, 0, 0.45);
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  overflow-y: auto;
}

.signup-brand img {
  width: 200px;
  margin-bottom: 1rem;
}
.signup-brand h1 {
  font-size: 1.8rem;
  line-height: 1.4;
}
.signup-brand p {
  color: var(--signup-text-muted);
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.signup-panel {
  background: rgba(15, 23, 42, 0.6);
  padding: 1.8rem;
  border-radius: 20px;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.alert {
  padding: 1rem;
  border-radius: 10px;
  font-size: 0.9rem;
  margin-bottom: 1rem;
}
.alert.success { background: rgba(74, 222, 128, 0.1); border: 1px solid var(--signup-success); }
.alert.error { background: rgba(248, 113, 113, 0.1); border: 1px solid var(--signup-error); }

.field {
  margin-bottom: 1.2rem;
}
.field label {
  display: block;
  margin-bottom: 0.35rem;
  font-size: 0.9rem;
  color: var(--signup-text-muted);
}
.field-control {
  width: 100%;
  border-radius: 10px;
  border: 1px solid var(--signup-border);
  background: rgba(255, 255, 255, 0.05);
  color: var(--signup-text-strong);
  padding: 0.9rem;
  font-size: 1rem;
}
.field-control:focus {
  outline: 2px solid var(--signup-primary);
  border-color: var(--signup-primary);
}

.btn {
  padding: 0.8rem 1.8rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
}
.btn.primary {
  background: linear-gradient(135deg, var(--signup-primary), var(--signup-accent));
  color: #fff;
}
.btn.ghost {
  background: rgba(255,255,255,0.1);
  color: var(--signup-text-strong);
  border: 1px solid rgba(255,255,255,0.15);
}
.btn:hover { opacity: 0.9; }

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  flex-wrap: wrap;
}

@media (max-width: 820px) {
  .signup-shell { grid-template-columns: 1fr; }
  .signup-brand { text-align: center; }
}
</style>
</head>
<body>
  <main class="signup-wrapper">
    <section class="signup-shell">
      <div class="signup-brand">
        <img src="dist/img/logo-horizontal.png" alt="Paper Trade Logo">
        <h1>Launch your simulated desk with confidence</h1>
        <p>Request secure access and receive a personalized playbook to start refining your strategies.</p>
      </div>

      <div class="signup-panel">
        <h2>Request Platform Access</h2>
        <form>
          <div class="field">
            <label for="fullName">Full Name *</label>
            <input class="field-control" type="text" id="fullName" name="fullName" required>
          </div>

          <div class="field">
            <label for="email">Email *</label>
            <input class="field-control" type="email" id="email" name="email" required>
          </div>

          <div class="field">
            <label for="password">Password *</label>
            <input class="field-control" type="password" id="password" name="password" required>
          </div>

          <div class="field">
            <label for="dob">Date of Birth *</label>
            <input class="field-control" type="date" id="dob" name="dob" required>
          </div>

          <div class="field">
            <label for="address">Address *</label>
            <textarea class="field-control" id="address" name="address" rows="3" required></textarea>
          </div>

          <div class="form-actions">
            <button type="reset" class="btn ghost">Reset</button>
            <button type="submit" class="btn primary">Create Account</button>
          </div>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
