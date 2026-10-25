<?php
/**
 * Kết nối CSDL qlbongro (XAMPP mặc định: user root, không mật khẩu)
 * Nếu nhóm đã có file kết nối chung thì dùng file của nhóm,
 * chỉ cần đảm bảo có hàm ketNoi() trả về PDO và hằng BASE_URL.
 */

// Tên thư mục dự án trong htdocs. VD: htdocs/BaiNhom  =>  '/BaiNhom'
if (!defined('BASE_URL')) {
    define('BASE_URL', '/BaiNhom');
}

// Máy nào đặt mật khẩu MySQL khác mặc định thì tạo file config/db.local.php
// (đã có trong .gitignore), ví dụ:  <?php define('DB_PASS', 'matkhau');
if (is_file(__DIR__ . '/db.local.php')) {
    require_once __DIR__ . '/db.local.php';
}
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_NAME') || define('DB_NAME', 'qlbongro');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');

function ketNoi(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}
