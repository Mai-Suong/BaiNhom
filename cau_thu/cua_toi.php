<?php
/** Trang "Của tôi" - cầu thủ đăng nhập xem thông tin, lịch đội mình, đổi ảnh đại diện */
require_once __DIR__ . '/../includes/giao_dien.php';

// Lấy mã cầu thủ từ SESSION (không lấy từ URL) để không xem/sửa được của người khác
$maCauThu = yeuCauCauThu();
$ct = layCauThu($maCauThu);
if (!$ct) {
    exit('Tài khoản chưa được liên kết với cầu thủ. Vui lòng liên hệ quản trị viên.');
}

$loi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    try {
        capNhatAnhDaiDien($maCauThu, $_FILES['anh'] ?? null);
        datThongBao('ok', 'Đã cập nhật ảnh đại diện.');
        chuyenTrang('cua_toi.php');
    } catch (RuntimeException $ex) {
        $loi = $ex->getMessage();
    }
}

$lich   = $ct['ma_doi'] ? layLichThiDauDoi((int) $ct['ma_doi']) : [];
$ketQua = $ct['ma_doi'] ? layKetQuaGanDayDoi((int) $ct['ma_doi']) : [];

dauTrang('Trang của tôi');
?>
<div class="dau">
    <h1>Xin chào, <?= e($ct['ho_ten']) ?></h1>
    <a class="nut phu" href="ho_so.php?id=<?= $maCauThu ?>">Xem hồ sơ công khai</a>
</div>

<?php if ($loi !== ''): ?><div class="thong-bao loi"><?= e($loi) ?></div><?php endif; ?>

<div class="the ho-so">
    <div style="text-align:center">
        <?= anhCauThu($ct, 120) ?>
        <form method="post" enctype="multipart/form-data" style="margin-top:10px">
            <?= oCsrf() ?>
            <input type="file" name="anh" accept="image/jpeg,image/png,image/webp" required style="width:200px">
            <div style="margin-top:6px"><button class="nut nho" type="submit">Đổi ảnh</button></div>
        </form>
    </div>
    <div class="thong-tin">
        <div><small>Đội</small><?= e($ct['ten_doi'] ?? 'Tự do') ?></div>
        <div><small>Số áo</small><?= e($ct['so_ao'] ?? '—') ?></div>
        <div><small>Vị trí</small><?= e(VI_TRI[$ct['vi_tri']] ?? $ct['vi_tri']) ?></div>
        <div><small>Trạng thái</small><?= nhanTrangThai($ct['trang_thai']) ?></div>
        <div><small>Ngày sinh</small><?= dinhDangNgay($ct['ngay_sinh']) ?></div>
        <div><small>Quốc tịch</small><?= e($ct['quoc_tich'] ?? '—') ?></div>
        <div><small>Chiều cao / Cân nặng</small>
            <?= $ct['chieu_cao_cm'] ? e($ct['chieu_cao_cm']) . ' cm' : '—' ?> /
            <?= $ct['can_nang_kg'] ? e($ct['can_nang_kg']) . ' kg' : '—' ?></div>
        <div><small>Kinh nghiệm</small><?= kinhNghiem($ct['nam_ra_mat_nba']) ?></div>
    </div>
</div>

<?php if (!$ct['ma_doi']): ?>
    <h2>Lịch thi đấu</h2>
    <div class="the mo">Bạn đang là cầu thủ tự do nên chưa có lịch thi đấu.</div>
<?php else: ?>
    <h2>Lịch thi đấu sắp tới của <?= e($ct['ten_doi']) ?></h2>
    <div class="cuon-ngang">
    <table>
        <thead><tr><th>Thời gian</th><th>Trận đấu</th><th>Sân</th><th>Ghi chú</th></tr></thead>
        <tbody>
        <?php if (!$lich): ?>
            <tr><td colspan="4" class="mo">Chưa có trận sắp tới.</td></tr>
        <?php endif; ?>
        <?php foreach ($lich as $t): ?>
            <?php $laNha = (int) $t['ma_doi_nha'] === (int) $ct['ma_doi']; ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($t['thoi_gian'])) ?></td>
                <td><?= e($t['ten_doi_khach']) ?> @ <?= e($t['ten_doi_nha']) ?>
                    <span class="mo">(<?= $laNha ? 'sân nhà' : 'sân khách' ?>)</span></td>
                <td><?= e($t['ten_san'] ?? '—') ?></td>
                <td><?= $t['trang_thai'] === 'hoan' ? '<span class="nhan chan_thuong">Hoãn</span>' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <h2>Kết quả gần đây</h2>
    <div class="cuon-ngang">
    <table>
        <thead><tr><th>Ngày</th><th>Trận đấu</th><th class="so">Tỉ số</th><th>Kết quả</th></tr></thead>
        <tbody>
        <?php if (!$ketQua): ?>
            <tr><td colspan="4" class="mo">Chưa có trận nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($ketQua as $t): ?>
            <?php
            $laNha = (int) $t['ma_doi_nha'] === (int) $ct['ma_doi'];
            $thang = $laNha ? $t['diem_doi_nha'] > $t['diem_doi_khach'] : $t['diem_doi_khach'] > $t['diem_doi_nha'];
            ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($t['thoi_gian'])) ?></td>
                <td><?= e($t['ten_doi_khach']) ?> @ <?= e($t['ten_doi_nha']) ?></td>
                <td class="so"><?= (int) $t['diem_doi_khach'] ?> – <?= (int) $t['diem_doi_nha'] ?></td>
                <td><?= $thang ? '<span class="nhan dang_thi_dau">Thắng</span>' : '<span class="nhan chan_thuong">Thua</span>' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<?php cuoiTrang();
