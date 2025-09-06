<?php
require_once 'includes/config.php';

// If a user is logged in, this block will redirect to appropriate dashboard
if (is_logged_in()) {
    if (is_super_admin()) {
        redirect('views/super_admin_dashboard.php');
    } elseif (is_admin()) {
        redirect('views/admin_dashboard.php');
    } else {
        redirect('views/user_dashboard.php');
    }
} else {
    // If the user is not logged in, this line of code will redirect the user to login page
    redirect('views/login.php');
}
?>