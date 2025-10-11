<?php
/****************************
 * register.php (single file)
 * - Self-posting form (HTML/CSS/JS below)
 * - Secure insert with prepared statements
 * - Server-side validation (required fields, email match, password match, 13+ age)
 ****************************/

// ---- CONFIG: DB connection ----
$DB_HOST = "127.0.0.1";
$DB_USER = "trade_user";
$DB_PASS = "trade_user";
$DB_NAME = "trade";
$DB_PORT = 3307;

// ---- STATE ----
$errors = [];
$success = false;

// Basic helper
function h($str) { return htmlspecialchars($str ?? "", ENT_QUOTES, "UTF-8"); }

// Validate 13+
function isAtLeast13($dobStr) {
    if (!$dobStr) return false;
    $dob = DateTime::createFromFormat('Y-m-d', $dobStr);
    if (!$dob) return false;
    $today = new DateTime('today');
    $min = (new DateTime('today'))->modify('-13 years');
    return $dob <= $min && $dob <= $today;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName        = trim($_POST['fullName'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $confirmEmail    = trim($_POST['confirmEmail'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $dob             = trim($_POST['dob'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $tos             = isset($_POST['tos']) ? $_POST['tos'] : '';

    // Server-side validations
    if (strlen($fullName) < 2)            { $errors['fullName'] = "Please enter your full name (min 2 characters)."; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = "Enter a valid email."; }
    if (strcasecmp($email, $confirmEmail) !== 0)    { $errors['confirmEmail'] = "Emails do not match."; }
    if (strlen($password) < 8)            { $errors['password'] = "Password must be at least 8 characters."; }
    if ($password !== $confirmPassword)   { $errors['confirmPassword'] = "Passwords do not match."; }
    if (!isAtLeast13($dob))               { $errors['dob'] = "You must be at least 13 years old."; }
    if ($address === '')                  { $errors['address'] = "Please provide your address."; }
    if ($tos !== 'on')                    { $errors['tos'] = "You must agree to the terms."; }

    // If valid, insert
    if (!$errors) {
        $conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
        if (!$conn) {
            $errors['db'] = "Database connection failed: " . mysqli_connect_error();
        } else {
            mysqli_set_charset($conn, 'utf8mb4');

            // Optional: check duplicate email (comment out if not needed)
            $dup = mysqli_prepare($conn, "SELECT 1 FROM pending_users WHERE user_email = ? LIMIT 1");
            if ($dup) {
                mysqli_stmt_bind_param($dup, "s", $email);
                mysqli_stmt_execute($dup);
                mysqli_stmt_store_result($dup);
                if (mysqli_stmt_num_rows($dup) > 0) {
                    $errors['email'] = "This email is already pending registration.";
                }
                mysqli_stmt_close($dup);
            }

            if (!$errors) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO pending_users (user_name, user_email, dob, address, password) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sssss", $fullName, $email, $dob, $address, $hashed);
                    if (mysqli_stmt_execute($stmt)) {
                        $success = true;
                        // Clear POST values so form resets visually
                        $_POST = [];
                    } else {
                        $errors['db'] = "Insert failed: " . h(mysqli_error($conn));
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errors['db'] = "Prepare failed: " . h(mysqli_error($conn));
                }
            }

            mysqli_close($conn);
        }
    }
}

// For DOB max attribute
$todayStr = (new DateTime('today'))->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Sign Up</title>
<style>
  :root {
    --bg: #0f172a; --panel: #111827; --muted: #94a3b8; --text: #e5e7eb;
    --accent: #22c55e; --warn: #ef4444; --focus: #60a5fa;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial;
    background: radial-gradient(1200px 800px at 10% 0%, #172554 0%, #0b1220 60%, var(--bg) 100%);
    color: var(--text); min-height: 100vh; display: grid; place-items: center; padding: 24px;
  }
  .card {
    width: 100%; max-width: 720px; background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
    border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 28px 24px; backdrop-filter: blur(8px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
  }
  h1 { margin: 0 0 6px; font-size: 28px; letter-spacing: 0.2px; }
  p.sub { margin: 0 0 20px; color: var(--muted); }
  form { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 14px; }
  .full { grid-column: 1/-1; }
  label { display: block; font-size: 14px; margin-bottom: 6px; color: #cbd5e1; }
  input, textarea, button { font: inherit; color: var(--text); }
  input[type="text"], input[type="email"], input[type="password"], input[type="date"], textarea {
    width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.14);
    background: rgba(2,6,23,0.6); outline: none; transition: border 150ms, box-shadow 150ms;
  }
  input:focus, textarea:focus { border-color: var(--focus); box-shadow: 0 0 0 3px rgba(96,165,250,0.25); }
  .error { font-size: 12px; color: var(--warn); margin-top: 6px; }
  .ok { font-size: 12px; color: var(--accent); margin-top: 6px; }
  .pw-toggle {
    position: absolute; right: 10px; top: 36px; background: none; border: none; cursor: pointer; color: var(--muted);
  }
  .field { position: relative; }
  .strength { height: 8px; border-radius: 6px; background: rgba(255,255,255,0.08); overflow: hidden; margin-top: 8px; }
  .strength > div { height: 100%; width: 0%; background: linear-gradient(90deg, #f97316, #22c55e); transition: width 200ms; }
  .actions { grid-column: 1/-1; display: flex; gap: 12px; align-items: center; justify-content: space-between; margin-top: 4px; }
  .btn {
    padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.14);
    background: rgba(255,255,255,0.06); cursor: pointer; transition: transform 80ms ease, background 150ms;
  }
  .btn.primary { background: linear-gradient(180deg, rgba(34,197,94,0.9), rgba(21,128,61,0.9)); border-color: rgba(34,197,94,0.5); }
  .btn:disabled { opacity: 0.6; cursor: not-allowed; }
  .btn:not(:disabled):active { transform: translateY(1px); }
  .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; }
  .alert.success { background:#052e1b; border:1px solid #14532d; color:#bbf7d0; }
  .alert.error { background:#3b0a0a; border:1px solid #7f1d1d; color:#fecaca; }
  @media (max-width: 640px) { form { grid-template-columns: 1fr; } }
</style>
</head>
<body>
  <div class="card" role="region" aria-labelledby="title">
    <h1 id="title">Create your account</h1>
    <p class="sub">Please fill out the form. Fields with * are required.</p>

    <?php if ($success): ?>
      <div class="alert success">✅ Registration saved! Check your email inbox for next steps (demo).</div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert error">
        <strong>There were some issues:</strong>
        <ul style="margin:8px 0 0 16px;">
          <?php foreach($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="signupForm" method="POST" novalidate>
      <!-- Full Name -->
      <div class="field full">
        <label for="fullName">Full name *</label>
        <input id="fullName" name="fullName" type="text" autocomplete="name" placeholder="e.g., Jane Doe" required minlength="2" value="<?= h($_POST['fullName'] ?? '') ?>"/>
        <?php if(isset($errors['fullName'])): ?><div class="error"><?= h($errors['fullName']) ?></div><?php endif; ?>
      </div>

      <!-- Email -->
      <div class="field">
        <label for="email">Email *</label>
        <input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" required value="<?= h($_POST['email'] ?? '') ?>"/>
        <?php if(isset($errors['email'])): ?><div class="error"><?= h($errors['email']) ?></div><?php endif; ?>
      </div>

      <!-- Confirm Email -->
      <div class="field">
        <label for="confirmEmail">Confirm email *</label>
        <input id="confirmEmail" name="confirmEmail" type="email" autocomplete="off" placeholder="Retype email" required value="<?= h($_POST['confirmEmail'] ?? '') ?>"/>
        <div class="ok" id="confirmEmailOk" style="display:none;">Emails match ✓</div>
        <?php if(isset($errors['confirmEmail'])): ?><div class="error"><?= h($errors['confirmEmail']) ?></div><?php endif; ?>
      </div>

      <!-- Password -->
      <div class="field">
        <label for="password">Password *</label>
        <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Choose a strong password" required minlength="8" />
        <button class="pw-toggle" type="button" aria-label="Toggle password visibility" data-target="password">👁️</button>
        <div class="strength"><div id="strengthBar"></div></div>
        <?php if(isset($errors['password'])): ?><div class="error"><?= h($errors['password']) ?></div><?php endif; ?>
      </div>

      <!-- Confirm Password -->
      <div class="field">
        <label for="confirmPassword">Confirm password *</label>
        <input id="confirmPassword" name="confirmPassword" type="password" autocomplete="new-password" placeholder="Retype password" required />
        <button class="pw-toggle" type="button" aria-label="Toggle password visibility" data-target="confirmPassword">👁️</button>
        <div class="ok" id="confirmPasswordOk" style="display:none;">Passwords match ✓</div>
        <?php if(isset($errors['confirmPassword'])): ?><div class="error"><?= h($errors['confirmPassword']) ?></div><?php endif; ?>
      </div>

      <!-- DOB -->
      <div class="field">
        <label for="dob">Date of birth *</label>
        <input id="dob" name="dob" type="date" required max="<?= h($todayStr) ?>" value="<?= h($_POST['dob'] ?? '') ?>"/>
        <?php if(isset($errors['dob'])): ?><div class="error"><?= h($errors['dob']) ?></div><?php endif; ?>
      </div>

      <!-- Address -->
      <div class="field full">
        <label for="address">Address *</label>
        <textarea id="address" name="address" rows="3" placeholder="Street, City, State/Province, ZIP/Postal" required><?= h($_POST['address'] ?? '') ?></textarea>
        <?php if(isset($errors['address'])): ?><div class="error"><?= h($errors['address']) ?></div><?php endif; ?>
      </div>

      <div class="actions">
        <label><input type="checkbox" id="tos" name="tos" <?= isset($_POST['tos']) ? 'checked' : '' ?> /> <span> I agree to the terms *</span></label>
        <div>
          <button type="reset" class="btn" id="resetBtn">Reset</button>
          <button type="submit" class="btn primary" id="submitBtn">Sign up</button>
        </div>
      </div>
    </form>
  </div>

<script>
  // Client-side helpers
  const form = document.getElementById('signupForm');
  const fullName = document.getElementById('fullName');
  const email = document.getElementById('email');
  const confirmEmail = document.getElementById('confirmEmail');
  const password = document.getElementById('password');
  const confirmPassword = document.getElementById('confirmPassword');
  const dob = document.getElementById('dob');
  const address = document.getElementById('address');
  const strengthBar = document.getElementById('strengthBar');
  const emailOk = document.getElementById('confirmEmailOk');
  const pwOk = document.getElementById('confirmPasswordOk');

  function emailsMatch() {
    return email.value.trim().toLowerCase() !== '' &&
           email.value.trim().toLowerCase() === confirmEmail.value.trim().toLowerCase();
  }
  function passwordsMatch() {
    return password.value !== '' && password.value === confirmPassword.value;
  }
  function passwordStrength(pw) {
    let s = 0;
    if (pw.length >= 8) s++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) s++;
    if (/\d/.test(pw)) s++;
    if (/[^\w\s]/.test(pw)) s++;
    return s; // 0–4
  }

  email.addEventListener('input', () => {
    if (emailsMatch()) { emailOk.style.display = 'block'; } else { emailOk.style.display = 'none'; }
  });
  confirmEmail.addEventListener('input', () => {
    if (emailsMatch()) { emailOk.style.display = 'block'; } else { emailOk.style.display = 'none'; }
  });

  password.addEventListener('input', () => {
    const s = passwordStrength(password.value);
    strengthBar.style.width = (s * 25) + '%';
    if (passwordsMatch()) { pwOk.style.display = 'block'; } else { pwOk.style.display = 'none'; }
  });
  confirmPassword.addEventListener('input', () => {
    if (passwordsMatch()) { pwOk.style.display = 'block'; } else { pwOk.style.display = 'none'; }
  });

  // Reset visuals on reset
  document.getElementById('resetBtn').addEventListener('click', () => {
    emailOk.style.display = 'none';
    pwOk.style.display = 'none';
    strengthBar.style.width = '0%';
  });

  // Optional: client-side gate (server side already validates)
  form.addEventListener('submit', (e) => {
    // Let the server handle final validation; client just provides hints
    if (!emailsMatch()) { alert('Emails do not match.'); e.preventDefault(); return; }
    if (!passwordsMatch()) { alert('Passwords do not match.'); e.preventDefault(); return; }
  });
</script>
</body>
</html>
