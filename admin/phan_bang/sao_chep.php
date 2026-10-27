<?php
/** Admin - Sao chép phân bảng từ mùa khác sang mùa chưa có phân bảng (chỉ nhận POST) - TV4 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_phan_bang.php';

$denMua = (int) ($_POST['den_mua'] ?? 0);
$urlTrang = url('/admin/phan_bang/index.php') . ($denMua ? '?mua=' . $denMua : '');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenTrang($urlTrang);
}
kiemTraCsrf();

[$so, $loi] = pbSaoChep((int) ($_POST['tu_mua'] ?? 0), $denMua);
if ($loi === null) {
    datThongBao('ok', "Đã sao chép phân bảng $so đội.");
} else {
    datThongBao('loi', $loi);
}
chuyenTrang($urlTrang);
