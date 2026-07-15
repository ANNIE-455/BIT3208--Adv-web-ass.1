<?php
session_start();

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'sweetcake2024');

$host    = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "cake_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    cake_name VARCHAR(255) NOT NULL,
    cake_price DECIMAL(10,2) NOT NULL,
    size VARCHAR(20) NOT NULL,
    message TEXT,
    mpesa_number VARCHAR(20),
    mpesa_code VARCHAR(50),
    delivery_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_date DATE DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS mpesa_number VARCHAR(20)");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS mpesa_code VARCHAR(50)");

// =============================================
//  ADMIN LOGIN
//  Satisfies: Exercise 1 —
//  "Create an HttpSession after a successful login"
//  PHP $_SESSION['admin'] = Java HttpSession for admin
//
//  Satisfies: Exercise 2 requirement 3 —
//  "Display the user's session ID and login time"
//  Stored in $_SESSION and shown on dashboard
// =============================================
if (isset($_POST['admin_login'])) {
    $u = trim($_POST['admin_username'] ?? '');
    $p = trim($_POST['admin_password'] ?? '');

    // Validate: username must not be empty (Exercise 1)
    if (empty($u)) {
        $loginError = "Admin username cannot be empty.";
    } elseif ($u === ADMIN_USER && $p === ADMIN_PASS) {

        // Create admin session (HttpSession equivalent)
        $_SESSION['admin']            = true;
        $_SESSION['admin_user']       = $u;
        $_SESSION['admin_login_time'] = date('d M Y, H:i:s');
        // session_id() is the PHP equivalent of session.getId()

        echo "<script>window.location.href = window.location.href.split('?')[0];</script>";
        exit;
    } else {
        $loginError = "Invalid username or password. Please try again.";
    }
}

// =============================================
//  ADMIN LOGOUT
//  Satisfies: Exercise 1 —
//  "Logout button that invalidates the session"
//  PHP session_destroy() = Java session.invalidate()
// =============================================
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    echo "<script>window.location.href = window.location.pathname;</script>";
    exit;
}

// =============================================
//  UPDATE ORDER STATUS
// =============================================
if (isset($_POST['update_status']) && isset($_SESSION['admin'])) {
    $id            = intval($_POST['order_id']);
    $status        = $conn->real_escape_string($_POST['status']);
    $delivery_date = $conn->real_escape_string($_POST['delivery_date'] ?? '');
    $conn->query("UPDATE orders SET status='$status', delivery_date=" .
        ($delivery_date ? "'$delivery_date'" : "NULL") . " WHERE id=$id");
    echo "<script>window.location.href = window.location.pathname;</script>";
    exit;
}

// =============================================
//  FETCH DATA (admin only)
// =============================================
$orders = [];
$users  = [];
if (isset($_SESSION['admin'])) {
    $res = $conn->query("SELECT * FROM orders ORDER BY delivery_date ASC, ordered_at DESC");
    while ($row = $res->fetch_assoc()) $orders[] = $row;
    $res2 = $conn->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC");
    while ($row = $res2->fetch_assoc()) $users[] = $row;
}
$conn->close();

$calendarOrders = [];
foreach ($orders as $o) {
    if (!empty($o['delivery_date'])) {
        $date = $o['delivery_date'];
        if (!isset($calendarOrders[$date])) $calendarOrders[$date] = [];
        $calendarOrders[$date][] = $o;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Cake — Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Quicksand',sans-serif; text-transform:none; }

        body { background:#F0F0F0; color:#1B1722; min-height:100vh; }

        /* ── LOGIN ── */
        .login-screen {
            display:flex; justify-content:center; align-items:center;
            min-height:100vh; background:#F0F0F0;
        }
        .login-box {
            background:#fff; border-radius:20px;
            padding:4rem 3rem; width:400px;
            box-shadow:0 4px 24px rgba(27,23,34,0.12);
            text-align:center;
            border:1px solid rgba(27,23,34,0.08);
        }
        .login-box .cake-icon { font-size:4rem; color:#1B1722; margin-bottom:1rem; }
        .login-box h2 { font-size:2.4rem; color:#1B1722; font-weight:700; margin-bottom:.3rem; }
        .login-box .subtitle { font-size:1.4rem; color:#999; margin-bottom:2.5rem; }
        .login-box input {
            width:100%; padding:1.2rem 1.5rem; margin-bottom:1.2rem;
            border-radius:10px; border:1.5px solid #e0e0e0;
            background:#f8f8f8; color:#1B1722;
            font-size:1.5rem; font-family:'Quicksand',sans-serif;
            transition:border-color .2s;
        }
        .login-box input:focus { border-color:#1B1722; outline:none; background:#fff; }
        .login-box button {
            width:100%; padding:1.3rem;
            background:#1B1722; color:#F0F0F0;
            font-size:1.6rem; font-weight:700;
            border-radius:10px; cursor:pointer;
            border:none; font-family:'Quicksand',sans-serif;
            transition:background .2s;
        }
        .login-box button:hover { background:#2d2638; }
        .error-msg {
            background:#fff0f0; color:#c0392b;
            border:1px solid #fcc; border-radius:8px;
            padding:.8rem 1rem; font-size:1.3rem;
            margin-bottom:1.5rem;
        }

        /* ── SESSION INFO BOX ──
           Satisfies: Exercise 2 requirement 3
           "Display the user's session ID and login time on the dashboard"
        ── */
        .session-info-box {
            background:#fff;
            border:1px solid #e8e8e8;
            border-left:5px solid #1B1722;
            border-radius:0 12px 12px 0;
            padding:1.5rem 2rem;
            margin-bottom:2rem;
            display:flex;
            flex-wrap:wrap;
            gap:2rem;
            align-items:center;
            font-size:1.4rem;
        }
        .session-info-box .si-item {
            display:flex; align-items:center; gap:.6rem; color:#1B1722;
        }
        .session-info-box .si-item i { color:#2980b9; font-size:1.5rem; }
        .session-info-box .si-item strong { color:#1B1722; font-weight:700; }
        .session-info-box .si-id {
            font-size:1.2rem; color:#888;
            font-family:monospace; word-break:break-all;
        }

        /* ── HEADER ── */
        .admin-header {
            background:#fff;
            padding:1.4rem 3rem;
            display:flex; justify-content:space-between; align-items:center;
            border-bottom:1px solid #e8e8e8;
            position:sticky; top:0; z-index:100;
            box-shadow:0 2px 8px rgba(27,23,34,0.08);
        }
        .admin-header .brand { display:flex; align-items:center; gap:1rem; }
        .admin-header .brand i { font-size:2.2rem; color:#1B1722; }
        .admin-header h1 { font-size:2rem; color:#1B1722; font-weight:700; }
        .admin-header .logout-btn {
            background:#1B1722; color:#F0F0F0;
            padding:.7rem 1.8rem; border-radius:8px;
            font-size:1.4rem; text-decoration:none;
            font-weight:600; transition:background .2s;
        }
        .admin-header .logout-btn:hover { background:#2d2638; }

        /* ── TABS ── */
        .tab-nav {
            display:flex; gap:.8rem; padding:2rem 3rem 0;
            background:#F0F0F0; border-bottom:2px solid #e8e8e8;
        }
        .tab-btn {
            padding:1rem 2rem;
            border-radius:10px 10px 0 0;
            cursor:pointer; font-size:1.5rem;
            font-weight:600; border:none;
            background:#e4e4e4; color:#888;
            font-family:'Quicksand',sans-serif;
            transition:all .2s;
        }
        .tab-btn:hover { background:#d4d4d4; color:#1B1722; }
        .tab-btn.active { background:#1B1722; color:#F0F0F0; }

        /* ── BODY ── */
        .tab-content { display:none; padding:2.5rem 3rem; }
        .tab-content.active { display:block; }

        /* ── STATS ── */
        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(14rem,1fr)); gap:1.5rem; margin-bottom:2.5rem; }
        .stat-card {
            background:#fff; border-radius:14px;
            padding:1.8rem; text-align:center;
            border:1px solid #e8e8e8;
            box-shadow:0 2px 8px rgba(27,23,34,0.06);
        }
        .stat-card .num { font-size:3rem; font-weight:700; color:#1B1722; }
        .stat-card .num.orange { color:#e67e22; }
        .stat-card .num.blue   { color:#2980b9; }
        .stat-card .num.green  { color:#27ae60; }
        .stat-card .num.red    { color:#c0392b; }
        .stat-card .lbl { font-size:1.3rem; color:#999; margin-top:.4rem; }

        /* ── SECTION TITLE ── */
        .section-title {
            font-size:1.8rem; font-weight:700;
            color:#1B1722; margin-bottom:1.5rem;
            padding-bottom:.8rem;
            border-bottom:2px solid #e8e8e8;
        }

        /* ── TABLES ── */
        .table-wrap { overflow-x:auto; border-radius:14px; border:1px solid #e8e8e8; background:#fff; }
        table { width:100%; border-collapse:collapse; font-size:1.4rem; }
        th {
            background:#f5f5f5; color:#1B1722;
            padding:1.2rem 1rem; text-align:left;
            font-weight:700; white-space:nowrap;
            border-bottom:2px solid #e8e8e8;
        }
        td {
            padding:1rem 1rem;
            border-bottom:1px solid #f0f0f0;
            vertical-align:middle; color:#1B1722;
        }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#fafafa; }

        /* ── BADGES ── */
        .badge {
            display:inline-block; padding:.3rem 1rem;
            border-radius:20px; font-size:1.2rem; font-weight:700;
            white-space:nowrap;
        }
        .b-Pending   { background:#fff3e0; color:#e67e22; }
        .b-Preparing { background:#e3f2fd; color:#2980b9; }
        .b-Delivered { background:#e8f5e9; color:#27ae60; }
        .b-Cancelled { background:#fce4ec; color:#c0392b; }

        /* ── DAYS LEFT ── */
        .urgent { color:#c0392b; font-weight:700; }
        .soon   { color:#e67e22; font-weight:700; }
        .ok     { color:#27ae60; font-weight:600; }

        /* ── FORMS IN TABLE ── */
        .status-form { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        select.status-select {
            background:#f5f5f5; color:#1B1722;
            border:1.5px solid #e0e0e0; border-radius:6px;
            padding:.4rem .8rem; font-size:1.3rem; cursor:pointer;
            font-family:'Quicksand',sans-serif;
        }
        input.date-input {
            background:#f5f5f5; color:#1B1722;
            border:1.5px solid #e0e0e0; border-radius:6px;
            padding:.4rem .8rem; font-size:1.3rem;
            font-family:'Quicksand',sans-serif;
        }
        .update-btn {
            background:#1B1722; color:#F0F0F0;
            border:none; padding:.5rem 1.2rem;
            border-radius:6px; cursor:pointer;
            font-size:1.3rem; font-weight:700;
            font-family:'Quicksand',sans-serif;
            transition:background .2s;
        }
        .update-btn:hover { background:#2d2638; }

        /* ── CALENDAR ── */
        .cal-nav { display:flex; align-items:center; gap:1.2rem; margin-bottom:1.5rem; }
        .cal-nav button {
            background:#fff; color:#1B1722;
            border:1.5px solid #e0e0e0; padding:.6rem 1.4rem;
            border-radius:8px; font-size:1.4rem; cursor:pointer;
            font-family:'Quicksand',sans-serif; font-weight:600;
            transition:all .2s;
        }
        .cal-nav button:hover { background:#1B1722; color:#F0F0F0; border-color:#1B1722; }
        .cal-nav span { font-size:1.8rem; font-weight:700; color:#1B1722; }

        .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:.5rem; }
        .cal-head { text-align:center; font-size:1.3rem; color:#999; padding:.5rem 0; font-weight:700; }
        .cal-day {
            background:#fff; border-radius:10px;
            padding:.8rem .6rem; min-height:7rem;
            border:1px solid #e8e8e8; cursor:pointer;
            transition:all .2s;
        }
        .cal-day:hover { border-color:#1B1722; box-shadow:0 2px 8px rgba(27,23,34,0.1); }
        .cal-day.today { border:2px solid #1B1722; background:#f8f8f8; }
        .cal-day.empty { background:transparent; border-color:transparent; cursor:default; }
        .cal-day.empty:hover { border-color:transparent; box-shadow:none; }
        .cal-day .dn { font-size:1.4rem; font-weight:700; color:#1B1722; margin-bottom:.3rem; }

        .cal-tag {
            display:block; font-size:1rem; border-radius:4px;
            padding:.2rem .5rem; margin:.2rem 0;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .ct-urgent { background:#fce4ec; color:#c0392b; }
        .ct-soon   { background:#fff3e0; color:#e67e22; }
        .ct-ok     { background:#e8f5e9; color:#27ae60; }

        /* ── DAY DETAIL MODAL ── */
        .day-modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(27,23,34,0.4); z-index:500;
        }
        .day-modal-overlay.active { display:block; }
        .day-modal {
            position:fixed; top:50%; left:50%;
            transform:translate(-50%,-50%);
            background:#fff; border-radius:16px;
            padding:2.5rem; width:50rem; max-width:95vw;
            max-height:80vh; overflow-y:auto;
            z-index:600; box-shadow:0 8px 40px rgba(27,23,34,0.15);
        }
        .day-modal h3 { font-size:2rem; color:#1B1722; margin-bottom:1.5rem; font-weight:700; }
        .day-modal .close-day { float:right; cursor:pointer; color:#999; font-size:2rem; background:none; border:none; }
        .day-modal .close-day:hover { color:#c0392b; }
        .day-order-card {
            background:#f8f8f8; border-radius:10px;
            padding:1.5rem; margin-bottom:1rem;
            border-left:4px solid #1B1722;
        }
        .day-order-card.urgent { border-left-color:#c0392b; background:#fff5f5; }
        .day-order-card.soon   { border-left-color:#e67e22; background:#fffaf0; }
        .day-order-card h4 { font-size:1.6rem; color:#1B1722; margin-bottom:.5rem; font-weight:700; }
        .day-order-card p  { font-size:1.3rem; color:#555; margin:.2rem 0; }

        /* ── DEADLINE POPUP ── */
        .deadline-popup {
            display:none; position:fixed;
            bottom:2rem; right:2rem;
            background:#fff; border-radius:14px;
            padding:2rem; width:32rem; max-width:95vw;
            border-left:5px solid #c0392b;
            box-shadow:0 8px 30px rgba(27,23,34,0.15);
            z-index:9999; animation:slideIn .4s ease;
        }
        .deadline-popup.active { display:block; }
        @keyframes slideIn {
            from { transform:translateX(120%); opacity:0; }
            to   { transform:translateX(0);    opacity:1; }
        }
        .deadline-popup h4 { font-size:1.6rem; color:#c0392b; margin-bottom:.8rem; font-weight:700; }
        .deadline-popup p  { font-size:1.3rem; color:#1B1722; margin:.3rem 0; }
        .deadline-popup .close-popup {
            position:absolute; top:1rem; right:1rem;
            cursor:pointer; color:#999; font-size:1.8rem;
            background:none; border:none;
        }
        .deadline-popup .close-popup:hover { color:#c0392b; }

        .pay-detail { font-size:1.2rem; color:#27ae60; }

        @media(max-width:768px) {
            .admin-header { flex-direction:column; gap:1rem; padding:1.2rem; }
            .tab-nav { padding:1rem 1rem 0; overflow-x:auto; }
            .tab-content { padding:1.5rem 1rem; }
            .stats { grid-template-columns:repeat(2,1fr); }
            .cal-day { min-height:5rem; }
            .cal-tag { display:none; }
            .session-info-box { flex-direction:column; gap:1rem; }
        }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['admin'])): ?>
<!-- ── LOGIN SCREEN ── -->
<div class="login-screen">
    <div class="login-box">
        <div class="cake-icon"><i class="fas fa-birthday-cake"></i></div>
        <h2>Sweet Cake</h2>
        <p class="subtitle">Admin Dashboard Login</p>
        <?php if (!empty($loginError)): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="admin_username" placeholder="Admin Username" required>
            <input type="password" name="admin_password" placeholder="Password" required>
            <button type="submit" name="admin_login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ── DASHBOARD ── -->
<div class="admin-header">
    <div class="brand">
        <i class="fas fa-birthday-cake"></i>
        <h1>Sweet Cake — Admin</h1>
    </div>
    <!--
        Logout link calls ?logout=1
        PHP destroys $_SESSION (session.invalidate() equivalent)
        Satisfies: Exercise 1 logout requirement
    -->
    <a href="?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="tab-nav">
    <button class="tab-btn active" onclick="showTab('dashboard',this)"><i class="fas fa-tachometer-alt"></i> Dashboard</button>
    <button class="tab-btn" onclick="showTab('orders',this)"><i class="fas fa-shopping-bag"></i> Orders</button>
    <button class="tab-btn" onclick="showTab('calendar',this)"><i class="fas fa-calendar-alt"></i> Calendar</button>
    <button class="tab-btn" onclick="showTab('users',this)"><i class="fas fa-users"></i> Users</button>
</div>

<?php
    $total     = count($orders);
    $pending   = count(array_filter($orders, fn($o) => $o['status'] === 'Pending'));
    $preparing = count(array_filter($orders, fn($o) => $o['status'] === 'Preparing'));
    $delivered = count(array_filter($orders, fn($o) => $o['status'] === 'Delivered'));
    $cancelled = count(array_filter($orders, fn($o) => $o['status'] === 'Cancelled'));
    $revenue   = array_sum(array_column($orders, 'cake_price'));
?>

<!-- ── DASHBOARD TAB ── -->
<div class="tab-content active" id="tab-dashboard">

    <!--
        SESSION INFO BOX
        Satisfies: Exercise 2 requirement 3 —
        "Display the user's session ID and login time on the dashboard"
        PHP session_id() = Java's session.getId()
        $_SESSION['admin_login_time'] = Java's session.getAttribute("loginTime")
    -->
    <div class="session-info-box">
        <div class="si-item">
            <i class="fas fa-user-shield"></i>
            <span>Logged in as: <strong><?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></strong></span>
        </div>
        <div class="si-item">
            <i class="fas fa-clock"></i>
            <span>Login time: <strong><?= htmlspecialchars($_SESSION['admin_login_time'] ?? '—') ?></strong></span>
        </div>
        <div class="si-item">
            <i class="fas fa-fingerprint"></i>
            <span>Session ID: <span class="si-id"><?= session_id() ?></span></span>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card"><div class="num"><?= $total ?></div><div class="lbl"><i class="fas fa-shopping-bag"></i> Total Orders</div></div>
        <div class="stat-card"><div class="num orange"><?= $pending ?></div><div class="lbl"><i class="fas fa-clock"></i> Pending</div></div>
        <div class="stat-card"><div class="num blue"><?= $preparing ?></div><div class="lbl"><i class="fas fa-fire"></i> Preparing</div></div>
        <div class="stat-card"><div class="num green"><?= $delivered ?></div><div class="lbl"><i class="fas fa-check-circle"></i> Delivered</div></div>
        <div class="stat-card"><div class="num">KSh <?= number_format($revenue) ?></div><div class="lbl"><i class="fas fa-money-bill-wave"></i> Revenue</div></div>
    </div>

    <p class="section-title"><i class="fas fa-fire"></i> Cakes to Bake</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Customer</th><th>Phone</th><th>Cake(s)</th>
                    <th>Size</th><th>Message</th>
                    <th>Delivery Date</th><th>Days Left</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $baking = array_filter($orders, fn($o) => !in_array($o['status'], ['Delivered','Cancelled']));
            usort($baking, fn($a,$b) =>
                strtotime($a['delivery_date'] ?? '9999-12-31') -
                strtotime($b['delivery_date'] ?? '9999-12-31')
            );
            if (empty($baking)):
            ?>
                <tr><td colspan="8" style="text-align:center;color:#999;padding:3rem;">No active orders yet.</td></tr>
            <?php else: foreach ($baking as $o):
                $daysLeft = 'No date set';
                $cls      = '';
                if (!empty($o['delivery_date'])) {
                    $diff = ceil((strtotime($o['delivery_date']) - time()) / 86400);
                    if ($diff < 0)      { $daysLeft = 'OVERDUE by '.abs($diff).' day(s)'; $cls = 'urgent'; }
                    elseif ($diff == 0) { $daysLeft = 'TODAY!';  $cls = 'urgent'; }
                    elseif ($diff <= 2) { $daysLeft = $diff.' day(s) left'; $cls = 'urgent'; }
                    elseif ($diff <= 5) { $daysLeft = $diff.' day(s) left'; $cls = 'soon'; }
                    else                { $daysLeft = $diff.' day(s) left'; $cls = 'ok'; }
                }
            ?>
                <tr>
                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td><?= htmlspecialchars($o['phone']) ?></td>
                    <td><strong><?= htmlspecialchars($o['cake_name']) ?></strong></td>
                    <td><?= htmlspecialchars($o['size']) ?></td>
                    <td><?= htmlspecialchars($o['message'] ?: '—') ?></td>
                    <td><?= !empty($o['delivery_date']) ? date('d M Y', strtotime($o['delivery_date'])) : '—' ?></td>
                    <td class="<?= $cls ?>"><?= $daysLeft ?></td>
                    <td><span class="badge b-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── ORDERS TAB ── -->
<div class="tab-content" id="tab-orders">
    <p class="section-title"><i class="fas fa-shopping-bag"></i> All Orders</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Customer</th><th>Phone</th><th>Area</th>
                    <th>Cake(s)</th><th>Price</th><th>Size</th><th>Message</th>
                    <th>M-Pesa No.</th><th>Code</th>
                    <th>Delivery Date</th><th>Ordered</th><th>Status</th><th>Update</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="14" style="text-align:center;color:#999;padding:3rem;">No orders yet.</td></tr>
            <?php else: foreach ($orders as $o): ?>
                <tr>
                    <td><?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td><?= htmlspecialchars($o['phone']) ?></td>
                    <td><?= htmlspecialchars($o['address']) ?></td>
                    <td><strong><?= htmlspecialchars($o['cake_name']) ?></strong></td>
                    <td>KSh <?= number_format($o['cake_price'],2) ?></td>
                    <td><?= htmlspecialchars($o['size']) ?></td>
                    <td><?= htmlspecialchars($o['message'] ?: '—') ?></td>
                    <td class="pay-detail"><?= htmlspecialchars($o['mpesa_number'] ?: '—') ?></td>
                    <td class="pay-detail"><?= htmlspecialchars($o['mpesa_code'] ?: '—') ?></td>
                    <td><?= !empty($o['delivery_date']) ? date('d M Y', strtotime($o['delivery_date'])) : '—' ?></td>
                    <td style="white-space:nowrap;"><?= date('d M Y H:i', strtotime($o['ordered_at'])) ?></td>
                    <td><span class="badge b-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
                    <td>
                        <form method="POST" class="status-form">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <select name="status" class="status-select">
                                <?php foreach(['Pending','Preparing','Delivered','Cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" name="delivery_date" class="date-input" value="<?= $o['delivery_date'] ?? '' ?>">
                            <button type="submit" name="update_status" class="update-btn">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── CALENDAR TAB ── -->
<div class="tab-content" id="tab-calendar">
    <p class="section-title"><i class="fas fa-calendar-alt"></i> Delivery Calendar</p>
    <div class="cal-nav">
        <button onclick="changeMonth(-1)">&#8592; Prev</button>
        <span id="cal-month-label"></span>
        <button onclick="changeMonth(1)">Next &#8594;</button>
    </div>
    <div class="cal-grid" id="calendar-grid"></div>
</div>

<!-- ── USERS TAB ── -->
<div class="tab-content" id="tab-users">
    <p class="section-title"><i class="fas fa-users"></i> Registered Users</p>
    <div class="stats" style="margin-bottom:2rem;">
        <div class="stat-card"><div class="num"><?= count($users) ?></div><div class="lbl">Total Users</div></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Username</th><th>Email</th><th>Joined</th></tr></thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="4" style="text-align:center;color:#999;padding:3rem;">No users yet.</td></tr>
            <?php else: foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── DAY DETAIL MODAL ── -->
<div class="day-modal-overlay" id="day-modal-overlay" onclick="closeDayModal()"></div>
<div class="day-modal" id="day-modal" style="display:none;">
    <button class="close-day" onclick="closeDayModal()"><i class="fas fa-times"></i></button>
    <h3 id="day-modal-title"></h3>
    <div id="day-modal-body"></div>
</div>

<!-- ── DEADLINE POPUP ── -->
<div class="deadline-popup" id="deadline-popup">
    <button class="close-popup" onclick="closeDeadlinePopup()"><i class="fas fa-times"></i></button>
    <h4><i class="fas fa-exclamation-triangle"></i> Urgent Deadline!</h4>
    <div id="deadline-popup-body"></div>
</div>

<?php
$ordersJson = json_encode(array_map(fn($o) => [
    'id'            => $o['id'],
    'customer_name' => $o['customer_name'],
    'cake_name'     => $o['cake_name'],
    'size'          => $o['size'],
    'phone'         => $o['phone'],
    'message'       => $o['message'] ?? '',
    'delivery_date' => $o['delivery_date'] ?? '',
    'status'        => $o['status'],
    'cake_price'    => $o['cake_price'],
], $orders));
?>
<script>
const allOrders = <?= $ordersJson ?>;
const today = new Date(); today.setHours(0,0,0,0);
const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const dayNames   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
let calYear = today.getFullYear(), calMonth = today.getMonth();

function showTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
    if (name === 'calendar') renderCalendar();
}

function getDayCls(d) {
    if (!d) return 'ok';
    const diff = Math.ceil((new Date(d) - today) / 86400000);
    return diff < 0 ? 'urgent' : diff <= 2 ? 'urgent' : diff <= 5 ? 'soon' : 'ok';
}

function changeMonth(dir) {
    calMonth += dir;
    if (calMonth < 0)  { calMonth = 11; calYear--; }
    if (calMonth > 11) { calMonth = 0;  calYear++; }
    renderCalendar();
}

function renderCalendar() {
    document.getElementById('cal-month-label').textContent = monthNames[calMonth] + ' ' + calYear;
    const grid = document.getElementById('calendar-grid');
    grid.innerHTML = '';
    dayNames.forEach(d => { const h = document.createElement('div'); h.className='cal-head'; h.textContent=d; grid.appendChild(h); });

    const firstDay  = new Date(calYear, calMonth, 1).getDay();
    const totalDays = new Date(calYear, calMonth+1, 0).getDate();
    const byDate    = {};
    allOrders.forEach(o => { if (!o.delivery_date) return; if (!byDate[o.delivery_date]) byDate[o.delivery_date]=[]; byDate[o.delivery_date].push(o); });

    for (let i=0; i<firstDay; i++) { const e=document.createElement('div'); e.className='cal-day empty'; grid.appendChild(e); }

    for (let day=1; day<=totalDays; day++) {
        const dateStr = calYear+'-'+String(calMonth+1).padStart(2,'0')+'-'+String(day).padStart(2,'0');
        const cell    = document.createElement('div');
        const cDate   = new Date(calYear, calMonth, day); cDate.setHours(0,0,0,0);
        cell.className = 'cal-day' + (cDate.getTime()===today.getTime()?' today':'');

        const numDiv = document.createElement('div'); numDiv.className='dn'; numDiv.textContent=day; cell.appendChild(numDiv);
        const dayOrders = byDate[dateStr] || [];
        dayOrders.slice(0,3).forEach(o => { const t=document.createElement('span'); t.className='cal-tag ct-'+getDayCls(o.delivery_date); t.textContent=o.cake_name.split(',')[0]; cell.appendChild(t); });
        if (dayOrders.length>3) { const m=document.createElement('span'); m.className='cal-tag'; m.style.color='#999'; m.textContent='+'+(dayOrders.length-3)+' more'; cell.appendChild(m); }
        if (dayOrders.length>0) cell.addEventListener('click', ()=>openDayModal(dateStr, dayOrders));
        grid.appendChild(cell);
    }
}

function openDayModal(dateStr, orders) {
    document.getElementById('day-modal-title').textContent = 'Orders for ' + new Date(dateStr).toDateString();
    const body = document.getElementById('day-modal-body'); body.innerHTML='';
    orders.forEach(o => {
        const cls  = getDayCls(o.delivery_date);
        const diff = o.delivery_date ? Math.ceil((new Date(o.delivery_date)-today)/86400000) : null;
        const card = document.createElement('div'); card.className='day-order-card '+(cls!=='ok'?cls:'');
        card.innerHTML=`<h4><i class="fas fa-birthday-cake"></i> ${o.cake_name}</h4>
            <p><i class="fas fa-user"></i> ${o.customer_name} &nbsp;|&nbsp; <i class="fas fa-phone"></i> ${o.phone}</p>
            <p><i class="fas fa-ruler"></i> ${o.size} &nbsp;|&nbsp; KSh ${parseFloat(o.cake_price).toLocaleString()}</p>
            ${o.message?`<p><i class="fas fa-comment"></i> "${o.message}"</p>`:''}
            <p style="margin-top:.5rem;"><span class="badge b-${o.status}">${o.status}</span>
            ${diff!==null?`<span style="margin-left:1rem;font-size:1.2rem;" class="${cls}">${diff<0?'OVERDUE by '+Math.abs(diff)+'d':diff===0?'Due TODAY!':diff+' day(s) left'}</span>`:''}
            </p>`;
        body.appendChild(card);
    });
    document.getElementById('day-modal-overlay').classList.add('active');
    document.getElementById('day-modal').style.display='block';
}
function closeDayModal() {
    document.getElementById('day-modal-overlay').classList.remove('active');
    document.getElementById('day-modal').style.display='none';
}

function showDeadlinePopup() {
    const urgent = allOrders.filter(o => {
        if (!o.delivery_date || ['Delivered','Cancelled'].includes(o.status)) return false;
        return Math.ceil((new Date(o.delivery_date)-today)/86400000) <= 2;
    });
    if (!urgent.length) return;
    const body = document.getElementById('deadline-popup-body'); body.innerHTML='';
    urgent.forEach(o => {
        const diff = Math.ceil((new Date(o.delivery_date)-today)/86400000);
        const p = document.createElement('p');
        p.innerHTML=`<strong>${o.cake_name}</strong> for ${o.customer_name} — <span class="urgent">${diff<0?'OVERDUE by '+Math.abs(diff)+'d!':diff===0?'Due TODAY!':diff+'d left!'}</span>`;
        body.appendChild(p);
    });
    document.getElementById('deadline-popup').classList.add('active');
    setTimeout(closeDeadlinePopup, 12000);
}
function closeDeadlinePopup() { document.getElementById('deadline-popup').classList.remove('active'); }

window.addEventListener('load', () => { renderCalendar(); setTimeout(showDeadlinePopup, 1500); });
</script>

<?php endif; ?>
</body>
</html>