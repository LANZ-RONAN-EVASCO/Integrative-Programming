<?php
require_once __DIR__ . '/../includes/auth.php';
if ($u = current_user()) log_activity($u['id'], 'Logged out');
$_SESSION = []; session_destroy();
session_start(); flash('success', 'You have been logged out.');
redirect('admin/login.php');
