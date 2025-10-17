<?php
require_once 'config.php';

// 获取文件ID
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo '缺少文件ID参数';
    exit;
}

$file_id = $_GET['id'];

// 验证文件ID格式（12位字母数字组合）
if (!preg_match('/^[a-zA-Z0-9]{12}$/', $file_id)) {
    http_response_code(400);
    echo '无效的文件ID格式';
    exit;
}

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 查询文件信息 - 移除用户验证，允许外部访问
    $stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        http_response_code(404);
        echo '文件不存在';
        exit;
    }
    
    // 获取文件信息
    $file_path = $file['file_path'];
    $file_name = $file['original_name'];
    $file_type = $file['file_type'];
    
    // 检查文件是否存在
    if (!file_exists($file_path)) {
        http_response_code(404);
        echo '文件不存在';
        exit;
    }
    
    // 设置正确的Content-Type
    header('Content-Type: ' . $file_type);
    
    // 允许跨域访问，支持外部网站嵌入
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // 直接输出文件内容 - 让浏览器决定如何展示
    readfile($file_path);
    
} catch (Exception $e) {
    http_response_code(500);
    echo '文件展示失败: ' . $e->getMessage();
}
?>