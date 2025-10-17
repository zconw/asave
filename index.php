<?php
require_once 'config.php';

// 检查用户是否已登录
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard');
    exit;
}

// 重定向到登录页面
header('Location: login');
exit;
?>