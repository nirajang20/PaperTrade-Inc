<?php
// set time zone
date_default_timezone_set('America/Chicago');
// (A) LOGIN CHECK
$_ISADMIN = isset($_SESSION["user"]);
if (!$_ISADMIN && !isset($_ISLOGIN)) {
  header("Location: " . URL_HOST . "login.php");
  exit();
}
if ($_ISADMIN && isset($_ISLOGIN)) {
  header("Location: " . URL_HOST);
  exit();
} ?>

<!DOCTYPE html>
<html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard</title>
<script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM="
  crossorigin="anonymous"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<!-- Font Awesome -->
<link rel="stylesheet" href="<?= URL_HOST ?>plugins/fontawesome-free/css/all.min.css">
<!-- Tempusdominus Bootstrap 4 -->
<link rel="stylesheet" href="<?= URL_HOST ?>plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
<!-- iCheck -->
<link rel="stylesheet" href="<?= URL_HOST ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
<!-- JQVMap -->
<link rel="stylesheet" href="<?= URL_HOST ?>plugins/jqvmap/jqvmap.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="<?= URL_HOST ?>dist/css/adminlte.min.css">
<!-- overlayScrollbars -->
<link rel="stylesheet" href="<?= URL_HOST ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
<!-- Daterange picker -->
<link rel="stylesheet" href="<?= URL_HOST ?>plugins/daterangepicker/daterangepicker.css">
<!-- summernote -->
<link rel="stylesheet" href="<?= URL_HOST ?>plugins/summernote/summernote-bs4.min.css">
<!-- Toastr -->
<link rel="stylesheet" href="<?= URL_HOST ?>plugins/toastr/toastr.min.css">
<script src="<?= URL_HOST ?>plugins/toastr/toastr.min.js"></script>
<!-- jQuery -->
<script src="<?= URL_HOST ?>plugins/jquery/jquery.min.js"></script>
<!-- admin.js -->
<script>var url = { host: "<?= URL_HOST ?>", public: "<?= URL_PUBLIC ?>" };</script>
<script async src="<?= URL_PUBLIC ?>admin.js"></script>
<!-- Sweet Alert-->
<script src="
https://cdn.jsdelivr.net/npm/sweetalert2@11.24.1/dist/sweetalert2.all.min.js
"></script>
<link href="
https://cdn.jsdelivr.net/npm/sweetalert2@11.24.1/dist/sweetalert2.min.css
" rel="stylesheet">


<!-- favicon   -->
<link rel="icon" href="<?= URL_HOST ?>dist/img/favicon.png" />

<script>var url = { host: "<?= URL_HOST ?>", public: "<?= URL_PUBLIC ?>" };</script>
<script async src="<?= URL_PUBLIC ?>admin.js?id=1"></script>
<?php include 'fetch.php'; ?>
<?php
$navNotifications = [];
$navNotificationsTotal = 0;
$navNotificationsUnread = 0;

if ($_ISADMIN && isset($conn) && isset($active_user['user_id'])) {
  $navUserId = (int) $active_user['user_id'];
  if ($navUserId > 0) {
    try {
      $navLimit = 6;
      $sqlNav = "SELECT notification_id, title, message, status, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
      $stmtNav = mysqli_prepare($conn, $sqlNav);
      if ($stmtNav) {
        mysqli_stmt_bind_param($stmtNav, "ii", $navUserId, $navLimit);
        mysqli_stmt_execute($stmtNav);
        mysqli_stmt_bind_result($stmtNav, $navId, $navTitle, $navMessage, $navStatus, $navCreatedAt);
        while (mysqli_stmt_fetch($stmtNav)) {
          $row = [
            "notification_id" => $navId,
            "title" => $navTitle,
            "message" => $navMessage,
            "status" => $navStatus ?? 'Unread',
            "created_at" => $navCreatedAt
          ];
          if (strcasecmp($row['status'], 'Read') !== 0) {
            $navNotificationsUnread++;
          }
          $navNotifications[] = $row;
        }
        mysqli_stmt_close($stmtNav);
      }
      $navNotificationsTotal = count($navNotifications);
    } catch (Throwable $th) {
      $navNotifications = [];
      $navNotificationsTotal = 0;
      $navNotificationsUnread = 0;
    }
  }
}
?>
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
          <li class="nav-item dropdown notifications-nav">
            <a class="nav-link" data-toggle="dropdown" href="#" role="button">
              <i class="far fa-bell"></i>
              <span class="badge badge-warning navbar-badge<?= $navNotificationsUnread > 0 ? '' : ' d-none' ?>">
                <?= $navNotificationsUnread ?>
              </span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right notifications-container">
              <span class="dropdown-header">
                <?= $navNotificationsTotal > 0 ? $navNotificationsTotal . ' Notifications' : 'No notifications' ?>
              </span>
<?php if ($navNotificationsTotal === 0) : ?>
                <div class="dropdown-divider"></div>
                <div class="dropdown-item text-center text-muted small">
                  You're all caught up!
                </div>
              <?php else : ?>
                <?php foreach ($navNotifications as $note) :
                  $noteStatus = $note['status'] ?? 'Unread';
                  $noteIsRead = strcasecmp($noteStatus, 'Read') === 0;
                  $noteTitle = $note['title'] !== null && $note['title'] !== '' ? $note['title'] : 'Notification';
                  $noteMessage = $note['message'] ?? '';
                  $noteDate = $note['created_at'] ? date('M j, Y g:i A', strtotime($note['created_at'])) : '';
                  $payload = htmlspecialchars(json_encode([
                    'title' => $noteTitle,
                    'message' => $noteMessage,
                    'created' => $noteDate,
                    'status' => $noteStatus
                  ]), ENT_QUOTES, 'UTF-8');
                ?>
                  <div class="dropdown-divider"></div>
                  <div class="dropdown-item notification-item<?= $noteIsRead ? ' read' : '' ?>"
                    data-id="<?= (int) $note['notification_id'] ?>"
                    data-payload="<?= $payload ?>">
                    <div class="notification-message" title="<?= htmlspecialchars($noteMessage, ENT_QUOTES, 'UTF-8') ?>">
                      <div class="font-weight-semibold">
                        <?= htmlspecialchars($noteTitle, ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <small class="text-muted d-block">
                        <?= htmlspecialchars($noteDate, ENT_QUOTES, 'UTF-8') ?>
                      </small>
                      <small class="text-secondary d-block text-truncate">
                        <?= htmlspecialchars($noteMessage, ENT_QUOTES, 'UTF-8') ?>
                      </small>
                    </div>
                    <div class="notification-actions text-right mt-1">
                      <a href="#" class="text-success mr-2 notification-read" title="Mark as Read"><i
                          class="fas fa-check-circle"></i></a>
                      <a href="#" class="text-danger notification-delete" title="Delete"><i class="fas fa-trash"></i></a>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
              <div class="dropdown-divider"></div>
              <a href="<?= URL_HOST ?>my-account/notifications.php" class="dropdown-item dropdown-footer text-primary">View all notifications</a>
            </div>
          </li>
          <style>
            .notification-item.read .notification-message {
              opacity: 0.6;
            }

            .notification-actions a {
              text-decoration: none;
            }

            .notification-item {
              cursor: pointer;
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

              // Notifications Dropdown Logic
              const notificationContainer = document.querySelector('.notifications-container');
              const notificationBadge = document.querySelector('.notifications-nav .navbar-badge');
              const notificationHeader = notificationContainer ? notificationContainer.querySelector('.dropdown-header') : null;

              const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = value;
                return div.innerHTML;
              };

              function updateNotificationCount() {
                if (!notificationContainer || !notificationBadge || !notificationHeader) return;
                const total = notificationContainer.querySelectorAll('.notification-item').length;
                const unread = notificationContainer.querySelectorAll('.notification-item:not(.read)').length;
                notificationBadge.textContent = unread;
                if (unread > 0) {
                  notificationBadge.classList.remove('d-none');
                } else {
                  notificationBadge.classList.add('d-none');
                }
                notificationHeader.textContent = total > 0 ? `${total} Notifications` : 'No notifications';
              }

              function postNotificationAction(action, notificationId, onSuccess) {
                if (!notificationId) return;
                const formData = new FormData();
                formData.append('req', action);
                formData.append('notification_id', notificationId);
                fetch(url.host + 'ajax-session.php', { method: 'POST', body: formData })
                  .then((res) => res.text())
                  .then((text) => {
                    if (text.trim() === 'OK') {
                      if (typeof onSuccess === 'function') {
                        onSuccess();
                      }
                    } else {
                      Swal.fire('Notification', text || 'Unable to update notification.', 'error');
                    }
                  })
                  .catch((err) => console.error(err));
              }

              function markNotificationAsRead(item) {
                if (!item || item.classList.contains('read')) {
                  return;
                }
                const notificationId = item.getAttribute('data-id');
                postNotificationAction('notification_mark', notificationId, function () {
                  item.classList.add('read');
                  updateNotificationCount();
                });
              }

              function deleteNotification(item) {
                if (!item) return;
                const notificationId = item.getAttribute('data-id');
                postNotificationAction('notification_delete', notificationId, function () {
                  const divider = item.previousElementSibling;
                  if (divider && divider.classList.contains('dropdown-divider')) {
                    divider.remove();
                  }
                  item.remove();
                  updateNotificationCount();
                });
              }

              function showNotificationDetails(item) {
                if (!item) return;
                const payload = item.getAttribute('data-payload');
                if (!payload) return;
                try {
                  const data = JSON.parse(payload);
                  const message = escapeHtml(data.message || '');
                  const created = escapeHtml(data.created || '');
                  Swal.fire({
                    title: data.title || 'Notification',
                    html: `<p class="text-left">${message.replace(/\n/g, '<br>')}</p>${created ? `<p class="text-muted text-left mb-0"><small>${created}</small></p>` : ''}`,
                    icon: 'info',
                    confirmButtonText: 'Close'
                  });
                  markNotificationAsRead(item);
                } catch (error) {
                  console.error('Unable to parse notification payload', error);
                }
              }

              if (notificationContainer) {
                notificationContainer.addEventListener('click', function (event) {
                  const action = event.target.closest('.notification-read, .notification-delete');
                  const item = event.target.closest('.notification-item');
                  if (action && item) {
                    event.preventDefault();
                    if (action.classList.contains('notification-read')) {
                      markNotificationAsRead(item);
                    }
                    if (action.classList.contains('notification-delete')) {
                      deleteNotification(item);
                    }
                    return;
                  }

                  if (item && !event.target.closest('.notification-actions')) {
                    event.preventDefault();
                    showNotificationDetails(item);
                  }
                });

                updateNotificationCount();
              }
            });
          </script>

          <!-- User Profile Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
              <img src="<?= URL_HOST ?>dist/img/<?= $active_user['avatar'] ?>" alt="User" class="img-size-32 img-circle">
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
      <aside class="main-sidebar elevation-4 bg-gradient-dark sidebar-dark-primary"
        style="background: #4d6d8e linear-gradient(180deg, #00193d, #000000) repeat-x !important;">
        <!-- Brand Logo -->
        <a href="<?= URL_HOST ?>" class="brand-link text-center border-bottom border-secondary py-3">
          <img src="<?= URL_HOST ?>dist/img/logo-horizontal.png" alt="PaperTrade Logo" class="img-fluid"
            style="max-height:40px;">
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
          <!-- User Panel -->
          <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center border-bottom border-secondary">
            <div class="image">
              <img src="<?= URL_HOST ?>dist/img/<?= $active_user['avatar'] ?>"
                class="img-circle elevation-3 border border-success" alt="User Image">
            </div>
            <div class="info">
              <a href="<?= URL_HOST ?>my-account/profile.php"
                class="d-block text-light font-weight-bold hover-glow"><?= $active_user['user_name'] ?></a>
              <span class="text-xs text-muted">Active Trader</span>
            </div>
          </div>

          <!-- Sidebar Menu -->
          <nav class="mt-2" id="sidebar">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

              <!-- Dashboard -->
              <li class="nav-item">
                <a href="<?= URL_HOST ?>" class="nav-link">
                  <i class="nav-icon fas fa-home text-success"></i>
                  <p>Dashboard</p>
                </a>
              </li>

              <!-- Trading -->
              <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-chart-line text-warning"></i>
                  <p>Trading <i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item"><a href="<?= URL_HOST ?>trading/buy.php" class="nav-link"><i
                        class="fas fa-coins nav-icon text-warning"></i>
                      <p>Buy Stocks/Crypto</p>
                    </a></li>
                  <li class="nav-item"><a href="<?= URL_HOST ?>trading/sell.php" class="nav-link"><i
                        class="fas fa-hand-holding-usd nav-icon text-warning"></i>
                      <p>Sell Stocks/Crypto</p>
                    </a></li>
                  <li class="nav-item"><a href="<?= URL_HOST ?>trading/reminder.php" class="nav-link"><i
                        class="fas fa-bell nav-icon text-warning"></i>
                      <p>Reminder</p>
                    </a></li>
                  <li class="nav-item"><a href="<?= URL_HOST ?>trading/search.php" class="nav-link"><i
                        class="fas fa-search-dollar nav-icon text-warning"></i>
                      <p>Search Stocks</p>
                    </a></li>
                  <li class="nav-item"><a href="<?= URL_HOST ?>trading/transactions.php" class="nav-link"><i
                        class="fas fa-history nav-icon text-warning"></i>
                      <p>My Transactions</p>
                    </a></li>
                </ul>
              </li>

              <!-- Portfolio -->
              <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-briefcase text-info"></i>
                  <p>Portfolio <i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item"><a href="<?= URL_HOST ?>portfolio/my-holdings.php" class="nav-link"><i
                        class="fas fa-wallet nav-icon text-info"></i>
                      <p>My Holdings</p>
                    </a></li>
                  <li class="nav-item"><a href="<?= URL_HOST ?>portfolio/watchlist.php" class="nav-link"><i
                        class="fas fa-eye nav-icon text-info"></i>
                      <p>Watchlist</p>
                    </a></li>
                  <li class="nav-item"><a href="<?= URL_HOST ?>portfolio/performance.php" class="nav-link"><i
                        class="fas fa-chart-pie nav-icon text-info"></i>
                      <p>Performance</p>
                    </a></li>
                  <li class="nav-item"><a href="<?= URL_HOST ?>portfolio/export.php" class="nav-link"><i
                        class="fas fa-file-export nav-icon text-info"></i>
                      <p>Export Portfolio</p>
                    </a></li>
                  <li class="nav-item"><a href="<?= URL_HOST ?>portfolio/screener.php" class="nav-link"><i
                        class="fas fa-filter nav-icon text-info"></i>
                      <p>Stock Screener</p>
                    </a></li>
                </ul>
              </li>

              <!-- My Account -->
              <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-user text-danger"></i>
                  <p>My Account <i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item"><a href="<?= URL_HOST ?>my-account/profile.php" class="nav-link"><i
                        class="fas fa-id-card nav-icon text-danger"></i>
                      <p>Profile</p>
                    </a></li>
                  <li class="nav-item"><a href="<?= URL_HOST ?>notifications.php" class="nav-link"><i
                        class="fas fa-bell nav-icon text-danger"></i>
                      <p>Notifications</p>
                    </a></li>
                  <li class="nav-item"><a href="<?= URL_HOST ?>my-account/activities.php" class="nav-link"><i
                        class="fas fa-tasks nav-icon text-danger"></i>
                      <p>Activities</p>
                    </a></li>
                  <li class="nav-item"><a href="javascript:void(0)" class="nav-link" onclick="admin.bye()"><i
                        class="fas fa-sign-out-alt nav-icon text-danger"></i>
                      <p>Logout</p>
                    </a></li>
                </ul>
              </li>
            <?php if ($active_user['role'] == 'Admin') { ?>
              <!-- Admin Management Start -->
              <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-user-shield text-purple"></i>
                  <p>Admin Management <i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>admin/all-users.php" class="nav-link">
                      <i class="fas fa-users nav-icon text-purple"></i>
                      <p>All Users</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>admin/notifications.php" class="nav-link">
                      <i class="fas fa-bell nav-icon text-purple"></i>
                      <p>Notifications</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>admin/general-settings.php" class="nav-link">
                      <i class="fas fa-cog nav-icon text-purple"></i>
                      <p>General</p>
                    </a>
                  </li>
                </ul>
              </li>
              <!-- Admin Management Stop -->
            <?php } ?>
            </ul>
          </nav>
        </div>
      </aside>


      <!-- Active link highlight -->
      <script type="text/javascript">
        const currentlocation = location.href;
        const menuItem = document.querySelectorAll('.nav-link');
        menuItem.forEach(item => {
          if (item.href === currentlocation) {
            item.classList.add('active');
            item.closest('.has-treeview')?.classList.add('menu-open');
            item.closest('.nav-treeview')?.previousElementSibling?.classList.add('active');
            item.querySelector('p')?.classList.add('text-success');
            item.closest('.nav-treeview')?.previousElementSibling?.querySelector('p')?.classList.add('text-success');
            let parentText = item.closest('.nav-treeview')?.previousElementSibling?.querySelector('p')?.textContent;
            if (parentText) {
              document.title += " | " + parentText;
            }
          }
        });
      </script>

      <style>
        /* Sidebar Enhancements */
        .main-sidebar {
          background: linear-gradient(180deg, #0f2027, #203a43, #2c5364);
        }

        .main-sidebar .nav-link.active {
          background-color: rgba(255, 255, 255, 0.1) !important;
          border-left: 3px solid #00ff88 !important;
          font-weight: 600;
        }

        .main-sidebar .nav-link:hover {
          background-color: rgba(255, 255, 255, 0.05);
          color: #00ffaa !important;
          transition: 0.3s;
        }

        .main-sidebar .hover-glow:hover {
          text-shadow: 0 0 8px #00ffcc;
        }

        .main-sidebar .nav-icon {
          width: 22px;
        }
      </style>

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
      <?php } ?>

      <!-- (D2) PAGE CONTENTS -->
