<?php
require "lib/Core.php";

if (isset($_POST["req"])) {
  switch ($_POST["req"]) {

    // (A) INVALID REQUEST
    default:
      exit("INVALID REQUEST");

    // (B) LOGIN
    case "in":
      // (B1) ALREADY SIGNED IN
      if (isset($_SESSION["user"])) {
        exit("OK");
      }

      // (B2) VERIFY
      else {
        require PATH_LIB . "LIB-database.php";
        $DB = new DB();

        // Get user by email
        $user = $DB->fetch(
          "SELECT * FROM `users` WHERE `user_email`=?",
          [$_POST["email"]]
        );

        $valid = is_array($user);
        if ($valid) {
          $valid = password_verify($_POST["password"], $user["user_password"]);
        }

        if ($valid) {
          $isActive = isset($user["status"]) ? (int) $user["status"] === 1 : false;
          if (!$isActive) {
            exit("Account not active. Please contact support.");
          }
          // Store user session (excluding password)
          $_SESSION["user"] = [];
          foreach ($user as $k => $v) {
            if ($k != "user_password") {
              $_SESSION["user"][$k] = $v;
            }
          }
          $_SESSION['active_email'] = $_POST["email"];
          $_SESSION['welcome_back'] = true;
          // UPDATE LAST LOGIN DATE
          $today = date("Y-m-d H:i:s");
          if (method_exists($DB, 'query')) {
            $DB->query(
              "UPDATE `users` SET `last_login`=? WHERE `user_email`=?",
              [$today, $_POST["email"]]
            );
          } elseif (method_exists($DB, 'exec')) {
            $DB->exec(
              "UPDATE `users` SET `last_login`='" . addslashes($today) . "' WHERE `user_email`='" . addslashes($_POST["email"]) . "'"
            );
          } elseif (method_exists($DB, 'run')) {
            $DB->run(
              "UPDATE `users` SET `last_login`=? WHERE `user_email`=?",
              [$today, $_POST["email"]]
            );
          } elseif (method_exists($DB, 'prepare')) {
            $stmt = $DB->prepare("UPDATE `users` SET `last_login`=? WHERE `user_email`=?");
            if ($stmt) { $stmt->execute([$today, $_POST["email"]]); }
          }

          exit("OK");
        }

        exit("Invalid Email or Password!");
      }

    // (C) NOTIFICATIONS
    case "notification_mark":
      if (!isset($_SESSION["user"], $_SESSION["user"]["user_id"])) {
        exit("Please login first.");
      }
      $notifId = filter_input(INPUT_POST, "notification_id", FILTER_VALIDATE_INT);
      if (!$notifId) {
        exit("Invalid notification selected.");
      }
      $statusInput = isset($_POST["status"]) ? strtolower(trim($_POST["status"])) : "read";
      $isMarkRead = $statusInput !== "unread";
      $ownerId = (int) $_SESSION["user"]["user_id"];

      if ($isMarkRead) {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET status='Read', read_at = IFNULL(read_at, NOW()) WHERE notification_id = ? AND user_id = ?");
      } else {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET status='Unread', read_at = NULL WHERE notification_id = ? AND user_id = ?");
      }

      if (!$stmt) {
        exit("Unable to update notification.");
      }

      mysqli_stmt_bind_param($stmt, "ii", $notifId, $ownerId);
      mysqli_stmt_execute($stmt);
      $affected = mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);
      if ($affected < 1) {
        exit("Notification not found.");
      }
      exit("OK");

    case "notification_delete":
      if (!isset($_SESSION["user"], $_SESSION["user"]["user_id"])) {
        exit("Please login first.");
      }
      $notifId = filter_input(INPUT_POST, "notification_id", FILTER_VALIDATE_INT);
      if (!$notifId) {
        exit("Invalid notification selected.");
      }
      $ownerId = (int) $_SESSION["user"]["user_id"];
      $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_deleted = 1 WHERE notification_id = ? AND user_id = ?");
      mysqli_stmt_bind_param($stmt, "ii", $notifId, $ownerId);
      mysqli_stmt_execute($stmt);
      $affected = mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);
      if ($affected < 1) {
        exit("Notification not found.");
      }
      exit("OK");

    case "notification_restore":
      if (!isset($_SESSION["user"], $_SESSION["user"]["user_id"])) {
        exit("Please login first.");
      }
      $notifId = filter_input(INPUT_POST, "notification_id", FILTER_VALIDATE_INT);
      if (!$notifId) {
        exit("Invalid notification selected.");
      }
      $ownerId = (int) $_SESSION["user"]["user_id"];
      $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_deleted = 0 WHERE notification_id = ? AND user_id = ?");
      mysqli_stmt_bind_param($stmt, "ii", $notifId, $ownerId);
      mysqli_stmt_execute($stmt);
      $affected = mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);
      if ($affected < 1) {
        exit("Notification not found.");
      }
      exit("OK");

    case "notification_destroy":
      if (!isset($_SESSION["user"], $_SESSION["user"]["user_id"])) {
        exit("Please login first.");
      }
      $notifId = filter_input(INPUT_POST, "notification_id", FILTER_VALIDATE_INT);
      if (!$notifId) {
        exit("Invalid notification selected.");
      }
      $ownerId = (int) $_SESSION["user"]["user_id"];
      $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE notification_id = ? AND user_id = ? AND is_deleted = 1");
      mysqli_stmt_bind_param($stmt, "ii", $notifId, $ownerId);
      mysqli_stmt_execute($stmt);
      $affected = mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);
      if ($affected < 1) {
        exit("Notification not found.");
      }
      exit("OK");

    case "notification_fetch":
      if (!isset($_SESSION["user"], $_SESSION["user"]["user_id"])) {
        header("Content-Type: application/json");
        exit(json_encode([
          "success" => false,
          "message" => "Please login first."
        ]));
      }
      $ownerId = (int) $_SESSION["user"]["user_id"];
      $limit = filter_input(INPUT_POST, "limit", FILTER_VALIDATE_INT);
      if (!$limit || $limit < 1) {
        $limit = 6;
      } elseif ($limit > 100) {
        $limit = 100;
      }
      $includeDeleted = isset($_POST["include_deleted"]) && (int)$_POST["include_deleted"] === 1;

      $notifications = [];
      $unread = 0;
      $sqlFetch = "SELECT notification_id, title, message, status, created_at, is_deleted
                   FROM notifications
                   WHERE user_id = ?";
      if (!$includeDeleted) {
        $sqlFetch .= " AND is_deleted = 0";
      }
      $sqlFetch .= " ORDER BY created_at DESC LIMIT ?";
      $stmt = mysqli_prepare($conn, $sqlFetch);
      if (!$stmt) {
        header("Content-Type: application/json");
        exit(json_encode([
          "success" => false,
          "message" => "Unable to fetch notifications."
        ]));
      }

      mysqli_stmt_bind_param($stmt, "ii", $ownerId, $limit);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_bind_result($stmt, $notificationId, $title, $message, $status, $createdAt, $isDeleted);
      while (mysqli_stmt_fetch($stmt)) {
        $statusValue = $status ?? "Unread";
        $isRead = strcasecmp($statusValue, "Read") === 0;
        if (!$isRead && !$isDeleted) {
          $unread++;
        }
        $notifications[] = [
          "notification_id" => (int) $notificationId,
          "title" => $title ?? "Notification",
          "message" => $message ?? "",
          "status" => $statusValue,
          "created_at" => $createdAt,
          "created_display" => $createdAt ? date("M j, Y g:i A", strtotime($createdAt)) : "",
          "is_deleted" => (int)$isDeleted
        ];
      }
      mysqli_stmt_close($stmt);

      header("Content-Type: application/json");
      exit(json_encode([
        "success" => true,
        "total" => count($notifications),
        "unread" => $unread,
        "notifications" => $notifications
      ]));

    // (D) LOGOUT
    case "out":
      unset($_SESSION["user"]);
      exit("OK");
  }
}
?>
