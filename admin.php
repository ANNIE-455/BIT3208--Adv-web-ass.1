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

if (isset($_POST['admin_login'])) {
    $u = trim($_POST['admin_username'] ?? '');
    $p = trim($_POST['admin_password'] ?? '');
    if (empty($u)) {
        $loginError = "Admin username cannot be empty.";
    } elseif ($u === ADMIN_USER && $p === ADMIN_PASS) {
        $_SESSION['admin']            = true;
        $_SESSION['admin_user']       = $u;
        $_SESSION['admin_login_time'] = date('d M Y, H:i:s');
        echo "<script>window.location.href = window.location.href.split('?')[0];</script>";
        exit;
    } else {
        $loginError = "Invalid username or password.";
    }
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    echo "<script>window.location.href = window.location.pathname;</script>";
    exit;
}

if (isset($_POST['update_status']) && isset($_SESSION['admin'])) {
    $id            = intval($_POST['order_id']);
    $status        = $conn->real_escape_string($_POST['status']);
    $delivery_date = $conn->real_escape_string($_POST['delivery_date'] ?? '');
    $conn->query("UPDATE orders SET status='$status', delivery_date=" .
        ($delivery_date ? "'$delivery_date'" : "NULL") . " WHERE id=$id");
    echo "<script>window.location.href = window.location.pathname;</script>";
    exit;
}

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
        /* =========================================
           RESET & BASE — matches main site colors
           ========================================= */
        * {
            margin:0; padding:0; box-sizing:border-box;
            font-family:'Quicksand',sans-serif;
            text-transform:none;
        }

        :root {
            --black:    #1B1722;
            --white:    #F0F0F0;
            --bg:       #F0F0F0;
            --card:     #ffffff;
            --border:   #e8e8e8;
            --text:     #1B1722;
            --muted:    #888;
            --accent:   #00efff;
            --danger:   #c0392b;
            --success:  #27ae60;
            --warning:  #e67e22;
            --info:     #2980b9;
            --shadow:   0 2px 8px rgba(27,23,34,0.08);
        }

        body { background:var(--bg); color:var(--text); min-height:100vh; }

        /* =========================================
           LOGIN SCREEN
           ========================================= */
        .login-screen {
            display:flex; justify-content:center; align-items:center;
            min-height:100vh; background:var(--bg);
        }
        .login-box {
            background:var(--card); border-radius:20px;
            padding:4rem 3rem; width:380px;
            box-shadow:0 4px 24px rgba(27,23,34,0.12);
            text-align:center;
            border:1px solid var(--border);
        }
        .login-box .cake-icon { font-size:4rem; color:var(--black); margin-bottom:1rem; }
        .login-box h2 { font-size:2.4rem; color:var(--black); font-weight:700; margin-bottom:.3rem; }
        .login-box .subtitle { font-size:1.4rem; color:var(--muted); margin-bottom:2.5rem; }
        .login-box input {
            width:100%; padding:1.2rem 1.5rem; margin-bottom:1.2rem;
            border-radius:10px; border:1.5px solid var(--border);
            background:var(--bg); color:var(--text);
            font-size:1.5rem; font-family:'Quicksand',sans-serif;
            transition:border-color .2s;
        }
        .login-box input:focus { border-color:var(--black); outline:none; background:#fff; }
        .login-box button {
            width:100%; padding:1.3rem;
            background:var(--black); color:var(--white);
            font-size:1.6rem; font-weight:700;
            border-radius:10px; cursor:pointer; border:none;
            font-family:'Quicksand',sans-serif; transition:opacity .2s;
        }
        .login-box button:hover { opacity:.85; }
        .error-msg {
            background:#fff0f0; color:var(--danger);
            border:1px solid #fcc; border-radius:8px;
            padding:.8rem 1rem; font-size:1.3rem; margin-bottom:1.5rem;
        }

        /* =========================================
           HEADER
           ========================================= */
        .admin-header {
            background:var(--card);
            padding:1.2rem 2.5rem;
            display:flex; justify-content:space-between; align-items:center;
            border-bottom:1px solid var(--border);
            position:sticky; top:0; z-index:100;
            box-shadow:var(--shadow);
        }
        .admin-header .brand { display:flex; align-items:center; gap:1rem; }
        .admin-header .brand i { font-size:2rem; color:var(--black); }
        .admin-header h1 { font-size:1.8rem; color:var(--black); font-weight:700; }
        .admin-header .right { display:flex; align-items:center; gap:1.5rem; }
        .admin-header .back-site {
            color:var(--black); font-size:1.3rem; font-weight:600;
            display:flex; align-items:center; gap:.4rem;
            text-decoration:none;
            border:1.5px solid var(--border);
            padding:.5rem 1.2rem; border-radius:8px;
            transition:all .2s;
        }
        .admin-header .back-site:hover { background:var(--black); color:var(--white); border-color:var(--black); }
        .admin-header .logout-btn {
            background:var(--black); color:var(--white);
            padding:.6rem 1.5rem; border-radius:8px;
            font-size:1.3rem; text-decoration:none;
            font-weight:600; transition:opacity .2s;
            display:flex; align-items:center; gap:.4rem;
        }
        .admin-header .logout-btn:hover { opacity:.85; }

        /* =========================================
           SESSION INFO BOX
           ========================================= */
        .session-info-box {
            background:var(--card);
            border:1px solid var(--border);
            border-left:4px solid var(--black);
            border-radius:0 10px 10px 0;
            padding:1.2rem 2rem;
            margin-bottom:2rem;
            display:flex; flex-wrap:wrap; gap:2rem; align-items:center;
            font-size:1.3rem;
        }
        .session-info-box .si-item {
            display:flex; align-items:center; gap:.5rem; color:var(--text);
        }
        .session-info-box .si-item i { color:var(--info); font-size:1.4rem; }
        .session-info-box .si-item strong { color:var(--black); font-weight:700; }
        .session-info-box .si-id {
            font-size:1.1rem; color:var(--muted);
            font-family:monospace; word-break:break-all;
        }

        /* =========================================
           TABS
           ========================================= */
        .tab-nav {
            display:flex; gap:.6rem; padding:1.5rem 2.5rem 0;
            background:var(--bg); border-bottom:2px solid var(--border);
            overflow-x:auto;
        }
        .tab-btn {
            padding:.8rem 1.8rem; border-radius:8px 8px 0 0;
            cursor:pointer; font-size:1.4rem; font-weight:600;
            border:none; background:#e4e4e4; color:var(--muted);
            font-family:'Quicksand',sans-serif; transition:all .2s;
            white-space:nowrap;
        }
        .tab-btn:hover { background:#d4d4d4; color:var(--black); }
        .tab-btn.active { background:var(--black); color:var(--white); }

        .tab-content { display:none; padding:2rem 2.5rem; }
        .tab-content.active { display:block; }

        /* =========================================
           STATS CARDS
           ========================================= */
        .stats {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(13rem,1fr));
            gap:1.2rem; margin-bottom:2rem;
        }
        .stat-card {
            background:var(--card); border-radius:12px;
            padding:1.5rem; text-align:center;
            border:1px solid var(--border); box-shadow:var(--shadow);
        }
        .stat-card .num { font-size:2.6rem; font-weight:700; color:var(--black); }
        .stat-card .num.orange { color:var(--warning); }
        .stat-card .num.blue   { color:var(--info); }
        .stat-card .num.green  { color:var(--success); }
        .stat-card .num.red    { color:var(--danger); }
        .stat-card .lbl { font-size:1.2rem; color:var(--muted); margin-top:.3rem; }

        /* =========================================
           SECTION TITLE
           ========================================= */
        .section-title {
            font-size:1.6rem; font-weight:700; color:var(--black);
            margin-bottom:1.2rem; padding-bottom:.6rem;
            border-bottom:2px solid var(--border);
            display:flex; align-items:center; gap:.6rem;
        }

        /* =========================================
           SEARCH BAR (orders tab)
           ========================================= */
        .search-bar {
            display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;
        }
        .search-bar input {
            flex:1; min-width:20rem; padding:.9rem 1.2rem;
            font-size:1.4rem; border-radius:8px;
            border:1.5px solid var(--border);
            background:var(--card); color:var(--text);
            font-family:'Quicksand',sans-serif;
            transition:border-color .2s;
        }
        .search-bar input:focus { border-color:var(--black); outline:none; }
        .search-bar button {
            padding:.9rem 2rem; font-size:1.4rem; font-weight:700;
            background:var(--black); color:var(--white);
            border:none; border-radius:8px; cursor:pointer;
            font-family:'Quicksand',sans-serif; transition:opacity .2s;
            display:flex; align-items:center; gap:.5rem;
        }
        .search-bar button:hover { opacity:.85; }
        .search-bar .clear-btn {
            background:var(--bg); color:var(--text);
            border:1.5px solid var(--border);
        }
        .search-bar .clear-btn:hover { background:var(--border); opacity:1; }

        /* =========================================
           TABLES — tablet sized, not full width
           ========================================= */
        .table-container {
            max-width:900px;          /* tablet width cap */
            margin:0 auto;
            overflow-x:auto;
            border-radius:12px;
            border:1px solid var(--border);
            background:var(--card);
            box-shadow:var(--shadow);
        }

        /* Full width only for the all-orders tab */
        .table-container.full-width { max-width:100%; }

        table { width:100%; border-collapse:collapse; font-size:1.3rem; }
        th {
            background:var(--black); color:var(--white);
            padding:1rem .8rem; text-align:left;
            font-weight:700; white-space:nowrap;
        }
        th:first-child { border-radius:0; }
        td {
            padding:.9rem .8rem;
            border-bottom:1px solid var(--border);
            vertical-align:middle; color:var(--text);
        }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#f8f8f8; }

        /* =========================================
           BADGES
           ========================================= */
        .badge {
            display:inline-block; padding:.25rem .9rem;
            border-radius:20px; font-size:1.1rem; font-weight:700;
            white-space:nowrap;
        }
        .b-Pending   { background:#fff3e0; color:var(--warning); }
        .b-Preparing { background:#e3f2fd; color:var(--info); }
        .b-Delivered { background:#e8f5e9; color:var(--success); }
        .b-Cancelled { background:#fce4ec; color:var(--danger); }

        /* =========================================
           DAYS LEFT
           ========================================= */
        .urgent { color:var(--danger); font-weight:700; }
        .soon   { color:var(--warning); font-weight:700; }
        .ok     { color:var(--success); font-weight:600; }

        /* =========================================
           STATUS FORM (inline in table)
           ========================================= */
        .status-form { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
        select.status-select {
            background:var(--bg); color:var(--text);
            border:1.5px solid var(--border); border-radius:6px;
            padding:.35rem .6rem; font-size:1.2rem; cursor:pointer;
            font-family:'Quicksand',sans-serif;
        }
        input.date-input {
            background:var(--bg); color:var(--text);
            border:1.5px solid var(--border); border-radius:6px;
            padding:.35rem .6rem; font-size:1.2rem;
            font-family:'Quicksand',sans-serif;
        }
        .update-btn {
            background:var(--black); color:var(--white);
            border:none; padding:.4rem 1rem;
            border-radius:6px; cursor:pointer;
            font-size:1.2rem; font-weight:700;
            font-family:'Quicksand',sans-serif; transition:opacity .2s;
        }
        .update-btn:hover { opacity:.85; }

        /* =========================================
           ACTION BUTTONS (edit / delete)
           ========================================= */
        .action-btns { display:flex; gap:.5rem; }
        .btn-edit {
            background:#e3f2fd; color:var(--info);
            border:1.5px solid var(--info); border-radius:6px;
            padding:.35rem .9rem; font-size:1.2rem; font-weight:700;
            cursor:pointer; font-family:'Quicksand',sans-serif;
            transition:all .2s; text-decoration:none;
            display:inline-flex; align-items:center; gap:.3rem;
        }
        .btn-edit:hover { background:var(--info); color:#fff; }
        .btn-delete {
            background:#fce4ec; color:var(--danger);
            border:1.5px solid var(--danger); border-radius:6px;
            padding:.35rem .9rem; font-size:1.2rem; font-weight:700;
            cursor:pointer; font-family:'Quicksand',sans-serif;
            transition:all .2s;
        }
        .btn-delete:hover { background:var(--danger); color:#fff; }

        /* =========================================
           EDIT MODAL
           ========================================= */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(27,23,34,0.5); z-index:500;
        }
        .modal-overlay.active { display:block; }
        .edit-modal {
            position:fixed; top:50%; left:50%;
            transform:translate(-50%,-50%);
            background:var(--card); border-radius:16px;
            padding:2.5rem; width:48rem; max-width:95vw;
            max-height:90vh; overflow-y:auto;
            z-index:600; box-shadow:0 8px 40px rgba(27,23,34,0.18);
        }
        .edit-modal h3 {
            font-size:1.8rem; color:var(--black);
            margin-bottom:1.5rem; font-weight:700;
            display:flex; justify-content:space-between; align-items:center;
        }
        .edit-modal .close-modal {
            cursor:pointer; color:var(--muted); font-size:1.8rem;
            background:none; border:none;
        }
        .edit-modal .close-modal:hover { color:var(--danger); }
        .form-field { margin-bottom:1.2rem; }
        .form-field label {
            display:block; font-size:1.3rem; font-weight:600;
            margin-bottom:.4rem; color:var(--text);
        }
        .form-field input,
        .form-field select {
            width:100%; padding:.9rem 1rem; font-size:1.4rem;
            border-radius:8px; border:1.5px solid var(--border);
            background:var(--bg); color:var(--text);
            font-family:'Quicksand',sans-serif; transition:border-color .2s;
        }
        .form-field input:focus,
        .form-field select:focus { border-color:var(--black); outline:none; }
        .modal-actions { display:flex; gap:1rem; margin-top:1.5rem; }
        .modal-actions button {
            flex:1; padding:1rem; font-size:1.5rem; font-weight:700;
            border-radius:8px; cursor:pointer; border:none;
            font-family:'Quicksand',sans-serif; transition:opacity .2s;
        }
        .btn-save { background:var(--black); color:var(--white); }
        .btn-save:hover { opacity:.85; }
        .btn-cancel { background:var(--bg); color:var(--text); border:1.5px solid var(--border) !important; }
        .btn-cancel:hover { background:var(--border); }

        /* =========================================
           CALENDAR
           ========================================= */
        .cal-nav { display:flex; align-items:center; gap:1rem; margin-bottom:1.2rem; }
        .cal-nav button {
            background:var(--card); color:var(--black);
            border:1.5px solid var(--border); padding:.5rem 1.2rem;
            border-radius:8px; font-size:1.3rem; cursor:pointer;
            font-family:'Quicksand',sans-serif; font-weight:600; transition:all .2s;
        }
        .cal-nav button:hover { background:var(--black); color:var(--white); border-color:var(--black); }
        .cal-nav span { font-size:1.6rem; font-weight:700; color:var(--black); }
        .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:.4rem; max-width:700px; }
        .cal-head { text-align:center; font-size:1.2rem; color:var(--muted); padding:.4rem 0; font-weight:700; }
        .cal-day {
            background:var(--card); border-radius:8px;
            padding:.6rem .4rem; min-height:6rem;
            border:1px solid var(--border); cursor:pointer; transition:all .2s;
        }
        .cal-day:hover { border-color:var(--black); box-shadow:var(--shadow); }
        .cal-day.today { border:2px solid var(--black); }
        .cal-day.empty { background:transparent; border-color:transparent; cursor:default; }
        .cal-day.empty:hover { border-color:transparent; box-shadow:none; }
        .cal-day .dn { font-size:1.3rem; font-weight:700; color:var(--black); margin-bottom:.2rem; }
        .cal-tag {
            display:block; font-size:.95rem; border-radius:4px;
            padding:.15rem .4rem; margin:.15rem 0;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .ct-urgent { background:#fce4ec; color:var(--danger); }
        .ct-soon   { background:#fff3e0; color:var(--warning); }
        .ct-ok     { background:#e8f5e9; color:var(--success); }

        /* =========================================
           DAY DETAIL MODAL
           ========================================= */
        .day-modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(27,23,34,0.4); z-index:500;
        }
        .day-modal-overlay.active { display:block; }
        .day-modal {
            position:fixed; top:50%; left:50%;
            transform:translate(-50%,-50%);
            background:var(--card); border-radius:14px;
            padding:2rem; width:46rem; max-width:95vw;
            max-height:80vh; overflow-y:auto;
            z-index:600; box-shadow:0 8px 40px rgba(27,23,34,0.15);
        }
        .day-modal h3 { font-size:1.8rem; color:var(--black); margin-bottom:1.2rem; font-weight:700; }
        .day-modal .close-day { float:right; cursor:pointer; color:var(--muted); font-size:1.8rem; background:none; border:none; }
        .day-modal .close-day:hover { color:var(--danger); }
        .day-order-card {
            background:var(--bg); border-radius:8px;
            padding:1.2rem; margin-bottom:.8rem;
            border-left:4px solid var(--black);
        }
        .day-order-card.urgent { border-left-color:var(--danger); background:#fff5f5; }
        .day-order-card.soon   { border-left-color:var(--warning); background:#fffaf0; }
        .day-order-card h4 { font-size:1.5rem; color:var(--black); margin-bottom:.4rem; font-weight:700; }
        .day-order-card p  { font-size:1.2rem; color:#555; margin:.2rem 0; }

        /* =========================================
           DEADLINE POPUP
           ========================================= */
        .deadline-popup {
            display:none; position:fixed;
            bottom:2rem; right:2rem;
            background:var(--card); border-radius:12px;
            padding:1.8rem; width:30rem; max-width:95vw;
            border-left:5px solid var(--danger);
            box-shadow:0 8px 30px rgba(27,23,34,0.15);
            z-index:9999; animation:slideIn .4s ease;
        }
        .deadline-popup.active { display:block; }
        @keyframes slideIn {
            from { transform:translateX(120%); opacity:0; }
            to   { transform:translateX(0);    opacity:1; }
        }
        .deadline-popup h4 { font-size:1.5rem; color:var(--danger); margin-bottom:.6rem; font-weight:700; }
        .deadline-popup p  { font-size:1.2rem; color:var(--text); margin:.2rem 0; }
        .deadline-popup .close-popup {
            position:absolute; top:.8rem; right:.8rem;
            cursor:pointer; color:var(--muted); font-size:1.6rem;
            background:none; border:none;
        }
        .deadline-popup .close-popup:hover { color:var(--danger); }

        .pay-detail { font-size:1.1rem; color:var(--success); }
        .empty-msg { text-align:center; color:var(--muted); padding:3rem; font-size:1.4rem; }

        /* =========================================
           RESPONSIVE
           ========================================= */
        @media(max-width:768px) {
            .admin-header { flex-direction:column; gap:.8rem; padding:1rem; }
            .tab-nav { padding:.8rem 1rem 0; }
            .tab-content { padding:1.2rem 1rem; }
            .stats { grid-template-columns:repeat(2,1fr); }
            .cal-day { min-height:4rem; }
            .cal-tag { display:none; }
            .session-info-box { flex-direction:column; gap:.8rem; }
            .table-container { max-width:100%; }
            .search-bar { flex-direction:column; }
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
        <p class="subtitle">Admin Dashboard</p>
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
    <div class="right">
        <a href="index.html" class="back-site"><i class="fas fa-home"></i> Back to Site</a>
        <a href="?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
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

    <!-- Session info box -->
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
        <div class="stat-card"><div class="num red"><?= $cancelled ?></div><div class="lbl"><i class="fas fa-times-circle"></i> Cancelled</div></div>
        <div class="stat-card"><div class="num">KSh <?= number_format($revenue) ?></div><div class="lbl"><i class="fas fa-money-bill-wave"></i> Revenue</div></div>
    </div>

    <p class="section-title"><i class="fas fa-fire"></i> Cakes to Bake</p>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Cake</th>
                    <th>Size</th>
                    <th>Delivery</th>
                    <th>Days Left</th>
                    <th>Status</th>
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
                <tr><td colspan="6" class="empty-msg">No active orders yet.</td></tr>
            <?php else: foreach ($baking as $o):
                $daysLeft = 'No date';
                $cls      = '';
                if (!empty($o['delivery_date'])) {
                    $diff = ceil((strtotime($o['delivery_date']) - time()) / 86400);
                    if ($diff < 0)      { $daysLeft = 'OVERDUE '.abs($diff).'d'; $cls = 'urgent'; }
                    elseif ($diff == 0) { $daysLeft = 'TODAY!';  $cls = 'urgent'; }
                    elseif ($diff <= 2) { $daysLeft = $diff.'d left'; $cls = 'urgent'; }
                    elseif ($diff <= 5) { $daysLeft = $diff.'d left'; $cls = 'soon'; }
                    else                { $daysLeft = $diff.'d left'; $cls = 'ok'; }
                }
            ?>
                <tr>
                    <td><?= htmlspecialchars($o['customer_name']) ?><br><small style="color:var(--muted)"><?= htmlspecialchars($o['phone']) ?></small></td>
                    <td><strong><?= htmlspecialchars($o['cake_name']) ?></strong></td>
                    <td><?= htmlspecialchars($o['size']) ?></td>
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

    <!-- Search bar -->
    <div class="search-bar">
        <input type="text" id="order-search" placeholder="Search by customer name or cake..." oninput="filterOrders()">
        <button class="clear-btn" onclick="clearSearch()"><i class="fas fa-times"></i> Clear</button>
    </div>

    <div class="table-container full-width">
        <table id="orders-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Area</th>
                    <th>Cake</th>
                    <th>Price</th>
                    <th>Size</th>
                    <th>M-Pesa</th>
                    <th>Delivery</th>
                    <th>Status</th>
                    <th>Update</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="orders-tbody">
            <?php if (empty($orders)): ?>
                <tr><td colspan="12" class="empty-msg">No orders yet.</td></tr>
            <?php else: foreach ($orders as $o): ?>
                <tr data-name="<?= strtolower(htmlspecialchars($o['customer_name'])) ?>"
                    data-cake="<?= strtolower(htmlspecialchars($o['cake_name'])) ?>">
                    <td><?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td><?= htmlspecialchars($o['phone']) ?></td>
                    <td><?= htmlspecialchars($o['address']) ?></td>
                    <td><strong><?= htmlspecialchars($o['cake_name']) ?></strong></td>
                    <td>KSh <?= number_format($o['cake_price'],2) ?></td>
                    <td><?= htmlspecialchars($o['size']) ?></td>
                    <td class="pay-detail"><?= htmlspecialchars($o['mpesa_code'] ?: '—') ?></td>
                    <td><?= !empty($o['delivery_date']) ? date('d M Y', strtotime($o['delivery_date'])) : '—' ?></td>
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
                    <td>
                        <div class="action-btns">
                            <!-- Edit button opens modal -->
                            <button class="btn-edit" onclick="openEditModal(
                                <?= $o['id'] ?>,
                                '<?= addslashes($o['customer_name']) ?>',
                                '<?= addslashes($o['phone']) ?>',
                                '<?= addslashes($o['address']) ?>',
                                '<?= addslashes($o['cake_name']) ?>',
                                '<?= $o['cake_price'] ?>',
                                '<?= $o['size'] ?>',
                                '<?= addslashes($o['message'] ?? '') ?>',
                                '<?= $o['delivery_date'] ?? '' ?>',
                                '<?= $o['status'] ?>'
                            )">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <!-- Delete with confirmation -->
                            <button class="btn-delete" onclick="confirmDelete(<?= $o['id'] ?>, '<?= addslashes($o['customer_name']) ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
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
    <div class="stats" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div class="num"><?= count($users) ?></div><div class="lbl">Total Users</div></div>
    </div>
    <div class="table-container">
        <table>
            <thead><tr><th>#</th><th>Username</th><th>Email</th><th>Joined</th></tr></thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="4" class="empty-msg">No users yet.</td></tr>
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

<!-- ── EDIT ORDER MODAL ── -->
<div class="modal-overlay" id="edit-overlay"></div>
<div class="edit-modal" id="edit-modal" style="display:none;">
    <form method="POST" action="customers.php">
        <h3>
            Edit Order
            <button type="button" class="close-modal" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </h3>
        <input type="hidden" name="edit_order" value="1">
        <input type="hidden" name="order_id" id="edit-id">
        <div class="form-field">
            <label>Customer Name</label>
            <input type="text" name="customer_name" id="edit-name" required>
        </div>
        <div class="form-field">
            <label>Phone</label>
            <input type="tel" name="phone" id="edit-phone" required>
        </div>
        <div class="form-field">
            <label>Delivery Area</label>
            <input type="text" name="address" id="edit-address" required>
        </div>
        <div class="form-field">
            <label>Cake</label>
            <input type="text" name="cake_name" id="edit-cake" required>
        </div>
        <div class="form-field">
            <label>Price (KSh)</label>
            <input type="number" name="cake_price" id="edit-price" required>
        </div>
        <div class="form-field">
            <label>Size</label>
            <select name="size" id="edit-size">
                <option value="small">Small</option>
                <option value="medium">Medium</option>
                <option value="large">Large</option>
                <option value="cart">Cart Order</option>
            </select>
        </div>
        <div class="form-field">
            <label>Message on Cake</label>
            <input type="text" name="message" id="edit-message">
        </div>
        <div class="form-field">
            <label>Delivery Date</label>
            <input type="date" name="delivery_date" id="edit-date">
        </div>
        <div class="form-field">
            <label>Status</label>
            <select name="status" id="edit-status">
                <option value="Pending">Pending</option>
                <option value="Preparing">Preparing</option>
                <option value="Delivered">Delivered</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
        <div class="modal-actions">
            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
        </div>
    </form>
</div>

<!-- ── DELETE FORM (hidden, submitted by JS) ── -->
<form method="POST" action="customers.php" id="delete-form" style="display:none;">
    <input type="hidden" name="delete_order" value="1">
    <input type="hidden" name="order_id" id="delete-order-id">
</form>

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
const allOrders  = <?= $ordersJson ?>;
const today      = new Date(); today.setHours(0,0,0,0);
const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const dayNames   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
let calYear = today.getFullYear(), calMonth = today.getMonth();

// ── TABS ──
function showTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
    if (name === 'calendar') renderCalendar();
}

// ── SEARCH ORDERS ──
function filterOrders() {
    const val = document.getElementById('order-search').value.toLowerCase().trim();
    document.querySelectorAll('#orders-tbody tr').forEach(row => {
        if (!row.dataset.name) return;
        const match = row.dataset.name.includes(val) || row.dataset.cake.includes(val);
        row.style.display = match ? '' : 'none';
    });
}
function clearSearch() {
    document.getElementById('order-search').value = '';
    filterOrders();
}

// ── EDIT MODAL ──
function openEditModal(id, name, phone, address, cake, price, size, message, date, status) {
    document.getElementById('edit-id').value      = id;
    document.getElementById('edit-name').value    = name;
    document.getElementById('edit-phone').value   = phone;
    document.getElementById('edit-address').value = address;
    document.getElementById('edit-cake').value    = cake;
    document.getElementById('edit-price').value   = price;
    document.getElementById('edit-size').value    = size;
    document.getElementById('edit-message').value = message;
    document.getElementById('edit-date').value    = date;
    document.getElementById('edit-status').value  = status;
    document.getElementById('edit-overlay').classList.add('active');
    document.getElementById('edit-modal').style.display = 'block';
}
function closeEditModal() {
    document.getElementById('edit-overlay').classList.remove('active');
    document.getElementById('edit-modal').style.display = 'none';
}

// ── DELETE WITH CONFIRMATION ──
function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete the order for "' + name + '"?\nThis cannot be undone.')) {
        document.getElementById('delete-order-id').value = id;
        document.getElementById('delete-form').submit();
    }
}

// ── CALENDAR ──
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
        const dateStr   = calYear+'-'+String(calMonth+1).padStart(2,'0')+'-'+String(day).padStart(2,'0');
        const cell      = document.createElement('div');
        const cDate     = new Date(calYear, calMonth, day); cDate.setHours(0,0,0,0);
        cell.className  = 'cal-day' + (cDate.getTime()===today.getTime()?' today':'');
        const numDiv    = document.createElement('div'); numDiv.className='dn'; numDiv.textContent=day; cell.appendChild(numDiv);
        const dayOrders = byDate[dateStr] || [];
        dayOrders.slice(0,2).forEach(o => { const t=document.createElement('span'); t.className='cal-tag ct-'+getDayCls(o.delivery_date); t.textContent=o.cake_name.split(',')[0]; cell.appendChild(t); });
        if (dayOrders.length>2) { const m=document.createElement('span'); m.className='cal-tag'; m.style.color='#999'; m.textContent='+'+(dayOrders.length-2)+' more'; cell.appendChild(m); }
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
            <p style="margin-top:.4rem;"><span class="badge b-${o.status}">${o.status}</span>
            ${diff!==null?`<span style="margin-left:.8rem;font-size:1.1rem;" class="${cls}">${diff<0?'OVERDUE '+Math.abs(diff)+'d':diff===0?'Due TODAY!':diff+'d left'}</span>`:''}
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

// ── DEADLINE POPUP ──
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
        p.innerHTML=`<strong>${o.cake_name}</strong> for ${o.customer_name} — <span class="urgent">${diff<0?'OVERDUE '+Math.abs(diff)+'d!':diff===0?'Due TODAY!':diff+'d left!'}</span>`;
        body.appendChild(p);
    });
    document.getElementById('deadline-popup').classList.add('active');
    setTimeout(closeDeadlinePopup, 12000);
}
function closeDeadlinePopup() { document.getElementById('deadline-popup').classList.remove('active'); }

window.addEventListener('load', () => { renderCalendar(); setTimeout(showDeadlinePopup,1500); });
</script>

<?php endif; ?>
</body>
</html>