<?php
/** Admin - Hủy kết quả 1 trận (chỉ nhận POST) - TV4 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_ket_qua.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenTrang(url('/admin/ket_qua/index.php'));
}
kiemTraCsrf();

$id   = (int) ($_POST['id'] ?? 0);
$tran = kqLayTran($id);
$loi  = kqHuy($id);
if ($loi === null) {
    datThongBao('ok', 'Đã hủy kết quả, trận trở về "Chưa đấu".');
} else {
    datThongBao('loi', $loi);
}
chuyenTrang(url('/admin/ket_qua/index.php') . ($tran ? '?mua=' . (int) $tran['ma_mua_giai'] : ''));
