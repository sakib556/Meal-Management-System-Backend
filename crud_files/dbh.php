<?php
$servername = "localhost";
$database = "id21447294_mealmanagement";
$username = "id21447294_sakib556";
$password = "Sakib@1223#";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected to the database successfully";
}

?>