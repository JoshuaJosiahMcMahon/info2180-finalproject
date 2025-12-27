<?php
require_once 'config.php';

if (isLoggedIn()) {
    echo '<div class="success" data-user-id="' . $_SESSION['user_id'] . '" data-firstname="' . sanitize($_SESSION['firstname']) . '" data-lastname="' . sanitize($_SESSION['lastname']) . '" data-role="' . sanitize($_SESSION['role']) . '">Session valid</div>';
} else {
    echo '<div class="error">Not logged in</div>';
}
?>

