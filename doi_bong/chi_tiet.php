<?php
/** Trang Khách - Chi tiết đội: thông tin, sân nhà, ban huấn luyện, đội hình, lịch & kết quả (TV2) */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_doi_bong.php';
require_once __DIR__ . '/../includes/ham_trao_giai.php'; // (TV3) layDanhHieuHLV, layLichThiDauDoi, layKetQuaGanDayDoi

$mua   = tv2LayMuaHienTai();
$id    = (int) ($_GET['id'] ?? 0);
$doi   = dbLayChiTiet($id, $mua ? (int) $mua['ma_mua_giai'] : null);

$tieuDe = $doi ? $doi['ten_doi'] : 'Không tìm thấy đội';
$menu   = 'doi_bong';

if (!$doi) {
    http_response_code(404);
    require __DIR__ . '/../includes/header.php';
    echo '<div class="card card-body">Không tìm thấy đội. <a href="' . e(url('/doi_bong/index.php')) . '">Quay lại danh sách đội</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$dsHlv    = hlvLayDanhSach('', (string) $id);
$doiHinh  = dbLayDoiHinh($id);
$lich     = layLichThiDauDoi($id, 5);
$ketQua   = layKetQuaGanDayDoi($id, 5);
$fmtNgay  = fn($s) => $s ? date('d/m/Y H:i', strtotime($s)) : '';

require __DIR__ . '/../includes/header.php';
?>

<nav class="mb-3"><a href="<?= url('/doi_bong/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách đội</a></nav>

<!-- Thông tin đội -->
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap gap-4 align-items-center">
        <?php if ($doi['logo']): ?>
            <img src="<?= e(url('/' . $doi['logo'])) ?>" alt="Logo <?= e($doi['ten_doi']) ?>" width="120" height="120" class="object-fit-contain">
        <?php else: ?>
            <div class="rounded-circle bg-nba text-white fw-bold fs-3 d-flex align-items-center justify-content-center"
                 style="width: 120px; height: 120px"><?= e($doi['ten_viet_tat']) ?></div>
        <?php endif; ?>
        <div class="flex-grow-1">
            <h1 class="h2 mb-1"><?= e($doi['ten_doi']) ?> <span class="text-muted fs-5"><?= e($doi['ten_viet_tat']) ?></span></h1>
            <div class="text-muted mb-2">
                <?= e($doi['thanh_pho']) ?>
                <?php if ($doi['nam_thanh_lap']): ?> · Thành lập <?= e($doi['nam_thanh_lap']) ?><?php endif; ?>
                <?php if ($doi['ten_khu_vuc']): ?> · <?= e($doi['ten_hoi_nghi']) ?>, khu vực <?= e($doi['ten_khu_vuc']) ?><?php endif; ?>
            </div>
            <?php if ($doi['mo_ta']): ?><p class="mb-0"><?= nl2br(e($doi['mo_ta'])) ?></p><?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Sân nhà -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-building"></i> Sân nhà</div>
            <div class="card-body">
                <?php if ($doi['ten_san'] === null): ?>
                    <span class="text-muted">Chưa có thông tin sân nhà.</span>
                <?php else: ?>
                    <?php if ($doi['hinh_anh_san']): ?>
                        <img src="<?= e(url('/' . $doi['hinh_anh_san'])) ?>" alt="" class="img-fluid rounded mb-3">
                    <?php endif; ?>
                    <div class="fw-semibold"><?= e($doi['ten_san']) ?></div>
                    <div class="text-muted small">
                        <?= e($doi['thanh_pho_san']) ?><?= $doi['tieu_bang'] ? ', ' . e($doi['tieu_bang']) : '' ?>
                        <?php if ($doi['suc_chua']): ?> · Sức chứa <?= number_format((int) $doi['suc_chua'], 0, ',', '.') ?> chỗ<?php endif; ?>
                        <?php if ($doi['nam_khanh_thanh']): ?> · Khánh thành <?= e($doi['nam_khanh_thanh']) ?><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ban huấn luyện -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-person-workspace"></i> Ban huấn luyện</div>
            <ul class="list-group list-group-flush">
                <?php if (!$dsHlv): ?>
                    <li class="list-group-item text-muted">Chưa có thông tin HLV.</li>
                <?php endif; ?>
                <?php foreach ($dsHlv as $hlv): $danhHieu = layDanhHieuHLV((int) $hlv['ma_hlv']); ?>
                    <li class="list-group-item d-flex gap-3 align-items-center">
                        <?php if ($hlv['anh']): ?>
                            <img src="<?= e(url('/' . $hlv['anh'])) ?>" alt="" width="48" height="48" class="rounded-circle object-fit-cover">
                        <?php else: ?>
                            <i class="bi bi-person-circle fs-2 text-muted"></i>
                        <?php endif; ?>
                        <div>
                            <div class="fw-semibold"><?= e($hlv['ho_ten']) ?>
                                <span class="badge <?= $hlv['vai_tro'] === 'truong' ? 'text-bg-primary' : 'text-bg-secondary' ?> ms-1">
                                    <?= e(VAI_TRO_HLV[$hlv['vai_tro']] ?? $hlv['vai_tro']) ?>
                                </span>
                            </div>
                            <div class="small text-muted">
                                <?= e($hlv['quoc_tich'] ?? '') ?>
                                <?php if ($hlv['ngay_bat_dau']): ?> · Dẫn dắt từ <?= e(date('d/m/Y', strtotime($hlv['ngay_bat_dau']))) ?><?php endif; ?>
                            </div>
                            <?php foreach ($danhHieu as $dh): ?>
                                <span class="badge text-bg-warning" title="<?= e($dh['ten_giai']) ?>">
                                    <i class="bi bi-trophy"></i> <?= e($dh['ten_viet_tat']) ?> · <?= e($dh['ten_mua_giai']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Đội hình -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-people"></i> Đội hình (<?= count($doiHinh) ?> cầu thủ)</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr><th style="width: 70px">Số áo</th><th>Cầu thủ</th><th>Vị trí</th><th class="text-end">Chiều cao</th>
                        <th class="text-end">Cân nặng</th><th>Quốc tịch</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$doiHinh): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">Chưa có cầu thủ.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($doiHinh as $ct): ?>
                        <tr>
                            <td class="fw-semibold"><?= $ct['so_ao'] !== null ? '#' . (int) $ct['so_ao'] : '' ?></td>
                            <td><a href="<?= url('/cau_thu/ho_so.php?id=' . (int) $ct['ma_cau_thu']) ?>"><?= e($ct['ho_ten']) ?></a></td>
                            <td><?= e($ct['vi_tri']) ?></td>
                            <td class="text-end"><?= $ct['chieu_cao_cm'] ? e($ct['chieu_cao_cm']) . ' cm' : '' ?></td>
                            <td class="text-end"><?= $ct['can_nang_kg'] ? e($ct['can_nang_kg']) . ' kg' : '' ?></td>
                            <td><?= e($ct['quoc_tich']) ?></td>
                            <td><span class="badge <?= $ct['trang_thai'] === 'chan_thuong' ? 'text-bg-danger' : 'text-bg-success' ?>">
                                <?= e(TRANG_THAI_CT[$ct['trang_thai']] ?? $ct['trang_thai']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Lịch thi đấu sắp tới -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-calendar-event"></i> Trận sắp tới</div>
            <ul class="list-group list-group-flush">
                <?php if (!$lich): ?><li class="list-group-item text-muted">Chưa có lịch thi đấu.</li><?php endif; ?>
                <?php foreach ($lich as $td): ?>
                    <li class="list-group-item">
                        <div class="fw-semibold"><?= e($td['ten_doi_khach']) ?> <span class="text-muted">@</span> <?= e($td['ten_doi_nha']) ?></div>
                        <div class="small text-muted"><?= e($fmtNgay($td['thoi_gian'])) ?><?= $td['ten_san'] ? ' · ' . e($td['ten_san']) : '' ?>
                            <?= $td['trang_thai'] === 'hoan' ? ' · <span class="text-danger">Hoãn</span>' : '' ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Kết quả gần đây -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-check2-square"></i> Kết quả gần đây</div>
            <ul class="list-group list-group-flush">
                <?php if (!$ketQua): ?><li class="list-group-item text-muted">Chưa có kết quả.</li><?php endif; ?>
                <?php foreach ($ketQua as $td):
                    $laNha = (int) $td['ma_doi_nha'] === $id;
                    $thang = $laNha ? $td['diem_doi_nha'] > $td['diem_doi_khach'] : $td['diem_doi_khach'] > $td['diem_doi_nha']; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div><?= e($td['ten_doi_khach']) ?> <strong><?= (int) $td['diem_doi_khach'] ?></strong>
                                 – <strong><?= (int) $td['diem_doi_nha'] ?></strong> <?= e($td['ten_doi_nha']) ?></div>
                            <div class="small text-muted"><?= e($fmtNgay($td['thoi_gian'])) ?></div>
                        </div>
                        <span class="badge <?= $thang ? 'text-bg-success' : 'text-bg-danger' ?>"><?= $thang ? 'Thắng' : 'Thua' ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
