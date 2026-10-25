<?php
/** Admin - Chuyển nhượng cầu thủ + xem lịch sử chuyển nhượng */
require_once __DIR__ . '/../../includes/giao_dien.php';
yeuCauAdmin();

$id = (int) ($_GET['id'] ?? 0);
$ct = layCauThu($id);
if (!$ct) {
    datThongBao('loi', 'Không tìm thấy cầu thủ.');
    chuyenTrang('index.php');
}

$f = [
    'loai'        => $ct['ma_doi'] === null ? 'ky_hop_dong' : 'trao_doi',
    'ma_doi_moi'  => '',
    'ngay_chuyen' => date('Y-m-d'),
    'so_ao_moi'   => '',
    'ghi_chu'     => '',
];
$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    foreach ($f as $k => $_) {
        $f[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    $soAoMoi = null;
    if ($f['so_ao_moi'] !== '') {
        $soAoMoi = filter_var($f['so_ao_moi'], FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 99]]);
        if ($soAoMoi === false) {
            $loi = 'Số áo mới phải là số nguyên từ 0 đến 99.';
        }
    }

    if ($loi === '') {
        try {
            chuyenNhuong(
                $id,
                $f['loai'],
                ctype_digit($f['ma_doi_moi']) ? (int) $f['ma_doi_moi'] : null,
                $f['ngay_chuyen'],
                $soAoMoi,
                $f['ghi_chu']
            );
            datThongBao('ok', "Đã chuyển nhượng {$ct['ho_ten']} thành công.");
            chuyenTrang('chuyen_nhuong.php?id=' . $id);
        } catch (RuntimeException $ex) {
            $loi = $ex->getMessage();
        } catch (PDOException $ex) {
            $loi = $ex->getCode() === '23000'
                ? 'Số áo bị trùng ở đội mới, vui lòng nhập số áo khác.'
                : 'Lỗi CSDL: ' . $ex->getMessage();
        }
    }
}

$dsDoi  = layDanhSachDoi();
$lichSu = layLichSuChuyenNhuong($id);
dauTrang('Chuyển nhượng - ' . $ct['ho_ten']);
?>
<div class="dau">
    <h1>Chuyển nhượng</h1>
    <a class="nut phu" href="index.php">← Danh sách</a>
</div>

<div class="the ho-so" style="margin-bottom:18px">
    <?= anhCauThu($ct, 72) ?>
    <div class="thong-tin">
        <div><small>Cầu thủ</small><strong><?= e($ct['ho_ten']) ?></strong></div>
        <div><small>Đội hiện tại</small><?= e($ct['ten_doi'] ?? 'Tự do') ?></div>
        <div><small>Số áo</small><?= e($ct['so_ao'] ?? '—') ?></div>
        <div><small>Trạng thái</small><?= nhanTrangThai($ct['trang_thai']) ?></div>
    </div>
</div>

<?php if ($ct['trang_thai'] === 'giai_nghe'): ?>
    <div class="thong-bao loi">Cầu thủ đã giải nghệ, không thể chuyển nhượng.</div>
<?php else: ?>
    <?php if ($loi !== ''): ?><div class="thong-bao loi"><?= e($loi) ?></div><?php endif; ?>

    <form class="the" method="post">
        <?= oCsrf() ?>
        <div class="luoi-form">
            <div class="truong">
                <label for="loai">Loại giao dịch</label>
                <select id="loai" name="loai">
                    <?php foreach (LOAI_CHUYEN_NHUONG as $ma => $ten): ?>
                        <option value="<?= $ma ?>"<?= chon($f['loai'], $ma) ?>><?= e($ten) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="truong" id="o-doi-moi">
                <label for="ma_doi_moi">Đội mới</label>
                <select id="ma_doi_moi" name="ma_doi_moi">
                    <option value="">-- Chọn đội --</option>
                    <?php foreach ($dsDoi as $doi): ?>
                        <?php if ((string) $doi['ma_doi'] === (string) $ct['ma_doi']) continue; ?>
                        <option value="<?= $doi['ma_doi'] ?>"<?= chon($f['ma_doi_moi'], $doi['ma_doi']) ?>><?= e($doi['ten_doi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="truong">
                <label for="ngay_chuyen">Ngày chuyển</label>
                <input type="date" id="ngay_chuyen" name="ngay_chuyen" value="<?= e($f['ngay_chuyen']) ?>" required>
            </div>
            <div class="truong" id="o-so-ao">
                <label for="so_ao_moi">Số áo mới (bỏ trống = giữ số <?= e($ct['so_ao'] ?? 'cũ') ?>)</label>
                <input type="number" id="so_ao_moi" name="so_ao_moi" min="0" max="99" value="<?= e($f['so_ao_moi']) ?>">
            </div>
            <div class="truong" style="grid-column:1/-1">
                <label for="ghi_chu">Ghi chú</label>
                <input type="text" id="ghi_chu" name="ghi_chu" maxlength="255" value="<?= e($f['ghi_chu']) ?>"
                       placeholder="VD: Trao đổi kèm 2 lượt chọn Draft vòng 2">
            </div>
        </div>
        <div style="margin-top:18px">
            <button class="nut" type="submit" onclick="return confirm('Xác nhận chuyển nhượng?')">Thực hiện</button>
        </div>
    </form>

    <script>
        // Giải phóng thì không cần chọn đội mới / số áo mới
        const chonLoai = document.getElementById('loai');
        function anHienDoiMoi() {
            const giaiPhong = chonLoai.value === 'giai_phong';
            document.getElementById('o-doi-moi').style.display = giaiPhong ? 'none' : '';
            document.getElementById('o-so-ao').style.display = giaiPhong ? 'none' : '';
        }
        chonLoai.addEventListener('change', anHienDoiMoi);
        anHienDoiMoi();
    </script>
<?php endif; ?>

<h2>Lịch sử chuyển nhượng</h2>
<div class="cuon-ngang">
<table>
    <thead><tr><th>Ngày</th><th>Loại</th><th>Từ đội</th><th>Đến đội</th><th>Ghi chú</th></tr></thead>
    <tbody>
    <?php if (!$lichSu): ?>
        <tr><td colspan="5" class="mo">Chưa có giao dịch nào.</td></tr>
    <?php endif; ?>
    <?php foreach ($lichSu as $ls): ?>
        <tr>
            <td><?= dinhDangNgay($ls['ngay_chuyen']) ?></td>
            <td><?= e(LOAI_CHUYEN_NHUONG[$ls['loai']] ?? $ls['loai']) ?></td>
            <td><?= e($ls['ten_doi_cu'] ?? 'Tự do') ?></td>
            <td><?= e($ls['ten_doi_moi'] ?? 'Tự do') ?></td>
            <td><?= e($ls['ghi_chu'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php cuoiTrang();
