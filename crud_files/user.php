<?php
// Include the database connection file
include 'dbh.php';
// baseUrl : https://mealmanagement556.000webhostapp.com

// Create user table if not exists
$sql = "CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userName VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$response = array();

if ($conn->query($sql) === TRUE) {
    // Return a JSON response for success
    $response['status'] = 200;
    $response['message'] = 'User table created successfully or already exists';
} else {
    http_response_code(400); // Set status code to 400
    // Return a JSON response for error
    $response['status'] = 400;
    $response['message'] = 'Error creating user table: ' . $conn->error;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Retrieve all user records
    if (isset($_GET['action']) && $_GET['action'] == 'getAllUsers') {
        $result = $conn->query("SELECT * FROM user");
        $users = array();

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        $response['data'] = array('users' => $users);
    }
    // Retrieve user record by id
    elseif (isset($_GET['action']) && $_GET['action'] == 'getUserById') {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $result = $conn->query("SELECT * FROM user WHERE id = $id");

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $response['data'] = $user;
            } else {
                http_response_code(400); // Set status code to 400
                // Return a JSON response for error
                $response['status'] = 400;
                $response['message'] = 'User record not found';
            }
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'User ID not provided';
        }
    }
    // Retrieve user record by email
    elseif (isset($_GET['action']) && $_GET['action'] == 'getUserByEmail') {
        if (isset($_GET['email'])) {
            $email = $_GET['email'];
            $result = $conn->query("SELECT * FROM user WHERE email = '$email'");

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $response['data'] = $user;
            } else {
                http_response_code(400); // Set status code to 400
                // Return a JSON response for error
                $response['status'] = 400;
                $response['message'] = 'Email not provided';
            }
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Email not provided';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new user record
    if (isset($_GET['action']) && $_GET['action'] == 'createUser') {
        $data = json_decode(file_get_contents('php://input'), true);

        $userName = $data['userName'];
        $email = $data['email'];

        $sql = "INSERT INTO user (userName, email) 
                VALUES ('$userName', '$email')";

        if ($conn->query($sql) === TRUE) {
            // Get the details of the newly created record
            $lastInsertedId = $conn->insert_id;
            $result = $conn->query("SELECT * FROM user WHERE id = $lastInsertedId");

            if ($result->num_rows > 0) {
                $createdUser = $result->fetch_assoc();
                // Return a JSON response for success with the details
                $response['status'] = 200;
                $response['message'] = 'User record created successfully';
                $response['data'] = $createdUser;
            } else {
                http_response_code(400); // Set status code to 400
                // Return a JSON response for error
                $response['status'] = 400;
                $response['message'] = 'Error retrieving created user record details';
            }
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Error creating user record: ' . $conn->error;
        }
    }
    // Update user record
    elseif (isset($_GET['action']) && $_GET['action'] == 'updateUser') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id = $data['id'];
        $userName = $data['userName'];
        $email = $data['email'];

        $sql = "UPDATE user 
                SET userName='$userName', email='$email'
                WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            // Return a JSON response for success
            $response['status'] = 200;
            $response['message'] = 'User record updated successfully';
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Error updating record: ' . $conn->error;
        }
    }
    // Delete user record
    elseif (isset($_GET['action']) && $_GET['action'] == 'deleteUser') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id = $data['id'];

        $sql = "DELETE FROM user WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            // Return a JSON response for success
            $response['status'] = 200;
            $response['message'] = 'User record deleted successfully';
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Error deleting record: ' . $conn->error;
        }
    }
}

echo json_encode($response);

$conn->close();
?>
