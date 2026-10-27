<?php
/** Admin - Xóa mùa giải (chỉ nhận POST từ nút Xóa ở danh sách) - TV4 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_mua_giai.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenTrang(url('/admin/mua_giai/index.php'));
}
kiemTraCsrf();

$loi = mgXoa((int) ($_POST['id'] ?? 0));
if ($loi === null) {
    datThongBao('ok', 'Đã xóa mùa giải.');
} else {
    datThongBao('loi', $loi);
}
chuyenTrang(url('/admin/mua_giai/index.php'));
