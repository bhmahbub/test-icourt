<?php
session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==================== DATABASE CONFIGURATION ====================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'icourt');

// Role constants
define('ROLE_ADMINISTRATOR', 'administrator');
define('ROLE_MASTER_USER', 'master_user');
define('ROLE_USER', 'user');

// Initialize variables
$error = '';
$message = '';
$toast = null;
$logged_in = isset($_SESSION['user_id']);

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ==================== TABLE SETUP & FUNCTIONS ====================
// Function to create tables for master user
function createMasterUserTables($conn, $master_user_id) {
    $thana_table = "thana_" . $master_user_id;
    $cases_table = "cases_" . $master_user_id;
    
    // Create thana table for master user
    $create_thana_table = "CREATE TABLE IF NOT EXISTS `$thana_table` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        district VARCHAR(50) NOT NULL,
        thana_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Create cases table for master user
    $create_cases_table = "CREATE TABLE IF NOT EXISTS `$cases_table` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        case_number VARCHAR(50) UNIQUE NOT NULL,
        case_type VARCHAR(100) NOT NULL,
        plaintiff VARCHAR(100) NOT NULL,
        defendant VARCHAR(100) NOT NULL,
        filing_date DATE NOT NULL,
        status ENUM('pending', 'hearing', 'decided', 'dismissed') DEFAULT 'pending',
        thana_id INT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_thana_table) && $conn->query($create_cases_table)) {
        return true;
    }
    return false;
}

// Handle AJAX request for districts
if (isset($_GET['action']) && $_GET['action'] == 'get_districts' && isset($_GET['division'])) {
    $division = $conn->real_escape_string($_GET['division']);
    $districts = [];
    
    $stmt = $conn->prepare("SELECT DISTINCT district FROM thanas_master WHERE division = ? ORDER BY district");
    $stmt->bind_param("s", $division);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row['district'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($districts);
    exit;
}

// ==================== USER AUTHENTICATION ====================
// Handle login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = TRUE");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($_POST['password'], $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            
            // Redirect based on role
            if ($user['role'] === ROLE_ADMINISTRATOR) {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] === ROLE_MASTER_USER) {
                header("Location: master_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Account not found or deactivated!";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    $message = "You have been logged out successfully!";
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle user creation with role-based permissions
if (isset($_POST['create_user']) && isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    $current_user_role = $_SESSION['role'];
    
    // Check permissions based on role
    $allowed_roles = [];
    if ($current_user_role === ROLE_ADMINISTRATOR) {
        $allowed_roles = [ROLE_ADMINISTRATOR, ROLE_MASTER_USER, ROLE_USER];
    } elseif ($current_user_role === ROLE_MASTER_USER) {
        $allowed_roles = [ROLE_USER];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Permission denied!'];
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    $requested_role = htmlspecialchars(trim($_POST['role']));
    if (!in_array($requested_role, $allowed_roles)) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid role assignment!'];
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    $name = htmlspecialchars(trim($_POST['name']));
    $username = htmlspecialchars(trim($_POST['username']));
    
    // Check for duplicate username
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Username already exists!'];
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    // Password validation
    if (strlen($_POST['password']) < 8) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Password must be at least 8 characters!'];
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $division = htmlspecialchars(trim($_POST['division']));
    $district = htmlspecialchars(trim($_POST['district']));
    $court_name = htmlspecialchars(trim($_POST['court_name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $mobile_no = htmlspecialchars(trim($_POST['mobile_no']));
    
    // Set master_user_id for users created by master users
    $master_user_id = null;
    if ($current_user_role === ROLE_MASTER_USER) {
        $master_user_id = $current_user_id;
    }
    
    $stmt = $conn->prepare("INSERT INTO users (name, username, password, division, district, court_name, email, mobile_no, role, master_user_id, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssii", $name, $username, $password, $division, $district, $court_name, $email, $mobile_no, $requested_role, $master_user_id, $current_user_id);
    
    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        
        // Create tables if user is master_user
        if ($requested_role === ROLE_MASTER_USER) {
            if (createMasterUserTables($conn, $new_user_id)) {
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Master user created with dedicated tables!'];
            } else {
                $_SESSION['toast'] = ['type' => 'error', 'message' => 'User created but table creation failed!'];
            }
        } else {
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'User created successfully!'];
        }
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error creating user: ' . $conn->error];
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle user deactivation
if (isset($_POST['deactivate_user']) && isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] !== ROLE_ADMINISTRATOR) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Only administrators can deactivate users!'];
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    $user_id = intval($_POST['user_id']);
    
    // Prevent self-deactivation
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Cannot deactivate your own account!'];
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'User deactivated successfully!'];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error deactivating user!'];
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit;
}

// Handle user activation
if (isset($_POST['activate_user']) && isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] !== ROLE_ADMINISTRATOR) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Only administrators can activate users!'];
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    $user_id = intval($_POST['user_id']);
    
    $stmt = $conn->prepare("UPDATE users SET is_active = TRUE WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'User activated successfully!'];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error activating user!'];
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit;
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'New passwords do not match!'];
    } elseif (strlen($new_password) < 8) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Password must be at least 8 characters!'];
    } else {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($current_password, $user['password'])) {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    // Regenerate session after password change
                    session_regenerate_id(true);
                    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Password changed successfully!'];
                } else {
                    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error updating password: ' . $conn->error];
                }
                $update_stmt->close();
            } else {
                $_SESSION['toast'] = ['type' => 'error', 'message' => 'Current password is incorrect!'];
            }
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'User not found!'];
        }
        $stmt->close();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Fetch users based on role permissions
$users = [];
if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    $current_user_role = $_SESSION['role'];
    
    if ($current_user_role === ROLE_ADMINISTRATOR) {
        $sql = "SELECT u.*, creator.name as created_by_name 
                FROM users u 
                LEFT JOIN users creator ON u.created_by = creator.id 
                ORDER BY u.created_at DESC";
        $result = $conn->query($sql);
    } elseif ($current_user_role === ROLE_MASTER_USER) {
        $sql = "SELECT u.*, creator.name as created_by_name 
                FROM users u 
                LEFT JOIN users creator ON u.created_by = creator.id 
                WHERE u.master_user_id = ? OR u.id = ?
                ORDER BY u.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $current_user_id, $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Regular users can only see their own profile
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    if (isset($result) && $result->num_rows > 0) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Fetch divisions for dropdowns
$divisions = [];
$sql = "SELECT DISTINCT division FROM thanas_master ORDER BY division";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $divisions[] = $row['division'];
    }
}

// Process toast messages
if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    unset($_SESSION['toast']);
} else {
    $toast = null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= ($logged_in) ? 'Dashboard' : 'Login' ?> - iCourt
    </title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='white' stroke='%23198754' stroke-width='2'/%3E%3Cg transform='translate(32,32) scale(-1,1) translate(-32,-32) translate(8,8) scale(0.075,0.075)'%3E%3Cpath fill='%23198754' d='M201.6 217.4L182.9 198.7C170.4 186.2 170.4 165.9 182.9 153.4L297.6 38.6C310.1 26.1 330.4 26.1 342.9 38.6L361.6 57.4C374.1 69.9 374.1 90.2 361.6 102.7L246.9 217.4C234.4 229.9 214.1 229.9 201.6 217.4zM308 275.7L276.6 244.3L388.6 132.3L508 251.7L396 363.7L364.6 332.3L132.6 564.3C117 579.9 91.7 579.9 76 564.3C60.3 548.7 60.4 523.4 76 507.7L308 275.7zM422.9 438.6C410.4 426.1 410.4 405.8 422.9 393.3L537.6 278.6C550.1 266.1 570.4 266.1 582.9 278.6L601.6 297.3C614.1 309.8 614.1 330.1 601.6 342.6L486.9 457.4C474.4 469.9 454.1 469.9 441.6 457.4L422.9 438.7z'/%3E%3C/g%3E%3C/svg%3E"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        .dropdown-toggle::after {
            display: none;
        }

        :root {
            --primary: #198754;
            --primary-dark: #09492b;
            --primary-light: #4caf50;
            --secondary: #f57f17;
            --light: #f1f8e9;
            --dark: #1a237e;
            --text: #212121;
            --text-light: #757575;
            --white: #ffffff;
            --gray: #e0e0e0;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background: var(--light);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Welcome banner */
        .welcome-banner {
            background: var(--primary);
            color: white;
            border-radius: 10px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        #welcomeBanner {
            opacity: 1;
            transition: opacity 0.5s ease-out;
            /* Smooth fade transition */
        }

        .welcome-banner::before,
        .welcome-banner::after {
            content: "";
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-banner::before {
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
        }

        .welcome-banner::after {
            bottom: -30px;
            right: 10%;
            width: 100px;
            height: 100px;
        }

        .welcome-banner h1 {
            font-size: 28px;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }

        .welcome-banner p {
            font-size: 16px;
            opacity: 0.9;
            max-width: 600px;
            line-height: 1.5;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            background: var(--white);
            border-radius: 0.5rem;
            padding: 1.25rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--light);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            color: var(--primary);
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 500;
        }

        /* Table styles */
        .table-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            padding: 1.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        .table-details {
            background: var(--light);
            border-radius: 0.5rem;
            padding: 1.25rem;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .table {
            color: var(--text);
            overflow: hidden;
        }

        .table th {
            background: var(--primary);
            color: var(--white);
            font-weight: 600;
            text-align: center;
        }

        .table td {
            vertical-align: middle;
            text-align: center;
        }

        /* Search styles */
        .search-wrapper {
            position: relative;
            max-width: 100%;
        }

        .search-input-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 0.5rem 0.5rem 2.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        }

        .search-icon-container {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            align-items: center;
            padding-left: 0.75rem;
        }

        .search-icon {
            width: 1rem;
            height: 1rem;
            color: #9ca3af;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-yes {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-no {
            background-color: #dcfce7;
            color: #166534;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            color: #4b5563;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .edit-btn:hover {
            background: #eff6ff;
            color: var(--primary-dark);
            border-color: #dbeafe;
        }

        .delete-btn:hover {
            background: #fef2f2;
            color: #ef4444;
            border-color: #fee2e2;
        }

        .login-screen {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--light);
        }

        .login-container {
            max-width: 1000px;
            width: 100%;
        }

        .login-card {
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .brand-section {
            background:  var(--primary-dark);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2.5rem 1.25rem;
            text-align: center;
            color: var(--white);
        }

        .logo-icon-login {
            background: var(--white);
            border: var(--primary) 2px solid;
            border-radius: 50%;
            color: var(--primary);
            padding: 0.5rem 0.9rem;
            margin-right: 0.5rem;
            font-size: 3rem;
            color: var(--primary);
        }


        .login-section {
            padding: 3rem 2.5rem;
        }

        .login-header h2 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1.25rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
        }

        .login-btn {
            background: var(--primary);
            color: var(--white);
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
            width: 100%;
        }

        .login-btn:hover {
            background: var(--primary-dark);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.4);
        }

        .copyright {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            margin-left: 0.75rem;
            border: 2px solid var(--primary);
            cursor: pointer;
            transition: var(--transition);
        }

        .user-avatar:hover {
            transform: scale(1.05);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dropdown-menu {
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            width: 200px;
            overflow: hidden;
            border: none;
        }

        .dropdown-item {
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background: var(--light);
        }

        .footer {
            background: var(--white);
            padding: 1rem;
            border-top: 1px solid var(--gray);
            text-align: center;
            color: var(--text-light);
            font-size: 0.875rem;
            margin-top: auto;
        }

        .message-container {
            position: fixed;
            top: 5rem;
            right: 1rem;
            z-index: 9999;
            max-width: 400px;
            width: 100%;
        }

        .message {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            opacity: 0;
            transform: translateX(100%);
            animation: slideIn 0.5s forwards, fadeOut 0.5s 3.5s forwards;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .add-btn {
            transition: all 0.2s ease;
            border-radius: 0.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            display: inline-flex;
            align-items: center;
            color: var(--primary-dark);
            background-color: var(--white);
            border: 1px solid var(--primary-light);
            font-size: 0.95rem;
            font-weight: bold;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.15);
            background-color: var(--primary);
            color: var(--white);
            cursor: pointer;
        }

        .empty-state {
            padding: 3rem 1.25rem;
            text-align: center;
        }

        .mobile-icon {
            background: var(--white);
            border: var(--primary) 2px solid;
            border-radius: 50%;
            color: var(--primary);
            padding: 0.5rem 0.4rem;
            margin-right: 0.5rem;
        }

        .lg-screen {
            display: block;
        }

        .xs-screen {
            display: none;
        }

        /* Sortable headers */
        th[data-sort] {
            cursor: pointer;
            position: relative;
            user-select: none;
        }

        th[data-sort]:hover {
            background-color: #166534;
        }

        /* Sort indicators */
        th[data-sort].asc::after {
            content: " ↑";
            font-size: 0.8em;
            position: absolute;
            right: 8px;
        }

        th[data-sort].desc::after {
            content: " ↓";
            font-size: 0.8em;
            position: absolute;
            right: 8px;
            color: white;
        }

        @media screen and (max-width: 768px) {
            .lg-screen {
                display: none;
            }

            .xs-screen {
                display: block;
            }
        }
    </style>
</head>

<body>
    <!-- Toast Notification Container -->
    <div class="toast-container">
        <?php if ($toast): ?>
            <div class="toast toast-<?= htmlspecialchars($toast['type']) ?> show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-body d-flex justify-content-between">
                    <?= htmlspecialchars($toast['message']) ?>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
    <!-- ==================== LOGIN SCREEN ==================== -->
    <div class="login-screen">
        <div class="container login-container">
            <div class="card login-card">
                <div class="row g-0">
                    <div class="col-md-5 brand-section">
                        <div class="">
                            <i class="fas fa-gavel fa-flip-horizontal px-3 py-3 fa-3x bg-white text-success border border-2 border-success rounded-circle"></i>
                        </div>
                        <h2 class="mt-3">iCourt</h2>
                    </div>
                    <div class="col-md-7">
                        <div class="card-body login-section">
                            <div class="login-header">
                                <h2>Login</h2>
                                <?php if (!empty($error)): ?>
                                <div class="alert alert-danger d-flex align-items-center mb-3">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo $error; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <form class="login-form" method="POST" action="">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" name="username" id="username" class="form-control"
                                            placeholder="Enter username" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="password" id="password" class="form-control"
                                            placeholder="Enter password" required>
                                        <button type="button" class="btn btn-outline-secondary" id="passwordToggle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" name="login" class="btn login-btn mt-3">Sign In</button>
                                <div class="copyright mt-4">
                                    <b>&copy;</b>
                                    <?php echo date('Y'); ?> iCourt.
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- ==================== DASHBOARD SCREEN ==================== -->
    <!-- Navbar with text -->
    <nav class="navbar navbar-expand bg-body-tertiary shadow shadow-sm">
        <div class="container px-md-0 px-5">
            <a class="navbar-brand text-success" href="icourt.php">
                <i class="fas fa-gavel fa-flip-horizontal px-2 mobile-icon"></i>
                <span class=""><b>iCourt</b></span>
            </a>

            <div class="d-flex align-items-center">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-2 fw-bold text-secondary">
                                <?php echo htmlspecialchars($_SESSION['name']); ?>
                            </span><img
                                src="https://ui-avatars.com/api/?name= <?php echo htmlspecialchars($_SESSION['name']); ?>&length=1&bold=true&background=198754&color=fff"
                                height="40px" class="rounded-circle" alt="User">
                        </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <?php if ($_SESSION['role'] === ROLE_ADMINISTRATOR): ?>
                                    <li><a class="dropdown-item text-success fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                        <i class="fas fa-user-plus me-2"></i> Create User
                                    </a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-success fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-2"></i> Change Password
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a></li>
                            </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <div class="container px-md-0 px-5 my-4">
        <?php if ($_SESSION['role'] === ROLE_ADMINISTRATOR): ?>
            <!-- Admin Dashboard Stats Section -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#usersListModal">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?= count($users) ?></div>
                                <div class="stat-label">Total Users</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#statModal">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">125</div>
                                <div class="stat-label">Total Cases</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#statModal">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">25</div>
                                <div class="stat-label">Today's Hearings</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#statModal">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">48</div>
                                <div class="stat-label">Pending Docs</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card action-card" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <div class="card-body text-center py-5">
                            <div class="bg-light rounded-circle p-4 d-inline-block mb-3">
                                <i class="fas fa-user-plus fa-3x text-primary"></i>
                            </div>
                            <h5>Create New User</h5>
                            <p class="text-muted mb-0">Add judges, clerks, and staff</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card action-card">
                        <div class="card-body text-center py-5">
                            <div class="bg-light rounded-circle p-4 d-inline-block mb-3">
                                <i class="fas fa-book fa-3x text-success"></i>
                            </div>
                            <h5>Manage Cases</h5>
                            <p class="text-muted mb-0">View and update court cases</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card action-card">
                        <div class="card-body text-center py-5">
                            <div class="bg-light rounded-circle p-4 d-inline-block mb-3">
                                <i class="fas fa-calendar-alt fa-3x text-info"></i>
                            </div>
                            <h5>Court Schedule</h5>
                            <p class="text-muted mb-0">Manage hearings calendar</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">User Management</h5>
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td>
                                                <span class="badge <?= $user['role'] === ROLE_ADMINISTRATOR ? 'bg-danger' : ($user['role'] === ROLE_MASTER_USER ? 'bg-warning' : 'bg-primary') ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <?php if ($user['is_active']): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <button type="submit" name="deactivate_user" class="btn btn-sm btn-warning">Deactivate</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <button type="submit" name="activate_user" class="btn btn-sm btn-success">Activate</button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users List Modal -->
            <div class="modal fade" id="usersListModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">System Users (<?= count($users) ?>)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>SL</th>
                                            <th class="text-center">User</th>
                                            <th>Court</th>
                                            <th>Location</th>
                                            <th>Role</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($users)): ?>
                                            <?php $i=0; foreach ($users as $user): $i++ ?>
                                                <tr>
                                                    <td><?= $i; ?></td>
                                                    <td>
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                                                            <div class="text-muted small">@<?= htmlspecialchars($user['username']) ?></div>
                                                        </div>
                                                    </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($user['court_name']) ?></td>
                                                    <td>
                                                        <div class="small"><?= htmlspecialchars($user['district']) ?></div>
                                                        <div class="text-muted small"><?= htmlspecialchars($user['division']) ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $user['role'] === ROLE_ADMINISTRATOR ? 'bg-danger' : ($user['role'] === ROLE_MASTER_USER ? 'bg-warning' : 'bg-primary') ?>">
                                                            <?= ucfirst($user['role']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <i class="fas fa-user-slash fa-3x mb-4 text-muted"></i>
                                                    <h5 class="text-muted">No users found</h5>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Placeholder Modal for Other Stats -->
            <div class="modal fade" id="statModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">System Statistics</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-center">Detailed statistics will be shown here.</p>
                            <div class="text-center mb-3">
                                <i class="fas fa-chart-bar fa-3x text-info"></i>
                            </div>
                            <p class="text-center text-muted">This feature is under development.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($_SESSION['role'] === ROLE_MASTER_USER): ?>
            <!-- Master User Dashboard -->
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Master User Dashboard</h5>
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                                    <?= htmlspecialchars(substr($_SESSION['name'], 0, 1)) ?>
                                </div>
                                <h3><?= htmlspecialchars($_SESSION['name']) ?></h3>
                                <p class="text-muted">Master User Panel - Manage your users and cases</p>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center">
                                            <h4><?= count($users) - 1 ?></h4>
                                            <p class="mb-0">My Users</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h4>45</h4>
                                            <p class="mb-0">Total Cases</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h4>12</h4>
                                            <p class="mb-0">Active Hearings</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-warning text-dark">
                                        <div class="card-body text-center">
                                            <h4>8</h4>
                                            <p class="mb-0">Pending Docs</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-center mb-4">
                                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                    <i class="fas fa-user-plus me-2"></i>Create New User
                                </button>
                                <button class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add New Case
                                </button>
                            </div>

                            <!-- Users created by this master user -->
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Court</th>
                                            <th>Created Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <?php if ($user['master_user_id'] == $_SESSION['user_id']): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['name']) ?></td>
                                                <td><?= htmlspecialchars($user['username']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= htmlspecialchars($user['court_name']) ?></td>
                                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Regular User Dashboard -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">User Dashboard</h5>
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-5">
                                <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                                    <?= htmlspecialchars(substr($_SESSION['name'], 0, 1)) ?>
                                </div>
                                <h3><?= htmlspecialchars($_SESSION['name']) ?></h3>
                                <p class="text-muted">Welcome to iCourt!</p>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-gavel court-icon"></i>
                                                <div>
                                                    <h5 class="mb-0">24</h5>
                                                    <p class="mb-0 text-muted">Active Cases</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar-day court-icon"></i>
                                                <div>
                                                    <h5 class="mb-0">3</h5>
                                                    <p class="mb-0 text-muted">Today's Hearings</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-file-contract court-icon"></i>
                                                <div>
                                                    <h5 class="mb-0">12</h5>
                                                    <p class="mb-0 text-muted">Pending Documents</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-bell court-icon"></i>
                                                <div>
                                                    <h5 class="mb-0">5</h5>
                                                    <p class="mb-0 text-muted">Notifications</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button class="btn btn-icourt me-2">
                                    <i class="fas fa-calendar me-1"></i>View Schedule
                                </button>
                                <button class="btn btn-outline-primary">
                                    <i class="fas fa-folder me-1"></i>Case Files
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="createUserForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password (min 8 characters)</label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Division</label>
                                <select name="division" id="divisionSelect" class="form-select" required>
                                    <option value="">Select Division</option>
                                    <?php foreach ($divisions as $division): ?>
                                        <option value="<?= htmlspecialchars($division) ?>"><?= htmlspecialchars($division) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">District</label>
                                <select name="district" id="districtSelect" class="form-select" required>
                                    <option value="">Select District</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Court Name</label>
                            <input type="text" name="court_name" class="form-control" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile Number</label>
                                <input type="tel" name="mobile_no" class="form-control" required maxlength="15">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <?php if ($_SESSION['role'] === ROLE_ADMINISTRATOR): ?>
                                    <option value="user">User</option>
                                    <option value="master_user">Master User</option>
                                    <option value="administrator">Administrator</option>
                                <?php else: ?>
                                    <option value="user">User</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_user" form="createUserForm" class="btn btn-success">
                        <i class="fas fa-user-plus me-2"></i>Create User
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="changePasswordForm">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password (min 8 characters)</label>
                            <input type="password" name="new_password" class="form-control" required minlength="8">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_password" form="changePasswordForm" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Change Password
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-sign-out-alt fa-3x text-warning mb-3"></i>
                        <p>Are you sure you want to logout?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="?logout" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p class="mb-0"><b>&copy;</b>
            <?php echo date('Y'); ?> iCourt. All rights reserved.
        </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ==================== PASSWORD TOGGLE ====================
        const passwordToggle = document.getElementById('passwordToggle');
        if (passwordToggle) {
            passwordToggle.addEventListener('click', function () {
                const passwordInput = document.getElementById('password');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordInput.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
        }

        // ==================== DISTRICT DROPDOWN AJAX ====================
        document.getElementById('divisionSelect').addEventListener('change', function() {
            const division = this.value;
            if (!division) return;
            
            fetch(`?action=get_districts&division=${encodeURIComponent(division)}`)
                .then(response => response.json())
                .then(districts => {
                    const districtSelect = document.getElementById('districtSelect');
                    districtSelect.innerHTML = '<option value="">Select District</option>';
                    districts.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district;
                        option.textContent = district;
                        districtSelect.appendChild(option);
                    });
                });
        });

        // ==================== AUTO-HIDE TOAST MESSAGES ====================
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 3500);
    </script>
</body>
</html>