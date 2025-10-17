<?php
require_once 'config.php';

// 检查用户是否已登录
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard');
    exit;
}

// 生成随机state参数防止CSRF攻击
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// 构建授权URL
$params = [
    'response_type' => 'code',
    'client_id' => CLIENT_ID,
    'redirect_uri' => REDIRECT_URI,
    'scope' => 'id account_id username email phone gender birthday avatar bio nickname region',
    'state' => $state
];

$auth_url = AUTH_URL . '?' . http_build_query($params);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>软柠资源站</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-black m-0 p-0 overflow-hidden h-screen">
    <div class="flex flex-col md:flex-row h-full">
        <!-- 左侧区域 -->
        <div class="flex flex-col items-center justify-center p-8 md:w-1/2 border-r border-gray-100">
            <img src="https://picbox.rutno.com/uploads/68dbc5d5863ee.png" alt="软柠资源站LOGO" class="w-32 mb-6">
            <p class="text-lg mb-8 text-center">精简图床系统，By: wangzikang&软柠科技</p>
            <a href="<?php echo htmlspecialchars($auth_url); ?>" class="login-btn bg-black text-white py-3 px-8 rounded-md hover:bg-gray-800 transition-colors">
                软柠账号登录
            </a>
        </div>
        
        <!-- 右侧区域 -->
        <div class="flex items-center justify-center p-8 md:w-1/2 bg-gray-50">
            <div class="relative w-full max-w-xl">
                <div class="aspect-video overflow-hidden rounded-lg shadow-md">
                    <img src="https://asave.rutno.com/fileview?id=OmZJY17OLtbV" alt="展示图片" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </div>
    
    <!-- 底部链接 -->
    <div class="fixed bottom-0 left-0 right-0 flex justify-center space-x-8 py-4 bg-white border-t border-gray-100">
        <a href="https://www.rutno.com" class="text-black hover:text-gray-600 transition-colors">软柠科技官网</a>
        <a href="https://id.rutno.com" class="text-black hover:text-gray-600 transition-colors">软柠账号</a>
        <a href="https://open.rutno.com" class="text-black hover:text-gray-600 transition-colors">软柠开放平台</a>
    </div>
</body>
</html>