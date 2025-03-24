<?php
// 配置允许删除的根目录（必须存在且可写）
$base_dir = __DIR__ . '/uploads/';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $file_path = isset($_GET['filepath']) ? urldecode($_GET['filepath']) : '';
    $md5 = isset($_GET['md5']) ? urldecode($_GET['md5']) : '';
    
    // 清理路径
    $file_path = str_replace(['../', '..\\', "\0"], '', $file_path);

    // 基础参数验证
    if (empty($file_path) || empty($md5)) {
        http_response_code(400);
        die("参数错误");
    }

    // 生成完整路径
    $full_path = realpath($base_dir . $file_path);
    $base_real = realpath($base_dir);

    // 路径安全检查
    if ($full_path === false || 
        strpos($full_path, $base_real) !== 0 || 
        !file_exists($full_path)) {
        http_response_code(403);
        die("无效的文件路径");
    }

    // 类型检查
    if (!is_file($full_path)) {
        http_response_code(400);
        die("只能删除文件");
    }

    // 如果没有确认参数，显示确认页面
    if (!isset($_GET['confirm'])) {
        $filename = basename($file_path);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>确认删除</title>
            <style>
                .confirmation-box {
                    padding: 20px;
                    border: 1px solid #ccc;
                    max-width: 400px;
                    margin: 50px auto;
                    text-align: center;
                }
                button {
                    padding: 8px 20px;
                    margin: 0 10px;
                }
            </style>
        </head>
        <body>
            <div class="confirmation-box">
                <h2>确认删除</h2>
                <p>确定要删除文件：<br><b><?php echo htmlspecialchars($filename); ?></b> 吗？</p>
                
                <form method="GET">
                    <input type="hidden" name="filepath" value="<?php echo urlencode($file_path); ?>">
                    <input type="hidden" name="md5" value="<?php echo htmlspecialchars($md5); ?>">
                    <input type="hidden" name="confirm" value="true">
                    <button type="submit" style="background-color: #ff4444;color: white;">确认删除</button>
                    <button type="button" onclick="window.history.back()">取消</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    // 如果有确认参数，继续执行删除验证
    $salt = 'your_secure_salt_here';
    $filename = basename($file_path);
    $expected_md5 = md5($filename . $salt);

    if ($md5 !== $expected_md5) {
        http_response_code(403);
        die("MD5校验失败");
    }

    if (!is_writable($full_path)) {
        http_response_code(403);
        die("文件不可写");
    }

    if (unlink($full_path)) {
        echo "文件删除成功";
    } else {
        http_response_code(500);
        echo "文件删除失败";
    }
} else {
    http_response_code(405);
    echo "仅允许GET请求";
}
?>
