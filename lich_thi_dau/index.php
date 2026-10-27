<?php
/** Trang Khách - Lịch thi đấu theo tháng, lọc theo đội (TV4) */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_trang_thi_dau.php';

$dsMua = ttdLayDsMua();
$mua   = ttdChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
$maMua = $mua ? (int) $mua['ma_mua_giai'] : 0;
$dsDoi = ttdLayDsDoi();
$maDoi = (int) ($_GET['doi'] ?? 0);
if (!in_array($maDoi, array_map('intval', array_column($dsDoi, 'ma_doi')), true)) {
    $maDoi = 0;
}

$dsThang = $mua ? ttdLayDsThang($maMua, $maDoi) : [];
$thang   = in_array($_GET['thang'] ?? '', $dsThang, true) ? $_GET['thang'] : ttdThangMacDinh($maMua, $maDoi, $dsThang);
$theoNgay = $thang ? ttdLayLichThang($maMua, $maDoi, $thang) : [];
$viTri   = $thang ? array_search($thang, $dsThang, true) : false;

/** Link giữ bộ lọc, đổi tháng */
function linkThang(int $maMua, int $maDoi, string $thang): string
{
    return url('/lich_thi_dau/index.php') . '?' . http_build_query(array_filter(['mua' => $maMua, 'doi' => $maDoi, 'thang' => $thang]));
}

$tieuDe = 'Lịch thi đấu';
$menu   = 'lich';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Lịch thi đấu<?= $mua ? ' mùa ' . e($mua['ten_mua_giai']) : '' ?></h1>
    <?php if ($dsMua): ?>
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

<?php if (!$mua): ?>
    <div class="card card-body text-muted">Chưa có mùa giải nào.</div>
<?php elseif (!$thang): ?>
    <div class="card card-body text-muted">Mùa <?= e($mua['ten_mua_giai']) ?> chưa có trận đấu<?= $maDoi ? ' của đội này' : '' ?>.</div>
<?php else: ?>
    <!-- Chuyển tháng -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <?php if ($viTri > 0): ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(linkThang($maMua, $maDoi, $dsThang[$viTri - 1])) ?>"><i class="bi bi-chevron-left"></i> <?= e(ttdHienThang($dsThang[$viTri - 1])) ?></a>
        <?php else: ?><span></span><?php endif; ?>
        <h2 class="h5 mb-0"><?= e(ttdHienThang($thang)) ?></h2>
        <?php if ($viTri < count($dsThang) - 1): ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(linkThang($maMua, $maDoi, $dsThang[$viTri + 1])) ?>"><?= e(ttdHienThang($dsThang[$viTri + 1])) ?> <i class="bi bi-chevron-right"></i></a>
        <?php else: ?><span></span><?php endif; ?>
    </div>

    <?php foreach ($theoNgay as $ngay => $dsTran): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold"><?= e(ttdHienNgay($ngay)) ?> <span class="text-muted fw-normal">· <?= count($dsTran) ?> trận</span></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                    <?php foreach ($dsTran as $t): ?>
                        <?php $daDau = $t['trang_thai'] === 'da_dau';
                              $khachThang = $daDau && (int) $t['diem_doi_khach'] > (int) $t['diem_doi_nha']; ?>
                        <tr>
                            <td class="text-muted text-nowrap" style="width: 70px"><?= e(ttdGio($t['thoi_gian'])) ?></td>
                            <td class="text-end text-nowrap" style="width: 35%">
                                <a class="text-reset text-decoration-none<?= $khachThang ? ' fw-bold' : '' ?>" href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $t['ma_doi_khach']) ?>"><?= e($t['ten_doi_khach']) ?></a>
                            </td>
                            <td class="text-center text-nowrap" style="width: 110px">
                                <?php if ($daDau): ?>
                                    <a class="badge text-bg-dark text-decoration-none fs-6" href="<?= url('/ket_qua/index.php?tran=' . (int) $t['ma_tran']) ?>"
                                       title="Xem điểm từng hiệp"><?= (int) $t['diem_doi_khach'] ?> - <?= (int) $t['diem_doi_nha'] ?></a>
                                <?php elseif ($t['trang_thai'] === 'hoan'): ?>
                                    <span class="badge text-bg-warning">Hoãn</span>
                                <?php else: ?>
                                    <span class="text-muted">@</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap" style="width: 35%">
                                <a class="text-reset text-decoration-none<?= $daDau && !$khachThang ? ' fw-bold' : '' ?>" href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $t['ma_doi_nha']) ?>"><?= e($t['ten_doi_nha']) ?></a>
                            </td>
                            <td class="small text-muted d-none d-md-table-cell">
                                <?= e($t['ten_san'] ?? '') ?>
                                <?php if ($t['giai_doan'] !== 'thuong'): ?>
                                    <span class="badge text-bg-danger ms-1"><?= e(TTD_GIAI_DOAN[$t['giai_doan']] ?? '') ?><?= $t['tran_so_trong_loat'] ? ' · Trận ' . (int) $t['tran_so_trong_loat'] : '' ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
    <p class="text-muted small">Đội khách bên trái, đội nhà bên phải. Bấm vào tỉ số để xem điểm từng hiệp.</p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
