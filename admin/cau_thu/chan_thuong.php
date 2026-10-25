<?php
/** Admin - Quản lý chấn thương của 1 cầu thủ */
require_once __DIR__ . '/../../includes/giao_dien.php';
yeuCauAdmin();

$id = (int) ($_GET['id'] ?? 0);
$ct = layCauThu($id);
if (!$ct) {
    datThongBao('loi', 'Không tìm thấy cầu thủ.');
    chuyenTrang('index.php');
}

$f = ['loai_chan_thuong' => '', 'ngay_bi' => date('Y-m-d'), 'ngay_du_kien_tro_lai' => '', 'ghi_chu' => ''];
$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $hanhDong = $_POST['hanh_dong'] ?? '';
    $maCT     = (int) ($_POST['ma_chan_thuong'] ?? 0);

    try {
        if ($hanhDong === 'them') {
            foreach ($f as $k => $_) {
                $f[$k] = trim((string) ($_POST[$k] ?? ''));
            }
            themChanThuong($id, $f['loai_chan_thuong'], $f['ngay_bi'], $f['ngay_du_kien_tro_lai'], $f['ghi_chu']);
            datThongBao('ok', 'Đã thêm chấn thương.');
        } elseif ($hanhDong === 'hoi_phuc') {
            danhDauHoiPhuc($id, $maCT);
            datThongBao('ok', 'Đã đánh dấu hồi phục.');
        } elseif ($hanhDong === 'xoa') {
            xoaChanThuong($id, $maCT);
            datThongBao('ok', 'Đã xóa bản ghi chấn thương.');
        }
        chuyenTrang('chan_thuong.php?id=' . $id);
    } catch (RuntimeException $ex) {
        $loi = $ex->getMessage();
    }
}

$ds = layDanhSachChanThuong($id);
dauTrang('Chấn thương - ' . $ct['ho_ten']);
?>
<div class="dau">
    <h1>Chấn thương: <?= e($ct['ho_ten']) ?></h1>
    <a class="nut phu" href="index.php">← Danh sách</a>
</div>
<p>Trạng thái hiện tại: <?= nhanTrangThai($ct['trang_thai']) ?></p>

<?php if ($loi !== ''): ?><div class="thong-bao loi"><?= e($loi) ?></div><?php endif; ?>

<?php if ($ct['trang_thai'] !== 'giai_nghe'): ?>
<form class="the" method="post">
    <?= oCsrf() ?>
    <input type="hidden" name="hanh_dong" value="them">
    <div class="luoi-form">
        <div class="truong">
            <label for="loai_chan_thuong">Loại chấn thương *</label>
            <input type="text" id="loai_chan_thuong" name="loai_chan_thuong" maxlength="100" required
                   placeholder="VD: Bong gân cổ chân trái" value="<?= e($f['loai_chan_thuong']) ?>">
        </div>
        <div class="truong">
            <label for="ngay_bi">Ngày bị *</label>
            <input type="date" id="ngay_bi" name="ngay_bi" max="<?= date('Y-m-d') ?>" value="<?= e($f['ngay_bi']) ?>" required>
        </div>
        <div class="truong">
            <label for="ngay_du_kien_tro_lai">Dự kiến trở lại</label>
            <input type="date" id="ngay_du_kien_tro_lai" name="ngay_du_kien_tro_lai" value="<?= e($f['ngay_du_kien_tro_lai']) ?>">
        </div>
        <div class="truong">
            <label for="ghi_chu">Ghi chú</label>
            <input type="text" id="ghi_chu" name="ghi_chu" maxlength="255" value="<?= e($f['ghi_chu']) ?>">
        </div>
    </div>
    <div style="margin-top:16px"><button class="nut" type="submit">Thêm chấn thương</button></div>
</form>
<?php endif; ?>

<h2>Lịch sử chấn thương</h2>
<div class="cuon-ngang">
<table>
    <thead><tr><th>Loại</th><th>Ngày bị</th><th>Dự kiến trở lại</th><th>Tình trạng</th><th>Ghi chú</th><th></th></tr></thead>
    <tbody>
    <?php if (!$ds): ?>
        <tr><td colspan="6" class="mo">Chưa có chấn thương nào.</td></tr>
    <?php endif; ?>
    <?php foreach ($ds as $c): ?>
        <tr>
            <td><?= e($c['loai_chan_thuong']) ?></td>
            <td><?= dinhDangNgay($c['ngay_bi']) ?></td>
            <td><?= dinhDangNgay($c['ngay_du_kien_tro_lai']) ?></td>
            <td><?= $c['da_hoi_phuc'] ? '<span class="nhan dang_thi_dau">Đã hồi phục</span>' : '<span class="nhan chan_thuong">Đang điều trị</span>' ?></td>
            <td><?= e($c['ghi_chu'] ?? '') ?></td>
            <td style="white-space:nowrap">
                <?php if (!$c['da_hoi_phuc']): ?>
                <form class="inline" method="post">
                    <?= oCsrf() ?>
                    <input type="hidden" name="hanh_dong" value="hoi_phuc">
                    <input type="hidden" name="ma_chan_thuong" value="<?= $c['ma_chan_thuong'] ?>">
                    <button class="nut phu nho" type="submit">Đã hồi phục</button>
                </form>
                <?php endif; ?>
                <form class="inline" method="post" onsubmit="return confirm('Xóa bản ghi chấn thương này?')">
                    <?= oCsrf() ?>
                    <input type="hidden" name="hanh_dong" value="xoa">
                    <input type="hidden" name="ma_chan_thuong" value="<?= $c['ma_chan_thuong'] ?>">
                    <button class="nut do nho" type="submit">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php cuoiTrang();
