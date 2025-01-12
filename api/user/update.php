<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../config/db.php';

function validateInput($data) {
    $errors = [];

    if (empty($data["id"])) {
        $errors[] = "ID is required";
    } elseif (!is_numeric($data["id"])) {
        $errors[] = "ID must be a number";
    }

    if (empty($data["username"])) {
        $errors[] = "Username is required";
    }

    if (!empty($data["password"]) && strlen($data["password"]) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    if (empty($data["email"])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($data["first_name"])) {
        $errors[] = "First name is required";
    }

    if (empty($data["last_name"])) {
        $errors[] = "Last name is required";
    }

    if (empty($data["role"])) {
        $errors[] = "Role is required";
    }

    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate input
    $errors = validateInput($data);
    if (empty($errors)) {
        $id = $data["id"];
        $username = $data["username"];
        $email = $data["email"];
        $first_name = $data["first_name"];
        $last_name = $data["last_name"];
        $role = $data["role"];

        // Update the password only if provided
        if (!empty($data["password"])) {
            $password = password_hash($data["password"], PASSWORD_BCRYPT);
            $stmt = $conn->prepare('UPDATE users SET username = ?, password = ?, email = ?, first_name = ?, last_name = ?, role = ? WHERE id = ?');
            $stmt->bind_param('ssssssi', $username, $password, $email, $first_name, $last_name, $role, $id);
        } else {
            $stmt = $conn->prepare('UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ? WHERE id = ?');
            $stmt->bind_param('sssssi', $username, $email, $first_name, $last_name, $role, $id);
        }

        // Execute statement
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(["status" => "success", "message" => "User updated successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => "User not found or no changes made"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }

        // Close statement
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid input", "errors" => $errors]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}

// Close connection
$conn->close();
