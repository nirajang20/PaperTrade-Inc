<?php
// set time zone
date_default_timezone_set('America/Chicago');
// (A) LOGIN CHECK
$_ISADMIN = isset($_SESSION["user"]);
if (!$_ISADMIN && !isset($_ISLOGIN)) {
  header("Location: ".URL_HOST."login.php"); exit();
}
if ($_ISADMIN && isset($_ISLOGIN)) {
  header("Location: ".URL_HOST); exit();
} ?>

<!DOCTYPE html>
<html>
<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>
  <script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM=" crossorigin="anonymous"></script>
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?=URL_HOST?>plugins/fontawesome-free/css/all.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="<?=URL_HOST?>plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="<?=URL_HOST?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="<?=URL_HOST?>plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?=URL_HOST?>dist/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="<?=URL_HOST?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="<?=URL_HOST?>plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="<?=URL_HOST?>plugins/summernote/summernote-bs4.min.css">
  <!-- Toastr -->
  <link rel="stylesheet" href="<?=URL_HOST?>plugins/toastr/toastr.min.css">
  <script src="<?=URL_HOST?>plugins/toastr/toastr.min.js"></script>
  <!-- jQuery -->
  <script src="<?=URL_HOST?>plugins/jquery/jquery.min.js"></script>
  <!-- admin.js -->
    <script>var url = {host:"<?=URL_HOST?>",public:"<?=URL_PUBLIC?>"};</script>
    <script async src="<?=URL_PUBLIC?>admin.js"></script>
  <!-- Sweet Alert-->
  <script src="
https://cdn.jsdelivr.net/npm/sweetalert2@11.24.1/dist/sweetalert2.all.min.js
"></script>
<link href="
https://cdn.jsdelivr.net/npm/sweetalert2@11.24.1/dist/sweetalert2.min.css
" rel="stylesheet">


  <!-- favicon   -->
       <link rel="icon" href="<?=URL_HOST?>dist/img/favicon.png" />

    <script>var url = {host:"<?=URL_HOST?>",public:"<?=URL_PUBLIC?>"};</script>
    <script async src="<?=URL_PUBLIC?>admin.js?id=1"></script>
     <?php  include 'fetch.php'; ?>
  </head>
  <body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php if ($_ISADMIN) { ?>
    <!-- (C) SIDE BAR -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
  </ul>

  <!-- Right navbar links -->
  <ul class="navbar-nav ml-auto">
    <!-- Dark/Light Mode Toggle -->
    <li class="nav-item">
      <a class="nav-link" id="dark-mode-toggle" href="#" role="button">
        <i class="fas fa-moon"></i>
      </a>
    </li>

    <!-- Notifications Dropdown -->
    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
      <i class="far fa-bell"></i>
      <span class="badge badge-warning navbar-badge">4</span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right notifications-container">
      <span class="dropdown-header">4 Notifications</span>
      <div class="dropdown-divider"></div>
      <div class="dropdown-item notification-item">
        <div class="notification-message">
        <i class="fas fa-envelope mr-2"></i> New message from support
        </div>
        <div class="notification-actions text-right mt-1">
        <a href="#" class="text-success mr-2 notification-read" title="Mark as Read"><i class="fas fa-check-circle"></i></a>
        <a href="#" class="text-danger notification-delete" title="Delete"><i class="fas fa-trash"></i></a>
        </div>
      </div>
      <div class="dropdown-divider"></div>
      <div class="dropdown-item notification-item">
        <div class="notification-message">
        <i class="fas fa-exclamation-triangle mr-2"></i> Margin call warning
        </div>
        <div class="notification-actions text-right mt-1">
        <a href="#" class="text-success mr-2 notification-read" title="Mark as Read"><i class="fas fa-check-circle"></i></a>
        <a href="#" class="text-danger notification-delete" title="Delete"><i class="fas fa-trash"></i></a>
        </div>
      </div>
      <div class="dropdown-divider"></div>
      <div class="dropdown-item notification-item">
        <div class="notification-message">
        <i class="fas fa-file-invoice-dollar mr-2"></i> Dividend for MSFT posted 
        </div>
        <div class="notification-actions text-right mt-1">
        <a href="#" class="text-success mr-2 notification-read" title="Mark as Read"><i class="fas fa-check-circle"></i></a>
        <a href="#" class="text-danger notification-delete" title="Delete"><i class="fas fa-trash"></i></a>
        </div>
      </div>
      <div class="dropdown-divider"></div>
      <div class="dropdown-item notification-item">
        <div class="notification-message">
        <i class="fas fa-chart-line mr-2"></i> GOOGL stock up 5%
        </div>
        <div class="notification-actions text-right mt-1">
        <a href="#" class="text-success mr-2 notification-read" title="Mark as Read"><i class="fas fa-check-circle"></i></a>
        <a href="#" class="text-danger notification-delete" title="Delete"><i class="fas fa-trash"></i></a>
        </div>
      </div>
      <div class="dropdown-divider"></div>
      </div>
    </li>

    <style>
      .notification-item.read .notification-message {
      opacity: 0.6;
      }
      .notification-actions a {
      text-decoration: none;
      }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Dark Mode Toggle
      const darkModeToggle = document.getElementById('dark-mode-toggle');
      const body = document.body;
      const darkModeIcon = darkModeToggle.querySelector('i');

      const setTheme = (theme) => {
        if (theme === 'dark') {
          body.classList.add('dark-mode');
          darkModeIcon.classList.remove('fa-moon');
          darkModeIcon.classList.add('fa-sun');
          localStorage.setItem('theme', 'dark');
        } else {
          body.classList.remove('dark-mode');
          darkModeIcon.classList.remove('fa-sun');
          darkModeIcon.classList.add('fa-moon');
          localStorage.setItem('theme', 'light');
        }
      };

      darkModeToggle.addEventListener('click', function (e) {
        e.preventDefault();
        const currentTheme = body.classList.contains('dark-mode') ? 'light' : 'dark';
        setTheme(currentTheme);
      });

      // Apply saved theme on load
      const savedTheme = localStorage.getItem('theme') || 'light';
      setTheme(savedTheme);

      // Notifications
      const notificationContainer = document.querySelector('.notifications-container');
      const notificationBadge = document.querySelector('.navbar-badge');
      const notificationHeader = document.querySelector('.dropdown-header');

      function updateNotificationCount() {
        const count = notificationContainer.querySelectorAll('.notification-item').length;
        notificationBadge.textContent = count;
        notificationHeader.textContent = `${count} Notifications`;
        if (count === 0) {
          notificationBadge.style.display = 'none';
          notificationHeader.textContent = 'No new notifications';
        }
      }

      notificationContainer.addEventListener('click', function (e) {
        const action = e.target.closest('.notification-read, .notification-delete');
        if (!action) return;

        e.preventDefault();
        const notificationItem = action.closest('.notification-item');
        const divider = notificationItem.previousElementSibling;

        if (action.classList.contains('notification-read')) {
          notificationItem.classList.toggle('read');
          action.classList.toggle('text-muted');
          action.classList.toggle('text-success');
        }

        if (action.classList.contains('notification-delete')) {
          notificationItem.remove();
          if (divider && divider.classList.contains('dropdown-divider')) {
            divider.remove();
          }
          updateNotificationCount();
        }
      });
    });
    </script>

    <!-- User Profile Dropdown -->
    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <img src="<?=URL_HOST?>dist/img/<?=$active_user['avatar']?>" alt="User" class="img-size-32 img-circle">
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <div class="dropdown-header text-center">
          <strong id="user-name">John Doe</strong><br>
          <small id="user-role" class="text-muted">Trader</small>
        </div>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item">
          <i class="fas fa-wallet mr-2"></i> Available Cash 
          <span class="float-right text-success" id="user-cash">$25,000</span>
        </a>
        <div class="dropdown-divider"></div>
        <a href="javascript:void(0)" class="dropdown-item dropdown-footer text-danger" onclick="admin.bye()">
          <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
      </div>
    </li>
  </ul>

</nav>

    <?php } ?>

    <!-- (D) MAIN CONTENTS -->
   
      <?php if ($_ISADMIN) { ?>
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?=URL_HOST?>" class="brand-link">
      <!-- <img src="<?=URL_HOST?>dist/img/logo-horizontal.png" alt="PaperTrade Logo" class="brand-image img-circle elevation-3" style="opacity: .8"> -->
      <span class="brand-text font-weight-light"><img src="<?=URL_HOST?>dist/img/logo-horizontal.png" height="40%" width="100%"></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="<?=URL_HOST?>dist/img/<?=$active_user['avatar']?>" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="<?=URL_HOST?>my-account/profile.php" class="d-block"><?=$active_user['user_name']?></a>
        </div>
      </div>
      <!-- Sidebar Menu -->
      <nav class="mt-2" id="sidebar">
  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

    <!-- Dashboard -->
    <li class="nav-item">
      <a href="<?=URL_HOST?>" class="nav-link">
        <i class="nav-icon fas fa-tachometer-alt"></i>
        <p>Dashboard</p>
      </a>
    </li>

    <!-- Trading -->
    <li class="nav-item has-treeview">
      <a href="#" class="nav-link">
        <i class="nav-icon fas fa-chart-line"></i>
        <p>
          Trading
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item"><a href="<?=URL_HOST?>trading/buy.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Buy Stocks/Crypto</p></a></li>
        <li class="nav-item"><a href="<?=URL_HOST?>trading/sell.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Sell Stocks/Crypto</p></a></li>
        <li class="nav-item"><a href="<?=URL_HOST?>trading/reminder.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Reminder</p></a></li>
        <li class="nav-item"><a href="<?=URL_HOST?>trading/search.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Search Stocks</p></a></li>
        <li class="nav-item"><a href="<?=URL_HOST?>trading/transactions.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>My Transactions</p></a></li>
      </ul>
    </li>

    <!-- Portfolio -->
    <li class="nav-item has-treeview">
      <a href="#" class="nav-link">
        <i class="nav-icon fas fa-briefcase"></i>
        <p>
          Portfolio
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item"><a href="<?=URL_HOST?>portfolio/my-holdings.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>My Holdings</p></a></li>
        <li class="nav-item"><a href="<?=URL_HOST?>portfolio/watchlist.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Watchlist</p></a></li>
        <li class="nav-item"><a href="<?=URL_HOST?>portfolio/performance.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Performance</p></a></li>
        <li class="nav-item"><a href="<?=URL_HOST?>portfolio/export.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Export Portfolio</p></a></li>
      </ul>
    </li>

    <!-- Market Tools -->
    <li class="nav-item has-treeview">
      <a href="#" class="nav-link">
        <i class="nav-icon fas fa-tools"></i>
        <p>
          Market Tools
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item"><a href="<?=URL_HOST?>screener.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Stock Screener</p></a></li>
        <li class="nav-item"><a href="<?=URL_HOST?>compare.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Compare Stocks</p></a></li>
        <li class="nav-item"><a href="<?=URL_HOST?>calendar.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Market Calendar</p></a></li>
        <li class="nav-item"><a href="<?=URL_HOST?>news.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>News & Announcements</p></a></li>
      </ul>

      
      
      <!-- Learning / Gamification -->
      <li class="nav-item has-treeview">
        <a href="#" class="nav-link">
          <i class="nav-icon fas fa-graduation-cap"></i>
          <p>
            Learning Zone
            <i class="right fas fa-angle-left"></i>
          </p>
        </a>
        <ul class="nav nav-treeview">
          <li class="nav-item"><a href="<?=URL_HOST?>tutorials.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Tutorials</p></a></li>
          <li class="nav-item"><a href="<?=URL_HOST?>challenges.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Trading Challenges</p></a></li>
          <li class="nav-item"><a href="<?=URL_HOST?>leaderboard.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Leaderboard</p></a></li>
        </ul>
      </li>
      
      <!-- Account -->
      <li class="nav-item has-treeview">
        <a href="#" class="nav-link">
          <i class="nav-icon fas fa-user"></i>
          <p>
            My Account
            <i class="right fas fa-angle-left"></i>
          </p>
        </a>
        <ul class="nav nav-treeview">
          <li class="nav-item"><a href="<?=URL_HOST?>my-account/profile.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Profile</p></a></li>
          <li class="nav-item"><a href="<?=URL_HOST?>settings.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Settings</p></a></li>
          <li class="nav-item"><a href="<?=URL_HOST?>notifications.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Notifications</p></a></li>
          <li class="nav-item"><a href="javascript:void(0)" class="nav-link" onclick="admin.bye()"><i class="far fa-circle nav-icon"></i><p>Logout</p></a></li>
        </ul>
      </li>
      
      <li class="nav-item">
      <a href="<?=URL_HOST?>test-page.php" class="nav-link">
        <i class="nav-icon fas fa-tachometer-alt"></i>
        <p>Test Page</p>
      </a>
    </li>
  </ul>
</nav>

      <script type="text/javascript">
    const currentlocation = location.href;
    const menuItem = document.querySelectorAll('a');
    const menuLength = menuItem.length
    for (let i = 0; i<menuLength; i++){
      if (menuItem[i].href === currentlocation) {
        menuItem[i].className = "nav-link active" 
         }
    }
  </script>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
      <?php } ?>

      <!-- (D2) PAGE CONTENTS -->
      
