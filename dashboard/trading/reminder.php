<?php
session_start();
require "../lib/Core.php";
$current_user_id = $_SESSION['user_id'] ?? 1;

/* ============================
   HANDLE DRAG UPDATE (AJAX)
============================ */
if (isset($_POST["drag_update"])) {
    $id = intval($_POST["id"]);
    $time = $_POST["remind_time"] ?? "";

    if (!preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $time)) {
        echo json_encode(["status" => "error", "msg" => "Invalid time"]);
        exit;
    }

    $time = mysqli_real_escape_string($conn, $time);
    $check = $conn->query("SELECT id FROM reminders WHERE id=$id AND user_id=$current_user_id LIMIT 1");

    if (!$check || $check->num_rows == 0) {
        echo json_encode(["status" => "error", "msg" => "Reminder not found"]);
        exit;
    }

    $conn->query("UPDATE reminders SET remind_time='$time', status='Pending' WHERE id=$id AND user_id=$current_user_id");
    echo json_encode(["status" => "success"]);
    exit;
}

/* ============================
   AUTO-MARK MISSED
============================ */
$conn->query("
    UPDATE reminders
    SET status='Missed'
    WHERE remind_time < NOW()
      AND status='Pending'
      AND user_id=$current_user_id
");

/* ============================
   AUTO-GENERATE RECURRING
============================ */
$rec = $conn->query("
    SELECT * FROM reminders
    WHERE user_id=$current_user_id
      AND recurring IN (1,2,3)
");

if ($rec && $rec->num_rows > 0) {
    while ($r = $rec->fetch_assoc()) {
        if ($r['recurring'] == 1) $next = "DATE_ADD('{$r['remind_time']}', INTERVAL 1 DAY)";
        if ($r['recurring'] == 2) $next = "DATE_ADD('{$r['remind_time']}', INTERVAL 1 WEEK)";
        if ($r['recurring'] == 3) $next = "DATE_ADD('{$r['remind_time']}', INTERVAL 1 MONTH)";
        $conn->query("
            INSERT INTO reminders (user_id,title,remind_time,category,description,status,recurring)
            SELECT user_id,title,$next,category,description,'Pending',recurring
            FROM reminders
            WHERE id={$r['id']} AND $next <= NOW()
        ");
    }
}

/* ============================
   FETCH REMINDERS
============================ */
$q = $conn->query("
    SELECT * FROM reminders
    WHERE user_id=$current_user_id
    ORDER BY remind_time ASC
");
$reminders = $q ? $q->fetch_all(MYSQLI_ASSOC) : [];

/* ============================
   ADD REMINDER
============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_reminder"])) {
    $title = mysqli_real_escape_string($conn, $_POST["title"]);
    $category = mysqli_real_escape_string($conn, $_POST["category"]);
    $description = mysqli_real_escape_string($conn, $_POST["description"]);
    $remind_time = mysqli_real_escape_string($conn, $_POST["remind_time"]);
    $rec = mysqli_real_escape_string($conn, $_POST["recurrence"]);
    $recurring = ($rec=="daily"?1:($rec=="weekly"?2:($rec=="monthly"?3:0)));

    $conn->query("
        INSERT INTO reminders (user_id,title,remind_time,category,description,status,recurring)
        VALUES ($current_user_id,'$title','$remind_time','$category','$description','Pending',$recurring)
    ");

    $_SESSION["flash_success"] = "✅ Reminder added!";
    header("Location: reminder.php"); exit;
}

/* ============================
   EDIT REMINDER
============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_reminder"])) {
    $id = intval($_POST["id"]);
    $title = mysqli_real_escape_string($conn, $_POST["title"]);
    $category = mysqli_real_escape_string($conn, $_POST["category"]);
    $description = mysqli_real_escape_string($conn, $_POST["description"]);
    $remind_time = mysqli_real_escape_string($conn, $_POST["remind_time"]);
    $rec = mysqli_real_escape_string($conn, $_POST["recurrence"]);
    $recurring = ($rec=="daily"?1:($rec=="weekly"?2:($rec=="monthly"?3:0)));

    $conn->query("
        UPDATE reminders SET
        title='$title',
        remind_time='$remind_time',
        category='$category',
        description='$description',
        recurring=$recurring
        WHERE id=$id AND user_id=$current_user_id
    ");

    $_SESSION["flash_success"] = "✏️ Reminder updated!";
    header("Location: reminder.php"); exit;
}

/* ============================
   DELETE
============================ */
if (isset($_GET["delete"])) {
    $id = intval($_GET["delete"]);
    $conn->query("DELETE FROM reminders WHERE id=$id AND user_id=$current_user_id");
    $_SESSION["flash_success"] = "🗑️ Deleted.";
    header("Location: reminder.php"); exit;
}

/* ============================
   DONE
============================ */
if (isset($_GET["done"])) {
    $id = intval($_GET["done"]);
    $conn->query("UPDATE reminders SET status='Completed' WHERE id=$id AND user_id=$current_user_id");
    $_SESSION["flash_success"] = "🎉 Completed!";
    header("Location: reminder.php"); exit;
}

/* ============================
   SNOOZE
============================ */
if (isset($_GET["snooze"])) {
    $id = intval($_GET["snooze"]);
    $conn->query("
        UPDATE reminders
        SET remind_time = DATE_ADD(remind_time, INTERVAL 1 DAY),
            status='Pending'
        WHERE id=$id AND user_id=$current_user_id
    ");
    $_SESSION["flash_success"] = "😴 Snoozed 1 day.";
    header("Location: reminder.php"); exit;
}

require PATH_LIB . "PAGE-top.php";
?>

<style>
.flash-container {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 5000;
    min-width: 280px;
    max-width: 340px;
}
.flash-message {
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    animation: flash-slide-in 0.25s ease-out;
}
@keyframes flash-slide-in {
    from { transform: translateX(12px); opacity: 0.6; }
    to { transform: translateX(0); opacity: 1; }
}
</style>

<?php if (!empty($_SESSION["flash_success"])): ?>
<div class="flash-container">
    <div class="alert alert-success m-0 flash-message" role="alert">
        <?= $_SESSION["flash_success"]; unset($_SESSION["flash_success"]); ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================
      TABS
============================ -->
<div class="container-fluid mb-3">
<ul class="nav nav-tabs" id="reminderTabs">
    <li class="nav-item"><a class="nav-link active" data-target="#tab-calendar">📅 Calendar View</a></li>
    <li class="nav-item"><a class="nav-link" data-target="#tab-list">📋 Remainder List</a></li>
</ul>
</div>

<div class="tab-content">

<!-- ============================
      CALENDAR TAB
============================ -->
<div class="tab-pane fade show active" id="tab-calendar">
<section class="content">
<div class="container-fluid"><div class="row">

<div class="col-md-3">
    <div class="sticky-top mb-3">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white"><h5 class="mb-0">Filter by Category</h5></div>
            <div class="card-body">
                <button class="btn btn-primary btn-block mb-2 category-filter" data-cat="all">Show All</button>
                <button class="btn btn-info btn-block mb-2 category-filter" data-cat="Buy/Sell Plan">Buy/Sell Plan</button>
                <button class="btn btn-success btn-block mb-2 category-filter" data-cat="Market Watch">Market Watch</button>
                <button class="btn btn-warning btn-block mb-2 category-filter" data-cat="Portfolio Review">Portfolio Review</button>
                <button class="btn btn-secondary btn-block category-filter" data-cat="General">General</button>
            </div>
        </div>
    </div>
</div>

<div class="col-md-9">
    <div class="card card-primary shadow-sm">
        <div class="card-body p-0">
            <div id="calendar" style="padding:10px;"></div>
        </div>
    </div>
</div>

</div></div>
</section>
</div>

<!-- ============================
      LIST TAB
============================ -->
<div class="tab-pane fade" id="tab-list">
<section class="content"><div class="container-fluid">
<div class="card shadow-sm">
<div class="card-header bg-secondary text-white"><h4>Your Reminders</h4></div>
<div class="card-body p-0">

<?php if (empty($reminders)): ?>
<div class="p-4 text-center text-muted">No reminders yet.</div>
<?php else: ?>
<table class="table table-striped table-hover mb-0">
<thead class="thead-dark">
<tr>
    <th>Title</th>
    <th>Category</th>
    <th>Description</th>
    <th>Date/Time</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($reminders as $r): ?>
<tr>
<td><?= htmlspecialchars($r['title']) ?></td>
<td><?= htmlspecialchars($r['category']) ?></td>
<td style="max-width:260px; white-space:normal;"><?= nl2br(htmlspecialchars($r['description'])) ?></td>
<td><?= date("M j, Y g:i A", strtotime($r['remind_time'])) ?></td>
<td>
<?php $badge = ($r["status"]=="Completed"?"success":($r["status"]=="Missed"?"danger":"warning")); ?>
<span class="badge badge-<?= $badge ?>"><?= $r["status"] ?></span>
</td>
<td>
<a href="?done=<?= $r['id'] ?>" class="btn btn-sm btn-success">Done</a>
<a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

</div></div></div></section>
</div>
</div>

<!-- Floating Add Button -->
<button id="addReminderBtn" class="btn btn-primary rounded-circle"
style="position:fixed; bottom:30px; right:30px; z-index:2000; width:60px; height:60px; font-size:30px;">+</button>

<!-- ADD MODAL -->
<div class="modal fade" id="addReminderModal">
<div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header bg-primary text-white">
<h5 class="modal-title">Add Reminder</h5><button class="close text-white" data-dismiss="modal">&times;</button></div>
<form method="POST"><input type="hidden" name="add_reminder" value="1">
<div class="modal-body">
<label>Title</label>
<input type="text" name="title" class="form-control" required>
<label class="mt-3">Date & Time</label>
<input type="datetime-local" name="remind_time" id="addDateTime" class="form-control" required>
<label class="mt-3">Category</label>
<select name="category" class="form-control">
<option>Buy/Sell Plan</option><option>Market Watch</option><option>Portfolio Review</option><option>General</option>
</select>
<label class="mt-3">Description</label>
<textarea name="description" class="form-control"></textarea>
<label class="mt-3">Recurrence</label>
<select name="recurrence" class="form-control">
<option value="none">None</option><option value="daily">Daily</option>
<option value="weekly">Weekly</option><option value="monthly">Monthly</option>
</select>
</div>
<div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
<button class="btn btn-success">Add</button></div></form>
</div></div></div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editReminderModal">
<div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header bg-info text-white">
<h5 class="modal-title">Edit Reminder</h5><button class="close text-white" data-dismiss="modal">&times;</button></div>

<form method="POST">
<input type="hidden" name="edit_reminder" value="1">
<input type="hidden" name="id" id="editId">

<div class="modal-body">
<label>Title</label>
<input type="text" name="title" id="editTitle" class="form-control" required>

<label class="mt-3">Date & Time</label>
<input type="datetime-local" name="remind_time" id="editDateTime" class="form-control" required>

<label class="mt-3">Category</label>
<select name="category" id="editCategory" class="form-control">
<option>Buy/Sell Plan</option><option>Market Watch</option>
<option>Portfolio Review</option><option>General</option>
</select>

<label class="mt-3">Description</label>
<textarea name="description" id="editDescription" class="form-control"></textarea>

<label class="mt-3">Recurrence</label>
<select name="recurrence" id="editRecurrence" class="form-control">
<option value="none">None</option><option value="daily">Daily</option>
<option value="weekly">Weekly</option><option value="monthly">Monthly</option>
</select>

<div class="mt-3">
    <a id="editDoneBtn" class="btn btn-success btn-block">Mark Done</a>
    <a id="editSnoozeBtn" class="btn btn-secondary btn-block">Snooze +1 Day</a>
    <a id="editDeleteBtn" class="btn btn-danger btn-block">Delete</a>
</div>
</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-dismiss="modal">Close</button>
<button class="btn btn-info">Save</button>
</div>

</form>
</div></div></div>

<!-- ============================
      JAVASCRIPT
============================ -->
<script>
document.addEventListener("DOMContentLoaded", function() {

    function refreshCalendarSize(){
        if (typeof calendar !== "undefined" && calendar) {
            calendar.updateSize();
            setTimeout(()=>calendar.updateSize(), 280);
        }
    }

    /* FLASH MESSAGE AUTO-HIDE */
    const flash = document.querySelector(".flash-message");
    if (flash) {
        $(flash).hide().fadeIn(200);
        setTimeout(() => {
            $(flash).fadeTo(350, 0).slideUp(300, function() { $(this).remove(); });
        }, 2200);
    }

    /* TAB SWITCH */
    document.querySelectorAll("#reminderTabs .nav-link").forEach(tab=>{
        tab.addEventListener("click",function(){
            document.querySelectorAll("#reminderTabs .nav-link").forEach(t=>t.classList.remove("active"));
            this.classList.add("active");
            let target=this.getAttribute("data-target");
            document.querySelectorAll(".tab-pane").forEach(p=>p.classList.remove("show","active"));
            document.querySelector(target).classList.add("show","active");
            if (target === "#tab-calendar") {
                setTimeout(refreshCalendarSize, 50);
            }
        });
    });

    /* EVENTS */
    let eventsFromPHP = [
        <?php foreach ($reminders as $r): ?>
        {
            id:"<?= $r['id'] ?>",
            title:"<?= addslashes($r['title']) ?>",
            start:"<?= date('Y-m-d\TH:i:s', strtotime($r['remind_time'])) ?>",
            extendedProps:{
                category:"<?= $r['category'] ?>",
                status:"<?= $r['status'] ?>",
                description:"<?= addslashes($r['description']) ?>",
                recurrence:"<?= $r['recurring'] ?>"
            },
            backgroundColor:"<?= $r['status']=='Completed'?'#28a745':($r['status']=='Missed'?'#dc3545':($r['category']=='Buy/Sell Plan'?'#007bff':($r['category']=='Market Watch'?'#28a745':($r['category']=='Portfolio Review'?'#fd7e14':'#6c757d')))) ?>"
        },
        <?php endforeach; ?>
    ];

    /* FULLCALENDAR */
    var calendar=new FullCalendar.Calendar(document.getElementById('calendar'),{
        height:750,
        initialView:'dayGridMonth',
        editable:true,
        eventStartEditable:true,
        eventDurationEditable:false,
        headerToolbar:{ left:'prev,next today',center:'title',right:'dayGridMonth,timeGridWeek,timeGridDay' },
        buttonText:{
            today:'Today',
            month:'Month',
            week:'Week',
            day:'Day'
        },
        events:eventsFromPHP,

        dateClick:function(info){
            document.getElementById("addDateTime").value=moment(info.date).format("YYYY-MM-DDTHH:00");
            $("#addReminderModal").modal("show");
        },

        eventClick:function(info){
            let e=info.event;
            $("#editId").val(e.id);
            $("#editTitle").val(e.title);
            $("#editDateTime").val(moment(e.start).format("YYYY-MM-DDTHH:mm"));
            $("#editCategory").val(e.extendedProps.category);
            $("#editDescription").val(e.extendedProps.description);
            $("#editRecurrence").val(e.extendedProps.recurrence??"none");
            $("#editDoneBtn").attr("href","reminder.php?done="+e.id);
            $("#editSnoozeBtn").attr("href","reminder.php?snooze="+e.id);
            $("#editDeleteBtn").attr("href","reminder.php?delete="+e.id);
            $("#editReminderModal").modal("show");
        },

        eventDrop:function(info){
            $.post("reminder.php",{ drag_update:1, id:info.event.id,
                remind_time:moment(info.event.start).format("YYYY-MM-DD HH:mm:ss")
            });
        }
    });
    calendar.render();
    window.addEventListener("resize", refreshCalendarSize);
    $(document).on("shown.lte.pushmenu collapsed.lte.pushmenu", function(){
        setTimeout(refreshCalendarSize, 220);
    });
    new MutationObserver(function(muts){
        if (muts.some(m=>m.attributeName==="class")) {
            setTimeout(refreshCalendarSize, 220);
        }
    }).observe(document.body,{attributes:true,attributeFilter:["class"]});

    /* ADD BTN */
    $("#addReminderBtn").click(()=>$("#addReminderModal").modal("show"));

    /* FILTERS */
    $(".category-filter").on("click",function(){
        let cat=$(this).data("cat");
        calendar.getEvents().forEach(ev=>{
            ev.setProp("display",(cat==="all"||ev.extendedProps.category===cat)?"auto":"none");
        });
    });

    /* DARK MODE */
    function applyTheme(){
        let dark=document.body.classList.contains("dark-mode");
        document.querySelectorAll(".fc").forEach(el=>{
            el.style.background=dark?"#1e1e1e":"#ffffff";
            el.style.color=dark?"#f8f9fa":"#212529";
        });
    }
    applyTheme();
    new MutationObserver(applyTheme).observe(document.body,{attributes:true,attributeFilter:["class"]});

    /* UPCOMING POPUPS */
    function checkUpcoming(){
        let now=moment();
        let soon=moment().add(10,"minutes");
        calendar.getEvents().forEach(ev=>{
            if(moment(ev.start).isBetween(now,soon)&&ev.extendedProps.status==="Pending"){
                showPopup(ev);
            }
        });
    }
    function showPopup(ev){
        let box=`
            <div class="alert alert-warning" style="position:fixed; bottom:20px; right:20px;
                 z-index:5000; min-width:280px;">
                <strong>⏰ Upcoming Reminder:</strong><br>${ev.title}<br>
                <small>${moment(ev.start).format("MMM D, h:mm A")}</small>
            </div>`;
        $("body").append(box);
        setTimeout(()=>$(".alert").fadeOut(400,function(){$(this).remove()}),6000);
    }
    setInterval(checkUpcoming,30000);
});
</script>

<?php require PATH_LIB . "PAGE-bottom.php"; ?>


