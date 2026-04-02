<?php
require "../lib/Core.php";
require PATH_LIB . "PAGE-top.php";

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 172800) {
        return 'yesterday';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}


if (!function_exists("account_notifications_escape")) {
  function account_notifications_escape(?string $value): string
  {
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
  }
}

if (!function_exists("account_notifications_snippet")) {
  function account_notifications_snippet(?string $value, int $maxWords = 22): string
  {
    $clean = trim(strip_tags((string)$value));
    if ($clean === "") {
      return "";
    }
    $parts = preg_split("/\\s+/", $clean);
    if (!$parts || count($parts) <= $maxWords) {
      return $clean;
    }
    return implode(" ", array_slice($parts, 0, $maxWords)) . "…";
  }
}

$ownerId = isset($active_user["user_id"]) ? (int)$active_user["user_id"] : 0;
$rawNotifications = [];
if ($ownerId > 0) {
  $limitRows = 150;
$sqlNotifications = "SELECT notification_id, title, message, status, is_deleted, created_at, read_at
                       FROM notifications
                       WHERE user_id = ?
                       ORDER BY created_at DESC
                       LIMIT ?";
  $stmtNotifications = mysqli_prepare($conn, $sqlNotifications);
  if ($stmtNotifications) {
    mysqli_stmt_bind_param($stmtNotifications, "ii", $ownerId, $limitRows);
    mysqli_stmt_execute($stmtNotifications);
    mysqli_stmt_bind_result(
      $stmtNotifications,
      $noteId,
      $noteTitle,
      $noteMessage,
      $noteStatus,
      $noteIsDeleted,
      $noteCreatedAt,
      $noteReadAt
    );
    while (mysqli_stmt_fetch($stmtNotifications)) {
      $rawNotifications[] = [
        "notification_id" => (int)$noteId,
        "title" => $noteTitle,
        "message" => $noteMessage,
        "status" => $noteStatus,
        "is_deleted" => (int)$noteIsDeleted,
        "created_at" => $noteCreatedAt,
        "read_at" => $noteReadAt
      ];
    }
    mysqli_stmt_close($stmtNotifications);
  }
}

$preparedNotifications = [];
$unreadCount = 0;
foreach ($rawNotifications as $entry) {
  $status = $entry["status"] ?? "Unread";
  $isRead = strcasecmp($status, "Read") === 0;
  $isDeleted = isset($entry["is_deleted"]) ? (int)$entry["is_deleted"] === 1 : false;
  if (!$isRead && !$isDeleted) {
    $unreadCount++;
  }
  $createdTs = $entry["created_at"] ? strtotime($entry["created_at"]) : 0;
  $preparedNotifications[] = [
    "notification_id" => $entry["notification_id"],
    "title" => ($entry["title"] !== null && $entry["title"] !== "") ? $entry["title"] : "Notification",
    "message" => $entry["message"] ?? "",
    "status" => $isRead ? "Read" : "Unread",
    "created_at" => $entry["created_at"],
    "created_display" => $createdTs ? date("M j, Y g:i A", $createdTs) : "",
    "created_ts" => $createdTs ?: null,
    "time_ago" => $entry["created_at"] ? timeAgo($entry["created_at"]) : "—",
    "read_at" => $entry["read_at"],
    "snippet" => account_notifications_snippet($entry["message"]),
    "is_deleted" => $isDeleted
  ];
}

$inboxCount = 0;
$trashCount = 0;
foreach ($preparedNotifications as $note) {
  if (!empty($note["is_deleted"])) {
    $trashCount++;
  } else {
    $inboxCount++;
  }
}
$latestActive = null;
foreach ($preparedNotifications as $note) {
  if (empty($note["is_deleted"])) {
    $latestActive = $note;
    break;
  }
}
$stats = [
  "inbox" => $inboxCount,
  "trash" => $trashCount,
  "total" => count($preparedNotifications),
  "unread" => $unreadCount,
  "read" => max(0, $inboxCount - $unreadCount),
  "latest_label" => $latestActive ? $latestActive["time_ago"] : "No notifications yet",
  "latest_exact" => $latestActive ? $latestActive["created_display"] : ""
];

$jsonNotifications = json_encode(
  $preparedNotifications,
  JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
if ($jsonNotifications === false) {
  $jsonNotifications = "[]";
}
?>

<style>
  .notifications-summary > [class*="col-"] {
    display: flex;
  }

  .notifications-summary .card {
    border: 0;
    border-left: 4px solid transparent;
    border-radius: 0.85rem;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    min-height: 120px;
    width: 100%;
    height: 100%;
  }

  .notifications-summary .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.15);
  }

  .summary-inbox {
    border-left-color: #007bff;
  }

  .summary-unread {
    border-left-color: #ffc107;
  }

  .summary-trash {
    border-left-color: #6c757d;
  }

  .notifications-card {
    border-radius: 1rem;
  }

  .notifications-table td,
  .notifications-table th {
    vertical-align: middle;
  }

  .notifications-table tbody tr.notification-row-unread {
    background: linear-gradient(90deg, rgba(255, 247, 220, 0.35), rgba(255, 255, 255, 0));
  }

  .notification-title {
    font-weight: 600;
  }

  .notification-snippet {
    max-width: 520px;
  }

  .notifications-empty-state {
    border: 2px dashed #d8e2ef;
    border-radius: 1rem;
    background: #f8fafc;
  }

  .notifications-card .card-header .form-control,
  .notifications-card .card-header .custom-select {
    min-width: 180px;
  }

  .notifications-card .card-header .input-group {
    max-width: 320px;
  }

  .notification-modal-text {
    white-space: pre-wrap;
    font-family: "Source Sans Pro", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 1rem;
  }

  .refresh-button.is-loading i {
    animation: spin 0.9s linear infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }
</style>

<section class="content-header">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-md-7">
        <h1 class="mb-0">Notifications</h1>
        <p class="text-muted mb-0">Stay on top of account alerts, approvals, and trading reminders.</p>
      </div>
      <div class="col-md-5 text-md-right mt-3 mt-md-0">
        <button type="button" class="btn btn-outline-primary btn-sm refresh-button" data-role="refresh-list">
          <i class="fas fa-sync-alt mr-1"></i>Refresh
        </button>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row notifications-summary">
      <div class="col-md-4 mb-3">
        <div class="card summary-inbox">
          <div class="card-body">
            <small class="text-uppercase text-muted">Inbox</small>
            <h3 class="mt-2 mb-0" data-role="summary-inbox"><?= number_format($stats["inbox"]) ?></h3>
            <span class="badge badge-light text-primary mt-2" data-role="summary-read">
              <?= number_format($stats["read"]) ?> read
            </span>
            <div class="mt-3">
              <small class="text-uppercase text-muted d-block">Latest update</small>
              <strong data-role="summary-latest"><?= account_notifications_escape($stats["latest_label"]) ?></strong><br>
              <small class="text-muted" data-role="summary-latest-exact"><?= account_notifications_escape($stats["latest_exact"]) ?></small>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card summary-unread">
          <div class="card-body">
            <small class="text-uppercase text-muted">Unread alerts</small>
            <h3 class="mt-2 mb-0 text-warning" data-role="summary-unread"><?= number_format($stats["unread"]) ?></h3>
            <span class="badge badge-warning text-dark mt-2">Action needed</span>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card summary-trash">
          <div class="card-body">
            <small class="text-uppercase text-muted">Trash</small>
            <h3 class="mt-2 mb-0 text-secondary" data-role="summary-trash"><?= number_format($stats["trash"]) ?></h3>
            <span class="badge badge-secondary mt-2">Stored safely</span>
          </div>
        </div>
      </div>
    </div>

    <div class="card card-outline card-primary notifications-card">
      <div class="card-header">
        <div class="d-flex flex-wrap align-items-center">
          <div class="btn-group btn-group-sm mr-3 mb-2" role="group" aria-label="Notification folders">
            <button type="button" class="btn btn-outline-primary active" data-folder-toggle="inbox">
              <i class="fas fa-inbox mr-1"></i>Inbox
              <span class="badge badge-primary ml-1" data-folder-count="inbox"><?= number_format($stats["inbox"]) ?></span>
            </button>
            <button type="button" class="btn btn-outline-secondary" data-folder-toggle="trash">
              <i class="far fa-trash-alt mr-1"></i>Trash
              <span class="badge badge-secondary ml-1" data-folder-count="trash"><?= number_format($stats["trash"]) ?></span>
            </button>
          </div>

          <div class="input-group input-group-sm mr-2 mb-2">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="search" class="form-control" placeholder="Search title or message…" data-role="filter-search">
          </div>

          <select class="custom-select custom-select-sm mr-2 mb-2" data-role="filter-status">
            <option value="all" selected>All statuses</option>
            <option value="unread">Unread only</option>
            <option value="read">Read only</option>
          </select>

          <select class="custom-select custom-select-sm mr-2 mb-2" data-role="sort-select">
            <option value="newest" selected>Newest first</option>
            <option value="oldest">Oldest first</option>
            <option value="unread">Unread first</option>
          </select>

          <button type="button" class="btn btn-outline-secondary btn-sm mb-2" data-role="reset-filters">
            Reset filters
          </button>

          <div class="ml-auto d-flex align-items-center flex-wrap">
            <span class="badge badge-light text-muted mr-2 mb-2" data-role="result-count">
              <?= $stats["inbox"] ?> results
            </span>
            <span class="badge badge-secondary mb-2" data-role="selection-count">0 selected</span>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 notifications-table">
          <thead>
            <tr>
              <th class="text-center" style="width: 56px;">
                <div class="custom-control custom-checkbox m-0">
                  <input type="checkbox" class="custom-control-input" id="notification-select-all" data-role="select-all">
                  <label class="custom-control-label" for="notification-select-all"></label>
                </div>
              </th>
              <th>Notification</th>
              <th class="text-right" style="width: 200px;">Received</th>
              <th class="text-right" style="width: 170px;">Actions</th>
            </tr>
          </thead>
          <tbody data-role="notifications-body">
            <?php if ($stats["inbox"] === 0) : ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-4">
                  No inbox notifications to display yet.
                </td>
              </tr>
            <?php else : ?>
              <?php foreach ($preparedNotifications as $note) :
                if (!empty($note["is_deleted"])) {
                  continue;
                }
                $isRead = strcasecmp($note["status"], "Read") === 0;
                $rowClass = $isRead ? "notification-row" : "notification-row notification-row-unread";
                $payload = htmlspecialchars(json_encode($note, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, "UTF-8");
              ?>
                <tr
                  class="<?= $rowClass ?>"
                  data-id="<?= (int)$note["notification_id"] ?>"
                  data-status="<?= strtolower($note["status"]) ?>"
                  data-deleted="0"
                  data-payload="<?= $payload ?>">
                  <td class="align-middle text-center">
                    <div class="custom-control custom-checkbox">
                      <input
                        type="checkbox"
                        class="custom-control-input notification-checkbox"
                        id="notification-row-<?= (int)$note["notification_id"] ?>"
                        data-id="<?= (int)$note["notification_id"] ?>">
                      <label class="custom-control-label" for="notification-row-<?= (int)$note["notification_id"] ?>"></label>
                    </div>
                  </td>
                  <td class="align-middle">
                    <div class="d-flex align-items-center notification-title text-break">
                      <span class="badge badge-pill mr-2 <?= $isRead ? 'badge-light text-muted' : 'badge-warning text-dark' ?>">
                        <?= $isRead ? "Read" : "Unread" ?>
                      </span>
                      <span><?= account_notifications_escape($note["title"]) ?></span>
                    </div>
                    <div class="text-muted notification-snippet text-truncate">
                      <?= account_notifications_escape($note["snippet"]) ?>
                    </div>
                  </td>
                  <td class="align-middle text-right">
                    <div class="font-weight-semibold"><?= account_notifications_escape($note["time_ago"]) ?></div>
                    <small class="text-muted"><?= account_notifications_escape($note["created_display"]) ?></small>
                  </td>
                  <td class="align-middle text-right">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-light" data-action="view" title="View details">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button
                        type="button"
                        class="btn btn-light"
                        data-action="<?= $isRead ? 'mark-unread' : 'mark-read' ?>"
                        title="<?= $isRead ? 'Mark as unread' : 'Mark as read' ?>">
                        <i class="fas <?= $isRead ? 'fa-envelope' : 'fa-envelope-open' ?>"></i>
                      </button>
                      <button type="button" class="btn btn-light text-danger" data-action="trash" title="Move to trash">
                        <i class="far fa-trash-alt"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="p-5 text-center notifications-empty-state <?= $stats["inbox"] > 0 ? 'd-none' : '' ?>" data-role="notifications-empty">
        <i class="fas fa-bell-slash fa-2x text-muted mb-3"></i>
        <p class="mb-0 text-muted" data-role="empty-message">You're all caught up. New alerts will appear here automatically.</p>
      </div>

      <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2 mb-md-0">
          <button type="button" class="btn btn-outline-secondary btn-sm mr-2" data-role="clear-selection">
            Clear selection
          </button>
          <span class="text-muted small">Auto-refreshes every minute.</span>
        </div>
        <div class="mb-2 mb-md-0" data-role="bulk-actions">
          <div class="d-flex flex-wrap align-items-center" data-folder-actions="inbox">
            <button type="button" class="btn btn-success btn-sm mr-2 mb-2" data-bulk-action="mark-read" disabled>
              <i class="fas fa-envelope-open mr-1"></i>Mark read
            </button>
            <button type="button" class="btn btn-warning btn-sm mr-2 mb-2" data-bulk-action="mark-unread" disabled>
              <i class="fas fa-envelope mr-1"></i>Mark unread
            </button>
            <button type="button" class="btn btn-danger btn-sm mb-2" data-bulk-action="trash" disabled>
              <i class="far fa-trash-alt mr-1"></i>Move to trash
            </button>
          </div>
          <div class="d-flex flex-wrap align-items-center d-none" data-folder-actions="trash">
            <button type="button" class="btn btn-outline-success btn-sm mr-2 mb-2" data-bulk-action="restore" disabled>
              <i class="fas fa-undo mr-1"></i>Restore
            </button>
            <button type="button" class="btn btn-danger btn-sm mb-2" data-bulk-action="destroy" disabled>
              <i class="fas fa-times-circle mr-1"></i>Delete forever
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="notificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="notificationModalLabel" data-role="modal-title">Notification</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center mb-3">
          <span class="badge badge-info text-uppercase mr-3" data-role="modal-status">Unread</span>
          <div>
            <small class="text-muted d-block">Received</small>
            <strong data-role="modal-received">—</strong>
          </div>
        </div>
        <div class="notification-modal-text" data-role="modal-message">No details available.</div>
      </div>
      <div class="modal-footer d-flex justify-content-between align-items-center">
        <small class="text-muted" data-role="modal-meta"></small>
        <div>
          <button type="button" class="btn btn-outline-success mr-2 d-none" data-action="modal-mark-read">
            <i class="fas fa-check mr-1"></i>Mark as read
          </button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  window.__NOTIFICATIONS_DATA__ = <?= $jsonNotifications ?>;
</script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const notificationsData = Array.isArray(window.__NOTIFICATIONS_DATA__) ? window.__NOTIFICATIONS_DATA__ : [];
    const tableBody = document.querySelector("[data-role='notifications-body']");
    const masterCheckbox = document.querySelector("[data-role='select-all']");
    const selectionBadge = document.querySelector("[data-role='selection-count']");
    const bulkButtons = document.querySelectorAll("[data-bulk-action]");
    const folderButtons = document.querySelectorAll("[data-folder-toggle]");
    const folderCountBadges = document.querySelectorAll("[data-folder-count]");
    const bulkActionGroups = document.querySelectorAll("[data-folder-actions]");
    const searchInput = document.querySelector("[data-role='filter-search']");
    const statusFilter = document.querySelector("[data-role='filter-status']");
    const sortSelect = document.querySelector("[data-role='sort-select']");
    const resetFiltersBtn = document.querySelector("[data-role='reset-filters']");
    const clearSelectionBtn = document.querySelector("[data-role='clear-selection']");
    const resultCount = document.querySelector("[data-role='result-count']");
    const emptyStateBlock = document.querySelector("[data-role='notifications-empty']");
    const emptyStateText = emptyStateBlock ? emptyStateBlock.querySelector("[data-role='empty-message']") : null;
    const refreshButton = document.querySelector("[data-role='refresh-list']");
    const modalEl = document.getElementById("notificationModal");
    const modalTitle = modalEl ? modalEl.querySelector("[data-role='modal-title']") : null;
    const modalMessage = modalEl ? modalEl.querySelector("[data-role='modal-message']") : null;
    const modalStatus = modalEl ? modalEl.querySelector("[data-role='modal-status']") : null;
    const modalReceived = modalEl ? modalEl.querySelector("[data-role='modal-received']") : null;
    const modalMeta = modalEl ? modalEl.querySelector("[data-role='modal-meta']") : null;
    const modalMarkReadBtn = modalEl ? modalEl.querySelector("[data-action='modal-mark-read']") : null;
    const pollInterval = 60000;
    const navBadge = document.querySelector(".notifications-nav .navbar-badge");
    const navHeader = document.querySelector(".notifications-nav [data-role='notifications-header']");
    let pollTimer = null;
    let lastVisibleItems = [];

    const state = {
      items: notificationsData.map(normalizeItem),
      filterStatus: "all",
      search: "",
      sort: "newest",
      selected: new Set(),
      modalItemId: null,
      folder: "inbox"
    };
    state.items.sort((a, b) => (b.createdAt || 0) - (a.createdAt || 0));
    if (!state.items.some((item) => !item.isDeleted) && state.items.some((item) => item.isDeleted)) {
      state.folder = "trash";
    }

    function normalizeItem(row) {
      const createdTs = row.created_ts
        ? Number(row.created_ts) * 1000
        : (row.created_at ? Date.parse(row.created_at.replace(" ", "T")) : 0);
      const statusLabel = (row.status || "Unread").toString();
      const isRead = statusLabel.toLowerCase() === "read";
      const isDeleted = row.is_deleted === true || row.is_deleted === 1 || row.is_deleted === "1";
      return {
        id: Number(row.notification_id),
        title: row.title || "Notification",
        message: row.message || "",
        status: isRead ? "Read" : "Unread",
        isRead,
        isDeleted,
        snippet: row.snippet || buildSnippet(row.message || ""),
        createdAt: createdTs,
        createdDisplay: row.created_display || (createdTs ? formatDate(createdTs) : ""),
        timeAgo: row.time_ago || (createdTs ? formatTimeAgo(createdTs) : "—")
      };
    }

    function buildSnippet(text) {
      const clean = (text || "").replace(/\s+/g, " ").trim();
      if (clean === "") {
        return "No preview available.";
      }
      const parts = clean.split(" ");
      if (parts.length <= 22) {
        return clean;
      }
      return parts.slice(0, 22).join(" ") + "…";
    }

    function formatDate(timestamp) {
      if (!timestamp) return "";
      return new Intl.DateTimeFormat("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit"
      }).format(new Date(timestamp));
    }

    function formatTimeAgo(timestamp) {
      if (!timestamp) return "—";
      const seconds = Math.floor((Date.now() - timestamp) / 1000);
      if (seconds < 60) return "just now";
      if (seconds < 3600) {
        const mins = Math.floor(seconds / 60);
        return `${mins} min${mins === 1 ? "" : "s"} ago`;
      }
      if (seconds < 86400) {
        const hrs = Math.floor(seconds / 3600);
        return `${hrs} hour${hrs === 1 ? "" : "s"} ago`;
      }
      if (seconds < 172800) return "yesterday";
      if (seconds < 2592000) {
        const days = Math.floor(seconds / 86400);
        return `${days} day${days === 1 ? "" : "s"} ago`;
      }
      if (seconds < 31536000) {
        const months = Math.floor(seconds / 2592000);
        return `${months} month${months === 1 ? "" : "s"} ago`;
      }
      const years = Math.floor(seconds / 31536000);
      return `${years} year${years === 1 ? "" : "s"} ago`;
    }

    function getFolderCounts() {
      return state.items.reduce((acc, item) => {
        if (item.isDeleted) {
          acc.trash++;
        } else {
          acc.inbox++;
          if (!item.isRead) {
            acc.unread++;
          }
        }
        return acc;
      }, { inbox: 0, trash: 0, unread: 0 });
    }

    function updateFolderCounts() {
      const counts = getFolderCounts();
      folderCountBadges.forEach((badge) => {
        const folder = badge.getAttribute("data-folder-count");
        if (folder === "inbox") {
          badge.textContent = counts.inbox.toLocaleString();
        } else if (folder === "trash") {
          badge.textContent = counts.trash.toLocaleString();
        }
      });
    }

    function updateFolderButtons() {
      folderButtons.forEach((btn) => {
        const target = btn.getAttribute("data-folder-toggle");
        const isActive = target === state.folder;
        btn.classList.toggle("active", isActive);
        if (target === "inbox") {
          btn.classList.toggle("btn-outline-primary", !isActive);
          btn.classList.toggle("btn-primary", isActive);
        } else if (target === "trash") {
          btn.classList.toggle("btn-outline-secondary", !isActive);
          btn.classList.toggle("btn-secondary", isActive);
        }
      });
    }

    function getFilteredItems() {
      const query = state.search.trim().toLowerCase();
      let filtered = state.items.filter((item) => {
        const folderMatch = state.folder === "trash" ? item.isDeleted : !item.isDeleted;
        if (!folderMatch) {
          return false;
        }
        const statusMatch =
          state.filterStatus === "all" ||
          (state.filterStatus === "read" && item.isRead) ||
          (state.filterStatus === "unread" && !item.isRead);
        const queryMatch =
          !query ||
          (item.title && item.title.toLowerCase().includes(query)) ||
          (item.message && item.message.toLowerCase().includes(query));
        return statusMatch && queryMatch;
      });

      if (state.sort === "oldest") {
        filtered = filtered.slice().sort((a, b) => (a.createdAt || 0) - (b.createdAt || 0));
      } else if (state.sort === "unread") {
        filtered = filtered.slice().sort((a, b) => {
          if (a.isRead === b.isRead) {
            return (b.createdAt || 0) - (a.createdAt || 0);
          }
          return a.isRead ? 1 : -1;
        });
      } else {
        filtered = filtered.slice().sort((a, b) => (b.createdAt || 0) - (a.createdAt || 0));
      }
      return filtered;
    }

    function renderTable() {
      if (!tableBody) return;
      const visibleItems = getFilteredItems();
      lastVisibleItems = visibleItems;
      tableBody.innerHTML = "";
      if (visibleItems.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");
        cell.colSpan = 4;
        cell.className = "text-center text-muted py-4";
        const folderHasItems = state.items.some((item) => state.folder === "trash" ? item.isDeleted : !item.isDeleted);
        const label = state.folder === "trash" ? "trash" : "inbox";
        cell.textContent = folderHasItems
          ? `No ${label} notifications match your filters.`
          : `No ${label} notifications yet.`;
        row.appendChild(cell);
        tableBody.appendChild(row);
      } else {
        visibleItems.forEach((item) => {
          tableBody.appendChild(buildRow(item));
        });
      }
      if (resultCount) {
        const label = state.folder === "trash" ? "trash" : "inbox";
        resultCount.textContent = `${visibleItems.length} ${label} result${visibleItems.length === 1 ? "" : "s"}`;
      }
      updateSelectionUi();
      updateSummaryUi();
      updateFolderCounts();
      updateFolderButtons();
      updateBulkActionVisibility();
      toggleEmptyState();
    }

    function buildRow(item) {
      const row = document.createElement("tr");
      const rowClasses = ["notification-row"];
      if (!item.isRead && !item.isDeleted) {
        rowClasses.push("notification-row-unread");
      }
      row.className = rowClasses.join(" ");
      row.dataset.id = String(item.id);
      row.dataset.deleted = item.isDeleted ? "1" : "0";

      const cellSelect = document.createElement("td");
      cellSelect.className = "align-middle text-center";
      const checkboxWrapper = document.createElement("div");
      checkboxWrapper.className = "custom-control custom-checkbox";
      const checkbox = document.createElement("input");
      checkbox.type = "checkbox";
      checkbox.className = "custom-control-input notification-checkbox";
      checkbox.id = `notification-row-${item.id}`;
      checkbox.dataset.id = String(item.id);
      checkbox.checked = state.selected.has(item.id);
      const checkboxLabel = document.createElement("label");
      checkboxLabel.className = "custom-control-label";
      checkboxLabel.setAttribute("for", checkbox.id);
      checkboxWrapper.appendChild(checkbox);
      checkboxWrapper.appendChild(checkboxLabel);
      cellSelect.appendChild(checkboxWrapper);

      const cellMessage = document.createElement("td");
      cellMessage.className = "align-middle";
      const titleWrapper = document.createElement("div");
      titleWrapper.className = "d-flex align-items-center notification-title text-break";
      const badge = document.createElement("span");
      badge.className = `badge badge-pill mr-2 ${item.isRead ? "badge-light text-muted" : "badge-warning text-dark"}`;
      badge.textContent = item.isRead ? "Read" : "Unread";
      const titleText = document.createElement("span");
      titleText.textContent = item.title;
      titleWrapper.appendChild(badge);
      titleWrapper.appendChild(titleText);
      const snippet = document.createElement("div");
      snippet.className = "text-muted notification-snippet text-truncate";
      snippet.textContent = item.snippet;
      cellMessage.appendChild(titleWrapper);
      cellMessage.appendChild(snippet);

      const cellDate = document.createElement("td");
      cellDate.className = "align-middle text-right";
      const timeAgo = document.createElement("div");
      timeAgo.className = "font-weight-semibold";
      timeAgo.textContent = item.timeAgo || "—";
      const exact = document.createElement("small");
      exact.className = "text-muted d-block";
      exact.textContent = item.createdDisplay || "";
      cellDate.appendChild(timeAgo);
      cellDate.appendChild(exact);

      const cellActions = document.createElement("td");
      cellActions.className = "align-middle text-right";
      const actionsGroup = document.createElement("div");
      actionsGroup.className = "btn-group btn-group-sm";

      const viewBtn = document.createElement("button");
      viewBtn.type = "button";
      viewBtn.className = "btn btn-light";
      viewBtn.dataset.action = "view";
      viewBtn.innerHTML = '<i class="fas fa-eye"></i>';
      actionsGroup.appendChild(viewBtn);

      if (state.folder === "trash") {
        const restoreBtn = document.createElement("button");
        restoreBtn.type = "button";
        restoreBtn.className = "btn btn-light";
        restoreBtn.dataset.action = "restore";
        restoreBtn.innerHTML = '<i class="fas fa-undo"></i>';

        const destroyBtn = document.createElement("button");
        destroyBtn.type = "button";
        destroyBtn.className = "btn btn-light text-danger";
        destroyBtn.dataset.action = "destroy";
        destroyBtn.innerHTML = '<i class="fas fa-times-circle"></i>';

        actionsGroup.appendChild(restoreBtn);
        actionsGroup.appendChild(destroyBtn);
      } else {
        const toggleBtn = document.createElement("button");
        toggleBtn.type = "button";
        toggleBtn.className = "btn btn-light";
        toggleBtn.dataset.action = item.isRead ? "mark-unread" : "mark-read";
        toggleBtn.innerHTML = `<i class="fas ${item.isRead ? "fa-envelope" : "fa-envelope-open"}"></i>`;

        const trashBtn = document.createElement("button");
        trashBtn.type = "button";
        trashBtn.className = "btn btn-light text-danger";
        trashBtn.dataset.action = "trash";
        trashBtn.innerHTML = '<i class="far fa-trash-alt"></i>';

        actionsGroup.appendChild(toggleBtn);
        actionsGroup.appendChild(trashBtn);
      }

      cellActions.appendChild(actionsGroup);

      row.appendChild(cellSelect);
      row.appendChild(cellMessage);
      row.appendChild(cellDate);
      row.appendChild(cellActions);
      return row;
    }

    function updateSummaryUi() {
      const summaryInbox = document.querySelector("[data-role='summary-inbox']");
      const summaryUnread = document.querySelector("[data-role='summary-unread']");
      const summaryRead = document.querySelector("[data-role='summary-read']");
      const summaryTrash = document.querySelector("[data-role='summary-trash']");
      const summaryLatest = document.querySelector("[data-role='summary-latest']");
      const summaryExact = document.querySelector("[data-role='summary-latest-exact']");
      const counts = getFolderCounts();
      const read = Math.max(0, counts.inbox - counts.unread);
      if (summaryInbox) summaryInbox.textContent = counts.inbox.toLocaleString();
      if (summaryUnread) summaryUnread.textContent = counts.unread.toLocaleString();
      if (summaryRead) summaryRead.textContent = `${read.toLocaleString()} read`;
      if (summaryTrash) summaryTrash.textContent = counts.trash.toLocaleString();
      const mostRecent = state.items
        .filter((item) => !item.isDeleted)
        .sort((a, b) => (b.createdAt || 0) - (a.createdAt || 0))[0];
      if (summaryLatest) {
        summaryLatest.textContent = mostRecent ? (mostRecent.timeAgo || "just now") : "No notifications yet";
      }
      if (summaryExact) {
        summaryExact.textContent = mostRecent ? (mostRecent.createdDisplay || "") : "";
      }
      syncNavBadge(counts.unread);
    }

    function toggleEmptyState() {
      if (!emptyStateBlock) return;
      const hasInbox = state.items.some((item) => !item.isDeleted);
      emptyStateBlock.classList.toggle("d-none", hasInbox);
      if (emptyStateText) {
        emptyStateText.textContent = hasInbox
          ? "You're all caught up. New alerts will appear here automatically."
          : "No notifications yet. Messages from the admin will appear here.";
      }
    }

    function updateSelectionUi() {
      const count = state.selected.size;
      if (selectionBadge) {
        selectionBadge.textContent = `${count} selected`;
      }
      bulkButtons.forEach((button) => {
        button.disabled = count === 0;
      });
      updateBulkActionVisibility();
      if (masterCheckbox) {
        const visibleIds = new Set(lastVisibleItems.map((item) => item.id));
        const visibleSelected = [...visibleIds].filter((id) => state.selected.has(id));
        const allVisibleSelected = visibleIds.size > 0 && visibleSelected.length === visibleIds.size;
        masterCheckbox.checked = allVisibleSelected;
        masterCheckbox.indeterminate = visibleSelected.length > 0 && visibleSelected.length < visibleIds.size;
        if (visibleIds.size === 0) {
          masterCheckbox.indeterminate = false;
          masterCheckbox.checked = false;
        }
      }
    }

    function updateBulkActionVisibility() {
      bulkActionGroups.forEach((group) => {
        const target = group.getAttribute("data-folder-actions");
        const isActive = target === state.folder;
        group.classList.toggle("d-none", !isActive);
        group.querySelectorAll("button").forEach((button) => {
          button.disabled = state.selected.size === 0;
        });
      });
    }

    function resetFilters() {
      state.filterStatus = "all";
      state.search = "";
      state.sort = "newest";
      if (searchInput) searchInput.value = "";
      if (statusFilter) statusFilter.value = "all";
      if (sortSelect) sortSelect.value = "newest";
      renderTable();
    }

    function clearSelection() {
      state.selected.clear();
      if (masterCheckbox) {
        masterCheckbox.checked = false;
        masterCheckbox.indeterminate = false;
      }
      if (tableBody) {
        tableBody.querySelectorAll(".notification-checkbox").forEach((checkbox) => {
          checkbox.checked = false;
        });
      }
      updateSelectionUi();
    }

    function showToast(message, type = "success") {
      if (window.toastr) {
        toastr.options = {
          positionClass: "toast-bottom-right",
          timeOut: 2500,
          progressBar: true
        };
        toastr[type](message);
      }
    }

    function synchronizeNavItems(ids, isRead) {
      ids.forEach((id) => {
        const navItem = document.querySelector(`.notifications-nav .notification-item[data-id='${id}']`);
        if (navItem) {
          navItem.classList.toggle("read", isRead);
          const toggleLink = navItem.querySelector(".notification-toggle");
          if (toggleLink) {
            toggleLink.classList.toggle("text-success", !isRead);
            toggleLink.classList.toggle("text-secondary", isRead);
            toggleLink.setAttribute("title", isRead ? "Mark as Unread" : "Mark as Read");
          }
        }
      });
      if (navHeader) {
        const navItems = document.querySelectorAll(".notifications-nav .notification-item");
        navHeader.textContent = navItems.length > 0
          ? `${navItems.length} Notifications`
          : "No notifications";
      }
    }

    function syncNavBadge(unread) {
      if (navBadge) {
        navBadge.textContent = unread;
        navBadge.classList.toggle("d-none", unread === 0);
      }
    }

    function handleRowAction(action, item) {
      if (!item) return;
      switch (action) {
        case "view":
          openModal(item);
          break;
        case "mark-read":
          applyStatus([item.id], "read");
          break;
        case "mark-unread":
          applyStatus([item.id], "unread");
          break;
        case "trash":
          moveToTrash([item.id]);
          break;
        case "restore":
          restoreNotifications([item.id]);
          break;
        case "destroy":
          destroyNotifications([item.id]);
          break;
        default:
          break;
      }
    }

    function applyStatus(ids, targetStatus) {
      if (!ids || ids.length === 0) return;
      const formRequests = ids.map((id) => postAction("notification_mark", id, { status: targetStatus }));
      Promise.allSettled(formRequests)
        .then((results) => {
          const failed = results.filter((res) => res.status === "rejected");
          if (failed.length) {
            Swal.fire("Update failed", failed[0].reason?.message || "Unable to update notification.", "error");
            return;
          }
          const isRead = targetStatus === "read";
          const idsSet = new Set(ids);
          state.items.forEach((item) => {
            if (idsSet.has(item.id)) {
              item.isRead = isRead;
              item.status = isRead ? "Read" : "Unread";
            }
          });
          synchronizeNavItems(ids, isRead);
          renderTable();
          showToast(isRead ? "Marked as read." : "Marked as unread.");
        })
        .catch((error) => {
          Swal.fire("Update failed", error.message || "Unable to update notification.", "error");
        });
    }

    function moveToTrash(ids) {
      if (!ids || ids.length === 0) return;
      const valid = ids.filter((id) => {
        const note = state.items.find((item) => item.id === id);
        return note && !note.isDeleted;
      });
      if (valid.length === 0) {
        return;
      }
      Swal.fire({
        title: valid.length === 1 ? "Move this notification to trash?" : `Move ${valid.length} notifications to trash?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Move",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#e74c3c"
      }).then((result) => {
        if (!result.isConfirmed) return;
        const requests = valid.map((id) => postAction("notification_delete", id));
        Promise.allSettled(requests)
          .then((responses) => {
            const failed = responses.filter((res) => res.status === "rejected");
            if (failed.length) {
              Swal.fire("Move failed", failed[0].reason?.message || "Unable to move notification.", "error");
              return;
            }
            const idSet = new Set(valid);
            state.items.forEach((item) => {
              if (idSet.has(item.id)) {
                item.isDeleted = true;
              }
            });
            valid.forEach((id) => state.selected.delete(id));
            removeNavItems(valid);
            renderTable();
            showToast(valid.length === 1 ? "Moved to trash." : "Moved to trash.", "warning");
          })
          .catch((error) => {
            Swal.fire("Move failed", error.message || "Unable to move notification.", "error");
          });
      });
    }

    function restoreNotifications(ids) {
      if (!ids || ids.length === 0) return;
      const valid = ids.filter((id) => {
        const note = state.items.find((item) => item.id === id);
        return note && note.isDeleted;
      });
      if (valid.length === 0) {
        return;
      }
      Swal.fire({
        title: valid.length === 1 ? "Restore this notification?" : `Restore ${valid.length} notifications?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Restore",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#28a745"
      }).then((result) => {
        if (!result.isConfirmed) return;
        const requests = valid.map((id) => postAction("notification_restore", id));
        Promise.allSettled(requests)
          .then((responses) => {
            const failed = responses.filter((res) => res.status === "rejected");
            if (failed.length) {
              Swal.fire("Restore failed", failed[0].reason?.message || "Unable to restore notification.", "error");
              return;
            }
            const idSet = new Set(valid);
            state.items.forEach((item) => {
              if (idSet.has(item.id)) {
                item.isDeleted = false;
              }
            });
            valid.forEach((id) => state.selected.delete(id));
            renderTable();
            showToast(valid.length === 1 ? "Notification restored." : "Notifications restored.");
          })
          .catch((error) => {
            Swal.fire("Restore failed", error.message || "Unable to restore notification.", "error");
          });
      });
    }

    function destroyNotifications(ids) {
      if (!ids || ids.length === 0) return;
      const valid = ids.filter((id) => {
        const note = state.items.find((item) => item.id === id);
        return note && note.isDeleted;
      });
      if (valid.length === 0) {
        return;
      }
      Swal.fire({
        title: valid.length === 1 ? "Delete this notification forever?" : `Delete ${valid.length} notifications forever?`,
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#d33"
      }).then((result) => {
        if (!result.isConfirmed) return;
        const requests = valid.map((id) => postAction("notification_destroy", id));
        Promise.allSettled(requests)
          .then((responses) => {
            const failed = responses.filter((res) => res.status === "rejected");
            if (failed.length) {
              Swal.fire("Delete failed", failed[0].reason?.message || "Unable to delete notification.", "error");
              return;
            }
            const idSet = new Set(valid);
            state.items = state.items.filter((item) => !idSet.has(item.id));
            valid.forEach((id) => state.selected.delete(id));
            removeNavItems(valid);
            renderTable();
            showToast(valid.length === 1 ? "Notification deleted." : "Notifications deleted.", "warning");
          })
          .catch((error) => {
            Swal.fire("Delete failed", error.message || "Unable to delete notification.", "error");
          });
      });
    }

    function removeNavItems(ids) {
      ids.forEach((id) => {
        const navItem = document.querySelector(`.notifications-nav .notification-item[data-id='${id}']`);
        if (navItem) {
          const prev = navItem.previousElementSibling;
          if (prev && prev.classList.contains("dropdown-divider")) {
            prev.remove();
          }
          navItem.remove();
        }
      });
      if (navHeader) {
        const navItems = document.querySelectorAll(".notifications-nav .notification-item");
        navHeader.textContent = navItems.length > 0 ? `${navItems.length} Notifications` : "No notifications";
      }
      const unread = state.items.filter((item) => !item.isRead && !item.isDeleted).length;
      syncNavBadge(unread);
    }

    function postAction(action, notificationId, extra = {}) {
      const formData = new FormData();
      formData.append("req", action);
      formData.append("notification_id", notificationId);
      Object.entries(extra).forEach(([key, value]) => {
        formData.append(key, value);
      });
      return fetch(url.host + "ajax-session.php", { method: "POST", body: formData })
        .then((res) => res.text())
        .then((text) => {
          const trimmed = text.trim();
          if (trimmed !== "OK") {
            throw new Error(trimmed || "Unexpected response.");
          }
          return true;
        });
    }

    function openModal(item) {
      if (!modalEl) return;
      state.modalItemId = item.id;
      if (modalTitle) modalTitle.textContent = item.title;
      if (modalMessage) modalMessage.textContent = item.message || "No additional details provided.";
      if (modalStatus) {
        modalStatus.textContent = item.isRead ? "Read" : "Unread";
        modalStatus.classList.toggle("badge-info", !item.isRead);
        modalStatus.classList.toggle("badge-success", item.isRead);
      }
      if (modalReceived) {
        modalReceived.textContent = item.createdDisplay || "—";
      }
      if (modalMeta) {
        modalMeta.textContent = item.timeAgo ? `Received ${item.timeAgo}` : "";
      }
      if (modalMarkReadBtn) {
        modalMarkReadBtn.classList.toggle("d-none", item.isRead || item.isDeleted);
      }
      if (window.jQuery) {
        window.jQuery(modalEl).modal("show");
      }
    }

    function mergeItems(freshItems) {
      if (!Array.isArray(freshItems) || freshItems.length === 0) return;
      const map = new Map(state.items.map((item) => [item.id, item]));
      freshItems.forEach((raw) => {
        const formatted = normalizeItem(raw);
        map.set(formatted.id, formatted);
      });
      state.items = Array.from(map.values()).sort((a, b) => (b.createdAt || 0) - (a.createdAt || 0));
    }

    function fetchLatest(limit = 50) {
      const formData = new FormData();
      formData.append("req", "notification_fetch");
      formData.append("limit", String(limit));
      formData.append("include_deleted", "1");
      return fetch(url.host + "ajax-session.php", { method: "POST", body: formData })
        .then((res) => res.text())
        .then((text) => {
          let payload;
          try {
            payload = JSON.parse(text);
          } catch (error) {
            throw new Error(text || "Unable to parse response.");
          }
          if (!payload || payload.success !== true || !Array.isArray(payload.notifications)) {
            throw new Error(payload?.message || "Unable to fetch notifications.");
          }
          return payload.notifications;
        });
    }

    function refreshNotifications(showToastMessage = true) {
      if (!refreshButton) return;
      refreshButton.classList.add("is-loading");
      refreshButton.disabled = true;
      fetchLatest(100)
        .then((rows) => {
          mergeItems(rows);
          renderTable();
          if (showToastMessage) {
            showToast("Notifications updated.");
          }
        })
        .catch((error) => {
          Swal.fire("Refresh failed", error.message || "Unable to refresh notifications.", "error");
        })
        .finally(() => {
          refreshButton.classList.remove("is-loading");
          refreshButton.disabled = false;
        });
    }

    function handleBulkAction(action) {
      const ids = Array.from(state.selected);
      if (ids.length === 0) return;
      switch (action) {
        case "mark-read":
          applyStatus(ids, "read");
          break;
        case "mark-unread":
          applyStatus(ids, "unread");
          break;
        case "trash":
          moveToTrash(ids);
          break;
        case "restore":
          restoreNotifications(ids);
          break;
        case "destroy":
          destroyNotifications(ids);
          break;
        default:
          break;
      }
    }

    if (tableBody) {
      tableBody.addEventListener("change", (event) => {
        const checkbox = event.target.closest(".notification-checkbox");
        if (!checkbox) return;
        const id = Number(checkbox.dataset.id);
        if (!id) return;
        if (checkbox.checked) {
          state.selected.add(id);
        } else {
          state.selected.delete(id);
        }
        updateSelectionUi();
      });

      tableBody.addEventListener("click", (event) => {
        const actionButton = event.target.closest("[data-action]");
        const row = event.target.closest("tr");
        if (!row) return;
        const id = Number(row.dataset.id);
        const item = state.items.find((n) => n.id === id);
        if (!item) return;
        if (actionButton) {
          event.preventDefault();
          handleRowAction(actionButton.dataset.action, item);
          return;
        }
        if (event.target.closest(".custom-control")) {
          return;
        }
        openModal(item);
      });
    }

    if (masterCheckbox) {
      masterCheckbox.addEventListener("change", () => {
        const visibleIds = new Set(lastVisibleItems.map((item) => item.id));
        tableBody.querySelectorAll(".notification-checkbox").forEach((checkbox) => {
          const id = Number(checkbox.dataset.id);
          if (visibleIds.has(id)) {
            checkbox.checked = masterCheckbox.checked;
            if (masterCheckbox.checked) {
              state.selected.add(id);
            } else {
              state.selected.delete(id);
            }
          }
        });
        updateSelectionUi();
      });
    }

    if (searchInput) {
      let searchTimer;
      searchInput.addEventListener("input", () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
          state.search = searchInput.value || "";
          renderTable();
        }, 200);
      });
    }

    if (statusFilter) {
      statusFilter.addEventListener("change", () => {
        state.filterStatus = statusFilter.value || "all";
        renderTable();
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener("change", () => {
        state.sort = sortSelect.value || "newest";
        renderTable();
      });
    }

    if (resetFiltersBtn) {
      resetFiltersBtn.addEventListener("click", () => resetFilters());
    }

    if (clearSelectionBtn) {
      clearSelectionBtn.addEventListener("click", () => clearSelection());
    }

    if (bulkButtons.length) {
      bulkButtons.forEach((button) => {
        button.addEventListener("click", () => handleBulkAction(button.dataset.bulkAction));
      });
    }

    if (folderButtons.length) {
      folderButtons.forEach((button) => {
        button.addEventListener("click", () => {
          const targetFolder = button.getAttribute("data-folder-toggle");
          if (!targetFolder || targetFolder === state.folder) {
            return;
          }
          state.folder = targetFolder;
          clearSelection();
          renderTable();
        });
      });
      updateFolderButtons();
    }

    if (refreshButton) {
      refreshButton.addEventListener("click", () => refreshNotifications());
    }

    if (modalMarkReadBtn) {
      modalMarkReadBtn.addEventListener("click", () => {
        if (!state.modalItemId) return;
        applyStatus([state.modalItemId], "read");
        if (window.jQuery) {
          window.jQuery(modalEl).modal("hide");
        }
      });
    }

    renderTable();
    pollTimer = setInterval(() => refreshNotifications(false), pollInterval);
    document.addEventListener("visibilitychange", () => {
      if (document.hidden) {
        clearInterval(pollTimer);
      } else {
        refreshNotifications(false);
        pollTimer = setInterval(() => refreshNotifications(false), pollInterval);
      }
    });
    window.addEventListener("beforeunload", () => pollTimer && clearInterval(pollTimer));
  });
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>
