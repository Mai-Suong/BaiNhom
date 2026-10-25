<?php
/** Admin - Trao giải thưởng cuối mùa (MVP, ROY, COY...) cho cầu thủ và HLV */
require_once __DIR__ . '/../../includes/giao_dien.php';
require_once __DIR__ . '/../../includes/ham_trao_giai.php';
yeuCauAdmin();

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
        chuyenTrang('index.php?mua=' . $maMua);
    } catch (RuntimeException $ex) {
        $loi = $ex->getMessage();
    }
}

$ketQua  =$maMua ? layKetQuaTraoGiai($maMua) : [];
$dsCT    = layCauThuDeTraoGiai();
$dsHLV   = layHLVDeTraoGiai();

// Gom cầu thủ theo đội để hiển thị <optgroup>
$ctTheoDoi = [];
foreach ($dsCT as $ct) {
    $ctTheoDoi[$ct['ten_doi'] ?? 'Cầu thủ tự do'][] = $ct;
}

dauTrang('Trao giải thưởng');
?>
<div class="dau">
    <h1>Trao giải thưởng</h1>
    <a class="nut phu" href="<?= BASE_URL ?>/admin/cau_thu/index.php">← Quản lý cầu thủ</a>
</div>

<?php if (!$dsMua): ?>
    <div class="the mo">Chưa có mùa giải nào. Cần có dữ liệu bảng <code>mua_giai</code> (TV4) trước khi trao giải.</div>
    <?php cuoiTrang(); exit; ?>
<?php endif; ?>

<form class="bo-loc" method="get">
    <label for="mua"><strong>Mùa giải:</strong></label>
    <select id="mua" name="mua" onchange="this.form.submit()">
        <?php foreach ($dsMua as $m): ?>
            <option value="<?= $m['ma_mua_giai'] ?>"<?= chon($maMua, $m['ma_mua_giai']) ?>>
                <?= e($m['ten_mua_giai']) ?><?= $m['la_mua_hien_tai'] ? ' (hiện tại)' : '' ?>
            </option>
        <?php endforeach; ?>
    </select>
    <noscript><button class="nut" type="submit">Xem</button></noscript>
</form>

<?php if ($loi !== ''): ?><div class="thong-bao loi"><?= e($loi) ?></div><?php endif; ?>

<form class="the" method="post" id="form-trao">
    <?= oCsrf() ?>
    <div class="luoi-form">
        <div class="truong">
            <label for="ma_giai_thuong">Giải thưởng</label>
            <select id="ma_giai_thuong" name="ma_giai_thuong" required>
                <option value="">-- Chọn giải --</option>
                <?php foreach ($dsGiai as $g): ?>
                    <option value="<?= $g['ma_giai_thuong'] ?>" data-doi-tuong="<?= e($g['doi_tuong']) ?>"<?= chon($daChonGiai, $g['ma_giai_thuong']) ?>>
                        <?= e($g['ten_viet_tat'] . ' – ' . $g['ten_giai']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="truong" id="o-cau-thu">
            <label for="ma_cau_thu">Cầu thủ nhận giải</label>
            <select id="ma_cau_thu" name="ma_cau_thu">
                <option value="">-- Chọn cầu thủ --</option>
                <?php foreach ($ctTheoDoi as $tenDoi => $ds): ?>
                    <optgroup label="<?= e($tenDoi) ?>">
                        <?php foreach ($ds as $ct): ?>
                            <option value="<?= $ct['ma_cau_thu'] ?>"><?= e($ct['ho_ten'] . ' (' . $ct['vi_tri'] . ')') ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="truong" id="o-hlv" style="display:none">
            <label for="ma_hlv">Huấn luyện viên nhận giải</label>
            <select id="ma_hlv" name="ma_hlv">
                <option value="">-- Chọn HLV --</option>
                <?php foreach ($dsHLV as $h): ?>
                    <option value="<?= $h['ma_hlv'] ?>">
                        <?= e($h['ho_ten'] . ' – ' . ($h['ten_doi'] ?? 'Tự do') . ($h['vai_tro'] === 'truong' ? ' (HLV trưởng)' : '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!$dsHLV): ?><div class="mo" style="font-size:13px;margin-top:3px">Chưa có HLV nào (dữ liệu của TV2).</div><?php endif; ?>
        </div>
    </div>
    <div style="margin-top:16px">
        <button class="nut" type="submit">Trao giải</button>
        <span class="mo" style="font-size:13px;margin-left:8px">Giải đã trao trong mùa này sẽ được thay người nhận.</span>
    </div>
</form>

<h2>Kết quả mùa <?= e(array_column($dsMua, 'ten_mua_giai', 'ma_mua_giai')[$maMua] ?? '') ?></h2>
<div class="cuon-ngang">
<table>
    <thead><tr><th>Giải</th><th>Tên giải</th><th>Người nhận</th><th>Đội</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($ketQua as $kq): ?>
        <tr>
            <td><strong><?= e($kq['ten_viet_tat']) ?></strong></td>
            <td><?= e($kq['ten_giai']) ?></td>
            <td>
                <?php if ($kq['ma_cau_thu']): ?>
                    <a href="<?= BASE_URL ?>/cau_thu/ho_so.php?id=<?= $kq['ma_cau_thu'] ?>"><?= e($kq['nguoi_nhan']) ?></a>
                <?php elseif ($kq['ma_hlv']): ?>
                    <?= e($kq['nguoi_nhan']) ?> <span class="mo">(HLV)</span>
                <?php else: ?>
                    <span class="mo">Chưa trao</span>
                <?php endif; ?>
            </td>
            <td><?= e($kq['ten_doi'] ?? '') ?></td>
            <td style="white-space:nowrap">
                <?php if ($kq['ma_trao_giai']): ?>
                <form class="inline" method="post" onsubmit="return confirm('Hủy trao giải <?= e($kq['ten_viet_tat']) ?>?')">
                    <?= oCsrf() ?>
                    <input type="hidden" name="hanh_dong" value="huy">
                    <input type="hidden" name="ma_giai_thuong" value="<?= $kq['ma_giai_thuong'] ?>">
                    <button class="nut do nho" type="submit">Hủy</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<script>
    // Giải dành cho HLV (COY) thì hiện ô chọn HLV, còn lại hiện ô chọn cầu thủ
    const chonGiai = document.getElementById('ma_giai_thuong');
    function capNhatOChon() {
        const opt = chonGiai.options[chonGiai.selectedIndex];
        const doiTuong = opt ? (opt.dataset.doiTuong || '') : '';
        const laHLV = doiTuong === 'hlv';
        document.getElementById('o-hlv').style.display = laHLV ? '' : 'none';
        document.getElementById('o-cau-thu').style.display = laHLV ? 'none' : '';
    }
    chonGiai.addEventListener('change', capNhatOChon);
    capNhatOChon();
</script>

<?php cuoiTrang();
