<?php
/**
 * Trang Khách - Chi tiết đội theo mùa (?id=&mua=): thông tin, thành tích, sân nhà, ban huấn luyện,
 * đội hình, lịch & kết quả của mùa đó (TV2)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_trang_khach.php';
require_once __DIR__ . '/../includes/ham_trao_giai.php'; // (TV3) layDanhHieuHLV, TRANG_THAI_CT

$dsMua = tkhLayDsMua();
$mua   = tkhChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
$maMua = $mua ? (int) $mua['ma_mua_giai'] : null;
$id    = (int) ($_GET['id'] ?? 0);
$doi   = tkhLayChiTietDoi($id, $maMua);

$tieuDe = $doi ? $doi['ten_doi'] : 'Không tìm thấy đội';
$menu   = 'doi_bong';

if (!$doi) {
    http_response_code(404);
    require __DIR__ . '/../includes/header.php';
    echo '<div class="card card-body">Không tìm thấy đội. <a href="' . e(url('/doi_bong/index.php')) . '">Quay lại danh sách đội</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$laHienTai = tkhLaMuaHienTai($mua);
$san       = tkhLaySanNhaMua($doi, $mua);
$dsHlv     = tkhLayBanHuanLuyen($id, $mua);
$doiHinh   = tkhLayDoiHinh($id, $mua);
$thanhTich = $maMua ? tkhLayThanhTich($id, $maMua) : null;
$lich      = $maMua ? tkhLayLichMua($id, $maMua, 5) : [];
$ketQua    = $maMua ? tkhLayKetQuaMua($id, $maMua, 5) : [];
$fmtNgay   = fn($s) => $s ? date('d/m/Y H:i', strtotime($s)) : '';
$ngay      = fn($s) => $s ? date('d/m/Y', strtotime($s)) : '';
/** Link sang trang lịch / kết quả (TV4) của đội trong mùa đang xem */
$linkTv4   = fn(string $duongDan) => url($duongDan) . '?' . http_build_query(['mua' => $maMua, 'doi' => $id]);

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <a href="<?= e(url('/doi_bong/index.php') . ($maMua ? '?mua=' . $maMua : '')) ?>"><i class="bi bi-arrow-left"></i> Danh sách đội</a>
    <?php if (count($dsMua) > 1): ?>
        <form method="get" class="d-flex gap-2">
            <input type="hidden" name="id" value="<?= $id ?>">
            <select class="form-select w-auto" name="mua" aria-label="Mùa giải" onchange="this.form.submit()">
                <?php foreach ($dsMua as $m): ?>
                    <option value="<?= (int) $m['ma_mua_giai'] ?>"<?= (int) $m['ma_mua_giai'] === $maMua ? ' selected' : '' ?>>Mùa <?= e($m['ten_mua_giai']) ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button class="btn btn-outline-secondary" type="submit">Xem</button></noscript>
        </form>
    <?php endif; ?>
</div>

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
        <?php if ($mua): ?>
            <div class="text-center px-3">
                <div class="small text-muted">Mùa <?= e($mua['ten_mua_giai']) ?></div>
                <?php if ($thanhTich): ?>
                    <div class="fs-2 fw-bold"><?= (int) $thanhTich['thang'] ?> - <?= (int) $thanhTich['thua'] ?></div>
                    <div class="small text-muted">
                        Sân nhà <?= (int) $thanhTich['thang_san_nha'] ?>-<?= (int) $thanhTich['thua_san_nha'] ?> ·
                        Sân khách <?= (int) $thanhTich['thang_san_khach'] ?>-<?= (int) $thanhTich['thua_san_khach'] ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted">Chưa có kết quả</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Sân nhà -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-building"></i> Sân nhà<?= $mua ? ' mùa ' . e($mua['ten_mua_giai']) : '' ?></div>
            <div class="card-body">
                <?php if (!$san): ?>
                    <span class="text-muted">Chưa có thông tin sân nhà.</span>
                <?php else: ?>
                    <?php if ($san['hinh_anh']): ?>
                        <img src="<?= e(url('/' . $san['hinh_anh'])) ?>" alt="" class="img-fluid rounded mb-3">
                    <?php endif; ?>
                    <div class="fw-semibold"><?= e(implode(' → ', $san['ten_trong_mua'])) ?></div>
                    <?php if (count($san['ten_trong_mua']) > 1): ?>
                        <div class="small text-muted">Sân đổi tên trong mùa.</div>
                    <?php endif; ?>
                    <?php if (end($san['ten_trong_mua']) !== $san['ten_san']): ?>
                        <div class="small text-muted">Tên hiện nay: <?= e($san['ten_san']) ?></div>
                    <?php endif; ?>
                    <div class="text-muted small">
                        <?= e($san['thanh_pho']) ?><?= $san['tieu_bang'] ? ', ' . e($san['tieu_bang']) : '' ?>
                        <?php if ($san['suc_chua']): ?> · Sức chứa <?= number_format((int) $san['suc_chua'], 0, ',', '.') ?> chỗ<?php endif; ?>
                        <?php if ($san['nam_khanh_thanh']): ?> · Khánh thành <?= e($san['nam_khanh_thanh']) ?><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ban huấn luyện -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-person-workspace"></i> Ban huấn luyện<?= $mua ? ' mùa ' . e($mua['ten_mua_giai']) : '' ?></div>
            <ul class="list-group list-group-flush">
                <?php if (!$dsHlv): ?>
                    <li class="list-group-item text-muted">Chưa có thông tin HLV mùa này.</li>
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
                                    <?= e(VAI_TRO_HLV[$hlv['vai_tro']] ?? $hlv['vai_tro']) ?></span>
                                <?php if ((int) $hlv['tam_quyen'] === 1): ?><span class="badge text-bg-warning">Tạm quyền</span><?php endif; ?>
                            </div>
                            <div class="small text-muted">
                                <?= e($hlv['quoc_tich'] ?? '') ?>
                                <?php if ($hlv['tu_ngay']): ?>
                                    · <?= $hlv['den_ngay'] ? 'Từ ' . e($ngay($hlv['tu_ngay'])) . ' đến ' . e($ngay($hlv['den_ngay']))
                                                           : 'Dẫn dắt từ ' . e($ngay($hlv['tu_ngay'])) ?>
                                <?php endif; ?>
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
            <div class="card-header fw-semibold">
                <i class="bi bi-people"></i> Đội hình<?= $mua ? ' mùa ' . e($mua['ten_mua_giai']) : '' ?> (<?= count($doiHinh) ?> cầu thủ)
                <?php if (!$laHienTai && $mua): ?><span class="small text-muted fw-normal">· cầu thủ đã ra sân cho đội trong mùa</span><?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <?php if ($laHienTai || !$mua): ?><th style="width: 70px">Số áo</th><?php endif; ?>
                        <th>Cầu thủ</th><th>Vị trí</th><th class="text-end">Chiều cao</th><th class="text-end">Cân nặng</th><th>Quốc tịch</th>
                        <?php if ($laHienTai || !$mua): ?><th>Trạng thái</th><?php else: ?><th class="text-end">Số trận</th><?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$doiHinh): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">
                            <?= ($laHienTai || !$mua) ? 'Chưa có cầu thủ.' : 'Chưa có dữ liệu thi đấu của đội trong mùa này.' ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($doiHinh as $ct): ?>
                        <tr>
                            <?php if ($laHienTai || !$mua): ?><td class="fw-semibold"><?= $ct['so_ao'] !== null ? '#' . (int) $ct['so_ao'] : '' ?></td><?php endif; ?>
                            <td><a href="<?= url('/cau_thu/ho_so.php?id=' . (int) $ct['ma_cau_thu']) ?>"><?= e($ct['ho_ten']) ?></a></td>
                            <td><?= e($ct['vi_tri']) ?></td>
                            <td class="text-end"><?= $ct['chieu_cao_cm'] ? e($ct['chieu_cao_cm']) . ' cm' : '' ?></td>
                            <td class="text-end"><?= $ct['can_nang_kg'] ? e($ct['can_nang_kg']) . ' kg' : '' ?></td>
                            <td><?= e($ct['quoc_tich']) ?></td>
                            <?php if ($laHienTai || !$mua): ?>
                                <td><span class="badge <?= $ct['trang_thai'] === 'chan_thuong' ? 'text-bg-danger' : 'text-bg-success' ?>">
                                    <?= e(TRANG_THAI_CT[$ct['trang_thai']] ?? $ct['trang_thai']) ?></span></td>
                            <?php else: ?>
                                <td class="text-end"><?= (int) $ct['so_tran'] ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Trận chưa đấu tiếp theo trong mùa -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold d-flex justify-content-between">
                <span><i class="bi bi-calendar-event"></i> Trận sắp tới</span>
                <?php if ($maMua): ?><a class="small fw-normal" href="<?= e($linkTv4('/lich_thi_dau/index.php')) ?>">Xem lịch cả mùa</a><?php endif; ?>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (!$lich): ?><li class="list-group-item text-muted">Không còn trận chưa đấu trong mùa này.</li><?php endif; ?>
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

    <!-- Kết quả gần đây trong mùa -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold d-flex justify-content-between">
                <span><i class="bi bi-check2-square"></i> Kết quả gần đây</span>
                <?php if ($maMua): ?><a class="small fw-normal" href="<?= e($linkTv4('/ket_qua/index.php')) ?>">Xem tất cả kết quả</a><?php endif; ?>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (!$ketQua): ?><li class="list-group-item text-muted">Chưa có kết quả trong mùa này.</li><?php endif; ?>
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
