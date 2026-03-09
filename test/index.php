<?php

$host = "127.0.0.1";
$user = "papertrade_trade";
$password = "your_password";
$database = "papertrade_trade";
$port = 3306;

$conn = @mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {

    $error_no = mysqli_connect_errno();
    $error_msg = mysqli_connect_error();

    echo "Database connection failed<br>";
    echo "Error Code: " . $error_no . "<br>";
    echo "Error Message: " . $error_msg . "<br><br>";

    switch ($error_no) {

        case 1045:
            echo "Problem: Invalid MySQL username or password.";
            break;

        case 1049:
            echo "Problem: Database does not exist.";
            break;

        case 2002:
            echo "Problem: Cannot connect to MySQL server (server down or wrong host/port).";
            break;

        case 2006:
            echo "Problem: MySQL server has gone away.";
            break;

        default:
            echo "Unknown database error occurred.";
    }

} else {

    echo "Database connected successfully<br>";

    // Check connection health
    if (mysqli_ping($conn)) {
        echo "Connection is alive.<br>";
    } else {
        echo "Connection lost.<br>";
    }

    // Check query ability
    $test = mysqli_query($conn, "SELECT 1");

    if ($test) {
        echo "Query execution working.";
    } else {
        echo "Query execution failed.";
    }
}

?>