<?php
/** Admin - Xóa cầu thủ (chỉ nhận POST từ nút Xóa ở danh sách) */
require_once __DIR__ . '/../../includes/ham_cau_thu.php';
require_once __DIR__ . '/../../includes/kiem_tra_quyen.php';
yeuCauAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenTrang('index.php');
}
kiemTraCsrf();

$lyDo = xoaCauThu((int) ($_POST['id'] ?? 0));
datThongBao($lyDo === null ? 'ok' : 'loi', $lyDo ?? 'Đã xóa cầu thủ.');
chuyenTrang('index.php');
