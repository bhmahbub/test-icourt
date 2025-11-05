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

// ==================== TABLE SETUP ====================
// Create users table
$create_users= "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    division VARCHAR(50) NOT NULL,
    district VARCHAR(50) NOT NULL,
    court_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    mobile_no VARCHAR(15) NOT NULL,
    role ENUM('administrator', 'master_user', 'user') DEFAULT 'user',
    master_user_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (master_user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
)";

if (!$conn->query($create_users)) {
    die("Error creating users table: " . $conn->error);
}

// Create default admin if not exists
$check_admin = "SELECT * FROM users WHERE username = 'admin'";
$result = $conn->query($check_admin);
if ($result->num_rows == 0) {
    $hashed_password = password_hash('Admin@123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, username, password, division, district, court_name, email, mobile_no, role)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $name = "Admin User";
    $username = "admin";
    $division = "Headquarters";
    $district = "All";
    $court = "Admin Office";
    $email = "admin@icourt.com";
    $mobile = "01234567890";
    $role = "administrator";
    
    $stmt->bind_param("sssssssss", $name, $username, $hashed_password, $division, $district, $court, $email, $mobile, $role);
    if (!$stmt->execute()) die("Error creating admin: " . $stmt->error);
    $stmt->close();
}

// Create thanas_master table
$create_thanas_master = "CREATE TABLE IF NOT EXISTS thanas_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    division VARCHAR(50) NOT NULL,
    district VARCHAR(50) NOT NULL,
    thana_name VARCHAR(50) NOT NULL
)";
$conn->query($create_thanas_master);

// Check if table is empty before inserting
$check_thanas = "SELECT COUNT(*) as count FROM thanas_master";
$result = $conn->query($check_thanas);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Full thana data for all divisions
        $thana_data = [
        // Barishal Division
        ['Barishal', 'Barishal', 'Barishal Sadar'],
        ['Barishal', 'Barishal', 'Bakerganj'],
        ['Barishal', 'Bhola', 'Bhola Sadar'],
        ['Barishal', 'Bhola', 'Daulatkhan'],
        ['Barishal', 'Jhalokati', 'Jhalokati Sadar'],
        ['Barishal', 'Jhalokati', 'Kathalia'],
        ['Barishal', 'Patuakhali', 'Patuakhali Sadar'],
        ['Barishal', 'Patuakhali', 'Mirzaganj'],
        ['Barishal', 'Pirojpur', 'Pirojpur Sadar'],
        ['Barishal', 'Pirojpur', 'Nazirpur'],
        
                
        // Sylhet Division
        ['Sylhet', 'Habiganj', 'Habiganj Sadar'],
        ['Sylhet', 'Habiganj', 'Ajmiriganj'],
        ['Sylhet', 'Moulvibazar', 'Moulvibazar Sadar'],
        ['Sylhet', 'Moulvibazar', 'Barlekha'],
        ['Sylhet', 'Sunamganj', 'Sunamganj Sadar'],
        ['Sylhet', 'Sunamganj', 'Bishwamvarpur'],
        ['Sylhet', 'Sylhet', 'Beanibazar'],
        ['Sylhet', 'Sylhet', 'Bishwanath']
    ];
    
    // Prepare and execute insert statement
    $stmt = $conn->prepare("INSERT INTO thanas_master (division, district, thana_name) VALUES (?, ?, ?)");
    if (!$stmt) die("Error preparing thana insert: " . $conn->error);
    
    foreach ($thana_data as $thana) {
        $stmt->bind_param("sss", $thana[0], $thana[1], $thana[2]);
        if (!$stmt->execute()) die("Error inserting thana: " . $stmt->error);
    }
    $stmt->close();
}

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
    $create_cases_table = "CREATE TABLE IF NOT EXISTS cases (
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
    
    if ($conn->query($create_thana_table) && $conn->query($create_cases_table)) {
        return true;
    }
    return false;
}

// Handle AJAX request for districts
if (isset($_GET['action']) && $_GET['action'] == 'get_districts' && isset($_GET['division'])) {
    $division = $conn->real_escape_string($_GET['division']);
    
    if (empty($division)) {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }
    
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
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $username = trim($_POST['username']);
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
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
            header("Location: ".$_SERVER['PHP_SELF']."?dashboard=".($user['role'] === 'administrator' ? 'administrator' : 'user'));
            exit;
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Account not found!";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_regenerate_id(true);
    session_destroy();
    $message = "You have been logged out successfully!";
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
    

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


    }



// Handle user creation (admin only)
if (isset($_POST['create_user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'administrator') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
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
    
    // Password strength validation
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
    $role = htmlspecialchars(trim($_POST['role']));
    
    $stmt = $conn->prepare("INSERT INTO users (name, username, password, division, district, court_name, email, mobile_no, role)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $name, $username, $password, $division, $district, $court_name, $email, $mobile_no, $role);
    
    if ($stmt->execute()) {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'User created successfully!'];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error creating user: ' . $conn->error];
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle password change
if (isset($_POST['change_password'])) {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
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

// Fetch users for admin dashboard
$users = [];
if (isset($_SESSION['role']) && $_SESSION['role'] === 'administrator') {
    $sql = "SELECT * FROM users ORDER BY created_at DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
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

<?php   include 'header.php';    ?>

<?php if (!isset($_SESSION['user_id'])): ?>
    <?php include 'login.php'; ?>
<?php else: ?>
    <!-- ==================== DASHBOARD SCREEN ==================== -->
<?php   include 'navbar.php';    ?>    
    <!-- Main content -->
    <div class="container px-md-0 px-5 my-4">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'administrator'): ?>
            <!-- Admin Dashboard Stats Section -->



            <div>
                admin dashboard
            </div>


        

        <?php elseif ($_SESSION['role'] === ROLE_MASTER_USER): ?>
            <!-- Master User Dashboard -->
        <?php include 'stat-grid.php'; ?>


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
                                                        <div class="d-flex align-items-center">
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
                                                        <span class="badge <?= $user['role'] === 'administrator' ? 'bg-success' : 'bg-primary' ?>">
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

        <?php else: ?>

                    <!-- Regular User Dashboard -->
            <div class="row justify-content-center">
user dashboard
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
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
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
                                <option value="user">User</option>
                                <option value="administrator">administrator</option>
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
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
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

<?php   include 'footer.php';    ?>