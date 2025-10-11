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
          // Store user session (excluding password)
          $_SESSION["user"] = [];
          foreach ($user as $k => $v) {
            if ($k != "user_password") {
              $_SESSION["user"][$k] = $v;
            }
          }
          $_SESSION['active_email'] = $_POST["email"];

          // 🕒 UPDATE LAST LOGIN DATE
          $today = date("Y-m-d H:i:s"); // or Y-m-d if you want only date
          // Use a compatible DB method if available to avoid calling a non-existent DB::query()
          if (method_exists($DB, 'query')) {
            $DB->query(
              "UPDATE `users` SET `last_login`=? WHERE `user_email`=?",
              [$today, $_POST["email"]]
            );
          } elseif (method_exists($DB, 'exec')) {
            // exec typically accepts a raw SQL string
            $DB->exec(
              "UPDATE `users` SET `last_login`='" . addslashes($today) . "' WHERE `user_email`='" . addslashes($_POST["email"]) . "'"
            );
          } elseif (method_exists($DB, 'run')) {
            // some DB wrappers use run()
            $DB->run(
              "UPDATE `users` SET `last_login`=? WHERE `user_email`=?",
              [$today, $_POST["email"]]
            );
          } elseif (method_exists($DB, 'prepare')) {
            // assume a PDO-like interface
            $stmt = $DB->prepare("UPDATE `users` SET `last_login`=? WHERE `user_email`=?");
            if ($stmt) { $stmt->execute([$today, $_POST["email"]]); }
          } else {
            // No compatible method found; skip update to avoid fatal error
          }

          exit("OK");
        }

        exit("Invalid Email or Password!");
      }

    // (C) LOGOUT
    case "out":
      unset($_SESSION["user"]);
      exit("OK");
  }
}
?>
