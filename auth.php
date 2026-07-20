<?php
session_start();

$host    = "tokaido.proxy.rlwy.net";
$db_user = "root";
$db_pass = "YkKOBQYpumRgnUGSbtwzrzjkeZnRABYB";
$db_name = "railway";
$port    = 49769;

$conn = new mysqli($host, $db_user, $db_pass, $db_name, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Auto-create tables
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        username   VARCHAR(100) NOT NULL UNIQUE,
        email      VARCHAR(150) NOT NULL UNIQUE,
        password   VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS orders (
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
    )
");

// =============================================
//  LOGOUT
//  Satisfies: Exercise 1 —
//  "Logout button that invalidates the session
//   and redirects back to the login page"
//  PHP equivalent of: session.invalidate() in Java
// =============================================
if (isset($_GET['logout'])) {
    // Destroy the server-side session (HttpSession equivalent)
    $_SESSION = [];
    session_destroy();

    // Clear the session info cookie so JS hides the bar
    setcookie('sc_session_info', '', time() - 3600, '/');

    // NOTE: sc_remember cookie is intentionally NOT cleared here
    // so the username still auto-fills on next visit (Remember Me)

    // Redirect back to homepage (login page equivalent)
    header('Location: index.html');
    exit;
}

// =============================================
//  SIGN UP
// =============================================
if (isset($_POST['signup'])) {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $email    = strtolower($conn->real_escape_string(trim($_POST['email'])));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Validate: username must not be empty (Exercise 1 requirement)
    if (empty($username)) {
        echo "<script>alert('Username cannot be empty.'); window.location.href='index.html';</script>";
        exit;
    }

    $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
    if ($check->num_rows > 0) {
        echo "<script>alert('Username or email already taken.'); window.location.href='index.html';</script>";
    } else {
        $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Account created! You can now log in.'); window.location.href='index.html';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "'); window.location.href='index.html';</script>";
        }
    }
    $conn->close();
    exit;
}

// =============================================
//  LOGIN
//  Satisfies: Exercise 1 —
//  "Create an HttpSession after a successful login"
//  PHP $_SESSION = Java HttpSession
//
//  Satisfies: Exercise 2 requirement 1 —
//  "Store the logged-in user's name using HttpSession"
//
//  Satisfies: Exercise 2 requirement 2 —
//  "Create a cookie to remember the user's preferred theme"
//  (theme cookie is handled by JS; here we handle sc_remember)
//
//  Satisfies: Exercise 2 requirement 3 —
//  "Display the user's session ID and login time"
//  We store these in the sc_session_info cookie for JS to read
//
//  Satisfies: Assignment —
//  "Remember Me feature using cookies"
// =============================================
if (isset($_POST['login'])) {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = $_POST['password'];

    // Validate: username must not be empty
    if (empty($username)) {
        echo "<script>alert('Username cannot be empty.'); window.location.href='index.html';</script>";
        $conn->close();
        exit;
    }

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {

            // ── Create session (HttpSession equivalent) ──────────────
            // session_start() already called at top of file
            // This is the PHP equivalent of:
            //   HttpSession session = request.getSession();
            //   session.setAttribute("user", username);

            $_SESSION['user']       = $row['username'];
            $_SESSION['user_id']    = $row['id'];
            $_SESSION['login_time'] = date('d M Y, H:i:s');
            // session_id() = Java's session.getId()

            // ── sc_session_info cookie ────────────────────────────────
            // Stores username, login time, and session ID
            // JS reads this to populate the session bar
            // Satisfies: Exercise 2 requirement 3
            $sessionData = json_encode([
                'username'   => $row['username'],
                'login_time' => $_SESSION['login_time'],
                'session_id' => session_id(),   // equivalent to session.getId()
            ]);
            // Expires when browser closes (session cookie — no days set)
            setcookie('sc_session_info', $sessionData, 0, '/');

            // ── Remember Me cookie ────────────────────────────────────
            // Satisfies: Assignment requirement
            // If checkbox was ticked, save username for 30 days
            if (isset($_POST['remember_me'])) {
                setcookie('sc_remember', $row['username'], time() + (30 * 24 * 60 * 60), '/');
            } else {
                // If not ticked, clear any old remember cookie
                setcookie('sc_remember', '', time() - 3600, '/');
            }

            // ── Redirect back to homepage with welcome message ────────
            echo "<script>
                alert('Welcome back, " . htmlspecialchars($row['username'], ENT_QUOTES) . "! You are now logged in.');
                window.location.href='index.html';
            </script>";

        } else {
            echo "<script>alert('Incorrect password.'); window.location.href='index.html';</script>";
        }
    } else {
        echo "<script>alert('No account found with that username.'); window.location.href='index.html';</script>";
    }

    $conn->close();
    exit;
}

// =============================================
//  PLACE ORDER
// =============================================
if (isset($_POST['place_order'])) {
    $customer_name = $conn->real_escape_string(trim($_POST['customer_name']));
    $phone         = $conn->real_escape_string(trim($_POST['phone']));
    $address       = $conn->real_escape_string(trim($_POST['address']));
    $cake_name     = $conn->real_escape_string(trim($_POST['cake_name']));
    $cake_price    = floatval($_POST['cake_price']);
    $size          = $conn->real_escape_string($_POST['size']);
    $message       = $conn->real_escape_string(trim($_POST['message'] ?? ''));
    $mpesa_number  = $conn->real_escape_string(trim($_POST['mpesa_number'] ?? ''));
    $mpesa_code    = strtoupper($conn->real_escape_string(trim($_POST['mpesa_code'] ?? '')));

    if ($size === 'medium') $cake_price += 500;
    if ($size === 'large')  $cake_price += 1200;

    $sql = "INSERT INTO orders
                (customer_name, phone, address, cake_name, cake_price, size, message, mpesa_number, mpesa_code)
            VALUES
                ('$customer_name','$phone','$address','$cake_name','$cake_price','$size','$message','$mpesa_number','$mpesa_code')";

    if ($conn->query($sql) === TRUE) {
        // ── Session check: personalise the message if logged in ───────
        // Satisfies: Exercise 2 requirement 1 —
        // "Store the logged-in user's name using HttpSession"
        $greeting = isset($_SESSION['user'])
            ? 'Thank you, ' . htmlspecialchars($_SESSION['user'], ENT_QUOTES) . '!'
            : 'Thank you, ' . htmlspecialchars($customer_name, ENT_QUOTES) . '!';

        echo "<script>
            alert('" . $greeting . "\\nYour order has been placed.\\nWe will contact you on $phone shortly.');
            window.location.href='index.html';
        </script>";
    } else {
        echo "<script>alert('Error placing order: " . $conn->error . "'); window.location.href='index.html';</script>";
    }

    $conn->close();
    exit;
}

$conn->close();
?>