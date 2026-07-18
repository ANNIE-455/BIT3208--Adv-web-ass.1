<?php
session_start();

$host    = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "cake_db";

// =============================================
//  USE PDO (PreparedStatement equivalent)
//  PHP PDO = Java PreparedStatement
//  Satisfies: Week 11 — "Ensure all SQL
//  operations use PreparedStatement"
// =============================================
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Auto-create orders table if missing
$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    phone         VARCHAR(20)  NOT NULL,
    address       TEXT         NOT NULL,
    cake_name     VARCHAR(255) NOT NULL,
    cake_price    DECIMAL(10,2) NOT NULL,
    size          VARCHAR(20)  NOT NULL,
    message       TEXT,
    mpesa_number  VARCHAR(20),
    mpesa_code    VARCHAR(50),
    delivery_date DATE         DEFAULT NULL,
    status        VARCHAR(50)  DEFAULT 'Pending',
    ordered_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

$isAdmin    = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
$message    = '';
$msgType    = '';

// =============================================
//  EDIT ORDER (admin only)
//  Uses PDO PreparedStatement
//  Satisfies: Week 11 — "Update database records"
// =============================================
if (isset($_POST['edit_order']) && $isAdmin) {
    $id            = intval($_POST['order_id']);
    $customer_name = trim($_POST['customer_name']);
    $phone         = trim($_POST['phone']);
    $address       = trim($_POST['address']);
    $cake_name     = trim($_POST['cake_name']);
    $cake_price    = floatval($_POST['cake_price']);
    $size          = trim($_POST['size']);
    $msg           = trim($_POST['message'] ?? '');
    $delivery_date = trim($_POST['delivery_date'] ?? '');
    $status        = trim($_POST['status']);

    // Input validation
    if (empty($customer_name) || empty($phone) || empty($cake_name)) {
        $message = "Name, phone and cake name are required.";
        $msgType = "error";
    } else {
        // PreparedStatement — equivalent to Java's PreparedStatement with setString()
        $stmt = $pdo->prepare("
            UPDATE orders SET
                customer_name = ?,
                phone         = ?,
                address       = ?,
                cake_name     = ?,
                cake_price    = ?,
                size          = ?,
                message       = ?,
                delivery_date = ?,
                status        = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $customer_name,
            $phone,
            $address,
            $cake_name,
            $cake_price,
            $size,
            $msg,
            $delivery_date ?: null,
            $status,
            $id
        ]);
        $message = "Order #$id updated successfully.";
        $msgType = "success";
    }
}

// =============================================
//  DELETE ORDER (admin only)
//  Uses PDO PreparedStatement
//  Satisfies: Week 11 — "Delete database records"
// =============================================
if (isset($_POST['delete_order']) && $isAdmin) {
    $id   = intval($_POST['order_id']);
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Order #$id deleted successfully.";
    $msgType = "success";
}

// =============================================
//  SEARCH ORDERS
//  Uses PDO PreparedStatement with LIKE
//  Satisfies: Week 11 — "Search for records"
//  Visible to ALL users (customers + admin)
// =============================================
$search      = trim($_GET['search'] ?? '');
$phoneSearch = trim($_GET['phone'] ?? '');
$orders      = [];

if ($isAdmin) {
    // Admin sees ALL orders, can search by name or cake
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT * FROM orders
            WHERE customer_name LIKE ? OR cake_name LIKE ?
            ORDER BY ordered_at DESC
        ");
        $like = '%' . $search . '%';
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders ORDER BY ordered_at DESC");
        $stmt->execute();
    }
    $orders = $stmt->fetchAll();

} else {
    // Customers see ONLY their own orders via phone number lookup
    if ($phoneSearch !== '') {
        $stmt = $pdo->prepare("
            SELECT * FROM orders
            WHERE phone = ?
            ORDER BY ordered_at DESC
        ");
        $stmt->execute([$phoneSearch]);
        $orders = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Cake — Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* =========================================
           BASE — matches main site exactly
           ========================================= */
        * {
            margin:0; padding:0; box-sizing:border-box;
            font-family:'Quicksand',sans-serif;
            text-transform:none;
        }

        :root {
            --black:   #1B1722;
            --white:   #F0F0F0;
            --bg:      #F0F0F0;
            --card:    #ffffff;
            --border:  #e8e8e8;
            --text:    #1B1722;
            --muted:   #888888;
            --accent:  #00efff;
            --danger:  #c0392b;
            --success: #27ae60;
            --warning: #e67e22;
            --info:    #2980b9;
            --shadow:  0 2px 8px rgba(27,23,34,0.08);
            --price:   #c0392b;
        }

        body { background:var(--bg); color:var(--text); min-height:100vh; }

        /* =========================================
           HEADER — matches main site header style
           ========================================= */
        .page-header {
            background:var(--card);
            padding:1.4rem 7%;
            display:flex; justify-content:space-between; align-items:center;
            position:sticky; top:0; z-index:100;
            box-shadow:var(--shadow);
            border-bottom:1px solid var(--border);
        }
        .page-header .brand {
            display:flex; align-items:center; gap:1rem;
            text-decoration:none;
        }
        .page-header .brand i { font-size:2.2rem; color:var(--black); }
        .page-header .brand span {
            font-size:2rem; font-weight:700; color:var(--black);
        }
        .page-header .nav-links {
            display:flex; align-items:center; gap:1.5rem;
        }
        .page-header .nav-links a {
            font-size:1.5rem; font-weight:600; color:var(--black);
            text-decoration:none; display:flex; align-items:center; gap:.4rem;
            padding:.5rem 1.2rem; border-radius:8px;
            border:1.5px solid var(--border);
            transition:all .2s;
        }
        .page-header .nav-links a:hover {
            background:var(--black); color:var(--white); border-color:var(--black);
        }
        .page-header .nav-links a.active {
            background:var(--black); color:var(--white); border-color:var(--black);
        }

        /* =========================================
           MAIN WRAPPER
           ========================================= */
        .main-wrap {
            max-width:960px;
            margin:3rem auto;
            padding:0 2rem;
        }

        /* =========================================
           PAGE TITLE
           ========================================= */
        .page-title {
            font-size:2.8rem; font-weight:700; color:var(--black);
            margin-bottom:.5rem;
            display:flex; align-items:center; gap:1rem;
        }
        .page-title i { color:var(--black); }
        .page-subtitle { font-size:1.5rem; color:var(--muted); margin-bottom:2.5rem; }

        /* =========================================
           ALERT / MESSAGE BOX
           ========================================= */
        .alert {
            padding:1.2rem 1.8rem; border-radius:10px;
            font-size:1.5rem; font-weight:600;
            margin-bottom:2rem;
            display:flex; align-items:center; gap:1rem;
        }
        .alert.success {
            background:#e8f5e9; color:var(--success);
            border:1.5px solid var(--success);
        }
        .alert.error {
            background:#fce4ec; color:var(--danger);
            border:1.5px solid var(--danger);
        }

        /* =========================================
           SEARCH SECTION
           ========================================= */
        .search-section {
            background:var(--card);
            border-radius:14px; padding:2rem;
            margin-bottom:2.5rem;
            border:1px solid var(--border);
            box-shadow:var(--shadow);
        }
        .search-section h3 {
            font-size:1.8rem; font-weight:700; color:var(--black);
            margin-bottom:1.2rem;
            display:flex; align-items:center; gap:.7rem;
        }
        .search-section h3 i { color:var(--black); }

        .search-form {
            display:flex; gap:1rem; flex-wrap:wrap;
        }
        .search-form input {
            flex:1; min-width:20rem; padding:1rem 1.4rem;
            font-size:1.5rem; border-radius:8px;
            border:1.5px solid var(--border);
            background:var(--bg); color:var(--text);
            font-family:'Quicksand',sans-serif;
            transition:border-color .2s;
        }
        .search-form input:focus { border-color:var(--black); outline:none; }
        .search-form button {
            padding:1rem 2rem; font-size:1.5rem; font-weight:700;
            background:var(--black); color:var(--white);
            border:none; border-radius:8px; cursor:pointer;
            font-family:'Quicksand',sans-serif;
            display:flex; align-items:center; gap:.5rem;
            transition:opacity .2s;
        }
        .search-form button:hover { opacity:.85; }
        .search-form .btn-clear {
            background:var(--bg); color:var(--text);
            border:1.5px solid var(--border) !important;
            border:none;
        }
        .search-form .btn-clear:hover { background:var(--border); opacity:1; }

        .search-hint {
            font-size:1.3rem; color:var(--muted);
            margin-top:1rem; display:flex; align-items:center; gap:.5rem;
        }

        /* =========================================
           RESULTS COUNT
           ========================================= */
        .results-bar {
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:1.2rem; flex-wrap:wrap; gap:1rem;
        }
        .results-count {
            font-size:1.5rem; font-weight:600; color:var(--text);
        }
        .results-count span { color:var(--black); font-weight:700; }

        /* =========================================
           ORDERS TABLE
           ========================================= */
        .table-wrap {
            background:var(--card); border-radius:14px;
            border:1px solid var(--border); overflow-x:auto;
            box-shadow:var(--shadow);
        }
        table { width:100%; border-collapse:collapse; font-size:1.35rem; }
        thead th {
            background:var(--black); color:var(--white);
            padding:1.1rem 1rem; text-align:left;
            font-weight:700; white-space:nowrap;
        }
        tbody td {
            padding:1rem; border-bottom:1px solid var(--border);
            vertical-align:middle; color:var(--text);
        }
        tbody tr:last-child td { border-bottom:none; }
        tbody tr:hover td { background:#f8f8f8; }

        /* =========================================
           BADGES
           ========================================= */
        .badge {
            display:inline-block; padding:.3rem 1rem;
            border-radius:20px; font-size:1.15rem; font-weight:700;
        }
        .b-Pending   { background:#fff3e0; color:var(--warning); }
        .b-Preparing { background:#e3f2fd; color:var(--info); }
        .b-Delivered { background:#e8f5e9; color:var(--success); }
        .b-Cancelled { background:#fce4ec; color:var(--danger); }

        /* =========================================
           PRICE
           ========================================= */
        .price-cell { font-weight:700; color:var(--price); }

        /* =========================================
           ACTION BUTTONS (admin only)
           ========================================= */
        .action-btns { display:flex; gap:.5rem; flex-wrap:wrap; }
        .btn-edit {
            background:#e3f2fd; color:var(--info);
            border:1.5px solid var(--info); border-radius:6px;
            padding:.35rem .9rem; font-size:1.2rem; font-weight:700;
            cursor:pointer; font-family:'Quicksand',sans-serif;
            transition:all .2s;
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
            padding:2.5rem; width:50rem; max-width:95vw;
            max-height:90vh; overflow-y:auto;
            z-index:600;
            box-shadow:0 8px 40px rgba(27,23,34,0.18);
        }
        .edit-modal h3 {
            font-size:1.8rem; font-weight:700; color:var(--black);
            margin-bottom:1.5rem;
            display:flex; justify-content:space-between; align-items:center;
        }
        .close-modal-btn {
            background:none; border:none; cursor:pointer;
            color:var(--muted); font-size:1.8rem;
        }
        .close-modal-btn:hover { color:var(--danger); }
        .form-field { margin-bottom:1.2rem; }
        .form-field label {
            display:block; font-size:1.3rem; font-weight:600;
            color:var(--text); margin-bottom:.4rem;
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
        .btn-cancel-modal {
            background:var(--bg); color:var(--text);
            border:1.5px solid var(--border) !important;
        }
        .btn-cancel-modal:hover { background:var(--border); }

        /* =========================================
           EMPTY STATE
           ========================================= */
        .empty-state {
            text-align:center; padding:5rem 2rem;
            color:var(--muted);
        }
        .empty-state i { font-size:5rem; margin-bottom:1.5rem; color:#ccc; }
        .empty-state h3 { font-size:2rem; margin-bottom:.8rem; color:var(--text); }
        .empty-state p  { font-size:1.5rem; }

        /* =========================================
           NO RESULTS
           ========================================= */
        .no-results {
            text-align:center; padding:3rem;
            color:var(--muted); font-size:1.5rem;
        }
        .no-results i { font-size:3rem; display:block; margin-bottom:1rem; color:#ccc; }

        /* =========================================
           FOOTER
           ========================================= */
        .page-footer {
            text-align:center; padding:2rem;
            font-size:1.3rem; color:var(--muted);
            margin-top:4rem;
            border-top:1px solid var(--border);
        }
        .page-footer a { color:var(--black); font-weight:600; text-decoration:none; }
        .page-footer a:hover { text-decoration:underline; }

        /* =========================================
           ADMIN BADGE IN HEADER
           ========================================= */
        .admin-badge {
            background:var(--black); color:var(--white);
            font-size:1.2rem; font-weight:700;
            padding:.3rem .9rem; border-radius:20px;
            display:flex; align-items:center; gap:.4rem;
        }

        /* =========================================
           RESPONSIVE
           ========================================= */
        @media(max-width:768px) {
            .main-wrap { padding:0 1rem; margin:2rem auto; }
            .page-header { padding:1.2rem 4%; }
            .page-header .nav-links a span { display:none; }
            .search-form { flex-direction:column; }
            .search-form input { min-width:100%; }
            .page-title { font-size:2.2rem; }
        }
        @media(max-width:480px) {
            .page-header .brand span { display:none; }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="page-header">
    <a href="index.html" class="brand">
        <i class="fas fa-birthday-cake"></i>
        <span>Sweet Cake</span>
    </a>
    <nav class="nav-links">
        <a href="index.html"><i class="fas fa-home"></i> <span>Home</span></a>
        <a href="index.html#product"><i class="fas fa-cake-candles"></i> <span>Products</span></a>
        <a href="customers.php" class="active"><i class="fas fa-receipt"></i> <span>My Orders</span></a>
        <?php if ($isAdmin): ?>
            <a href="admin.php"><i class="fas fa-lock"></i> <span>Admin</span></a>
            <span class="admin-badge"><i class="fas fa-user-shield"></i> Admin</span>
        <?php endif; ?>
    </nav>
</header>
<!-- ===== END HEADER ===== -->

<div class="main-wrap">

    <!-- Page title -->
    <h1 class="page-title">
        <i class="fas fa-receipt"></i>
        <?= $isAdmin ? 'All Customer Orders' : 'Track Your Order' ?>
    </h1>
    <p class="page-subtitle">
        <?= $isAdmin
            ? 'View, search, edit, and delete all orders. Full CRUD management.'
            : 'Enter your phone number to view your order history.' ?>
    </p>

    <!-- Alert message -->
    <?php if ($message): ?>
        <div class="alert <?= $msgType ?>">
            <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- =============================================
         SEARCH SECTION
         Visible to ALL users
         Satisfies: Week 11 — "Search feature"
         ============================================= -->
    <div class="search-section">
        <h3>
            <i class="fas fa-search"></i>
            <?= $isAdmin ? 'Search Orders' : 'Look Up Your Orders' ?>
        </h3>

        <?php if ($isAdmin): ?>
            <!-- Admin search: by customer name or cake name -->
            <form method="GET" class="search-form">
                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search by customer name or cake name..."
                    autocomplete="off"
                >
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if ($search): ?>
                    <a href="customers.php" style="text-decoration:none;">
                        <button type="button" class="btn-clear"><i class="fas fa-times"></i> Clear</button>
                    </a>
                <?php endif; ?>
            </form>
            <?php if ($search): ?>
                <p class="search-hint">
                    <i class="fas fa-info-circle"></i>
                    Showing results for: <strong>"<?= htmlspecialchars($search) ?>"</strong>
                </p>
            <?php endif; ?>

        <?php else: ?>
            <!-- Customer search: by phone number only -->
            <form method="GET" class="search-form">
                <input
                    type="tel"
                    name="phone"
                    value="<?= htmlspecialchars($phoneSearch) ?>"
                    placeholder="Enter your phone number e.g. 0712345678"
                    autocomplete="tel"
                    required
                >
                <button type="submit"><i class="fas fa-search"></i> Find My Orders</button>
                <?php if ($phoneSearch): ?>
                    <a href="customers.php" style="text-decoration:none;">
                        <button type="button" class="btn-clear"><i class="fas fa-times"></i> Clear</button>
                    </a>
                <?php endif; ?>
            </form>
            <p class="search-hint">
                <i class="fas fa-shield-alt"></i>
                Your orders are private. Only you can view them using your phone number.
            </p>
        <?php endif; ?>
    </div>

    <!-- =============================================
         ORDERS TABLE
         Satisfies: Week 10 — "Display all records dynamically"
         Satisfies: Week 11 — "View all records"
         ============================================= -->
    <?php if (!$isAdmin && $phoneSearch === ''): ?>
        <!-- Customer hasn't searched yet -->
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Enter Your Phone Number</h3>
            <p>Type the phone number you used when placing your order to see your order history.</p>
        </div>

    <?php elseif (empty($orders)): ?>
        <!-- No results found -->
        <div class="no-results">
            <i class="fas fa-inbox"></i>
            <?php if ($isAdmin && $search): ?>
                No orders found matching "<strong><?= htmlspecialchars($search) ?></strong>".
            <?php elseif (!$isAdmin): ?>
                No orders found for <strong><?= htmlspecialchars($phoneSearch) ?></strong>.
                Please check your phone number and try again.
            <?php else: ?>
                No orders have been placed yet.
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- Results found — show table -->
        <div class="results-bar">
            <p class="results-count">
                Showing <span><?= count($orders) ?></span>
                order<?= count($orders) !== 1 ? 's' : '' ?>
                <?= $search ? 'for "' . htmlspecialchars($search) . '"' : '' ?>
                <?= $phoneSearch ? 'for ' . htmlspecialchars($phoneSearch) : '' ?>
            </p>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Cake</th>
                        <th>Size</th>
                        <th>Price</th>
                        <th>Delivery Area</th>
                        <th>Message</th>
                        <th>Delivery Date</th>
                        <th>Ordered On</th>
                        <th>Status</th>
                        <?php if ($isAdmin): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td><?= htmlspecialchars($o['phone']) ?></td>
                        <td><strong><?= htmlspecialchars($o['cake_name']) ?></strong></td>
                        <td><?= htmlspecialchars($o['size']) ?></td>
                        <td class="price-cell">KSh <?= number_format($o['cake_price'], 2) ?></td>
                        <td><?= htmlspecialchars($o['address']) ?></td>
                        <td><?= htmlspecialchars($o['message'] ?: '—') ?></td>
                        <td><?= !empty($o['delivery_date']) ? date('d M Y', strtotime($o['delivery_date'])) : '—' ?></td>
                        <td style="white-space:nowrap;"><?= date('d M Y, H:i', strtotime($o['ordered_at'])) ?></td>
                        <td><span class="badge b-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
                        <?php if ($isAdmin): ?>
                        <td>
                            <div class="action-btns">
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
                                <button class="btn-delete" onclick="confirmDelete(<?= $o['id'] ?>, '<?= addslashes($o['customer_name']) ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <!-- =============================================
         EDIT MODAL (admin only)
         Satisfies: Week 11 — "Edit existing records"
         Uses PreparedStatement in PHP above
         ============================================= -->
    <div class="modal-overlay" id="edit-overlay"></div>
    <div class="edit-modal" id="edit-modal" style="display:none;">
        <form method="POST">
            <h3>
                <span><i class="fas fa-edit"></i> Edit Order</span>
                <button type="button" class="close-modal-btn" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
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
                <label>Cake Name</label>
                <input type="text" name="cake_name" id="edit-cake" required>
            </div>
            <div class="form-field">
                <label>Price (KSh)</label>
                <input type="number" name="cake_price" id="edit-price" min="0" required>
            </div>
            <div class="form-field">
                <label>Size</label>
                <select name="size" id="edit-size">
                    <option value="small">Small (6 inch)</option>
                    <option value="medium">Medium (8 inch)</option>
                    <option value="large">Large (10 inch)</option>
                    <option value="cart">Cart Order</option>
                </select>
            </div>
            <div class="form-field">
                <label>Message on Cake</label>
                <input type="text" name="message" id="edit-message" placeholder="e.g. Happy Birthday!">
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
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <button type="button" class="btn-cancel-modal" onclick="closeEditModal()">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Hidden delete form -->
    <form method="POST" id="delete-form" style="display:none;">
        <input type="hidden" name="delete_order" value="1">
        <input type="hidden" name="order_id" id="delete-id">
    </form>
    <?php endif; ?>

</div>
<!-- ===== END MAIN WRAP ===== -->

<!-- ===== FOOTER ===== -->
<footer class="page-footer">
    <p>
        &copy; 2024 Sweet Cake. All Rights Reserved. &nbsp;|&nbsp;
        <a href="index.html">Back to Home</a>
        <?php if ($isAdmin): ?>
            &nbsp;|&nbsp; <a href="admin.php">Admin Dashboard</a>
        <?php endif; ?>
    </p>
</footer>

<script>
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
    // Prevent background scroll
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('edit-overlay').classList.remove('active');
    document.getElementById('edit-modal').style.display = 'none';
    document.body.style.overflow = '';
}

// Close modal when clicking overlay
document.getElementById('edit-overlay')?.addEventListener('click', closeEditModal);

// ── DELETE WITH CONFIRMATION ──
function confirmDelete(id, name) {
    if (confirm('Delete order for "' + name + '"?\n\nThis action cannot be undone.')) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-form').submit();
    }
}
</script>

</body>
</html>
