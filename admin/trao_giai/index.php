<?php
/** Admin - Trao giải thưởng cuối mùa (MVP, ROY, COY...) cho cầu thủ và HLV (TV3) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/giao_dien.php';
require_once __DIR__ . '/../../includes/ham_trao_giai.php';

$dsMua = layDanhSachMuaGiai();
$maMua = (int) ($_GET['mua'] ?? 0);
if (!in_array($maMua, array_map('intval', array_column($dsMua, 'ma_mua_giai')), true)) {
    $maMua = layMuaGiaiMacDinh($dsMua) ?? 0;
}

$dsGiai = layDanhSachGiaiThuong();
$loi = '';
$daChonGiai = (int) ($_POST['ma_giai_thuong'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $maMua) {
    kiemTraCsrf();
    try {
        if (($_POST['hanh_dong'] ?? '') === 'huy') {
            huyTraoGiai($maMua, (int) ($_POST['ma_giai_thuong'] ?? 0));
            datThongBao('ok', 'Đã hủy trao giải.');
        } else {
            // Form có 2 ô chọn (cầu thủ / HLV). Lấy ô nào là do loại giải quyết định,
            // tra từ CSDL chứ không tin giá trị gửi lên từ trình duyệt.
            $doiTuong  = array_column($dsGiai, 'doi_tuong', 'ma_giai_thuong')[$daChonGiai] ?? 'cau_thu';
            $nguoiNhan = $doiTuong === 'hlv'
                ? (int) ($_POST['ma_hlv'] ?? 0)
                : (int) ($_POST['ma_cau_thu'] ?? 0);
            traoGiai($maMua, $daChonGiai, $nguoiNhan);
            datThongBao('ok', 'Đã lưu kết quả trao giải.');
        }
        chuyenTrang(url('/admin/trao_giai/index.php?mua=' . $maMua));
    } catch (RuntimeException $ex) {
        $loi = $ex->getMessage();
    }
}

$ketQua = $maMua ? layKetQuaTraoGiai($maMua) : [];
$dsCT   = layCauThuDeTraoGiai();
$dsHLV  = layHLVDeTraoGiai();
$tenMua = array_column($dsMua, 'ten_mua_giai', 'ma_mua_giai')[$maMua] ?? '';

// Gom cầu thủ theo đội để hiển thị <optgroup>
$ctTheoDoi = [];
foreach ($dsCT as $ct) {
    $ctTheoDoi[$ct['ten_doi'] ?? 'Không có đội'][] = $ct;
}

$tieuDe = 'Trao giải thưởng';
$menu   = 'trao_giai';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Trao giải thưởng</h1>
    <?php if ($dsMua): ?>
        <form class="d-flex align-items-center gap-2" method="get">
            <label class="fw-semibold text-nowrap" for="mua">Mùa giải</label>
            <select class="form-select" id="mua" name="mua" onchange="this.form.submit()">
                <?php foreach ($dsMua as $m): ?>
                    <option value="<?= (int) $m['ma_mua_giai'] ?>"<?= chon($maMua, $m['ma_mua_giai']) ?>>
                        <?= e($m['ten_mua_giai']) ?><?= $m['la_mua_hien_tai'] ? ' (hiện tại)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button class="btn btn-outline-secondary" type="submit">Xem</button></noscript>
        </form>
    <?php endif; ?>
</div>

<?php if (!$dsMua): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Chưa có mùa giải nào. Cần có dữ liệu bảng <code>mua_giai</code> (TV4) trước khi trao giải.
    </div>
    <?php require __DIR__ . '/../../includes/admin_footer.php'; exit; ?>
<?php endif; ?>

<?php if ($loi !== ''): ?><div class="alert alert-danger"><?= e($loi) ?></div><?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-trophy"></i> Trao giải mùa <?= e($tenMua) ?></div>
    <div class="card-body">
        <form method="post">
            <?= oCsrf() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="ma_giai_thuong">Giải thưởng</label>
                    <select class="form-select" id="ma_giai_thuong" name="ma_giai_thuong" required>
                        <option value="">-- Chọn giải --</option>
                        <?php foreach ($dsGiai as $g): ?>
                            <option value="<?= (int) $g['ma_giai_thuong'] ?>" data-doi-tuong="<?= e($g['doi_tuong']) ?>"<?= chon($daChonGiai, $g['ma_giai_thuong']) ?>>
                                <?= e($g['ten_viet_tat'] . ' – ' . $g['ten_giai']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6" id="o-cau-thu">
                    <label class="form-label fw-semibold" for="ma_cau_thu">Cầu thủ nhận giải</label>
                    <select class="form-select" id="ma_cau_thu" name="ma_cau_thu">
                        <option value="">-- Chọn cầu thủ --</option>
                        <?php foreach ($ctTheoDoi as $tenDoi => $ds): ?>
                            <optgroup label="<?= e($tenDoi) ?>">
                                <?php foreach ($ds as $ct): ?>
                                    <option value="<?= (int) $ct['ma_cau_thu'] ?>"><?= e($ct['ho_ten'] . ' (' . $ct['vi_tri'] . ')') ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 d-none" id="o-hlv">
                    <label class="form-label fw-semibold" for="ma_hlv">Huấn luyện viên nhận giải</label>
                    <select class="form-select" id="ma_hlv" name="ma_hlv">
                        <option value="">-- Chọn HLV --</option>
                        <?php foreach ($dsHLV as $h): ?>
                            <option value="<?= (int) $h['ma_hlv'] ?>">
                                <?= e($h['ho_ten'] . ' – ' . ($h['ten_doi'] ?? 'Tự do') . ($h['vai_tro'] === 'truong' ? ' (HLV trưởng)' : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$dsHLV): ?><div class="form-text">Chưa có HLV nào (dữ liệu của TV2).</div><?php endif; ?>
                </div>
            </div>
            <div class="mt-4 d-flex align-items-center flex-wrap gap-3">
                <button class="btn btn-primary" type="submit"><i class="bi bi-award"></i> Trao giải</button>
                <span class="text-muted small">Giải đã trao trong mùa này sẽ được thay người nhận.</span>
            </div>
        </form>
    </div>
</div>

<h2 class="h5 mb-3">Kết quả mùa <?= e($tenMua) ?></h2>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th style="width: 90px">Giải</th><th>Tên giải</th><th>Người nhận</th><th>Đội</th><th class="text-end"></th></tr></thead>
            <tbody>
            <?php foreach ($ketQua as $kq): ?>
                <tr>
                    <td><span class="badge text-bg-warning"><?= e($kq['ten_viet_tat']) ?></span></td>
                    <td><?= e($kq['ten_giai']) ?></td>
                    <td>
                        <?php if ($kq['ma_cau_thu']): ?>
                            <a class="fw-semibold" href="<?= url('/cau_thu/ho_so.php?id=' . (int) $kq['ma_cau_thu']) ?>"><?= e($kq['nguoi_nhan']) ?></a>
                        <?php elseif ($kq['ma_hlv']): ?>
                            <span class="fw-semibold"><?= e($kq['nguoi_nhan']) ?></span> <span class="text-muted">(HLV)</span>
                        <?php else: ?>
                            <span class="text-muted">Chưa trao</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($kq['ten_doi'] ?? '') ?></td>
                    <td class="text-end">
                        <?php if ($kq['ma_trao_giai']): ?>
                            <form class="d-inline" method="post" onsubmit="return confirm('Hủy trao giải <?= e($kq['ten_viet_tat']) ?>?')">
                                <?= oCsrf() ?>
                                <input type="hidden" name="hanh_dong" value="huy">
                                <input type="hidden" name="ma_giai_thuong" value="<?= (int) $kq['ma_giai_thuong'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Hủy</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Giải dành cho HLV (COY) thì hiện ô chọn HLV, còn lại hiện ô chọn cầu thủ
    const chonGiai = document.getElementById('ma_giai_thuong');
    function capNhatOChon() {
        const opt = chonGiai.options[chonGiai.selectedIndex];
        const laHLV = opt && opt.dataset.doiTuong === 'hlv';
        document.getElementById('o-hlv').classList.toggle('d-none', !laHLV);
        document.getElementById('o-cau-thu').classList.toggle('d-none', laHLV);
    }
    chonGiai.addEventListener('change', capNhatOChon);
    capNhatOChon();
</script>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
