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
}
?>

<!DOCTYPE html>
<html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<head>
  <title>Dashboard</title>

  <script src="https://code.jquery.com/jquery-3.6.3.js"
    integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM="
    crossorigin="anonymous"></script>

  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

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
  <script>
    var url = { host: "<?= URL_HOST ?>", public: "<?= URL_PUBLIC ?>" };
  </script>
  <script async src="<?= URL_PUBLIC ?>admin.js"></script>

  <!-- Sweet Alert -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.24.1/dist/sweetalert2.all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.24.1/dist/sweetalert2.min.css" rel="stylesheet">

  <!-- favicon -->
  <link rel="icon" href="<?= URL_HOST ?>dist/img/favicon.png" />

  <script>
    var url = { host: "<?= URL_HOST ?>", public: "<?= URL_PUBLIC ?>" };
  </script>
  <script async src="<?= URL_PUBLIC ?>admin.js?id=1"></script>

  <?php include 'fetch.php'; ?>

  <?php
  // =========================
  // AVATAR FIX ONLY
  // =========================
  function dashboard_avatar_url($avatarFilename)
  {
    $avatarFilename = trim((string)$avatarFilename);

    // fallback if empty
    if ($avatarFilename === '') {
      return 'http://localhost/trade/img/avatars/avatar_01.png';
    }

    // default avatar path: /trade/img/avatars/
    $defaultAvatarPath = dirname(__DIR__, 2) . '/img/avatars/' . $avatarFilename;

    // uploaded avatar path: /trade/dashboard/dist/img/
    $uploadedAvatarPath = dirname(__DIR__) . '/dist/img/' . $avatarFilename;

    if (file_exists($defaultAvatarPath)) {
      return 'http://localhost/trade/img/avatars/' . rawurlencode($avatarFilename);
    }

    if (file_exists($uploadedAvatarPath)) {
      return URL_HOST . 'dist/img/' . rawurlencode($avatarFilename);
    }

    // final fallback
    return 'http://localhost/trade/img/avatars/avatar_01.png';
  }

  $activeAvatarUrl = dashboard_avatar_url($active_user['avatar'] ?? '');
  ?>

  <?php
  $navNotifications = [];
  $navNotificationsTotal = 0;
  $navNotificationsUnread = 0;

  if ($_ISADMIN && isset($conn) && isset($active_user['user_id'])) {
    $navUserId = (int)$active_user['user_id'];
    if ($navUserId > 0) {
      try {
        $navLimit = 6;
        $sqlNav = "SELECT notification_id, title, message, status, created_at 
                   FROM notifications 
                   WHERE user_id = ? 
                   ORDER BY created_at DESC 
                   LIMIT ?";
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

  <!-- FullCalendar CSS -->
  <link rel="stylesheet" href="<?= URL_HOST ?>plugins/fullcalendar/main.css">

  <!-- moment.js -->
  <script src="<?= URL_HOST ?>plugins/moment/moment.min.js"></script>

  <!-- FullCalendar JS -->
  <script src="<?= URL_HOST ?>plugins/fullcalendar/main.js"></script>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">

    <?php if ($_ISADMIN) { ?>
      <!-- TOP NAVBAR -->
      <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
              <i class="fas fa-bars"></i>
            </a>
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
              <span class="dropdown-header" data-role="notifications-header">
                <?= $navNotificationsTotal > 0 ? $navNotificationsTotal . ' Notifications' : 'No notifications' ?>
              </span>

              <div class="notifications-body">
                <?php if ($navNotificationsTotal === 0): ?>
                  <div class="dropdown-divider"></div>
                  <div class="dropdown-item text-center text-muted small notifications-empty">
                    You're all caught up!
                  </div>
                <?php else: ?>
                  <?php foreach ($navNotifications as $note):
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
                      data-id="<?= (int)$note['notification_id'] ?>"
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
                        <a href="#"
                          class="notification-toggle<?= $noteIsRead ? ' text-secondary' : ' text-success' ?>"
                          title="<?= $noteIsRead ? 'Mark as Unread' : 'Mark as Read' ?>">
                          <i class="fas fa-check-circle"></i>
                        </a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>

              <div class="dropdown-divider"></div>
              <a href="<?= URL_HOST ?>my-account/notifications.php"
                class="dropdown-item dropdown-footer text-primary">View all notifications</a>
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

              const savedTheme = localStorage.getItem('theme') || 'light';
              setTheme(savedTheme);

              const notificationContainer = document.querySelector('.notifications-container');
              const notificationBadge = document.querySelector('.notifications-nav .navbar-badge');
              const notificationHeader = notificationContainer ? notificationContainer.querySelector('.dropdown-header') : null;
              const notificationBody = notificationContainer ? notificationContainer.querySelector('.notifications-body') : null;
              const NOTIFICATION_POLL_INTERVAL = 60000;
              let notificationPollTimer = null;
              let notificationFetchInProgress = false;

              const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = value;
                return div.innerHTML;
              };

              function updateNotificationCount() {
                if (!notificationContainer) return;
                const total = notificationContainer.querySelectorAll('.notification-item').length;
                const unread = notificationContainer.querySelectorAll('.notification-item:not(.read)').length;

                if (notificationBadge) {
                  notificationBadge.textContent = unread;
                  if (unread > 0) {
                    notificationBadge.classList.remove('d-none');
                  } else {
                    notificationBadge.classList.add('d-none');
                  }
                }

                if (notificationHeader) {
                  notificationHeader.textContent = total > 0 ? `${total} Notifications` : 'No notifications';
                }
              }

              function postNotificationAction(action, notificationId, onSuccess, extraData) {
                if (!notificationId) return;

                const formData = new FormData();
                formData.append('req', action);
                formData.append('notification_id', notificationId);

                if (extraData && typeof extraData === 'object') {
                  Object.keys(extraData).forEach((key) => {
                    if (extraData[key] !== undefined && extraData[key] !== null) {
                      formData.append(key, extraData[key]);
                    }
                  });
                }

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

              function applyNotificationReadState(item, isRead) {
                if (!item) return;

                item.classList.toggle('read', isRead);
                const toggleLink = item.querySelector('.notification-toggle');

                if (toggleLink) {
                  toggleLink.classList.toggle('text-success', !isRead);
                  toggleLink.classList.toggle('text-secondary', isRead);
                  toggleLink.setAttribute('title', isRead ? 'Mark as Unread' : 'Mark as Read');
                }

                const payload = item.getAttribute('data-payload');
                if (payload) {
                  try {
                    const data = JSON.parse(payload);
                    data.status = isRead ? 'Read' : 'Unread';
                    item.setAttribute('data-payload', JSON.stringify(data));
                  } catch (error) {
                    console.warn('Unable to update notification payload', error);
                  }
                }
              }

              function renderNotifications(list) {
                if (!notificationBody) return;
                notificationBody.innerHTML = '';

                if (!Array.isArray(list) || list.length === 0) {
                  const divider = document.createElement('div');
                  divider.className = 'dropdown-divider';
                  notificationBody.appendChild(divider);

                  const empty = document.createElement('div');
                  empty.className = 'dropdown-item text-center text-muted small notifications-empty';
                  empty.textContent = "You're all caught up!";
                  notificationBody.appendChild(empty);
                  return;
                }

                list.forEach((note) => {
                  const divider = document.createElement('div');
                  divider.className = 'dropdown-divider';
                  notificationBody.appendChild(divider);

                  const status = typeof note.status === 'string' ? note.status : 'Unread';
                  const isRead = status.toLowerCase() === 'read';
                  const titleText = note.title || 'Notification';
                  const messageText = note.message || '';
                  const createdText = note.created_display || '';

                  const item = document.createElement('div');
                  item.className = 'dropdown-item notification-item' + (isRead ? ' read' : '');
                  item.dataset.id = String(note.notification_id || '');
                  item.setAttribute('data-payload', JSON.stringify({
                    title: titleText,
                    message: messageText,
                    created: createdText,
                    status: status
                  }));

                  const messageWrapper = document.createElement('div');
                  messageWrapper.className = 'notification-message';
                  if (messageText) {
                    messageWrapper.setAttribute('title', messageText);
                  }

                  const titleEl = document.createElement('div');
                  titleEl.className = 'font-weight-semibold';
                  titleEl.textContent = titleText;

                  const dateEl = document.createElement('small');
                  dateEl.className = 'text-muted d-block';
                  dateEl.textContent = createdText;

                  const messageEl = document.createElement('small');
                  messageEl.className = 'text-secondary d-block text-truncate';
                  messageEl.textContent = messageText;

                  messageWrapper.appendChild(titleEl);
                  messageWrapper.appendChild(dateEl);
                  messageWrapper.appendChild(messageEl);

                  const actionsWrapper = document.createElement('div');
                  actionsWrapper.className = 'notification-actions text-right mt-1';

                  const toggleLink = document.createElement('a');
                  toggleLink.href = '#';
                  toggleLink.className = 'notification-toggle ' + (isRead ? 'text-secondary' : 'text-success');
                  toggleLink.setAttribute('title', isRead ? 'Mark as Unread' : 'Mark as Read');

                  const iconEl = document.createElement('i');
                  iconEl.className = 'fas fa-check-circle';
                  toggleLink.appendChild(iconEl);

                  actionsWrapper.appendChild(toggleLink);
                  item.appendChild(messageWrapper);
                  item.appendChild(actionsWrapper);
                  notificationBody.appendChild(item);
                });
              }

              function toggleNotificationState(item, targetStatus) {
                if (!item) return;
                const notificationId = item.getAttribute('data-id');

                postNotificationAction(
                  'notification_mark',
                  notificationId,
                  function () {
                    applyNotificationReadState(item, targetStatus === 'read');
                    updateNotificationCount();
                  },
                  { status: targetStatus }
                );
              }

              function markNotificationAsRead(item) {
                if (!item || item.classList.contains('read')) return;
                toggleNotificationState(item, 'read');
              }

              function fetchNotifications() {
                if (!notificationContainer || notificationFetchInProgress) return;

                notificationFetchInProgress = true;
                const formData = new FormData();
                formData.append('req', 'notification_fetch');
                formData.append('limit', '6');

                fetch(url.host + 'ajax-session.php', { method: 'POST', body: formData })
                  .then((res) => res.text())
                  .then((text) => {
                    let data;
                    try {
                      data = JSON.parse(text);
                    } catch (error) {
                      throw new Error(text || 'Invalid response');
                    }

                    if (!data || data.success !== true || !Array.isArray(data.notifications)) {
                      if (data && data.message) {
                        console.warn('Notification fetch:', data.message);
                      }
                      return;
                    }

                    renderNotifications(data.notifications);
                    updateNotificationCount();
                  })
                  .catch((err) => {
                    console.error('Notification fetch failed', err);
                  })
                  .finally(() => {
                    notificationFetchInProgress = false;
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

                  if ((data.status || '').toLowerCase() !== 'read') {
                    markNotificationAsRead(item);
                  }
                } catch (error) {
                  console.warn('Unable to read notification payload', error);
                }
              }

              if (notificationContainer) {
                notificationContainer.addEventListener('click', function (event) {
                  const toggleLink = event.target.closest('.notification-toggle');
                  if (toggleLink) {
                    event.preventDefault();
                    event.stopPropagation();
                    const item = toggleLink.closest('.notification-item');
                    if (!item) return;
                    const shouldMarkRead = !item.classList.contains('read');
                    toggleNotificationState(item, shouldMarkRead ? 'read' : 'unread');
                    return;
                  }

                  const item = event.target.closest('.notification-item');
                  if (item) {
                    event.preventDefault();
                    showNotificationDetails(item);
                  }
                });

                fetchNotifications();
                notificationPollTimer = window.setInterval(fetchNotifications, NOTIFICATION_POLL_INTERVAL);
              }
            });
          </script>

          <!-- USER DROPDOWN -->
          <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
              <!-- AVATAR FIXED HERE -->
              <img src="<?= htmlspecialchars($activeAvatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                alt="User"
                class="img-size-32 img-circle">
              <span class="d-none d-md-inline"><?= htmlspecialchars($active_user['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
            </a>

            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
              <li class="user-header bg-primary">
                <img src="<?= htmlspecialchars($activeAvatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                  class="img-circle elevation-2"
                  alt="User Image">

                <p>
                  <?= htmlspecialchars($active_user['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>
                  <small><?= htmlspecialchars($active_user['user_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                </p>
              </li>

              <li class="user-footer">
                <a href="<?= URL_HOST ?>my-account/profile.php" class="btn btn-default btn-flat">Profile</a>
                <a href="javascript:void(0)" class="btn btn-default btn-flat float-right" onclick="admin.bye()">Sign out</a>
              </li>

              <div class="dropdown-divider"></div>
              <a href="javascript:void(0)" class="dropdown-item dropdown-footer text-danger" onclick="admin.bye()">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
              </a>
            </ul>
          </li>
        </ul>
      </nav>
    <?php } ?>

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
              <!-- AVATAR FIXED HERE -->
              <img src="<?= htmlspecialchars($activeAvatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                class="img-circle elevation-3 border border-success"
                alt="User Image">
            </div>

            <div class="info">
              <a href="<?= URL_HOST ?>my-account/profile.php"
                class="d-block text-light font-weight-bold hover-glow">
                <?= htmlspecialchars($active_user['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>
              </a>
              <span class="text-xs text-muted">Active Trader</span>
            </div>
          </div>

          <!-- Sidebar Menu -->
          <nav class="mt-2" id="sidebar">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

              <li class="nav-item">
                <a href="<?= URL_HOST ?>" class="nav-link">
                  <i class="nav-icon fas fa-home text-success"></i>
                  <p>Dashboard</p>
                </a>
              </li>

              <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-chart-line text-warning"></i>
                  <p>Trading <i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>trading/buy.php" class="nav-link">
                      <i class="fas fa-coins nav-icon text-warning"></i>
                      <p>Buy Stocks/Crypto</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>trading/sell.php" class="nav-link">
                      <i class="fas fa-hand-holding-usd nav-icon text-warning"></i>
                      <p>Sell Stocks/Crypto</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>trading/reminder.php" class="nav-link">
                      <i class="fas fa-bell nav-icon text-warning"></i>
                      <p>Reminder</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>trading/search.php" class="nav-link">
                      <i class="fas fa-search-dollar nav-icon text-warning"></i>
                      <p>Search Stocks</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>trading/transactions.php" class="nav-link">
                      <i class="fas fa-history nav-icon text-warning"></i>
                      <p>My Transactions</p>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-briefcase text-info"></i>
                  <p>Portfolio <i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>portfolio/my-holdings.php" class="nav-link">
                      <i class="fas fa-wallet nav-icon text-info"></i>
                      <p>My Holdings</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>portfolio/watchlist.php" class="nav-link">
                      <i class="fas fa-eye nav-icon text-info"></i>
                      <p>Watchlist</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>portfolio/performance.php" class="nav-link">
                      <i class="fas fa-chart-pie nav-icon text-info"></i>
                      <p>Performance</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>portfolio/export.php" class="nav-link">
                      <i class="fas fa-file-export nav-icon text-info"></i>
                      <p>Export Portfolio</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>portfolio/screener.php" class="nav-link">
                      <i class="fas fa-filter nav-icon text-info"></i>
                      <p>Stock Screener</p>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-user text-danger"></i>
                  <p>My Account <i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>my-account/profile.php" class="nav-link">
                      <i class="fas fa-id-card nav-icon text-danger"></i>
                      <p>Profile</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>my-account/notifications.php" class="nav-link">
                      <i class="fas fa-bell nav-icon text-danger"></i>
                      <p>Notifications</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="<?= URL_HOST ?>my-account/activities.php" class="nav-link">
                      <i class="fas fa-tasks nav-icon text-danger"></i>
                      <p>Activities</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link" onclick="admin.bye()">
                      <i class="fas fa-sign-out-alt nav-icon text-danger"></i>
                      <p>Logout</p>
                    </a>
                  </li>
                </ul>
              </li>

              <?php if (($active_user['role'] ?? '') == 'Admin') { ?>
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
              <?php } ?>

            </ul>
          </nav>
        </div>
      </aside>

      <script>
        const currentPath = location.pathname;
        const menuItem = document.querySelectorAll('.nav-link');

        menuItem.forEach(item => {
          let href = item.getAttribute("href");

          if (!href || href === "#" || href.startsWith("javascript")) return;

          const itemPath = new URL(href, location.origin).pathname;

          if (itemPath === currentPath) {
            item.classList.add('active');
            item.closest('.has-treeview')?.classList.add('menu-open');
            item.closest('.nav-treeview')?.previousElementSibling?.classList.add('active');
            item.querySelector('p')?.classList.add('text-success');
            item.closest('.nav-treeview')?.previousElementSibling?.querySelector('p')?.classList.add('text-success');

            let parentText = item.closest('.nav-treeview')?.previousElementSibling?.querySelector('p')?.textContent;
            if (parentText) {
              document.title = parentText + " | PaperTrade";
            }
          }
        });
      </script>

      <style>
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

      <div class="content-wrapper">
    <?php } ?>

    <!-- PAGE CONTENTS -->