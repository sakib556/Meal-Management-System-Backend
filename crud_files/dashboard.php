<?php
// Include the database connection file
include 'dbh.php';
// baseUrl : https://mealmanagement556.000webhostapp.com

// Helper function to calculate date range condition
function getDateRangeCondition($startDate, $endDate, $dateColumnName)
{
    return "`$dateColumnName` BETWEEN '$startDate' AND '$endDate'";
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Retrieve dashboard information by date range
    if (isset($_GET['action']) && $_GET['action'] == 'getDashboardInfo') {
        if (isset($_GET['startDate']) && isset($_GET['endDate']) && isset($_GET['userId'])) {
            $startDate = $_GET['startDate'];
            $endDate = $_GET['endDate'];
            $userId = $_GET['userId'];

            // Get the date range condition for cost table
            $costDateRangeCondition = getDateRangeCondition($startDate, $endDate, 'costDate');

            // Get the date range condition for meal table
            $mealDateRangeCondition = getDateRangeCondition($startDate, $endDate, 'mealDate');

            try {
                // (1) Total Member by the user
                $totalMembersResult = $conn->query("SELECT COUNT(*) AS totalMembers FROM member WHERE userId = $userId");
                $totalMembers = $totalMembersResult->fetch_assoc()['totalMembers'];

                // (2) Total BazarCost with costType == Bazar
                $totalBazarCostResult = $conn->query("SELECT SUM(amount) AS totalBazarCost FROM cost WHERE costType = 'Bazar' AND $costDateRangeCondition AND memberId IN (SELECT id FROM member WHERE userId = $userId)");
                $totalBazarCost = $totalBazarCostResult->fetch_assoc()['totalBazarCost'];
                $totalBazarCost = ($totalBazarCost > 0) ? $totalBazarCost : 0;
                // (3) Total UtilityCost with costType != Bazar
                $totalUtilityCostResult = $conn->query("SELECT SUM(amount) AS totalUtilityCost FROM cost WHERE costType != 'Bazar' AND $costDateRangeCondition AND memberId IN (SELECT id FROM member WHERE userId = $userId)");
                $totalUtilityCost = $totalUtilityCostResult->fetch_assoc()['totalUtilityCost'];
                $totalUtilityCost = ($totalUtilityCost > 0) ? $totalUtilityCost : 0;

                // (4) Total Meals
                $totalMealsResult = $conn->query("SELECT SUM(mealCount) AS totalMeals FROM meal WHERE $mealDateRangeCondition AND memberId IN (SELECT id FROM member WHERE userId = $userId)");
                $totalMeals = $totalMealsResult->fetch_assoc()['totalMeals'];
                $totalMeals = ($totalMeals > 0) ? $totalMeals : 0; // Ensure totalMeals is not zero                
                // (5) Cost Per Meal
                $costPerMeal = ($totalMembers != 0 && $totalBazarCost!=0 && $totalMeals!=0)
                    ? $totalBazarCost / $totalMeals 
                    : $totalBazarCost;

                // (6) Utility Cost Per Member
                $utilityCostPerMember = ($totalMembers > 0 && $totalUtilityCost!=0)
                    ? $totalUtilityCost / $totalMembers
                    : $totalUtilityCost;

                // (7) All Member Details
                $allMemberDetailsResult = $conn->query("
                  SELECT m.id,   m.memberName, 
        m.gender,
        COALESCE(SUM(bazarCost.amount), 0) AS memberTotalBazarCost,
        COALESCE(SUM(utilityCost.amount), 0) AS memberTotalUtilityCost,
        COALESCE(SUM(me.mealCount), 0) AS memberTotalMeal 
    FROM member m
    LEFT JOIN (
        SELECT memberId, SUM(amount) AS amount
        FROM cost
        WHERE costType = 'Bazar' AND $costDateRangeCondition
        GROUP BY memberId
    ) bazarCost ON m.id = bazarCost.memberId
    LEFT JOIN (
        SELECT memberId, SUM(amount) AS amount
        FROM cost
        WHERE costType != 'Bazar' AND $costDateRangeCondition
        GROUP BY memberId
    ) utilityCost ON m.id = utilityCost.memberId
    LEFT JOIN (
        SELECT memberId, SUM(mealCount) AS mealCount
        FROM meal
        WHERE $mealDateRangeCondition
        GROUP BY memberId
    ) me ON m.id = me.memberId
    WHERE m.userId = $userId
    GROUP BY m.id, m.memberName, m.gender
");



                $allMemberDetails = array();

                while ($row = $allMemberDetailsResult->fetch_assoc()) {
                    // (8) Member Meal Cost
                    $mealCount = $row['memberTotalMeal'];
                    //  echo "mealCount: $mealCount<br>";
                    $memberMealCost = $mealCount * $costPerMeal;

                    // (9) Member Total Deposit
                    $memberTotalDeposit = $row['memberTotalBazarCost'] + $row['memberTotalUtilityCost'];

                    // (10) Member Account
                    $memberAccount = $memberTotalDeposit - ($memberMealCost + $utilityCostPerMember);

                    // Add calculated values to the row
                    $row['memberMealCost'] = $memberMealCost;
                    $row['memberTotalDeposit'] = $memberTotalDeposit;
                    $row['memberAccount'] = $memberAccount;

                    $allMemberDetails[] = $row;
                }

                // Prepare and send the JSON response
                $response = array(
                    'status' => 200,
                    'data' => array(
                        'totalMembers' => $totalMembers,
                        'totalBazarCost' => $totalBazarCost,
                        'totalUtilityCost' => $totalUtilityCost,
                        'totalMeals' => $totalMeals,
                        'costPerMeal' => $costPerMeal,
                        'utilityCostPerMember' => $utilityCostPerMember,
                        'allMemberDetails' => $allMemberDetails
                    ),
                    'message' => 'Dashboard information retrieved successfully'
                );

                echo json_encode($response);
            } catch (Exception $e) {
                http_response_code(500); // Internal Server Error
                // Return a JSON response for error
                $response = array(
                    'status' => 500,
                    'message' => 'Error: ' . $e->getMessage()
                );
                echo json_encode($response);
            }
        } else {
            http_response_code(400); // Set status code to 400
            // Return a JSON response for error
            $response = array(
                'status' => 400,
                'message' => 'Invalid parameters provided'
            );
            echo json_encode($response);
        }
    }
}

$conn->close();
