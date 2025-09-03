<?php
require_once 'includes/config.php';

// If user is logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    if (is_super_admin()) {
        redirect('views/super_admin_dashboard.php');
    } elseif (is_admin()) {
        redirect('views/admin_dashboard.php');
    } else {
        redirect('views/user_dashboard.php');
    }
} else {
    // If not logged in, redirect to login page
    redirect('views/login.php');
}
?>