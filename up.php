<?php
header('Content-Type: application/json');

try {
    // 1. 验证请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("只允许POST请求", 400);
    }

    // 2. 检查文件是否存在
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("请选择要上传的文件", 400);
    }

    $file = $_FILES['file'];

    // 3. 验证文件大小（100KB限制）
    if ($file['size'] > 100 * 1024) {
        throw new Exception("文件大小不能超过50KB", 400);
    }

    // 4. 双重验证文件类型
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($mimeType !== 'image/png' || $extension !== 'png') {
        throw new Exception("只允许上传PNG格式文件", 400);
    }

    // 5. 创建上传目录
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 6. 生成安全文件名
    $safeFilename = uniqid() . '_' . preg_replace('/[^a-z0-9\.]/', '', $file['name']);
    $targetPath = $uploadDir . $safeFilename;

    // 7. 移动文件到目标位置
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("文件保存失败", 500);
    }

    // 8. 生成MD5校验值（文件名+盐）
    $salt = 'your_secure_salt_here'; // 改成你的复杂盐值
    $checksum = md5($safeFilename . $salt);


    // 9. 返回成功响应
    echo json_encode([
        'message' => '文件上传成功',
        'file' => 'uploads/' . $safeFilename,
        'md5' => $checksum // 添加MD5字段
        // 'size'    => $file['size']
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>