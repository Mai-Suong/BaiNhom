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

<nav class="thanh-dieu-huong mb-3">
    <a class="btn btn-sm btn-outline-secondary rounded-pill px-3" href="<?= url('/cau_thu/index.php') ?>">
        <i class="bi bi-chevron-left"></i> Danh sách cầu thủ
    </a>
    <?php if (laAdmin()): ?>
        <?php
        // Chỉ admin thấy: 1 nút quay lại đúng trang quản trị vừa đi sang
        // (từ trang Trao giải thì link có ?tu=trao_giai&mua=..., còn lại là trang Cầu thủ)
        $tuTraoGiai = ($_GET['tu'] ?? '') === 'trao_giai';
        $muaQuayLai = (int) ($_GET['mua'] ?? 0);
        ?>
        <?php if ($tuTraoGiai): ?>
            <a class="btn btn-sm rounded-pill px-3 nut-quan-tri"
               href="<?= url('/admin/trao_giai/index.php' . ($muaQuayLai ? '?mua=' . $muaQuayLai : '')) ?>"
               title="Về trang quản trị trao giải">
                <i class="bi bi-trophy-fill"></i> Quản trị trao giải
            </a>
        <?php else: ?>
            <a class="btn btn-sm rounded-pill px-3 nut-quan-tri" href="<?= url('/admin/cau_thu/index.php') ?>"
               title="Về trang quản trị cầu thủ">
                <i class="bi bi-people-fill"></i> Quản trị cầu thủ
            </a>
        <?php endif; ?>
    <?php endif; ?>
</nav>
<style>
    /* Thanh điều hướng đầu trang hồ sơ (TV3) */
    .thanh-dieu-huong { display: flex; justify-content: space-between; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .thanh-dieu-huong .btn { font-weight: 600; }
    .nut-quan-tri {
        background: var(--nba-toi, #0b1d3a); color: #fff !important; border: 0;
        box-shadow: 0 2px 6px rgba(11, 29, 58, .25); transition: transform .15s, box-shadow .15s, background .15s;
    }
    .nut-quan-tri:hover { background: var(--nba-do, #c8102e); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(200, 16, 46, .3); }
    .nut-quan-tri .bi { transition: transform .3s; display: inline-block; }
    .nut-quan-tri:hover .bi { transform: scale(1.2) rotate(-10deg); }
</style>

<!-- Đầu trang: khung nổi bật màu đội (cùng kiểu khung cầu thủ nổi bật ở trang chủ) -->
<div class="noi-bat mb-4" style="background: <?= e(gdMauDoi($ct['ten_viet_tat'] ?? null)) ?>">
    <div class="noi-bat-anh">
        <?php if ($ct['so_ao'] !== null): ?><span class="so-ao-mo" aria-hidden="true"><?= (int) $ct['so_ao'] ?></span><?php endif; ?>
        <?= gdAnhCauThu($ct['anh'] ?? null, $ct['ho_ten']) ?>
    </div>
    <div class="noi-bat-noi-dung">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if ($ct['ma_doi']): ?>
                <a class="d-inline-flex align-items-center gap-2 text-white text-decoration-none fw-semibold"
                   href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $ct['ma_doi']) ?>">
                    <?= gdHuyHieu($ct['logo_doi'] ?? null, (string) ($ct['ten_viet_tat'] ?? ''), (string) $ct['ten_doi'], 36) ?>
                    <?= e($ct['ten_doi']) ?>
                </a>
            <?php else: ?>
                <span class="fw-semibold">Không có đội</span>
            <?php endif; ?>
            <span class="noi-bat-phu">
                <?= $ct['so_ao'] !== null ? '· #' . (int) $ct['so_ao'] : '' ?> · <?= e(VI_TRI[$ct['vi_tri']] ?? $ct['vi_tri']) ?>
            </span>
        </div>
        <h1 class="noi-bat-ten"><?= e($ct['ho_ten']) ?></h1>
        <div><?= nhanTrangThai($ct['trang_thai']) ?></div>
        <?php $csMoi = $chiSo[0] ?? null; ?>
        <?php if ($csMoi): ?>
            <div class="noi-bat-phu small mt-2">Trung bình mỗi trận · <?= e($csMoi['ten_mua_giai']) ?> (<?= e(GIAI_DOAN[$csMoi['giai_doan']] ?? $csMoi['giai_doan']) ?>)</div>
            <div class="noi-bat-so">
                <div><div class="so"><?= $fmt($csMoi['diem_tb']) ?></div><div class="nhan">Điểm</div></div>
                <div><div class="so"><?= $fmt($csMoi['bat_bong_tb']) ?></div><div class="nhan">Bắt bóng</div></div>
                <div><div class="so"><?= $fmt($csMoi['kien_tao_tb']) ?></div><div class="nhan">Kiến tạo</div></div>
            </div>
        <?php else: ?>
            <div class="noi-bat-so mt-2">
                <div><div class="so"><?= $ct['chieu_cao_cm'] ? (int) $ct['chieu_cao_cm'] : '—' ?></div><div class="nhan">Chiều cao (cm)</div></div>
                <div><div class="so"><?= $ct['can_nang_kg'] ? (int) $ct['can_nang_kg'] : '—' ?></div><div class="nhan">Cân nặng (kg)</div></div>
                <div><div class="so"><?= tinhTuoi($ct['ngay_sinh']) ?></div><div class="nhan">Tuổi</div></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Thông tin cá nhân -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-person-vcard"></i> Thông tin</div>
    <div class="card-body">
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
                        <?= tenDoiCoLogo($d['logo'] ?? null, $d['ten_viet_tat'] ?? null, $d['ten_doi'], '', 24, (int) $d['ma_doi']) ?>
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
                        <td><?= tenDoiCoLogo($ls['logo_doi_cu'], $ls['vt_doi_cu'], $ls['ten_doi_cu'], 'Tự do') ?></td>
                        <td><?= tenDoiCoLogo($ls['logo_doi_moi'], $ls['vt_doi_moi'], $ls['ten_doi_moi'], 'Tự do') ?></td>
                        <td><?= e($ls['ghi_chu'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
