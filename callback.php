<?php
require_once 'config.php';

// 验证state参数防止CSRF攻击
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('State验证失败');
}

// 获取授权码
if (!isset($_GET['code'])) {
    die('未收到授权码');
}
$code = $_GET['code'];

// 使用授权码获取访问令牌
$token_params = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => REDIRECT_URI,
    'client_id' => CLIENT_ID,
    'client_secret' => CLIENT_SECRET
];

$ch = curl_init(TOKEN_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_params));
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);
if (!isset($token_data['access_token'])) {
    die('获取访问令牌失败');
}
$access_token = $token_data['access_token'];

// 获取用户信息
$ch = curl_init(USERINFO_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token
]);
$response = curl_exec($ch);
curl_close($ch);

$user_info = json_decode($response, true);
if (!isset($user_info['account_id'])) {
    die('获取用户信息失败');
}

// 保存用户信息到数据库
try {
    $db = getDB();
    
    // 检查用户是否已存在
    $stmt = $db->prepare("SELECT id FROM users WHERE account_id = ?");
    $stmt->execute([$user_info['account_id']]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        $_SESSION['user_id'] = $existing_user['id'];
    } else {
        // 插入新用户
        $stmt = $db->prepare("INSERT INTO users (account_id, username, email, avatar) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $user_info['account_id'],
            $user_info['username'] ?? '',
            $user_info['email'] ?? '',
            $user_info['avatar'] ?? ''
        ]);
        $_SESSION['user_id'] = $db->lastInsertId();
    }
    
    // 保存用户信息到session
    $_SESSION['user_info'] = $user_info;
    
    // 重定向到控制台
    header('Location: dashboard');
    exit;
    
} catch(PDOException $e) {
    die('数据库错误: ' . $e->getMessage());
}
?>