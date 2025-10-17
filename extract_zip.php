<?php
require_once 'config.php';

// 递归删除目录函数
function rmdir_recursive($dir) {
    if (!file_exists($dir)) return;
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            rmdir_recursive($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

// 生成文件ID函数
function generateFileId() {
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 6)), 0, 12);
}

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

// 获取文件ID
if (!isset($_GET['id'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '缺少文件ID参数']);
    exit;
}

$file_id = $_GET['id'];

// 验证文件ID格式
if (!preg_match('/^[a-zA-Z0-9]{12}$/', $file_id)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的文件ID格式']);
    exit;
}

try {
    $db = getDB();
    
    // 查询文件信息
    $stmt = $db->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$file_id, $_SESSION['user_id']]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '文件不存在']);
        exit;
    }
    
    // 检查文件类型是否为ZIP
    $extension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
    
    if ($extension !== 'zip') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '只有ZIP文件支持解压功能']);
        exit;
    }
    
    // 检查文件是否存在
    if (!file_exists($file['file_path'])) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '文件不存在']);
        exit;
    }
    
    // 检查ZipArchive扩展是否可用
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '服务器不支持ZIP解压功能']);
        exit;
    }
    
    // 创建解压目录
    $extract_dir = dirname($file['file_path']) . '/extracted_' . $file_id . '/';
    if (!file_exists($extract_dir)) {
        mkdir($extract_dir, 0755, true);
    }
    
    // 解压ZIP文件
    $zip = new ZipArchive();
    if ($zip->open($file['file_path']) === TRUE) {
        // 解压所有文件
        $zip->extractTo($extract_dir);
        $zip->close();
        
        // 获取解压后的文件列表
        $extracted_files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extract_dir),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file_info) {
            if ($file_info->isFile()) {
                $relative_path = str_replace($extract_dir, '', $file_info->getPathname());
                
                // 获取文件信息
                $file_name = basename($file_info->getPathname());
                $file_size = $file_info->getSize();
                $file_type = mime_content_type($file_info->getPathname());
                
                // 确定文件分类
                $category = 'other';
                if (strpos($file_type, 'image/') === 0) {
                    $category = 'image';
                } elseif (strpos($file_type, 'text/') === 0 || in_array(pathinfo($file_name, PATHINFO_EXTENSION), ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'])) {
                    $category = 'document';
                } elseif (strpos($file_type, 'audio/') === 0) {
                    $category = 'audio';
                } elseif (strpos($file_type, 'video/') === 0) {
                    $category = 'video';
                } elseif (in_array(pathinfo($file_name, PATHINFO_EXTENSION), ['zip', 'rar', '7z', 'tar', 'gz'])) {
                    $category = 'archive';
                }
                
                // 生成唯一文件ID
                $new_file_id = generateFileId();
                
                // 确定存储路径
                $upload_dir = dirname($file['file_path']) . '/' . $category . '/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $new_file_path = $upload_dir . $new_file_id . '_' . time() . '_' . $file_name;
                
                // 移动文件到对应分类目录
                rename($file_info->getPathname(), $new_file_path);
                
                // 插入数据库记录
                $stmt = $db->prepare("INSERT INTO files (id, user_id, original_name, file_path, file_size, file_type, category, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $new_file_id,
                    $_SESSION['user_id'],
                    $file_name,
                    $new_file_path,
                    $file_size,
                    $file_type,
                    $category
                ]);
                
                $extracted_files[] = [
                    'id' => $new_file_id,
                    'name' => $file_name,
                    'size' => $file_size,
                    'type' => $file_type,
                    'category' => $category
                ];
            }
        }
        
        // 清理解压目录
        rmdir_recursive($extract_dir);
        
        // 返回成功信息
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'ZIP文件解压成功，共解压出 ' . count($extracted_files) . ' 个文件',
            'extracted_files' => $extracted_files
        ]);
        
    } else {
        throw new Exception('无法打开ZIP文件');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '解压失败: ' . $e->getMessage()]);
}
?>