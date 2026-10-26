<?php
/** Trang "Của tôi" - cầu thủ đăng nhập xem thông tin, danh hiệu, lịch đội mình, đổi ảnh đại diện (TV3) */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/giao_dien.php';
require_once __DIR__ . '/../includes/ham_trao_giai.php';

// Lấy mã cầu thủ từ SESSION (không lấy từ URL) để không xem/sửa được của người khác
$maCauThu = yeuCauCauThu();
$ct = layCauThu($maCauThu);

$tieuDe = 'Trang của tôi';
$menu   = '';

if (!$ct) {
    require __DIR__ . '/../includes/header.php';
    echo '<div class="alert alert-warning">Tài khoản chưa được liên kết với cầu thủ. Vui lòng liên hệ quản trị viên.</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$loi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    try {
        capNhatAnhDaiDien($maCauThu, $_FILES['anh'] ?? null);
        datThongBao('ok', 'Đã cập nhật ảnh đại diện.');
        chuyenTrang(url('/cau_thu/cua_toi.php'));
    } catch (RuntimeException $ex) {
        $loi = $ex->getMessage();
    }
}

$danhHieu = layDanhHieuCauThu($maCauThu);
$lich     = $ct['ma_doi'] ? layLichThiDauDoi((int) $ct['ma_doi']) : [];
$ketQua   = $ct['ma_doi'] ? layKetQuaGanDayDoi((int) $ct['ma_doi']) : [];

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Xin chào, <?= e($ct['ho_ten']) ?></h1>
    <a class="btn btn-outline-primary" href="<?= url('/cau_thu/ho_so.php?id=' . $maCauThu) ?>"><i class="bi bi-person-vcard"></i> Xem hồ sơ công khai</a>
</div>

<?php if ($loi !== ''): ?><div class="alert alert-danger"><?= e($loi) ?></div><?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap gap-4 align-items-start">
        <div class="text-center" style="width: 220px">
            <?= anhCauThu($ct, 120) ?>
            <form class="mt-3" method="post" enctype="multipart/form-data">
                <?= oCsrf() ?>
                <input class="form-control form-control-sm" type="file" name="anh" accept="image/jpeg,image/png,image/webp" required>
                <button class="btn btn-sm btn-primary mt-2" type="submit"><i class="bi bi-upload"></i> Đổi ảnh đại diện</button>
                <div class="form-text">JPG, PNG, WEBP, tối đa 2MB</div>
            </form>
        </div>
        <div class="flex-grow-1">
            <div class="row row-cols-2 row-cols-md-4 g-3 small">
                <div><div class="text-muted">Đội</div><?= e($ct['ten_doi'] ?? 'Không có đội') ?></div>
                <div><div class="text-muted">Số áo</div><?= $ct['so_ao'] !== null ? '#' . (int) $ct['so_ao'] : '—' ?></div>
                <div><div class="text-muted">Vị trí</div><?= e(VI_TRI[$ct['vi_tri']] ?? $ct['vi_tri']) ?></div>
                <div><div class="text-muted">Trạng thái</div><?= nhanTrangThai($ct['trang_thai']) ?></div>
                <div><div class="text-muted">Ngày sinh</div><?= dinhDangNgay($ct['ngay_sinh']) ?></div>
                <div><div class="text-muted">Quốc tịch</div><?= e($ct['quoc_tich'] ?? '—') ?></div>
                <div><div class="text-muted">Chiều cao / Cân nặng</div>
                    <?= $ct['chieu_cao_cm'] ? e($ct['chieu_cao_cm']) . ' cm' : '—' ?> /
                    <?= $ct['can_nang_kg'] ? e($ct['can_nang_kg']) . ' kg' : '—' ?></div>
                <div><div class="text-muted">Kinh nghiệm</div><?= kinhNghiem($ct['nam_ra_mat_nba']) ?></div>
            </div>
            <p class="text-muted small mt-3 mb-0">Thông tin cá nhân do quản trị viên cập nhật. Bạn chỉ có thể đổi ảnh đại diện.</p>
        </div>
    </div>
</div>

<h2 class="h5 mb-3"><i class="bi bi-trophy"></i> Danh hiệu của tôi</h2>
<?= hienThiDanhHieu($danhHieu) ?>

<?php if (!$ct['ma_doi']): ?>
    <div class="card card-body text-muted mt-4">Bạn hiện không thuộc đội nào nên chưa có lịch thi đấu.</div>
<?php else: ?>
    <div class="row g-4 mt-0">
        <div class="col-lg-7">
            <div class="card h-100 shadow-sm">
                <div class="card-header fw-semibold"><i class="bi bi-calendar-event"></i> Lịch thi đấu sắp tới của <?= e($ct['ten_doi']) ?></div>
                <ul class="list-group list-group-flush">
                    <?php if (!$lich): ?>
                        <li class="list-group-item text-muted">Chưa có trận sắp tới.</li>
                    <?php endif; ?>
                    <?php foreach ($lich as $t): ?>
                        <?php $laNha = (int) $t['ma_doi_nha'] === (int) $ct['ma_doi']; ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold"><?= e($t['ten_doi_khach']) ?> @ <?= e($t['ten_doi_nha']) ?></div>
                                <div class="small text-muted">
                                    <?= $laNha ? 'Sân nhà' : 'Sân khách' ?><?= $t['ten_san'] ? ' · ' . e($t['ten_san']) : '' ?>
                                </div>
                            </div>
                            <div class="text-end small">
                                <?= date('d/m/Y H:i', strtotime($t['thoi_gian'])) ?>
                                <?php if ($t['trang_thai'] === 'hoan'): ?><span class="badge text-bg-warning ms-1">Hoãn</span><?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100 shadow-sm">
                <div class="card-header fw-semibold"><i class="bi bi-clipboard-check"></i> Kết quả gần đây</div>
                <ul class="list-group list-group-flush">
                    <?php if (!$ketQua): ?>
                        <li class="list-group-item text-muted">Chưa có trận nào.</li>
                    <?php endif; ?>
                    <?php foreach ($ketQua as $t): ?>
                        <?php
                        $laNha = (int) $t['ma_doi_nha'] === (int) $ct['ma_doi'];
                        $thang = $laNha ? $t['diem_doi_nha'] > $t['diem_doi_khach'] : $t['diem_doi_khach'] > $t['diem_doi_nha'];
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div><?= e($t['ten_doi_khach']) ?> @ <?= e($t['ten_doi_nha']) ?></div>
                                <div class="small text-muted"><?= date('d/m/Y', strtotime($t['thoi_gian'])) ?></div>
                            </div>
                            <div class="text-nowrap">
                                <span class="fw-semibold me-2"><?= (int) $t['diem_doi_khach'] ?> – <?= (int) $t['diem_doi_nha'] ?></span>
                                <span class="badge <?= $thang ? 'text-bg-success' : 'text-bg-danger' ?>"><?= $thang ? 'Thắng' : 'Thua' ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
