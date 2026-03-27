<?php
if ($_ISADMIN) {

  // default user state
  $active_user = null;

  // get active page
  $active_page = basename($_SERVER['PHP_SELF']);
  $active_page = explode('.', $active_page);

  // make sure session user exists
  if (
    isset($_SESSION['user']) &&
    is_array($_SESSION['user']) &&
    isset($_SESSION['user']['user_id'])
  ) {
    $active_user_id = (int) $_SESSION['user']['user_id'];

    if ($active_user_id > 0) {
      $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ? LIMIT 1");

      if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $active_user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
          $active_user = mysqli_fetch_assoc($result);
        }

        mysqli_stmt_close($stmt);
      }
    }
  }

  // fallback safety so page does not break
  if (!$active_user) {
    $active_user = [
      'user_id' => 0,
      'user_name' => 'User',
      'user_email' => '',
      'avatar' => 'avatar_01.png',
      'role' => 'User'
    ];
  }
}
?>