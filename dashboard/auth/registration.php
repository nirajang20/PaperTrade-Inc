<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . "/../lib/Core.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/
$turnstileSiteKey   = "1x00000000000000000000AA";
$turnstileSecretKey = "1x0000000000000000000000000000000AA";
$otpEndpoint        = "https://send-email.paper-trade.org/send_otp.php";
$otpPurpose         = "register";
$otpExpiryMinutes   = 10;
$defaultBalance     = 100000;
$defaultRole        = "user";
$defaultStatus      = 1;

$allowedAvatars = array(
    "avatar_01.png",
    "avatar_02.png",
    "avatar_03.png",
    "avatar_04.png"
);
$defaultAvatar = "avatar_01.png";

$errors  = array();
$success = "";

if (!isset($_SESSION['registration_step'])) {
    $_SESSION['registration_step'] = 1;
}
$currentStep = (int) $_SESSION['registration_step'];

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function getClientIp(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return substr($_SERVER['HTTP_CF_CONNECTING_IP'], 0, 45);
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return substr(trim($parts[0]), 0, 45);
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return substr($_SERVER['REMOTE_ADDR'], 0, 45);
    }
    return '0.0.0.0';
}

function registrationValues(string $defaultAvatar): array {
    if (isset($_SESSION['pending_registration']) && is_array($_SESSION['pending_registration'])) {
        return $_SESSION['pending_registration'];
    }

    return array(
        'fullName' => '',
        'email'    => '',
        'phone'    => '',
        'dob'      => '',
        'address'  => '',
        'avatar'   => $defaultAvatar
    );
}

function sendPostRequest(string $url, array $params): array {
    if (!function_exists('curl_init')) {
        return array(
            'ok'   => false,
            'http' => 0,
            'body' => '',
            'err'  => 'cURL is not enabled on this server.'
        );
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array(
        'ok'   => ($err === '' && $http >= 200 && $http < 300),
        'http' => $http,
        'body' => $body ?: '',
        'err'  => $err
    );
}

function verifyTurnstileToken(string $secret, string $token, string $ip): bool {
    if ($token === '' || !function_exists('curl_init')) {
        return false;
    }

    $ch = curl_init("https://challenges.cloudflare.com/turnstile/v0/siteverify");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $ip
    )));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $body = curl_exec($ch);
    curl_close($ch);

    if (!$body) {
        return false;
    }

    $json = json_decode($body, true);
    return !empty($json['success']);
}

function normalizeAvatar(string $selectedAvatar, array $allowedAvatars, string $defaultAvatar): string {
    $selectedAvatar = trim($selectedAvatar);
    if ($selectedAvatar === '' || !in_array($selectedAvatar, $allowedAvatars, true)) {
        return $defaultAvatar;
    }
    return $selectedAvatar;
}

/*
|--------------------------------------------------------------------------
| STAGE 1: DETAILS + SEND OTP
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $fullName        = trim($_POST['fullName'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phoneRaw        = trim($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $dob             = trim($_POST['dob'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $selectedAvatar  = normalizeAvatar($_POST['avatar'] ?? '', $allowedAvatars, $defaultAvatar);

    $phoneDigits = preg_replace('/\D+/', '', $phoneRaw);
    $ipAddress   = getClientIp();

    if ($fullName === '' || strlen($fullName) < 3) {
        $errors[] = "Please enter a valid full name.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (preg_match('/[\r\n\t]/', $email)) {
        $errors[] = "Invalid email address.";
    }

    if ($phoneDigits === '' || strlen($phoneDigits) < 7 || strlen($phoneDigits) > 15) {
        $errors[] = "Please enter a valid phone number.";
    }

    if ($password === '' || strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Password and confirm password do not match.";
    }

    if ($dob === '') {
        $errors[] = "Please select your date of birth.";
    }

    if ($address === '' || strlen($address) < 5) {
        $errors[] = "Please enter your address.";
    }

    if (empty($errors)) {
        $checkUser = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_email = ? LIMIT 1");
        if ($checkUser) {
            mysqli_stmt_bind_param($checkUser, "s", $email);
            mysqli_stmt_execute($checkUser);
            mysqli_stmt_store_result($checkUser);

            if (mysqli_stmt_num_rows($checkUser) > 0) {
                $errors[] = "An account with this email already exists.";
            }
            mysqli_stmt_close($checkUser);
        } else {
            $errors[] = "Database error while checking existing user.";
        }
    }

    if (empty($errors)) {
        $deleteOld = mysqli_prepare($conn, "DELETE FROM otp_tokens WHERE email = ? AND purpose = ? AND is_used = 0");
        if ($deleteOld) {
            mysqli_stmt_bind_param($deleteOld, "ss", $email, $otpPurpose);
            mysqli_stmt_execute($deleteOld);
            mysqli_stmt_close($deleteOld);
        }

        $otp      = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash  = hash('sha256', $otp);
        $expires  = date('Y-m-d H:i:s', strtotime("+" . $otpExpiryMinutes . " minutes"));

        $insertOtp = mysqli_prepare(
            $conn,
            "INSERT INTO otp_tokens (email, otp_hash, purpose, ip_address, is_used, attempts, expires_at)
             VALUES (?, ?, ?, ?, 0, 0, ?)"
        );

        if ($insertOtp) {
            mysqli_stmt_bind_param($insertOtp, "sssss", $email, $otpHash, $otpPurpose, $ipAddress, $expires);

            if (!mysqli_stmt_execute($insertOtp)) {
                $errors[] = "Could not create OTP record. Please try again.";
            }
            mysqli_stmt_close($insertOtp);
        } else {
            $errors[] = "Database error while saving OTP.";
        }

        if (empty($errors)) {
            $sendResult = sendPostRequest($otpEndpoint, array(
                'toemail' => $email,
                'otp'     => $otp
            ));

            $responseJson = json_decode($sendResult['body'], true);

            if (!$sendResult['ok'] || empty($responseJson['status']) || $responseJson['status'] !== 'success') {
                $apiMessage = isset($responseJson['message']) ? $responseJson['message'] : 'Could not send OTP email right now.';
                $errors[] = "OTP send failed: " . $apiMessage;
            } else {
                $_SESSION['pending_registration'] = array(
                    'fullName'      => $fullName,
                    'email'         => $email,
                    'phone'         => $phoneDigits,
                    'dob'           => $dob,
                    'address'       => $address,
                    'avatar'        => $selectedAvatar,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT)
                );

                $_SESSION['registration_step'] = 2;
                $currentStep = 2;
                $success = "OTP sent successfully. Please check your email.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| STAGE 2: VERIFY OTP
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $pending  = isset($_SESSION['pending_registration']) ? $_SESSION['pending_registration'] : null;
    $otpInput = trim($_POST['otp'] ?? '');

    if (!$pending) {
        $errors[] = "Registration session expired. Please start again.";
        $_SESSION['registration_step'] = 1;
        $currentStep = 1;
    } elseif (!preg_match('/^\d{6}$/', $otpInput)) {
        $errors[] = "Please enter a valid 6-digit OTP.";
        $currentStep = 2;
    } else {
        $email = $pending['email'];

        $query = mysqli_prepare(
            $conn,
            "SELECT id, otp_hash, attempts, expires_at
             FROM otp_tokens
             WHERE email = ? AND purpose = ? AND is_used = 0
             ORDER BY id DESC
             LIMIT 1"
        );

        if ($query) {
            mysqli_stmt_bind_param($query, "ss", $email, $otpPurpose);
            mysqli_stmt_execute($query);
            $result = mysqli_stmt_get_result($query);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($query);

            if (!$row) {
                $errors[] = "No active OTP found. Please request a new OTP.";
                $_SESSION['registration_step'] = 1;
                $currentStep = 1;
            } elseif ((int)$row['attempts'] >= 5) {
                $errors[] = "This OTP has been blocked after too many attempts. Please request a new OTP.";
                $_SESSION['registration_step'] = 1;
                $currentStep = 1;
            } elseif (strtotime($row['expires_at']) < time()) {
                $errors[] = "This OTP has expired. Please request a new OTP.";
                $_SESSION['registration_step'] = 1;
                $currentStep = 1;
            } else {
                $inputHash = hash('sha256', $otpInput);

                if (!hash_equals($row['otp_hash'], $inputHash)) {
                    $increase = mysqli_prepare($conn, "UPDATE otp_tokens SET attempts = attempts + 1 WHERE id = ?");
                    if ($increase) {
                        $otpId = (int)$row['id'];
                        mysqli_stmt_bind_param($increase, "i", $otpId);
                        mysqli_stmt_execute($increase);
                        mysqli_stmt_close($increase);
                    }

                    $errors[] = "Incorrect OTP. Please try again.";
                    $currentStep = 2;
                } else {
                    $markUsed = mysqli_prepare($conn, "UPDATE otp_tokens SET is_used = 1, used_at = NOW() WHERE id = ?");
                    if ($markUsed) {
                        $otpId = (int)$row['id'];
                        mysqli_stmt_bind_param($markUsed, "i", $otpId);
                        mysqli_stmt_execute($markUsed);
                        mysqli_stmt_close($markUsed);
                    }

                    $_SESSION['otp_verified'] = true;
                    $_SESSION['registration_step'] = 3;
                    $currentStep = 3;
                    $success = "OTP verified. Complete the security check below.";
                }
            }
        } else {
            $errors[] = "Database error while verifying OTP.";
            $currentStep = 2;
        }
    }
}

/*
|--------------------------------------------------------------------------
| STAGE 3: TURNSTILE + CREATE USER
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_registration'])) {
    $pending        = isset($_SESSION['pending_registration']) ? $_SESSION['pending_registration'] : null;
    $otpVerified    = !empty($_SESSION['otp_verified']);
    $turnstileToken = trim($_POST['cf-turnstile-response'] ?? '');

    if (!$pending || !$otpVerified) {
        $errors[] = "Your registration session is incomplete. Please start again.";
        $_SESSION['registration_step'] = 1;
        $currentStep = 1;
    } else {
        $ipAddress = getClientIp();

        if (!verifyTurnstileToken($turnstileSecretKey, $turnstileToken, $ipAddress)) {
            $errors[] = "Turnstile verification failed. Please try again.";
            $currentStep = 3;
        } else {
            $email = $pending['email'];

            $dup = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_email = ? LIMIT 1");
            if ($dup) {
                mysqli_stmt_bind_param($dup, "s", $email);
                mysqli_stmt_execute($dup);
                mysqli_stmt_store_result($dup);
                $alreadyExists = mysqli_stmt_num_rows($dup) > 0;
                mysqli_stmt_close($dup);

                if ($alreadyExists) {
                    $errors[] = "This email has already been registered.";
                    $_SESSION['registration_step'] = 1;
                    $currentStep = 1;
                } else {
                    $fullName       = $pending['fullName'];
                    $phoneValue     = $pending['phone'];
                    $dob            = $pending['dob'];
                    $address        = $pending['address'];
                    $selectedAvatar = !empty($pending['avatar']) ? $pending['avatar'] : $defaultAvatar;
                    $passwordHash   = $pending['password_hash'];

                    $insert = mysqli_prepare(
                        $conn,
                        "INSERT INTO users
                        (user_email, dob, user_phone, address, user_name, user_password, avatar, user_balance, role, last_login, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)"
                    );

                    if ($insert) {
                        $balance = $defaultBalance;
                        $status  = $defaultStatus;

                        mysqli_stmt_bind_param(
                            $insert,
                            "sssssssisi",
                            $email,
                            $dob,
                            $phoneValue,
                            $address,
                            $fullName,
                            $passwordHash,
                            $selectedAvatar,
                            $balance,
                            $defaultRole,
                            $status
                        );

                        if (mysqli_stmt_execute($insert)) {
                            $newUserId = mysqli_insert_id($conn);
                            mysqli_stmt_close($insert);

                            $_SESSION["user"] = array(
                                "user_id"    => $newUserId,
                                "user_email" => $email,
                                "user_name"  => $fullName
                            );
                            $_SESSION["active_email"] = $email;
                            $_SESSION["welcome_back"] = true;

                            unset($_SESSION['pending_registration']);
                            unset($_SESSION['registration_step']);
                            unset($_SESSION['otp_verified']);

                            header("Location: ../index.php");
                            exit;
                        } else {
                            $errors[] = "Could not create account. Please try again.";
                            mysqli_stmt_close($insert);
                            $currentStep = 3;
                        }
                    } else {
                        $errors[] = "Database error while creating user account.";
                        $currentStep = 3;
                    }
                }
            } else {
                $errors[] = "Database error while checking duplicate email.";
                $currentStep = 3;
            }
        }
    }
}

$values = registrationValues($defaultAvatar);
$currentStep = isset($_SESSION['registration_step']) ? (int)$_SESSION['registration_step'] : $currentStep;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Paper Trade | Registration</title>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<style>
:root {
  --signup-primary: #6c5ce7;
  --signup-accent: #00d4ff;
  --signup-surface: rgba(15, 23, 42, 0.86);
  --signup-surface-soft: rgba(15, 23, 42, 0.62);
  --signup-border: rgba(148, 163, 184, 0.28);
  --signup-text-strong: #f8fafc;
  --signup-text-muted: rgba(248, 250, 252, 0.78);
  --signup-success: #4ade80;
  --signup-error: #f87171;
}

*, *::before, *::after { box-sizing: border-box; }

body {
  margin: 0;
  font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  background:
    radial-gradient(circle at 0% 0%, rgba(108, 92, 231, 0.45), transparent 55%),
    radial-gradient(circle at 100% 0%, rgba(0, 212, 255, 0.35), transparent 60%),
    linear-gradient(135deg, #0f172a 0%, #1e293b 52%, #312e81 100%);
  color: var(--signup-text-strong);
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

main.signup-wrapper {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.25rem;
  min-height: 100vh;
}

.signup-shell {
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

.signup-brand {
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

.signup-brand img {
  width: min(220px, 58%);
  max-width: 220px;
}

.signup-brand h1 {
  font-size: clamp(2rem, 1vw + 1.6rem, 3rem);
  line-height: 1.15;
  margin: 0;
}

.signup-brand p {
  margin: 0;
  color: var(--signup-text-muted);
  line-height: 1.75;
  font-size: 1rem;
}

.signup-feature-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
}

.signup-feature {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px;
  padding: 1rem;
}

.signup-feature strong {
  display: block;
  margin-bottom: 0.3rem;
  font-size: 0.95rem;
}

.signup-feature span {
  color: var(--signup-text-muted);
  font-size: 0.9rem;
  line-height: 1.5;
}

.signup-panel {
  background: var(--signup-surface);
  border-radius: 24px;
  padding: clamp(1.4rem, 2vw, 2.2rem);
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
}

.signup-panel-header {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  margin-bottom: 1.4rem;
}

.signup-panel-header h2 {
  margin: 0;
  font-size: 1.8rem;
}

.signup-panel-header p {
  margin: 0;
  color: var(--signup-text-muted);
  line-height: 1.6;
}

.signup-steps {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}

.step-badge {
  padding: 0.85rem 1rem;
  border-radius: 16px;
  font-size: 0.9rem;
  border: 1px solid var(--signup-border);
  color: var(--signup-text-muted);
  background: rgba(255,255,255,0.04);
  text-align: center;
  font-weight: 600;
}

.step-badge.active {
  background: linear-gradient(135deg, rgba(108, 92, 231, 0.26), rgba(0, 212, 255, 0.14));
  border-color: rgba(108, 92, 231, 0.55);
  color: var(--signup-text-strong);
  box-shadow: 0 10px 24px rgba(108, 92, 231, 0.16);
}

.alert {
  padding: 1rem 1rem;
  border-radius: 14px;
  font-size: 0.95rem;
  margin-bottom: 1rem;
}

.alert.success {
  background: rgba(74, 222, 128, 0.1);
  border: 1px solid var(--signup-success);
}

.alert.error {
  background: rgba(248, 113, 113, 0.1);
  border: 1px solid var(--signup-error);
}

.alert ul {
  margin: 0.45rem 0 0 1.2rem;
  padding: 0;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.form-grid .span-2 {
  grid-column: span 2;
}

.field {
  margin-bottom: 1rem;
}

.field label {
  display: block;
  margin-bottom: 0.42rem;
  font-size: 0.92rem;
  color: var(--signup-text-muted);
  font-weight: 600;
}

.field-control {
  width: 100%;
  border-radius: 14px;
  border: 1px solid var(--signup-border);
  background: rgba(255, 255, 255, 0.045);
  color: var(--signup-text-strong);
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

.field-control[readonly] {
  opacity: 0.86;
}

textarea.field-control {
  resize: vertical;
  min-height: 64px;
  max-height: 120px;
}

.avatar-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(70px, 1fr));
  gap: 0.9rem;
  margin-top: 0.4rem;
}

.avatar-choice {
  position: relative;
  display: block;
  cursor: pointer;
}

.avatar-choice input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.avatar-choice img {
  width: 100%;
  max-width: 92px;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  border-radius: 18px;
  border: 3px solid transparent;
  background: rgba(255,255,255,0.04);
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.avatar-choice:hover img {
  transform: translateY(-2px);
  border-color: rgba(255,255,255,0.24);
}

.avatar-choice input:checked + img {
  border-color: rgba(108, 92, 231, 0.9);
  box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.18);
  transform: translateY(-2px);
}

.btn-row {
  display: flex;
  gap: 0.9rem;
  justify-content: space-between;
  flex-wrap: wrap;
  margin-top: 1rem;
}

.btn {
  padding: 0.92rem 1.5rem;
  border: none;
  border-radius: 14px;
  font-weight: 700;
  cursor: pointer;
  font-size: 0.96rem;
  transition: transform 0.18s ease, opacity 0.18s ease, box-shadow 0.18s ease;
}

.btn.primary {
  background: linear-gradient(135deg, var(--signup-primary), var(--signup-accent));
  color: #fff;
  box-shadow: 0 14px 28px rgba(108, 92, 231, 0.28);
}

.btn.ghost {
  background: rgba(255,255,255,0.08);
  color: var(--signup-text-strong);
  border: 1px solid rgba(255,255,255,0.12);
}

.btn:hover {
  opacity: 0.96;
  transform: translateY(-1px);
}

.small-note {
  color: var(--signup-text-muted);
  font-size: 0.9rem;
  line-height: 1.65;
  margin-top: 0.4rem;
}

.otp-panel {
  max-width: 480px;
}

.otp-code {
  text-align: center;
  letter-spacing: 0.35rem;
  font-size: 1.25rem;
  font-weight: 700;
}

.turnstile-box {
  padding: 1rem;
  border-radius: 16px;
  border: 1px dashed rgba(255,255,255,0.18);
  background: rgba(255,255,255,0.035);
}

@media (max-width: 1100px) {
  .signup-shell {
    grid-template-columns: 1fr;
  }

  .signup-brand {
    order: 2;
  }

  .signup-panel {
    order: 1;
  }
}

@media (max-width: 720px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-grid .span-2 {
    grid-column: span 1;
  }

  .signup-feature-grid {
    grid-template-columns: 1fr;
  }

  .signup-steps {
    grid-template-columns: 1fr;
  }

  .avatar-grid {
    grid-template-columns: repeat(2, minmax(90px, 1fr));
  }
}
</style>
</head>
<body>
  <main class="signup-wrapper">
    <section class="signup-shell">

      <div class="signup-brand">
        <div>
          <img src="../../img/logo-horizontal.png" alt="Paper Trade Logo">
          <h1>Build your simulated trading desk with confidence</h1>
          <p>
            Register securely, verify your email with a one-time password, choose your starting identity,
            and complete a final security check before getting access to your PaperTrade dashboard.
          </p>
        </div>

        <div class="signup-feature-grid">
          <div class="signup-feature">
            <strong>Secure onboarding</strong>
            <span>Email OTP verification and bot protection before account creation.</span>
          </div>
          <div class="signup-feature">
            <strong>Fresh simulation funds</strong>
            <span>Every new account starts with a default virtual balance of $100,000.</span>
          </div>
          <div class="signup-feature">
            <strong>Personalized profile</strong>
            <span>Select a starter avatar during signup and update it later from your profile.</span>
          </div>
          <div class="signup-feature">
            <strong>Fast account flow</strong>
            <span>Three simple steps from registration to dashboard access.</span>
          </div>
        </div>
      </div>

      <div class="signup-panel">
        <div class="signup-panel-header">
          <h2>Request Platform Access</h2>
          <p>Complete the steps below to create your PaperTrade simulation account.</p>

          <div class="signup-steps">
            <span class="step-badge <?php echo $currentStep === 1 ? 'active' : ''; ?>">1. Details</span>
            <span class="step-badge <?php echo $currentStep === 2 ? 'active' : ''; ?>">2. Verify OTP</span>
            <span class="step-badge <?php echo $currentStep === 3 ? 'active' : ''; ?>">3. Security Check</span>
          </div>
        </div>

        <?php if ($success !== ''): ?>
          <div class="alert success"><?php echo h($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert error">
            <strong>Please fix the following:</strong>
            <ul>
              <?php foreach ($errors as $error): ?>
                <li><?php echo h($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($currentStep === 1): ?>
          <form method="POST" action="">
            <div class="form-grid">
              <div class="field">
                <label for="fullName">Full Name *</label>
                <input class="field-control" type="text" id="fullName" name="fullName" value="<?php echo h($values['fullName']); ?>" required>
              </div>

              <div class="field">
                <label for="email">Email *</label>
                <input class="field-control" type="email" id="email" name="email" value="<?php echo h($values['email']); ?>" required>
              </div>

              <div class="field">
                <label for="phone">Phone *</label>
                <input class="field-control" type="text" id="phone" name="phone" value="<?php echo h($values['phone']); ?>" required>
              </div>

              <div class="field">
                <label for="dob">Date of Birth *</label>
                <input class="field-control" type="date" id="dob" name="dob" value="<?php echo h($values['dob']); ?>" required>
              </div>

              <div class="field">
                <label for="password">Password *</label>
                <input class="field-control" type="password" id="password" name="password" required>
              </div>

              <div class="field">
                <label for="confirmPassword">Confirm Password *</label>
                <input class="field-control" type="password" id="confirmPassword" name="confirmPassword" required>
              </div>

              <div class="field span-2">
                <label for="address">Address *</label>
                <textarea class="field-control" id="address" name="address" rows="2" placeholder="Street, City, State" required><?php echo h($values['address']); ?></textarea>
              </div>

              <div class="field span-2">
                <label>Choose Avatar</label>
                <div class="avatar-grid">
                  <?php foreach ($allowedAvatars as $avatarOption): ?>
                    <label class="avatar-choice">
                      <input
                        type="radio"
                        name="avatar"
                        value="<?php echo h($avatarOption); ?>"
                        <?php echo (($values['avatar'] ?? $defaultAvatar) === $avatarOption) ? 'checked' : ''; ?>
                      >
                      <img src="../../img/avatars/<?php echo h($avatarOption); ?>" alt="Avatar option">
                    </label>
                  <?php endforeach; ?>
                </div>
                <p class="small-note">If you do not choose one, the default avatar will be <strong><?php echo h($defaultAvatar); ?></strong>.</p>
              </div>
            </div>

            <div class="btn-row">
              <button type="reset" class="btn ghost">Reset</button>
              <button type="submit" name="send_otp" class="btn primary">Send OTP</button>
            </div>
          </form>

        <?php elseif ($currentStep === 2): ?>
          <div class="otp-panel">
            <form method="POST" action="">
              <div class="field">
                <label>Email</label>
                <input class="field-control" type="text" value="<?php echo h($values['email']); ?>" readonly>
              </div>

              <div class="field">
                <label for="otp">Enter 6-digit OTP *</label>
                <input class="field-control otp-code" type="text" id="otp" name="otp" maxlength="6" inputmode="numeric" required>
              </div>

              <p class="small-note">
                We sent a one-time password to your email address. Enter it below to continue.
              </p>

              <div class="btn-row">
                <button type="submit" name="verify_otp" class="btn primary">Verify OTP</button>
              </div>
            </form>
          </div>

        <?php else: ?>
          <form method="POST" action="">
            <div class="field">
              <label>Email</label>
              <input class="field-control" type="text" value="<?php echo h($values['email']); ?>" readonly>
            </div>

            <div class="field">
              <label>Selected Avatar</label>
              <div class="avatar-grid" style="grid-template-columns: repeat(1, 100px);">
                <label class="avatar-choice">
                  <input type="radio" checked disabled>
                  <img src="../../img/avatars/<?php echo h($values['avatar'] ?? $defaultAvatar); ?>" alt="Selected avatar">
                </label>
              </div>
            </div>

            <div class="field">
              <label>Security Verification *</label>
              <div class="turnstile-box">
                <div class="cf-turnstile" data-sitekey="<?php echo h($turnstileSiteKey); ?>"></div>
              </div>
            </div>

            <p class="small-note">
              Complete the security check to finish creating your account.
            </p>

            <div class="btn-row">
              <button type="submit" name="complete_registration" class="btn primary">Create Account</button>
            </div>
          </form>
        <?php endif; ?>
      </div>

    </section>
  </main>
</body>
</html>