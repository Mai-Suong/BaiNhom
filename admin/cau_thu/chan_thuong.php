<?php
/** Admin - Quản lý chấn thương của 1 cầu thủ (TV3) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/giao_dien.php';

$id = (int) ($_GET['id'] ?? 0);
$ct = layCauThu($id);
if (!$ct) {
    datThongBao('loi', 'Không tìm thấy cầu thủ.');
    chuyenTrang(url('/admin/cau_thu/index.php'));
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
        chuyenTrang(url('/admin/cau_thu/chan_thuong.php?id=' . $id));
    } catch (RuntimeException $ex) {
        $loi = $ex->getMessage();
    }
}

$ds = layDanhSachChanThuong($id);

$tieuDe = 'Chấn thương - ' . $ct['ho_ten'];
$menu   = 'cau_thu';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= url('/admin/cau_thu/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách cầu thủ</a></nav>
<h1 class="h3 mb-3 d-flex align-items-center gap-2 flex-wrap">
    Chấn thương: <?= e($ct['ho_ten']) ?> <?= nhanTrangThai($ct['trang_thai']) ?>
</h1>

<?php if ($loi !== ''): ?><div class="alert alert-danger"><?= e($loi) ?></div><?php endif; ?>

<?php if ($ct['trang_thai'] !== 'giai_nghe'): ?>
<div class="card shadow-sm">
    <div class="card-header fw-semibold"><i class="bi bi-bandaid"></i> Thêm chấn thương</div>
    <div class="card-body">
        <form method="post">
            <?= oCsrf() ?>
            <input type="hidden" name="hanh_dong" value="them">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="loai_chan_thuong">Loại chấn thương *</label>
                    <input class="form-control" type="text" id="loai_chan_thuong" name="loai_chan_thuong" maxlength="100" required
                           placeholder="VD: Bong gân cổ chân trái" value="<?= e($f['loai_chan_thuong']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="ngay_bi">Ngày bị *</label>
                    <input class="form-control" type="date" id="ngay_bi" name="ngay_bi" max="<?= date('Y-m-d') ?>" value="<?= e($f['ngay_bi']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="ngay_du_kien_tro_lai">Dự kiến trở lại</label>
                    <input class="form-control" type="date" id="ngay_du_kien_tro_lai" name="ngay_du_kien_tro_lai" value="<?= e($f['ngay_du_kien_tro_lai']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="ghi_chu">Ghi chú</label>
                    <input class="form-control" type="text" id="ghi_chu" name="ghi_chu" maxlength="255" value="<?= e($f['ghi_chu']) ?>">
                </div>
            </div>
            <div class="mt-4"><button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Thêm chấn thương</button></div>
        </form>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-warning">Cầu thủ đã giải nghệ, không thêm chấn thương mới.</div>
<?php endif; ?>

<h2 class="h5 mt-4 mb-3">Lịch sử chấn thương</h2>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr><th>Loại</th><th>Ngày bị</th><th>Dự kiến trở lại</th><th>Tình trạng</th><th>Ghi chú</th><th class="text-end"></th></tr>
            </thead>
            <tbody>
            <?php if (!$ds): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">Chưa có chấn thương nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($ds as $c): ?>
                <tr>
                    <td><?= e($c['loai_chan_thuong']) ?></td>
                    <td class="text-nowrap"><?= dinhDangNgay($c['ngay_bi']) ?></td>
                    <td class="text-nowrap"><?= dinhDangNgay($c['ngay_du_kien_tro_lai']) ?></td>
                    <td><?= $c['da_hoi_phuc']
                            ? '<span class="badge text-bg-success">Đã hồi phục</span>'
                            : '<span class="badge text-bg-danger">Đang điều trị</span>' ?></td>
                    <td><?= e($c['ghi_chu'] ?? '') ?></td>
                    <td class="text-end text-nowrap">
                        <?php if (!$c['da_hoi_phuc']): ?>
                            <form class="d-inline" method="post">
                                <?= oCsrf() ?>
                                <input type="hidden" name="hanh_dong" value="hoi_phuc">
                                <input type="hidden" name="ma_chan_thuong" value="<?= (int) $c['ma_chan_thuong'] ?>">
                                <button class="btn btn-sm btn-outline-success" type="submit">Đã hồi phục</button>
                            </form>
                        <?php endif; ?>
                        <form class="d-inline" method="post">
                            <?= oCsrf() ?>
                            <input type="hidden" name="hanh_dong" value="xoa">
                            <input type="hidden" name="ma_chan_thuong" value="<?= (int) $c['ma_chan_thuong'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-xoa" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
