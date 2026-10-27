<?php
/** Admin - Phân bảng: xếp đội vào khu vực theo từng mùa (TV4) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_phan_bang.php';

$dsMua = pbLayDsMua();
$mua   = pbChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
$maMua = $mua ? (int) $mua['ma_mua_giai'] : 0;
$urlTrang = url('/admin/phan_bang/index.php') . ($maMua ? '?mua=' . $maMua : '');

$dsKhuVuc = pbLayDsKhuVuc();
$dsDoi    = $mua ? pbLayDsDoi($maMua) : [];
$loi = [];

if ($mua && $_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $chon = is_array($_POST['khu_vuc'] ?? null) ? $_POST['khu_vuc'] : [];
    [$gan, $loi] = pbKiemTra($maMua, $chon, $dsDoi, $dsKhuVuc);
    if (!$loi) {
        try {
            pbLuu($maMua, $gan);
            datThongBao('ok', 'Đã lưu phân bảng mùa ' . $mua['ten_mua_giai'] . '.');
            chuyenTrang($urlTrang);
        } catch (PDOException $ex) {
            $loi[] = 'Không lưu được dữ liệu: ' . $ex->getMessage();
        }
    }
    // Có lỗi: giữ lại lựa chọn vừa gửi để người dùng sửa tiếp
    foreach ($dsDoi as &$doi) {
        $v = trim((string) ($chon[$doi['ma_doi']] ?? ''));
        $doi['ma_khu_vuc'] = $v === '' ? null : $v;
    }
    unset($doi);
}

// Nhóm đội theo khu vực để hiện bảng tổng quan
$theoKhuVuc = [];
$chuaPhan = [];
foreach ($dsDoi as $doi) {
    if ($doi['ma_khu_vuc'] === null) {
        $chuaPhan[] = $doi;
    } else {
        $theoKhuVuc[(int) $doi['ma_khu_vuc']][] = $doi;
    }
}
$theoHoiNghi = [];
foreach ($dsKhuVuc as $kv) {
    $theoHoiNghi[$kv['ten_hoi_nghi']][] = $kv;
}

$tieuDe = 'Phân bảng';
$menu   = 'phan_bang';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Phân bảng</h1>
    <?php if ($dsMua): ?>
        <form class="d-flex gap-2 align-items-center" method="get">
            <label class="form-label mb-0 text-nowrap" for="mua">Mùa giải</label>
            <select class="form-select" id="mua" name="mua" onchange="this.form.submit()">
                <?php foreach ($dsMua as $m): ?>
                    <option value="<?= (int) $m['ma_mua_giai'] ?>" <?= (int) $m['ma_mua_giai'] === $maMua ? 'selected' : '' ?>>
                        <?= e($m['ten_mua_giai']) ?><?= (int) $m['la_mua_hien_tai'] === 1 ? ' (hiện tại)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button class="btn btn-outline-secondary" type="submit">Xem</button></noscript>
        </form>
    <?php endif; ?>
</div>

<?php if (!$mua): ?>
    <div class="alert alert-warning">
        Chưa có mùa giải nào. Hãy <a href="<?= url('/admin/mua_giai/form.php') ?>">thêm mùa giải</a> trước.
    </div>
    <?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
    <?php return; ?>
<?php endif; ?>

<?php if ($loi): ?>
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Chưa lưu được:</div>
        <ul class="mb-0"><?php foreach ($loi as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<!-- Tổng quan: 2 hội nghị x 3 khu vực -->
<div class="row g-3 mb-3">
    <?php foreach ($theoHoiNghi as $tenHn => $dsKv): ?>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold"><?= e($tenHn) ?></div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php foreach ($dsKv as $kv): ?>
                            <?php $doiKv = $theoKhuVuc[(int) $kv['ma_khu_vuc']] ?? []; ?>
                            <div class="col-md-4">
                                <div class="fw-semibold small mb-1">
                                    <?= e($kv['ten_khu_vuc']) ?>
                                    <span class="text-muted">(<?= count($doiKv) ?>/<?= PB_TOI_DA_MOI_KHU_VUC ?>)</span>
                                </div>
                                <?php foreach ($doiKv as $doi): ?>
                                    <span class="badge text-bg-light border me-1 mb-1" title="<?= e($doi['ten_doi']) ?>"><?= e($doi['ten_viet_tat']) ?></span>
                                <?php endforeach; ?>
                                <?php if (!$doiKv): ?><span class="text-muted small">Chưa có đội</span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($chuaPhan): ?>
    <div class="alert alert-info">
        <?= count($chuaPhan) ?> đội chưa phân bảng trong mùa <?= e($mua['ten_mua_giai']) ?>:
        <?= e(implode(', ', array_column($chuaPhan, 'ten_viet_tat'))) ?>.
    </div>
<?php endif; ?>

<?php if ((int) $mua['so_doi'] === 0 && count($dsMua) > 1): ?>
    <!-- Mùa chưa có phân bảng: cho sao chép từ mùa khác -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form class="d-flex flex-wrap gap-2 align-items-center" method="post" action="<?= url('/admin/phan_bang/sao_chep.php') ?>">
                <?= oCsrf() ?>
                <input type="hidden" name="den_mua" value="<?= $maMua ?>">
                <label class="form-label mb-0" for="tu_mua">Sao chép phân bảng từ mùa</label>
                <select class="form-select w-auto" id="tu_mua" name="tu_mua">
                    <?php foreach ($dsMua as $m): ?>
                        <?php if ((int) $m['ma_mua_giai'] !== $maMua && (int) $m['so_doi'] > 0): ?>
                            <option value="<?= (int) $m['ma_mua_giai'] ?>"><?= e($m['ten_mua_giai']) ?> (<?= (int) $m['so_doi'] ?> đội)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-files"></i> Sao chép</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Chỉnh khu vực từng đội -->
<form method="post" action="<?= e($urlTrang) ?>">
    <?= oCsrf() ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Đội</th>
                    <th style="width: 90px">Mã</th>
                    <th class="text-end" style="width: 130px">Trận mùa thường</th>
                    <th style="width: 320px">Khu vực mùa <?= e($mua['ten_mua_giai']) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$dsDoi): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Chưa có đội bóng nào.</td></tr>
                <?php endif; ?>
                <?php foreach ($dsDoi as $doi): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($doi['ten_doi']) ?></td>
                        <td><?= e($doi['ten_viet_tat']) ?></td>
                        <td class="text-end"><?= (int) $doi['so_tran'] ?></td>
                        <td>
                            <select class="form-select form-select-sm" name="khu_vuc[<?= (int) $doi['ma_doi'] ?>]">
                                <option value="">Chưa phân bảng</option>
                                <?php foreach ($theoHoiNghi as $tenHn => $dsKv): ?>
                                    <optgroup label="<?= e($tenHn) ?>">
                                        <?php foreach ($dsKv as $kv): ?>
                                            <option value="<?= (int) $kv['ma_khu_vuc'] ?>"
                                                <?= (string) $doi['ma_khu_vuc'] === (string) $kv['ma_khu_vuc'] ? 'selected' : '' ?>>
                                                <?= e($kv['ten_khu_vuc']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($dsDoi): ?>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Lưu phân bảng</button>
            <a class="btn btn-outline-secondary" href="<?= e($urlTrang) ?>">Hủy thay đổi</a>
        </div>
    <?php endif; ?>
</form>
<p class="text-muted small mt-2">
    Mỗi khu vực tối đa <?= PB_TOI_DA_MOI_KHU_VUC ?> đội. Đội đã có trận mùa thường trong mùa thì không bỏ khỏi phân bảng được
    (bảng xếp hạng chỉ tính đội có phân bảng).
</p>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
