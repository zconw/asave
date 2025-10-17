# 程序权益说明与搭建指南

## 一、权益与版权说明



1. **开发与版权归属**：本程序由杭州软柠科技有限公司开发，与 “软柠账号” 捆绑申请软件著作权，软著登记号：**2025SR0250034**

2. **使用权限**：

* 允许个人学习、自行使用及团队内部使用，支持二次开发后自用

* 禁止用于商业行为（包括但不限于出售、分发、修改后商用等），开源≠免费

1. **第三方资源声明**：代码中 SVG 图标非本司设计，来源：[icon](https://www.iconfont.cn/collections/detail?cid=52422)[font](https://www.iconfont.cn/collections/detail?cid=52422)[.cn](https://www.iconfont.cn/collections/detail?cid=52422)[ 集合页](https://www.iconfont.cn/collections/detail?cid=52422)

## 二、程序核心信息



* **架构特点**：无预设后台及管理后台，纯代码管理数据库与文件（需手动操作），后台可自行开发

* **技术难度**：整体代码难度低，二次开发便捷

## 三、安装环境要求



| 组件    | 版本要求                | 说明              |
| ----- | ------------------- | --------------- |
| PHP   | 8.2（需安装 Imagick 扩展） | 缩略图压缩依赖此扩展，必须启用 |
| MySQL | 5.7                 | -               |
| Nginx | 1.26.2（其他版本可兼容）     | -               |

## 四、关键配置步骤

### 1. 伪静态配置（Nginx）



```
if (\$request\_uri \~ ^(/\[^?]+).php(\\?.\*)?\$) {

&#x20;   return 301 \$1\$2;

}

if (\$request\_uri \~ ^(/\[^?]+).php\$) {

&#x20;   return 301 \$1;

}

location / {

&#x20;   try\_files \$uri \$uri/ \$uri.php?\$args;

}
```

### 2. 文件目录说明（uploads/）



```
uploads/

├─archive（压缩包）

├─audio（音频文件）

├─docs（文档文件）

├─document（文档文件）

├─files（其他文档）

├─image（图片文件）

├─img（图片文件）

└─video（视频文件）
```

### 3. config.php 配置（必改项）

#### （1）OAuth 登录配置（如需启用）



* **需申请替换的参数**（向软柠开放平台申请，地址：[ope](https://open.rutno.com/)[n.rut](https://open.rutno.com/)[no.co](https://open.rutno.com/)[m](https://open.rutno.com/)）：



```
define('CLIENT\_ID', '53d11e18f8ad3');       // 替换为申请的CLIENT\_ID

define('CLIENT\_SECRET', 'ed02739dd2f6');   // 替换为申请的CLIENT\_SECRET

define('REDIRECT\_URI', 'https://asave.rutno.com/callback'); // 替换为申请的回调地址
```



* **禁止修改的参数**：



```
define('AUTH\_URL', 'https://id.rutno.com/rn-oauth/authorize');

define('TOKEN\_URL', 'https://id.rutno.com/rn-oauth/token');

define('USERINFO\_URL', 'https://id.rutno.com/rn-oauth/userinfo');
```



* **OAuth 使用建议**：文件床类网站易遭流量盗刷，软柠账号中心含安全程序与手机验证，建议保留；仅内部使用可删除（需同时删除 OAuth 系统并适配 MySQL 数据库结构）

#### （2）基础域名配置（必须使用 SSL）



```
define('BASE\_URL', 'https://asave.rutno.com'); // 替换为自身网站域名
```

#### （3）数据库配置



```
define('DB\_HOST', 'localhost');  // 数据库地址

define('DB\_NAME', 'xxx');        // 数据库名（替换为实际名称）

define('DB\_USER', 'xxx');        // 数据库用户名（替换为实际用户名）

define('DB\_PASS', 'xxx');        // 数据库密码（替换为实际密码）
```

> 数据库安装请访问install.php（首次安装需访问，后续无需访问）为了安全，首次访问安装成功后建议删除安装文件。
