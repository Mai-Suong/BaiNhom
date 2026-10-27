<?php
/** Admin - Lịch thi đấu: danh sách trận theo mùa, lọc, phân trang (TV4) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_lich_thi_dau.php';

$dsMua = ltdLayDsMua();
$mua   = ltdChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
$dsDoi = ltdLayDsDoi();

$loc = [
    'mua'        => $mua ? (int) $mua['ma_mua_giai'] : 0,
    'doi'        => (int) ($_GET['doi'] ?? 0),
    'trang_thai' => (string) ($_GET['trang_thai'] ?? ''),
    'giai_doan'  => (string) ($_GET['giai_doan'] ?? ''),
    'ngay'       => (string) ($_GET['ngay'] ?? ''),
];
$tongTran = $mua ? ltdDemTran($loc) : 0;
$soTrang  = max(1, (int) ceil($tongTran / LTD_MOI_TRANG));
$trang    = min($soTrang, max(1, (int) ($_GET['trang'] ?? 1)));
$dsTran   = $mua ? ltdLayDsTran($loc, $trang) : [];

/** Link giữ nguyên bộ lọc, đổi trang */
function ltdLinkTrang(array $loc, int $trang): string
{
    return url('/admin/lich_thi_dau/index.php') . '?' . http_build_query(array_filter($loc + ['trang' => $trang]));
}

$tieuDe = 'Lịch thi đấu';
$menu   = 'lich';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Lịch thi đấu</h1>
    <?php if ($mua): ?>
        <a class="btn btn-primary" href="<?= url('/admin/lich_thi_dau/form.php?mua=' . $loc['mua']) ?>"><i class="bi bi-plus-lg"></i> Thêm trận</a>
    <?php endif; ?>
</div>

<?php if (!$mua): ?>
    <div class="alert alert-warning">
        Chưa có mùa giải nào. Hãy <a href="<?= url('/admin/mua_giai/form.php') ?>">thêm mùa giải</a> trước.
    </div>
    <?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
    <?php return; ?>
<?php endif; ?>

<form class="row g-2 align-items-end mb-3" method="get">
    <div class="col-sm-6 col-lg-2">
        <label class="form-label small mb-1" for="mua">Mùa giải</label>
        <select class="form-select" id="mua" name="mua">
            <?php foreach ($dsMua as $m): ?>
                <option value="<?= (int) $m['ma_mua_giai'] ?>" <?= (int) $m['ma_mua_giai'] === $loc['mua'] ? 'selected' : '' ?>>
                    <?= e($m['ten_mua_giai']) ?><?= (int) $m['la_mua_hien_tai'] === 1 ? ' (hiện tại)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-6 col-lg-3">
        <label class="form-label small mb-1" for="doi">Đội</label>
        <select class="form-select" id="doi" name="doi">
            <option value="">Tất cả đội</option>
            <?php foreach ($dsDoi as $doi): ?>
                <option value="<?= (int) $doi['ma_doi'] ?>" <?= (int) $doi['ma_doi'] === $loc['doi'] ? 'selected' : '' ?>><?= e($doi['ten_doi']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-4 col-lg-2">
        <label class="form-label small mb-1" for="trang_thai">Trạng thái</label>
        <select class="form-select" id="trang_thai" name="trang_thai">
            <option value="">Tất cả</option>
            <?php foreach (LTD_TRANG_THAI as $k => $ten): ?>
                <option value="<?= e($k) ?>" <?= $loc['trang_thai'] === $k ? 'selected' : '' ?>><?= e($ten) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-4 col-lg-2">
        <label class="form-label small mb-1" for="giai_doan">Giai đoạn</label>
        <select class="form-select" id="giai_doan" name="giai_doan">
            <option value="">Tất cả</option>
            <?php foreach (LTD_GIAI_DOAN as $k => $ten): ?>
                <option value="<?= e($k) ?>" <?= $loc['giai_doan'] === $k ? 'selected' : '' ?>><?= e($ten) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-4 col-lg-2">
        <label class="form-label small mb-1" for="ngay">Ngày</label>
        <input class="form-control" type="date" id="ngay" name="ngay" value="<?= e($loc['ngay']) ?>">
    </div>
    <div class="col-lg-1 d-flex gap-1">
        <button class="btn btn-outline-secondary w-100" type="submit" title="Lọc"><i class="bi bi-funnel"></i></button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>Thời gian</th>
                <th>Trận (khách @ nhà)</th>
                <th>Sân</th>
                <th>Giai đoạn</th>
                <th>Trạng thái</th>
                <th class="text-center">Tỉ số</th>
                <th class="text-center">Trọng tài</th>
                <th class="text-end" style="width: 130px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsTran): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Không có trận nào phù hợp.</td></tr>
            <?php endif; ?>
            <?php foreach ($dsTran as $t): ?>
                <tr>
                    <td class="text-nowrap"><?= e(ltdHienThoiGian($t['thoi_gian'])) ?></td>
                    <td class="text-nowrap">
                        <span title="<?= e($t['ten_doi_khach']) ?>"><?= e($t['vt_khach']) ?></span>
                        <span class="text-muted">@</span>
                        <span class="fw-semibold" title="<?= e($t['ten_doi_nha']) ?>"><?= e($t['vt_nha']) ?></span>
                    </td>
                    <td><?= e($t['ten_san'] ?? '') ?></td>
                    <td>
                        <?= e(LTD_GIAI_DOAN[$t['giai_doan']] ?? $t['giai_doan']) ?>
                        <?php if ($t['tran_so_trong_loat']): ?><span class="text-muted small">· Trận <?= (int) $t['tran_so_trong_loat'] ?></span><?php endif; ?>
                    </td>
                    <td><span class="badge text-bg-<?= ltdMauTrangThai($t['trang_thai']) ?>"><?= e(LTD_TRANG_THAI[$t['trang_thai']] ?? $t['trang_thai']) ?></span></td>
                    <td class="text-center text-nowrap">
                        <?= $t['trang_thai'] === 'da_dau' ? (int) $t['diem_doi_khach'] . ' - ' . (int) $t['diem_doi_nha'] : '' ?>
                    </td>
                    <td class="text-center">
                        <span class="<?= (int) $t['so_trong_tai'] === LTD_SO_TRONG_TAI ? '' : 'text-danger' ?>"><?= (int) $t['so_trong_tai'] ?>/<?= LTD_SO_TRONG_TAI ?></span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/lich_thi_dau/form.php?id=' . (int) $t['ma_tran']) ?>">Sửa</a>
                        <?php if ($t['trang_thai'] !== 'da_dau'): ?>
                            <form class="d-inline" method="post" action="<?= url('/admin/lich_thi_dau/xoa.php') ?>">
                                <?= oCsrf() ?>
                                <input type="hidden" name="id" value="<?= (int) $t['ma_tran'] ?>">
                                <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
    <p class="text-muted small mb-0">Tổng: <?= $tongTran ?> trận. Tỉ số ghi theo thứ tự khách - nhà. Trận đã đấu không xóa được.</p>
    <?php if ($soTrang > 1): ?>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $soTrang; $i++): ?>
                    <li class="page-item<?= $i === $trang ? ' active' : '' ?>"><a class="page-link" href="<?= e(ltdLinkTrang($loc, $i)) ?>"><?= $i ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
