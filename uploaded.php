<?php
session_start();

// ==================== DATABASE CONFIGURATION ====================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jms');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ==================== TABLE SETUP ====================
// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type VARCHAR(50) NOT NULL DEFAULT 'Magistrate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creating users table: " . $conn->error);
}

// Create cases table
$sql = "CREATE TABLE IF NOT EXISTS cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_type ENUM('CR', 'GR', 'NGR', 'Others') NOT NULL,
    case_no VARCHAR(50) NOT NULL,
    case_year YEAR NOT NULL,
    thana ENUM('Sadar', 'Sreemangal', 'Komolganj', 'Borlekha', 'Juri', 'Kulaura', 'Rajnagar') NOT NULL,
    judgement VARCHAR(100) NOT NULL, 
    judgement_date DATE NOT NULL,
    applied_for_copy ENUM('yes', 'no') NOT NULL,  
    if_typed ENUM('yes', 'no') NOT NULL DEFAULT 'no',
    if_corrected ENUM('yes', 'no') NOT NULL DEFAULT 'no',
    if_final_printed ENUM('yes', 'no') NOT NULL DEFAULT 'no',
    supplied_copy ENUM('yes', 'no') NOT NULL DEFAULT 'no',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creating cases table: " . $conn->error);
}

// ==================== USER AUTHENTICATION ====================
// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, password, user_type FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $user['user_type'];
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }
    }
    
    $_SESSION['error'] = "Invalid username or password";
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

    // Handle logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    // Add sample admin user if not exists
    // $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'Admin'");
    // $stmt->execute();
    // if ($stmt->get_result()->num_rows === 0) {
    //     $password = password_hash('Admin123', PASSWORD_DEFAULT);
    //     $conn->query("INSERT INTO users (username, password) VALUES ('Admin', '$password')");
    // }
    
    // ==================== CASE MANAGEMENT ====================
    // Check if user is logged in
    $logged_in = isset($_SESSION['user_id']);
    $cases_result = null;
    $total_cases = $applied_cases = $current_year_cases = $other_cases = 0;
    
    if ($logged_in) {
        // Add new case
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_case'])) {

            $case_type = $conn->real_escape_string($_POST['case_type']);
            $case_no = $conn->real_escape_string($_POST['case_no']);
            $case_year = $conn->real_escape_string($_POST['case_year']);
            $thana = $conn->real_escape_string($_POST['thana']);
            $judgement = $conn->real_escape_string($_POST['judgement']);
            $judgement_date = $conn->real_escape_string($_POST['judgement_date']);
            $applied_for_copy = $conn->real_escape_string($_POST['applied_for_copy']);

            // Check if case exists
            $check_case = $conn->query("SELECT id FROM cases WHERE case_type = '$case_type' AND  case_no = '$case_no' AND case_year = '$case_year' AND thana = '$thana' ");

            if ($check_case->num_rows > 0) {
                $_SESSION['error'] = "Case already exists!";
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            }
            else {
                $stmt = $conn->prepare("INSERT INTO cases (case_type, case_no, case_year, thana, judgement, judgement_date, applied_for_copy) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssissss", $case_type, $case_no, $case_year, $thana, $judgement, $judgement_date, $applied_for_copy);
            
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Case added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding case: " . $conn->error;
                }
                
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            }            
            
        }
        
        // Handle Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_case'])) {
        $id = $conn->real_escape_string($_POST['id']);
        $applied_for_copy = $conn->real_escape_string($_POST['applied_for_copy']);       
        $if_typed = $conn->real_escape_string($_POST['if_typed']);     
        $if_corrected = $conn->real_escape_string($_POST['if_corrected']);        
        $if_final_printed = $conn->real_escape_string($_POST['if_final_printed']);       
        $supplied_copy = $conn->real_escape_string($_POST['supplied_copy']);
    
        // Prepare the update query
        $stmt = $conn->prepare("UPDATE cases SET 
            applied_for_copy = ?,
            if_typed = ?,
            if_corrected = ?,
            if_final_printed = ?,
            supplied_copy = ?
            WHERE id = ?");
        
        $stmt->bind_param("sssssi", 
            $applied_for_copy,
            $if_typed,
            $if_corrected,
            $if_final_printed,
            $supplied_copy,
            $id
        );
    
        if ($stmt->execute()) {
            $_SESSION['success'] = "Case updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating case: " . $conn->error;
        }
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
        
    // Delete case
    if (isset($_GET['delete'])) {
        $id = $conn->real_escape_string($_GET['delete']);
        if ($conn->query("DELETE FROM cases WHERE id = $id")) {
            $_SESSION['success'] = "Case deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting case: " . $conn->error;
        }
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    // Fetch all cases
    $cases_result = $conn->query("SELECT * FROM cases ORDER BY id DESC");
    $total_cases = $cases_result ? $cases_result->num_rows : 0;
    
    if ($cases_result) {
        $pending_typing = $conn->query("SELECT COUNT(*) AS count FROM cases WHERE if_typed = 'no'")->fetch_assoc()['count'];
        $pending_correction = $conn->query("SELECT COUNT(*) AS count FROM cases WHERE if_typed = 'yes' AND if_corrected = 'no'")->fetch_assoc()['count'];
        $pending_final = $conn->query("SELECT COUNT(*) AS count FROM cases WHERE if_final_printed = 'no'")->fetch_assoc()['count'];
        $cases_result->data_seek(0);
        $cases_this_month = $conn->query("SELECT COUNT(*) AS judgement_count FROM cases WHERE MONTH(judgement_date) = MONTH(CURRENT_DATE()) AND YEAR(judgement_date) = YEAR(CURRENT_DATE())")->fetch_assoc()['judgement_count'];
        $cases_last_month = $conn->query("SELECT COUNT(*) AS judgement_count FROM cases WHERE MONTH(judgement_date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(judgement_date) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)")->fetch_assoc()['judgement_count'];
        $count_pending_copy = $conn->query("SELECT COUNT(*) AS total_count_pending_copy FROM `cases` 
               WHERE applied_for_copy = 'yes' AND supplied_copy = 'no'")->fetch_assoc()['total_count_pending_copy'];

    }

    // Process password change form
    if (isset($_POST['CPass'])) {
        $user_id = $_POST['id'];
        $oldPass = $_POST['oldPass'];
        $newPass = $_POST['newPass'];
        $CnewPass = $_POST['CnewPass'];

        // Validate inputs
        if (empty($oldPass) || empty($newPass) || empty($CnewPass)) {
            $error_message = "All fields are required!";
        } elseif ($newPass !== $CnewPass) {
            $error_message = "New passwords do not match!";
        } else {
            // Fetch current password from database
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify old password
                if (password_verify($oldPass, $user['password'])) {
                    // Hash new password
                    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                    
                    // Update password in database
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $newHash, $user_id);
                    
                    if ($update_stmt->execute()) {
                        $_SESSION['success'] = "Password changed successfully!";
                    } else {
                        $_SESSION['error'] = "Error updating password: " . $conn->error;
                    }
                    $update_stmt->close();
                } else {
                    $_SESSION['error'] = "Incorrect old password!";
                }
            } else {
                $_SESSION['error'] = "User not found!";
            }
            $stmt->close();
        }
    }

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];
        $user_type = $conn->real_escape_string($_POST['user_type'] ?? 'user');
        
        // Check if username exists
        $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($check->num_rows > 0) {
            $_SESSION['error'] = "Username already exists!";
        } else {
            // Hash password and insert
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->query("INSERT INTO users (username, password, user_type) VALUES ('$username', '$hashed_password', '$user_type')");
            
            if ($insert) {
                $_SESSION['success'] = "User added successfully!";
            } else {
                $_SESSION['error'] = "Error adding user: " . $conn->error;
            }
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }

    // Get all users for display
    $users = $conn->query("SELECT id, username, user_type FROM users ORDER BY id");
}

// ==================== SESSION MESSAGES ====================
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $logged_in ? 'Dashboard' : 'Login' ?> - JMS
    </title>
    <link rel="icon" type="image/x-icon" href="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEifDQsoqLU6MqZ6LDd-1pEmzLNu1AwlcvfYy9D_9d0c2o3Ha_Na4sicCQZ_rOceUW9MAgVtEADJUMRHy3dE9xSHywhu4IYrTGJVGR6CHxHWZ_HV0YR-WGQgtBC7in5UL8I853vY0ET1qpvm0M6APKxEGm6aQWAdKKF5gzC1On6gPvxOgLnMS1xsGoeG9nJi/s640/scale-balanced-solid%20(1).png">
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
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2.5rem 1.25rem;
            text-align: center;
        }

        .logo-icon-login {
            width: 80px;
            height: 80px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .logo-icon-login i {
            font-size: 2rem;
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
    <?php if (!$logged_in): ?>
    <!-- ==================== LOGIN SCREEN ==================== -->
    <div class="login-screen">
        <div class="container login-container">
            <div class="card login-card">
                <div class="row g-0">
                    <div class="col-md-5 brand-section">
                        <div class="logo-icon-login">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <h2 class="mt-3">Judgement Management System</h2>
                    </div>
                    <div class="col-md-7">
                        <div class="card-body login-section">
                            <div class="login-header">
                                <h2>Login</h2>
                                <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger d-flex align-items-center mb-3">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo $error_message; ?>
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
                                    <?php echo date('Y'); ?> Judgement Management System.
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
            <a class="navbar-brand text-success lg-screen" href="index.php">
                <i class="fas fa-balance-scale mobile-icon"></i>
                <span class="" st><b>Judgement Management System</b></span>
            </a>
            <a class="navbar-brand text-success xs-screen" href="index.php">
                <i class="fas fa-balance-scale mobile-icon"></i>
                <span class=""><b>JMS</b></span>
            </a>

            <div class="d-flex align-items-center">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-2 fw-bold text-secondary">
                                <?php echo urlencode($_SESSION['username']); ?>
                            </span><img
                                src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&length=1&bold=true&background=198754&color=fff"
                                height="40px" class="rounded-circle" alt="User">
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
                            <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addUserModal"><i
                                        class="fas fa-user text-success me-2"></i>
                                    <span class="text-success fw-bold">Create User</span></button></li>
                            <?php endif; ?>
                            <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#CPassModal"><i
                                        class="fas fa-cog text-success me-2"></i>
                                    <span class="text-success fw-bold">Change Password</span></button></li>

                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="?logout"><i
                                        class="fas fa-sign-out-alt me-2"></i><b>Logout</b></a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <div class="container px-md-0 px-5 my-4">
        <!-- Flash messages -->
        <div class="message-container">
            <?php if (!empty($success_message)): ?>
            <div class="message success-message">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="message error-message">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Welcome Banner -->
        <div class="welcome-banner xs-screen" id="welcomeBanner">
            <h1 class="mb-3">Welcome back,
                <?php echo htmlspecialchars($_SESSION['username']); ?>!
            </h1>
            <p class="mb-0">In this court, <span class="fw-bold" style="cursor: pointer" data-bs-toggle="modal"
                    data-bs-target="#modal1">
                    <?php echo $cases_this_month; ?>
                    <?php echo ($cases_this_month<2 ? ' case is' : ' cases are') ?>
                </span> disposed of in this month. Total <span class="fw-bold" style="cursor: pointer"
                    data-bs-toggle="modal" data-bs-target="#modal2">
                    <?php echo $pending_typing; ?>
                    <?php echo ($pending_typing<2 ? ' case is' : ' cases are') ?>
                </span>pending for typing, <span class="fw-bold" style="cursor: pointer" data-bs-toggle="modal"
                    data-bs-target="#modal3">
                    <?php echo $pending_final; ?>
                    <?php echo ($pending_final<2 ? ' case is' : ' cases are') ?>
                </span>pending for finalized judgement and <span class="fw-bold" style="cursor: pointer"
                    data-bs-toggle="modal" data-bs-target="#modal4">
                    <?php echo $count_pending_copy; ?>
                    <?php echo ($count_pending_copy<2 ? ' record is' : ' records are') ?>
                </span> pending for copying dept..
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="lg-screen">
            <div class="row g-4 mb-4 ">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal1">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $cases_this_month; ?>
                                </div>
                                <div class="stat-label">Disposal this Month</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal2">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $pending_typing; ?>
                                </div>
                                <div class="stat-label">Pending Typing</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal6">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $pending_correction; ?>
                                </div>
                                <div class="stat-label">Pending Correction</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal3">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $pending_final; ?>
                                </div>
                                <div class="stat-label">Pending Final</div>
                            </div>
                        </div>
                    </div>
                </div>



            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal5">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $cases_last_month; ?>
                                </div>
                                <div class="stat-label">Disposal Last Month</div>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" data-bs-toggle="modal" data-bs-target="#modal4">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    <?php echo $count_pending_copy; ?>
                                </div>
                                <div class="stat-label">Pending Copy</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Cases Table -->
        <div class="table-card">
            <div class="table-details">
                <h4 class="mb-0 text-secondary">Disposed Cases</h4>
            </div>

            <div class="row d-flex justify-content-between align-items-center mb-3">
                <div class="col-8 d-flex justify-content-start">
                    <button id="add-case-btn" class="add-btn btn px-4" data-bs-toggle="modal"
                        data-bs-target="#addCaseModal">
                        Add Case
                    </button>
                </div>
                <div class="d-flex align-items-center justify-content-end col-4">
                    <div class="search-container">
                        <div class="search-wrapper">
                            <div class="search-input-container">
                                <input id="searchInput" name="search" type="text" class="search-input"
                                    placeholder="Search..." aria-label="Search cases">
                                <div class="search-icon-container">
                                    <svg class="search-icon" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="sortableTable"
                    class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th class="align-top border border-0">SL</th>
                            <th class="align-top border border-0 text-start">Case No</th>
                            <th class="align-top border border-0">Judgement</th>
                            <th class="align-top border border-0" data-sort="date">Date</th>
                            <th class="d-none">Sortable Date</th>
                            <th class="align-top border border-0" data-sort="copy">For Copy</th>
                            <th class="align-top border border-0">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cases_result && $cases_result->num_rows > 0): $i =1; ?>
                        <?php foreach ($cases_result as $case): ?>
                        <tr>
                            <td>
                                <?php echo $i; ?>
                            </td>
                            <td class="text-start">
                                <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                            </td>
                            <td class="">
                                <?php 
                                    if (strpos($case['judgement'], 'Conviction') !== false) {
                                                    echo 'Conviction';
                                                }
                                    elseif (strpos($case['judgement'], 'Acquittal') !== false) {
                                                    echo 'Acquittal';
                                                }
                                                    else{
                                                        echo  'Others';
                                                    }
                                                ?>
                            </td>
                            <td>
                                <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                            </td>
                            <td class="d-none">
                                <?php echo $case['judgement_date']; ?>
                            </td>
                            <td><span
                                    class="status-badge <?php echo ($case['applied_for_copy'] === 'yes' AND $case['supplied_copy'] === 'no') ? 'status-yes' : 'status-no'; ?>">
                                    <?php 
                                                    if(($case['applied_for_copy'] === 'yes' AND $case['supplied_copy'] === 'no') AND $case['supplied_copy'] === 'no'){
                                                        echo  'Pending Copy';
                                                    } 
                                                    elseif(($case['applied_for_copy'] === 'yes' AND $case['supplied_copy'] === 'yes') AND $case['supplied_copy'] === 'yes'){
                                                        echo  'Done';
                                                    }
                                                    else{
                                                        echo  'N/A';
                                                    }
                                                ?>
                                </span>
                            </td>
                            <td class="">
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                        data-bs-target="#viewModal<?= $case['id'] ?>">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                    <?php if ($_SESSION['user_type'] === 'Magistrate' || $_SESSION['user_type'] === 'Peshkar' || $_SESSION['user_type'] === 'Steno') : ?>
                                    <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                        data-bs-target="#editModal<?= $case['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
                                    <button type="button" class="action-btn delete-btn" data-bs-toggle="modal"
                                        data-bs-target="#deleteModal<?= $case['id'] ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php $i++; endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h4>No case records found</h4>
                                    <p>Add your first case using the "Add Case" button</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
        <div class="table-responsive mt-5">
            <table class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3">
                <thead>
                    <tr>
                        <th>SL</th>
                        <th>Username</th>
                        <th>user_type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows > 0): $i =1; ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <?= $i ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($user['username']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($user['user_type']) ?>
                        </td>
                    </tr>
                    <?php $i++;  endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p class="mb-0"><b>&copy;</b>
            <?php echo date('Y'); ?> JMS. All rights reserved.
        </p>
    </footer>

    <!-- Modal 1 Disposed this Month -->
    <div class="modal fade" id="modal1" tabindex="-1" aria-labelledby="modal1Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal1Label">Disposed this Month</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                    $all_this_month = $conn->query("SELECT * FROM cases WHERE MONTH(judgement_date) = MONTH(CURRENT_DATE()) AND YEAR(judgement_date) = YEAR(CURRENT_DATE()) ORDER BY judgement_date"); 
                                ?>
                            <?php if ($all_this_month && $all_this_month->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_this_month as $case): ?>
                            <tr>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    
                                    <?php 
                                    if (strpos($case['judgement'], 'Conviction') !== false) {
                                                    echo 'Conviction';
                                                }
                                    elseif (strpos($case['judgement'], 'Acquittal') !== false) {
                                                    echo 'Acquittal';
                                                }
                                                    else{
                                                        echo  'Others';
                                                    }
                                                ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php if($case['if_typed'] === 'no' AND $case['if_corrected'] === 'no' AND $case['if_final_printed'] === 'no') {
                                                        echo  '<i class="fa-regular fa-circle-xmark text-danger"></i>';
                                                    } 
                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no' AND $case['if_final_printed'] === 'no') { echo  '<i class="fa-solid fa-check"></i>'; }

                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'no') { echo  '<i class="fa-regular fa-circle-check"></i>'; }
                                        
                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  '<i class="fa-solid fa-circle-check text-success"></i>';
                                                    } else {
                                                        echo  '<i class="fa-regular fa-circle-question"></i>';
                                                    } ?>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <h5 class="py-2">No case records found</h5>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 2 Pending Typing-->
    <div class="modal fade" id="modal2" tabindex="-1" aria-labelledby="modal2Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal1Label">Pending Typing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $all_pending_typing = $conn->query("SELECT * FROM cases WHERE if_typed = 'no' ORDER BY judgement_date"); ?>
                            <?php if ($all_pending_typing && $all_pending_typing->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_pending_typing as $case): ?>
                            <tr>
                                <td>
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start">
                                    <?php echo htmlspecialchars($case['judgement']); ?>
                                </td>
                                <td>
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>
                                <td class="">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $case['id'] ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate' || $_SESSION['user_type'] === 'Peshkar' || $_SESSION['user_type'] === 'Steno') : ?>
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $case['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
                                        <button type="button" class="action-btn delete-btn" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?= $case['id'] ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <h5 class="py-2">No case records found</h5>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 3 Pending Final -->
    <div class="modal fade" id="modal3" tabindex="-1" aria-labelledby="modal3Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal1Label">Pending Final</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $all_pending_final = $conn->query("SELECT * FROM cases WHERE if_final_printed = 'no' ORDER BY judgement_date"); ?>
                            <?php if ($all_pending_final && $all_pending_final->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_pending_final as $case): ?>
                            <tr>
                                <td class=" <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo htmlspecialchars($case['judgement']); ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25';
                                                    } 
                                                     ?>">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $case['id'] ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate' || $_SESSION['user_type'] === 'Peshkar' || $_SESSION['user_type'] === 'Steno') : ?>
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $case['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
                                        <button type="button" class="action-btn delete-btn" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?= $case['id'] ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <h5 class="py-2">No case records found</h5>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 4 Pending copy -->
    <div class="modal fade" id="modal4" tabindex="-1" aria-labelledby="modal4Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal1Label">Pending Copy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $all_pending_copy = $conn->query("SELECT * FROM cases WHERE applied_for_copy = 'yes' AND supplied_copy = 'no' ORDER BY judgement_date"); ?>
                            <?php if ($all_pending_copy && $all_pending_copy->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_pending_copy as $case): ?>
                            <tr>
                                <td class=" <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo htmlspecialchars($case['judgement']); ?>
                                </td>
                                <td class=" <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25 text-success fw-bold';
                                                    } 
                                                    elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no') {
                                                        echo  'text-success fw-bold';
                                                    }
                                                    else{
                                                        echo  'text-danger fw-bold';
                                                    }  ?>">
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes') {
                                                        echo  'bg-success bg-opacity-25';
                                                    } 
                                                      ?>">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $case['id'] ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate' || $_SESSION['user_type'] === 'Peshkar' || $_SESSION['user_type'] === 'Steno') : ?>
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $case['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
                                        <button type="button" class="action-btn delete-btn" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?= $case['id'] ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <h5 class="py-2">No case records found</h5>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 5 Disposed Last Month -->
    <div class="modal fade" id="modal5" tabindex="-1" aria-labelledby="modal5Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal5Label">Disposed Last Month</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table id="sortableTable"
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $all_last_month = $conn->query("SELECT * FROM cases WHERE MONTH(judgement_date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(judgement_date) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH) ORDER BY judgement_date"); ?>

                            <?php if ($all_last_month && $all_last_month->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_last_month as $case): ?>
                            <tr>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start <?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php 
                                    if (strpos($case['judgement'], 'Conviction') !== false) {
                                                    echo 'Conviction';
                                                }
                                    elseif (strpos($case['judgement'], 'Acquittal') !== false) {
                                                    echo 'Acquittal';
                                                }
                                                    else{
                                                        echo  'Others';
                                                    }
                                                ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>
                                <td class="<?php if($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  'text-success fw-bold';
                                                    } ?>">
                                    <?php if($case['if_typed'] === 'no' AND $case['if_corrected'] === 'no' AND $case['if_final_printed'] === 'no') {
                                                        echo  '<i class="fa-regular fa-circle-xmark text-danger"></i>';
                                                    } 
                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'no' AND $case['if_final_printed'] === 'no') { echo  '<i class="fa-solid fa-check"></i>'; }

                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'no') { echo  '<i class="fa-regular fa-circle-check"></i>'; }
                                        
                                        elseif($case['if_typed'] === 'yes' AND $case['if_corrected'] === 'yes' AND $case['if_final_printed'] === 'yes') {
                                                        echo  '<i class="fa-solid fa-circle-check text-success"></i>';
                                                    } else {
                                                        echo  '<i class="fa-regular fa-circle-question"></i>';
                                                    } ?>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">

                                    <h5 class="py-2">No case records found</h5>


                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal 6 Pending Correction-->
    <div class="modal fade" id="modal6" tabindex="-1" aria-labelledby="modal6Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal6Label">Pending Correction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table id="sortableTable"
                        class="table table-hover align-middle table-bordered shadow shadow-sm rounded rounded-3 mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="align-top">SL</th>
                                <th class="align-top text-start">Case No</th>
                                <th class="align-top text-start">Judgement</th>
                                <th class="align-top">Date</th>
                                <th class="align-top">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $all_pending_correction = $conn->query("SELECT * FROM cases WHERE if_typed = 'yes' AND if_corrected = 'no' ORDER BY judgement_date"); ?>
                            <?php if ($all_pending_correction && $all_pending_correction->num_rows > 0): $i =1; ?>
                            <?php foreach ($all_pending_correction as $case): ?>
                            <tr>
                                <td>
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-start">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </td>
                                <td class="text-start">
                                    <?php echo htmlspecialchars($case['judgement']); ?>
                                </td>
                                <td>
                                    <?php echo date('d M, Y', strtotime($case['judgement_date'])); ?>
                                </td>

                                <td class="">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $case['id'] ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate' || $_SESSION['user_type'] === 'Peshkar' || $_SESSION['user_type'] === 'Steno') : ?>
                                        <button type="button" class="action-btn edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $case['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['user_type'] === 'Magistrate'): ?>
                                        <button type="button" class="action-btn delete-btn" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?= $case['id'] ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>

                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <h5 class="py-2">No case records found</h5>

                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog p-3">
            <div class="modal-content">
                <div class="modal-header bg-success text-white px-3">
                    <h5 class="modal-title" id="addUserModalLabel">Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username"
                                required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Password" required>
                        </div>
                        <div class="mb-3">
                            <select class="form-select" id="user_type" name="user_type">
                                <option selected disabled>User Type</option>
                                <option value="Peshkar">Peshkar</option>
                                <option value="Steno">Steno</option>
                                <option value="MLSS">MLSS</option>
                                <option value="Copy Dept.">Copy Dept.</option>
                                <option value="Magistrate">Magistrate</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-outline-success px-4">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Case Modal -->
    <div class="modal fade" id="addCaseModal" tabindex="-1" aria-labelledby="addCaseModalLabel" aria-hidden="true">
        <div class="modal-dialog p-3">
            <div class="modal-content">
                <div class="modal-header bg-success text-white px-3">
                    <h5 class="modal-title" id="addCaseModalLabel">Add Case</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST" action="index.php">
                    <div class="modal-body px-3">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="">
                                    <label for="thana" class="form-label">Thana</label>
                                    <select name="thana" id="thana" required class="form-select">
                                        <option value="" selected disabled>Select Thana</option>
                                        <option value="Sadar">Sadar</option>
                                        <option value="Sreemangal">Sreemangal</option>
                                        <option value="Komolganj">Komolganj</option>
                                        <option value="Rajnagar">Rajnagar</option>
                                        <option value="Kulaura">Kulaura</option>
                                        <option value="Juri">Juri</option>
                                        <option value="Borlekha">Borlekha</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="">
                                    <label for="caseType" class="form-label">Case Type</label>
                                    <select name="case_type" required class="form-select">
                                        <option value="" selected disabled>Select Type</option>
                                        <option value="CR">CR</option>
                                        <option value="GR">GR</option>
                                        <option value="NGR">NGR</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="">
                                    <label for="case_no" class="form-label">Case no</label>
                                    <input type="text" id="case_no" name="case_no" required class="form-control"
                                        placeholder="Enter case number">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="">
                                    <label for="case_year" class="form-label">Case Year</label>
                                    <input type="number" name="case_year" id="case_year" required class="form-control"
                                        placeholder="Enter year" min="2010" max="<?php echo date('Y'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="">
                                    <label for="judgement" class="form-label">Judgement</label>
                                    <select name="judgement" id="judgement" required class="form-select">
                                        <option value="" selected disabled>Select</option>
                                        <option value="Conviction">Conviction</option>
                                        <option value="Conviction - Guilty Plea">Conviction - Guilty Plea</option>
                                        <option value="Acquittal - Full Trial">Acquittal - Full Trial</option>
                                        <option value="Acquittal - On Compromise">Acquittal - On Compromise</option>
                                        <option value="Discharge">Discharge</option>
                                        <option value="249/247">249/247</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" id="date" name="judgement_date" required class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Applied for Copy?</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="applied_for_copy" id="copy-yes"
                                        value="yes" required>
                                    <label class="form-check-label" for="copy-yes">
                                        Yes
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="applied_for_copy" id="copy-no"
                                        value="no" checked>
                                    <label class="form-check-label" for="copy-no">
                                        No
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_case" class="btn btn-success px-4">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modals -->
    <?php foreach ($cases_result as $case): ?>
    <div class="modal fade" id="viewModal<?= $case['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog p-3">
            <div class="modal-content">
                <div class="modal-header bg-success text-white px-5">
                    <h5 class="modal-title">Case no: <span class="fw-bold">
                            <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                        </span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body px-5">
                        <input type="hidden" name="id" value="<?= $case['id'] ?>">

                        <div>
                            <h6 class="text-success">Judgement:
                                <?php echo htmlspecialchars($case['judgement']); ?>
                            </h6>
                            <h6 class="text-success">Judgement Date:
                                <?php echo date('d/m/Y', strtotime($case['judgement_date'])); ?></h6class="text-success">
                                <h6 class="text-success">If Typed: <span
                                        class="<?php echo $case['if_typed'] === 'yes' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo htmlspecialchars($case['if_typed']); ?>
                                    </span></h6>
                                <h6 class="text-success">If Corrected: <span
                                        class="<?php echo $case['if_corrected'] === 'yes' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo htmlspecialchars($case['if_corrected']); ?>
                                    </span></h6>
                                <h6 class="text-success">If Finalized: <span
                                        class="<?php echo $case['if_final_printed'] === 'yes' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo htmlspecialchars($case['if_final_printed']); ?>
                                    </span></h6>
                                <hr>
                                <h6 class="text-success">If Applied for Copy: <span
                                        class="<?php echo ($case['applied_for_copy'] === 'yes' AND $case['supplied_copy'] === 'no') ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo htmlspecialchars($case['applied_for_copy']); ?>
                                    </span></h6>
                                <?php if (($case['applied_for_copy'] === 'yes' AND $case['supplied_copy'] === 'no')): ?>
                                <h6 class="text-success">If Supplied for Copy: <span
                                        class="<?php echo $case['supplied_copy'] === 'yes' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo htmlspecialchars($case['supplied_copy']); ?>
                                    </span></h6>
                                <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer px-5">
                        <button type="button" class="btn btn-outline-success px-5" data-bs-dismiss="modal">OK</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Edit Modal -->
        <?php foreach ($cases_result as $case): ?>
    <div class="modal fade" id="editModal<?= $case['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog p-3">
            <div class="modal-content">
                <div class="modal-header bg-success text-white px-3">
                    <h5 class="modal-title">Update Case Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body px-3">
                        <input type="hidden" name="id" value="<?= $case['id'] ?>">

                        <div class="text-success">
                            <h5>Case no: <span class="fw-bold">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </span></h5>
                            <h6>Judgement:
                                <?php echo htmlspecialchars($case['judgement']); ?>
                            </h6>
                            <h6>Judgement Date:
                                <?php echo date('d/m/Y', strtotime($case['judgement_date'])); ?>
                            </h6>
                        </div>
                        <hr>

                        <div class="row mb-3">
                            <div class="col-sm-4 col-xs-6">
                                <label class="form-label">Typed</label>
                                <select class="form-select" name="if_typed" required>
                                    <option value="yes" <?=$case['if_typed']==='yes' ? 'selected' : '' ?>>Yes</option>
                                    <option value="no" <?=$case['if_typed']==='no' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="col-sm-4 col-xs-6" <?php if ($_SESSION['user_type'] != 'Magistrate' && $_SESSION['user_type'] != 'Peshkar') { echo 'hidden'; } ?>>
                                <label class="form-label">Corrected</label>
                                <select class="form-select" name="if_corrected" required>
                                    <option value="yes" <?=$case['if_corrected']==='yes' ? 'selected' : '' ?>>Yes
                                    </option>
                                    <option value="no" <?=$case['if_corrected']==='no' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            <div class="col-sm-4 col-xs-6" <?php if ($_SESSION['user_type'] != 'Magistrate' && $_SESSION['user_type'] != 'Peshkar') { echo 'hidden'; } ?>>
                                <label class="form-label">Final Printed</label>
                                <select class="form-select" name="if_final_printed" required>
                                    <option value="yes" <?=$case['if_final_printed']==='yes' ? 'selected' : '' ?>>Yes
                                    </option>
                                    <option value="no" <?=$case['if_final_printed']==='no' ? 'selected' : '' ?>>No
                                    </option>
                                </select>
                            </div>
                        
                        </div>
                       
                        <div class="row" <?php if ($_SESSION['user_type'] != 'Magistrate' && $_SESSION['user_type'] != 'Peshkar') { echo 'hidden'; } ?>>
                            <div class="col-sm-4 col-xs-6">
                                <label class="form-label">Applied for Copy</label>
                                <select class="form-select" name="applied_for_copy" required>
                                    <option value="yes" <?=($case['applied_for_copy']==='yes' AND
                                        $case['supplied_copy']==='no' ) ? 'selected' : '' ?>>Yes</option>
                                    <option value="no" <?=$case['applied_for_copy']==='no' ? 'selected' : '' ?>>No
                                    </option>
                                </select>
                            </div>
                            <div class="col-sm-4 col-xs-6">
                                <label class="form-label">Supplied Copy</label>
                                <select class="form-select" name="supplied_copy" required>
                                    <option value="yes" <?=$case['supplied_copy']==='yes' ? 'selected' : '' ?>>Yes
                                    </option>
                                    <option value="no" <?=$case['supplied_copy']==='no' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_case" class="btn btn-success px-3">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <!-- delete modal -->
    <?php foreach ($cases_result as $case): ?>
    <div class="modal fade" id="deleteModal<?= $case['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog p-3">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white px-5">
                    <h5 class="modal-title">Attention!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body px-5">
                        <div class="my-5">
                            <h5 class="text-danger">Do you want to delete <br>Case no: <span class="fw-bold">
                                    <?php echo htmlspecialchars($case['case_type']." ".$case['case_no']."/".$case['case_year']." (".$case['thana'].")"); ?>
                                </span>?</h5>
                        </div>
                    </div>
                    <div class="modal-footer px-5">
                        <button type="button" class="btn btn-outline-secondary px-3"
                            data-bs-dismiss="modal">Cancel</button>
                        <a href="?delete=<?php echo $case['id']; ?>" class="btn btn-danger px-3" ;">Delete</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Change password modal -->
    <div class="modal fade" id="CPassModal" tabindex="-1" aria-labelledby="CPassModalLabel" aria-hidden="true">
        <div class="modal-dialog p-3">
            <div class="modal-content">
                <div class="modal-header bg-success text-white px-4">
                    <h5 class="modal-title" id="CPassModalLabel">Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST" action="index.php">
                    <input type="hidden" name="id" value="<?= $_SESSION['user_id'] ?>">
                    <div class="modal-body px-4">
                        <div class="my-3">
                            <input type="text" id="oldPass" name="oldPass" placeholder="Old Password" required
                                class="form-control">
                        </div>
                        <div class="mb-3">
                            <input type="text" id="newPass" name="newPass" placeholder="New Password" required
                                class="form-control">
                        </div>
                        <div class="mb-3">
                            <input type="text" id="CnewPass" name="CnewPass" placeholder="Confirm New Password" required
                                class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer px-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="CPass" class="btn btn-success px-4">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
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

        // ==================== SEARCH FUNCTIONALITY ====================
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const tableRows = document.querySelectorAll('tbody tr');

                tableRows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    row.style.display = rowText.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        // ==================== Data Sort FUNCTIONALITY ====================
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('sortableTable');
            if (table) {
                const headers = table.querySelectorAll('th[data-sort]');
                const tbody = table.querySelector('tbody');

                headers.forEach(header => {
                    header.addEventListener('click', () => {
                        const sortType = header.getAttribute('data-sort');
                        const rows = Array.from(tbody.querySelectorAll('tr'));

                        // Determine sort direction
                        const direction = header.classList.contains('asc') ? 'desc' : 'asc';

                        // Remove all sort classes
                        headers.forEach(h => {
                            h.classList.remove('asc', 'desc');
                        });

                        // Add class to current header
                        header.classList.add(direction);

                        // Sort rows
                        rows.sort((a, b) => {
                            const aValue = getCellValue(a, sortType);
                            const bValue = getCellValue(b, sortType);

                            if (sortType === 'date') {
                                // Use the hidden column value (already in YYYY-MM-DD format)
                                const aHiddenDate = a.cells[4].textContent.trim();
                                const bHiddenDate = b.cells[4].textContent.trim();
                                return direction === 'asc'
                                    ? aHiddenDate.localeCompare(bHiddenDate)
                                    : bHiddenDate.localeCompare(aHiddenDate);
                            } else if (sortType === 'copy') {
                                const aCopy = aValue === 'Pending Copy' ? 1 : 0;
                                const bCopy = bValue === 'Pending Copy' ? 1 : 0;
                                return direction === 'asc' ? aCopy - bCopy : bCopy - aCopy;
                            } else {
                                return direction === 'asc'
                                    ? aValue.localeCompare(bValue)
                                    : bValue.localeCompare(aValue);
                            }
                        });

                        // Re-add rows in sorted order
                        rows.forEach(row => tbody.appendChild(row));
                    });
                });

                function getCellValue(row, sortType) {
                    // Map sort types to column indexes
                    const columnMap = {
                        'sl': 0,
                        'case_no': 1,
                        'judgement': 2,
                        'date': 3,  // Displayed date column
                        'copy': 5   // Skip the hidden date column (index 4)
                    };

                    const index = columnMap[sortType];
                    return row.cells[index].textContent.trim();
                }
            }
        });
    </script>
</body>

</html>