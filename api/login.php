<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        echo '<div class="error-message">Please enter both email and password.</div>';
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<div class="error-message">Please enter a valid email address.</div>';
        exit;
    }
    
    try {
        $pdo = getDB();

        $stmt = $pdo->prepare("SELECT id, firstname, lastname, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            echo '<div class="success" data-user-id="' . $user['id'] . '" data-firstname="' . sanitize($user['firstname']) . '" data-lastname="' . sanitize($user['lastname']) . '" data-role="' . sanitize($user['role']) . '">Login successful</div>';
        } else {
            echo '<div class="error-message">Invalid email or password.</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error-message">An error occurred. Please try again.</div>';
    }
} else {
    echo '<div class="error-message">Invalid request method.</div>';
}
?>

