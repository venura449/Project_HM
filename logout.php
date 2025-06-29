<?php
require_once 'Includes/auth.php';

// Logout the admin
logoutAdmin();

// Redirect to login page with success message
header('Location: login.php?success=logged_out');
exit();
?>
