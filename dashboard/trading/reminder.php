<?php
require "../lib/Core.php";

$current_user_id = $_SESSION['user_id'] ?? 1; // fallback for testing

// ===== (A) ADD NEW REMINDER =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reminder'])) {
  $title = mysqli_real_escape_string($conn, $_POST['title']);
  $remind_time = mysqli_real_escape_string($conn, $_POST['remind_time']);
  $category = mysqli_real_escape_string($conn, $_POST['category']);
  $description = mysqli_real_escape_string($conn, $_POST['description']);
  $recurring = isset($_POST['recurring']) ? 1 : 0;

  $sql = "INSERT INTO reminders (user_id, title, remind_time, category, description, status, recurring)
          VALUES ('$current_user_id', '$title', '$remind_time', '$category', '$description', 'Pending', '$recurring')";
  $conn->query($sql);

  // ✅ Show success message then redirect
  echo "<div style='
          position:fixed;top:20px;right:20px;
          background:#4BB543;color:white;
          padding:12px 18px;border-radius:6px;
          box-shadow:0 2px 8px rgba(0,0,0,0.2);
          font-weight:600;z-index:9999;'>✅ Reminder Added Successfully</div>";
  echo "<script>
          setTimeout(() => { window.location='reminder.php'; }, 1200);
        </script>";
  exit;
}

// ===== (B) UPDATE STATUS (Complete/Missed) =====
if (isset($_GET['mark']) && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  $status = ($_GET['mark'] === 'done') ? 'Completed' : 'Missed';
  $conn->query("UPDATE reminders SET status='$status' WHERE id=$id AND user_id=$current_user_id");
  echo "<script>window.location='reminder.php';</script>";
  exit;
}

// ===== (C) SEARCH/FILTER =====
$where = "WHERE user_id = $current_user_id";
if (!empty($_GET['search'])) {
  $q = mysqli_real_escape_string($conn, $_GET['search']);
  $where .= " AND (title LIKE '%$q%' OR category LIKE '%$q%' OR description LIKE '%$q%')";
}
if (!empty($_GET['status']) && $_GET['status'] != 'all') {
  $s = mysqli_real_escape_string($conn, $_GET['status']);
  $where .= " AND status = '$s'";
}

// ===== (D) FETCH REMINDERS =====
$sql = "SELECT * FROM reminders $where ORDER BY remind_time ASC";
$res = $conn->query($sql);
$reminders = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// ===== (E) WEEKLY STATS =====
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));
$stats_sql = "
  SELECT 
    SUM(status='Completed') AS done,
    COUNT(*) AS total
  FROM reminders
  WHERE user_id=$current_user_id AND DATE(remind_time) BETWEEN '$weekStart' AND '$weekEnd'";
$stats = $conn->query($stats_sql)->fetch_assoc();
$done = $stats['done'] ?? 0;
$total = $stats['total'] ?? 0;
$percent = ($total > 0) ? round(($done / $total) * 100) : 0;

require PATH_LIB . "PAGE-top.php"; // ✅ moved here safely
?>

<section class="content">
  <div class="container-fluid">

    <!-- ====== STATS WIDGET ====== -->
    <div class="row mb-3">
      <div class="col-md-12">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <h5 class="mb-0">Reminders Completed This Week</h5>
            <h3 class="text-success mt-1"><?= "$done / $total ($percent%)"; ?></h3>
            <div class="progress mt-2" style="height:8px;">
              <div class="progress-bar bg-success" style="width: <?= $percent; ?>%;"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ====== ADD NEW REMINDER FORM ====== -->
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-bell mr-2"></i>Add New Reminder
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="add_reminder" value="1">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Title</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group col-md-4">
              <label>Date & Time</label>
              <input type="datetime-local" name="remind_time" class="form-control" required>
            </div>
            <div class="form-group col-md-4">
              <label>Category</label>
              <select name="category" class="form-control">
                <option>Buy/Sell Plan</option>
                <option>Market Watch</option>
                <option>Portfolio Review</option>
                <option>General</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="2" class="form-control"></textarea>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="recurring" id="recurring">
            <label class="form-check-label" for="recurring">Repeat Weekly</label>
          </div>
          <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add Reminder</button>
        </form>
      </div>
    </div>

    <!-- ====== SEARCH & FILTER ====== -->
    <div class="card mb-3 shadow-sm">
      <div class="card-body">
        <form class="form-inline" method="GET">
          <input type="text" name="search" class="form-control mr-2" placeholder="Search reminders..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
          <select name="status" class="form-control mr-2">
            <option value="all">All</option>
            <option value="Pending" <?= ($_GET['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Completed" <?= ($_GET['status'] ?? '') == 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Missed" <?= ($_GET['status'] ?? '') == 'Missed' ? 'selected' : '' ?>>Missed</option>
          </select>
          <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
          <a href="reminder.php" class="btn btn-secondary ml-2">Reset</a>
        </form>
      </div>
    </div>

    <!-- ====== REMINDERS LIST ====== -->
    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white">
        <i class="fas fa-clock mr-2"></i>Upcoming Reminders
      </div>
      <div class="card-body p-0">
        <?php if (empty($reminders)): ?>
          <div class="p-4 text-center text-muted">No reminders found.</div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Time</th>
                <th>Remaining</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reminders as $r): ?>
                <?php
                  $t = strtotime($r['remind_time']);
                  $timeLeft = $t - time();
                  if ($timeLeft <= 0) {
                    $remaining = "<span class='text-danger'>Expired</span>";
                  } else {
                    $hours = floor($timeLeft / 3600);
                    $minutes = floor(($timeLeft % 3600) / 60);
                    $remaining = ($hours > 0) ? "$hours hr $minutes min" : "$minutes min";
                  }

                  $badgeClass = match($r['status']) {
                    'Completed' => 'badge-success',
                    'Missed' => 'badge-danger',
                    default => 'badge-warning'
                  };
                ?>
                <tr>
                  <td><?= htmlspecialchars($r['title']) ?></td>
                  <td><?= htmlspecialchars($r['category']) ?></td>
                  <td><?= date('M j, Y g:i A', $t) ?></td>
                  <td><?= $remaining ?></td>
                  <td><span class="badge <?= $badgeClass ?>"><?= $r['status'] ?></span></td>
                  <td>
                    <?php if ($r['status'] == 'Pending'): ?>
                      <a href="?mark=done&id=<?= $r['id'] ?>" class="btn btn-sm btn-success">Mark Done</a>
                      <a href="?mark=missed&id=<?= $r['id'] ?>" class="btn btn-sm btn-danger">Missed</a>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>
