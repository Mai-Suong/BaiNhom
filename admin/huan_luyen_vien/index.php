<?php
/** Admin - Danh sách huấn luyện viên (TV2). ?mua= : xem đội / vai trò của HLV trong mùa đó (bảng nhiem_ky_hlv) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_huan_luyen_vien.php';
require_once __DIR__ . '/../../includes/ham_trang_khach.php'; // chọn mùa (tkh...)

$tuKhoa = trim((string) ($_GET['q'] ?? ''));
$locDoi = (string) ($_GET['doi'] ?? '');
$dsMua  = tkhLayDsMua();
$mua    = tkhChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
$maMua  = $mua ? (int) $mua['ma_mua_giai'] : null;
$laHienTai = $mua === null || tkhLaMuaHienTai($mua);
$dsDoi  = hlvLayDoiLuaChon();

if ($laHienTai) {
    // Mùa hiện tại: đội / vai trò hiện tại của HLV (đúng với dữ liệu nút Sửa)
    $dsHlv = hlvLayDanhSach($tuKhoa, $locDoi);
    foreach ($dsHlv as &$hlv) {
        $hlv['ds_nhiem_ky'] = $hlv['ma_doi'] === null ? [] : [[
            'ten_doi' => $hlv['ten_doi'], 'vai_tro' => $hlv['vai_tro'], 'tam_quyen' => 0,
            'tu_ngay' => $hlv['ngay_bat_dau'], 'den_ngay' => null,
        ]];
    }
    unset($hlv);
} else {
    $dsHlv = hlvLayDanhSachTheoMua($tuKhoa, $locDoi, $maMua);
}
$ngay = fn(?string $s) => $s ? date('d/m/Y', strtotime($s)) : '';

$tieuDe = 'Quản lý huấn luyện viên';
$menu   = 'hlv';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Huấn luyện viên<?= $mua ? ' mùa ' . e($mua['ten_mua_giai']) : '' ?>
        <?php if ($mua && $laHienTai): ?><span class="badge text-bg-success fs-6 align-middle">Mùa hiện tại</span><?php endif; ?>
    </h1>
    <a class="btn btn-primary" href="<?= url('/admin/huan_luyen_vien/form.php') ?>"><i class="bi bi-plus-lg"></i> Thêm HLV</a>
</div>

<?php if (!$laHienTai): ?>
    <div class="alert alert-info py-2">
        <i class="bi bi-clock-history"></i> Đang xem mùa <?= e($mua['ten_mua_giai']) ?>: đội, vai trò và thời gian dẫn dắt lấy từ nhiệm kỳ của mùa này.
        Nút Sửa / Xóa vẫn sửa thông tin hiện tại của HLV; sửa lịch sử bằng nút "Nhiệm kỳ".
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
    <input class="form-control" style="max-width: 260px" type="search" name="q"
           value="<?= e($tuKhoa) ?>" placeholder="Tìm theo tên HLV">
    <select class="form-select" style="max-width: 240px" name="doi">
        <option value="">Tất cả đội</option>
        <option value="tu_do"<?= $locDoi === 'tu_do' ? ' selected' : '' ?>><?= $laHienTai ? 'Đang tự do' : 'Không dẫn dắt trong mùa' ?></option>
        <?php foreach ($dsDoi as $doi): ?>
            <option value="<?= (int) $doi['ma_doi'] ?>"<?= $locDoi === (string) $doi['ma_doi'] ? ' selected' : '' ?>>
                <?= e($doi['ten_doi']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel"></i> Lọc</button>
    <?php if ($tuKhoa !== '' || $locDoi !== ''): ?>
        <a class="btn btn-link" href="<?= url('/admin/huan_luyen_vien/index.php') . ($maMua !== null ? '?mua=' . $maMua : '') ?>">Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 56px">Ảnh</th>
                <th>Họ tên</th>
                <th>Đội</th>
                <th>Vai trò</th>
                <th>Quốc tịch</th>
                <th>Ngày sinh</th>
                <th><?= $laHienTai ? 'Dẫn dắt từ' : 'Thời gian trong mùa' ?></th>
                <th class="text-end" style="width: 250px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsHlv): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Không có HLV phù hợp.</td></tr>
            <?php endif; ?>
            <?php foreach ($dsHlv as $hlv): ?>
                <tr>
                    <td>
                        <?php if ($hlv['anh']): ?>
                            <img src="<?= e(url('/' . $hlv['anh'])) ?>" alt="" width="40" height="40" class="rounded-circle object-fit-cover">
                        <?php else: ?>
                            <i class="bi bi-person-circle fs-3 text-muted"></i>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= e($hlv['ho_ten']) ?></td>
                    <td>
                        <?php if (!$hlv['ds_nhiem_ky']): ?>
                            <span class="badge text-bg-light border"><?= $laHienTai ? 'Tự do' : 'Không dẫn dắt' ?></span>
                        <?php endif; ?>
                        <?php foreach ($hlv['ds_nhiem_ky'] as $nk): ?><div><?= e($nk['ten_doi']) ?></div><?php endforeach; ?>
                    </td>
                    <td>
                        <?php foreach ($hlv['ds_nhiem_ky'] ?: ($laHienTai ? [['vai_tro' => $hlv['vai_tro'], 'tam_quyen' => 0]] : []) as $nk): ?>
                            <div>
                                <span class="badge <?= $nk['vai_tro'] === 'truong' ? 'text-bg-primary' : 'text-bg-secondary' ?>">
                                    <?= e(VAI_TRO_HLV[$nk['vai_tro']] ?? $nk['vai_tro']) ?>
                                </span>
                                <?php if ($nk['tam_quyen'] === 1): ?><span class="badge text-bg-warning">Tạm quyền</span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </td>
                    <td><?= e($hlv['quoc_tich']) ?></td>
                    <td><?= e($ngay($hlv['ngay_sinh'])) ?></td>
                    <td class="text-nowrap">
                        <?php foreach ($hlv['ds_nhiem_ky'] as $nk): ?>
                            <div><?= $laHienTai ? e($ngay($nk['tu_ngay']))
                                     : ($nk['den_ngay'] ? e($ngay($nk['tu_ngay'])) . ' – ' . e($ngay($nk['den_ngay'])) : 'Từ ' . e($ngay($nk['tu_ngay']))) ?></div>
                        <?php endforeach; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" title="Lịch sử dẫn dắt theo mùa"
                           href="<?= url('/admin/huan_luyen_vien/nhiem_ky.php?hlv=' . (int) $hlv['ma_hlv']) ?>">Nhiệm kỳ (<?= (int) $hlv['so_nhiem_ky'] ?>)</a>
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= url('/admin/huan_luyen_vien/form.php?id=' . (int) $hlv['ma_hlv']) ?>">Sửa</a>
                        <form class="d-inline" method="post" action="<?= url('/admin/huan_luyen_vien/xoa.php') ?>">
                            <?= oCsrf() ?>
                            <input type="hidden" name="id" value="<?= (int) $hlv['ma_hlv'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">Tổng: <?= count($dsHlv) ?> HLV. Mỗi đội chỉ có một HLV trưởng; HLV đã nhận giải thưởng không xóa được.</p>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
