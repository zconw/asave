<?php
session_start();
require_once 'config.php';

// 生成随机文件ID函数
function generateFileId($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

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

// 检查文件是否上传成功
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => '文件上传失败']);
    exit;
}

$file = $_FILES['file'];
$user_id = $_SESSION['user_id'];

try {
    $db = getDB();
    
    // 生成唯一的文件ID
    $file_id = generateFileId();
    
    // 确保文件ID唯一
    $stmt = $db->prepare("SELECT COUNT(*) FROM files WHERE id = ?");
    $stmt->execute([$file_id]);
    $count = $stmt->fetchColumn();
    
    // 如果文件ID已存在，重新生成（最多尝试5次）
    $attempts = 0;
    while ($count > 0 && $attempts < 5) {
        $file_id = generateFileId();
        $stmt->execute([$file_id]);
        $count = $stmt->fetchColumn();
        $attempts++;
    }
    
    if ($count > 0) {
        throw new Exception('无法生成唯一的文件ID');
    }
    
    // 获取文件信息
    $filename = $file['name'];
    $file_size = $file['size'];
    $tmp_name = $file['tmp_name'];
    $mime_type = mime_content_type($tmp_name);
    
    // 确定文件类型和分类
    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $category = 'files';
    
    // 文件类型分类
    $file_types = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
        'document' => ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'xls', 'xlsx'],
        'audio' => ['mp3', 'wav', 'ogg', 'flac'],
        'video' => ['mp4', 'avi', 'mkv', 'mov'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz']
    ];
    
    foreach ($file_types as $type => $extensions) {
        if (in_array($file_extension, $extensions)) {
            $category = $type;
            break;
        }
    }
    
    // 生成唯一文件名
    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
    
    // 确保分类目录存在
    $category_dir = UPLOAD_DIR . $category . '/';
    if (!file_exists($category_dir)) {
        if (!mkdir($category_dir, 0755, true)) {
            throw new Exception('无法创建分类目录: ' . $category_dir);
        }
    }
    
    // 确定存储路径
    $upload_path = $category_dir . $unique_name;
    
    // 移动文件到目标位置
    if (!move_uploaded_file($tmp_name, $upload_path)) {
        throw new Exception('文件移动失败，请检查目录权限');
    }
    
    // 如果是图片，生成缩略图
    $thumbnail_path = null;
    $thumbnail_url = null;
    if ($category === 'image') {
        $thumbnail_filename = generateThumbnail($upload_path, $unique_name);
        if ($thumbnail_filename) {
            $thumbnail_path = THUMBNAIL_DIR . $thumbnail_filename;
            $thumbnail_url = THUMBNAIL_URL . $thumbnail_filename;
        }
    }
    
    // 生成文件访问URL
    $file_url = UPLOAD_URL . $category . '/' . $unique_name;
    
    // 保存文件信息到数据库
    $stmt = $db->prepare("INSERT INTO files (id, user_id, filename, original_name, file_path, file_url, file_type, file_size, mime_type, thumbnail_path, thumbnail_url, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $file_id,
        $user_id,
        $unique_name,
        $filename,
        $upload_path,
        $file_url,
        $file_extension,
        $file_size,
        $mime_type,
        $thumbnail_path,
        $thumbnail_url,
        $category
    ]);
    
    echo json_encode([
        'success' => true,
        'file_id' => $file_id,
        'filename' => $filename,
        'category' => $category,
        'file_size' => $file_size
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// 使用ImageMagick生成缩略图（更高效，内存占用更少）
function generateThumbnailWithImageMagick($source_path, $filename, $width, $height, $type, $thumb_size) {
    try {
        // 创建ImageMagick对象
        $image = new Imagick($source_path);
        
        // 设置缩略图尺寸
        if ($width > $height) {
            $thumb_width = $thumb_size;
            $thumb_height = intval($height * $thumb_size / $width);
        } else {
            $thumb_height = $thumb_size;
            $thumb_width = intval($width * $thumb_size / $height);
        }
        
        // 对于大图片，使用渐进式缩放策略
        if ($width > 2000 || $height > 2000) {
            // 先缩小到中间尺寸（更高效）
            $intermediate_size = 1000;
            if ($width > $height) {
                $intermediate_width = $intermediate_size;
                $intermediate_height = intval($height * $intermediate_size / $width);
            } else {
                $intermediate_height = $intermediate_size;
                $intermediate_width = intval($width * $intermediate_size / $height);
            }
            
            // 渐进式缩放
            $image->resizeImage($intermediate_width, $intermediate_height, Imagick::FILTER_LANCZOS, 0.9);
            
            // 再缩放到最终尺寸
            $image->resizeImage($thumb_width, $thumb_height, Imagick::FILTER_LANCZOS, 1);
        } else {
            // 直接缩放到最终尺寸
            $image->resizeImage($thumb_width, $thumb_height, Imagick::FILTER_LANCZOS, 1);
        }
        
        // 设置图片质量
        $image->setImageCompressionQuality(85);
        
        // 生成缩略图文件名和路径
        $thumb_filename = 'thumb_' . $filename;
        $thumb_path = THUMBNAIL_DIR . $thumb_filename;
        
        // 确保缩略图目录存在
        if (!file_exists(THUMBNAIL_DIR)) {
            if (!mkdir(THUMBNAIL_DIR, 0755, true)) {
                throw new Exception('无法创建缩略图目录');
            }
        }
        
        // 保存缩略图
        $image->writeImage($thumb_path);
        
        // 释放内存
        $image->clear();
        $image->destroy();
        
        return $thumb_filename;
        
    } catch (Exception $e) {
        // 如果ImageMagick处理失败，回退到GD库（仅处理小图片）
        if ($width <= 2000 && $height <= 2000) {
            return generateThumbnailWithGD($source_path, $filename, $width, $height, $type, $thumb_size);
        }
        return null;
    }
}

// 使用GD库生成缩略图（作为ImageMagick的备用方案）
function generateThumbnailWithGD($source_path, $filename, $width, $height, $type, $thumb_size) {
    // 根据图片类型创建源图像
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($source_path);
            break;
        default:
            return null;
    }
    
    if (!$source) return null;
    
    // 计算缩略图尺寸
    if ($width > $height) {
        $thumb_width = $thumb_size;
        $thumb_height = intval($height * $thumb_size / $width);
    } else {
        $thumb_height = $thumb_size;
        $thumb_width = intval($width * $thumb_size / $height);
    }
    
    // 创建缩略图
    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
    
    // 处理PNG和GIF的透明度
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $thumb_width, $thumb_height, $transparent);
    }
    
    // 调整图片大小
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);
    
    // 保存缩略图
    $thumb_filename = 'thumb_' . $filename;
    $thumb_path = THUMBNAIL_DIR . $thumb_filename;
    
    // 确保缩略图目录存在
    if (!file_exists(THUMBNAIL_DIR)) {
        if (!mkdir(THUMBNAIL_DIR, 0755, true)) {
            throw new Exception('无法创建缩略图目录');
        }
    }
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $thumb_path, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $thumb_path, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $thumb_path);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumb, $thumb_path, 85);
            break;
    }
    
    // 释放内存
    imagedestroy($source);
    imagedestroy($thumb);
    
    return $thumb_filename;
}

// 生成缩略图函数
function generateThumbnail($source_path, $filename) {
    $thumb_size = 200; // 缩略图大小
    
    // 获取图片信息
    $image_info = getimagesize($source_path);
    if (!$image_info) return null;
    
    list($width, $height, $type) = $image_info;
    
    // 优先使用ImageMagick生成缩略图（更高效，内存占用更少）
    if (extension_loaded('imagick')) {
        return generateThumbnailWithImageMagick($source_path, $filename, $width, $height, $type, $thumb_size);
    }
    
    // 如果ImageMagick不可用，使用GD库（仅处理小图片以避免内存问题）
    if ($width <= 2000 && $height <= 2000) {
        return generateThumbnailWithGD($source_path, $filename, $width, $height, $type, $thumb_size);
    }
    
    // 对于大图片且没有ImageMagick，跳过缩略图生成
    return null;
}
    



// 辅助函数：将内存限制字符串转换为字节数
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = intval($val);
    
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    
    return $val;
}
?>