<?php
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "cake_db"; // ⚠️ Double-check in phpMyAdmin that this matches exactly!

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['signup'])) {
    // Using object-oriented escaping to match your $conn syntax
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";

    if ($conn->query($sql) === TRUE) {
        // '../index.html' ensures it goes back to the root folder website
        echo "<script>alert('Account created successfully!'); window.location.href='../index.html';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            echo "<script>alert('Welcome back, " . $row['username'] . "!'); window.location.href='../index.html';</script>";
        } else {
            echo "<script>alert('Incorrect password!'); window.location.href='../index.html';</script>";
        }
    } else {
        echo "<script>alert('No user found with that username!'); window.location.href='../index.html';</script>";
    }
}
$conn->close();
?>
