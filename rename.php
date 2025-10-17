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

if (!isset($input['file_id']) || !isset($input['new_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少必要参数']);
    exit;
}

$file_id = $input['file_id'];
$new_name = trim($input['new_name']);

// 验证文件ID格式（12位字母数字组合）
if (!preg_match('/^[a-zA-Z0-9]{12}$/', $file_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '无效的文件ID格式']);
    exit;
}

// 验证新文件名
if (empty($new_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '新文件名不能为空']);
    exit;
}

// 检查文件名是否包含非法字符
if (preg_match('/[\\/:*?"<>|]/', $new_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '文件名包含非法字符']);
    exit;
}

try {
    $db = getDB();
    
    // 获取当前用户ID
    $user_id = $_SESSION['user_id'];
    
    // 查询文件信息
    $stmt = $db->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$file_id, $user_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '文件不存在或无权访问']);
        exit;
    }
    
    // 获取文件扩展名
    $file_extension = pathinfo($file['original_name'], PATHINFO_EXTENSION);
    $new_filename = $new_name . ($file_extension ? '.' . $file_extension : '');
    
    // 检查新文件名是否已存在
    $stmt = $db->prepare("SELECT COUNT(*) FROM files WHERE user_id = ? AND original_name = ? AND id != ?");
    $stmt->execute([$user_id, $new_filename, $file_id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '文件名已存在']);
        exit;
    }
    
    // 更新数据库记录
    $stmt = $db->prepare("UPDATE files SET original_name = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$new_filename, $file_id, $user_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => '文件重命名成功',
        'new_name' => $new_filename
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '重命名失败: ' . $e->getMessage()]);
}
?>