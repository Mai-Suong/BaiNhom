<?php
/** Trang Khách - Kết quả các trận đã đấu kèm điểm từng hiệp; ?tran=ID xem 1 trận (TV4) */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_trang_thi_dau.php';

$maTran = (int) ($_GET['tran'] ?? 0);
$motTran = $maTran ? ttdLayMotKetQua($maTran) : null;

$dsMua = ttdLayDsMua();
$mua   = ttdChonMua($dsMua, (int) ($motTran['ma_mua_giai'] ?? ($_GET['mua'] ?? 0)));
$maMua = $mua ? (int) $mua['ma_mua_giai'] : 0;
$dsDoi = ttdLayDsDoi();
$maDoi = (int) ($_GET['doi'] ?? 0);

if ($motTran) {
    $dsTran = [$motTran];
    $tong = 1;
    $soTrang = $trang = 1;
} else {
    $tong    = $mua ? ttdDemKetQua($maMua, $maDoi) : 0;
    $soTrang = max(1, (int) ceil($tong / TTD_KQ_MOI_TRANG));
    $trang   = min($soTrang, max(1, (int) ($_GET['trang'] ?? 1)));
    $dsTran  = $mua ? ttdLayKetQua($maMua, $maDoi, $trang) : [];
}

$tieuDe = $motTran ? $motTran['vt_khach'] . ' ' . $motTran['diem_doi_khach'] . ' - ' . $motTran['diem_doi_nha'] . ' ' . $motTran['vt_nha'] : 'Kết quả';
$menu   = 'ket_qua';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0"><?= $motTran ? 'Kết quả trận đấu' : 'Kết quả' . ($mua ? ' mùa ' . e($mua['ten_mua_giai']) : '') ?></h1>
    <?php if ($motTran): ?>
        <a class="btn btn-outline-secondary" href="<?= url('/ket_qua/index.php?mua=' . $maMua) ?>"><i class="bi bi-arrow-left"></i> Tất cả kết quả</a>
    <?php elseif ($dsMua): ?>
        <form class="d-flex flex-wrap gap-2" method="get">
            <select class="form-select w-auto" name="mua" aria-label="Mùa giải">
                <?php foreach ($dsMua as $m): ?>
                    <option value="<?= (int) $m['ma_mua_giai'] ?>" <?= (int) $m['ma_mua_giai'] === $maMua ? 'selected' : '' ?>><?= e($m['ten_mua_giai']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select w-auto" name="doi" aria-label="Đội">
                <option value="">Tất cả đội</option>
                <?php foreach ($dsDoi as $doi): ?>
                    <option value="<?= (int) $doi['ma_doi'] ?>" <?= (int) $doi['ma_doi'] === $maDoi ? 'selected' : '' ?>><?= e($doi['ten_doi']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Xem</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($maTran && !$motTran): ?>
    <div class="alert alert-warning">Không tìm thấy kết quả của trận này (có thể trận chưa đấu).</div>
<?php endif; ?>
<?php if (!$dsTran && !$maTran): ?>
    <div class="card card-body text-muted"><?= $mua ? 'Chưa có trận nào có kết quả.' : 'Chưa có mùa giải nào.' ?></div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($dsTran as $t): ?>
        <?php
        $soHiep = max(4, $t['hiep'] ? max(array_keys($t['hiep'])) : 4);
        $khachThang = (int) $t['diem_doi_khach'] > (int) $t['diem_doi_nha'];
        ?>
        <div class="<?= $motTran ? 'col-12' : 'col-lg-6' ?>">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between small">
                    <span><?= e(ttdHienNgay($t['thoi_gian'])) ?> · <?= e(ttdGio($t['thoi_gian'])) ?></span>
                    <span class="text-muted">
                        <?= e(TTD_GIAI_DOAN[$t['giai_doan']] ?? '') ?><?= $t['tran_so_trong_loat'] ? ' · Trận ' . (int) $t['tran_so_trong_loat'] : '' ?>
                        <?= $soHiep > 4 ? ' · ' . e(ttdTenHiep($soHiep)) : '' ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle text-center mb-0">
                        <thead>
                        <tr class="small text-muted">
                            <th class="text-start ps-3">Đội</th>
                            <?php for ($h = 1; $h <= $soHiep; $h++): ?><th><?= e(ttdTenHiep($h)) ?></th><?php endfor; ?>
                            <th>Tổng</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (['khach' => $khachThang, 'nha' => !$khachThang] as $ben => $thang): ?>
                            <tr class="<?= $thang ? 'fw-bold' : '' ?>">
                                <td class="text-start ps-3 text-nowrap">
                                    <a class="text-reset text-decoration-none" href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $t['ma_doi_' . $ben]) ?>"
                                       title="<?= e($t['ten_doi_' . $ben]) ?>"><?= e($motTran ? $t['ten_doi_' . $ben] : $t['vt_' . $ben]) ?></a>
                                    <?php if ($ben === 'nha'): ?><span class="text-muted small fw-normal">(nhà)</span><?php endif; ?>
                                </td>
                                <?php for ($h = 1; $h <= $soHiep; $h++): ?>
                                    <td class="fw-normal"><?= isset($t['hiep'][$h][$ben]) ? (int) $t['hiep'][$h][$ben] : '' ?></td>
                                <?php endfor; ?>
                                <td class="fs-5"><?= (int) $t['diem_doi_' . $ben] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted d-flex justify-content-between flex-wrap gap-2">
                    <span><i class="bi bi-building"></i> <?= e($t['ten_san'] ?? 'Chưa rõ sân') ?>
                        <?= $t['khan_gia'] !== null ? ' · ' . number_format((int) $t['khan_gia'], 0, ',', '.') . ' khán giả' : '' ?></span>
                    <?php if (!$motTran): ?>
                        <a href="<?= url('/ket_qua/index.php?tran=' . (int) $t['ma_tran']) ?>">Chi tiết</a>
                    <?php endif; ?>
                </div>
                <?php if ($motTran && $t['trong_tai']): ?>
                    <div class="card-body small">
                        <span class="fw-semibold">Trọng tài:</span>
                        <?= e(implode(', ', array_map(fn($tt) => $tt['ho_ten'] . ($tt['so_ao'] !== null ? ' (#' . $tt['so_ao'] . ')' : ''), $t['trong_tai']))) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!$motTran && $soTrang > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($i = 1; $i <= $soTrang; $i++): ?>
                <li class="page-item<?= $i === $trang ? ' active' : '' ?>">
                    <a class="page-link" href="<?= e(url('/ket_qua/index.php') . '?' . http_build_query(array_filter(['mua' => $maMua, 'doi' => $maDoi, 'trang' => $i]))) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
<?php if (!$motTran && $tong): ?>
    <p class="text-muted small text-center">Tổng: <?= $tong ?> trận. Đội thắng in đậm.</p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
