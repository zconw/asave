<?php
require_once 'config.php';

header('Content-Type: application/json');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只支持POST请求']);
    exit;
}

// 获取POST数据
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['file_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少文件ID参数']);
    exit;
}

$file_id = intval($input['file_id']);

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 获取当前用户ID
    $user_id = $_SESSION['user_id'];
    
    // 查询文件信息
    $stmt = $db->prepare("SELECT original_name FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$file_id, $user_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '文件不存在或无权访问']);
        exit;
    }
    
    // 生成下载地址
    $download_url = BASE_URL . '/download.php?id=' . $file_id;
    
    echo json_encode([
        'success' => true, 
        'file_url' => $download_url,
        'filename' => $file['original_name']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '获取文件URL失败: ' . $e->getMessage()]);
}
?>