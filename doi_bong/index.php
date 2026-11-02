<?php
/** Trang Khách - Danh sách đội bóng theo mùa (?mua=), lọc Miền Đông / Miền Tây (?mien=) (TV2) */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_trang_khach.php';
require_once __DIR__ . '/../includes/giao_dien_moi.php';   // màu đội, huy hiệu (giao diện mới)

$dsMua    = tkhLayDsMua();
$mua      = tkhChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
$maMua    = $mua ? (int) $mua['ma_mua_giai'] : null;
$coPhanBang = $maMua !== null && tkhDemPhanBang($maMua) > 0;
$dsHoiNghi  = tkhLayHoiNghi();

// ?mien=1 hoặc 2 (mã hội nghị). Chỉ lọc được khi mùa đang xem đã phân bảng.
$mien = ctype_digit((string) ($_GET['mien'] ?? '')) ? (int) $_GET['mien'] : null;
if (!$coPhanBang || !in_array($mien, array_map('intval', array_column($dsHoiNghi, 'ma_hoi_nghi')), true)) {
    $mien = null;
}

$dsDoi = tkhLayDanhSachDoi($maMua, $mien);

/** Link giữ mùa đang xem */
$linkMua = fn(string $duongDan, array $thamSo = []) => url($duongDan) . '?' . http_build_query(array_filter(['mua' => $maMua] + $thamSo));

// Gom theo khu vực để hiển thị từng nhóm (khi chưa phân bảng: 1 nhóm "Tất cả đội")
$theoKhuVuc = [];
foreach ($dsDoi as $doi) {
    $san = tkhLaySanNhaMua($doi, $mua);   // tên sân đúng theo mùa đang xem
    $doi['ten_san'] = $san ? implode(' → ', $san['ten_trong_mua']) : null;
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
    <div class="d-flex flex-wrap gap-2">
    <?php if (count($dsMua) > 1): ?>
        <form method="get">
            <?php if ($mien !== null): ?><input type="hidden" name="mien" value="<?= (int) $mien ?>"><?php endif; ?>
            <select class="form-select w-auto" name="mua" aria-label="Mùa giải" onchange="this.form.submit()">
                <?php foreach ($dsMua as $m): ?>
                    <option value="<?= (int) $m['ma_mua_giai'] ?>"<?= (int) $m['ma_mua_giai'] === $maMua ? ' selected' : '' ?>>Mùa <?= e($m['ten_mua_giai']) ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button class="btn btn-outline-secondary" type="submit">Xem</button></noscript>
        </form>
    <?php endif; ?>
    <div class="btn-group" role="group" aria-label="Lọc theo miền">
        <a class="btn btn-outline-primary<?= $mien === null ? ' active' : '' ?>" href="<?= e($linkMua('/doi_bong/index.php')) ?>">Tất cả</a>
        <?php foreach ($dsHoiNghi as $hn): ?>
            <?php if ($coPhanBang): ?>
                <a class="btn btn-outline-primary<?= $mien === (int) $hn['ma_hoi_nghi'] ? ' active' : '' ?>"
                   href="<?= e($linkMua('/doi_bong/index.php', ['mien' => (int) $hn['ma_hoi_nghi']])) ?>"><?= e($hn['ten_hoi_nghi']) ?></a>
            <?php else: ?>
                <span class="btn btn-outline-primary disabled"><?= e($hn['ten_hoi_nghi']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    </div>
</div>

<?php if (!$coPhanBang): ?>
    <div class="alert alert-info py-2">
        <i class="bi bi-info-circle"></i>
        <?= $mua ? 'Mùa ' . e($mua['ten_mua_giai']) . ' chưa phân bảng' : 'Chưa có mùa giải nào' ?>
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
                <a class="the-doi" href="<?= e($linkMua('/doi_bong/chi_tiet.php', ['id' => (int) $doi['ma_doi']])) ?>">
                    <span class="dai-mau" style="background: <?= e(gdMauDoi($doi['ten_viet_tat'])) ?>">
                        <span class="vong-logo"><?= gdHuyHieu($doi['logo'], $doi['ten_viet_tat'], $doi['ten_doi'], 48) ?></span>
                        <span class="chu-mo" aria-hidden="true" style="color: <?= e(gdMauPhu($doi['ten_viet_tat'])) ?>"><?= e($doi['ten_viet_tat']) ?></span>
                    </span>
                    <span class="noi-dung">
                        <span class="phu"><?= e($doi['thanh_pho']) ?></span>
                        <span class="ten"><?= e($doi['ten_doi']) ?></span>
                        <?php if ($doi['ten_san'] !== null): ?>
                            <span class="phu fw-normal"><i class="bi bi-building"></i> <?= e($doi['ten_san']) ?></span>
                        <?php endif; ?>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
