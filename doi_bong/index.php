<?php
/** Trang Khách - Danh sách đội bóng, lọc Miền Đông / Miền Tây (TV2) */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_trang_khach.php';

$mua      = tv2LayMuaHienTai();
$maMua    = $mua ? (int) $mua['ma_mua_giai'] : null;
$coPhanBang = $maMua !== null && tkDemPhanBang($maMua) > 0;
$dsHoiNghi  = tkLayHoiNghi();

// ?mien=1 hoặc 2 (mã hội nghị). Chỉ lọc được khi mùa hiện tại đã phân bảng.
$mien = ctype_digit((string) ($_GET['mien'] ?? '')) ? (int) $_GET['mien'] : null;
if (!$coPhanBang || !in_array($mien, array_map('intval', array_column($dsHoiNghi, 'ma_hoi_nghi')), true)) {
    $mien = null;
}

$dsDoi = tkLayDanhSachDoi($maMua, $mien);

// Gom theo khu vực để hiển thị từng nhóm (khi chưa phân bảng: 1 nhóm "Tất cả đội")
$theoKhuVuc = [];
foreach ($dsDoi as $doi) {
    $nhom = $doi['ten_khu_vuc'] !== null ? $doi['ten_hoi_nghi'] . ' · Khu vực ' . $doi['ten_khu_vuc']
          : ($coPhanBang ? 'Chưa phân bảng' : 'Tất cả đội');
    $theoKhuVuc[$nhom][] = $doi;
}

$tieuDe = 'Danh sách đội bóng';
$menu   = 'doi_bong';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Đội bóng<?= $mua ? ' mùa ' . e($mua['ten_mua_giai']) : '' ?></h1>
    <div class="btn-group" role="group" aria-label="Lọc theo miền">
        <a class="btn btn-outline-primary<?= $mien === null ? ' active' : '' ?>" href="<?= url('/doi_bong/index.php') ?>">Tất cả</a>
        <?php foreach ($dsHoiNghi as $hn): ?>
            <?php if ($coPhanBang): ?>
                <a class="btn btn-outline-primary<?= $mien === (int) $hn['ma_hoi_nghi'] ? ' active' : '' ?>"
                   href="<?= url('/doi_bong/index.php?mien=' . (int) $hn['ma_hoi_nghi']) ?>"><?= e($hn['ten_hoi_nghi']) ?></a>
            <?php else: ?>
                <span class="btn btn-outline-primary disabled"><?= e($hn['ten_hoi_nghi']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$coPhanBang): ?>
    <div class="alert alert-info py-2">
        <i class="bi bi-info-circle"></i>
        <?= $mua ? 'Mùa ' . e($mua['ten_mua_giai']) . ' chưa phân bảng' : 'Chưa có mùa giải hiện tại' ?>
        nên chưa lọc được theo Miền Đông / Miền Tây. Đang hiển thị tất cả đội.
    </div>
<?php endif; ?>

<?php if (!$dsDoi): ?>
    <div class="card card-body text-muted">Chưa có đội bóng nào.</div>
<?php endif; ?>

<?php foreach ($theoKhuVuc as $nhom => $dsTrongNhom): ?>
    <h2 class="h5 mt-4 mb-3"><?= e($nhom) ?> <span class="text-muted fw-normal">(<?= count($dsTrongNhom) ?>)</span></h2>
    <div class="row g-3">
        <?php foreach ($dsTrongNhom as $doi): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a class="card h-100 shadow-sm text-decoration-none text-reset"
                   href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $doi['ma_doi']) ?>">
                    <div class="card-body text-center">
                        <?php if ($doi['logo']): ?>
                            <img src="<?= e(url('/' . $doi['logo'])) ?>" alt="Logo <?= e($doi['ten_doi']) ?>"
                                 width="72" height="72" class="object-fit-contain mb-2">
                        <?php else: ?>
                            <div class="rounded-circle bg-nba text-white fw-bold d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width: 72px; height: 72px"><?= e($doi['ten_viet_tat']) ?></div>
                        <?php endif; ?>
                        <div class="fw-semibold"><?= e($doi['ten_doi']) ?></div>
                        <div class="small text-muted"><?= e($doi['thanh_pho']) ?></div>
                        <?php if ($doi['ten_san'] !== null): ?>
                            <div class="small text-muted"><i class="bi bi-building"></i> <?= e($doi['ten_san']) ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
