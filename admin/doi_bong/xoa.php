<?php
/** Admin - Xóa đội bóng (chỉ nhận POST từ nút Xóa ở danh sách) - TV2 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_doi_bong.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenTrang(url('/admin/doi_bong/index.php'));
}
kiemTraCsrf();

$id = (int) ($_POST['id'] ?? 0);
$loi = dbXoa($id);
if ($loi === null) {
    datThongBao('ok', 'Đã xóa đội.');
} else {
    datThongBao('loi', $loi);
}
chuyenTrang(url('/admin/doi_bong/index.php'));
