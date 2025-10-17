<?php
require_once 'config.php';

try {
    $db = getDB();
    
    // 创建用户表
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_id VARCHAR(255) UNIQUE NOT NULL,
        username VARCHAR(255),
        email VARCHAR(255),
        avatar TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 创建文件表
    $db->exec("CREATE TABLE IF NOT EXISTS files (
        id VARCHAR(12) PRIMARY KEY,
        user_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_url VARCHAR(500) NOT NULL,
        file_type VARCHAR(50) NOT NULL,
        file_size BIGINT NOT NULL,
        mime_type VARCHAR(100),
        thumbnail_path VARCHAR(500),
        thumbnail_url VARCHAR(500),
        category VARCHAR(50) DEFAULT 'files',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // 检查并添加file_url字段（如果不存在）
    $result = $db->query("SHOW COLUMNS FROM files LIKE 'file_url'");
    if ($result->rowCount() == 0) {
        $db->exec("ALTER TABLE files ADD COLUMN file_url VARCHAR(500) NOT NULL AFTER file_path");
    }
    
    // 检查并添加thumbnail_url字段（如果不存在）
    $result = $db->query("SHOW COLUMNS FROM files LIKE 'thumbnail_url'");
    if ($result->rowCount() == 0) {
        $db->exec("ALTER TABLE files ADD COLUMN thumbnail_url VARCHAR(500) AFTER thumbnail_path");
    }
    
    echo "数据库初始化成功！<br>";
    echo "用户表和文件表已创建。<br>";
    
} catch(PDOException $e) {
    echo "数据库初始化失败: " . $e->getMessage();
}
?>