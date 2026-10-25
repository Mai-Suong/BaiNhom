<?php
/** Trang Khách - Hồ sơ cầu thủ: thông tin, chỉ số trung bình mùa, chấn thương, chuyển nhượng */
require_once __DIR__ . '/../includes/giao_dien.php';

$id = (int) ($_GET['id'] ?? 0);
$ct = layCauThu($id);
if (!$ct) {
    http_response_code(404);
    dauTrang('Không tìm thấy cầu thủ');
    echo '<div class="the">Không tìm thấy cầu thủ.</div>';
    cuoiTrang();
    exit;
}

$chiSo     = layChiSoTrungBinh($id);
$chanThuong = layDanhSachChanThuong($id, true);
$lichSu    = layLichSuChuyenNhuong($id);
$fmt = fn($v) => $v === null ? '—' : number_format((float) $v, 1);

dauTrang($ct['ho_ten']);
?>
<div class="the ho-so">
    <?= anhCauThu($ct, 130) ?>
    <div style="flex:1;min-width:240px">
        <h1><?= e($ct['ho_ten']) ?>
            <?php if ($ct['so_ao'] !== null): ?><span class="mo">#<?= e($ct['so_ao']) ?></span><?php endif; ?>
        </h1>
        <p style="margin:6px 0 16px">
            <?= e(VI_TRI[$ct['vi_tri']] ?? $ct['vi_tri']) ?> ·
            <?= e($ct['ten_doi'] ?? 'Cầu thủ tự do') ?> ·
            <?= nhanTrangThai($ct['trang_thai']) ?>
        </p>
        <div class="thong-tin">
            <div><small>Ngày sinh</small><?= dinhDangNgay($ct['ngay_sinh']) ?> (<?= tinhTuoi($ct['ngay_sinh']) ?> tuổi)</div>
            <div><small>Nơi sinh</small><?= e($ct['noi_sinh'] ?? '—') ?></div>
            <div><small>Quốc tịch</small><?= e($ct['quoc_tich'] ?? '—') ?></div>
            <div><small>Chiều cao / Cân nặng</small>
                <?= $ct['chieu_cao_cm'] ? e($ct['chieu_cao_cm']) . ' cm' : '—' ?> /
                <?= $ct['can_nang_kg'] ? e($ct['can_nang_kg']) . ' kg' : '—' ?></div>
            <div><small>Tay ném</small><?= $ct['tay_nem'] === 'trai' ? 'Trái' : 'Phải' ?></div>
            <div><small>Đại học</small><?= e($ct['truong_dai_hoc'] ?? '—') ?></div>
            <div><small>Kinh nghiệm NBA</small><?= kinhNghiem($ct['nam_ra_mat_nba']) ?></div>
        </div>
    </div>
</div>

<?php if ($chanThuong): ?>
<div class="thong-bao loi" style="margin-top:16px">
    <?php foreach ($chanThuong as $c): ?>
        <div>Đang chấn thương: <strong><?= e($c['loai_chan_thuong']) ?></strong>
            (từ <?= dinhDangNgay($c['ngay_bi']) ?><?= $c['ngay_du_kien_tro_lai'] ? ', dự kiến trở lại ' . dinhDangNgay($c['ngay_du_kien_tro_lai']) : '' ?>)</div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<h2>Chỉ số trung bình mỗi trận</h2>
<?php if (!$chiSo): ?>
    <div class="the mo">Chưa có dữ liệu thi đấu.</div>
<?php else: ?>
<div class="cuon-ngang">
<table>
    <thead>
    <tr>
        <th>Mùa</th><th>Giai đoạn</th><th class="so">Trận</th><th class="so">Phút</th>
        <th class="so">PPG</th><th class="so">RPG</th><th class="so">APG</th>
        <th class="so">STL</th><th class="so">BLK</th>
        <th class="so">FG%</th><th class="so">3P%</th><th class="so">FT%</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($chiSo as $cs): ?>
        <tr>
            <td><?= e($cs['ten_mua_giai']) ?></td>
            <td><?= e(GIAI_DOAN[$cs['giai_doan']] ?? $cs['giai_doan']) ?></td>
            <td class="so"><?= (int) $cs['so_tran'] ?></td>
            <td class="so"><?= $fmt($cs['phut_tb']) ?></td>
            <td class="so"><strong><?= $fmt($cs['diem_tb']) ?></strong></td>
            <td class="so"><?= $fmt($cs['bat_bong_tb']) ?></td>
            <td class="so"><?= $fmt($cs['kien_tao_tb']) ?></td>
            <td class="so"><?= $fmt($cs['cuop_bong_tb']) ?></td>
            <td class="so"><?= $fmt($cs['chan_bong_tb']) ?></td>
            <td class="so"><?= $fmt($cs['ti_le_nem']) ?></td>
            <td class="so"><?= $fmt($cs['ti_le_3_diem']) ?></td>
            <td class="so"><?= $fmt($cs['ti_le_phat']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<h2>Lịch sử chuyển nhượng</h2>
<?php if (!$lichSu): ?>
    <div class="the mo">Chưa có giao dịch nào.</div>
<?php else: ?>
<div class="cuon-ngang">
<table>
    <thead><tr><th>Ngày</th><th>Loại</th><th>Từ đội</th><th>Đến đội</th></tr></thead>
    <tbody>
    <?php foreach ($lichSu as $ls): ?>
        <tr>
            <td><?= dinhDangNgay($ls['ngay_chuyen']) ?></td>
            <td><?= e(LOAI_CHUYEN_NHUONG[$ls['loai']] ?? $ls['loai']) ?></td>
            <td><?= e($ls['ten_doi_cu'] ?? 'Tự do') ?></td>
            <td><?= e($ls['ten_doi_moi'] ?? 'Tự do') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php cuoiTrang();
