<?php
require "../lib/Core.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['admin_flash']) || !is_array($_SESSION['admin_flash'])) {
  $_SESSION['admin_flash'] = [];
}

function adminAddFlash(string $type, string $title, string $message): void {
  $_SESSION['admin_flash'][] = [
    "type" => $type,
    "title" => $title,
    "message" => $message
  ];
}

function adminRedirectSelf(): void {
  header("Location: " . URL_HOST . "admin/all-users.php");
  exit;
}

function adminNormalizePhone(?string $phone): string {
  if ($phone === null) {
    return "0";
  }
  $digits = preg_replace("/\\D+/", "", $phone);
  return $digits !== "" ? $digits : "0";
}

function adminNormalizeBalance($value): int {
  if ($value === null || $value === "") {
    return 0;
  }
  if (is_numeric($value)) {
    return (int) round((float) $value);
  }
  $clean = preg_replace("/[^0-9.]/", "", (string) $value);
  return (int) round((float) $clean);
}

function adminAvatarFile(?string $avatar): string {
  return $avatar && trim($avatar) !== "" ? trim($avatar) : "avatar.png";
}

function adminEsc(?string $value): string {
  return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

function adminAttr(?string $value): string {
  $value = $value ?? "";
  $value = preg_replace("/\s+/", " ", $value);
  return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";
  try {
    switch ($action) {
      case "approve_pending":
        $pendingId = filter_input(INPUT_POST, "pending_id", FILTER_VALIDATE_INT);
        if (!$pendingId) {
          throw new RuntimeException("Invalid pending user identifier.");
        }

        $userName = "";
        $txStarted = false;

        try {
          mysqli_begin_transaction($conn);
          $txStarted = true;

          $stmt = mysqli_prepare($conn, "SELECT id, user_name, user_email, avatar, dob, address, password FROM pending_users WHERE id = ?");
          mysqli_stmt_bind_param($stmt, "i", $pendingId);
          mysqli_stmt_execute($stmt);
          mysqli_stmt_bind_result($stmt, $id, $selectedName, $selectedEmail, $selectedAvatar, $selectedDob, $selectedAddress, $selectedPassword);
          $hasRow = mysqli_stmt_fetch($stmt);
          mysqli_stmt_close($stmt);

          if (!$hasRow) {
            throw new RuntimeException("Pending user not found.");
          }

          $userName = $selectedName;

          $stmtCheck = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_email = ? LIMIT 1");
          mysqli_stmt_bind_param($stmtCheck, "s", $selectedEmail);
          mysqli_stmt_execute($stmtCheck);
          mysqli_stmt_store_result($stmtCheck);
          $duplicate = mysqli_stmt_num_rows($stmtCheck) > 0;
          mysqli_stmt_close($stmtCheck);

          if ($duplicate) {
            throw new RuntimeException("A verified user already exists with this email address.");
          }

          $avatarFile = adminAvatarFile($selectedAvatar);
          $userPhone = "0";
          $userBalance = 100000;
          $role = "user";
          $status = 1;

          $insertSql = "INSERT INTO users (user_email, dob, user_phone, address, user_name, user_password, avatar, user_balance, role, last_login, last_updated, created_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), CURDATE(), ?)";
          $stmtInsert = mysqli_prepare($conn, $insertSql);
          mysqli_stmt_bind_param($stmtInsert, "sssssssisi", $selectedEmail, $selectedDob, $userPhone, $selectedAddress, $selectedName, $selectedPassword, $avatarFile, $userBalance, $role, $status);
          mysqli_stmt_execute($stmtInsert);
          mysqli_stmt_close($stmtInsert);

          $stmtDelete = mysqli_prepare($conn, "DELETE FROM pending_users WHERE id = ?");
          mysqli_stmt_bind_param($stmtDelete, "i", $pendingId);
          mysqli_stmt_execute($stmtDelete);
          mysqli_stmt_close($stmtDelete);

          mysqli_commit($conn);
        } catch (Throwable $th) {
          if ($txStarted) {
            mysqli_rollback($conn);
          }
          throw $th;
        }

        adminAddFlash(
          "success",
          "User Verified",
          sprintf("%s has been verified and funded with $%s.", $userName, number_format(100000, 0))
        );
        break;

      case "create_pending":
        $userName = trim($_POST["user_name"] ?? "");
        $userEmail = trim($_POST["user_email"] ?? "");
        $dob = $_POST["dob"] ?? "";
        $address = trim($_POST["address"] ?? "");
        $rawPassword = $_POST["user_password"] ?? "";
        $password = trim($rawPassword);

        if ($userName === "" || $userEmail === "" || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
          throw new RuntimeException("Please provide a valid name and email address.");
        }
        if ($dob === "") {
          throw new RuntimeException("Date of birth is required.");
        }
        if ($password === "") {
          throw new RuntimeException("Password is required for new users.");
        }
        if (strlen($password) < 8) {
          throw new RuntimeException("Password must be at least 8 characters long.");
        }

        $duplicate = false;
        $stmtCheckPending = mysqli_prepare($conn, "SELECT id FROM pending_users WHERE user_email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtCheckPending, "s", $userEmail);
        mysqli_stmt_execute($stmtCheckPending);
        mysqli_stmt_store_result($stmtCheckPending);
        $duplicate = mysqli_stmt_num_rows($stmtCheckPending) > 0;
        mysqli_stmt_close($stmtCheckPending);

        if (!$duplicate) {
          $stmtCheckUsers = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_email = ? LIMIT 1");
          mysqli_stmt_bind_param($stmtCheckUsers, "s", $userEmail);
          mysqli_stmt_execute($stmtCheckUsers);
          mysqli_stmt_store_result($stmtCheckUsers);
          $duplicate = mysqli_stmt_num_rows($stmtCheckUsers) > 0;
          mysqli_stmt_close($stmtCheckUsers);
        }

        if ($duplicate) {
          throw new RuntimeException("A user with this email already exists or is pending verification.");
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $avatarFile = adminAvatarFile(null);

        $stmtCreate = mysqli_prepare($conn, "INSERT INTO pending_users (user_name, user_email, avatar, dob, address, password) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmtCreate, "ssssss", $userName, $userEmail, $avatarFile, $dob, $address, $hashedPassword);
        mysqli_stmt_execute($stmtCreate);
        mysqli_stmt_close($stmtCreate);

        adminAddFlash("success", "Pending User Added", "New pending user has been created successfully.");
        break;

      case "update_verified":
        $userId = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
        if (!$userId) {
          throw new RuntimeException("Invalid user identifier.");
        }

        $userName = trim($_POST["user_name"] ?? "");
        $userEmail = trim($_POST["user_email"] ?? "");
        $userPhone = adminNormalizePhone($_POST["user_phone"] ?? null);
        $dob = $_POST["dob"] ?? "";
        $address = trim($_POST["address"] ?? "");
        $role = trim($_POST["role"] ?? "user");
        $status = isset($_POST["status"]) && (int) $_POST["status"] === 1 ? 1 : 0;
        $userBalance = adminNormalizeBalance($_POST["user_balance"] ?? 0);
        $newPasswordRaw = $_POST["user_password"] ?? "";
        $newPassword = trim($newPasswordRaw);
        $hashedPassword = null;

        if ($userName === "" || $userEmail === "" || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
          throw new RuntimeException("Please provide a valid name and email address.");
        }
        if ($dob === "") {
          throw new RuntimeException("Date of birth is required.");
        }

        if ($newPassword !== "") {
          if (strlen($newPassword) < 8) {
            throw new RuntimeException("New password must be at least 8 characters long.");
          }
          $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $stmtCheck = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_email = ? AND user_id <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmtCheck, "si", $userEmail, $userId);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);
        $emailExists = mysqli_stmt_num_rows($stmtCheck) > 0;
        mysqli_stmt_close($stmtCheck);

        if ($emailExists) {
          throw new RuntimeException("Another user already uses this email address.");
        }

        if ($hashedPassword !== null) {
          $sql = "UPDATE users SET user_name = ?, user_email = ?, user_phone = ?, dob = ?, address = ?, user_balance = ?, role = ?, status = ?, user_password = ?, last_updated = NOW() WHERE user_id = ?";
          $stmt = mysqli_prepare($conn, $sql);
          mysqli_stmt_bind_param($stmt, "sssssisisi", $userName, $userEmail, $userPhone, $dob, $address, $userBalance, $role, $status, $hashedPassword, $userId);
        } else {
          $sql = "UPDATE users SET user_name = ?, user_email = ?, user_phone = ?, dob = ?, address = ?, user_balance = ?, role = ?, status = ?, last_updated = NOW() WHERE user_id = ?";
          $stmt = mysqli_prepare($conn, $sql);
          mysqli_stmt_bind_param($stmt, "sssssisii", $userName, $userEmail, $userPhone, $dob, $address, $userBalance, $role, $status, $userId);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        adminAddFlash("success", "User Updated", "User profile was updated successfully.");
        break;

      case "update_pending":
        $pendingId = filter_input(INPUT_POST, "pending_id", FILTER_VALIDATE_INT);
        if (!$pendingId) {
          throw new RuntimeException("Invalid pending user identifier.");
        }

        $userName = trim($_POST["user_name"] ?? "");
        $userEmail = trim($_POST["user_email"] ?? "");
        $dob = $_POST["dob"] ?? "";
        $address = trim($_POST["address"] ?? "");

        if ($userName === "" || $userEmail === "" || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
          throw new RuntimeException("Please provide a valid name and email address.");
        }
        if ($dob === "") {
          throw new RuntimeException("Date of birth is required.");
        }

        $stmtCheck = mysqli_prepare($conn, "SELECT id FROM pending_users WHERE user_email = ? AND id <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmtCheck, "si", $userEmail, $pendingId);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);
        $emailExists = mysqli_stmt_num_rows($stmtCheck) > 0;
        mysqli_stmt_close($stmtCheck);

        if ($emailExists) {
          throw new RuntimeException("Another pending user already uses this email address.");
        }

        $stmt = mysqli_prepare($conn, "UPDATE pending_users SET user_name = ?, user_email = ?, dob = ?, address = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssssi", $userName, $userEmail, $dob, $address, $pendingId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        adminAddFlash("success", "Pending User Updated", "Pending user information saved.");
        break;

      case "delete_verified":
        $userId = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
        if (!$userId) {
          throw new RuntimeException("Invalid user identifier.");
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected < 1) {
          throw new RuntimeException("User not found or already removed.");
        }

        adminAddFlash("success", "User Deleted", "The verified user was removed.");
        break;

      case "delete_pending":
        $pendingId = filter_input(INPUT_POST, "pending_id", FILTER_VALIDATE_INT);
        if (!$pendingId) {
          throw new RuntimeException("Invalid pending user identifier.");
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM pending_users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $pendingId);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected < 1) {
          throw new RuntimeException("Pending user not found or already removed.");
        }

        adminAddFlash("success", "Pending User Deleted", "The pending user entry was deleted.");
        break;

      default:
        adminAddFlash("error", "Invalid Action", "The requested action is not supported.");
    }
  } catch (Throwable $th) {
    adminAddFlash("error", "Action Failed", $th->getMessage());
  }

  adminRedirectSelf();
}

$alerts = $_SESSION['admin_flash'];
$_SESSION['admin_flash'] = [];

$users = [];
$pendingUsers = [];

try {
  $usersResult = mysqli_query($conn, "SELECT user_id, user_name, user_email, user_phone, dob, address, avatar, user_balance, role, status, last_login, created_at FROM users ORDER BY created_at DESC");
  while ($row = mysqli_fetch_assoc($usersResult)) {
    $row['avatar'] = adminAvatarFile($row['avatar']);
    $users[] = $row;
  }
  mysqli_free_result($usersResult);
} catch (Throwable $th) {
  $alerts[] = [
    "type" => "error",
    "title" => "Load Failed",
    "message" => "Unable to load verified users: " . $th->getMessage()
  ];
}

try {
  $pendingResult = mysqli_query($conn, "SELECT id, user_name, user_email, avatar, dob, address, created_at FROM pending_users ORDER BY created_at DESC");
  while ($row = mysqli_fetch_assoc($pendingResult)) {
    $row['avatar'] = adminAvatarFile($row['avatar']);
    $pendingUsers[] = $row;
  }
  mysqli_free_result($pendingResult);
} catch (Throwable $th) {
  $alerts[] = [
    "type" => "error",
    "title" => "Load Failed",
    "message" => "Unable to load pending users: " . $th->getMessage()
  ];
}

require PATH_LIB . "PAGE-top.php";
?>

<section class="content pt-3">
  <div class="container-fluid">
    <style>
      .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
      }
      .table td {
        vertical-align: middle;
      }
      .table .text-wrap {
        white-space: normal;
      }
      .card-header-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      @media (max-width: 575.98px) {
        .card-header-actions {
          margin-top: 0.75rem;
          width: 100%;
          justify-content: flex-start;
        }
        .card-header-actions .badge {
          margin-bottom: 0.5rem;
        }
      }
    </style>

    <div class="row">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-user-check mr-2"></i>Verified Users</h3>
            <div class="card-header-actions">
              <span class="badge badge-secondary">Total: <?= count($users); ?></span>
              <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createPendingModal">
                <i class="fas fa-user-plus mr-1"></i>Add New
              </button>
            </div>
          </div>
          <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0" id="verified-users-table">
              <thead class="thead-light">
                <tr>
                  <th style="width: 40px;">#</th>
                  <th>User</th>
                  <th>Contact</th>
                  <th>Balance</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Last Login</th>
                  <th style="width: 140px;" class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$users): ?>
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">No verified users found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($users as $index => $user): ?>
                    <?php
                      $avatarUrl = URL_HOST . "dist/img/" . adminAvatarFile($user['avatar']);
                      $statusBadgeClass = ((int) $user['status'] === 1) ? 'badge-success' : 'badge-secondary';
                      $statusLabel = ((int) $user['status'] === 1) ? 'Active' : 'Inactive';
                      $lastLogin = $user['last_login'] ? $user['last_login'] : 'Never';
                    ?>
                    <tr>
                      <td><?= $index + 1; ?></td>
                      <td>
                        <div class="d-flex align-items-center">
                          <img src="<?= adminEsc($avatarUrl); ?>" alt="<?= adminEsc($user['user_name']); ?>" class="user-avatar mr-2">
                          <div>
                            <div class="font-weight-bold"><?= adminEsc($user['user_name']); ?></div>
                            <small class="text-muted">Joined <?= adminEsc($user['created_at']); ?></small>
                          </div>
                        </div>
                      </td>
                      <td class="text-wrap">
                        <div><i class="fas fa-envelope mr-1 text-muted"></i><?= adminEsc($user['user_email']); ?></div>
                        <small class="text-muted"><i class="fas fa-phone mr-1"></i><?= adminEsc($user['user_phone']); ?></small>
                      </td>
                      <td>$<?= number_format((float) $user['user_balance'], 2); ?></td>
                      <td class="text-capitalize"><?= adminEsc($user['role']); ?></td>
                      <td><span class="badge <?= $statusBadgeClass; ?> px-3"><?= $statusLabel; ?></span></td>
                      <td><?= adminEsc($lastLogin); ?></td>
                      <td class="text-right">
                        <div class="btn-group btn-group-sm">
                          <button
                            type="button"
                            class="btn btn-primary btn-view-verified"
                            data-toggle="modal"
                            data-target="#viewVerifiedModal"
                            data-user-id="<?= (int) $user['user_id']; ?>"
                            data-name="<?= adminAttr($user['user_name']); ?>"
                            data-email="<?= adminAttr($user['user_email']); ?>"
                            data-phone="<?= adminAttr($user['user_phone']); ?>"
                            data-dob="<?= adminAttr($user['dob']); ?>"
                            data-address="<?= adminAttr($user['address']); ?>"
                            data-balance="<?= adminAttr(number_format((float) $user['user_balance'], 2)); ?>"
                            data-role="<?= adminAttr($user['role']); ?>"
                            data-status="<?= (int) $user['status']; ?>"
                            data-avatar="<?= adminAttr($avatarUrl); ?>"
                            data-created="<?= adminAttr($user['created_at']); ?>"
                            data-last-login="<?= adminAttr($lastLogin); ?>"
                          >
                            <i class="fas fa-eye"></i>
                          </button>
                          <button
                            type="button"
                            class="btn btn-info btn-edit-verified"
                            data-toggle="modal"
                            data-target="#editVerifiedModal"
                            data-user-id="<?= (int) $user['user_id']; ?>"
                            data-name="<?= adminAttr($user['user_name']); ?>"
                            data-email="<?= adminAttr($user['user_email']); ?>"
                            data-phone="<?= adminAttr($user['user_phone']); ?>"
                            data-dob="<?= adminAttr($user['dob']); ?>"
                            data-address="<?= adminAttr($user['address']); ?>"
                            data-balance="<?= adminAttr((string) $user['user_balance']); ?>"
                            data-role="<?= adminAttr($user['role']); ?>"
                            data-status="<?= (int) $user['status']; ?>"
                          >
                            <i class="fas fa-edit"></i>
                          </button>
                          <button
                            type="button"
                            class="btn btn-danger btn-delete-verified"
                            data-user-id="<?= (int) $user['user_id']; ?>"
                            data-user-name="<?= adminAttr($user['user_name']); ?>"
                          >
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-user-clock mr-2"></i>Pending Users</h3>
            <span class="badge badge-warning text-dark">Total: <?= count($pendingUsers); ?></span>
          </div>
          <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0" id="pending-users-table">
              <thead class="thead-light">
                <tr>
                  <th style="width: 40px;">#</th>
                  <th>User</th>
                  <th>Email</th>
                  <th>Date of Birth</th>
                  <th>Submitted</th>
                  <th style="width: 200px;" class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$pendingUsers): ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">No pending registrations at the moment.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($pendingUsers as $index => $pending): ?>
                    <?php
                      $avatarUrl = URL_HOST . "dist/img/" . adminAvatarFile($pending['avatar']);
                    ?>
                    <tr>
                      <td><?= $index + 1; ?></td>
                      <td>
                        <div class="d-flex align-items-center">
                          <img src="<?= adminEsc($avatarUrl); ?>" alt="<?= adminEsc($pending['user_name']); ?>" class="user-avatar mr-2">
                          <div>
                            <div class="font-weight-bold"><?= adminEsc($pending['user_name']); ?></div>
                            <small class="text-muted text-wrap"><?= adminEsc($pending['address']); ?></small>
                          </div>
                        </div>
                      </td>
                      <td><?= adminEsc($pending['user_email']); ?></td>
                      <td><?= adminEsc($pending['dob']); ?></td>
                      <td><?= adminEsc($pending['created_at']); ?></td>
                      <td class="text-right">
                        <div class="btn-group btn-group-sm">
                          <button
                            type="button"
                            class="btn btn-primary btn-view-pending"
                            data-toggle="modal"
                            data-target="#viewPendingModal"
                            data-pending-id="<?= (int) $pending['id']; ?>"
                            data-name="<?= adminAttr($pending['user_name']); ?>"
                            data-email="<?= adminAttr($pending['user_email']); ?>"
                            data-dob="<?= adminAttr($pending['dob']); ?>"
                            data-address="<?= adminAttr($pending['address']); ?>"
                            data-created="<?= adminAttr($pending['created_at']); ?>"
                            data-avatar="<?= adminAttr($avatarUrl); ?>"
                          >
                            <i class="fas fa-eye"></i>
                          </button>
                          <button
                            type="button"
                            class="btn btn-info btn-edit-pending"
                            data-toggle="modal"
                            data-target="#editPendingModal"
                            data-pending-id="<?= (int) $pending['id']; ?>"
                            data-name="<?= adminAttr($pending['user_name']); ?>"
                            data-email="<?= adminAttr($pending['user_email']); ?>"
                            data-dob="<?= adminAttr($pending['dob']); ?>"
                            data-address="<?= adminAttr($pending['address']); ?>"
                          >
                            <i class="fas fa-edit"></i>
                          </button>
                          <button
                            type="button"
                            class="btn btn-success btn-approve-pending"
                            data-pending-id="<?= (int) $pending['id']; ?>"
                            data-user-name="<?= adminAttr($pending['user_name']); ?>"
                          >
                            <i class="fas fa-user-check"></i>
                          </button>
                          <button
                            type="button"
                            class="btn btn-danger btn-delete-pending"
                            data-pending-id="<?= (int) $pending['id']; ?>"
                            data-user-name="<?= adminAttr($pending['user_name']); ?>"
                          >
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- View Verified Modal -->
<div class="modal fade" id="viewVerifiedModal" tabindex="-1" role="dialog" aria-labelledby="viewVerifiedLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewVerifiedLabel"><i class="fas fa-id-card mr-1"></i>User Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center mb-3">
          <img id="viewVerifiedAvatar" src="" alt="User avatar" class="user-avatar mr-3" style="width:64px;height:64px;">
          <div>
            <h4 class="mb-0" id="viewVerifiedName"></h4>
            <small class="text-muted" id="viewVerifiedCreated"></small>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <p><strong>Email:</strong> <span id="viewVerifiedEmail"></span></p>
            <p><strong>Phone:</strong> <span id="viewVerifiedPhone"></span></p>
            <p><strong>Date of Birth:</strong> <span id="viewVerifiedDob"></span></p>
          </div>
          <div class="col-md-6">
            <p><strong>Balance:</strong> $<span id="viewVerifiedBalance"></span></p>
            <p><strong>Role:</strong> <span id="viewVerifiedRole"></span></p>
            <p><strong>Status:</strong> <span id="viewVerifiedStatus"></span></p>
          </div>
        </div>
        <p class="mb-1"><strong>Address:</strong></p>
        <p class="border rounded p-2 bg-light" id="viewVerifiedAddress"></p>
        <p class="mb-0 text-muted"><strong>Last Login:</strong> <span id="viewVerifiedLastLogin"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Verified Modal -->
<div class="modal fade" id="editVerifiedModal" tabindex="-1" role="dialog" aria-labelledby="editVerifiedLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form method="post" id="editVerifiedForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="editVerifiedLabel"><i class="fas fa-user-edit mr-1"></i>Edit Verified User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="update_verified">
          <input type="hidden" name="user_id" id="editVerifiedId">
          <div class="form-group">
            <label for="editVerifiedName">Full Name</label>
            <input type="text" class="form-control" id="editVerifiedName" name="user_name" required>
          </div>
          <div class="form-group">
            <label for="editVerifiedEmail">Email</label>
            <input type="email" class="form-control" id="editVerifiedEmail" name="user_email" required>
          </div>
          <div class="form-group">
            <label for="editVerifiedPassword">New Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="editVerifiedPassword" name="user_password" placeholder="Leave blank to keep current password" minlength="8">
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary toggle-password-btn" data-target="#editVerifiedPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <small class="form-text text-muted">Leave blank to keep the existing password.</small>
          </div>
          <div class="form-group">
            <label for="editVerifiedPhone">Phone</label>
            <input type="text" class="form-control" id="editVerifiedPhone" name="user_phone">
          </div>
          <div class="form-group">
            <label for="editVerifiedDob">Date of Birth</label>
            <input type="date" class="form-control" id="editVerifiedDob" name="dob" required>
          </div>
          <div class="form-group">
            <label for="editVerifiedAddress">Address</label>
            <textarea class="form-control" id="editVerifiedAddress" name="address" rows="3"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="editVerifiedBalance">Balance ($)</label>
              <input type="number" class="form-control" id="editVerifiedBalance" name="user_balance" min="0" step="100">
            </div>
            <div class="form-group col-md-3">
              <label for="editVerifiedRole">Role</label>
              <input type="text" class="form-control text-capitalize" id="editVerifiedRole" name="role" placeholder="user">
            </div>
            <div class="form-group col-md-3">
              <label for="editVerifiedStatus">Status</label>
              <select class="form-control" id="editVerifiedStatus" name="status">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Pending Modal -->
<div class="modal fade" id="viewPendingModal" tabindex="-1" role="dialog" aria-labelledby="viewPendingLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewPendingLabel"><i class="fas fa-user-clock mr-1"></i>Pending User</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center mb-3">
          <img id="viewPendingAvatar" src="" alt="User avatar" class="user-avatar mr-3" style="width:64px;height:64px;">
          <div>
            <h4 class="mb-0" id="viewPendingName"></h4>
            <small class="text-muted" id="viewPendingCreated"></small>
          </div>
        </div>
        <p><strong>Email:</strong> <span id="viewPendingEmail"></span></p>
        <p><strong>Date of Birth:</strong> <span id="viewPendingDob"></span></p>
        <p class="mb-1"><strong>Address:</strong></p>
        <p class="border rounded p-2 bg-light" id="viewPendingAddress"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Pending Modal -->
<div class="modal fade" id="editPendingModal" tabindex="-1" role="dialog" aria-labelledby="editPendingLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form method="post" id="editPendingForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="editPendingLabel"><i class="fas fa-user-edit mr-1"></i>Edit Pending User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="update_pending">
          <input type="hidden" name="pending_id" id="editPendingId">
          <div class="form-group">
            <label for="editPendingName">Full Name</label>
            <input type="text" class="form-control" id="editPendingName" name="user_name" required>
          </div>
          <div class="form-group">
            <label for="editPendingEmail">Email</label>
            <input type="email" class="form-control" id="editPendingEmail" name="user_email" required>
          </div>
          <div class="form-group">
            <label for="editPendingDob">Date of Birth</label>
            <input type="date" class="form-control" id="editPendingDob" name="dob" required>
          </div>
          <div class="form-group">
            <label for="editPendingAddress">Address</label>
            <textarea class="form-control" id="editPendingAddress" name="address" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<form method="post" class="d-none" id="verifiedActionForm">
  <input type="hidden" name="action" id="verifiedActionType" value="">
  <input type="hidden" name="user_id" id="verifiedActionId" value="">
</form>

<form method="post" class="d-none" id="pendingActionForm">
  <input type="hidden" name="action" id="pendingActionType" value="">
  <input type="hidden" name="pending_id" id="pendingActionId" value="">
</form>

<!-- Create Pending Modal -->
<div class="modal fade" id="createPendingModal" tabindex="-1" role="dialog" aria-labelledby="createPendingLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form method="post" id="createPendingForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="createPendingLabel"><i class="fas fa-user-plus mr-1"></i>Create Pending User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="create_pending">
          <div class="form-group">
            <label for="createPendingName">Full Name</label>
            <input type="text" class="form-control" id="createPendingName" name="user_name" required>
          </div>
          <div class="form-group">
            <label for="createPendingEmail">Email</label>
            <input type="email" class="form-control" id="createPendingEmail" name="user_email" required>
          </div>
          <div class="form-group">
            <label for="createPendingDob">Date of Birth</label>
            <input type="date" class="form-control" id="createPendingDob" name="dob" required>
          </div>
          <div class="form-group">
            <label for="createPendingAddress">Address</label>
            <textarea class="form-control" id="createPendingAddress" name="address" rows="3" required></textarea>
          </div>
          <div class="form-group">
            <label for="createPendingPassword">Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="createPendingPassword" name="user_password" required minlength="8">
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary toggle-password-btn" data-target="#createPendingPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <small class="form-text text-muted">Password must be at least 8 characters.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Pending User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const flashMessages = <?= json_encode($alerts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    if (Array.isArray(flashMessages) && flashMessages.length) {
      if (window.toastr) {
        toastr.options = {
          positionClass: 'toast-top-right',
          progressBar: true,
          timeOut: 5000
        };

        flashMessages.forEach(function (msg) {
          const type = (msg.type === 'success' || msg.type === 'error' || msg.type === 'warning') ? msg.type : 'info';
          if (typeof toastr[type] === 'function') {
            toastr[type](msg.message || '', msg.title || '');
          } else {
            toastr.info(msg.message || '', msg.title || '');
          }
        });
      }

      const primary = flashMessages[flashMessages.length - 1];
      if (window.Swal && primary) {
        let icon = 'info';
        if (primary.type === 'success') icon = 'success';
        if (primary.type === 'error') icon = 'error';
        if (primary.type === 'warning') icon = 'warning';
        Swal.fire({
          icon: icon,
          title: primary.title || '',
          text: primary.message || ''
        });
      }
    }

    function resetPasswordToggle(targetSelector) {
      const input = document.querySelector(targetSelector);
      if (input) {
        input.value = '';
        input.setAttribute('type', 'password');
      }
      const toggleButton = document.querySelector('.toggle-password-btn[data-target="' + targetSelector + '"]');
      if (toggleButton) {
        const icon = toggleButton.querySelector('i');
        if (icon) {
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
        }
      }
    }

    document.querySelectorAll('.toggle-password-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const targetSelector = this.getAttribute('data-target');
        if (!targetSelector) return;
        const input = document.querySelector(targetSelector);
        if (!input) return;
        const icon = this.querySelector('i');
        const isHidden = input.getAttribute('type') === 'password';
        input.setAttribute('type', isHidden ? 'text' : 'password');
        if (icon) {
          if (isHidden) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
          } else {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
          }
        }
      });
    });

    $('#viewVerifiedModal').on('show.bs.modal', function (event) {
      const button = $(event.relatedTarget);
      const modal = $(this);
      modal.find('#viewVerifiedAvatar').attr('src', button.data('avatar'));
      modal.find('#viewVerifiedName').text(button.data('name') || '');
      modal.find('#viewVerifiedEmail').text(button.data('email') || '');
      modal.find('#viewVerifiedPhone').text(button.data('phone') || '');
      modal.find('#viewVerifiedDob').text(button.data('dob') || '');
      modal.find('#viewVerifiedBalance').text(button.data('balance') || '0.00');
      modal.find('#viewVerifiedRole').text(button.data('role') || '');
      modal.find('#viewVerifiedStatus').text(button.data('status') === 1 ? 'Active' : 'Inactive');
      modal.find('#viewVerifiedAddress').text(button.data('address') || '');
      modal.find('#viewVerifiedLastLogin').text(button.data('last-login') || 'Never');
      modal.find('#viewVerifiedCreated').text('Member since ' + (button.data('created') || '')); 
    });

    $('#editVerifiedModal').on('show.bs.modal', function (event) {
      const button = $(event.relatedTarget);
      $('#editVerifiedId').val(button.data('user-id'));
      $('#editVerifiedName').val(button.data('name'));
      $('#editVerifiedEmail').val(button.data('email'));
      $('#editVerifiedPhone').val(button.data('phone'));
      $('#editVerifiedDob').val(button.data('dob'));
      $('#editVerifiedAddress').val(button.data('address'));
      $('#editVerifiedBalance').val(button.data('balance'));
      $('#editVerifiedRole').val(button.data('role'));
      $('#editVerifiedStatus').val(button.data('status'));
      resetPasswordToggle('#editVerifiedPassword');
    });

    $('#viewPendingModal').on('show.bs.modal', function (event) {
      const button = $(event.relatedTarget);
      const modal = $(this);
      modal.find('#viewPendingAvatar').attr('src', button.data('avatar'));
      modal.find('#viewPendingName').text(button.data('name'));
      modal.find('#viewPendingEmail').text(button.data('email'));
      modal.find('#viewPendingDob').text(button.data('dob'));
      modal.find('#viewPendingAddress').text(button.data('address'));
      modal.find('#viewPendingCreated').text('Submitted ' + (button.data('created') || ''));
    });

    $('#editPendingModal').on('show.bs.modal', function (event) {
      const button = $(event.relatedTarget);
      $('#editPendingId').val(button.data('pending-id'));
      $('#editPendingName').val(button.data('name'));
      $('#editPendingEmail').val(button.data('email'));
      $('#editPendingDob').val(button.data('dob'));
      $('#editPendingAddress').val(button.data('address'));
    });

    $('#createPendingModal').on('show.bs.modal', function () {
      const form = document.getElementById('createPendingForm');
      if (form) {
        form.reset();
      }
      resetPasswordToggle('#createPendingPassword');
    });

    document.querySelectorAll('.btn-delete-verified').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const userId = this.getAttribute('data-user-id');
        const userName = this.getAttribute('data-user-name') || 'this user';
        Swal.fire({
          icon: 'warning',
          title: 'Delete user?',
          text: 'This will permanently remove ' + userName + '.',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: 'Yes, delete'
        }).then(function (result) {
          if (result.isConfirmed) {
            document.getElementById('verifiedActionType').value = 'delete_verified';
            document.getElementById('verifiedActionId').value = userId;
            document.getElementById('verifiedActionForm').submit();
          }
        });
      });
    });

    document.querySelectorAll('.btn-delete-pending').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const pendingId = this.getAttribute('data-pending-id');
        const userName = this.getAttribute('data-user-name') || 'this pending user';
        Swal.fire({
          icon: 'warning',
          title: 'Delete pending user?',
          text: 'This will remove ' + userName + ' from pending registrations.',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: 'Yes, delete'
        }).then(function (result) {
          if (result.isConfirmed) {
            document.getElementById('pendingActionType').value = 'delete_pending';
            document.getElementById('pendingActionId').value = pendingId;
            document.getElementById('pendingActionForm').submit();
          }
        });
      });
    });

    document.querySelectorAll('.btn-approve-pending').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const pendingId = this.getAttribute('data-pending-id');
        const userName = this.getAttribute('data-user-name') || 'this pending user';
        Swal.fire({
          icon: 'question',
          title: 'Approve user?',
          text: 'Verify ' + userName + ' and credit $100,000 to their account?',
          showCancelButton: true,
          confirmButtonText: 'Yes, approve',
          confirmButtonColor: '#28a745'
        }).then(function (result) {
          if (result.isConfirmed) {
            document.getElementById('pendingActionType').value = 'approve_pending';
            document.getElementById('pendingActionId').value = pendingId;
            document.getElementById('pendingActionForm').submit();
          }
        });
      });
    });
  });
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>
