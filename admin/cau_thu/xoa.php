<?php
/** Admin - Xóa cầu thủ (chỉ nhận POST từ nút Xóa ở danh sách) (TV3) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_cau_thu.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $lyDo = xoaCauThu((int) ($_POST['id'] ?? 0));
    datThongBao($lyDo === null ? 'ok' : 'loi', $lyDo ?? 'Đã xóa cầu thủ.');
}
chuyenTrang(url('/admin/cau_thu/index.php'));
