<?php
// Include the database connection file
include 'dbh.php';
// baseUrl : https://mealmanagement556.000webhostapp.com
// Create member table if not exists
$sql = "CREATE TABLE IF NOT EXISTS member (
    id INT AUTO_INCREMENT PRIMARY KEY,
    memberName VARCHAR(255) NOT NULL,
    gender VARCHAR(255) NOT NULL,
    userId INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES user(id)
)";
$response = array();

if ($conn->query($sql) === TRUE) {
    // Return a JSON response for success
    $response['status'] = 200;
    $response['message'] = 'Member table created successfully or already exists';
} else {
    http_response_code(400); // Set status code to 400
    // Return a JSON response for error
    $response['status'] = 400;
    $response['message'] = 'Error creating member table: ' . $conn->error;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Retrieve all member records
    if (isset($_GET['action']) && $_GET['action'] == 'getAllMembers') {
        $result = $conn->query("SELECT * FROM member");
        $members = array();

        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }

        $response['status'] = 200;
        $response['data'] = array('members' => $members);
    }
    // Retrieve member record by id
    elseif (isset($_GET['action']) && $_GET['action'] == 'getMemberById') {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $result = $conn->query("SELECT * FROM member WHERE id = $id");

            if ($result->num_rows > 0) {
                $member = $result->fetch_assoc();
                $response['status'] = 200;
                $response['data'] = $member;
            } else {
                http_response_code(400); // Set status code to 400
                // Return a JSON response for error
                $response['status'] = 400;
                $response['message'] = 'Member record not found';
            }
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Member ID not provided';
        }
    }
    // Retrieve all member records by userId
    elseif (isset($_GET['action']) && $_GET['action'] == 'getAllMembersByUserId') {
        if (isset($_GET['userId'])) {
            $userId = $_GET['userId'];
            $result = $conn->query("SELECT * FROM member WHERE userId = $userId");

            $members = array();

            while ($row = $result->fetch_assoc()) {
                $members[] = $row;
            }

            $response['status'] = 200;
            $response['data'] = array('members' => $members);
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'User ID not provided';
        }
    }
    //https://mealmanagement556.000webhostapp.com/member.php?action=getMealAndCostByMemberIdAndDateRange&memberId=1&startDate=2023-01-01&endDate=2023-12-31

    elseif (isset($_GET['action']) && $_GET['action'] == 'getMealAndCostByMemberIdAndDateRange') {
        if (isset($_GET['memberId']) && isset($_GET['startDate']) && isset($_GET['endDate'])) {
            $memberId = $_GET['memberId'];
            $startDate = $_GET['startDate'];
            $endDate = $_GET['endDate'];

            // Retrieve cost records within the date range
            $costResult = $conn->query("SELECT * FROM cost WHERE memberId = $memberId AND costDate BETWEEN '$startDate' AND '$endDate'");
            $costs = array();

            while ($costRow = $costResult->fetch_assoc()) {
                $costs[] = $costRow;
            }

            // Retrieve meal records within the date range
            $mealResult = $conn->query("SELECT * FROM meal WHERE memberId = $memberId AND mealDate BETWEEN '$startDate' AND '$endDate'");
            $meals = array();

            while ($mealRow = $mealResult->fetch_assoc()) {
                $meals[] = $mealRow;
            }

            $response['status'] = 200;
            $response['data'] = array('costs' => $costs, 'meals' => $meals);
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Member ID, startDate, or endDate not provided';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new member record
    if (isset($_GET['action']) && $_GET['action'] == 'createMember') {
        $data = json_decode(file_get_contents('php://input'), true);

        $memberName = $data['memberName'];
        $gender = $data['gender'];
        $userId = $data['userId'];

        $sql = "INSERT INTO member (memberName, gender, userId) 
                VALUES ('$memberName', '$gender', $userId)";

        if ($conn->query($sql) === TRUE) {
            // Get the details of the newly created record
            $lastInsertedId = $conn->insert_id;
            $result = $conn->query("SELECT * FROM member WHERE id = $lastInsertedId");

            if ($result->num_rows > 0) {
                $createdMember = $result->fetch_assoc();
                // Return a JSON response for success with the details
                $response['status'] = 200;
                $response['message'] = 'Member record created successfully';
                $response['data'] = $createdMember;
            } else {
                http_response_code(400); // Set status code to 400
                // Return a JSON response for error
                $response['status'] = 400;
                $response['message'] = 'Error retrieving created member record details';
            }
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Error creating member record: ' . $conn->error;
        }
    }
    // Update member record
    elseif (isset($_GET['action']) && $_GET['action'] == 'updateMember') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id = $data['id'];
        $memberName = $data['memberName'];
        $gender = $data['gender'];
        $userId = $data['userId'];

        $sql = "UPDATE member 
                SET memberName='$memberName', gender='$gender', userId=$userId
                WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            $response['status'] = 200;
            $response['message'] = 'Member record updated successfully';
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response['status'] = 400;
            $response['message'] = 'Error updating record: ' . $conn->error;
        }
    }
    // Delete member record
    elseif (isset($_GET['action']) && $_GET['action'] == 'deleteMember') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id = $data['id'];

        $sql = "DELETE FROM member WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            $response['status'] = 200;
            $response['message'] = 'Member record deleted successfully';
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
