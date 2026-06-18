<?php
// 1. Define configuration settings
$host = "localhost";
$user = "root";
$password = "";
$dbname = "studentdb";

echo "<h2>Testing Database Connection...</h2>";

// 2. Attempt to connect to MySQL using mysqli_connect
$conn = mysqli_connect($host, $user, $password, $dbname);

// 3. Evaluate the connection state
if (!$conn) {
    // If connection failed, stop execution and print the specific error
    die("<b style='color: red;'>Connection Failed!</b><br> Error Details: " . mysqli_connect_error());
}

// If it didn't fail, show this success message
echo "<b style='color: green;'>Connected Successfully!</b> Your PHP script is talking to the MySQL database.";

// 4. Close the connection
mysqli_close($conn);
?>