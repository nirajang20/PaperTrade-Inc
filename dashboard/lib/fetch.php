<?php if ($_ISADMIN) { 
// active user details 
$active_email = $_SESSION['active_email'];
$user_details = mysqli_query($conn,"SELECT * FROM users WHERE user_email='$active_email'"); 
$active_user = mysqli_fetch_array($user_details);

// for active panel
$active_page = basename($_SERVER['PHP_SELF']);
$active_page = explode('.',$active_page);


} ?>