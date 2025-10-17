<?php
require_once 'config.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '未登录']);
    exit;
}

// 检查是否为POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '方法不允许']);
    exit;
}

// 获取JSON数据
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['file_id']) && !isset($input['fileId'])) {
    http_response_code(400);
    echo json_encode(['error' => '缺少文件ID']);
    exit;
}

// 兼容两种参数名：file_id（新）和 fileId（旧）
$file_id = isset($input['file_id']) ? $input['file_id'] : $input['fileId'];

// 验证文件ID格式（12位字母数字组合）
if (!preg_match('/^[a-zA-Z0-9]{12}$/', $file_id)) {
    http_response_code(400);
    echo json_encode(['error' => '无效的文件ID格式']);
    exit;
}
$user_id = $_SESSION['user_id'];

try {
    $db = getDB();
    
    // 获取文件信息
    $stmt = $db->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$file_id, $user_id]);
    $file = $stmt->fetch();
    
    if (!$file) {
        http_response_code(404);
        echo json_encode(['error' => '文件不存在或无权访问']);
        exit;
    }
    
    // 删除物理文件
    if (file_exists($file['file_path'])) {
        unlink($file['file_path']);
    }
    
    // 删除缩略图文件（如果存在）
    if ($file['thumbnail_path'] && file_exists($file['thumbnail_path'])) {
        unlink($file['thumbnail_path']);
    }
    
    // 删除数据库记录
    $stmt = $db->prepare("DELETE FROM files WHERE id = ?");
    $stmt->execute([$file_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '删除失败: ' . $e->getMessage()]);
}
?>