<?php
// Disable error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once 'dbconnection.php';

// Helper function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Helper function to validate phone
function isValidPhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

// Helper function to send response
function sendResponse($status, $message, $data = null) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON input
        $jsonInput = file_get_contents('php://input');
        if (!$jsonInput) {
            sendResponse('error', 'No data received');
        }

        $data = json_decode($jsonInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendResponse('error', 'Invalid JSON format');
        }

        $action = isset($_GET['action']) ? $_GET['action'] : '';

        if ($action === 'signup') {
            // Validate required fields
            $requiredFields = ['user_type', 'full_name', 'username', 'email', 'password'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    sendResponse('error', ucfirst($field) . ' is required');
                }
            }

            // Validate email
            if (!isValidEmail($data['email'])) {
                sendResponse('error', 'Invalid email format');
            }

            // Validate phone if provided
            if (!empty($data['phone']) && !isValidPhone($data['phone'])) {
                sendResponse('error', 'Invalid phone number format');
            }

            try {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT email FROM user WHERE email = ?");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $stmt->bind_param("s", $data['email']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    sendResponse('error', 'Email already exists');
                }

                // Check if username already exists
                $stmt = $conn->prepare("SELECT username FROM user WHERE username = ?");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $stmt->bind_param("s", $data['username']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    sendResponse('error', 'Username already exists');
                }

                // Hash password
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                // Insert user
                $stmt = $conn->prepare("INSERT INTO user (user_type, full_name, username, email, password, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                
                // Set default values for optional fields
                $phone = isset($data['phone']) ? $data['phone'] : null;
                $address = isset($data['address']) ? $data['address'] : null;

                $stmt->bind_param("sssssss", 
                    $data['user_type'],
                    $data['full_name'],
                    $data['username'],
                    $data['email'],
                    $hashedPassword,
                    $phone,
                    $address
                );

                if ($stmt->execute()) {
                    sendResponse('success', 'User registered successfully', [
                        'user_id' => $stmt->insert_id,
                        'email' => $data['email'],
                        'username' => $data['username']
                    ]);
                } else {
                    throw new Exception($stmt->error);
                }
            } catch (Exception $e) {
                sendResponse('error', 'Database error: ' . $e->getMessage());
            }
        }
        elseif ($action === 'login') {
            // Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                sendResponse('error', 'Email and password are required');
            }
        
            try {
                // Get user by email
                $stmt = $conn->prepare("SELECT user_id, user_type, full_name, username, email, password FROM user WHERE email = ?");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $stmt->bind_param("s", $data['email']);
                $stmt->execute();
                $result = $stmt->get_result();
        
                if ($result->num_rows === 0) {
                    sendResponse('error', 'Invalid email or password');
                }
        
                $user = $result->fetch_assoc();
        
                // Verify password
                if (!password_verify($data['password'], $user['password'])) {
                    sendResponse('error', 'Invalid email or password');
                }
        
                // Start session and store user data
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['logged_in'] = true;
        
                // Return user data (excluding password)
                unset($user['password']);
                $user['login_time'] = date('Y-m-d H:i:s');
                
                sendResponse('success', 'Login successful', $user);
            } catch (Exception $e) {
                sendResponse('error', 'Database error: ' . $e->getMessage());
            }
        }
        else {
            sendResponse('error', 'Invalid action');
        }
    } catch (Exception $e) {
        sendResponse('error', 'Server error: ' . $e->getMessage());
    }
} else {
    sendResponse('error', 'Invalid request method');
}
?>