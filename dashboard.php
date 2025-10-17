<?php
require_once 'config.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

// 获取用户信息
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// 获取用户文件
$stmt = $db->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$files = $stmt->fetchAll();

// 文件分类统计
$file_stats = [
    'all' => count($files),
    'image' => 0,
    'document' => 0,
    'audio' => 0,
    'video' => 0,
    'archive' => 0,
    'other' => 0
];

foreach ($files as $file) {
    if (isset($file_stats[$file['category']])) {
        $file_stats[$file['category']]++;
    } else {
        $file_stats['other']++;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASave - 我的网盘</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #fff;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }
        
        /* 侧边栏样式 */
        .sidebar {
            width: 280px;
            background: #fbfbfb;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        .user-info {
    padding: 10px 10px;
    text-align: center;
    display: flex
;
    gap: 15px;
    margin-bottom: 20px;
    background-color: #f5f5f5;
    border-radius: 7px;
        }
        
        .user-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2em;
        }
        
        .user-name {
            font-size: 16px;
            font-weight: 600;
            /* margin-bottom: 5px; */
                display: flex
;
    align-items: center;
        }
        
        .user-email {
            color: #666;
            font-size: 0.9em;
        }
        
        .storage-info {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .storage-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
        }
        
        .storage-progress {
            height: 100%;
            background: #667eea;
            width: 35%;
            border-radius: 4px;
        }
        
        .nav-menu {
            flex: 1;
            padding: 20px 0;
        }
        
        .nav-item {
    display: flex
;
    align-items: center;
      padding: 12px 10px;
    color: #2b2b2b;
    text-decoration: none;
    cursor: pointer;
    margin: 10px 10px 10px 10px;
    border-radius: 7px;
    transition: all 0.3s;
        }
        
        .nav-item:hover{
    background:rgb(244, 244, 244);
    color:rgb(72, 72, 72);
        }


        .nav-item.active {
    background: #ffffff;
    color: #000000;
    font-weight: 500;
    box-shadow: 0px 1px 3px 0px #0000000d !important;
        }
        
        .nav-item i {
            margin-right: 10px;
            /* width: 20px; */
            text-align: center;
                display: flex
;
        }
        
        .nav-count {
            margin-left: auto;
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        
        /* 主内容区样式 */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        
        .header {
            /* background: white; */
            padding: 0 30px;
            /* border-bottom: 1px solid #e0e0e0; */
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .search-box {
display: flex
;
    align-items: center;
    /* background: #f5f5f5; */
    border-radius: 50px;
    padding: 8px 15px;
    width: 400px;
    height: 40px;
    background-color: #fbfbfb;
    box-shadow: 0px 1px 3px 0px #0000000d !important;
    border: 1px solid #eeeeee;
        }
        
        .search-box input {
            border: none;
            background: none;
            outline: none;
            flex: 1;
            margin-left: 10px;
        }
        
        .upload-btn {
            background: #000000;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .content-area {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .file-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .file-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* ZIP文件预览样式 */
        .zip-breadcrumb {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f9f9f9;
            font-size: 14px;
        }
        
        .breadcrumb-item {
            color: #007bff;
            cursor: pointer;
            text-decoration: none;
        }
        
        .breadcrumb-item:hover {
            text-decoration: underline;
        }
        
        .breadcrumb-item.current {
            color: #333;
            cursor: default;
        }
        
        .breadcrumb-item.current:hover {
            text-decoration: none;
        }
        
        .breadcrumb-separator {
            margin: 0 8px;
            color: #999;
        }
        
        .zip-file-list {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .zip-folder-item, .zip-file-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .zip-folder-item:hover, .zip-file-item:hover {
            background-color: #f5f5f5;
        }
        
        .zip-folder-item i {
            color: #ffc107;
            margin-right: 10px;
            font-size: 16px;
        }
        
        .zip-file-item i {
            color: #6c757d;
            margin-right: 10px;
            font-size: 16px;
        }
        
        .zip-file-name {
            flex: 1;
            font-size: 14px;
            color: #333;
        }
        
        .zip-file-size {
            font-size: 12px;
            color: #999;
            min-width: 80px;
            text-align: right;
        }
        
        .file-icon {
            font-size: 3em;
            margin-bottom: 15px;
            color: #000;
        }
        
        .file-name {
            font-weight: 600;
            margin-bottom: 5px;
            word-break: break-all;
        }
        
        .file-size {
            color: #666;
            font-size: 0.9em;
        }
        
        .file-date {
            color: #999;
            font-size: 0.8em;
            margin-top: 5px;
        }
        
        .file-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: none;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 4px;
            padding: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .file-actions button {
            background: none;
            border: none;
            padding: 5px 8px;
            border-radius: 3px;
            cursor: pointer;
            color: #666;
            font-size: 0.9em;
        }
        
        .file-actions button:hover {
            background: #f0f0f0;
            color: #333;
        }
        
        .file-item:hover .file-actions {
            display: block;
        }
        
        /* 移动端菜单按钮 */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.2em;
            color: #000;
            cursor: pointer;
            padding: 8px;
            margin-right: 10px;
            transition: transform 0.3s ease;
        }
        
        .mobile-menu-btn:hover {
            transform: scale(1.1);
        }
        
        /* 移动端适配 */
        @media (max-width: 768px) {
            /* 禁用移动端文本选择，但保留触摸反馈 */
            html, body, div, span, p, h1, h2, h3, h4, h5, h6, a, button, .file-item, .context-menu-item, .sidebar, .nav-item, .mobile-menu-btn, .mobile-close-btn {
                -webkit-touch-callout: none !important;
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
                -webkit-user-drag: none !important;
                -khtml-user-drag: none !important;
                -moz-user-drag: none !important;
                -o-user-drag: none !important;
                user-drag: none !important;
            }
            
            /* 为可点击元素添加适当的触摸反馈 */
            button, a, .file-item, .context-menu-item, .mobile-menu-btn, .mobile-close-btn {
                -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1) !important;
            }
            
            /* 允许输入框和文本区域选择文本 */
            input, textarea {
                -webkit-user-select: text !important;
                -moz-user-select: text !important;
                -ms-user-select: text !important;
                user-select: text !important;
                -webkit-tap-highlight-color: rgba(0, 0, 0, 0.2) !important;
            }
            .mobile-menu-btn {
                display: block !important;
            }
            
            .sidebar {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 280px;
                height: 100%;
                background: white;
                z-index: 1000;
                box-shadow: 2px 0 20px rgba(0,0,0,0.15);
                transform: translateX(-100%);
                transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                opacity: 0;
            }
            
            .sidebar.mobile-open {
                display: block;
                transform: translateX(0);
                opacity: 1;
            }
            
            /* 侧边栏菜单项动画 */
            .sidebar.mobile-open .nav-item {
                animation: slideInFromLeft 0.5s ease forwards;
                opacity: 0;
                transform: translateX(-20px);
            }
            
            .sidebar.mobile-open .nav-item:nth-child(1) { animation-delay: 0.1s; }
            .sidebar.mobile-open .nav-item:nth-child(2) { animation-delay: 0.15s; }
            .sidebar.mobile-open .nav-item:nth-child(3) { animation-delay: 0.2s; }
            .sidebar.mobile-open .nav-item:nth-child(4) { animation-delay: 0.25s; }
            .sidebar.mobile-open .nav-item:nth-child(5) { animation-delay: 0.3s; }
            .sidebar.mobile-open .nav-item:nth-child(6) { animation-delay: 0.35s; }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0);
                z-index: 999;
                transition: background-color 0.4s ease;
            }
            
            .sidebar-overlay.mobile-open {
                display: block;
                background: rgba(0,0,0,0.5);
            }
            
            /* 侧边栏动画定义 */
            @keyframes slideInFromLeft {
                from {
                    opacity: 0;
                    transform: translateX(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            /* 移动端侧边栏头部样式 */
            .mobile-sidebar-header {
                display: none;
                align-items: center;
                padding: 15px 20px;
                border-bottom: 1px solid #f0f0f0;
                justify-content: space-between;
            }
            
            .mobile-close-btn {
                background: none;
                border: none;
                font-size: 1.3em;
                color: #666;
                cursor: pointer;
                padding: 8px;
                border-radius: 50%;
                transition: all 0.3s ease;
            }
            
            .mobile-close-btn:hover {
                background: #f5f5f5;
                transform: scale(1.1);
            }
            
            .desktop-logo {
                display: block;
            }
            
            .mobile-sidebar-header logo {
                display: flex;
                align-items: center;
            }
            
            @media (max-width: 768px) {
                .mobile-sidebar-header {
                    display: flex !important;
                }
                
                .desktop-logo {
                    display: none !important;
                }
            }
            
            .header {
                padding: 0 15px;
                height: 100px;
                flex-wrap: wrap;
            }
            
            .search-box {
                width: 100%;
                max-width: calc(100% - 100px);
                margin-right: 10px;
                height: 36px;
            }
            
            .upload-btn {
                padding: 8px 16px;
                font-size: 0.8em;
                white-space: nowrap;
                height: 36px;
            }
            
            .content-area {
                padding: 15px;
            }
            
            .files-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .file-item {
                padding: 15px;
            }
            
            .file-icon {
                font-size: 2.5em;
            }
            
            .file-name {
                font-size: 0.9em;
            }
            
            .file-actions {
                display: block !important;
                position: static;
                background: transparent;
                box-shadow: none;
                padding: 5px 0;
                margin-top: 10px;
            }
            
            .file-actions button {
                display: inline-block;
                margin: 0 2px;
                background: #f0f0f0;
                padding: 4px 8px;
                border-radius: 4px;
            }
            
            /* 移动端右键菜单优化 */
            .context-menu {
                min-width: 140px;
                font-size: 16px; /* 移动端字体放大 */
            }
            
            .context-menu-item {
                padding: 15px 20px;
                font-size: 1em;
            }
            
            /* 移动端弹窗优化 */
            .modal-content {
                width: 90%;
                max-width: 300px;
            }
            
            .upload-content {
                width: 90%;
                max-width: 300px;
            }
        }
        
        /* 右键菜单 */
        .context-menu {
            position: fixed;
            background: white;
            border-radius: 7px;
            box-shadow: 0px 1px 3px 0px #0000000d !important;
            z-index: 1000;
            display: none;
            min-width: 120px;
            border: 1px solid #eeeeee;
            animation: slideIn 0.2s ease-out;
        }
        
        .context-menu-item {
            padding: 10px 20px;
            cursor: pointer;
            transition: background 0.3s;
            color: #000000;
        }
        
        .context-menu-item:hover {
            background: #f5f5f5;
        }
        
        /* 上传模态框 */
        .upload-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            animation: fadeIn 0.3s ease-out;
        }
        
        .upload-modal.fade-out {
            animation: fadeOut 0.3s ease-in;
        }
        
        .upload-content {
            background: white;
            padding: 30px;
            border-radius: 7px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0px 1px 3px 0px #0000000d !important;
            border: 1px solid #eeeeee;
            animation: slideIn 0.3s ease-out;
        }
        
        .upload-content.fade-out {
            animation: slideOut 0.3s ease-in;
        }
        
        /* 弹窗样式 - 更新版 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            animation: fadeIn 0.3s ease-out;
        }
        
        .modal-overlay.fade-out {
            animation: fadeOut 0.3s ease-in;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 7px;
            width: 400px;
            max-width: 90%;
            text-align: center;
            box-shadow: 0px 1px 3px 0px #0000000d !important;
            border: 1px solid #eeeeee;
            animation: slideIn 0.3s ease-out;
        }
        
        .modal-content.fade-out {
            animation: slideOut 0.3s ease-in;
        }
        
        .modal-title {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 15px;
            color: #000000;
        }
        
        .modal-message {
            color: #666666;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        
        .modal-button {
            background: #000;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }
        
        .modal-button:hover {
            background: #4e4e4e;
        }
        
        /* 预览弹窗专用样式 */
        .preview-modal-content {
            display: flex;
            flex-direction: column;
            padding: 0;
            overflow: hidden;
        }
        
        .preview-modal-title {
            flex-shrink: 0;
            border-bottom: 1px solid #eeeeee;
            background: #fafafa;
        }
        
        .preview-modal-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        #previewFrame {
            flex: 1;
            border: none;
            background: white;
        }
        
        /* 图片查看器样式 */
        .preview-image-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8f9fa;
            position: relative;
        }
        
        #previewImage {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
            cursor: grab;
        }
        
        #previewImage:active {
            cursor: grabbing;
        }
        
        .preview-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-left: auto;
        }
        
        .preview-control-btn {
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 0.85em;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .preview-control-btn:hover:not(:disabled) {
            background: #e0e0e0;
            border-color: #ccc;
        }
        
        .preview-control-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .preview-control-btn i {
            font-size: 0.9em;
        }
        
        .non-image-preview {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            color: #666;
            text-align: center;
        }
        
        .non-image-preview i {
            font-size: 4em;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .non-image-preview p {
            margin: 10px 0;
            font-size: 1.1em;
        }
        
        .non-image-preview button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 15px;
            transition: background 0.2s ease;
        }
        
        .non-image-preview button:hover {
            background: #0056b3;
        }
        
        /* ZIP预览样式 */
        .zip-preview-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: #f8f9fa;
        }
        
        .zip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: white;
        }
        
        .zip-header h4 {
            margin: 0;
            color: #333;
        }
        
        .zip-file-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            text-align: left;
        }
        
        .zip-file-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s ease;
            text-align: left;
        }
        
        .zip-file-item:hover {
            background: #f0f0f0;
        }
        
        .zip-file-item i {
            margin-right: 10px;
            color: #666;
            width: 20px;
            text-align: left;
        }
        
        .zip-file-name {
            flex: 1;
            font-size: 0.9em;
            text-align: left;
        }
        
        .zip-file-size {
            color: #999;
            font-size: 0.8em;
            text-align: left;
        }
        
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
        }
        
        .loading i {
            margin-right: 10px;
        }
        
        /* Docs预览样式 */
        .docs-preview-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: #f8f9fa;
        }
        
        .docs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: white;
        }
        
        .docs-header h4 {
            margin: 0;
            color: #333;
        }
        
        .docs-controls {
            display: flex;
            gap: 10px;
        }
        
        .docs-viewer {
            flex: 1;
            border: none;
        }
        
        /* 预览内容通用样式 */
        .preview-content {
            width: 100%;
            height: 100%;
        }
        
        /* 动画定义 */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        @keyframes slideOut {
            from { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            to { 
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
        }
        
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: #000;
        }

        .user-rn-avatar {
            height: 45px;
            width: 45px;
            border-radius: 50%;
        }

        logo {
        height: 70px;
display: flex;
align-items: center;
        }
    </style>
</head>
<body>
    <!-- 侧边栏遮罩层 -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- 侧边栏 -->
    <div class="sidebar" id="sidebar">
        <!-- 移动端关闭按钮 -->
        <div class="mobile-sidebar-header" style="display: none;">
            <button class="mobile-close-btn" id="mobileCloseBtn">
                <i class="fas fa-times"></i>
            </button>
            <logo>
                <img src="https://picbox.rutno.com/uploads/68dbc5d5863ee.png" alt="logo" style="height: 30px;">
            </logo>
        </div>
        
        <logo class="desktop-logo">
            <img src="https://picbox.rutno.com/uploads/68dbc5d5863ee.png" alt="logo" style="height: 30px;
    margin-left: 20px;">
        </logo>

        
        <div class="nav-menu">
            <div class="nav-item active" data-category="all">
                <i><svg t="1759237349984" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="8480" width="24" height="24"><path d="M128 576h768v192a128 128 0 0 1-128 128H256a128 128 0 0 1-128-128v-192z" fill="#38D677" p-id="8481"></path><path d="M128 576l108.544-325.653333A85.333333 85.333333 0 0 1 317.504 192h388.992a85.333333 85.333333 0 0 1 80.96 58.346667L896 576H640a128 128 0 1 1-256 0H128z" fill="#8BF0AD" p-id="8482"></path></svg></i>
                <span>全部文件</span>
             
            </div>
            <div class="nav-item" data-category="image">
                <i><svg t="1759237866428" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="8686" width="24" height="24"><path d="M643.669333 110.336l205.994667 205.994667A85.333333 85.333333 0 0 1 874.666667 376.682667V810.666667a128 128 0 0 1-128 128H277.333333a128 128 0 0 1-128-128V213.333333a128 128 0 0 1 128-128h305.984a85.333333 85.333333 0 0 1 60.352 25.002667z" fill="#0DAEFF" p-id="8687"></path><path d="M554.666667 128v234.666667a42.666667 42.666667 0 0 0 42.666666 42.666666h234.666667c38.016 0 57.045333-45.952 30.165333-72.832l-234.666666-234.666666C600.618667 70.954667 554.666667 89.984 554.666667 128z" fill="#8EE4FE" p-id="8688"></path><path d="M373.930667 551.509333V518.613333h-35.626667v32.853334h35.626667z m-40 207.488c15.850667 0 26.453333-3.285333 31.872-9.877333 5.418667-6.570667 8.128-16.746667 8.128-30.506667v-150.229333h-35.626667v149.610667c0 4.906667-0.896 8.234667-2.688 9.941333-1.792 1.706667-4.437333 2.56-7.936 2.56-0.64 0-1.408 0-2.24-0.064a148.181333 148.181333 0 0 1-4.266667-0.298667v28.501334c3.2 0.064 5.824 0.149333 7.957334 0.234666 2.133333 0.085333 3.733333 0.128 4.8 0.128z m108.138666-1.365333v-70.997333c3.733333 5.76 7.573333 10.112 11.498667 13.12 7.168 5.418667 16.170667 8.106667 26.986667 8.106666 17.002667 0 30.912-6.272 41.685333-18.858666 10.794667-12.586667 16.213333-30.869333 16.213333-54.869334 0-22.762667-5.546667-40-16.576-51.754666a53.376 53.376 0 0 0-40.448-17.621334c-10.901333 0-20.330667 3.029333-28.245333 9.109334a57.173333 57.173333 0 0 0-12.245333 14.016v-20.138667h-34.133334v189.866667h35.264z m29.12-79.637333c-6.826667 0-12.757333-1.877333-17.749333-5.610667-8.426667-6.421333-12.629333-17.557333-12.629333-33.386667 0-10.005333 1.258667-18.24 3.754666-24.746666 4.821333-12.245333 13.696-18.368 26.624-18.368 10.752 0 18.517333 4.053333 23.317334 12.117333 4.778667 8.085333 7.189333 17.365333 7.189333 27.882667 0 12.736-2.602667 22.954667-7.829333 30.613333-5.205333 7.68-12.757333 11.52-22.677334 11.52z m148.757334 81.749333c28.821333 0 48.32-7.68 58.496-22.997333 5.909333-8.917333 8.874667-22.165333 8.874666-39.744v-129.258667h-34.624v19.626667c-5.333333-9.578667-12.330667-16.170667-21.013333-19.754667a46.933333 46.933333 0 0 0-17.877333-3.114667c-18.496 0-32.789333 6.890667-42.88 20.693334-10.069333 13.781333-15.104 30.805333-15.104 51.050666 0 21.013333 5.226667 37.653333 15.68 49.941334 10.453333 12.288 24.448 18.432 41.941333 18.432 11.328 0 20.544-2.709333 27.626667-8.106667 3.904-2.922667 7.573333-7.296 10.986666-13.141333v8.746666c0 13.013333-1.408 22.250667-4.245333 27.754667-4.330667 8.576-12.906667 12.885333-25.749333 12.885333-9.088 0-15.530667-1.642667-19.370667-4.885333-2.24-1.834667-3.882667-4.821333-4.885333-9.002667h-38.741334c1.173333 13.589333 7.253333 23.786667 18.261334 30.634667 10.986667 6.826667 25.194667 10.24 42.624 10.24z m2.730666-84.992c-12.48 0-21.162667-5.76-25.984-17.258667a52.778667 52.778667 0 0 1-4.010666-21.12c0-9.173333 1.258667-16.853333 3.754666-23.125333 4.757333-11.733333 13.376-17.621333 25.877334-17.621333 9.173333 0 16.469333 3.370667 21.930666 10.133333 5.461333 6.741333 8.192 16.576 8.192 29.482667 0 13.76-2.88 23.786667-8.618666 30.08-5.76 6.272-12.8 9.429333-21.12 9.429333z" fill="#FFFFFF" p-id="8689"></path></svg></i>
                <span>图片</span>
          
            </div>
            <div class="nav-item" data-category="document">
                <i><svg t="1759237898467" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="9215" width="24" height="24"><path d="M643.669333 110.336l205.994667 205.994667A85.333333 85.333333 0 0 1 874.666667 376.682667V810.666667a128 128 0 0 1-128 128H277.333333a128 128 0 0 1-128-128V213.333333a128 128 0 0 1 128-128h305.984a85.333333 85.333333 0 0 1 60.352 25.002667z" fill="#FE7840" p-id="9216"></path><path d="M554.666667 128v234.666667a42.666667 42.666667 0 0 0 42.666666 42.666666h234.666667c38.016 0 57.045333-45.952 30.165333-72.832l-234.666666-234.666666C600.618667 70.954667 554.666667 89.984 554.666667 128z" fill="#FEC7A3" p-id="9217"></path><path d="M486.997333 725.333333v-88.32h52.16c27.221333 0 48.170667-6.016 62.848-18.005333 14.656-12.010667 21.994667-32.512 21.994667-61.504 0-26.56-7.338667-46.165333-21.994667-58.837333-14.677333-12.672-34.346667-18.986667-59.008-18.986667h-106.986666V725.333333h50.986666z m47.829334-130.666666h-47.829334v-72.32h47.829334c12.117333 0 21.546667 2.709333 28.266666 8.149333 6.72 5.44 10.069333 14.549333 10.069334 27.328 0 12.8-3.349333 22.122667-10.069334 28.010667-6.741333 5.888-16.149333 8.832-28.266666 8.832z" fill="#FFFFFF" p-id="9218"></path></svg></i>
                <span>文档</span>
             
            </div>
            <div class="nav-item" data-category="audio">
                <i><svg t="1759237915634" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="9422" width="24" height="24"><path d="M643.669333 110.336l205.994667 205.994667A85.333333 85.333333 0 0 1 874.666667 376.682667V810.666667a128 128 0 0 1-128 128H277.333333a128 128 0 0 1-128-128V213.333333a128 128 0 0 1 128-128h305.984a85.333333 85.333333 0 0 1 60.352 25.002667z" fill="#A889FE" p-id="9423"></path><path d="M554.666667 128v234.666667a42.666667 42.666667 0 0 0 42.666666 42.666666h234.666667c38.016 0 57.045333-45.952 30.165333-72.832l-234.666666-234.666666C600.618667 70.954667 554.666667 89.984 554.666667 128z" fill="#D1B7FE" p-id="9424"></path><path d="M512 448a21.333333 21.333333 0 0 1 21.333333 21.333333h42.666667a85.333333 85.333333 0 0 1 85.226667 81.066667L661.333333 554.666667h-64a85.12 85.12 0 0 1-64-28.885334V704a85.333333 85.333333 0 1 1-42.645333-73.898667L490.666667 469.333333a21.333333 21.333333 0 0 1 21.333333-21.333333z" fill="#FFFFFF" p-id="9425"></path></svg></i>
                <span>音乐</span>
               
            </div>
            <div class="nav-item" data-category="video">
                <i><svg t="1759237950455" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="9832" width="24" height="24"><path d="M192 192m85.333333 0l469.333334 0q85.333333 0 85.333333 85.333333l0 405.333334q0 85.333333-85.333333 85.333333l-469.333334 0q-85.333333 0-85.333333-85.333333l0-405.333334q0-85.333333 85.333333-85.333333Z" fill="#70B8FF" p-id="9833"></path><path d="M256 256m42.666667 0l426.666666 0q42.666667 0 42.666667 42.666667l0 362.666666q0 42.666667-42.666667 42.666667l-426.666666 0q-42.666667 0-42.666667-42.666667l0-362.666666q0-42.666667 42.666667-42.666667Z" fill="#FCFCFC" opacity=".6" p-id="9834"></path><path d="M64 704m64 0l768 0q64 0 64 64l0 0q0 64-64 64l-768 0q-64 0-64-64l0 0q0-64 64-64Z" fill="#4A9EFF" p-id="9835"></path><path d="M451.114667 391.253333a4.266667 4.266667 0 0 1 2.517333 0.853334l139.52 102.485333a4.266667 4.266667 0 0 1-0.597333 7.232l-55.850667 28.437333 37.546667 75.050667-28.629334 14.293333-37.418666-74.837333-55.168 28.074667a4.266667 4.266667 0 0 1-6.186667-3.797334V395.52c0-2.346667 1.92-4.266667 4.266667-4.266667z" fill="#237FFA" p-id="9836"></path></svg></i>
                <span>视频</span>
               
            </div>
            <div class="nav-item" data-category="archive">
                <i><svg t="1759237934451" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="9625" width="24" height="24"><path d="M643.669333 110.336l205.994667 205.994667A85.333333 85.333333 0 0 1 874.666667 376.682667V810.666667a128 128 0 0 1-128 128H277.333333a128 128 0 0 1-128-128V213.333333a128 128 0 0 1 128-128h305.984a85.333333 85.333333 0 0 1 60.352 25.002667z" fill="#FAB007" p-id="9626"></path><path d="M405.333333 512v85.333333h106.666667v170.666667H298.666667v-85.333333h106.666666v-85.333334h-106.666666v-85.333333h106.666666z m106.666667-85.333333v85.333333h-106.666667v-85.333333h106.666667z m-106.666667-85.333334v85.333334h-106.666666v-85.333334h106.666666z m106.666667-85.333333v85.333333h-106.666667v-85.333333h106.666667z m-106.666667-85.333333v85.333333h-106.666666V170.666667h106.666666z" fill="#FFFFFF" p-id="9627"></path><path d="M554.666667 128v234.666667a42.666667 42.666667 0 0 0 42.666666 42.666666h234.666667c38.016 0 57.045333-45.952 30.165333-72.832l-234.666666-234.666666C600.618667 70.954667 554.666667 89.984 554.666667 128z" fill="#FEE789" p-id="9628"></path></svg></i>
                <span>压缩包</span>
              
            </div>
        </div>
        
        <div style="padding: 20px; border-top: 1px solid #e0e0e0;">
                    <div class="user-info">
            <div class="user-avatar">
                <img class="user-rn-avatar" src="<?php echo htmlspecialchars($user['avatar'] ?: '用户'); ?>">
                
            </div>
            <div class="user-name"><?php echo htmlspecialchars($user['username'] ?: '用户'); ?></div>
        </div>
            <a href="logout" style="color: #666; text-decoration: none; display: flex; align-items: center;    padding: 0px 10px;">
                <i class="fas fa-sign-out-alt" style="margin-right: 10px;"></i>
                <span>退出登录</span>
            </a>
        </div>
    </div>
    
    <!-- 主内容区 -->
    <div class="main-content">
        <div class="header">
            <!-- 移动端菜单按钮 -->
            <button class="mobile-menu-btn" id="mobileMenuBtn" style="display: none;">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-box">
                <i class="fas fa-search" style="color: #999;"></i>
                <input type="text" id="searchInput" placeholder="搜索文件...">
            </div>
            <button class="upload-btn" onclick="openUploadModal()">
                <i class="fas fa-cloud-upload-alt"></i> 上传文件
            </button>
        </div>
        
        <div class="content-area">
            <div class="files-grid" id="filesGrid">
                <?php foreach ($files as $file): ?>
                <div class="file-item" data-file-id="<?php echo $file['id']; ?>" data-category="<?php echo $file['category']; ?>"
                     ondblclick="showFilePreview('<?php echo $file['id']; ?>')">
                    <?php if ($file['category'] === 'image' && $file['thumbnail_url']): ?>
                        <img src="<?php echo htmlspecialchars($file['thumbnail_url']); ?>" alt="缩略图" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                    <?php else: ?>
                        <div class="file-icon">
                            <?php 
                            $icon = 'file';
                            switch($file['category']) {
                                case 'image': $icon = 'file-image'; break;
                                case 'document': $icon = 'file-alt'; break;
                                case 'audio': $icon = 'file-audio'; break;
                                case 'video': $icon = 'file-video'; break;
                                case 'archive': $icon = 'file-archive'; break;
                            }
                            ?>
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                        </div>
                    <?php endif; ?>
                    <div class="file-name"><?php echo htmlspecialchars($file['original_name']); ?></div>
                    <div class="file-size"><?php echo formatFileSize($file['file_size']); ?></div>
                    <div class="file-date"><?php echo date('Y-m-d H:i', strtotime($file['created_at'])); ?></div>
                    <div class="file-actions" style="position: absolute; top: 10px; right: 10px; display: none;">
                   
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($files)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: #666;">
                        <i class="fas fa-cloud" style="font-size: 4em; margin-bottom: 20px; opacity: 0.3;"></i>
                        <h3>暂无文件</h3>
                        <p>点击上方"上传文件"按钮开始上传您的文件</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 右键菜单 -->
    <div class="context-menu" id="contextMenu">
        <div class="context-menu-item" data-action="copyLink">复制外链</div>
        <div class="context-menu-item" data-action="rename">重命名</div>
        <div class="context-menu-item" data-action="delete" style="color: #ff4757;">删除</div>
    </div>
    
    <!-- 弹窗 -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-content">
            <div class="modal-title" id="modalTitle"></div>
            <div class="modal-message" id="modalMessage"></div>
            <button class="modal-button" onclick="closeModal()">确定</button>
        </div>
    </div>
    
    <!-- 文件预览弹窗 -->
    <div class="modal-overlay" id="previewModal" style="display: none;">
        <div class="modal-content preview-modal-content" style="width: 95%; max-width: 1400px; height: 95%; max-height: 900px; background: #ffffff; border-radius: 12px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);">
            <div class="modal-title preview-modal-title" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0; padding: 20px 30px; border-bottom: 1px solid #f0f0f0; background: #fafafa; border-radius: 12px 12px 0 0;">
                <span id="previewTitle" style="font-size: 18px; font-weight: 600; color: #333;">文件预览</span>
                <div class="preview-controls" id="previewControls">
                    <!-- 图片专用控制按钮 -->
                    <div id="imageControls" style="display: none;">
                        <button id="zoomInBtn" class="preview-control-btn" title="放大" onclick="zoomIn()">
                            <i class="fas fa-search-plus"></i> 放大
                        </button>
                        <button id="zoomOutBtn" class="preview-control-btn" title="缩小" onclick="zoomOut()">
                            <i class="fas fa-search-minus"></i> 缩小
                        </button>
                        <button id="rotateBtn" class="preview-control-btn" title="旋转" onclick="rotateImage()">
                            <i class="fas fa-redo"></i> 旋转
                        </button>
                        <button id="resetBtn" class="preview-control-btn" title="重置" onclick="resetImage()">
                            <i class="fas fa-sync"></i> 重置
                        </button>
                    </div>
                    <!-- 通用控制按钮 -->
                    <button onclick="downloadFile()" class="preview-control-btn" title="下载">
                        <i class="fas fa-download"></i> 下载
                    </button>
                    <button class="preview-control-btn" title="关闭" onclick="closePreviewModal()" style="background: #ff4757; color: white;">
                        <i class="fas fa-times"></i> 关闭
                    </button>
                </div>
            </div>
            <div class="preview-modal-body" style="height: calc(100% - 80px); padding: 0; margin: 0; border-radius: 0 0 12px 12px; overflow: hidden; background: #ffffff;">
                <!-- 图片预览 -->
                <div id="imagePreview" class="preview-content" style="display: none; width: 100%; height: 100%; position: relative;">
                    <div class="preview-image-container" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                        <div id="imageLoading" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;">
                            <div style="text-align: center; color: #666;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2em; margin-bottom: 10px;"></i>
                                <p>正在加载图片...</p>
                            </div>
                        </div>
                        <img id="previewImage" src="" alt="预览图片" style="display: none; max-width: 100%; max-height: 100%; object-fit: contain; transition: opacity 0.3s ease;">
                    </div>
                </div>
                
                <!-- ZIP文件预览 -->
                <div id="zipPreview" class="preview-content" style="display: none; width: 100%; height: 100%;">
                    <div class="zip-preview-container">
                        <div class="zip-header" style="background: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                            <h4 style="margin: 0; color: #333; font-weight: 600;">ZIP文件内容</h4>
                            <button onclick="extractZip()" class="preview-control-btn" style="background: #007bff; color: white;">
                                <i class="fas fa-folder-open"></i> 解压查看
                            </button>
                        </div>
                        <div id="zipBreadcrumb" class="zip-breadcrumb" style="padding: 15px 20px; background: #f0f0f0; border-bottom: 1px solid #e0e0e0;">
                            <div class="loading">
                                <i class="fas fa-spinner fa-spin"></i> 正在加载ZIP文件内容...
                            </div>
                        </div>
                        <div id="zipFileList" class="zip-file-list" style="text-align: left; padding: 20px;">
                        </div>
                    </div>
                </div>
                
                <!-- Docs文件预览 -->
                <div id="docsPreview" class="preview-content" style="display: none; width: 100%; height: 100%;">
                    <div class="docs-preview-container">
                        <div class="docs-header" style="background: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
                            <h4 style="margin: 0; color: #333; font-weight: 600;">文档预览</h4>
                            <div class="docs-controls">
                                <button onclick="openInOnlyOffice()" class="preview-control-btn" style="background: #28a745; color: white;">
                                    <i class="fas fa-external-link-alt"></i> 在OnlyOffice中打开
                                </button>
                                <button onclick="downloadFile()" class="preview-control-btn">
                                    <i class="fas fa-download"></i> 下载
                                </button>
                            </div>
                        </div>
                        <div id="docsViewer" class="docs-viewer">
                            <iframe id="docsFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                    </div>
                </div>
                
                <!-- 不支持预览的文件 -->
                <div id="nonImagePreview" class="preview-content" style="display: none; width: 100%; height: 100%;">
                    <div class="non-image-preview" style="background: #f8f9fa;">
                        <i class="fas fa-file" style="color: #999; font-size: 4em;"></i>
                        <p style="color: #666; font-size: 1.1em; margin: 10px 0;">此文件类型不支持预览</p>
                        <button onclick="downloadFile()" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 1em; margin-top: 15px; transition: background 0.2s ease;">下载文件</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 上传模态框 -->
    <div class="upload-modal" id="uploadModal">
        <div class="upload-content">
            <h3 style="margin-bottom: 20px;">上传文件</h3>
            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-cloud-upload-alt" style="font-size: 3em; color: #ccc; margin-bottom: 15px;"></i>
                <p>点击选择文件或拖拽文件到此处</p>
                <p style="font-size: 0.9em; color: #999; margin-top: 10px;">支持单个或多个文件上传</p>
            </div>
            <input type="file" id="fileInput" multiple style="display: none;" onchange="handleFileSelect(this.files)">
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button onclick="closeUploadModal()" style="padding: 8px 16px; border: 1px solid #eeeeee; background: white; border-radius: 7px; cursor: pointer; color: #000000;">取消</button>
                <button onclick="uploadFiles()" style="padding: 8px 16px; background: #000; color: white; border: none; border-radius: 7px; cursor: pointer; box-shadow: 0px 1px 3px 0px #0000000d !important;">开始上传</button>
            </div>
        </div>
    </div>
    
    <script>
        let selectedFileId = null;
        
        // 弹窗功能
        function showModal(title, message) {
            const overlay = document.getElementById('modalOverlay');
            const content = overlay.querySelector('.modal-content');
            
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            
            // 移除淡出类，添加淡入动画
            overlay.classList.remove('fade-out');
            content.classList.remove('fade-out');
            
            overlay.style.display = 'flex';
        }
        
        function closeModal() {
            const overlay = document.getElementById('modalOverlay');
            const content = overlay.querySelector('.modal-content');
            
            // 添加淡出动画
            overlay.classList.add('fade-out');
            content.classList.add('fade-out');
            
            // 动画结束后隐藏
            setTimeout(() => {
                overlay.style.display = 'none';
                overlay.classList.remove('fade-out');
                content.classList.remove('fade-out');
            }, 300);
        }
        
        // 确认弹窗
        function showConfirm(title, message) {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.className = 'modal-overlay';
                overlay.style.display = 'flex';
                
                overlay.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-title">${title}</div>
                        <div class="modal-message">${message}</div>
                        <div style="display: flex; gap: 10px; justify-content: center;">
                            <button class="modal-button" id="confirmBtn" style="background: #000;">确定</button>
                            <button class="modal-button" id="cancelBtn" style="background: #999999;">取消</button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(overlay);
                
                // 获取元素引用
                const confirmBtn = overlay.querySelector('#confirmBtn');
                const cancelBtn = overlay.querySelector('#cancelBtn');
                
                // 添加按钮事件监听器
                confirmBtn.addEventListener('click', function() {
                    overlay.remove();
                    resolve(true);
                });
                
                cancelBtn.addEventListener('click', function() {
                    overlay.remove();
                    resolve(false);
                });
            });
        }
        
        // 输入弹窗
        function showInputModal(title, message, defaultValue = '') {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.className = 'modal-overlay';
                overlay.style.display = 'flex';
                
                overlay.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-title">${title}</div>
                        <div class="modal-message">${message}</div>
                        <input type="text" id="inputField" value="${defaultValue}" style="width: 100%; padding: 12px; border: 1px solid #eeeeee; border-radius: 7px; margin: 15px 0; background: #ffffff; color: #000000; font-size: 14px; outline: none; transition: border-color 0.2s; box-shadow: 0px 1px 3px 0px #0000000d !important;" autofocus>
                        <div style="display: flex; gap: 10px; justify-content: center;">
                            <button class="modal-button" id="confirmBtn" style="background: #000;">确定</button>
                            <button class="modal-button" id="cancelBtn" style="background: #999999;">取消</button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(overlay);
                
                // 获取元素引用
                const inputField = overlay.querySelector('#inputField');
                const confirmBtn = overlay.querySelector('#confirmBtn');
                const cancelBtn = overlay.querySelector('#cancelBtn');
                
                // 添加按钮事件监听器
                confirmBtn.addEventListener('click', function() {
                    overlay.remove();
                    resolve(inputField.value);
                });
                
                cancelBtn.addEventListener('click', function() {
                    overlay.remove();
                    resolve(null);
                });
                
                // 添加回车键支持
                inputField.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        overlay.remove();
                        resolve(this.value);
                    }
                });
                
                // 自动聚焦输入框
                inputField.focus();
            });
        }
        
        // 文件大小格式化函数
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // 分类筛选
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                const category = this.dataset.category;
                document.querySelectorAll('.file-item').forEach(file => {
                    if (category === 'all' || file.dataset.category === category) {
                        file.style.display = 'block';
                    } else {
                        file.style.display = 'none';
                    }
                });
            });
        });
        
        // 搜索功能
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.file-item').forEach(file => {
                const fileName = file.querySelector('.file-name').textContent.toLowerCase();
                if (fileName.includes(searchTerm)) {
                    file.style.display = 'block';
                } else {
                    file.style.display = 'none';
                }
            });
        });
        
        // 右键菜单功能
        function showContextMenu(event, fileId) {
            event.preventDefault();
            selectedFileId = fileId;
            
            const menu = document.getElementById('contextMenu');
            menu.style.display = 'block';
            
            // 移动端适配：确保菜单在屏幕内显示
            let x = event.pageX;
            let y = event.pageY;
            
            // 如果是移动端长按事件，调整位置到文件项中心
            if (event.touches) {
                const touch = event.touches[0];
                x = touch.pageX;
                y = touch.pageY;
            }
            
            // 确保菜单不会超出屏幕边界
            const menuWidth = menu.offsetWidth;
            const menuHeight = menu.offsetHeight;
            const screenWidth = window.innerWidth;
            const screenHeight = window.innerHeight;
            
            if (x + menuWidth > screenWidth) {
                x = screenWidth - menuWidth - 10;
            }
            if (y + menuHeight > screenHeight) {
                y = screenHeight - menuHeight - 10;
            }
            
            menu.style.left = Math.max(10, x) + 'px';
            menu.style.top = Math.max(10, y) + 'px';
            
            // 点击其他地方关闭菜单
            document.addEventListener('click', function closeMenu() {
                menu.style.display = 'none';
                document.removeEventListener('click', closeMenu);
            });
            
            // 移动端添加触摸关闭支持
            document.addEventListener('touchstart', function closeMenuTouch(e) {
                if (!menu.contains(e.target)) {
                    menu.style.display = 'none';
                    document.removeEventListener('touchstart', closeMenuTouch);
                }
            });
        }
        
        // 长按事件处理（移动端）
        let longPressTimer;
        let longPressTarget;
        
        // 为文件项添加长按事件（移动端）和右键菜单事件（PC端）
        document.getElementById('filesGrid').addEventListener('touchstart', function(event) {
            const fileItem = event.target.closest('.file-item');
            if (fileItem) {
                longPressTarget = fileItem;
                longPressTimer = setTimeout(() => {
                    const fileId = fileItem.dataset.fileId;
                    // 模拟右键菜单位置
                    const rect = fileItem.getBoundingClientRect();
                    const fakeEvent = {
                        pageX: rect.left + rect.width / 2,
                        pageY: rect.top + rect.height / 2,
                        preventDefault: () => {}
                    };
                    showContextMenu(fakeEvent, fileId);
                }, 500); // 长按500毫秒触发
            }
        });
        
        document.getElementById('filesGrid').addEventListener('touchend', function(event) {
            clearTimeout(longPressTimer);
        });
        
        document.getElementById('filesGrid').addEventListener('touchmove', function(event) {
            clearTimeout(longPressTimer);
        });
        
        // 为文件项添加右键菜单事件委托（PC端）
        document.getElementById('filesGrid').addEventListener('contextmenu', function(event) {
            console.log('右键菜单事件被触发', event.target);
            const fileItem = event.target.closest('.file-item');
            if (fileItem) {
                console.log('找到文件项:', fileItem.dataset.fileId);
                event.preventDefault();
                const fileId = fileItem.dataset.fileId;
                showContextMenu(event, fileId);
            } else {
                console.log('未找到文件项');
            }
        });
        
        // 为右键菜单项添加点击事件委托
        document.getElementById('contextMenu').addEventListener('click', function(event) {
            const menuItem = event.target.closest('.context-menu-item');
            if (menuItem) {
                const action = menuItem.dataset.action;
                switch(action) {
                    case 'copyLink':
                        copyFileLink();
                        break;
                    case 'rename':
                        renameFileContext();
                        break;
                    case 'delete':
                        deleteFile();
                        break;
                }
                // 隐藏右键菜单
                this.style.display = 'none';
            }
        });
        
        function copyFileLink() {
            console.log('开始复制外链，文件ID:', selectedFileId);
            
            // 检查文件ID是否有效
            if (!selectedFileId) {
                showModal('错误', '请先选择文件');
                return;
            }
            
            // 构造文件展示URL（不带.php后缀，符合伪静态规则）
            const fileViewUrl = window.location.origin + '/fileview?id=' + selectedFileId;
            
            // 复制URL到剪贴板
            navigator.clipboard.writeText(fileViewUrl).then(() => {
                showModal('成功', '文件外链已复制到剪贴板: ' + fileViewUrl);
            }).catch(() => {
                // 如果clipboard API不可用，使用备用方法
                const textArea = document.createElement('textarea');
                textArea.value = fileViewUrl;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showModal('成功', '文件外链已复制到剪贴板: ' + fileViewUrl);
            });
        }
        
        function showFilePreview(fileId = null) {
            const id = fileId || selectedFileId;
            
            // 检查文件ID是否有效
            if (!id) {
                showModal('错误', '请先选择文件');
                return;
            }
            
            // 获取文件项信息
            const fileItem = document.querySelector(`[data-file-id="${id}"]`);
            if (!fileItem) {
                showModal('错误', '文件不存在');
                return;
            }
            
            const fileName = fileItem.querySelector('.file-name').textContent;
            const fileCategory = fileItem.dataset.category;
            const fileExtension = fileName.split('.').pop().toLowerCase();
            
            // 设置标题
            document.getElementById('previewTitle').textContent = '预览: ' + fileName;
            
            // 隐藏图片专用控制按钮
            document.getElementById('imageControls').style.display = 'none';
            
            // 显示预览弹窗
            const previewModal = document.getElementById('previewModal');
            previewModal.style.display = 'flex';
            
            // 隐藏所有预览内容
            document.querySelectorAll('.preview-content').forEach(el => {
                el.style.display = 'none';
            });
            
            // 根据文件类型显示不同的预览
            if (fileCategory === 'image') {
                // 图片预览
                document.getElementById('imagePreview').style.display = 'block';
                const imageUrl = window.location.origin + '/fileview?id=' + id;
                const previewImage = document.getElementById('previewImage');
                const imageLoading = document.getElementById('imageLoading');
                
                // 显示加载状态
                imageLoading.style.display = 'flex';
                previewImage.style.display = 'none';
                
                // 显示图片专用控制按钮
                document.getElementById('imageControls').style.display = 'flex';
                
                // 创建新的Image对象进行预加载
                const img = new Image();
                img.onload = function() {
                    // 图片加载完成后显示
                    previewImage.src = imageUrl;
                    previewImage.style.display = 'block';
                    imageLoading.style.display = 'none';
                    
                    // 初始化图片查看器
                    initImageViewer();
                };
                img.onerror = function() {
                    // 图片加载失败
                    imageLoading.innerHTML = '<div style="text-align: center; color: #ff4757;"><i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px;"></i><p>图片加载失败</p></div>';
                };
                img.src = imageUrl;
                
            } else if (fileExtension === 'zip' || fileExtension === 'rar' || fileExtension === '7z') {
                // ZIP文件预览
                document.getElementById('zipPreview').style.display = 'block';
                loadZipContents(id);
                
            } else if (isDocumentFile(fileExtension)) {
                // Docs文件预览
                document.getElementById('docsPreview').style.display = 'block';
                loadDocumentPreview(id, fileExtension);
                
            } else {
                // 不支持预览的文件
                document.getElementById('nonImagePreview').style.display = 'block';
                const downloadBtn = document.querySelector('#nonImagePreview button');
                downloadBtn.onclick = function() {
                    downloadFile(id);
                };
            }
            
            // 添加ESC键关闭监听
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    closePreviewModal();
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);
            
            // 添加点击外部关闭监听
            const clickHandler = (e) => {
                if (e.target === previewModal) {
                    closePreviewModal();
                    previewModal.removeEventListener('click', clickHandler);
                }
            };
            previewModal.addEventListener('click', clickHandler);
        }
        
        function closePreviewModal() {
            const previewModal = document.getElementById('previewModal');
            const modalContent = previewModal.querySelector('.modal-content');
            
            // 添加淡出动画
            previewModal.classList.add('fade-out');
            modalContent.classList.add('fade-out');
            
            // 动画结束后隐藏
            setTimeout(() => {
                previewModal.style.display = 'none';
                previewModal.classList.remove('fade-out');
                modalContent.classList.remove('fade-out');
                
                // 清空所有预览内容
                document.querySelectorAll('.preview-content').forEach(el => {
                    el.style.display = 'none';
                });
                
                // 清空图片内容
                const previewImage = document.getElementById('previewImage');
                previewImage.src = '';
                previewImage.style.display = 'none';
                
                // 重置图片加载状态
                const imageLoading = document.getElementById('imageLoading');
                imageLoading.style.display = 'flex';
                imageLoading.innerHTML = '<div style="text-align: center; color: #666;"><i class="fas fa-spinner fa-spin" style="font-size: 2em; margin-bottom: 10px;"></i><p>正在加载图片...</p></div>';
                
                // 隐藏图片专用控制按钮
                document.getElementById('imageControls').style.display = 'none';
                
                // 清空ZIP文件列表
                const zipFileList = document.getElementById('zipFileList');
                zipFileList.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> 正在加载ZIP文件内容...</div>';
                
                // 清空Docs预览
                const docsFrame = document.getElementById('docsFrame');
                docsFrame.src = '';
                
                // 重置图片查看器状态
                resetImageViewer();
            }, 300);
        }
        
        // 图片查看器功能
        let currentScale = 1;
        let currentRotation = 0;
        
        function initImageViewer() {
            // 重置状态
            currentScale = 1;
            currentRotation = 0;
            
            // 更新按钮状态
            updateViewerButtons();
        }
        
        function resetImageViewer() {
            currentScale = 1;
            currentRotation = 0;
        }
        
        function updateViewerButtons() {
            // 更新缩放按钮状态
            document.getElementById('zoomInBtn').disabled = currentScale >= 5;
            document.getElementById('zoomOutBtn').disabled = currentScale <= 0.1;
            
            // 更新旋转按钮文本
            document.getElementById('rotateBtn').innerHTML = 
                `<i class="fas fa-redo"></i> 旋转 (${currentRotation}°)`;
        }
        
        function zoomIn() {
            if (currentScale < 5) {
                currentScale += 0.1;
                applyImageTransform();
            }
        }
        
        function zoomOut() {
            if (currentScale > 0.1) {
                currentScale -= 0.1;
                applyImageTransform();
            }
        }
        
        function rotateImage() {
            currentRotation = (currentRotation + 90) % 360;
            applyImageTransform();
        }
        
        function resetImage() {
            currentScale = 1;
            currentRotation = 0;
            applyImageTransform();
        }
        
        function applyImageTransform() {
            const previewImage = document.getElementById('previewImage');
            previewImage.style.transform = `scale(${currentScale}) rotate(${currentRotation}deg)`;
            updateViewerButtons();
        }
        
        // 判断是否为文档文件
        function isDocumentFile(extension) {
            const docExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'rtf', 'odt', 'ods', 'odp'];
            return docExtensions.includes(extension);
        }
        
        // 全局变量存储ZIP导航状态
        let currentZipPath = '';
        let currentZipFileId = null;
        
        // 加载ZIP文件内容
        async function loadZipContents(fileId, path = '') {
            try {
                const url = '/get_zip_contents.php?id=' + fileId + (path ? '&path=' + encodeURIComponent(path) : '');
                const response = await fetch(url);
                const data = await response.json();
                
                const zipFileList = document.getElementById('zipFileList');
                
                if (data.success && data.files) {
                    // 更新导航状态
                    currentZipPath = path;
                    currentZipFileId = fileId;
                    
                    // 更新面包屑导航
                    updateBreadcrumb(path, fileId);
                    
                    zipFileList.innerHTML = '';
                    
                    // 先显示文件夹，再显示文件
                    const folders = data.files.filter(item => item.is_directory);
                    const files = data.files.filter(item => !item.is_directory);
                    
                    // 显示文件夹
                    folders.forEach(folder => {
                        const folderItem = document.createElement('div');
                        folderItem.className = 'zip-folder-item';
                        folderItem.innerHTML = `
                            <i class="fas fa-folder"></i>
                            <span class="zip-file-name" onclick="enterFolder('${folder.full_path}')">${folder.name}</span>
                            <span class="zip-file-size">文件夹</span>
                        `;
                        zipFileList.appendChild(folderItem);
                    });
                    
                    // 显示文件
                    files.forEach(file => {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'zip-file-item';
                        fileItem.innerHTML = `
                            <i class="fas fa-file"></i>
                            <span class="zip-file-name">${file.name}</span>
                            <span class="zip-file-size">${formatFileSize(file.size)}</span>
                        `;
                        zipFileList.appendChild(fileItem);
                    });
                    
                    // 如果没有内容
                    if (folders.length === 0 && files.length === 0) {
                        zipFileList.innerHTML = '<div style="text-align: center; color: #666; padding: 40px;">文件夹为空</div>';
                    }
                } else {
                    zipFileList.innerHTML = '<div style="text-align: center; color: #666; padding: 40px;">无法读取ZIP文件内容</div>';
                }
            } catch (error) {
                console.error('Error loading ZIP contents:', error);
                const zipFileList = document.getElementById('zipFileList');
                zipFileList.innerHTML = '<div style="text-align: center; color: #666; padding: 40px;">加载ZIP文件内容失败</div>';
            }
        }
        
        // 更新面包屑导航
        function updateBreadcrumb(path, fileId) {
            const breadcrumbContainer = document.getElementById('zipBreadcrumb');
            if (!breadcrumbContainer) return;
            
            const pathParts = path ? path.split('/').filter(p => p) : [];
            
            let breadcrumbHtml = '<span class="breadcrumb-item" onclick="loadZipContents(\'' + fileId + '\')">根目录</span>';
            let currentPath = '';
            
            pathParts.forEach((part, index) => {
                currentPath += (currentPath ? '/' : '') + part;
                const isLast = index === pathParts.length - 1;
                
                if (isLast) {
                    breadcrumbHtml += ' <span class="breadcrumb-separator">/</span> <span class="breadcrumb-item current">' + part + '</span>';
                } else {
                    breadcrumbHtml += ' <span class="breadcrumb-separator">/</span> <span class="breadcrumb-item" onclick="loadZipContents(\'' + fileId + '\', \'' + currentPath + '\')">' + part + '</span>';
                }
            });
            
            breadcrumbContainer.innerHTML = breadcrumbHtml;
        }
        
        // 解压ZIP文件
        async function extractZip() {
            const fileId = selectedFileId;
            try {
                const response = await fetch('/extract_zip.php?id=' + fileId);
                const data = await response.json();
                
                if (data.success) {
                    showModal('成功', 'ZIP文件解压成功');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showModal('错误', '解压失败: ' + data.message);
                }
            } catch (error) {
                console.error('Error extracting ZIP:', error);
                showModal('错误', '解压失败: ' + error.message);
            }
        }
        
        // 加载文档预览
        function loadDocumentPreview(fileId, extension) {
            const docsFrame = document.getElementById('docsFrame');
            
            if (extension === 'pdf') {
                // PDF文件直接预览
                docsFrame.src = window.location.origin + '/fileview?id=' + fileId;
            } else if (extension === 'txt') {
                // 文本文件直接预览
                docsFrame.src = window.location.origin + '/fileview?id=' + fileId;
            } else {
                // 其他文档类型显示OnlyOffice预览提示
                docsFrame.src = 'about:blank';
                const docsViewer = document.getElementById('docsViewer');
                docsViewer.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column; color: #666;">
                        <i class="fas fa-file" style="font-size: 4em; margin-bottom: 20px;"></i>
                        <h3>文档预览</h3>
                        <p>点击下方按钮在OnlyOffice中打开此文档</p>
                        <button onclick="openInOnlyOffice()" class="preview-control-btn" style="margin-top: 20px;">
                            <i class="fas fa-external-link-alt"></i> 在OnlyOffice中打开
                        </button>
                    </div>
                `;
            }
        }
        
        // 在OnlyOffice中打开文档
        function openInOnlyOffice() {
            const fileId = selectedFileId;
            const fileUrl = window.location.origin + '/fileview?id=' + fileId;
            
            // 这里需要配置OnlyOffice服务器地址
            const onlyOfficeUrl = 'https://documentserver.example.com/'; // 请替换为实际的OnlyOffice服务器地址
            const editorUrl = `${onlyOfficeUrl}web-apps/apps/api/documents/api.js`;
            
            // 打开新窗口或嵌入OnlyOffice编辑器
            window.open(`${onlyOfficeUrl}web-apps/apps/documenteditor/main.html?file=${encodeURIComponent(fileUrl)}`, '_blank');
        }
        
        // 进入文件夹函数
        function enterFolder(folderPath) {
            console.log('进入文件夹:', folderPath);
            
            // 更新当前路径并重新加载ZIP内容
            currentPath = folderPath;
            loadZipContents(currentZipFileId, currentPath);
            
            // 更新面包屑导航
            updateBreadcrumb(currentPath, currentZipFileId);
        }
        
        // 文件大小格式化函数
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // 下载文件函数
        function downloadFile(fileId = null) {
            const id = fileId || selectedFileId;
            if (!id) {
                showModal('错误', '请先选择文件');
                return;
            }
            
            // 创建下载链接并触发下载
            const downloadUrl = window.location.origin + '/fileview?id=' + id + '&download=1';
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = true;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        async function renameFileContext() {
            const fileItem = document.querySelector(`[data-file-id="${selectedFileId}"]`);
            const currentName = fileItem.querySelector('.file-name').textContent;
            
            // 使用弹窗输入新文件名
            const newName = await showInputModal('重命名文件', '请输入新的文件名（不含扩展名）:', currentName.replace(/\.[^/.]+$/, ""));
            
            if (newName !== null && newName.trim() !== '') {
                await renameFile(selectedFileId, newName.trim());
            }
        }
        
        async function deleteFile() {
            const confirmed = await showConfirm('确认删除', '确定要删除这个文件吗？');
            
            if (confirmed) {
                try {
                    const response = await fetch('/delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({file_id: selectedFileId})
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showModal('成功', '文件删除成功');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showModal('错误', '删除失败: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showModal('错误', '删除失败: ' + error.message);
                }
            }
        }

        // 重命名文件（直接调用）
        async function renameFile(fileId, newName) {
            if (newName !== null && newName.trim() !== '') {
                try {
                    const response = await fetch('/rename', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ 
                            file_id: fileId, 
                            new_name: newName.trim() 
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showModal('成功', '文件重命名成功');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showModal('错误', '重命名失败: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showModal('错误', '重命名失败: ' + error.message);
                }
            }
        }


        
        // 上传功能
        function openUploadModal() {
            const modal = document.getElementById('uploadModal');
            const content = modal.querySelector('.upload-content');
            
            // 移除淡出类，添加淡入动画
            modal.classList.remove('fade-out');
            content.classList.remove('fade-out');
            
            modal.style.display = 'flex';
        }
        
        function closeUploadModal() {
            const modal = document.getElementById('uploadModal');
            const content = modal.querySelector('.upload-content');
            
            // 添加淡出动画
            modal.classList.add('fade-out');
            content.classList.add('fade-out');
            
            // 动画结束后隐藏
            setTimeout(() => {
                modal.style.display = 'none';
                modal.classList.remove('fade-out');
                content.classList.remove('fade-out');
                
                // 重置上传区域
                const uploadArea = document.querySelector('.upload-area');
                uploadArea.innerHTML = `
                    <i class="fas fa-cloud-upload-alt" style="font-size: 3em; color: #ccc; margin-bottom: 15px;"></i>
                    <p>点击选择文件或拖拽文件到此处</p>
                    <p style="font-size: 0.9em; color: #999; margin-top: 10px;">支持单个或多个文件上传</p>
                `;
                selectedFiles = [];
                document.getElementById('fileInput').value = '';
            }, 300);
        }
        
        let selectedFiles = [];
        
        function handleFileSelect(files) {
            selectedFiles = Array.from(files);
            
            // 显示选中的文件
            const uploadArea = document.querySelector('.upload-area');
            uploadArea.innerHTML = `
                <i class="fas fa-check-circle" style="font-size: 3em; color: #4CAF50; margin-bottom: 15px;"></i>
                <p>已选择 ${selectedFiles.length} 个文件</p>
                <div style="max-height: 150px; overflow-y: auto; margin-top: 10px; text-align: left;">
                    ${selectedFiles.map(file => 
                        `<div style="padding: 5px 0; border-bottom: 1px solid #f0f0f0;">
                            <i class="fas fa-file" style="margin-right: 8px; color: #666;"></i>
                            ${file.name} (${formatFileSize(file.size)})
                        </div>`
                    ).join('')}
                </div>
                <p style="font-size: 0.9em; color: #999; margin-top: 10px;">点击"开始上传"按钮上传文件</p>
            `;
        }
        
        function uploadFiles() {
            if (selectedFiles.length === 0) {
                showModal('提示', '请先选择文件');
                return;
            }
            
            const uploadArea = document.querySelector('.upload-area');
            uploadArea.innerHTML = `
                <i class="fas fa-spinner fa-spin" style="font-size: 3em; color: #000; margin-bottom: 15px;"></i>
                <p>正在上传文件...</p>
                <div id="uploadProgress" style="width: 100%; background: #f0f0f0; border-radius: 7px; height: 8px; margin: 10px 0;">
                    <div id="progressBar" style="width: 0%; height: 100%; background: #000; border-radius: 7px; transition: width 0.3s;"></div>
                </div>
                <p id="uploadStatus" style="font-size: 0.9em; color: #666666;">准备上传...</p>
            `;
            
            // 逐个上传文件
            uploadFile(0);
        }
        
        function uploadFile(index) {
            if (index >= selectedFiles.length) {
                // 所有文件上传完成
                setTimeout(() => {
                    closeUploadModal();
                    location.reload(); // 刷新页面显示新文件
                }, 1000);
                return;
            }
            
            const file = selectedFiles[index];
            const formData = new FormData();
            formData.append('file', file);
            
            // 更新上传状态
            document.getElementById('uploadStatus').textContent = `正在上传: ${file.name} (${index + 1}/${selectedFiles.length})`;
            
            // 计算进度
            const progress = (index / selectedFiles.length) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            
            fetch('upload', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP错误: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        // 上传成功，继续下一个文件
                        uploadFile(index + 1);
                    } else {
                        showModal('错误', '上传失败: ' + data.error);
                        closeUploadModal();
                    }
                } catch (e) {
                    // 如果不是有效的JSON，显示原始响应
                    console.error('JSON解析错误:', e, '响应内容:', text);
                    showModal('错误', '服务器响应格式错误: ' + text.substring(0, 200));
                    closeUploadModal();
                }
            });
        }
        
        // 拖拽上传
        const uploadArea = document.querySelector('.upload-area');
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#000';
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = '#ccc';
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#ccc';
            handleFileSelect(e.dataTransfer.files);
        });
        
        // 移动端侧边栏功能
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileCloseBtn = document.getElementById('mobileCloseBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        // 禁用移动端文本选择，但保留点击功能
        if (window.innerWidth <= 768) {
            // 阻止长按选择文本（只阻止长按，不阻止点击）
            document.addEventListener('contextmenu', function(e) {
                if (!e.target.matches('input, textarea, select')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // 阻止选择文本
            document.addEventListener('selectstart', function(e) {
                if (!e.target.matches('input, textarea, select')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // 阻止拖拽行为
            document.addEventListener('dragstart', function(e) {
                e.preventDefault();
                return false;
            });
        }
        
        // 展开侧边栏
        function openSidebar() {
            sidebar.classList.add('mobile-open');
            sidebarOverlay.classList.add('mobile-open');
            document.body.style.overflow = 'hidden'; // 防止背景滚动
        }
        
        // 关闭侧边栏
        function closeSidebar() {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('mobile-open');
            document.body.style.overflow = ''; // 恢复滚动
        }
        
        // 绑定事件
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', openSidebar);
        }
        
        if (mobileCloseBtn) {
            mobileCloseBtn.addEventListener('click', closeSidebar);
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }
        
        // 点击侧边栏链接时关闭侧边栏（移动端）
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });
        
        // ESC键关闭侧边栏
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                closeSidebar();
            }
        });
        
        // 窗口大小变化时自动关闭侧边栏
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && sidebar.classList.contains('mobile-open')) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>

<?php
// 文件大小格式化函数
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>