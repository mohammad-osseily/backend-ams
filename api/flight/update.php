<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($data->id)) {
    $id = $data->id;
    $flight_number = $data->flight_number;
    $departure_airport = $data->departure_airport;
    $arrival_airport = $data->arrival_airport;
    $departure_time = $data->departure_time;
    $arrival_time = $data->arrival_time;
    $capacity = $data->capacity;
    $price = $data->price;

    if ($departure_airport == $arrival_airport) {
        echo json_encode(["status" => "error", "message" => "Departure and arrival airports cannot be the same"]);
        exit;
    }

    if (strtotime($departure_time) >= strtotime($arrival_time)) {
        echo json_encode(["status" => "error", "message" => "Departure time must be before arrival time"]);
        exit;
    }

    if (strtotime($departure_time) < time()) {
        echo json_encode(["status" => "error", "message" => "Departure time must be in the future"]);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9]{1,10}$/', $flight_number)) {
        echo json_encode(["status" => "error", "message" => "Invalid flight number format"]);
        exit;
    }

     if (!filter_var($capacity, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        echo json_encode(["status" => "error", "message" => "Invalid capacity value"]);
        exit;
    }

    if (!filter_var($price, FILTER_VALIDATE_FLOAT) || $price <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid price value"]);
        exit;
    }

    $airport_check_sql = "SELECT id FROM airports WHERE id IN (?, ?)";
    $stmt = $conn->prepare($airport_check_sql);
    $stmt->bind_param('ii', $departure_airport, $arrival_airport);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 2) {
        echo json_encode(["status" => "error", "message" => "One or both airport IDs are invalid"]);
        exit;
    }

    $sql = "
        UPDATE flights
        SET
            flight_number = ?,
            departure_airport_id = ?,
            arrival_airport_id = ?,
            departure_time = ?,
            arrival_time = ?,
            capacity = ?,
            price = ?
        WHERE
            id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('siissiis', $flight_number, $departure_airport, $arrival_airport, $departure_time, $arrival_time, $capacity, $price, $id);

    try {
        $stmt->execute();
        echo json_encode(["status" => "success", "message" => "Flight updated"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Failed to update flight: " . $stmt->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method or missing id"]);
}

$conn->close();
?>
