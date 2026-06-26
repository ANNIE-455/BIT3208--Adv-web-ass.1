<?php
// =============================================
//  DB CONNECTION
// =============================================
$host    = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "cake_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// =============================================
//  AUTO-CREATE TABLES IF NOT EXISTS
//  (so you don't need to manually set up SQL)
// =============================================

// Users table
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id       INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email    VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Orders table
$conn->query("
    CREATE TABLE IF NOT EXISTS orders (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(150) NOT NULL,
        phone         VARCHAR(20)  NOT NULL,
        address       TEXT         NOT NULL,
        cake_name     VARCHAR(150) NOT NULL,
        cake_price    DECIMAL(10,2) NOT NULL,
        size          VARCHAR(20)  NOT NULL,
        message       TEXT,
        status        VARCHAR(50)  DEFAULT 'Pending',
        ordered_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    )
");

// =============================================
//  SIGN UP
// =============================================
if (isset($_POST['signup'])) {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $email    = $conn->real_escape_string(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username or email already exists
    $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
    if ($check->num_rows > 0) {
        echo "<script>alert('Username or email already taken. Please try another.'); window.location.href='index.html';</script>";
    } else {
        $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Account created successfully! You can now log in.'); window.location.href='index.html';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "'); window.location.href='index.html';</script>";
        }
    }
}

// =============================================
//  LOGIN
// =============================================
if (isset($_POST['login'])) {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = $_POST['password'];

    $sql    = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            echo "<script>alert('Welcome back, " . htmlspecialchars($row['username']) . "!'); window.location.href='index.html';</script>";
        } else {
            echo "<script>alert('Incorrect password. Please try again.'); window.location.href='index.html';</script>";
        }
    } else {
        echo "<script>alert('No account found with that username.'); window.location.href='index.html';</script>";
    }
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

    // Adjust price based on size
    if ($size === 'medium') {
        $cake_price += 500;
    } elseif ($size === 'large') {
        $cake_price += 1200;
    }

    $sql = "INSERT INTO orders (customer_name, phone, address, cake_name, cake_price, size, message)
            VALUES ('$customer_name', '$phone', '$address', '$cake_name', '$cake_price', '$size', '$message')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
            alert('Order placed successfully! Thank you, $customer_name. We will contact you shortly on $phone.');
            window.location.href='index.html';
        </script>";
    } else {
        echo "<script>alert('Sorry, there was an error placing your order: " . $conn->error . "'); window.location.href='index.html';</script>";
    }
}

$conn->close();
?>