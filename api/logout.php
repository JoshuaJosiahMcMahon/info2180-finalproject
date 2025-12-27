<?php
require_once 'config.php';

session_unset();
session_destroy();

echo '<div class="success">Logged out successfully.</div>';
?>

