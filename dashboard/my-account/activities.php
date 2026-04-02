<?php
require "../lib/Core.php";
require PATH_LIB . "PAGE-top.php";

$current_user_id = (int)($_SESSION['user']['user_id'] ?? ($_SESSION['user_id'] ?? 0));
$activities = [];
$hasMoreActivities = false;
$currentPage = 1;
$pageSize = 50;
$activityPreferences = [
  'muted_event_types' => [],
  'items_per_page' => 50,
  'grouping_window_minutes' => 12,
];

if ($current_user_id > 0 && $conn) {
  ActivityLogger::ensureSchema($conn);
  ActivityLogger::ensurePreferencesTable($conn);

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_activity_prefs'])) {
    $mutedTypes = isset($_POST['muted_event_types']) && is_array($_POST['muted_event_types']) ? $_POST['muted_event_types'] : [];
    $itemsPerPage = (int)($_POST['items_per_page'] ?? 50);
    $groupWindow = (int)($_POST['grouping_window_minutes'] ?? 12);
    ActivityLogger::savePreferences($conn, $current_user_id, [
      'muted_event_types' => $mutedTypes,
      'items_per_page' => $itemsPerPage,
      'grouping_window_minutes' => $groupWindow,
    ]);
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
  }

  $activityPreferences = ActivityLogger::getPreferences($conn, $current_user_id);
  $pageSize = (int)($activityPreferences['items_per_page'] ?? 50);
  if ($pageSize < 20) $pageSize = 20;
  if ($pageSize > 200) $pageSize = 200;

  $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  if ($currentPage < 1) $currentPage = 1;
  if ($currentPage > 500) $currentPage = 500;
  $offset = ($currentPage - 1) * $pageSize;
  $queryLimit = $pageSize + 1;

  $stmt = $conn->prepare("
    SELECT
      id,
      action_title,
      page_name,
      event_type,
      notes,
      metadata_json,
      status,
      is_suspicious,
      device,
      ipv4,
      ipv6,
      point_color,
      user_id,
      created_at
    FROM activities
    WHERE user_id = ?
    ORDER BY created_at DESC, id DESC
    LIMIT ? OFFSET ?
  ");
  if ($stmt) {
    $stmt->bind_param("iii", $current_user_id, $queryLimit, $offset);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) {
        $activities[] = $row;
      }
    }
    $stmt->close();
  }

  if (count($activities) > $pageSize) {
    $hasMoreActivities = true;
    array_pop($activities);
  }
}

// status map for badges (db → css)
$statusBadgeMap = [
  'Success' => ['class' => 'status-success', 'label' => 'Success'],
  'Pending' => ['class' => 'status-pending', 'label' => 'Pending'],
  'Failed'  => ['class' => 'status-failed', 'label' => 'Failed'],
  'Warning' => ['class' => 'status-warning', 'label' => 'Warning'],
];

if (!function_exists('activity_status_tooltip')) {
  function activity_status_tooltip($status)
  {
    $map = [
      'Success' => 'This action completed successfully.',
      'Pending' => 'This action is still in progress.',
      'Failed' => 'This action did not complete successfully.',
      'Warning' => 'This action needs attention.',
    ];
    return $map[$status] ?? 'Activity status.';
  }
}

if (!function_exists('activity_event_icon')) {
  function activity_event_icon($eventType, $module)
  {
    $eventType = trim((string)$eventType);
    $module = trim((string)$module);

    $eventMap = [
      'trade.buy' => 'fas fa-arrow-circle-up',
      'trade.sell' => 'fas fa-arrow-circle-down',
      'watchlist.add' => 'fas fa-eye',
      'watchlist.remove' => 'fas fa-eye-slash',
      'portfolio.export_csv' => 'fas fa-file-csv',
      'account.profile_name_updated' => 'fas fa-user-edit',
      'account.email_updated' => 'fas fa-envelope-open-text',
      'account.password_changed' => 'fas fa-key',
      'auth.sign_in_failed' => 'fas fa-shield-alt',
      'auth.new_device_sign_in' => 'fas fa-laptop',
      'security.suspicious_signin' => 'fas fa-exclamation-triangle',
      'activity.archive_summary' => 'fas fa-archive',
    ];
    if (isset($eventMap[$eventType])) {
      return $eventMap[$eventType];
    }

    $moduleMap = [
      'trading' => 'fas fa-chart-line',
      'portfolio' => 'fas fa-briefcase',
      'account' => 'fas fa-user-cog',
      'dashboard' => 'fas fa-home',
    ];
    return $moduleMap[$module] ?? 'fas fa-circle';
  }
}

// helper so we don’t get XSS
if (!function_exists('activity_escape')) {
  function activity_escape($value)
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('activity_extract_symbol')) {
  function activity_extract_symbol($text)
  {
    $subject = strtoupper((string)$text);
    if (preg_match('/\b[A-Z]{1,6}(?:\.[A-Z]{1,2})?\b/', $subject, $m)) {
      return $m[0];
    }
    return null;
  }
}

if (!function_exists('activity_mask_ip')) {
  function activity_mask_ip($ip)
  {
    $ip = trim((string)$ip);
    if ($ip === '') {
      return 'IP protected';
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      $parts = explode('.', $ip);
      if (count($parts) === 4) {
        return $parts[0] . '.' . $parts[1] . '.x.x';
      }
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      $parts = explode(':', $ip);
      $parts = array_values(array_filter($parts, static function ($part) {
        return $part !== '';
      }));
      if (count($parts) >= 2) {
        return strtolower($parts[0] . ':' . $parts[1] . ':****');
      }
      return 'IP protected';
    }
    return 'IP protected';
  }
}

if (!function_exists('activity_summarize_device')) {
  function activity_summarize_device($ua)
  {
    $ua = strtolower((string)$ua);
    if ($ua === '' || $ua === 'unknown') {
      return 'Browser session';
    }

    $browser = 'Browser';
    if (strpos($ua, 'edg') !== false) {
      $browser = 'Edge';
    } elseif (strpos($ua, 'chrome') !== false) {
      $browser = 'Chrome';
    } elseif (strpos($ua, 'firefox') !== false) {
      $browser = 'Firefox';
    } elseif (strpos($ua, 'safari') !== false && strpos($ua, 'chrome') === false) {
      $browser = 'Safari';
    }

    $os = 'Desktop';
    if (strpos($ua, 'iphone') !== false || strpos($ua, 'ios') !== false) {
      $os = 'iOS';
    } elseif (strpos($ua, 'android') !== false) {
      $os = 'Android';
    } elseif (strpos($ua, 'windows') !== false) {
      $os = 'Windows';
    } elseif (strpos($ua, 'mac os') !== false || strpos($ua, 'macintosh') !== false) {
      $os = 'macOS';
    } elseif (strpos($ua, 'linux') !== false) {
      $os = 'Linux';
    }

    return $browser . ' on ' . $os;
  }
}

if (!function_exists('activity_normalize_title')) {
  function activity_normalize_title($rawTitle, $rawNotes)
  {
    $title = trim((string)$rawTitle);
    $notes = trim((string)$rawNotes);
    $joined = strtolower($title . ' ' . $notes);
    $symbol = activity_extract_symbol($title . ' ' . $notes);

    $exactAllowed = [
      'Updated profile name',
      'Updated email',
      'Changed password',
      'Exported portfolio CSV',
      'Signed in from new device',
      'Failed sign-in attempt'
    ];
    if (in_array($title, $exactAllowed, true)) {
      return $title;
    }

    if (strpos($joined, 'buy') !== false) {
      return $symbol ? ("Bought " . $symbol) : 'Bought stock';
    }
    if (strpos($joined, 'sell') !== false) {
      return $symbol ? ("Sold " . $symbol) : 'Sold stock';
    }
    if (strpos($joined, 'watchlist') !== false && (strpos($joined, 'remove') !== false || strpos($joined, 'delete') !== false)) {
      return $symbol ? ("Removed " . $symbol . " from watchlist") : 'Removed stock from watchlist';
    }
    if (strpos($joined, 'watchlist') !== false && (strpos($joined, 'add') !== false || strpos($joined, 'track') !== false)) {
      return $symbol ? ("Added " . $symbol . " to watchlist") : 'Added stock to watchlist';
    }
    if (strpos($joined, 'export') !== false) {
      return 'Exported portfolio CSV';
    }
    if (strpos($joined, 'password') !== false && (strpos($joined, 'change') !== false || strpos($joined, 'update') !== false)) {
      return 'Changed password';
    }
    if (strpos($joined, 'profile') !== false && strpos($joined, 'name') !== false && strpos($joined, 'update') !== false) {
      return 'Updated profile name';
    }
    if (strpos($joined, 'email') !== false && strpos($joined, 'update') !== false) {
      return 'Updated email';
    }
    if (strpos($joined, 'failed sign-in') !== false || strpos($joined, 'sign-in attempt') !== false) {
      return 'Failed sign-in attempt';
    }

    return $title !== '' ? $title : 'Activity';
  }
}

if (!function_exists('activity_detect_module')) {
  function activity_detect_module($page, $title, $notes)
  {
    $hay = strtolower(trim((string)$page . ' ' . (string)$title . ' ' . (string)$notes));

    if (
      strpos($hay, 'sign-in') !== false ||
      strpos($hay, 'login') !== false ||
      strpos($hay, 'otp') !== false ||
      strpos($hay, 'password') !== false ||
      strpos($hay, 'security') !== false ||
      strpos($hay, 'auth') !== false
    ) {
      return 'account';
    }
    if (strpos($hay, 'buy') !== false || strpos($hay, 'sell') !== false || strpos($hay, 'trade') !== false) {
      return 'trading';
    }
    if (
      strpos($hay, 'watchlist') !== false ||
      strpos($hay, 'portfolio') !== false ||
      strpos($hay, 'export') !== false ||
      strpos($hay, 'holding') !== false ||
      strpos($hay, 'transaction') !== false ||
      strpos($hay, 'performance') !== false ||
      strpos($hay, 'screener') !== false
    ) {
      return 'portfolio';
    }
    if (
      strpos($hay, 'profile') !== false ||
      strpos($hay, 'account') !== false ||
      strpos($hay, 'name') !== false ||
      strpos($hay, 'email') !== false ||
      strpos($hay, 'setting') !== false ||
      strpos($hay, 'notification') !== false ||
      strpos($hay, 'reminder') !== false
    ) {
      return 'account';
    }
    if (strpos($hay, 'search') !== false || strpos($hay, 'dashboard') !== false) {
      return 'dashboard';
    }

    return 'dashboard';
  }
}

if (!function_exists('activity_module_label')) {
  function activity_module_label($module)
  {
    $labels = [
      'dashboard' => 'Dashboard',
      'trading' => 'Trading',
      'portfolio' => 'Portfolio',
      'account' => 'Account',
    ];
    return $labels[$module] ?? 'Dashboard';
  }
}

if (!function_exists('activity_public_notes')) {
  function activity_public_notes($title, $module, $status, $rawNotes)
  {
    $title = (string)$title;
    $status = (string)$status;
    if ($title === 'Updated profile name' || $title === 'Updated email') {
      return 'Profile details were updated.';
    }
    if ($title === 'Changed password') {
      return 'Security credentials were updated.';
    }
    if ($title === 'Signed in from new device') {
      return 'A new-device sign-in was detected.';
    }
    if ($title === 'Failed sign-in attempt') {
      return 'A sign-in attempt was rejected.';
    }
    if (strpos($title, 'Bought ') === 0) {
      return $status === 'Failed' ? 'Buy order failed.' : 'Buy order recorded.';
    }
    if (strpos($title, 'Sold ') === 0) {
      return $status === 'Failed' ? 'Sell order failed.' : 'Sell order recorded.';
    }
    if (strpos($title, 'Added ') === 0 && strpos($title, 'watchlist') !== false) {
      return $status === 'Failed' ? 'Watchlist update failed.' : 'Watchlist item added.';
    }
    if (strpos($title, 'Removed ') === 0 && strpos($title, 'watchlist') !== false) {
      return $status === 'Failed' ? 'Watchlist update failed.' : 'Watchlist item removed.';
    }
    if ($title === 'Exported portfolio CSV') {
      return $status === 'Failed' ? 'Portfolio export failed.' : 'Portfolio export completed.';
    }
    if ($module === 'security' || $module === 'account') {
      return 'Account activity updated.';
    }

    $rawNotes = trim((string)$rawNotes);
    return $rawNotes !== '' ? $rawNotes : 'Activity recorded.';
  }
}

if (!function_exists('activity_title_from_event')) {
  function activity_title_from_event($eventType, $metadata, $fallbackTitle, $fallbackNotes)
  {
    $eventType = trim((string)$eventType);
    $metadata = is_array($metadata) ? $metadata : [];
    $symbol = trim((string)($metadata['symbol'] ?? ''));
    if ($symbol === '') {
      $symbol = (string)(activity_extract_symbol($fallbackTitle . ' ' . $fallbackNotes) ?? '');
    }
    if ($symbol !== '') {
      $symbol = strtoupper($symbol);
    }

    if ($eventType === 'trade.buy') {
      return $symbol !== '' ? "Bought {$symbol}" : 'Bought stock';
    }
    if ($eventType === 'trade.sell') {
      return $symbol !== '' ? "Sold {$symbol}" : 'Sold stock';
    }
    if ($eventType === 'watchlist.add') {
      return $symbol !== '' ? "Added {$symbol} to watchlist" : 'Added stock to watchlist';
    }
    if ($eventType === 'watchlist.remove') {
      return $symbol !== '' ? "Removed {$symbol} from watchlist" : 'Removed stock from watchlist';
    }
    if ($eventType === 'portfolio.export_csv') {
      return 'Exported portfolio CSV';
    }
    if ($eventType === 'account.profile_name_updated') {
      return 'Updated profile name';
    }
    if ($eventType === 'account.email_updated') {
      return 'Updated email';
    }
    if ($eventType === 'account.password_changed') {
      return 'Changed password';
    }
    if ($eventType === 'auth.sign_in_failed') {
      return 'Failed sign-in attempt';
    }
    if ($eventType === 'auth.new_device_sign_in') {
      return 'Signed in from new device';
    }
    if ($eventType === 'security.suspicious_signin') {
      return 'Suspicious sign-in activity';
    }

    return activity_normalize_title($fallbackTitle, $fallbackNotes);
  }
}

if (!function_exists('activity_is_always_visible_event')) {
  function activity_is_always_visible_event($eventType)
  {
    $eventType = trim((string)$eventType);
    if ($eventType === 'account.password_changed') {
      return true;
    }
    if (strpos($eventType, 'auth.') === 0) {
      return true;
    }
    if (strpos($eventType, 'security.') === 0) {
      return true;
    }
    return false;
  }
}

if (!function_exists('activity_section_key')) {
  function activity_section_key($createdAt)
  {
    $ts = strtotime((string)$createdAt);
    if (!$ts) {
      return 'earlier';
    }
    $date = date('Y-m-d', $ts);
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($date === $today) {
      return 'today';
    }
    if ($date === $yesterday) {
      return 'yesterday';
    }
    return 'earlier';
  }
}

if (!function_exists('activity_section_label')) {
  function activity_section_label($section)
  {
    if ($section === 'today') {
      return 'Today';
    }
    if ($section === 'yesterday') {
      return 'Yesterday';
    }
    return 'Earlier';
  }
}

if (!function_exists('activity_decode_metadata')) {
  function activity_decode_metadata($json)
  {
    $json = trim((string)$json);
    if ($json === '') return [];
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
  }
}

if (!function_exists('activity_resolve_event_type')) {
  function activity_resolve_event_type($row, $title, $module)
  {
    $eventType = trim((string)($row['event_type'] ?? ''));
    if ($eventType !== '') {
      return $eventType;
    }
    return ActivityLogger::deriveEventType($title, (string)($row['page_name'] ?? ''), $module);
  }
}

if (!function_exists('activity_build_events')) {
  function activity_build_events($rows, $prefs = [])
  {
    $muted = [];
    $mutedInput = isset($prefs['muted_event_types']) && is_array($prefs['muted_event_types']) ? $prefs['muted_event_types'] : [];
    foreach ($mutedInput as $type) {
      $type = trim((string)$type);
      if ($type !== '') $muted[$type] = true;
    }

    $events = [];
    foreach ($rows as $row) {
      $dbStatus = in_array(($row['status'] ?? 'Success'), ['Success', 'Pending', 'Failed', 'Warning'], true)
        ? ($row['status'] ?? 'Success')
        : 'Success';
      $metadata = activity_decode_metadata($row['metadata_json'] ?? '');
      $fallbackTitle = activity_normalize_title($row['action_title'] ?? '', $row['notes'] ?? '');
      $module = activity_detect_module($row['page_name'] ?? '', $fallbackTitle, $row['notes'] ?? '');
      $eventType = activity_resolve_event_type($row, $fallbackTitle, $module);
      $title = activity_title_from_event($eventType, $metadata, $fallbackTitle, (string)($row['notes'] ?? ''));
      if (isset($muted[$eventType]) && !activity_is_always_visible_event($eventType)) {
        continue;
      }

      $createdRaw = $row['created_at'] ?? '';
      $timestamp = strtotime((string)$createdRaw);
      $dateIso = $createdRaw ? date('c', $timestamp ?: time()) : '';
      $dateExact = $createdRaw ? date('M j, Y - g:i A', $timestamp ?: time()) : '';
      $safeDevice = activity_summarize_device($row['device'] ?? '');
      $safeIp = activity_mask_ip(($row['ipv4'] ?? '') !== '' ? ($row['ipv4'] ?? '') : ($row['ipv6'] ?? ''));
      $notes = activity_public_notes($title, $module, $dbStatus, $row['notes'] ?? '');

      $events[] = [
        'id' => (int)($row['id'] ?? 0),
        'title' => $title,
        'module' => $module,
        'module_label' => activity_module_label($module),
        'event_type' => $eventType,
        'status' => $dbStatus,
        'notes' => $notes,
        'safe_device' => $safeDevice,
        'safe_ip' => $safeIp,
        'date_iso' => $dateIso,
        'date_exact' => $dateExact,
        'created_ts' => (int)($timestamp ?: 0),
        'metadata' => $metadata,
        'is_important' => in_array($title, ['Changed password', 'Signed in from new device', 'Failed sign-in attempt'], true),
        'is_suspicious' => (int)($row['is_suspicious'] ?? 0) === 1,
        'group_count' => 1,
        'group_items' => [[
          'when' => $dateExact,
          'status' => $dbStatus,
          'notes' => $notes,
        ]],
      ];
    }

    $windowMinutes = (int)($prefs['grouping_window_minutes'] ?? 12);
    if ($windowMinutes < 3) $windowMinutes = 3;
    if ($windowMinutes > 60) $windowMinutes = 60;
    $windowSeconds = $windowMinutes * 60;

    $groupableTypes = [
      'trade.buy' => true,
      'trade.sell' => true,
      'watchlist.add' => true,
      'watchlist.remove' => true,
    ];

    $grouped = [];
    foreach ($events as $event) {
      $canGroup = isset($groupableTypes[$event['event_type']]);
      if ($canGroup && !empty($grouped)) {
        $lastIndex = count($grouped) - 1;
        $last = $grouped[$lastIndex];
        if (
          isset($groupableTypes[$last['event_type']]) &&
          $last['event_type'] === $event['event_type'] &&
          $last['title'] === $event['title'] &&
          $last['status'] === $event['status'] &&
          $last['module'] === $event['module'] &&
          abs($last['created_ts'] - $event['created_ts']) <= $windowSeconds
        ) {
          $grouped[$lastIndex]['group_count'] += 1;
          $grouped[$lastIndex]['group_items'][] = $event['group_items'][0];
          continue;
        }
      }
      $grouped[] = $event;
    }

    $final = [];
    $i = 0;
    $suspiciousWindowSeconds = 15 * 60;
    while ($i < count($grouped)) {
      $event = $grouped[$i];
      if ($event['event_type'] === 'auth.sign_in_failed') {
        $isWindowStart = (
          $i === 0 ||
          $grouped[$i - 1]['event_type'] !== 'auth.sign_in_failed' ||
          (($grouped[$i - 1]['created_ts'] - $event['created_ts']) > $suspiciousWindowSeconds)
        );
        if ($isWindowStart) {
          $count = 1;
          $j = $i + 1;
          while ($j < count($grouped)) {
            $next = $grouped[$j];
            if ($next['event_type'] !== 'auth.sign_in_failed') break;
            if (($event['created_ts'] - $next['created_ts']) > $suspiciousWindowSeconds) break;
            $count++;
            $j++;
          }
          if ($count >= 3) {
            $final[] = [
              'id' => 0,
              'title' => 'Suspicious sign-in activity',
              'module' => 'account',
              'module_label' => 'Account',
              'event_type' => 'security.suspicious_signin',
              'status' => 'Warning',
              'notes' => 'Multiple failed sign-in attempts were detected in a short period.',
              'safe_device' => 'Security monitor',
              'safe_ip' => 'IP protected',
              'date_iso' => $event['date_iso'],
              'date_exact' => $event['date_exact'],
              'created_ts' => $event['created_ts'],
              'metadata' => ['failed_attempts' => $count, 'window_minutes' => 15],
              'is_important' => true,
              'is_suspicious' => true,
              'group_count' => 1,
              'group_items' => [[
                'when' => $event['date_exact'],
                'status' => 'Warning',
                'notes' => "Detected {$count} failed attempts in 15 minutes."
              ]]
            ];
          }
        }
      }
      $final[] = $event;
      $i++;
    }

    $archiveThreshold = strtotime('-90 days');
    $archiveableTypes = [
      'watchlist.add' => true,
      'watchlist.remove' => true,
      'portfolio.export_csv' => true,
      'activity.generic' => true,
    ];

    $archiveBuckets = [];
    $preserved = [];
    foreach ($final as $event) {
      $eventType = (string)($event['event_type'] ?? 'activity.generic');
      $status = (string)($event['status'] ?? 'Success');
      $createdTs = (int)($event['created_ts'] ?? 0);
      $isArchiveCandidate = (
        $createdTs > 0 &&
        $createdTs < $archiveThreshold &&
        isset($archiveableTypes[$eventType]) &&
        in_array($status, ['Success', 'Pending'], true) &&
        empty($event['is_important']) &&
        empty($event['is_suspicious'])
      );
      if (!$isArchiveCandidate) {
        $preserved[] = $event;
        continue;
      }

      $bucketKey = date('Y-m', $createdTs);
      if (!isset($archiveBuckets[$bucketKey])) {
        $archiveBuckets[$bucketKey] = [
          'count' => 0,
          'latest_ts' => $createdTs,
          'group_items' => [],
        ];
      }
      $archiveBuckets[$bucketKey]['count']++;
      if ($createdTs > $archiveBuckets[$bucketKey]['latest_ts']) {
        $archiveBuckets[$bucketKey]['latest_ts'] = $createdTs;
      }
      $archiveBuckets[$bucketKey]['group_items'][] = [
        'when' => (string)($event['date_exact'] ?? ''),
        'status' => $status,
        'notes' => (string)($event['notes'] ?? ''),
      ];
    }

    foreach ($archiveBuckets as $bucketKey => $bucketData) {
      $ts = (int)$bucketData['latest_ts'];
      $monthLabel = date('M Y', strtotime($bucketKey . '-01'));
      $count = (int)$bucketData['count'];
      $preserved[] = [
        'id' => 0,
        'title' => 'Archived routine activity',
        'module' => 'account',
        'module_label' => 'Account',
        'event_type' => 'activity.archive_summary',
        'status' => 'Success',
        'notes' => "{$count} low-priority events were archived from {$monthLabel}.",
        'safe_device' => 'Archive engine',
        'safe_ip' => 'IP protected',
        'date_iso' => date('c', $ts),
        'date_exact' => date('M j, Y - g:i A', $ts),
        'created_ts' => $ts,
        'metadata' => [
          'archived_count' => $count,
          'month' => $monthLabel,
        ],
        'is_important' => false,
        'is_suspicious' => false,
        'group_count' => max(1, $count),
        'group_items' => $bucketData['group_items'],
      ];
    }

    usort($preserved, static function ($a, $b) {
      $aTs = (int)($a['created_ts'] ?? 0);
      $bTs = (int)($b['created_ts'] ?? 0);
      if ($aTs === $bTs) {
        $aId = (int)($a['id'] ?? 0);
        $bId = (int)($b['id'] ?? 0);
        return $bId <=> $aId;
      }
      return $bTs <=> $aTs;
    });

    return $preserved;
  }
}

$timelineEvents = activity_build_events($activities, $activityPreferences);

$activityStats = [
  'today_total' => 0,
  'today_trades' => 0,
  'today_account' => 0,
  'today_warnings' => 0,
];
$todayStart = strtotime(date('Y-m-d 00:00:00'));
foreach ($timelineEvents as $event) {
  $ts = (int)($event['created_ts'] ?? 0);
  if ($ts < $todayStart) {
    continue;
  }
  $activityStats['today_total']++;
  if (($event['module'] ?? '') === 'trading') {
    $activityStats['today_trades']++;
  }
  if (($event['module'] ?? '') === 'account') {
    $activityStats['today_account']++;
  }
  if (in_array(($event['status'] ?? 'Success'), ['Warning', 'Failed'], true)) {
    $activityStats['today_warnings']++;
  }
}
?>

<section class="content-header">
  <div class="container activity-container"></div>
</section>

<section class="content">
  <div class="container activity-container">

    <!-- ===== Filters ===== -->
    <div class="card mb-3 shadow-sm filters-sticky">
      <div class="card-body">
        <form id="filtersForm">
          <div class="form-row align-items-start">
            <!-- Date Range -->
            <div class="form-group col-12 col-md-6 col-lg-4">
              <label class="small text-muted d-block">Date range</label>
              <div class="date-range-control">
                <span class="dr-icon"><i class="far fa-calendar-alt"></i></span>
                <input type="date" class="form-control dr-input" id="fltStart" placeholder="mm/dd/yyyy">
                <span class="dr-sep">to</span>
                <input type="date" class="form-control dr-input" id="fltEnd" placeholder="mm/dd/yyyy">
              </div>
              <div class="preset-row small mt-1">
                <a href="#" data-preset="7d"  class="mr-2 preset-link">Last 7d</a>
                <a href="#" data-preset="30d" class="mr-2 preset-link">30d</a>
                <a href="#" data-preset="ytd" class="preset-link">YTD</a>
              </div>
            </div>

            <!-- Status -->
            <div class="form-group col-12 col-md-6 col-lg-4">
              <label class="small text-muted d-block">Status</label>
              <select id="fltStatus" class="form-control">
                <option value="all">All</option>
                <option value="success">Success</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
                <option value="warning">Warning</option>
              </select>
            </div>

            <!-- Search -->
            <div class="form-group col-12 col-md-6 col-lg-4">
              <label class="small text-muted d-block">Search</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input id="fltSearch" type="text" class="form-control" placeholder="Symbol, action, device, etc.">
              </div>
            </div>
          </div>

          <div class="form-group mb-2">
            <label class="small text-muted d-block">Category</label>
            <div class="category-chip-row" id="categoryFilter">
              <button type="button" class="btn btn-sm btn-outline-secondary category-chip is-active" data-category="all">All</button>
              <button type="button" class="btn btn-sm btn-outline-secondary category-chip" data-category="trading">Trading</button>
              <button type="button" class="btn btn-sm btn-outline-secondary category-chip" data-category="account">Account</button>
              <button type="button" class="btn btn-sm btn-outline-secondary category-chip" data-category="portfolio">Portfolio</button>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex flex-wrap justify-content-end">
            <button type="button" class="btn btn-primary btn-sm mr-2" id="btnApply">
              <i class="fas fa-filter mr-1"></i>Apply
            </button>
            <button type="button" class="btn btn-outline-success btn-sm mr-2" id="btnExportCsv">
              <i class="fas fa-file-csv mr-1"></i>Export Filtered CSV
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm mr-2" data-toggle="modal" data-target="#activityPrefsModal">
              <i class="fas fa-cog mr-1"></i>Preferences
            </button>
            <div class="d-flex align-items-center">
              <button type="button" class="btn btn-outline-secondary btn-sm mr-3" id="btnReset">Reset</button>
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="maskSensitive" checked>
                <label class="custom-control-label" for="maskSensitive">Mask sensitive details</label>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
    <!-- ===== /Filters ===== -->

    <div class="card mb-3 shadow-sm">
      <div class="card-body py-2">
        <div class="activity-metric-row">
          <span class="badge badge-light metric-chip">Today events <strong id="mTodayTotal"><?= (int)$activityStats['today_total']; ?></strong></span>
          <span class="badge badge-light metric-chip">Today trades <strong id="mTodayTrades"><?= (int)$activityStats['today_trades']; ?></strong></span>
          <span class="badge badge-light metric-chip">Account changes <strong id="mTodayAccount"><?= (int)$activityStats['today_account']; ?></strong></span>
          <span class="badge badge-light metric-chip">Warnings/failed <strong id="mTodayWarn"><?= (int)$activityStats['today_warnings']; ?></strong></span>
        </div>
      </div>
    </div>

    <!-- ===== Timeline ===== -->
    <div class="card card-outline activity-card">
      <div class="card-body p-0">
        <div class="activity-viewport">

          <ul class="activity-timeline-alt" id="activityList">
            <?php if (count($timelineEvents) === 0): ?>
              <!-- we’ll also show empty state below via JS -->
            <?php else: ?>
              <?php $visibleIndex = 0; $lastSectionKey = ''; ?>
              <?php foreach ($timelineEvents as $event): ?>
                <?php
                  $dbStatus = $event['status'];
                  $map = $statusBadgeMap[$dbStatus] ?? ['class' => 'status-info', 'label' => 'Info'];
                  $module = $event['module'];
                  $moduleLabel = $event['module_label'];
                  $normalizedTitle = $event['title'];
                  $normalizedNotes = $event['notes'];
                  $safeDevice = $event['safe_device'];
                  $safeIp = $event['safe_ip'];
                  $isImportant = !empty($event['is_important']) || !empty($event['is_suspicious']);
                  $groupCount = (int)($event['group_count'] ?? 1);
                  $groupItemsJson = activity_escape(json_encode($event['group_items'] ?? [], JSON_UNESCAPED_SLASHES));
                  $eventType = (string)($event['event_type'] ?? 'activity.generic');
                  $eventIcon = activity_event_icon($eventType, $module);
                  $statusHelp = activity_status_tooltip($dbStatus);

                  // section headers
                  $sectionKey = activity_section_key($event['date_iso'] ?? '');
                  $sectionLabel = activity_section_label($sectionKey);

                  // alternate left/right only for visible event rows
                  $alignClass = ($visibleIndex % 2 === 0) ? 'alt-left' : 'alt-right';
                  $visibleIndex++;
                ?>
                <?php if ($sectionKey !== $lastSectionKey): ?>
                  <li class="tl-day-separator" data-section="<?= activity_escape($sectionKey); ?>">
                    <span><?= activity_escape($sectionLabel); ?></span>
                  </li>
                  <?php $lastSectionKey = $sectionKey; ?>
                <?php endif; ?>

                <li class="tl-item <?= activity_escape($alignClass); ?>"
                    data-module="<?= activity_escape($module); ?>"
                    data-category="<?= activity_escape($module); ?>"
                    data-status="<?= activity_escape(strtolower($dbStatus)); ?>"
                    data-action="<?= activity_escape($normalizedTitle); ?>"
                    data-device="<?= activity_escape($safeDevice); ?>"
                    data-ip="<?= activity_escape($safeIp); ?>"
                    data-date="<?= activity_escape($event['date_iso']); ?>"
                    data-exact="<?= activity_escape($event['date_exact']); ?>"
                    data-title="<?= activity_escape($normalizedTitle); ?>"
                    data-notes="<?= activity_escape($normalizedNotes); ?>"
                    data-section="<?= activity_escape($sectionKey); ?>"
                    data-important="<?= $isImportant ? '1' : '0'; ?>"
                    data-event-type="<?= activity_escape($event['event_type']); ?>"
                    data-group-count="<?= $groupCount; ?>"
                    data-group-items="<?= $groupItemsJson; ?>"
                    data-id="<?= (int)($event['id'] ?? 0); ?>">

                  <?php if ($alignClass === 'alt-left'): ?>
                    <div class="tl-time"></div>
                    <div class="tl-node"></div>
                    <div class="tl-card <?= $isImportant ? 'is-important' : ''; ?>" role="button" tabindex="0">
                      <div class="d-flex align-items-center justify-content-between">
                        <div class="tl-title">
                          <i class="<?= activity_escape($eventIcon); ?> tl-event-icon" aria-hidden="true"></i>
                          <?= activity_escape($normalizedTitle); ?>
                          <?php if ($groupCount > 1): ?>
                            <span class="badge badge-dark ml-2">x<?= (int)$groupCount; ?></span>
                          <?php endif; ?>
                          <span class="badge badge-light text-muted ml-2">
                            <?= activity_escape($moduleLabel); ?>
                          </span>
                          <?php if ($isImportant): ?>
                            <span class="badge badge-danger ml-2">Important</span>
                          <?php endif; ?>
                        </div>
                        <span class="status-badge <?= activity_escape($map['class']); ?>" data-toggle="tooltip" data-placement="top" title="<?= activity_escape($statusHelp); ?>"><?= activity_escape($map['label']); ?></span>
                      </div>
                      <?php if (!empty($normalizedNotes)): ?>
                        <ul class="tl-list">
                          <li><?= activity_escape($normalizedNotes); ?></li>
                        </ul>
                      <?php endif; ?>
                      <p class="muted-detail small mb-0 mt-2">
                        Session · <?= activity_escape($safeDevice); ?> · <?= activity_escape($safeIp); ?>
                      </p>
                    </div>
                  <?php else: ?>
                    <div class="tl-card <?= $isImportant ? 'is-important' : ''; ?>" role="button" tabindex="0">
                      <div class="d-flex align-items-center justify-content-between">
                        <div class="tl-title">
                          <i class="<?= activity_escape($eventIcon); ?> tl-event-icon" aria-hidden="true"></i>
                          <?= activity_escape($normalizedTitle); ?>
                          <?php if ($groupCount > 1): ?>
                            <span class="badge badge-dark ml-2">x<?= (int)$groupCount; ?></span>
                          <?php endif; ?>
                          <span class="badge badge-light text-muted ml-2">
                            <?= activity_escape($moduleLabel); ?>
                          </span>
                          <?php if ($isImportant): ?>
                            <span class="badge badge-danger ml-2">Important</span>
                          <?php endif; ?>
                        </div>
                        <span class="status-badge <?= activity_escape($map['class']); ?>" data-toggle="tooltip" data-placement="top" title="<?= activity_escape($statusHelp); ?>"><?= activity_escape($map['label']); ?></span>
                      </div>
                      <?php if (!empty($normalizedNotes)): ?>
                        <ul class="tl-list">
                          <li><?= activity_escape($normalizedNotes); ?></li>
                        </ul>
                      <?php endif; ?>
                      <p class="muted-detail small mb-0 mt-2">
                        Session · <?= activity_escape($safeDevice); ?> · <?= activity_escape($safeIp); ?>
                      </p>
                    </div>
                    <div class="tl-node"></div>
                    <div class="tl-time"></div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>

          <div id="emptyState" class="text-center py-4 <?= count($timelineEvents) ? 'd-none' : ''; ?>">
            <p class="mb-1">No activity matches your filters.</p>
            <small class="text-muted">Try widening the date range or adjusting your search.</small>
            <div class="empty-actions mt-3">
              <button type="button" class="btn btn-outline-primary btn-sm mr-2" data-empty-action="7d">Last 7 days</button>
              <button type="button" class="btn btn-outline-primary btn-sm mr-2" data-empty-action="30d">Last 30 days</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-empty-action="reset">Reset filters</button>
            </div>
          </div>

          <?php if ($hasMoreActivities): ?>
            <div class="text-center py-3 border-top">
              <a class="btn btn-outline-secondary btn-sm" id="loadOlderBtn" href="?page=<?= (int)($currentPage + 1); ?>">Load older activity</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ===== Event Details Modal ===== -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventModalLabel">Event details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0 small">
          <dt class="col-4 text-muted">Title</dt><dd class="col-8" id="mdTitle"></dd>
          <dt class="col-4 text-muted">Event type</dt><dd class="col-8"><span class="badge badge-light text-muted" id="mdEventType"></span></dd>
          <dt class="col-4 text-muted">Status</dt><dd class="col-8" id="mdStatus"></dd>
          <dt class="col-4 text-muted">When</dt><dd class="col-8" id="mdWhen"></dd>
          <dt class="col-4 text-muted">Device</dt><dd class="col-8" id="mdDevice"></dd>
          <dt class="col-4 text-muted">IP</dt><dd class="col-8" id="mdIP"></dd>
          <dt class="col-4 text-muted">Notes</dt><dd class="col-8" id="mdNotes"></dd>
          <dt class="col-4 text-muted d-none" id="mdGroupLabel">Grouped</dt>
          <dd class="col-8 d-none" id="mdGroupWrap">
            <ul class="pl-3 mb-0" id="mdGroupList"></ul>
          </dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-primary" id="btnCopyDetails">Copy details</button>
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== Activity Preferences Modal ===== -->
<div class="modal fade" id="activityPrefsModal" tabindex="-1" aria-labelledby="activityPrefsLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="activityPrefsLabel">Activity Preferences</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <?php $mutedTypes = is_array($activityPreferences['muted_event_types'] ?? null) ? $activityPreferences['muted_event_types'] : []; ?>
          <p class="small text-muted mb-2">Mute low-value events (security alerts always remain visible).</p>

          <div class="custom-control custom-checkbox mb-2">
            <input class="custom-control-input" type="checkbox" id="muteExportCsv" name="muted_event_types[]" value="portfolio.export_csv" <?= in_array('portfolio.export_csv', $mutedTypes, true) ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="muteExportCsv">Mute export completed events</label>
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input class="custom-control-input" type="checkbox" id="muteWatchAdd" name="muted_event_types[]" value="watchlist.add" <?= in_array('watchlist.add', $mutedTypes, true) ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="muteWatchAdd">Mute watchlist add events</label>
          </div>
          <div class="custom-control custom-checkbox mb-3">
            <input class="custom-control-input" type="checkbox" id="muteWatchRemove" name="muted_event_types[]" value="watchlist.remove" <?= in_array('watchlist.remove', $mutedTypes, true) ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="muteWatchRemove">Mute watchlist remove events</label>
          </div>

          <div class="form-group">
            <label class="small text-muted">Items per page</label>
            <?php $itemsPerPage = (int)($activityPreferences['items_per_page'] ?? 50); ?>
            <select class="form-control" name="items_per_page">
              <option value="20" <?= $itemsPerPage === 20 ? 'selected' : ''; ?>>20</option>
              <option value="50" <?= $itemsPerPage === 50 ? 'selected' : ''; ?>>50</option>
              <option value="100" <?= $itemsPerPage === 100 ? 'selected' : ''; ?>>100</option>
              <option value="200" <?= $itemsPerPage === 200 ? 'selected' : ''; ?>>200</option>
            </select>
          </div>

          <div class="form-group mb-0">
            <label class="small text-muted">Burst grouping window (minutes)</label>
            <?php $groupWindow = (int)($activityPreferences['grouping_window_minutes'] ?? 12); ?>
            <select class="form-control" name="grouping_window_minutes">
              <option value="5" <?= $groupWindow === 5 ? 'selected' : ''; ?>>5</option>
              <option value="10" <?= $groupWindow === 10 ? 'selected' : ''; ?>>10</option>
              <option value="12" <?= $groupWindow === 12 ? 'selected' : ''; ?>>12</option>
              <option value="15" <?= $groupWindow === 15 ? 'selected' : ''; ?>>15</option>
              <option value="30" <?= $groupWindow === 30 ? 'selected' : ''; ?>>30</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <input type="hidden" name="save_activity_prefs" value="1">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save preferences</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
/* ===== Theme tokens & alignment math ===== */
:root{
  --connector: 96px;
  --thickness: 1.25px;
  --line-color: #cbd5e1;
  --dot: 22px;
  --dot-border: 3px;
  --dot-outline: 3px;
  --spine-gap: calc(var(--dot)/2 + var(--dot-border) + var(--dot-outline));
  --card-bg:#ffffff;
  --card-border:#e5e7eb;
  --card-shadow:rgba(0,0,0,.06);
  --tint-dashboard:34,197,94;
  --tint-trading:251,191,36;
  --tint-portfolio:14,165,233;
  --tint-account:239,68,68;
}
.dark-mode{
  --card-bg:#0f172a;
  --card-border:#273042;
  --card-shadow:rgba(0,0,0,.45);
}
.activity-container{max-width:100%;padding:0 14px}
.activity-card{background:var(--card-bg);border-color:var(--card-border);box-shadow:0 2px 6px var(--card-shadow)}
.filters-sticky{position:relative;top:auto;z-index:1}
.activity-viewport{height:calc(100vh - 220px);overflow-y:auto;overflow-x:hidden;padding:16px 16px 24px}
.activity-timeline-alt{position:relative;list-style:none;margin:0;padding:8px 0 16px}
.activity-timeline-alt::before{
  content:"";position:absolute;left:50%;top:0;transform:translateX(-50%);
  width:80px;height:100%;pointer-events:none;z-index:0;background-repeat:repeat-y;background-position:center top;background-size:100% 260px;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 100' preserveAspectRatio='none'><path d='M40 0 C 15 10, 65 20, 40 30 S 15 45, 40 55 S 65 70, 40 80 S 15 90, 40 100' stroke='%23cbd5e1' stroke-width='3.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/><path d='M40 -2 C 12 8, 68 18, 40 30 S 12 45, 40 55 S 68 70, 40 82 S 12 92, 40 104' stroke='white' stroke-opacity='0.6' stroke-width='1.4' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>");
}
.tl-item{--time-offset:120px;--card-offset:120px;--node-offset-x:0px;--node-offset-y:0px;display:grid;grid-template-columns:1fr 28px 1fr;align-items:center;position:relative;margin:44px 0;z-index:1}
.tl-item::after{
  content:"";position:absolute;top:calc(50% + var(--node-offset-y,0px));transform:translateY(-50%);width:var(--connector);height:0;
  border-top:var(--thickness) solid var(--line-color);z-index:1;pointer-events:none;
}
.tl-item.alt-left::after{
  left:calc(50% + var(--spine-gap) + var(--node-offset-x,0px));
}
.tl-item.alt-right::after{
  right:calc(50% + var(--spine-gap) - var(--node-offset-x,0px));
}
.tl-time{grid-column:1;font-size:.95rem;color:#9aa0a6;text-align:right;padding-right:var(--time-offset);white-space:nowrap;}
.tl-time::after{
  content:"";position:absolute;top:calc(50% + var(--node-offset-y,0px));transform:translateY(-50%);width:var(--connector);height:0;
  border-top:var(--thickness) solid var(--line-color);
  right:calc(50% + var(--spine-gap) - var(--node-offset-x,0px));
}
.tl-node{
  grid-column:2;width:var(--dot);height:var(--dot);border-radius:50%;
  border:var(--dot-border) solid #fff;box-shadow:0 0 0 var(--dot-outline) rgba(0,0,0,.12);
  justify-self:center;background:var(--module-solid);transform:translate(var(--node-offset-x,0px),var(--node-offset-y,0px));
  z-index:2;
}
.tl-item{--module-rgb:34,197,94;--module-solid:#22c55e}
.tl-item[data-module="trading"]{--module-rgb:251,191,36;--module-solid:#fbbf24}
.tl-item[data-module="portfolio"]{--module-rgb:14,165,233;--module-solid:#0ea5e9}
.tl-item[data-module="account"]{--module-rgb:239,68,68;--module-solid:#ef4444}
.tl-card{
  grid-column:3;background:var(--card-bg);border:1px solid var(--card-border);border-radius:12px;
  padding:10px 12px;max-width:clamp(460px,44vw,640px);
  transition:box-shadow .2s ease,border-color .2s ease,background-color .2s ease;
  position:relative;
}
.tl-card::after{
  content:"";position:absolute;inset:0;border-radius:inherit;pointer-events:none;
  background:radial-gradient(circle at var(--gx,50%) var(--gy,50%), rgba(var(--module-rgb),.18), rgba(var(--module-rgb),0) 58%);
  opacity:0;transition:opacity .18s ease;z-index:1;
}
.tl-card > *{position:relative;z-index:3}
.tl-item.alt-right .tl-card{grid-column:1}
.tl-item.alt-right .tl-time{grid-column:3;text-align:left;padding-right:0;padding-left:var(--time-offset)}
.tl-item.alt-right .tl-time::after{right:auto;left:calc(50% + var(--spine-gap) + var(--node-offset-x,0px))}
.tl-item.alt-left  .tl-card{margin-left:var(--card-offset)}
.tl-item.alt-right .tl-card{margin-right:var(--card-offset)}
.tl-title{font-weight:600;color:#111827;margin-bottom:6px}
.dark-mode .tl-title{color:#e5e7eb}
.tl-list{margin:0;padding-left:18px;color:#374151}
.dark-mode .tl-list{color:#cbd5e1}
.status-badge{font-size:.72rem;border-radius:999px;padding:.25rem .5rem}
.status-success{background:#eaf7ee;color:#207245}
.status-pending{background:#fff2db;color:#8a5a00}
.status-warning{background:#fff2db;color:#8a5a00}
.status-failed{background:#fee2e2;color:#991b1b}
.status-info{background:#edf2ff;color:#3949ab}
.dark-mode .status-success{background:rgba(34,197,94,.15);color:#86efac}
.dark-mode .status-pending{background:rgba(245,158,11,.15);color:#fbbf24}
.dark-mode .status-warning{background:rgba(245,158,11,.15);color:#fbbf24}
.dark-mode .status-failed{background:rgba(239,68,68,.2);color:#fca5a5}
.dark-mode .status-info{background:rgba(59,130,246,.15);color:#93c5fd}
.badge.text-muted{border:1px solid var(--card-border)}
.muted-detail{color:#6b7280}.dark-mode .muted-detail{color:#9ca3af}
.tl-card:hover{transform:none}
.tl-card:hover{border-color:rgba(var(--module-rgb),.40);box-shadow:0 8px 18px rgba(var(--module-rgb),.14)}
.tl-card:hover::after{opacity:1}
.tl-card.is-active{transform:none}
.tl-card.is-active{border-color:rgba(var(--module-rgb),.55);box-shadow:0 10px 22px rgba(var(--module-rgb),.18)}
.tl-card.is-active::after{opacity:1}
.tl-card[tabindex="0"]:focus-visible{outline:0;border-color:rgba(var(--module-rgb),.65);box-shadow:0 0 0 3px rgba(var(--module-rgb),.24)}
.tl-card.is-important{border-width:2px}
.tl-day-separator{
  position:relative; z-index:1; display:flex; justify-content:center; margin:12px 0 4px;
}
.tl-day-separator span{
  font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; font-weight:700;
  color:#6b7280; background:var(--card-bg); border:1px solid var(--card-border); border-radius:999px; padding:3px 10px;
}
.category-chip-row{display:flex;flex-wrap:wrap;gap:.4rem}
.category-chip{border-radius:999px}
.category-chip.is-active{background:#111827;color:#fff;border-color:#111827}
.dark-mode .category-chip.is-active{background:#e5e7eb;color:#111827;border-color:#e5e7eb}
.empty-actions .btn{min-width:110px}
.activity-metric-row{display:flex;flex-wrap:wrap;gap:.45rem}
.metric-chip{font-size:.76rem;padding:.4rem .65rem;border:1px solid var(--card-border)}
.tl-event-icon{width:16px;text-align:center;margin-right:.3rem;opacity:.85}
.tl-card.is-new{border-color:rgba(59,130,246,.45);box-shadow:0 0 0 2px rgba(59,130,246,.12)}
.new-badge{font-size:.67rem}
.tl-skeleton .tl-node{opacity:.35}
.tl-skeleton .tl-card{position:relative;overflow:hidden;min-height:82px}
.tl-skeleton .tl-card::before{
  content:"";position:absolute;inset:10px 12px;border-radius:8px;
  background:linear-gradient(90deg, rgba(148,163,184,.16) 25%, rgba(148,163,184,.30) 50%, rgba(148,163,184,.16) 75%);
  background-size:220% 100%;animation:tlShimmer 1.2s ease-in-out infinite;
}
@keyframes tlShimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
@media (max-width:768px){
  .activity-viewport{height:calc(100vh - 260px);overflow-y:auto}
  .tl-item{grid-template-columns:24px 1fr;margin:28px 0}
  .tl-time{grid-column:1 / span 2;order:-1;text-align:left;padding:0 0 6px}
  .tl-node{grid-column:1;justify-self:start}
  .tl-card{grid-column:2;max-width:100%}
  .tl-time::after,.tl-item::after{display:none}
}
.date-range-control{display:flex;align-items:center;height:38px;border:1px solid var(--card-border);border-radius:.25rem;background:var(--card-bg);overflow:hidden;margin-bottom:0}
.dr-icon{width:40px;background:rgba(0,0,0,.04);border-right:1px solid var(--card-border);color:#6b7280;display:flex;align-items:center;justify-content:center}
.dr-input{border:0!important;height:100%;padding:.375rem .5rem;background:transparent;color:inherit}
.dr-sep{padding:0 .5rem;color:#6b7280;border-left:1px solid var(--card-border);border-right:1px solid var(--card-border);display:flex;align-items:center;height:100%}
</style>

<script>
(function(){
  const raf = window.requestAnimationFrame || (cb => setTimeout(cb,0));
  const caf = window.cancelAnimationFrame || (id => clearTimeout(id));
  const rtf = new Intl.RelativeTimeFormat(undefined,{numeric:'auto'});
  const UNITS=[['year',31536e6],['month',2592e6],['week',6048e5],['day',864e5],['hour',36e5],['minute',6e4],['second',1e3]];
  let activeCategory = 'all';
  const listEl = document.getElementById('activityList');
  const currentUserId = <?= (int)$current_user_id; ?>;
  const lastVisitKey = `activity:lastVisit:${currentUserId}`;

  function ago(ms){const d=Date.now()-ms;for(const [u,s] of UNITS){if(Math.abs(d)>=s||u==='second')return rtf.format(Math.trunc(-d/s),u)}}
  function renderTimes(){
    document.querySelectorAll('#activityList .tl-item').forEach(li=>{
      const el=li.querySelector('.tl-time'); const iso=li.dataset.date;
      if(!iso||!el) return; const ms=Date.parse(iso);
      el.textContent = isNaN(ms)?'':ago(ms);
      el.title = li.dataset.exact || new Date(iso).toLocaleString();
    });
  }

  function applyPreset(preset){
    const t=new Date(); let s=null;
    if(preset==='7d') s=new Date(t.getFullYear(),t.getMonth(),t.getDate()-7);
    if(preset==='30d') s=new Date(t.getFullYear(),t.getMonth(),t.getDate()-30);
    if(preset==='ytd') s=new Date(t.getFullYear(),0,1);
    if(!s) return;
    document.getElementById('fltStart').value=s.toISOString().slice(0,10);
    document.getElementById('fltEnd').value=t.toISOString().slice(0,10);
  }

  function applyMask(){
    const on=document.getElementById('maskSensitive').checked;
    document.querySelectorAll('.muted-detail').forEach(e=>e.classList.toggle('d-none',on));

    const modalOpen = document.body.classList.contains('modal-open');
    if(modalOpen){
      const activeCard = document.querySelector('#activityList .tl-card.is-active');
      if(activeCard){
        const li = activeCard.closest('.tl-item');
        document.getElementById('mdDevice').textContent = on ? 'Protected session' : (li.dataset.device || 'Browser session');
        document.getElementById('mdIP').textContent = on ? 'IP protected' : (li.dataset.ip || 'IP protected');
      }
    }
  }

  function syncDaySeparators(){
    const list = document.getElementById('activityList');
    if(!list) return;

    let currentSeparator = null;
    let hasVisibleItem = false;
    Array.from(list.children).forEach((child)=>{
      if(child.classList.contains('tl-day-separator')){
        if(currentSeparator){
          currentSeparator.classList.toggle('d-none', !hasVisibleItem);
        }
        currentSeparator = child;
        hasVisibleItem = false;
      } else if(child.classList.contains('tl-item') && !child.classList.contains('d-none')){
        hasVisibleItem = true;
      }
    });
    if(currentSeparator){
      currentSeparator.classList.toggle('d-none', !hasVisibleItem);
    }
  }

  function updateMetrics(){
    const todayStart = new Date();
    todayStart.setHours(0,0,0,0);
    const todayMs = todayStart.getTime();
    let total=0,trades=0,account=0,warnings=0;
    document.querySelectorAll('#activityList .tl-item:not(.d-none)').forEach((li)=>{
      const ms = Date.parse(li.dataset.date || '');
      if (Number.isNaN(ms) || ms < todayMs) return;
      total++;
      const mod = li.dataset.category || '';
      const status = (li.dataset.status || '').toLowerCase();
      if (mod === 'trading') trades++;
      if (mod === 'account') account++;
      if (status === 'warning' || status === 'failed') warnings++;
    });
    const setText = (id, value)=>{
      const el = document.getElementById(id);
      if (el) el.textContent = String(value);
    };
    setText('mTodayTotal', total);
    setText('mTodayTrades', trades);
    setText('mTodayAccount', account);
    setText('mTodayWarn', warnings);
  }

  function initTooltips(){
    if (window.jQuery && typeof window.jQuery.fn.tooltip === 'function') {
      window.jQuery('[data-toggle="tooltip"]').tooltip({ trigger: 'hover' });
    }
  }

  function markNewSinceLastVisit(){
    let lastVisit = 0;
    try {
      lastVisit = parseInt(localStorage.getItem(lastVisitKey) || '0', 10) || 0;
    } catch (_err) {
      lastVisit = 0;
    }
    if (lastVisit > 0) {
      document.querySelectorAll('#activityList .tl-item').forEach((li)=>{
        const ms = Date.parse(li.dataset.date || '');
        if (Number.isNaN(ms) || ms <= lastVisit) return;
        const card = li.querySelector('.tl-card');
        const title = li.querySelector('.tl-title');
        if (card) card.classList.add('is-new');
        if (title && !title.querySelector('.new-badge')) {
          const badge = document.createElement('span');
          badge.className = 'badge badge-primary ml-2 new-badge';
          badge.textContent = 'New';
          title.appendChild(badge);
        }
      });
    }
    try {
      localStorage.setItem(lastVisitKey, String(Date.now()));
    } catch (_err) {}
  }

  function appendLoadSkeletons(){
    if (!listEl) return;
    for (let i=0;i<3;i++){
      const li = document.createElement('li');
      li.className = `tl-item tl-skeleton ${i % 2 === 0 ? 'alt-left' : 'alt-right'}`;
      li.innerHTML = `
        <div class="tl-time"></div>
        <div class="tl-node"></div>
        <div class="tl-card" aria-hidden="true"></div>
      `;
      listEl.appendChild(li);
    }
  }

  function copyDetails(){
    const notifyCopied = () => {
      if (window.toastr && typeof window.toastr.success === 'function') {
        window.toastr.success('Event details copied.');
      }
    };
    const line = (label, id) => {
      const el = document.getElementById(id);
      const value = el ? (el.textContent || '').trim() : '';
      return `${label}: ${value || '—'}`;
    };
    const payload = [
      line('Title', 'mdTitle'),
      line('Event type', 'mdEventType'),
      line('Status', 'mdStatus'),
      line('When', 'mdWhen'),
      line('Device', 'mdDevice'),
      line('IP', 'mdIP'),
      line('Notes', 'mdNotes')
    ].join('\n');

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(payload).then(notifyCopied);
      return;
    }
    const ta = document.createElement('textarea');
    ta.value = payload;
    ta.setAttribute('readonly', 'readonly');
    ta.style.position = 'fixed';
    ta.style.top = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      notifyCopied();
    } catch (_err) {}
    document.body.removeChild(ta);
  }

  document.querySelectorAll('.preset-link').forEach(a=>{
    a.addEventListener('click',e=>{
      e.preventDefault();
      applyPreset(a.dataset.preset);
      applyFilters();
    });
  });

  document.querySelectorAll('[data-empty-action]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const act = btn.getAttribute('data-empty-action');
      if(act === 'reset'){
        document.getElementById('btnReset').click();
        return;
      }
      if(act === '7d' || act === '30d'){
        applyPreset(act);
        applyFilters();
      }
    });
  });

  document.querySelectorAll('.category-chip').forEach(chip=>{
    chip.addEventListener('click', ()=>{
      activeCategory = chip.dataset.category || 'all';
      document.querySelectorAll('.category-chip').forEach(c=>c.classList.remove('is-active'));
      chip.classList.add('is-active');
      applyFilters();
    });
  });

  function clearActiveCards(){
    document.querySelectorAll('#activityList .tl-card.is-active').forEach(c=>c.classList.remove('is-active'));
  }
  function setActiveCard(li){
    clearActiveCards();
    const card=li.querySelector('.tl-card'); if(card) card.classList.add('is-active');
  }
  function reflowAlternation(){
    caf(reflowAlternation._raf || 0);
    reflowAlternation._raf = raf(()=>{
      const items = Array.from(document.querySelectorAll('#activityList .tl-item'));
      items.forEach(li=>li.classList.remove('alt-left','alt-right'));
      let idx=0;
      items.forEach(li=>{
        if(li.classList.contains('d-none')) return;
        const alignLeft = idx % 2 === 0;
        li.classList.add(alignLeft ? 'alt-left' : 'alt-right');
        const timeEl = li.querySelector('.tl-time');
        const nodeEl = li.querySelector('.tl-node');
        const cardEl = li.querySelector('.tl-card');
        if (timeEl && nodeEl && cardEl){
          const order = alignLeft ? [timeEl, nodeEl, cardEl] : [cardEl, nodeEl, timeEl];
          order.forEach(child => li.appendChild(child));
        }
        idx++;
      });
    });
  }

  function applyFilters(){
    const status = document.getElementById('fltStatus').value;
    const q      = (document.getElementById('fltSearch').value||'').toLowerCase();
    const sVal   = document.getElementById('fltStart').value;
    const eVal   = document.getElementById('fltEnd').value;
    const sMs = sVal? Date.parse(sVal+'T00:00:00'):null;
    const eMs = eVal? Date.parse(eVal+'T23:59:59'):null;
    let shown=0;
    document.querySelectorAll('#activityList .tl-item').forEach(li=>{
      const liStatus=li.dataset.status;
      const liCategory=li.dataset.category || 'dashboard';
      const text=li.innerText.toLowerCase(); const liMs=Date.parse(li.dataset.date);
      let ok=true;
      if(status!=='all' && liStatus!==status) ok=false;
      if(activeCategory!=='all' && liCategory!==activeCategory) ok=false;
      if(sMs && !(liMs>=sMs)) ok=false;
      if(eMs && !(liMs<=eMs)) ok=false;
      if(q && !text.includes(q)) ok=false;
      li.classList.toggle('d-none', !ok);
      if(ok) shown++;
    });
    syncDaySeparators();
    updateMetrics();
    document.getElementById('emptyState').classList.toggle('d-none', shown!==0);
    clearActiveCards();
    reflowAlternation();
  }

  document.getElementById('btnApply').addEventListener('click', applyFilters);
  document.getElementById('fltStatus').addEventListener('change', applyFilters);
  document.getElementById('btnReset').addEventListener('click', ()=>{
    document.getElementById('fltStatus').value='all';
    document.getElementById('fltSearch').value='';
    document.getElementById('fltStart').value='';
    document.getElementById('fltEnd').value='';
    activeCategory='all';
    document.querySelectorAll('.category-chip').forEach(chip=>chip.classList.toggle('is-active', chip.dataset.category === 'all'));
    document.querySelectorAll('#activityList .tl-item').forEach(el=>el.classList.remove('d-none'));
    syncDaySeparators();
    updateMetrics();
    document.getElementById('emptyState').classList.add('d-none');
    reflowAlternation();
    applyMask(); clearActiveCards();
  });

  const csvBtn = document.getElementById('btnExportCsv');
  if (csvBtn) {
    csvBtn.addEventListener('click', ()=>{
      const rows = [['Date', 'Relative', 'Title', 'Event Type', 'Module', 'Status', 'Notes']];
      document.querySelectorAll('#activityList .tl-item:not(.d-none)').forEach((li)=>{
        const relative = (li.querySelector('.tl-time')?.textContent || '').trim();
        rows.push([
          li.dataset.exact || '',
          relative,
          li.dataset.title || '',
          li.dataset.eventType || '',
          li.dataset.category || '',
          li.dataset.status || '',
          li.dataset.notes || ''
        ]);
      });
      if (rows.length <= 1) {
        toastr.info('No visible activity rows to export.');
        return;
      }
      const csv = rows.map(cols => cols.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = `activity-filtered-${new Date().toISOString().slice(0,10)}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(a.href);
    });
  }

  document.getElementById('maskSensitive').addEventListener('change', applyMask);

  document.querySelectorAll('#activityList .tl-card').forEach(card=>{
    card.addEventListener('mousemove', (e)=>{
      const rect = card.getBoundingClientRect();
      const x = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
      const y = Math.max(0, Math.min(rect.height, e.clientY - rect.top));
      card.style.setProperty('--gx', `${x}px`);
      card.style.setProperty('--gy', `${y}px`);
    });
    card.addEventListener('mouseleave', ()=>{
      card.style.removeProperty('--gx');
      card.style.removeProperty('--gy');
    });

    const activate = ()=>{
      const li = card.closest('.tl-item');
      setActiveCard(li);
      const get = (k)=> li.dataset[k] || '—';
      const cap = s => s ? s.charAt(0).toUpperCase()+s.slice(1) : '—';
      document.getElementById('mdTitle').textContent  = get('title');
      document.getElementById('mdEventType').textContent = get('eventType');
      document.getElementById('mdStatus').textContent = cap(get('status'));
      document.getElementById('mdWhen').textContent   = li.dataset.exact || new Date(li.dataset.date).toLocaleString();
      document.getElementById('mdDevice').textContent = (document.getElementById('maskSensitive').checked ? 'Protected session' : get('device'));
      document.getElementById('mdIP').textContent     = (document.getElementById('maskSensitive').checked ? 'IP protected' : get('ip'));
      document.getElementById('mdNotes').textContent  = get('notes');

      const groupCount = parseInt(li.dataset.groupCount || '1', 10);
      const groupWrap = document.getElementById('mdGroupWrap');
      const groupLabel = document.getElementById('mdGroupLabel');
      const groupList = document.getElementById('mdGroupList');
      groupList.innerHTML = '';
      if (groupCount > 1) {
        let items = [];
        try {
          items = JSON.parse(li.dataset.groupItems || '[]');
        } catch (_e) {
          items = [];
        }
        items.forEach(item => {
          const when = item && item.when ? String(item.when) : '—';
          const status = item && item.status ? String(item.status) : '—';
          const entry = document.createElement('li');
          entry.textContent = `${when} · ${status}`;
          groupList.appendChild(entry);
        });
        groupWrap.classList.remove('d-none');
        groupLabel.classList.remove('d-none');
      } else {
        groupWrap.classList.add('d-none');
        groupLabel.classList.add('d-none');
      }
      $('#eventModal').modal('show');
    };
    card.addEventListener('click', activate);
    card.addEventListener('keydown', (e)=>{
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(); }
    });
  });

  const copyBtn = document.getElementById('btnCopyDetails');
  if (copyBtn) {
    copyBtn.addEventListener('click', copyDetails);
  }

  const loadOlderBtn = document.getElementById('loadOlderBtn');
  if (loadOlderBtn) {
    loadOlderBtn.addEventListener('click', (e)=>{
      e.preventDefault();
      appendLoadSkeletons();
      setTimeout(()=>{ window.location.href = loadOlderBtn.href; }, 180);
    });
  }

  document.addEventListener('keydown', (e)=>{
    const target = e.target;
    const tag = target && target.tagName ? target.tagName.toLowerCase() : '';
    const typing = tag === 'input' || tag === 'textarea' || (target && target.isContentEditable);
    if (!typing && e.key === '/') {
      const search = document.getElementById('fltSearch');
      if (search) {
        e.preventDefault();
        search.focus();
      }
    }
    if (e.key === 'Escape' && window.jQuery && window.jQuery('#eventModal').hasClass('show')) {
      window.jQuery('#eventModal').modal('hide');
    }
  });

  initTooltips();
  markNewSinceLastVisit();
  syncDaySeparators();
  updateMetrics();
  reflowAlternation();
  renderTimes(); applyMask(); setInterval(renderTimes, 60000);

  (function(){
    const clearActive = () => {
      document.querySelectorAll('#activityList .tl-card.is-active')
        .forEach(el => el.classList.remove('is-active'));
    };
    $('#eventModal').on('hide.bs.modal hidden.bs.modal', clearActive);
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.tl-card') && !e.target.closest('.modal')) clearActive();
    });
  })();
})();
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>


