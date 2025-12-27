<?php
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'add':
        addUser();
        break;
    case 'list':
        listUsers();
        break;
    case 'form':
        showAddUserForm();
        break;
    default:
        echo '<div class="error-message">Invalid action.</div>';
}

function showAddUserForm() {
    requireAdmin();
    ?>
    <div class="page-header">
        <h2>Add New User</h2>
    </div>
    <div class="form-container">
        <form id="add-user-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="firstname">First Name *</label>
                    <input type="text" id="firstname" name="firstname" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name *</label>
                    <input type="text" id="lastname" name="lastname" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
                <small>Password must be at least 8 characters with at least one uppercase letter, one lowercase letter, and one number.</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="Admin">Admin</option>
                    <option value="Member">Member</option>
                </select>
            </div>
            <div id="user-form-message"></div>
            <button type="submit" class="btn btn-primary">Create User</button>
        </form>
    </div>
    <?php
}

function addUser() {
    requireAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo '<div class="error-message">Invalid request method.</div>';
        return;
    }
    
    $firstname = isset($_POST['firstname']) ? sanitize($_POST['firstname']) : '';
    $lastname = isset($_POST['lastname']) ? sanitize($_POST['lastname']) : '';
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $role = isset($_POST['role']) ? sanitize($_POST['role']) : '';

    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($role)) {
        echo '<div class="error-message">All fields are required.</div>';
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<div class="error-message">Please enter a valid email address.</div>';
        return;
    }

    if ($password !== $confirm_password) {
        echo '<div class="error-message">Passwords do not match.</div>';
        return;
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        echo '<div class="error-message">Password must be at least 8 characters with at least one uppercase letter, one lowercase letter, and one number.</div>';
        return;
    }

    if (!in_array($role, ['Admin', 'Member'])) {
        echo '<div class="error-message">Invalid role selected.</div>';
        return;
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo '<div class="error-message">A user with this email already exists.</div>';
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$firstname, $lastname, $email, $hashedPassword, $role]);
        
        echo '<div class="success-message">User created successfully!</div>';
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred. Please try again.</div>';
    }
}

function listUsers() {
    requireAdmin();
    
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT id, firstname, lastname, email, role, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        ?>
        <div class="page-header">
            <h2>Users</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo sanitize($user['firstname'] . ' ' . $user['lastname']); ?></td>
                    <td><?php echo sanitize($user['email']); ?></td>
                    <td><span class="badge badge-<?php echo strtolower($user['role']); ?>"><?php echo sanitize($user['role']); ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred loading users.</div>';
    }
}
?>

