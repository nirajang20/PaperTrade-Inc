<?php
require "../lib/Core.php";
require "../lib/PAGE-top.php";

$user_id = 0;
$passwordLastChanged = "—";

if (isset($active_user) && is_array($active_user)) {
  if (isset($active_user['user_id'])) {
    $user_id = (int) $active_user['user_id'];
  }

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
}
?>
<style>
/* Page specific tweaks for the profile layout */
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
.timeline-item {
  border-left: 3px solid;
  padding-left: 0.75rem;
  margin-left: 0.5rem;
}
.timeline-item.success {
  border-color: #28a745;
}
.timeline-item.warning {
  border-color: #ffc107;
}
.timeline-item.info {
  border-color: #17a2b8;
}
.timeline-item small {
  color: #6c757d;
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
              <h3 class="widget-user-username mb-1" id="profile-name"><?=$active_user['user_name']?></h3>
              <h5 class="widget-user-desc text-capitalize" id="profile-role"><?=$active_user['role']?></h5>
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
              src="<?=URL_HOST?>dist/img/<?=$active_user['avatar']?>"
              alt="User avatar"
            >
            <button class="btn btn-primary change-avatar-btn" type="button" id="change-avatar-btn">
              <i class="fas fa-camera mr-1"></i>Change
            </button>
          </div>
          <div class="card-footer pt-5 profile-stats">
            <div class="row mt-3">
              <div class="col-sm-4 col-12 stat mb-3 mb-sm-0 text-center">
                <h5 id="profile-last-login"><?=$active_user['last_login']?> CST</h5>
                <span>Last Login</span>
              </div>
              <div class="col-sm-4 col-12 stat mb-3 mb-sm-0 text-center">
                <h5 id="profile-member-since"><?= isset($active_user['created_at']) ? substr($active_user['created_at'], 0, 4) : '' ?></h5>
                <span>Member Since</span>
              </div>
              <div class="col-sm-4 col-12 text-center">
                <span class="badge badge-success px-3 py-2 text-white font-weight-bold" id="profile-status">
                  <?php
                  if ($active_user['status'] == '1') {
                    echo "Active";
                  } else {
                    echo "Inactive";
                  } 
                  ?>
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
                <a class="nav-link active" id="personal-tab" data-toggle="pill" href="#personal" role="tab" aria-controls="personal" aria-selected="true">
                  <i class="fas fa-user mr-1"></i>Personal Info
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="security-tab" data-toggle="pill" href="#security" role="tab" aria-controls="security" aria-selected="false">
                  <i class="fas fa-lock mr-1"></i>Security
                </a>
              </li>
            </ul>
          </div>
          <div class="card-body">
            <div class="tab-content" id="profile-tabs-content">
              <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                <form id="personal-form" class="needs-validation" autocomplete="off" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="input-name">Full Name</label>
                      <input type="text" class="form-control" id="input-name" name="name" value="<?=$active_user['user_name']?>" placeholder="Enter full name" required>
                      <div class="invalid-feedback">Please provide your full name.</div>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="input-email">Email</label>
                      <input type="email" class="form-control" id="input-email" name="email" value="<?=$active_user['user_email']?>" placeholder="name@email.com" required>
                      <div class="invalid-feedback">Please provide a valid email address.</div>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="input-dob">Date of Birth</label>
                      <input type="date" class="form-control" id="input-dob" value="<?=$active_user['dob']?>" name="dob" required>
                      <div class="invalid-feedback">Please choose your date of birth.</div>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="input-phone">Phone</label>
                      <input type="tel" class="form-control" id="input-phone" name="phone" value="<?=$active_user['user_phone']?>">
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-8">
                      <label for="input-address">Address</label>
                      <textarea class="form-control" id="input-address" rows="3" name="address" required><?=$active_user['address']?></textarea>
                      <div class="invalid-feedback">Please provide your mailing address.</div>
                    </div>
                  </div>
                  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center align-items-start">
                    <small class="text-muted mb-3 mb-md-0">Last updated: <span id="personal-last-updated"><?=$active_user['last_updated']?> CST</span></small>
                    <button type="submit" name="info_update" class="btn btn-primary">
                      <i class="fas fa-save mr-1"></i>Save Changes
                    </button>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                <form id="security-form" class="needs-validation" method="post" action="<?=htmlspecialchars($_SERVER['PHP_SELF']); ?>" autocomplete="off" novalidate>
                  <div class="form-group">
                    <label for="input-current-password">Current Password</label>
                    <div class="input-group">
                      <input type="password" class="form-control" id="input-current-password" name="currentPassword" autocomplete="current-password" required>
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="input-current-password">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                      <div class="invalid-feedback">Enter your current password.</div>
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
                      <div class="invalid-feedback">Please enter a new password.</div>
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
                      <div class="invalid-feedback">Please confirm the new password.</div>
                    </div>
                  </div>
                  <ul class="small password-guidelines pl-3">
                    <li><i class="fas fa-check text-success mr-1"></i>Enable two-factor authentication for higher security.</li>
                    <li><i class="fas fa-check text-success mr-1"></i>Avoid reusing passwords across trading platforms.</li>
                    <li><i class="fas fa-check text-success mr-1"></i>Update passwords every 90 days to keep your account compliant.</li>
                  </ul>
                  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center align-items-start">
                    <small class="text-muted mb-3 mb-md-0">Last changed <span id="security-last-updated"><?=$active_user['last_updated']?></span></small>
                    <button type="submit" name="password_update" class="btn btn-dark">
                      <i class="fas fa-save mr-1"></i>Update Password
                    </button>
                  </div>
                </form>
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
                <span class="value" id="summary-member-since"><?=$active_user['created_at']?></span>
              </li>
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Last Login</span>
                <span class="value" id="summary-last-login"><?=$active_user['last_login']?> CST</span>
              </li>
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Email</span>
                <span class="value" id="summary-email"><?=$active_user['user_email']?></span>
              </li>
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Phone</span>
                <span class="value" id="summary-phone"><?=$active_user['user_phone']?></span>
              </li>
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Address</span>
                <span class="value text-right" id="summary-address">4<?=$active_user['address']?></span>
              </li>
              <li class="d-flex justify-content-between mb-2">
                <span class="text-muted text-uppercase small">Plan</span>
                <span class="value" id="summary-plan"><?=$active_user['role']?></span>
              </li>
              <li class="d-flex justify-content-between align-items-center">
                <span class="text-muted text-uppercase small">Status</span>
                <span class="badge badge-success" id="summary-status"><?php
                  if ($active_user['status'] == '1') {
                    echo "Active";
                  } else {
                    echo "Inactive";
                  } 
                  ?></span>
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
      <form id="avatar-form" method="post" enctype="multipart/form-data" action="<?=htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="avatar-modal-label">Update Profile Picture</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <img src="<?=URL_HOST?>dist/img/<?=$active_user['avatar']?>" alt="Current avatar" id="avatar-preview">
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
      if (!targetInput) {
        return;
      }
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
    if (avatarInput) {
      avatarInput.value = "";
    }
    if (avatarFileName) {
      avatarFileName.textContent = "No file selected";
    }
    if (avatarSaveButton) {
      avatarSaveButton.disabled = true;
    }
    if (avatarPreview && profileAvatarImg) {
      avatarPreview.src = profileAvatarImg.src;
    }
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
      } else {
        avatarModalElement.classList.add("show");
        avatarModalElement.removeAttribute("aria-hidden");
        avatarModalElement.style.display = "block";
        document.body.classList.add("modal-open");
      }
    });
  }

  if (avatarModalElement) {
    avatarModalElement.addEventListener("hidden.bs.modal", resetAvatarModal);
  }

  const showAvatarError = function (message) {
    if (typeof Swal !== "undefined") {
      Swal.fire({
        title: "Upload Error",
        text: message,
        icon: "error"
      });
    } else {
      alert(message);
    }
  };

  if (avatarInput) {
    avatarInput.addEventListener("change", function () {
      const file = this.files && this.files[0] ? this.files[0] : null;
      if (!file) {
        resetAvatarModal();
        return;
      }

      const allowedMimeTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
      if (!allowedMimeTypes.includes(file.type)) {
        showAvatarError("Please choose a valid image file (JPG, PNG, GIF, or WEBP).");
        this.value = "";
        resetAvatarModal();
        return;
      }

      const maxSizeBytes = 2 * 1024 * 1024;
      if (file.size > maxSizeBytes) {
        showAvatarError("Image size must be 2 MB or smaller.");
        this.value = "";
        resetAvatarModal();
        return;
      }

      if (avatarFileName) {
        avatarFileName.textContent = file.name;
      }
      if (avatarSaveButton) {
        avatarSaveButton.disabled = false;
      }
      if (avatarPreview) {
        if (avatarObjectUrl) {
          URL.revokeObjectURL(avatarObjectUrl);
        }
        avatarObjectUrl = URL.createObjectURL(file);
        avatarPreview.src = avatarObjectUrl;
      }
    });
  }

  const newPasswordInput = document.getElementById("input-new-password");
  const confirmPasswordInput = document.getElementById("input-confirm-password");
  const strengthBar = document.getElementById("password-strength-bar");
  const strengthText = document.getElementById("password-strength-text");

  const updateStrengthMeter = function (password) {
    if (!strengthBar || !strengthText) {
      return;
    }

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

  if (confirmPasswordInput && newPasswordInput) {
    confirmPasswordInput.addEventListener("input", function () {
      if (this.value && this.value === newPasswordInput.value) {
        this.classList.remove("is-invalid");
      }
    });
  }
});
</script>

<?php

if ($user_id > 0 && isset($_POST['avatar_update'])) {
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
                    $errors[] = "Unable to update your profile picture. Please try again.";
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
    $errorMessage = implode("\n", array_unique($errors));
    echo "<script>
      Swal.fire({
        title: 'Avatar Update Failed',
        text: " . json_encode($errorMessage) . ",
        icon: 'error'
      }).then(() => {
        if (typeof $ !== 'undefined' && $('#avatar-modal').length) {
          $('#avatar-modal').modal('show');
        }
      });
    </script>";
  } elseif ($avatarUrl) {
    echo "<script>
      Swal.fire({
        title: 'Avatar Updated',
        text: 'Your profile picture has been refreshed.',
        icon: 'success'
      }).then(() => {
        if (typeof $ !== 'undefined' && $('#avatar-modal').length) {
          $('#avatar-modal').modal('hide');
        }
        const avatarImg = document.getElementById('profile-avatar');
        if (avatarImg) {
          avatarImg.src = " . json_encode($avatarUrl . "?v=" . time()) . ";
        }
        const avatarPreviewEl = document.getElementById('avatar-preview');
        if (avatarPreviewEl) {
          avatarPreviewEl.src = " . json_encode($avatarUrl . "?v=" . time()) . ";
        }
        const avatarFileNameEl = document.getElementById('avatar-file-name');
        if (avatarFileNameEl) {
          avatarFileNameEl.textContent = 'No file selected';
        }
        const avatarSaveBtn = document.getElementById('avatar-save-btn');
        if (avatarSaveBtn) {
          avatarSaveBtn.disabled = true;
        }
          window.location.href = window.location.href;
      });
    </script>";
  }
}

if ($user_id > 0 && isset($_POST['password_update'])) {
  $currentPassword = trim($_POST['currentPassword'] ?? "");
  $newPassword = trim($_POST['newPassword'] ?? "");
  $confirmPassword = trim($_POST['confirmPassword'] ?? "");

  $errors = [];
  $missingFields = $currentPassword === "" || $newPassword === "" || $confirmPassword === "";
  if ($missingFields) {
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
      $errors[] = "Unable to verify current password. Please try again.";
    }
    $stmtCheck->close();
  } else {
    $errors[] = "Unable to prepare password verification.";
  }

  $currentPasswordValid = false;
  $newMatchesCurrent = false;
  if ($storedHash !== null) {
    $currentPasswordValid = password_verify($currentPassword, $storedHash);
    if (!$currentPasswordValid) {
      $errors[] = "The current password you entered is incorrect.";
    }

    if ($newPassword !== "") {
      $newMatchesCurrent = password_verify($newPassword, $storedHash);
      if ($newMatchesCurrent) {
        $errors[] = "Your new password cannot be the same as the current password.";
      }
    }
  } else {
    $errors[] = "Account record could not be found.";
  }

  $confirmMismatch = $newPassword !== $confirmPassword;
  if ($confirmMismatch) {
    $errors[] = "New password and confirmation do not match.";
  }

  $strengthRules = [];
  if ($newPassword !== "") {
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
    $errorMessage = implode("\n", array_unique($errors));
    echo "<script>
      Swal.fire({
        title: 'Password Update Failed',
        text: " . json_encode($errorMessage) . ",
        icon: 'error'
      }).then(() => {
        const form = document.getElementById('security-form');
        if (form) {
          form.classList.add('was-validated');
        }
        const confirmInput = document.getElementById('input-confirm-password');
        if (confirmInput && " . ($confirmMismatch ? "true" : "false") . ") {
          confirmInput.classList.add('is-invalid');
        }
      });
    </script>";
  } else {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $timestamp = date("Y-m-d H:i:s");
    $passwordTimestampField = null;
    if (isset($active_user) && is_array($active_user)) {
      foreach (['password_updated_at', 'password_updated', 'password_changed_at', 'last_password_change'] as $field) {
        if (array_key_exists($field, $active_user)) {
          $passwordTimestampField = $field;
          break;
        }
      }
    }

    $stmtUpdate = null;
    if ($passwordTimestampField) {
      $updateSql = "UPDATE users SET user_password = ?, $passwordTimestampField = ? WHERE user_id = ?";
      $stmtUpdate = $conn->prepare($updateSql);
      if ($stmtUpdate) {
        $stmtUpdate->bind_param("ssi", $hashedPassword, $timestamp, $user_id);
      }
    } else {
      $updateSql = "UPDATE users SET user_password = ? WHERE user_id = ?";
      $stmtUpdate = $conn->prepare($updateSql);
      if ($stmtUpdate) {
        $stmtUpdate->bind_param("si", $hashedPassword, $user_id);
      }
    }

    if (!$stmtUpdate) {
      echo "<script>
        Swal.fire({
          title: 'Database Error',
          text: " . json_encode("Unable to prepare password update statement. Please try again later.") . ",
          icon: 'error'
        });
      </script>";
    } else {
      if ($stmtUpdate->execute()) {
        $formattedTimestamp = date("M d, Y g:i A", strtotime($timestamp));
        if ($passwordTimestampField) {
          $active_user[$passwordTimestampField] = $timestamp;
          $passwordLastChanged = $formattedTimestamp;
        }
        echo "<script>
          Swal.fire({
            title: 'Success!',
            text: 'Your password has been updated.',
            icon: 'success'
          }).then(() => {
            window.location.href = window.location.href;
            const form = document.getElementById('security-form');
            if (form) {
              form.reset();
              form.classList.remove('was-validated');
            }
            const currentInput = document.getElementById('input-current-password');
            if (currentInput) {
              currentInput.classList.remove('is-invalid');
            }
            const newInput = document.getElementById('input-new-password');
            if (newInput) {
              newInput.classList.remove('is-invalid');
            }
            const confirmInput = document.getElementById('input-confirm-password');
            if (confirmInput) {
              confirmInput.classList.remove('is-invalid');
            }
            const bar = document.getElementById('password-strength-bar');
            if (bar) {
              bar.style.width = '0%';
              bar.className = 'progress-bar';
            }
            const strength = document.getElementById('password-strength-text');
            if (strength) {
              strength.textContent = 'Strength: —';
            }
            const lastUpdatedEl = document.getElementById('security-last-updated');
            if (lastUpdatedEl) {
              lastUpdatedEl.textContent = " . json_encode($formattedTimestamp) . ";
            }
          });
        </script>";
      } else {
        echo "<script>
          Swal.fire({
            title: 'Database Error',
            text: " . json_encode("Unable to update password: " . $stmtUpdate->error) . ",
            icon: 'error'
          });
        </script>";
      }
      $stmtUpdate->close();
    }
  }
}

if ($user_id > 0 && isset($_POST['info_update'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $dob = trim($_POST['dob']);
  $phone = trim($_POST['phone']);
  $address = trim($_POST['address']);
  $updated_at = date("Y-m-d H:i:s");

  if (empty($name) || empty($email) || empty($dob) || empty($address)) {
    echo "<script>
      Swal.fire({
        title: 'Error!',
        text: 'Please fill all required fields.',
        icon: 'error'
      });
    </script>";
  } else {
    $sql = "UPDATE users 
            SET 
              user_name = ?, 
              user_email = ?, 
              dob = ?, 
              user_phone = ?, 
              address = ?, 
              last_updated = ?
            WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->bind_param("ssssssi", $name, $email, $dob, $phone, $address, $updated_at, $user_id);
      if ($stmt->execute()) {
        echo "<script>
          Swal.fire({
            title: 'Success!',
            text: 'Your information has been updated successfully.',
            icon: 'success'
          }).then(() => {
            window.location.href = window.location.href;
          });
        </script>";
      } else {
        echo "<script>
          Swal.fire({
            title: 'Database Error!',
            text: 'Error updating record: " . addslashes($stmt->error) . "',
            icon: 'error'
          });
        </script>";
      }
      $stmt->close();
    } else {
      echo "<script>
        Swal.fire({
          title: 'Error!',
          text: 'Database prepare failed: " . addslashes($conn->error) . "',
          icon: 'error'
        });
      </script>";
    }
  }
}
?>
<?php require "../lib/PAGE-bottom.php"; ?>
