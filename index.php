<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/ham_trang_chu.php'; // TV2: dữ liệu 3 khung bên dưới

$mua        = tv2LayMuaHienTai();
$maMua      = $mua ? (int) $mua['ma_mua_giai'] : null;
$tranSapToi = tcTranSapToi($maMua, 5);
$ketQua     = tcKetQuaMoiNhat($maMua, 5);
$topCauThu  = tcTopCauThu($maMua, 5);
$fmtNgay    = fn($s) => date('d/m H:i', strtotime($s));

$tieuDe = 'Trang chủ';
$menu   = 'trang_chu';
require __DIR__ . '/includes/header.php';
?>

<div class="p-5 mb-4 rounded-3 hero">
    <h1 class="display-5 fw-bold">Giải Bóng rổ Nhà nghề Mỹ</h1>
    <p class="fs-5 mb-0">Thông tin đội bóng, cầu thủ, lịch thi đấu, kết quả và bảng xếp hạng NBA.</p>
</div>

<!-- TV2 (Duy): 3 khung dữ liệu. Trận đấu lấy từ tran_dau (TV4), top cầu thủ từ view v_chi_so_trung_binh (TV5) -->
<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-calendar-event"></i> Trận sắp diễn ra</div>
            <ul class="list-group list-group-flush">
                <?php if (!$tranSapToi): ?>
                    <li class="list-group-item text-muted">Chưa có dữ liệu lịch thi đấu.</li>
                <?php endif; ?>
                <?php foreach ($tranSapToi as $td): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span>
                                <a href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $td['ma_doi_khach']) ?>" title="<?= e($td['ten_doi_khach']) ?>"><?= e($td['vt_khach']) ?></a>
                                <span class="text-muted">@</span>
                                <a href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $td['ma_doi_nha']) ?>" title="<?= e($td['ten_doi_nha']) ?>"><?= e($td['vt_nha']) ?></a>
                            </span>
                            <span class="small text-muted"><?= e($fmtNgay($td['thoi_gian'])) ?></span>
                        </div>
                        <div class="small text-muted">
                            <?= e($td['ten_san'] ?? '') ?>
                            <?php if ($td['trang_thai'] === 'hoan'): ?><span class="badge text-bg-warning">Hoãn</span><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-check2-square"></i> Kết quả mới nhất</div>
            <ul class="list-group list-group-flush">
                <?php if (!$ketQua): ?>
                    <li class="list-group-item text-muted">Chưa có dữ liệu kết quả.</li>
                <?php endif; ?>
                <?php foreach ($ketQua as $td): $khachThang = $td['diem_doi_khach'] > $td['diem_doi_nha']; ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <a class="<?= $khachThang ? 'fw-bold' : 'text-muted' ?>" href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $td['ma_doi_khach']) ?>"
                               title="<?= e($td['ten_doi_khach']) ?>"><?= e($td['vt_khach']) ?></a>
                            <span class="<?= $khachThang ? 'fw-bold' : 'text-muted' ?>"><?= (int) $td['diem_doi_khach'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a class="<?= !$khachThang ? 'fw-bold' : 'text-muted' ?>" href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $td['ma_doi_nha']) ?>"
                               title="<?= e($td['ten_doi_nha']) ?>"><?= e($td['vt_nha']) ?></a>
                            <span class="<?= !$khachThang ? 'fw-bold' : 'text-muted' ?>"><?= (int) $td['diem_doi_nha'] ?></span>
                        </div>
                        <div class="small text-muted"><?= e($fmtNgay($td['thoi_gian'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-star"></i> Top cầu thủ<?= $mua ? ' ' . e($mua['ten_mua_giai']) : '' ?>
                <span class="small text-muted fw-normal">(điểm/trận)</span></div>
            <ol class="list-group list-group-flush list-group-numbered">
                <?php if (!$topCauThu): ?>
                    <li class="list-group-item text-muted" style="list-style: none">Chưa có dữ liệu thống kê.</li>
                <?php endif; ?>
                <?php foreach ($topCauThu as $ct): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <a class="fw-semibold" href="<?= url('/cau_thu/ho_so.php?id=' . (int) $ct['ma_cau_thu']) ?>"><?= e($ct['ho_ten']) ?></a>
                            <div class="small text-muted">
                                <?= e($ct['ten_viet_tat'] ?? 'Tự do') ?> · <?= (int) $ct['so_tran'] ?> trận ·
                                <?= e($ct['bat_bong_tb']) ?> RPG · <?= e($ct['kien_tao_tb']) ?> APG
                            </div>
                        </div>
                        <span class="badge text-bg-primary fs-6"><?= e($ct['diem_tb']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
