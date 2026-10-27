<?php
/** Admin - Nhập kết quả: danh sách trận cần nhập / đã nhập (TV4) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_ket_qua.php';

$dsMua = kqLayDsMua();
$mua   = kqChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
$dsDoi = kqLayDsDoi();
$dsLoai = ['cho' => 'Chờ nhập kết quả', 'da' => 'Đã có kết quả', 'tat_ca' => 'Tất cả trận'];

$loc = [
    'mua'  => $mua ? (int) $mua['ma_mua_giai'] : 0,
    'doi'  => (int) ($_GET['doi'] ?? 0),
    'loai' => isset($dsLoai[$_GET['loai'] ?? '']) ? $_GET['loai'] : 'cho',
];
$tongTran = $mua ? kqDemTran($loc) : 0;
$soTrang  = max(1, (int) ceil($tongTran / KQ_MOI_TRANG));
$trang    = min($soTrang, max(1, (int) ($_GET['trang'] ?? 1)));
$dsTran   = $mua ? kqLayDsTran($loc, $trang) : [];

$tieuDe = 'Nhập kết quả';
$menu   = 'ket_qua';
require __DIR__ . '/../../includes/admin_header.php';
?>

<h1 class="h3 mb-3">Nhập kết quả</h1>

<?php if (!$mua): ?>
    <div class="alert alert-warning">Chưa có mùa giải nào. Hãy <a href="<?= url('/admin/mua_giai/form.php') ?>">thêm mùa giải</a> trước.</div>
    <?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
    <?php return; ?>
<?php endif; ?>

<form class="row g-2 align-items-end mb-3" method="get">
    <div class="col-sm-4 col-lg-2">
        <label class="form-label small mb-1" for="mua">Mùa giải</label>
        <select class="form-select" id="mua" name="mua">
            <?php foreach ($dsMua as $m): ?>
                <option value="<?= (int) $m['ma_mua_giai'] ?>" <?= (int) $m['ma_mua_giai'] === $loc['mua'] ? 'selected' : '' ?>>
                    <?= e($m['ten_mua_giai']) ?><?= (int) $m['la_mua_hien_tai'] === 1 ? ' (hiện tại)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-4 col-lg-3">
        <label class="form-label small mb-1" for="loai">Hiện</label>
        <select class="form-select" id="loai" name="loai">
            <?php foreach ($dsLoai as $k => $ten): ?>
                <option value="<?= e($k) ?>" <?= $loc['loai'] === $k ? 'selected' : '' ?>><?= e($ten) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-4 col-lg-3">
        <label class="form-label small mb-1" for="doi">Đội</label>
        <select class="form-select" id="doi" name="doi">
            <option value="">Tất cả đội</option>
            <?php foreach ($dsDoi as $doi): ?>
                <option value="<?= (int) $doi['ma_doi'] ?>" <?= (int) $doi['ma_doi'] === $loc['doi'] ? 'selected' : '' ?>><?= e($doi['ten_doi']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-lg-1">
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
                <th class="text-center">Tỉ số</th>
                <th>Ghi chú</th>
                <th class="text-end" style="width: 150px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsTran): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">
                    <?= $loc['loai'] === 'cho' ? 'Không có trận nào đang chờ nhập kết quả.' : 'Không có trận nào phù hợp.' ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($dsTran as $t): ?>
                <?php $daCo = $t['trang_thai'] === 'da_dau'; ?>
                <tr>
                    <td class="text-nowrap"><?= e(kqHienThoiGian($t['thoi_gian'])) ?></td>
                    <td class="text-nowrap">
                        <span title="<?= e($t['ten_doi_khach']) ?>"><?= e($t['vt_khach']) ?></span>
                        <span class="text-muted">@</span>
                        <span class="fw-semibold" title="<?= e($t['ten_doi_nha']) ?>"><?= e($t['vt_nha']) ?></span>
                    </td>
                    <td class="text-center text-nowrap fw-semibold">
                        <?= $daCo ? (int) $t['diem_doi_khach'] . ' - ' . (int) $t['diem_doi_nha'] : '' ?>
                    </td>
                    <td class="small text-muted">
                        <?php if ($daCo && (int) $t['so_hiep'] > KQ_SO_HIEP_CHINH): ?><?= e(kqTenHiep((int) $t['so_hiep'])) ?><?php endif; ?>
                        <?php if ($t['trang_thai'] === 'hoan'): ?><span class="badge text-bg-warning">Hoãn</span><?php endif; ?>
                        <?php if ($t['trang_thai'] === 'chua_dau' && strtotime($t['thoi_gian']) > time()): ?>Chưa tới giờ đấu<?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if (kqLyDoKhongNhap($t) === null): ?>
                            <a class="btn btn-sm <?= $daCo ? 'btn-outline-primary' : 'btn-primary' ?>"
                               href="<?= url('/admin/ket_qua/nhap.php?id=' . (int) $t['ma_tran']) ?>"><?= $daCo ? 'Sửa kết quả' : 'Nhập kết quả' ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
    <p class="text-muted small mb-0">Tổng: <?= $tongTran ?> trận. Tỉ số ghi theo thứ tự khách - nhà.</p>
    <?php if ($soTrang > 1): ?>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $soTrang; $i++): ?>
                    <li class="page-item<?= $i === $trang ? ' active' : '' ?>">
                        <a class="page-link" href="<?= e(url('/admin/ket_qua/index.php') . '?' . http_build_query(array_filter($loc + ['trang' => $i]))) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
