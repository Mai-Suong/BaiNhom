<?php
/** Admin - Xóa sân thi đấu (chỉ nhận POST từ nút Xóa ở danh sách) - TV2 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_doi_bong.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenTrang(url('/admin/san_thi_dau/index.php'));
}
kiemTraCsrf();

$id = (int) ($_POST['id'] ?? 0);
$loi = sanXoa($id);
if ($loi === null) {
    datThongBao('ok', 'Đã xóa sân.');
} else {
    datThongBao('loi', $loi);
}
chuyenTrang(url('/admin/san_thi_dau/index.php'));
