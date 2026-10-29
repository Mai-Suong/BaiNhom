<?php
/** Admin - Danh sách đội bóng (TV2). ?mua= : xem sân nhà, HLV trưởng, cầu thủ, thành tích theo mùa */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_doi_bong.php';
require_once __DIR__ . '/../../includes/ham_trang_khach.php'; // hàm theo mùa (tkh...)

$tuKhoa = trim((string) ($_GET['q'] ?? ''));
$dsDoi  = dbLayDanhSach($tuKhoa);
$dsMua  = tkhLayDsMua();
$mua    = tkhChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
$maMua  = $mua ? (int) $mua['ma_mua_giai'] : null;
$laHienTai = $mua === null || tkhLaMuaHienTai($mua);

// Thông tin của từng đội trong mùa đang xem (mùa hiện tại: giữ số cầu thủ / HLV hiện tại để khớp với nút Sửa)
foreach ($dsDoi as &$doi) {
    $san = tkhLaySanNhaMua($doi, $mua);
    $doi['san_mua'] = $san ? implode(' → ', $san['ten_trong_mua']) : null;
    $dsTruong = array_filter(tkhLayBanHuanLuyen((int) $doi['ma_doi'], $mua), fn($h) => $h['vai_tro'] === 'truong');
    $doi['hlv_mua'] = $dsTruong
        ? implode(' → ', array_map(fn($h) => $h['ho_ten'] . ((int) $h['tam_quyen'] === 1 ? ' (tạm quyền)' : ''), $dsTruong))
        : ($laHienTai ? $doi['hlv_truong'] : null);
    if (!$laHienTai) {
        $doi['so_cau_thu'] = count(tkhLayDoiHinh((int) $doi['ma_doi'], $mua));
    }
    $doi['thanh_tich'] = $maMua !== null ? tkhLayThanhTich((int) $doi['ma_doi'], $maMua) : null;
}
unset($doi);

$tieuDe = 'Quản lý đội bóng';
$menu   = 'doi_bong';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Đội bóng<?= $mua ? ' mùa ' . e($mua['ten_mua_giai']) : '' ?>
        <?php if ($mua && $laHienTai): ?><span class="badge text-bg-success fs-6 align-middle">Mùa hiện tại</span><?php endif; ?>
    </h1>
    <a class="btn btn-primary" href="<?= url('/admin/doi_bong/form.php') ?>"><i class="bi bi-plus-lg"></i> Thêm đội</a>
</div>

<?php if (!$laHienTai): ?>
    <div class="alert alert-info py-2">
        <i class="bi bi-clock-history"></i> Đang xem mùa <?= e($mua['ten_mua_giai']) ?>: sân nhà, HLV trưởng, số cầu thủ ra sân và thành tích là của mùa này.
        Nút Sửa / Xóa vẫn sửa thông tin hiện tại của đội.
    </div>
<?php endif; ?>

<form class="d-flex flex-wrap gap-2 mb-3" method="get">
    <?php if ($dsMua): ?>
        <select class="form-select w-auto" name="mua" aria-label="Mùa giải" onchange="this.form.submit()">
            <?php foreach ($dsMua as $m): ?>
                <option value="<?= (int) $m['ma_mua_giai'] ?>"<?= (int) $m['ma_mua_giai'] === $maMua ? ' selected' : '' ?>>
                    Mùa <?= e($m['ten_mua_giai']) ?><?= (int) $m['la_mua_hien_tai'] === 1 ? ' (hiện tại)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <input class="form-control" style="max-width: 320px" type="search" name="q"
           value="<?= e($tuKhoa) ?>" placeholder="Tìm theo tên, tên viết tắt, thành phố">
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Tìm</button>
    <?php if ($tuKhoa !== ''): ?>
        <a class="btn btn-link" href="<?= url('/admin/doi_bong/index.php') . ($maMua !== null ? '?mua=' . $maMua : '') ?>">Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 64px">Logo</th>
                <th>Tên đội</th>
                <th>Viết tắt</th>
                <th>Thành phố</th>
                <th>Sân nhà</th>
                <th>HLV trưởng</th>
                <th class="text-end" title="<?= $laHienTai ? 'Cầu thủ đang thuộc đội' : 'Cầu thủ đã ra sân cho đội trong mùa' ?>">Cầu thủ</th>
                <th class="text-end" title="Thắng - thua mùa thường">Thành tích</th>
                <th class="text-end">Thành lập</th>
                <th class="text-end" style="width: 150px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsDoi): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">
                    <?= $tuKhoa !== '' ? 'Không tìm thấy đội phù hợp.' : 'Chưa có đội nào.' ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($dsDoi as $doi): ?>
                <tr>
                    <td>
                        <?php if ($doi['logo']): ?>
                            <img src="<?= e(url('/' . $doi['logo'])) ?>" alt="" width="44" height="44" class="object-fit-contain">
                        <?php else: ?>
                            <span class="badge text-bg-secondary"><?= e($doi['ten_viet_tat']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= e($doi['ten_doi']) ?></td>
                    <td><?= e($doi['ten_viet_tat']) ?></td>
                    <td><?= e($doi['thanh_pho']) ?></td>
                    <td><?= $doi['san_mua'] !== null ? e($doi['san_mua']) : '<span class="text-muted">Chưa có</span>' ?></td>
                    <td><?= $doi['hlv_mua'] !== null ? e($doi['hlv_mua']) : '<span class="text-muted">Chưa có</span>' ?></td>
                    <td class="text-end"><?= $laHienTai || $doi['so_cau_thu'] > 0 ? (int) $doi['so_cau_thu'] : '<span class="text-muted">-</span>' ?></td>
                    <td class="text-end text-nowrap"><?= $doi['thanh_tich'] ? (int) $doi['thanh_tich']['thang'] . '-' . (int) $doi['thanh_tich']['thua'] : '<span class="text-muted">-</span>' ?></td>
                    <td class="text-end"><?= e($doi['nam_thanh_lap']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= url('/admin/doi_bong/form.php?id=' . (int) $doi['ma_doi']) ?>">Sửa</a>
                        <form class="d-inline" method="post" action="<?= url('/admin/doi_bong/xoa.php') ?>">
                            <?= oCsrf() ?>
                            <input type="hidden" name="id" value="<?= (int) $doi['ma_doi'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">Tổng: <?= count($dsDoi) ?> đội. Đội còn cầu thủ hoặc đã có trận đấu sẽ không xóa được.</p>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
