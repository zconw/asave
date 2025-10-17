<?php
require_once 'config.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('未登录');
}

// 检查文件ID参数
if (!isset($_GET['id'])) {
    http_response_code(400);
    die('缺少文件ID');
}

$file_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

try {
    $db = getDB();
    
    // 获取文件信息
    $stmt = $db->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$file_id, $user_id]);
    $file = $stmt->fetch();
    
    if (!$file) {
        http_response_code(404);
        die('文件不存在或无权访问');
    }
    
    // 检查文件是否存在
    if (!file_exists($file['file_path'])) {
        http_response_code(404);
        die('文件不存在');
    }
    
    // 设置下载头信息
    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
    header('Content-Length: ' . filesize($file['file_path']));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // 输出文件内容
    readfile($file['file_path']);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    die('下载失败: ' . $e->getMessage());
}
?>