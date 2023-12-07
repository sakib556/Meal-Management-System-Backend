<?php
// Include the database connection file
include 'dbh.php';
// baseUrl : https://mealmanagement556.000webhostapp.com

// Create cost table if not exists
$sql = "CREATE TABLE IF NOT EXISTS cost (
    id INT AUTO_INCREMENT PRIMARY KEY,
    memberId INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    costType VARCHAR(255) NOT NULL,
    details TEXT,
    costDate DATE NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (memberId) REFERENCES member(id)
)";

$response = array();

if ($conn->query($sql) === TRUE) {
    // Return a JSON response for success
    $response['status'] = 200;
    $response['message'] = 'Cost table created successfully or already exists';
} else {
    http_response_code(400); // Set status code to 400
    // Return a JSON response for error
    $response['status'] = 400;
    $response['message'] = 'Error creating cost table: ' . $conn->error;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Retrieve all cost records
    if (isset($_GET['action']) && $_GET['action'] == 'getAllCosts') {
        $result = $conn->query("SELECT * FROM cost");
        $costs = array();

        while ($row = $result->fetch_assoc()) {
            $costs[] = $row;
        }

        $response['status'] = 200;
        $response['data'] = array('costs' => $costs);
    }
    // Retrieve cost record by id
    elseif (isset($_GET['action']) && $_GET['action'] == 'getCostById') {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $result = $conn->query("SELECT * FROM cost WHERE id = $id");

            if ($result->num_rows > 0) {
                $cost = $result->fetch_assoc();
                $response['status'] = 200;
                $response['data'] = $cost;
            } else {
                http_response_code(400); // Set status code to 400
                // Return a JSON response for error
                $response['status'] = 400;
                $response['message'] = 'Cost record not found';
            }
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Cost ID not provided';
        }
    }
    // Retrieve all cost records by memberId
    elseif (isset($_GET['action']) && $_GET['action'] == 'getAllCostsByMemberId') {
        if (isset($_GET['memberId'])) {
            $memberId = $_GET['memberId'];
            $result = $conn->query("SELECT * FROM cost WHERE memberId = $memberId");

            $costs = array();

            while ($row = $result->fetch_assoc()) {
                $costs[] = $row;
            }

            $response['status'] = 200;
            $response['data'] = array('costs' => $costs);
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Member ID not provided';
        }
    }
    // Retrieve all cost records by userId
    elseif (isset($_GET['action']) && $_GET['action'] == 'getAllCostsByUserId') {
        if (isset($_GET['userId'])) {
            $userId = $_GET['userId'];
            $result = $conn->query("SELECT c.* FROM cost c
                                    JOIN member m ON c.memberId = m.id
                                    WHERE m.userId = $userId");

            $costs = array();

            while ($row = $result->fetch_assoc()) {
                $costs[] = $row;
            }

            $response['status'] = 200;
            $response['data'] = array('costs' => $costs);
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'User ID not provided';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new cost record
    if ($_GET['action'] == 'createCost') {
        $data = json_decode(file_get_contents('php://input'), true);

        $memberId = $data['memberId'];
        $amount = $data['amount'];
        $costType = $data['costType'];
        $details = $data['details'];
        $costDate = $data['costDate'];

        $sql = "INSERT INTO cost (memberId, amount, costType, details, costDate) 
                VALUES ($memberId, $amount, '$costType', '$details', '$costDate')";

        if ($conn->query($sql) === TRUE) {
            // Get the details of the newly created record
            $lastInsertedId = $conn->insert_id;
            $result = $conn->query("SELECT * FROM cost WHERE id = $lastInsertedId");

            if ($result->num_rows > 0) {
                $createdCost = $result->fetch_assoc();
                // Return a JSON response for success with the details
                $response['status'] = 200;
                $response['message'] = 'Cost record created successfully';
                $response['data'] = $createdCost;
            } else {
                http_response_code(400); // Set status code to 400
                // Return a JSON response for error
                $response['status'] = 400;
                $response['message'] = 'Error retrieving created cost record details';
            }
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Error creating cost record: ' . $conn->error;
        }
    }

    // Update cost record
    elseif (isset($_GET['action']) && $_GET['action'] == 'updateCost') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id = $data['id'];
        $memberId = $data['memberId'];
        $amount = $data['amount'];
        $costType = $data['costType'];
        $details = $data['details'];
        $costDate = $data['costDate'];

        $sql = "UPDATE cost 
                SET memberId=$memberId, amount=$amount, costType='$costType', details='$details', costDate='$costDate'
                WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            $response['status'] = 200;
            $response['message'] = 'Cost record updated successfully';
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Error updating record: ' . $conn->error;
        }
    }
    // Delete cost record
    elseif (isset($_GET['action']) && $_GET['action'] == 'deleteCost') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id = $data['id'];

        $sql = "DELETE FROM cost WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            $response['status'] = 200;
            $response['message'] = 'Cost record deleted successfully';
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
