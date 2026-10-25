<?php
/**
 * File khởi tạo dùng chung. Trang mới require file này ở dòng đầu:
 *   - Trang ở thư mục gốc:               require_once __DIR__ . '/includes/init.php';
 *   - Trang trong 1 thư mục (VD doi_bong/): require_once __DIR__ . '/../includes/init.php';
 *   - Trang trong admin/ten_module/:      require_once __DIR__ . '/../../includes/init.php';
 *
 * Sau đó có sẵn:
 *   - $pdo (kết nối database; hoặc gọi ketNoi() ở bất kỳ đâu)
 *   - session, yeuCauAdmin(), yeuCauCauThu(), oCsrf(), kiemTraCsrf(),
 *     datThongBao(), chuyenTrang()        (từ includes/kiem_tra_quyen.php)
 *   - e(), url(), daDangNhap(), laAdmin(), laCauThu(), tenDangNhap() (từ includes/ham_chung.php)
 */
require_once __DIR__ . '/kiem_tra_quyen.php'; // nạp config/db.php + mở session
require_once __DIR__ . '/ham_chung.php';

try {
    $pdo = ketNoi();
} catch (PDOException $ex) {
    http_response_code(500);
    exit('Không kết nối được database "' . DB_NAME . '". Kiểm tra XAMPP đã bật MySQL '
        . 'và đã import database/qlbongro.sql chưa.<br>Chi tiết: ' . htmlspecialchars($ex->getMessage()));
}
