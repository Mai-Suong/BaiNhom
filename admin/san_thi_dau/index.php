<?php
/** Admin - Danh sách sân thi đấu (TV2). ?mua= : xem đội chủ nhà, số trận, tên sân theo mùa */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_san_thi_dau.php';
require_once __DIR__ . '/../../includes/ham_trang_khach.php'; // hàm theo mùa (tkh...)

$tuKhoa = trim((string) ($_GET['q'] ?? ''));
$dsSan  = sanLayDanhSach($tuKhoa);
$dsMua  = tkhLayDsMua();
$mua    = tkhChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
$maMua  = $mua ? (int) $mua['ma_mua_giai'] : null;
$laHienTai = $mua === null || tkhLaMuaHienTai($mua);

// Đội chủ nhà của từng sân trong mùa (theo trận sân nhà của mùa đó) và số trận tổ chức
$doiChuNha = [];
foreach (tkhLayDanhSachDoi($maMua) as $doi) {
    $sanNha = tkhLaySanNhaMua($doi, $mua);
    if ($sanNha) {
        $doiChuNha[(int) $sanNha['ma_san']][] = $doi['ten_viet_tat'];
    }
}
$soTranMua = $maMua !== null ? sanDemTranTheoMua($maMua) : [];

$tieuDe = 'Quản lý sân thi đấu';
$menu   = 'san';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Sân thi đấu<?= $mua ? ' mùa ' . e($mua['ten_mua_giai']) : '' ?>
        <?php if ($mua && $laHienTai): ?><span class="badge text-bg-success fs-6 align-middle">Mùa hiện tại</span><?php endif; ?>
    </h1>
    <a class="btn btn-primary" href="<?= url('/admin/san_thi_dau/form.php') ?>"><i class="bi bi-plus-lg"></i> Thêm sân</a>
</div>

<?php if (!$laHienTai): ?>
    <div class="alert alert-info py-2">
        <i class="bi bi-clock-history"></i> Đang xem mùa <?= e($mua['ten_mua_giai']) ?>: đội chủ nhà, số trận và tên sân dùng trong mùa là của mùa này.
        Nút Sửa / Xóa vẫn sửa thông tin hiện tại của sân.
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
           value="<?= e($tuKhoa) ?>" placeholder="Tìm theo tên sân hoặc thành phố">
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Tìm</button>
    <?php if ($tuKhoa !== ''): ?>
        <a class="btn btn-link" href="<?= url('/admin/san_thi_dau/index.php') . ($maMua !== null ? '?mua=' . $maMua : '') ?>">Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 72px">Ảnh</th>
                <th>Tên sân</th>
                <th>Thành phố</th>
                <th>Bang / tỉnh</th>
                <th class="text-end">Sức chứa</th>
                <th class="text-end">Khánh thành</th>
                <th>Đội chủ nhà</th>
                <th class="text-end" title="Số trận tổ chức ở sân trong mùa">Số trận</th>
                <th class="text-end" style="width: 150px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsSan): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">
                    <?= $tuKhoa !== '' ? 'Không tìm thấy sân phù hợp.' : 'Chưa có sân nào.' ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($dsSan as $san): ?>
                <tr>
                    <td>
                        <?php if ($san['hinh_anh']): ?>
                            <img src="<?= e(url('/' . $san['hinh_anh'])) ?>" alt="" width="56" height="40"
                                 class="rounded object-fit-cover">
                        <?php else: ?>
                            <span class="text-muted"><i class="bi bi-building fs-4"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= e($san['ten_san']) ?></div>
                        <?php $tenMua = $mua ? tkhTenSanTrongKhoang((int) $san['ma_san'], $san['ten_san'], $mua['ngay_bat_dau'], $mua['ngay_ket_thuc']) : [$san['ten_san']]; ?>
                        <?php if ($tenMua !== [$san['ten_san']]): ?>
                            <div class="small text-muted">Tên trong mùa: <?= e(implode(' → ', $tenMua)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($san['thanh_pho']) ?></td>
                    <td><?= e($san['tieu_bang']) ?></td>
                    <td class="text-end"><?= $san['suc_chua'] !== null ? number_format((int) $san['suc_chua'], 0, ',', '.') : '' ?></td>
                    <td class="text-end"><?= e($san['nam_khanh_thanh']) ?></td>
                    <td><?= isset($doiChuNha[(int) $san['ma_san']]) ? e(implode(', ', $doiChuNha[(int) $san['ma_san']])) : '<span class="text-muted">-</span>' ?></td>
                    <td class="text-end"><?= (int) ($soTranMua[(int) $san['ma_san']] ?? 0) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= url('/admin/san_thi_dau/form.php?id=' . (int) $san['ma_san']) ?>">Sửa</a>
                        <form class="d-inline" method="post" action="<?= url('/admin/san_thi_dau/xoa.php') ?>">
                            <?= oCsrf() ?>
                            <input type="hidden" name="id" value="<?= (int) $san['ma_san'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">Tổng: <?= count($dsSan) ?> sân. Sân đang là sân nhà của đội hoặc đã có trận đấu sẽ không xóa được.</p>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
