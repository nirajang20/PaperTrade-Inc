<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";

if (!isset($active_user) || !is_array($active_user) || empty($active_user['user_id'])) {
  echo "<script>
    Swal.fire({
      title: 'Session Error',
      text: 'Unable to load your account profile.',
      icon: 'error'
    }).then(() => {
      window.location.href = '../auth/login.php';
    });
  </script>";
  require "../lib/PAGE-bottom.php";
  exit;
}

$user_id = (int) $active_user['user_id'];

function h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function swalScript($title, $text, $icon = 'info', $thenJs = '') {
  echo "<script>
    Swal.fire({
      title: " . json_encode($title) . ",
      text: " . json_encode($text) . ",
      icon: " . json_encode($icon) . "
    }).then(() => {
      $thenJs
    });
  </script>";
}

$passwordLastChanged = "—";
$passwordLastChangedRaw = null;
foreach (['password_updated_at', 'password_updated', 'password_changed_at', 'last_password_change'] as $field) {
  if (!empty($active_user[$field])) {
    $passwordLastChangedRaw = $active_user[$field];
    break;
  }
}
if ($passwordLastChangedRaw) {
  $timestamp = strtotime($passwordLastChangedRaw);
  $passwordLastChanged = $timestamp ? date("M d, Y g:i A", $timestamp) : $passwordLastChangedRaw;
}

/* ----------------------------------------------------------
   HANDLE PERSONAL INFO UPDATE
---------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['info_update'])) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $dob = trim($_POST['dob'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $updated_at = date("Y-m-d H:i:s");

  $errors = [];

  if ($name === '') {
    $errors[] = "Full name is required.";
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "A valid email address is required.";
  }
  if ($dob === '') {
    $errors[] = "Date of birth is required.";
  }
  if ($address === '') {
    $errors[] = "Address is required.";
  }

  if (empty($errors)) {
    $stmtDup = $conn->prepare("SELECT user_id FROM users WHERE user_email = ? AND user_id != ? LIMIT 1");
    if ($stmtDup) {
      $stmtDup->bind_param("si", $email, $user_id);
      $stmtDup->execute();
      $stmtDup->store_result();
      if ($stmtDup->num_rows > 0) {
        $errors[] = "That email address is already being used by another account.";
      }
      $stmtDup->close();
    } else {
      $errors[] = "Unable to validate the email address.";
    }
  }

  if (!empty($errors)) {
    swalScript("Update Failed", implode("\n", array_unique($errors)), "error", "
      const tab = document.getElementById('personal-tab');
      if (tab) {
        if (typeof $ !== 'undefined' && typeof $.fn.tab === 'function') {
          $(tab).tab('show');
        } else {
          tab.click();
        }
      }
    ");
  } else {
    $sql = "UPDATE users
            SET user_name = ?, user_email = ?, dob = ?, user_phone = ?, address = ?, last_updated = ?
            WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->bind_param("ssssssi", $name, $email, $dob, $phone, $address, $updated_at, $user_id);
      if ($stmt->execute()) {
        swalScript("Success!", "Your information has been updated successfully.", "success", "
          window.location.href = window.location.href;
        ");
      } else {
        swalScript("Database Error", "Unable to update your information.", "error");
      }
      $stmt->close();
    } else {
      swalScript("Database Error", "Unable to prepare the profile update.", "error");
    }
  }
}

/* ----------------------------------------------------------
   HANDLE PASSWORD UPDATE
---------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_update'])) {
  $currentPassword = trim($_POST['currentPassword'] ?? '');
  $newPassword = trim($_POST['newPassword'] ?? '');
  $confirmPassword = trim($_POST['confirmPassword'] ?? '');

  $errors = [];

  if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    $errors[] = "Please complete all password fields.";
  }

  $storedHash = null;
  $stmtCheck = $conn->prepare("SELECT user_password FROM users WHERE user_id = ?");
  if ($stmtCheck) {
    $stmtCheck->bind_param("i", $user_id);
    if ($stmtCheck->execute()) {
      $stmtCheck->bind_result($storedHash);
      $stmtCheck->fetch();
    } else {
      $errors[] = "Unable to verify current password.";
    }
    $stmtCheck->close();
  } else {
    $errors[] = "Unable to prepare password verification.";
  }

  if ($storedHash) {
    if (!password_verify($currentPassword, $storedHash)) {
      $errors[] = "The current password you entered is incorrect.";
    }

    if ($newPassword !== '' && password_verify($newPassword, $storedHash)) {
      $errors[] = "Your new password cannot be the same as the current password.";
    }
  } else {
    $errors[] = "Account record could not be found.";
  }

  if ($newPassword !== $confirmPassword) {
    $errors[] = "New password and confirmation do not match.";
  }

  $strengthRules = [];
  if ($newPassword !== '') {
    if (strlen($newPassword) < 8) {
      $strengthRules[] = "be at least 8 characters";
    }
    if (!preg_match('/[A-Z]/', $newPassword)) {
      $strengthRules[] = "include an uppercase letter";
    }
    if (!preg_match('/[a-z]/', $newPassword)) {
      $strengthRules[] = "include a lowercase letter";
    }
    if (!preg_match('/\d/', $newPassword)) {
      $strengthRules[] = "include a number";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
      $strengthRules[] = "include a symbol";
    }
    if (!empty($strengthRules)) {
      $errors[] = "New password must " . implode(", ", $strengthRules) . ".";
    }
  }

  if (!empty($errors)) {
    swalScript("Password Update Failed", implode("\n", array_unique($errors)), "error", "
      const tab = document.getElementById('security-tab');
      if (tab) {
        if (typeof $ !== 'undefined' && typeof $.fn.tab === 'function') {
          $(tab).tab('show');
        } else {
          tab.click();
        }
      }
    ");
  } else {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $timestamp = date("Y-m-d H:i:s");

    $stmtUpdate = $conn->prepare("UPDATE users SET user_password = ? WHERE user_id = ?");
    if (!$stmtUpdate) {
      swalScript("Database Error", "Unable to prepare password update statement.", "error");
    } else {
      $stmtUpdate->bind_param("si", $hashedPassword, $user_id);
      if ($stmtUpdate->execute()) {
        swalScript("Success!", "Your password has been updated.", "success", "
          window.location.href = window.location.href;
        ");
      } else {
        swalScript("Database Error", "Unable to update password.", "error");
      }
      $stmtUpdate->close();
    }
  }
}

/* ----------------------------------------------------------
   HANDLE ACCOUNT DELETE
---------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
  $deletePassword = trim($_POST['deleteCurrentPassword'] ?? '');
  $deleteConfirm = isset($_POST['deleteConfirm']) && $_POST['deleteConfirm'] === '1';

  $errors = [];

  if ($deletePassword === '') {
    $errors[] = "Please enter your current password.";
  }

  if (!$deleteConfirm) {
    $errors[] = "Please confirm that you understand this action is permanent.";
  }

  $storedHash = null;
  $stmtCheck = $conn->prepare("SELECT user_password, user_email FROM users WHERE user_id = ?");
  $userEmail = '';
  if ($stmtCheck) {
    $stmtCheck->bind_param("i", $user_id);
    if ($stmtCheck->execute()) {
      $stmtCheck->bind_result($storedHash, $userEmail);
      $stmtCheck->fetch();
    } else {
      $errors[] = "Unable to verify your account password.";
    }
    $stmtCheck->close();
  } else {
    $errors[] = "Unable to prepare password verification.";
  }

  if ($storedHash && !password_verify($deletePassword, $storedHash)) {
    $errors[] = "The current password you entered is incorrect.";
  }

  if (!empty($errors)) {
    swalScript("Delete Account Failed", implode("\n", array_unique($errors)), "error", "
      const tab = document.getElementById('delete-tab');
      if (tab) {
        if (typeof $ !== 'undefined' && typeof $.fn.tab === 'function') {
          $(tab).tab('show');
        } else {
          tab.click();
        }
      }
    ");
  } else {
    mysqli_begin_transaction($conn);

    try {
      $deleteByUserId = [
        "DELETE FROM activities WHERE user_id = ?",
        "DELETE FROM holding WHERE user_id = ?",
        "DELETE FROM transaction WHERE user_id = ?",
        "DELETE FROM watchlist WHERE user_id = ?"
      ];

      foreach ($deleteByUserId as $sql) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
          throw new Exception("Unable to prepare account cleanup query.");
        }
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
          $stmt->close();
          throw new Exception("Failed while deleting related account data.");
        }
        $stmt->close();
      }

      $deleteByEmail = [
        "DELETE FROM otp_tokens WHERE email = ?",
        "DELETE FROM pending_users WHERE user_email = ?"
      ];

      foreach ($deleteByEmail as $sql) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
          throw new Exception("Unable to prepare email-linked cleanup query.");
        }
        $stmt->bind_param("s", $userEmail);
        if (!$stmt->execute()) {
          $stmt->close();
          throw new Exception("Failed while deleting email-linked account data.");
        }
        $stmt->close();
      }

      $stmtDeleteUser = $conn->prepare("DELETE FROM users WHERE user_id = ?");
      if (!$stmtDeleteUser) {
        throw new Exception("Unable to prepare user deletion.");
      }

      $stmtDeleteUser->bind_param("i", $user_id);
      if (!$stmtDeleteUser->execute()) {
        $stmtDeleteUser->close();
        throw new Exception("Unable to delete user account.");
      }
      $stmtDeleteUser->close();

      mysqli_commit($conn);

      $_SESSION = [];
      if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
          session_name(),
          '',
          time() - 42000,
          $params["path"],
          $params["domain"],
          $params["secure"],
          $params["httponly"]
        );
      }
      session_destroy();

      swalScript("Account Deleted", "Your account and simulation progress have been permanently removed.", "success", "
        window.location.href = '../auth/login.php';
      ");
    } catch (Exception $e) {
      mysqli_rollback($conn);
      swalScript("Delete Account Failed", $e->getMessage(), "error", "
        const tab = document.getElementById('delete-tab');
        if (tab) {
          if (typeof $ !== 'undefined' && typeof $.fn.tab === 'function') {
            $(tab).tab('show');
          } else {
            tab.click();
          }
        }
      ");
    }
  }
}

/* ----------------------------------------------------------
   HANDLE AVATAR UPDATE
---------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avatar_update'])) {
  $errors = [];
  $avatarUrl = null;
  $upload = $_FILES['avatar'] ?? null;

  if (!$upload) {
    $errors[] = "Please choose an image to upload.";
  } else {
    $errorCode = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
      $errors[] = "Please choose an image to upload.";
    } elseif ($errorCode !== UPLOAD_ERR_OK) {
      $errors[] = "Upload failed (error code: " . $errorCode . "). Please try again.";
    } else {
      $tmpPath = $upload['tmp_name'] ?? "";
      $originalName = $upload['name'] ?? "";
      $fileSize = (int) ($upload['size'] ?? 0);

      if ($fileSize <= 0 || !is_uploaded_file($tmpPath)) {
        $errors[] = "The uploaded file is invalid.";
      } else {
        $maxSize = 2 * 1024 * 1024;
        if ($fileSize > $maxSize) {
          $errors[] = "Image must be 2 MB or smaller.";
        } else {
          $mimeType = null;
          if (function_exists("finfo_open")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
              $mimeType = finfo_file($finfo, $tmpPath);
              finfo_close($finfo);
            }
          }
          if (!$mimeType && function_exists("mime_content_type")) {
            $mimeType = mime_content_type($tmpPath);
          }

          $allowedMimeTypes = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/gif"  => "gif",
            "image/webp" => "webp"
          ];

          if (!$mimeType || !isset($allowedMimeTypes[$mimeType])) {
            $errors[] = "Unsupported image format. Use JPG, PNG, GIF, or WEBP.";
          } else {
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedMimeTypes, true)) {
              $extension = $allowedMimeTypes[$mimeType];
            }

            $uploadDir = __DIR__ . "/../dist/img";
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
              $errors[] = "Upload directory is not writable.";
            } else {
              do {
                $randomNumber = (string) random_int(1000000000, 9999999999);
                $newFilename = $randomNumber . "." . $extension;
                $destination = $uploadDir . DIRECTORY_SEPARATOR . $newFilename;
              } while (file_exists($destination));

              if (!move_uploaded_file($tmpPath, $destination)) {
                $errors[] = "Unable to save the uploaded image.";
              } else {
                $stmtAvatar = $conn->prepare("UPDATE users SET avatar = ? WHERE user_id = ?");
                if ($stmtAvatar) {
                  $stmtAvatar->bind_param("si", $newFilename, $user_id);
                  if ($stmtAvatar->execute()) {
                    $avatarUrl = URL_HOST . "dist/img/" . $newFilename;
                    $active_user['avatar'] = $newFilename;
                  } else {
                    $errors[] = "Unable to update your profile picture.";
                    @unlink($destination);
                  }
                  $stmtAvatar->close();
                } else {
                  $errors[] = "Unable to prepare avatar update statement.";
                  @unlink($destination);
                }
              }
            }
          }
        }
      }
    }
  }

  if (!empty($errors)) {
    swalScript("Avatar Update Failed", implode("\n", array_unique($errors)), "error", "
      if (typeof $ !== 'undefined' && $('#avatar-modal').length) {
        $('#avatar-modal').modal('show');
      }
    ");
  } elseif ($avatarUrl) {
    swalScript("Avatar Updated", "Your profile picture has been refreshed.", "success", "
      window.location.href = window.location.href;
    ");
  }
}
?>
<style>
.profile-cover {
  background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #4b79a1 100%);
  min-height: 170px;
  border-radius: 0.75rem 0.75rem 0 0;
}
.profile-overview-card .widget-user-image {
  left: 50%;
  margin-left: -60px;
  top: 95px;
}
.profile-overview-card .widget-user-image img {
  width: 120px;
  height: 120px;
  object-fit: cover;
}
.avatar-modal .modal-body {
  text-align: center;
}
#avatar-preview {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #f4f6f9;
  box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.75);
  margin-bottom: 1rem;
}
#avatar-file-name {
  display: block;
  margin-top: 0.75rem;
  font-size: 0.85rem;
  color: #6c757d;
}
.change-avatar-btn {
  position: absolute;
  bottom: -12px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.75rem;
  padding: 0.25rem 0.75rem;
}
.balance-badge {
  background: rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  padding: 0.75rem 1.25rem;
  color: #fff;
  min-width: 170px;
}
.balance-badge .display-balance {
  font-size: 1.5rem;
  letter-spacing: 0.5px;
}
.profile-stats .stat {
  border-right: 1px solid rgba(0, 0, 0, 0.05);
}
.profile-stats .stat:last-child {
  border-right: none;
}
.profile-stats h5 {
  margin-bottom: 0.25rem;
}
.profile-stats span {
  font-size: 0.8rem;
  color: #6c757d;
}
.list-summary li span.value {
  font-weight: 600;
  color: #212529;
}
.password-guidelines li {
  margin-bottom: 0.3rem;
}
.delete-account-panel {
  border: 1px solid rgba(220, 53, 69, 0.18);
  border-radius: 0.5rem;
  background: #fff;
}
.delete-account-note {
  font-size: 0.92rem;
  line-height: 1.7;
}
@media (max-width: 575.98px) {
  .profile-overview-card .widget-user-image {
    top: 115px;
  }
  .change-avatar-btn {
    bottom: -16px;
  }
  .balance-badge {
    margin-top: 0.75rem;
    width: 100%;
  }
}
</style>

<section class="content pt-3">
  <div class="container-fluid">

    <div class="row">
      <div class="col-12">
        <div class="card card-widget widget-user shadow-sm profile-overview-card">
          <div class="widget-user-header text-white profile-cover d-flex flex-column flex-md-row align-items-md-end align-items-start justify-content-between">
            <div class="mb-3 mb-md-0">
              <h3 class="widget-user-username mb-1" id="profile-name"><?=h($active_user['user_name'])?></h3>
              <h5 class="widget-user-desc text-capitalize" id="profile-role"><?=h($active_user['role'])?></h5>
            </div>
            <div class="balance-badge text-center">
              <span class="text-uppercase small d-block">Account Balance</span>
              <span class="display-balance font-weight-bold" id="profile-balance">$<?= number_format((float)$active_user['user_balance'], 2, '.', ',') ?></span>
            </div>
          </div>

          <div class="widget-user-image">
            <img
              class="img-circle elevation-2"
              id="profile-avatar"
              src="<?=URL_HOST?>dist/img/<?=h($active_user['avatar'])?>"
              alt="User avatar">
            <button class="btn btn-primary change-avatar-btn" type="button" id="change-avatar-btn">
              <i class="fas fa-camera mr-1"></i>Change
            </button>
          </div>

          <div class="card-footer pt-5 profile-stats">
            <div class="row mt-3">
              <div class="col-sm-4 col-12 stat mb-3 mb-sm-0 text-center">
                <h5 id="profile-last-login"><?=h($active_user['last_login'])?> CST</h5>
                <span>Last Login</span>
              </div>
              <div class="col-sm-4 col-12 stat mb-3 mb-sm-0 text-center">
                <h5 id="profile-member-since"><?= isset($active_user['created_at']) ? h(substr($active_user['created_at'], 0, 4)) : '' ?></h5>
                <span>Member Since</span>
              </div>
              <div class="col-sm-4 col-12 text-center">
                <span class="badge badge-success px-3 py-2 text-white font-weight-bold" id="profile-status">
                  <?= ((int)$active_user['status'] === 1) ? "Active" : "Inactive" ?>
                </span>
                <span class="d-block mt-1 text-muted small">Status</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card card-outline card-secondary mb-3">
          <div class="card-header border-bottom-0 pb-0">
            <ul class="nav nav-pills" id="profile-tabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="personal-tab" data-toggle="pill" href="#personal" role="tab">
                  <i class="fas fa-user mr-1"></i>Personal Info
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="security-tab" data-toggle="pill" href="#security" role="tab">
                  <i class="fas fa-lock mr-1"></i>Security
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link text-danger" id="delete-tab" data-toggle="pill" href="#delete-account" role="tab">
                  <i class="fas fa-trash-alt mr-1"></i>Delete Account
                </a>
              </li>
            </ul>
          </div>

          <div class="card-body">
            <div class="tab-content" id="profile-tabs-content">

              <div class="tab-pane fade show active" id="personal" role="tabpanel">
                <form id="personal-form" method="post" action="<?=htmlspecialchars($_SERVER["PHP_SELF"]);?>" autocomplete="off">
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="input-name">Full Name</label>
                      <input type="text" class="form-control" id="input-name" name="name" value="<?=h($active_user['user_name'])?>" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="input-email">Email</label>
                      <input type="email" class="form-control" id="input-email" name="email" value="<?=h($active_user['user_email'])?>" required>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="input-dob">Date of Birth</label>
                      <input type="date" class="form-control" id="input-dob" name="dob" value="<?=h($active_user['dob'])?>" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="input-phone">Phone</label>
                      <input type="tel" class="form-control" id="input-phone" name="phone" value="<?=h($active_user['user_phone'])?>">
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-8">
                      <label for="input-address">Address</label>
                      <textarea class="form-control" id="input-address" rows="3" name="address" required><?=h($active_user['address'])?></textarea>
                    </div>
                  </div>

                  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center align-items-start">
                    <small class="text-muted mb-3 mb-md-0">Last updated: <span id="personal-last-updated"><?=h($active_user['last_updated'])?> CST</span></small>
                    <button type="submit" name="info_update" class="btn btn-primary">
                      <i class="fas fa-save mr-1"></i>Save Changes
                    </button>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade" id="security" role="tabpanel">
                <form id="security-form" method="post" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>" autocomplete="off">
                  <div class="form-group">
                    <label for="input-current-password">Current Password</label>
                    <div class="input-group">
                      <input type="password" class="form-control" id="input-current-password" name="currentPassword" autocomplete="current-password" required>
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="input-current-password">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="input-new-password">New Password</label>
                    <div class="input-group">
                      <input type="password" class="form-control" id="input-new-password" name="newPassword" autocomplete="new-password" required>
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="input-new-password">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                    </div>
                    <small class="form-text text-muted">Use at least 8 characters, including uppercase, numbers, and symbols.</small>
                    <div class="progress progress-xxs mt-2">
                      <div class="progress-bar" id="password-strength-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="d-block mt-1" id="password-strength-text">Strength: —</small>
                  </div>

                  <div class="form-group">
                    <label for="input-confirm-password">Confirm Password</label>
                    <div class="input-group">
                      <input type="password" class="form-control" id="input-confirm-password" name="confirmPassword" autocomplete="new-password" required>
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="input-confirm-password">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <ul class="small password-guidelines pl-3">
                    <li><i class="fas fa-check text-success mr-1"></i>Enable two-factor authentication for higher security.</li>
                    <li><i class="fas fa-check text-success mr-1"></i>Avoid reusing passwords across trading platforms.</li>
                    <li><i class="fas fa-check text-success mr-1"></i>Update passwords every 90 days to keep your account compliant.</li>
                  </ul>

                  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center align-items-start">
                    <small class="text-muted mb-3 mb-md-0">Last changed <span id="security-last-updated"><?=h($passwordLastChanged)?></span></small>
                    <button type="submit" name="password_update" class="btn btn-dark">
                      <i class="fas fa-save mr-1"></i>Update Password
                    </button>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade" id="delete-account" role="tabpanel">
                <div class="alert alert-danger">
                  <h5 class="mb-2"><i class="fas fa-exclamation-triangle mr-1"></i>Permanently Delete Account</h5>
                  <p class="mb-2">
                    Deleting your account will permanently erase your PaperTrade simulation data, including portfolio progress,
                    trade history, watchlist records, notifications, and account activity.
                  </p>
                  <p class="mb-0">
                    If you register again later with the same email, you will start from the beginning with a fresh account balance of
                    <strong>$100,000</strong>.
                  </p>
                </div>

                <div class="card delete-account-panel shadow-none">
                  <div class="card-body">
                    <p class="text-muted delete-account-note mb-4">
                      This action is permanent and cannot be undone.
                    </p>

                    <form id="delete-account-form" method="post" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
                      <div class="form-group">
                        <label for="delete-current-password">Confirm Current Password</label>
                        <div class="input-group">
                          <input type="password" class="form-control" id="delete-current-password" name="deleteCurrentPassword" autocomplete="current-password" required>
                          <div class="input-group-append">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="delete-current-password">
                              <i class="fas fa-eye"></i>
                            </button>
                          </div>
                        </div>
                        <small class="form-text text-muted">Enter your password to confirm permanent deletion.</small>
                      </div>

                      <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="delete-confirm-checkbox" name="deleteConfirm" value="1" required>
                        <label class="form-check-label" for="delete-confirm-checkbox">
                          I understand this action is permanent and all my progress will be lost.
                        </label>
                      </div>

                      <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <small class="text-danger mb-3 mb-md-0">This action cannot be undone.</small>
                        <button type="submit" name="delete_account" class="btn btn-danger">
                          <i class="fas fa-trash-alt mr-1"></i>Delete My Account
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card card-outline card-info mb-3">
          <div class="card-header">
            <h3 class="card-title mb-0">Account Summary</h3>
          </div>
          <div class="card-body">
            <ul class="list-unstyled list-summary mb-0">
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Member Since</span>
                <span class="value" id="summary-member-since"><?=h($active_user['created_at'])?></span>
              </li>
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Last Login</span>
                <span class="value" id="summary-last-login"><?=h($active_user['last_login'])?> CST</span>
              </li>
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Email</span>
                <span class="value" id="summary-email"><?=h($active_user['user_email'])?></span>
              </li>
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Phone</span>
                <span class="value" id="summary-phone"><?=h($active_user['user_phone'])?></span>
              </li>
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Address</span>
                <span class="value text-right" id="summary-address"><?=h($active_user['address'])?></span>
              </li>
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Plan</span>
                <span class="value" id="summary-plan"><?=h($active_user['role'])?></span>
              </li>
              <li class="d-flex justify-content-between align-items-center">
                <span class="text-muted text-uppercase small">Status</span>
                <span class="badge badge-success" id="summary-status"><?= ((int)$active_user['status'] === 1) ? "Active" : "Inactive" ?></span>
              </li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<div class="modal fade avatar-modal" id="avatar-modal" tabindex="-1" role="dialog" aria-labelledby="avatar-modal-label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="avatar-form" method="post" enctype="multipart/form-data" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
        <div class="modal-header">
          <h5 class="modal-title" id="avatar-modal-label">Update Profile Picture</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <img src="<?=URL_HOST?>dist/img/<?=h($active_user['avatar'])?>" alt="Current avatar" id="avatar-preview">
          <div class="form-group text-left mb-0">
            <label class="font-weight-bold small text-uppercase text-muted d-block" for="avatar-input">Select New Avatar</label>
            <input type="file" class="form-control-file" id="avatar-input" name="avatar" accept="image/*" required>
            <small id="avatar-file-name">No file selected</small>
            <small class="form-text text-muted mt-2">Accepted formats: JPG, PNG, GIF, or WEBP (max 2 MB).</small>
          </div>
        </div>
        <div class="modal-footer">
          <input type="hidden" name="avatar_update" value="1">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="avatar-save-btn" disabled>Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const toggleButtons = document.querySelectorAll(".toggle-password");
  toggleButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const targetId = this.getAttribute("data-target");
      const targetInput = document.getElementById(targetId);
      if (!targetInput) return;
      const icon = this.querySelector("i");
      const isPassword = targetInput.type === "password";
      targetInput.type = isPassword ? "text" : "password";
      if (icon) {
        icon.classList.toggle("fa-eye", !isPassword);
        icon.classList.toggle("fa-eye-slash", isPassword);
      }
    });
  });

  const changeAvatarButton = document.getElementById("change-avatar-btn");
  const avatarModalElement = document.getElementById("avatar-modal");
  const avatarInput = document.getElementById("avatar-input");
  const avatarPreview = document.getElementById("avatar-preview");
  const avatarSaveButton = document.getElementById("avatar-save-btn");
  const avatarFileName = document.getElementById("avatar-file-name");
  const profileAvatarImg = document.getElementById("profile-avatar");
  let avatarObjectUrl = null;

  const resetAvatarModal = function () {
    if (avatarInput) avatarInput.value = "";
    if (avatarFileName) avatarFileName.textContent = "No file selected";
    if (avatarSaveButton) avatarSaveButton.disabled = true;
    if (avatarPreview && profileAvatarImg) avatarPreview.src = profileAvatarImg.src;
    if (avatarObjectUrl) {
      URL.revokeObjectURL(avatarObjectUrl);
      avatarObjectUrl = null;
    }
  };

  if (changeAvatarButton && avatarModalElement) {
    changeAvatarButton.addEventListener("click", function () {
      resetAvatarModal();
      if (typeof $ !== "undefined" && typeof $.fn.modal === "function") {
        $("#avatar-modal").modal("show");
      }
    });
  }

  if (avatarModalElement) {
    avatarModalElement.addEventListener("hidden.bs.modal", resetAvatarModal);
  }

  if (avatarInput) {
    avatarInput.addEventListener("change", function () {
      const file = this.files && this.files[0] ? this.files[0] : null;
      if (!file) {
        resetAvatarModal();
        return;
      }

      const allowedMimeTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
      if (!allowedMimeTypes.includes(file.type)) {
        alert("Please choose a valid image file (JPG, PNG, GIF, or WEBP).");
        this.value = "";
        resetAvatarModal();
        return;
      }

      const maxSizeBytes = 2 * 1024 * 1024;
      if (file.size > maxSizeBytes) {
        alert("Image size must be 2 MB or smaller.");
        this.value = "";
        resetAvatarModal();
        return;
      }

      if (avatarFileName) avatarFileName.textContent = file.name;
      if (avatarSaveButton) avatarSaveButton.disabled = false;

      if (avatarPreview) {
        if (avatarObjectUrl) URL.revokeObjectURL(avatarObjectUrl);
        avatarObjectUrl = URL.createObjectURL(file);
        avatarPreview.src = avatarObjectUrl;
      }
    });
  }

  const newPasswordInput = document.getElementById("input-new-password");
  const strengthBar = document.getElementById("password-strength-bar");
  const strengthText = document.getElementById("password-strength-text");

  const updateStrengthMeter = function (password) {
    if (!strengthBar || !strengthText) return;

    if (!password) {
      strengthBar.style.width = "0%";
      strengthBar.className = "progress-bar";
      strengthText.textContent = "Strength: —";
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
    strengthBar.style.width = percent + "%";

    let colorClass = "bg-danger";
    let label = "Weak";

    if (passed >= 4) {
      colorClass = "bg-success";
      label = "Strong";
    } else if (passed === 3) {
      colorClass = "bg-info";
      label = "Good";
    } else if (passed === 2) {
      colorClass = "bg-warning";
      label = "Fair";
    }

    strengthBar.className = "progress-bar " + colorClass;
    strengthText.textContent = "Strength: " + label;
  };

  if (newPasswordInput) {
    updateStrengthMeter(newPasswordInput.value);
    newPasswordInput.addEventListener("input", function () {
      updateStrengthMeter(this.value);
    });
  }
});
</script>

<?php require "../lib/PAGE-bottom.php"; ?>
