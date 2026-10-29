<?php
/** Trang Khách - Hồ sơ cầu thủ: thông tin, danh hiệu, chỉ số trung bình mùa, chuyển nhượng (TV3) */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/giao_dien.php';
require_once __DIR__ . '/../includes/ham_trao_giai.php';

$id = (int) ($_GET['id'] ?? 0);
$ct = layCauThu($id);

$tieuDe = $ct ? $ct['ho_ten'] : 'Không tìm thấy cầu thủ';
$menu   = 'cau_thu';

if (!$ct) {
    http_response_code(404);
    require __DIR__ . '/../includes/header.php';
    echo '<div class="card card-body">Không tìm thấy cầu thủ. <a href="' . e(url('/cau_thu/index.php')) . '">Quay lại danh sách cầu thủ</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$chiSo      = layChiSoTrungBinh($id);
$chanThuong = layDanhSachChanThuong($id, true);
$lichSu     = layLichSuChuyenNhuong($id);
$danhHieu   = layDanhHieuCauThu($id);
$doiTheoMua = layDoiTheoMuaCuaCauThu($id);
$fmt = fn($v) => $v === null ? '—' : number_format((float) $v, 1);

require __DIR__ . '/../includes/header.php';
?>

<nav class="mb-3"><a href="<?= url('/cau_thu/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách cầu thủ</a></nav>

<!-- Thông tin cầu thủ -->
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap gap-4 align-items-center">
        <?= anhCauThu($ct, 128) ?>
        <div class="flex-grow-1">
            <h1 class="h2 mb-1">
                <?= e($ct['ho_ten']) ?>
                <?php if ($ct['so_ao'] !== null): ?><span class="text-muted fs-4">#<?= (int) $ct['so_ao'] ?></span><?php endif; ?>
            </h1>
            <div class="text-muted mb-3 d-flex flex-wrap align-items-center gap-2">
                <span><?= e(VI_TRI[$ct['vi_tri']] ?? $ct['vi_tri']) ?></span> ·
                <?php if ($ct['ma_doi']): ?>
                    <a href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $ct['ma_doi']) ?>"><?= e($ct['ten_doi']) ?></a>
                <?php else: ?>
                    <span>Không có đội</span>
                <?php endif; ?>
                · <?= nhanTrangThai($ct['trang_thai']) ?>
            </div>
            <div class="row row-cols-2 row-cols-md-4 g-3 small">
                <div><div class="text-muted">Ngày sinh</div>
                    <?= dinhDangNgay($ct['ngay_sinh']) ?><?= $ct['ngay_sinh'] ? ' (' . tinhTuoi($ct['ngay_sinh']) . ' tuổi)' : '' ?></div>
                <div><div class="text-muted">Nơi sinh</div><?= e($ct['noi_sinh'] ?? '—') ?></div>
                <div><div class="text-muted">Quốc tịch</div><?= e($ct['quoc_tich'] ?? '—') ?></div>
                <div><div class="text-muted">Chiều cao / Cân nặng</div>
                    <?= $ct['chieu_cao_cm'] ? e($ct['chieu_cao_cm']) . ' cm' : '—' ?> /
                    <?= $ct['can_nang_kg'] ? e($ct['can_nang_kg']) . ' kg' : '—' ?></div>
                <div><div class="text-muted">Tay ném</div><?= $ct['tay_nem'] === 'trai' ? 'Trái' : 'Phải' ?></div>
                <div><div class="text-muted">Đại học</div><?= e($ct['truong_dai_hoc'] ?? '—') ?></div>
                <div><div class="text-muted">Kinh nghiệm NBA</div><?= kinhNghiem($ct['nam_ra_mat_nba']) ?></div>
            </div>
        </div>
    </div>
</div>

<?php foreach ($chanThuong as $c): ?>
    <div class="alert alert-danger">
        <i class="bi bi-bandaid"></i> Đang chấn thương: <strong><?= e($c['loai_chan_thuong']) ?></strong>
        (từ <?= dinhDangNgay($c['ngay_bi']) ?><?= $c['ngay_du_kien_tro_lai'] ? ', dự kiến trở lại ' . dinhDangNgay($c['ngay_du_kien_tro_lai']) : '' ?>)
    </div>
<?php endforeach; ?>

<h2 class="h5 mb-3"><i class="bi bi-trophy"></i> Danh hiệu</h2>
<?= hienThiDanhHieu($danhHieu) ?>

<h2 class="h5 mt-4 mb-3"><i class="bi bi-bar-chart"></i> Chỉ số trung bình mỗi trận</h2>
<?php if (!$chiSo): ?>
    <div class="card card-body text-muted">Chưa có dữ liệu thi đấu.</div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                <tr>
                    <th>Mùa</th><th>Giai đoạn</th><th class="text-end">Trận</th><th class="text-end">Phút</th>
                    <th class="text-end">PPG</th><th class="text-end">RPG</th><th class="text-end">APG</th>
                    <th class="text-end">STL</th><th class="text-end">BLK</th>
                    <th class="text-end">FG%</th><th class="text-end">3P%</th><th class="text-end">FT%</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($chiSo as $cs): ?>
                    <tr>
                        <td><?= e($cs['ten_mua_giai']) ?></td>
                        <td><?= e(GIAI_DOAN[$cs['giai_doan']] ?? $cs['giai_doan']) ?></td>
                        <td class="text-end"><?= (int) $cs['so_tran'] ?></td>
                        <td class="text-end"><?= $fmt($cs['phut_tb']) ?></td>
                        <td class="text-end fw-semibold"><?= $fmt($cs['diem_tb']) ?></td>
                        <td class="text-end"><?= $fmt($cs['bat_bong_tb']) ?></td>
                        <td class="text-end"><?= $fmt($cs['kien_tao_tb']) ?></td>
                        <td class="text-end"><?= $fmt($cs['cuop_bong_tb']) ?></td>
                        <td class="text-end"><?= $fmt($cs['chan_bong_tb']) ?></td>
                        <td class="text-end"><?= $fmt($cs['ti_le_nem']) ?></td>
                        <td class="text-end"><?= $fmt($cs['ti_le_3_diem']) ?></td>
                        <td class="text-end"><?= $fmt($cs['ti_le_phat']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($doiTheoMua): ?>
    <h2 class="h5 mt-4 mb-3"><i class="bi bi-shield"></i> Đội theo mùa</h2>
    <p class="text-muted small">Mùa hiện tại: đội đang thi đấu. Mùa trước: các đội đã ra sân theo box score.</p>
    <div class="card shadow-sm">
        <ul class="list-group list-group-flush">
            <?php foreach ($doiTheoMua as $m): ?>
                <li class="list-group-item d-flex flex-wrap gap-2 align-items-center">
                    <span class="fw-semibold" style="width: 80px"><?= e($m['ten_mua_giai']) ?></span>
                    <?php foreach ($m['ds_doi'] as $i => $d): ?>
                        <?php if ($i > 0): ?><i class="bi bi-arrow-right text-muted"></i><?php endif; ?>
                        <a href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $d['ma_doi']) ?>"><?= e($d['ten_doi']) ?></a>
                        <?php if ($d['so_tran'] !== null): ?><span class="text-muted small">(<?= (int) $d['so_tran'] ?> trận)</span><?php endif; ?>
                    <?php endforeach; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<h2 class="h5 mt-4 mb-3"><i class="bi bi-arrow-left-right"></i> Lịch sử chuyển nhượng</h2>
<?php if (!$lichSu): ?>
    <div class="card card-body text-muted">Chưa có giao dịch nào.</div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Ngày</th><th>Loại</th><th>Từ đội</th><th>Đến đội</th><th>Ghi chú</th></tr></thead>
                <tbody>
                <?php foreach ($lichSu as $ls): ?>
                    <tr>
                        <td class="text-nowrap"><?= dinhDangNgay($ls['ngay_chuyen']) ?></td>
                        <td><?= e(LOAI_CHUYEN_NHUONG[$ls['loai']] ?? $ls['loai']) ?></td>
                        <td><?= e($ls['ten_doi_cu'] ?? 'Tự do') ?></td>
                        <td><?= e($ls['ten_doi_moi'] ?? 'Tự do') ?></td>
                        <td><?= e($ls['ghi_chu'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
