<?php
/** Admin - Xóa trận (chỉ nhận POST từ nút Xóa ở danh sách) - TV4 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_lich_thi_dau.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenTrang(url('/admin/lich_thi_dau/index.php'));
}
kiemTraCsrf();

$id   = (int) ($_POST['id'] ?? 0);
$tran = ltdLay($id);
$loi  = ltdXoa($id);
if ($loi === null) {
    datThongBao('ok', 'Đã xóa trận đấu.');
} else {
    datThongBao('loi', $loi);
}
chuyenTrang(url('/admin/lich_thi_dau/index.php') . ($tran ? '?mua=' . (int) $tran['ma_mua_giai'] : ''));
