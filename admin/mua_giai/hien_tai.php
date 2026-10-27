<?php
/** Admin - Đặt mùa hiện tại (chỉ nhận POST từ nút ở danh sách) - TV4 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_mua_giai.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenTrang(url('/admin/mua_giai/index.php'));
}
kiemTraCsrf();

$id  = (int) ($_POST['id'] ?? 0);
$loi = mgDatHienTai($id);
if ($loi === null) {
    $mua = mgLay($id);
    datThongBao('ok', 'Đã đặt ' . $mua['ten_mua_giai'] . ' làm mùa hiện tại.');
} else {
    datThongBao('loi', $loi);
}
chuyenTrang(url('/admin/mua_giai/index.php'));
