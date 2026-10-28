<?php
/**
 * Nhánh đấu Playoff cho Khách xem (TV5). Trang quản trị: /admin/playoff/index.php
 *   /thong_ke/playoff.php          -> mùa hiện tại
 *   /thong_ke/playoff.php?mua=2    -> mùa có ma_mua_giai = 2
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_thong_ke.php';

[$dsMua, $maMua] = tkLayMua($pdo);
$loat   = tkLayLoat($pdo, $maMua);
$tenMua = array_column($dsMua, 'ten_mua_giai', 'ma_mua_giai')[$maMua] ?? '';

$tieuDe = 'Playoff ' . $tenMua;
$menu   = 'thong_ke';
require __DIR__ . '/../includes/header.php';
echo tkCss();
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Thống kê</h1>
        <p class="text-muted mb-0">Playoff <?= e($tenMua) ?> · nhánh đấu và tỉ số từng loạt</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-3">
        <?= tkChonMua($dsMua, $maMua) ?>
        <?php if (laAdmin()): ?>
            <a class="btn btn-sm btn-nba" href="<?= url('/admin/playoff/index.php?mua=' . $maMua) ?>">Quản lý Playoff</a>
        <?php endif; ?>
    </div>
</div>

<?= tkMenu('playoff') ?>

<?php if (!$loat): ?>
    <div class="alert alert-info">Chưa có loạt playoff nào trong mùa này.</div>
<?php endif; ?>

<?= tkSoDoNhanh($loat) ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
