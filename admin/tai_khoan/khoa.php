<?php
/** Admin - Khóa / mở khóa tài khoản (chỉ nhận POST từ nút ở danh sách) - TV1 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_tai_khoan.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenTrang(url('/admin/tai_khoan/index.php'));
}
kiemTraCsrf();

$id   = (int) ($_POST['id'] ?? 0);
$khoa = ($_POST['khoa'] ?? '') === '1';
$tk   = tkLay($id);

$loi = tkDoiKhoa($id, $khoa, (int) $_SESSION['ma_tai_khoan']);
if ($loi === null) {
    datThongBao('ok', ($khoa ? 'Đã khóa' : 'Đã mở khóa') . ' tài khoản ' . $tk['ten_dang_nhap'] . '.');
} else {
    datThongBao('loi', $loi);
}
chuyenTrang(url('/admin/tai_khoan/index.php'));
