<?php
/**
 * Admin - Lịch sử dẫn dắt (nhiệm kỳ theo mùa) của 1 HLV - TV2
 *   nhiem_ky.php?hlv=5           : danh sách + form thêm nhiệm kỳ
 *   nhiem_ky.php?hlv=5&sua=12    : danh sách + form sửa nhiệm kỳ 12
 *   POST hanh_dong=luu | xoa     : lưu / xóa nhiệm kỳ
 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_huan_luyen_vien.php';

$maHlv = (int) ($_GET['hlv'] ?? 0);
$hlv   = $maHlv ? hlvLay($maHlv) : null;
if (!$hlv) {
    datThongBao('loi', 'Không tìm thấy HLV.');
    chuyenTrang(url('/admin/huan_luyen_vien/index.php'));
}
$trangNay = url('/admin/huan_luyen_vien/nhiem_ky.php?hlv=' . $maHlv);

$maSua = (int) ($_GET['sua'] ?? 0);
$cu    = $maSua ? nkLay($maSua) : null;
if ($maSua && (!$cu || (int) $cu['ma_hlv'] !== $maHlv)) {
    datThongBao('loi', 'Không tìm thấy nhiệm kỳ.');
    chuyenTrang($trangNay);
}

$d   = $cu ?? ['ma_mua_giai' => '', 'ma_doi' => $hlv['ma_doi'] ?? '', 'vai_tro' => $hlv['vai_tro'],
               'tam_quyen' => 0, 'tu_ngay' => '', 'den_ngay' => ''];
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    if (($_POST['hanh_dong'] ?? '') === 'xoa') {
        if (nkXoa($maHlv, (int) ($_POST['ma_nhiem_ky'] ?? 0))) {
            datThongBao('ok', 'Đã xóa nhiệm kỳ.');
        } else {
            datThongBao('loi', 'Không tìm thấy nhiệm kỳ.');
        }
        chuyenTrang($trangNay);
    }

    $d   = nkChuanHoa($_POST);
    $loi = nkKiemTra($maHlv, $d, $maSua);
    if (!$loi) {
        try {
            if ($cu) {
                nkSua($maSua, $d);
                datThongBao('ok', 'Đã cập nhật nhiệm kỳ.');
            } else {
                nkThem($maHlv, $d);
                datThongBao('ok', 'Đã thêm nhiệm kỳ.');
            }
            chuyenTrang($trangNay);
        } catch (PDOException $ex) {
            $loi['chung'] = $ex->getCode() === '23000'
                ? 'Nhiệm kỳ này đã có (trùng HLV, đội, mùa và ngày bắt đầu).'
                : 'Không lưu được dữ liệu: ' . $ex->getMessage();
        }
    }
}

$dsNhiemKy = nkLayCuaHlv($maHlv);
$dsMua     = nkLayDsMua();
$dsDoi     = hlvLayDoiLuaChon();
$ngay      = fn($s) => $s ? date('d/m/Y', strtotime($s)) : '';

$tieuDe = 'Nhiệm kỳ của ' . $hlv['ho_ten'];
$menu   = 'hlv';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= url('/admin/huan_luyen_vien/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách HLV</a></nav>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Lịch sử dẫn dắt: <?= e($hlv['ho_ten']) ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('/admin/huan_luyen_vien/form.php?id=' . $maHlv) ?>">
        <i class="bi bi-pencil"></i> Sửa thông tin HLV</a>
</div>
<p class="text-muted small">
    Mỗi mùa một dòng. Thay HLV giữa mùa: cho nhiệm kỳ cũ có "đến ngày", nhiệm kỳ mới bắt đầu từ ngày hôm sau.
    Đội hiện tại của HLV vẫn sửa ở trang "Sửa thông tin HLV".
</p>

<?php if (isset($loi['chung'])): ?>
    <div class="alert alert-danger"><?= e($loi['chung']) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr><th>Mùa</th><th>Đội</th><th>Vai trò</th><th>Từ ngày</th><th>Đến ngày</th><th class="text-end" style="width: 150px"></th></tr>
            </thead>
            <tbody>
            <?php if (!$dsNhiemKy): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có nhiệm kỳ nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($dsNhiemKy as $nk): ?>
                <tr<?= (int) $nk['ma_nhiem_ky'] === $maSua ? ' class="table-warning"' : '' ?>>
                    <td class="fw-semibold"><?= e($nk['ten_mua_giai']) ?></td>
                    <td><?= e($nk['ten_doi']) ?></td>
                    <td>
                        <span class="badge <?= $nk['vai_tro'] === 'truong' ? 'text-bg-primary' : 'text-bg-secondary' ?>">
                            <?= e(VAI_TRO_HLV[$nk['vai_tro']] ?? $nk['vai_tro']) ?></span>
                        <?php if ((int) $nk['tam_quyen'] === 1): ?><span class="badge text-bg-warning">Tạm quyền</span><?php endif; ?>
                    </td>
                    <td><?= e($ngay($nk['tu_ngay'])) ?></td>
                    <td><?= $nk['den_ngay'] ? e($ngay($nk['den_ngay'])) : '<span class="text-muted">Hết mùa</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e($trangNay . '&sua=' . (int) $nk['ma_nhiem_ky']) ?>">Sửa</a>
                        <form class="d-inline" method="post" action="<?= e($trangNay) ?>">
                            <?= oCsrf() ?>
                            <input type="hidden" name="hanh_dong" value="xoa">
                            <input type="hidden" name="ma_nhiem_ky" value="<?= (int) $nk['ma_nhiem_ky'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header fw-semibold"><?= $cu ? 'Sửa nhiệm kỳ' : 'Thêm nhiệm kỳ' ?></div>
    <div class="card-body">
        <?php if (!$dsMua): ?>
            <div class="alert alert-warning mb-0">Chưa có mùa giải nào. Cần thêm mùa giải (TV4) trước.</div>
        <?php else: ?>
        <form method="post" action="<?= e($trangNay . ($cu ? '&sua=' . $maSua : '')) ?>" novalidate>
            <?= oCsrf() ?>
            <input type="hidden" name="hanh_dong" value="luu">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="ma_mua_giai">Mùa giải *</label>
                    <select class="form-select<?= isset($loi['ma_mua_giai']) ? ' is-invalid' : '' ?>" id="ma_mua_giai" name="ma_mua_giai">
                        <option value="">-- Chọn mùa --</option>
                        <?php foreach ($dsMua as $m): ?>
                            <option value="<?= (int) $m['ma_mua_giai'] ?>"<?= (string) $m['ma_mua_giai'] === (string) $d['ma_mua_giai'] ? ' selected' : '' ?>>
                                <?= e($m['ten_mua_giai']) ?> (<?= e($ngay($m['ngay_bat_dau'])) ?> - <?= e($ngay($m['ngay_ket_thuc'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($loi['ma_mua_giai'])): ?><div class="invalid-feedback"><?= e($loi['ma_mua_giai']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="ma_doi">Đội *</label>
                    <select class="form-select<?= isset($loi['ma_doi']) ? ' is-invalid' : '' ?>" id="ma_doi" name="ma_doi">
                        <option value="">-- Chọn đội --</option>
                        <?php foreach ($dsDoi as $doi): ?>
                            <option value="<?= (int) $doi['ma_doi'] ?>"<?= (string) $doi['ma_doi'] === (string) $d['ma_doi'] ? ' selected' : '' ?>><?= e($doi['ten_doi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($loi['ma_doi'])): ?><div class="invalid-feedback"><?= e($loi['ma_doi']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="vai_tro">Vai trò *</label>
                    <select class="form-select<?= isset($loi['vai_tro']) ? ' is-invalid' : '' ?>" id="vai_tro" name="vai_tro">
                        <?php foreach (VAI_TRO_HLV as $ma => $ten): ?>
                            <option value="<?= e($ma) ?>"<?= $d['vai_tro'] === $ma ? ' selected' : '' ?>><?= e($ten) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($loi['vai_tro'])): ?><div class="invalid-feedback"><?= e($loi['vai_tro']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="tu_ngay">Từ ngày</label>
                    <input class="form-control<?= isset($loi['tu_ngay']) ? ' is-invalid' : '' ?>" type="date" id="tu_ngay" name="tu_ngay" value="<?= e($d['tu_ngay']) ?>">
                    <div class="form-text">Để trống = ngày bắt đầu mùa.</div>
                    <?php if (isset($loi['tu_ngay'])): ?><div class="invalid-feedback"><?= e($loi['tu_ngay']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="den_ngay">Đến ngày</label>
                    <input class="form-control<?= isset($loi['den_ngay']) ? ' is-invalid' : '' ?>" type="date" id="den_ngay" name="den_ngay" value="<?= e($d['den_ngay']) ?>">
                    <div class="form-text">Để trống = dẫn dắt đến hết mùa.</div>
                    <?php if (isset($loi['den_ngay'])): ?><div class="invalid-feedback"><?= e($loi['den_ngay']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="tam_quyen" name="tam_quyen" value="1"<?= (int) $d['tam_quyen'] === 1 ? ' checked' : '' ?>>
                        <label class="form-check-label" for="tam_quyen">HLV tạm quyền (interim)</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> <?= $cu ? 'Lưu thay đổi' : 'Thêm nhiệm kỳ' ?></button>
                <?php if ($cu): ?><a class="btn btn-outline-secondary" href="<?= e($trangNay) ?>">Hủy sửa</a><?php endif; ?>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
