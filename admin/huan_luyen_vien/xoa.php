<?php
/** Admin - Xóa huấn luyện viên (chỉ nhận POST từ nút Xóa ở danh sách) - TV2 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_huan_luyen_vien.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenTrang(url('/admin/huan_luyen_vien/index.php'));
}
kiemTraCsrf();

$id = (int) ($_POST['id'] ?? 0);
$loi = hlvXoa($id);
if ($loi === null) {
    datThongBao('ok', 'Đã xóa HLV.');
} else {
    datThongBao('loi', $loi);
}
chuyenTrang(url('/admin/huan_luyen_vien/index.php'));
