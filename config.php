<?php
// 数据库配置
// 填写您的数据库信息
define('DB_HOST', 'localhost');
define('DB_NAME', 'xxx');
define('DB_USER', 'xxx');
define('DB_PASS', 'xxx');

// OAuth2配置
// 填写您在软柠账号开放平台申请的Client ID和Client Secret，回调地址填写您当前网站的域名（必须SSL）申请地址：https://open.rutno.com/
define('CLIENT_ID', '53d11e18f8ad3');
define('CLIENT_SECRET', 'ed02739dd2f6');
define('REDIRECT_URI', 'https://asave.rutno.com/callback');

// 下面的不能修改，否则无法登录（或者自行删除完整的OAuth系统*不建议）
define('AUTH_URL', 'https://id.rutno.com/rn-oauth/authorize');
define('TOKEN_URL', 'https://id.rutno.com/rn-oauth/token');
define('USERINFO_URL', 'https://id.rutno.com/rn-oauth/userinfo');

// 文件存储路径
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('THUMBNAIL_DIR', __DIR__ . '/thumbnails/');

// 网站域名
// 填写您当前网站的域名（必须SSL）
define('BASE_URL', 'https://asave.rutno.com');

// 文件访问URL
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('THUMBNAIL_URL', BASE_URL . '/thumbnails/');

// 文件类型分类
$file_types = [
    'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
    'document' => ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'xls', 'xlsx'],
    'audio' => ['mp3', 'wav', 'ogg', 'flac'],
    'video' => ['mp4', 'avi', 'mkv', 'mov'],
    'archive' => ['zip', 'rar', '7z', 'tar', 'gz']
];

// 启动会话
session_start();

// 数据库连接函数
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    return $db;
}

// 创建必要的目录
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(THUMBNAIL_DIR)) {
    mkdir(THUMBNAIL_DIR, 0755, true);
}

// 创建子目录
$sub_dirs = ['docs', 'files', 'img', 'audio', 'video', 'archive'];
foreach ($sub_dirs as $dir) {
    $path = UPLOAD_DIR . $dir;
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
}
?>