<?php
// Include the database connection file
include 'dbh.php';
// baseUrl : https://mealmanagement556.000webhostapp.com

// Create meal table if not exists
$sql = "CREATE TABLE IF NOT EXISTS meal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    memberId INT NOT NULL,
    mealCount INT NOT NULL,
    mealDate DATE NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (memberId) REFERENCES member(id)
)";
$response = array();

if ($conn->query($sql) === TRUE) {
    // Return a JSON response for success
    $response['status'] = 200;
    $response['message'] = 'Meal table created successfully or already exists';
} else {
    http_response_code(400); // Set status code to 400
    // Return a JSON response for error
    $response['status'] = 400;
    $response['message'] = 'Error creating meal table: ' . $conn->error;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Retrieve all meal records
    if (isset($_GET['action']) && $_GET['action'] == 'getAllMeals') {
        $result = $conn->query("SELECT * FROM meal");
        $meals = array();

        while ($row = $result->fetch_assoc()) {
            $meals[] = $row;
        }

        $response['status'] = 200;
        $response['data'] = array('meals' => $meals);
    }
    // Retrieve meal record by id
    elseif (isset($_GET['action']) && $_GET['action'] == 'getMealById') {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $result = $conn->query("SELECT * FROM meal WHERE id = $id");

            if ($result->num_rows > 0) {
                $meal = $result->fetch_assoc();
                $response['status'] = 200;
                $response['data'] = $meal;
            } else {
                http_response_code(400); // Set status code to 400
                // Return a JSON response for error
                $response['status'] = 400;
                $response['message'] = 'Meal record not found';
            }
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Meal ID not provided';
        }
    }
    // Retrieve all meal records by memberId
    elseif (isset($_GET['action']) && $_GET['action'] == 'getAllMealsByMemberId') {
        if (isset($_GET['memberId'])) {
            $memberId = $_GET['memberId'];
            $result = $conn->query("SELECT * FROM meal WHERE memberId = $memberId");

            $meals = array();

            while ($row = $result->fetch_assoc()) {
                $meals[] = $row;
            }

            $response['status'] = 200;
            $response['data'] = array('meals' => $meals);
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Member ID not provided';
        }
    }
    // Retrieve all meal records by userId
    elseif (isset($_GET['action']) && $_GET['action'] == 'getAllMealsByUserId') {
        if (isset($_GET['userId'])) {
            $userId = $_GET['userId'];
            $result = $conn->query("SELECT m.* FROM meal m
                                    JOIN member mb ON m.memberId = mb.id
                                    WHERE mb.userId = $userId");

            $meals = array();

            while ($row = $result->fetch_assoc()) {
                $meals[] = $row;
            }

            $response['status'] = 200;
            $response['data'] = array('meals' => $meals);
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'User ID not provided';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new meal record
    if (isset($_GET['action']) && $_GET['action'] == 'createMeal') {
        $data = json_decode(file_get_contents('php://input'), true);

        $memberId = $data['memberId'];
        $mealCount = $data['mealCount'];
        $mealDate = $data['mealDate'];

        $sql = "INSERT INTO meal (memberId, mealCount, mealDate) 
                VALUES ($memberId, $mealCount, '$mealDate')";

        if ($conn->query($sql) === TRUE) {
            // Get the details of the newly created record
            $lastInsertedId = $conn->insert_id;
            $result = $conn->query("SELECT * FROM meal WHERE id = $lastInsertedId");

            if ($result->num_rows > 0) {
                $createdMeal = $result->fetch_assoc();
                // Return a JSON response for success with the details
                $response['status'] = 200;
                $response['message'] = 'Meal record created successfully';
                $response['data'] = $createdMeal;
            } else {
                http_response_code(400); // Set status code to 400
                // Return a JSON response for error
                $response['status'] = 400;
                $response['message'] = 'Error retrieving created meal record details';
            }
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Error creating meal record: ' . $conn->error;
        }
    }
    // Update meal record
    elseif (isset($_GET['action']) && $_GET['action'] == 'updateMeal') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id = $data['id'];
        $memberId = $data['memberId'];
        $mealCount = $data['mealCount'];
        $mealDate = $data['mealDate'];

        $sql = "UPDATE meal 
                SET memberId=$memberId, mealCount=$mealCount, mealDate='$mealDate'
                WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            $response['status'] = 200;
            $response['message'] = 'Meal record updated successfully';
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Error updating record: ' . $conn->error;
        }
    }
    // Delete meal record
    elseif (isset($_GET['action']) && $_GET['action'] == 'deleteMeal') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id = $data['id'];

        $sql = "DELETE FROM meal WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            $response['status'] = 200;
            $response['message'] = 'Meal record deleted successfully';
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
