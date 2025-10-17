<?php
require_once 'config.php';

// 设置UTF-8编码
header('Content-Type: application/json; charset=utf-8');

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
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
$path = $_GET['path'] ?? '';

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
    
    // 检查文件类型是否为压缩包
    $extension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
    $archive_extensions = ['zip', 'rar', '7z', 'tar', 'gz'];
    
    if (!in_array($extension, $archive_extensions)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '文件不是压缩包格式']);
        exit;
    }
    
    // 检查文件是否存在
    if (!file_exists($file['file_path'])) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '文件不存在']);
        exit;
    }
    
    // 根据文件类型使用不同的方法读取压缩包内容
    $files = [];
    
    if ($extension === 'zip') {
        // 检查ZipArchive扩展是否可用
        if (!class_exists('ZipArchive')) {
            throw new Exception('服务器不支持ZIP文件读取功能');
        }
        
        // 设置执行时间限制
        set_time_limit(30); // 30秒超时
        
        // 使用ZipArchive读取ZIP文件
        $zip = new ZipArchive();
        if ($zip->open($file['file_path']) === TRUE) {
            // 简化处理逻辑，直接获取当前路径下的内容
            $files = [];
            $processedFolders = [];
            
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileInfo = $zip->statIndex($i);
                if (!$fileInfo) continue;
                
                $name = $fileInfo['name'];
                
                // 跳过以/结尾的目录项
                if (substr($name, -1) === '/') {
                    continue;
                }
                
                // 如果指定了路径，只处理该路径下的文件
                if ($path) {
                    // 检查是否在当前路径下
                    if (strpos($name, $path . '/') !== 0) {
                        continue;
                    }
                    
                    // 获取相对于当前路径的部分
                    $relativePath = substr($name, strlen($path) + 1);
                    if ($relativePath === false || $relativePath === '') {
                        continue;
                    }
                } else {
                    $relativePath = $name;
                }
                
                // 解析相对路径
                $pathParts = explode('/', $relativePath);
                
                // 如果是当前路径下的直接文件或文件夹
                if (count($pathParts) === 1) {
                    // 直接文件
                    $files[] = [
                        'name' => $pathParts[0],
                        'full_path' => $name,
                        'size' => $fileInfo['size'],
                        'compressed_size' => $fileInfo['comp_size'],
                        'modified' => date('Y-m-d H:i:s', $fileInfo['mtime']),
                        'is_directory' => false
                    ];
                } else if (count($pathParts) > 1) {
                    // 这是一个子文件夹中的文件，我们只显示第一级文件夹
                    $folderName = $pathParts[0];
                    $folderPath = $path ? $path . '/' . $folderName : $folderName;
                    
                    // 避免重复添加文件夹
                    if (!in_array($folderPath, $processedFolders)) {
                        $files[] = [
                            'name' => $folderName,
                            'full_path' => $folderPath,
                            'size' => 0,
                            'compressed_size' => 0,
                            'modified' => '',
                            'is_directory' => true
                        ];
                        $processedFolders[] = $folderPath;
                    }
                }
            }
            
            $zip->close();
        } else {
            throw new Exception('无法打开ZIP文件');
        }
    } else if ($extension === 'rar') {
        // RAR文件需要rar扩展支持，这里返回基本信息
        $files[] = [
            'name' => 'RAR文件内容预览',
            'size' => filesize($file['file_path']),
            'compressed_size' => filesize($file['file_path']),
            'modified' => date('Y-m-d H:i:s', filemtime($file['file_path'])),
            'note' => 'RAR文件内容预览需要服务器安装rar扩展'
        ];
    } else if (in_array($extension, ['7z', 'tar', 'gz'])) {
        // 其他压缩格式返回基本信息
        $files[] = [
            'name' => strtoupper($extension) . '文件内容预览',
            'size' => filesize($file['file_path']),
            'compressed_size' => filesize($file['file_path']),
            'modified' => date('Y-m-d H:i:s', filemtime($file['file_path'])),
            'note' => '此压缩格式的内容预览功能正在开发中'
        ];
    }
    
    // 返回文件列表
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'files' => $files,
        'total_files' => count($files),
        'archive_name' => $file['original_name'],
        'archive_size' => filesize($file['file_path'])
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '读取压缩包内容失败: ' . $e->getMessage()]);
}



?>