<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/ham_trang_chu.php';   // TV2: trận sắp tới, kết quả mới nhất
require_once __DIR__ . '/includes/giao_dien_moi.php';   // giao diện mới: màu đội, huy hiệu, dẫn đầu chỉ số, BXH gọn

$mua        = tv2LayMuaHienTai();
$maMua      = $mua ? (int) $mua['ma_mua_giai'] : null;
$tranSapToi = tcTranSapToi($maMua, 4);
$ketQua     = tcKetQuaMoiNhat($maMua, 6);
$topDiem    = gdDanDau($maMua, 'diem_tb', 5);
$noiBat     = $topDiem[0] ?? null;
$bxhGon     = gdBxhGon($maMua, 5);
$fmtNgay    = fn($s) => date('d/m H:i', strtotime($s));

// 4 thẻ "Dẫn đầu chỉ số": [cột trong view, nhãn]
$dsChiSo = [
    ['bat_bong_tb',  'bắt bóng/trận'],
    ['kien_tao_tb',  'kiến tạo/trận'],
    ['cuop_bong_tb', 'cướp bóng/trận'],
    ['chan_bong_tb', 'chặn bóng/trận'],
];
$danDau = [];
foreach ($dsChiSo as [$cot, $nhan]) {
    $r = gdDanDau($maMua, $cot, 1);
    if ($r) {
        $danDau[] = $r[0] + ['nhan' => $nhan];
    }
}

/* ---- Dải tỉ số dưới thanh trên cùng: kết quả mới nhất (cũ -> mới) rồi trận sắp tới ---- */
$daiTiSo = '';
if ($ketQua || $tranSapToi) {
    ob_start();
    ?>
    <section class="dai-ti-so" aria-label="Tỉ số các trận gần đây">
        <div class="container">
            <?php foreach (array_reverse($ketQua) as $td):
                $khachThang = (int) $td['diem_doi_khach'] > (int) $td['diem_doi_nha']; ?>
                <a class="the-tran" href="<?= url('/thong_ke/box_score.php?id=' . (int) $td['ma_tran']) ?>">
                    <span class="trang-thai">Kết thúc · <?= e(date('d/m', strtotime($td['thoi_gian']))) ?></span>
                    <span class="dong-doi<?= $khachThang ? ' thang' : '' ?>">
                        <?= gdHuyHieu($td['logo_khach'], $td['vt_khach'], $td['ten_doi_khach'], 26) ?>
                        <span class="ten"><?= e($td['vt_khach']) ?></span>
                        <span class="diem"><?= (int) $td['diem_doi_khach'] ?></span>
                    </span>
                    <span class="dong-doi<?= !$khachThang ? ' thang' : '' ?>">
                        <?= gdHuyHieu($td['logo_nha'], $td['vt_nha'], $td['ten_doi_nha'], 26) ?>
                        <span class="ten"><?= e($td['vt_nha']) ?></span>
                        <span class="diem"><?= (int) $td['diem_doi_nha'] ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
            <?php foreach ($tranSapToi as $td): ?>
                <a class="the-tran" href="<?= url('/lich_thi_dau/index.php') ?>">
                    <span class="trang-thai sap"><?= $td['trang_thai'] === 'hoan' ? 'Hoãn' : 'Sắp đấu · ' . e($fmtNgay($td['thoi_gian'])) ?></span>
                    <span class="dong-doi">
                        <?= gdHuyHieu($td['logo_khach'], $td['vt_khach'], $td['ten_doi_khach'], 26) ?>
                        <span class="ten"><?= e($td['ten_doi_khach']) ?></span>
                    </span>
                    <span class="dong-doi">
                        <?= gdHuyHieu($td['logo_nha'], $td['vt_nha'], $td['ten_doi_nha'], 26) ?>
                        <span class="ten"><?= e($td['ten_doi_nha']) ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    $daiTiSo = ob_get_clean();
}

$tieuDe = 'Trang chủ';
$menu   = 'trang_chu';
require __DIR__ . '/includes/header.php';
?>

<!-- Khung nổi bật: cầu thủ ghi điểm nhiều nhất + top 5 -->
<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <?php if ($noiBat): ?>
            <article class="noi-bat">
                <div class="noi-bat-anh" style="background: <?= e(gdMauDoi($noiBat['ten_viet_tat'])) ?>">
                    <?php if ($noiBat['so_ao'] !== null): ?><span class="so-ao-mo" aria-hidden="true"><?= (int) $noiBat['so_ao'] ?></span><?php endif; ?>
                    <?= gdAnhCauThu($noiBat['anh'], $noiBat['ho_ten']) ?>
                </div>
                <div class="noi-bat-noi-dung">
                    <span class="noi-bat-nhan">Vua ghi điểm mùa <?= e($mua['ten_mua_giai']) ?></span>
                    <h1 class="noi-bat-ten"><?= e($noiBat['ho_ten']) ?></h1>
                    <span class="noi-bat-phu">
                        <?= e($noiBat['ten_doi'] ?? 'Tự do') ?> · <?= e($noiBat['vi_tri']) ?><?= $noiBat['so_ao'] !== null ? ' · #' . (int) $noiBat['so_ao'] : '' ?>
                    </span>
                    <div class="noi-bat-so">
                        <div><div class="so"><?= e($noiBat['diem_tb']) ?></div><div class="nhan">Điểm/trận</div></div>
                        <div><div class="so"><?= e($noiBat['kien_tao_tb']) ?></div><div class="nhan">Kiến tạo</div></div>
                        <div><div class="so"><?= e($noiBat['bat_bong_tb']) ?></div><div class="nhan">Bắt bóng</div></div>
                    </div>
                    <a class="btn btn-nba" href="<?= url('/cau_thu/ho_so.php?id=' . (int) $noiBat['ma_cau_thu']) ?>">Xem hồ sơ cầu thủ</a>
                </div>
            </article>
        <?php else: ?>
            <div class="noi-bat p-5 flex-column justify-content-center">
                <h1 class="noi-bat-ten">Giải Bóng rổ Nhà nghề Mỹ</h1>
                <p class="noi-bat-phu mb-0 mt-3">Thông tin đội bóng, cầu thủ, lịch thi đấu, kết quả và bảng xếp hạng NBA.
                    Khi có box score của các trận, cầu thủ nổi bật sẽ hiện ở đây.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-baseline">
                Top ghi điểm <small class="text-muted fw-normal text-lowercase" style="font-family: var(--bs-body-font-family); font-size: .8rem">điểm/trận</small>
            </div>
            <div class="card-body py-1">
                <?php if (!$topDiem): ?>
                    <p class="text-muted my-3">Chưa có dữ liệu thống kê.</p>
                <?php else: ?>
                    <ol class="danh-sach-top">
                        <?php foreach ($topDiem as $i => $ct): ?>
                            <li>
                                <a href="<?= url('/cau_thu/ho_so.php?id=' . (int) $ct['ma_cau_thu']) ?>">
                                    <span class="hang"><?= $i + 1 ?></span>
                                    <span class="anh-tron" style="background: <?= e(gdMauDoi($ct['ten_viet_tat'])) ?>"><?= gdAnhCauThu($ct['anh'], $ct['ho_ten'], '') ?></span>
                                    <span class="ten"><strong><?= e($ct['ho_ten']) ?></strong><small><?= e($ct['ten_viet_tat'] ?? 'Tự do') ?> · <?= e($ct['vi_tri']) ?> · <?= (int) $ct['so_tran'] ?> trận</small></span>
                                    <span class="so"><?= e($ct['diem_tb']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-0 pt-0"><a href="<?= url('/thong_ke/index.php') ?>" class="fw-semibold text-decoration-none">Xem thống kê đầy đủ →</a></div>
        </div>
    </div>
</div>

<!-- Bảng xếp hạng thu gọn -->
<section class="mb-5">
    <div class="tieu-de-khu">
        <h2>Bảng xếp hạng<?= $mua ? ' ' . e($mua['ten_mua_giai']) : '' ?></h2>
        <a href="<?= url('/bang_xep_hang/index.php') ?>">Xem đầy đủ →</a>
    </div>
    <?php if (!$bxhGon): ?>
        <div class="card card-body text-muted">Mùa hiện tại chưa phân bảng nên chưa có bảng xếp hạng.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($bxhGon as $tenHoiNghi => $dsDoi): ?>
                <div class="col-md-6">
                    <div class="card bxh-gon overflow-hidden">
                        <div class="card-header text-white" style="background: var(--nba-toi); border-bottom: 0"><?= e($tenHoiNghi) ?></div>
                        <div class="dong tieu-de-cot"><span>#</span><span>Đội</span><span class="so">T-B</span><span class="so">%</span></div>
                        <?php foreach ($dsDoi as $i => $d): ?>
                            <a class="dong" href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $d['ma_doi']) ?>">
                                <span class="hang"><?= $i + 1 ?></span>
                                <span class="doi"><?= gdHuyHieu($d['logo'], $d['ten_viet_tat'], $d['ten_doi'], 28) ?><span><?= e($d['ten_doi']) ?></span></span>
                                <span class="so"><?= (int) $d['thang'] ?>-<?= (int) $d['thua'] ?></span>
                                <span class="so fw-bold"><?= $d['ti_le_thang'] !== null ? e(ltrim(number_format((float) $d['ti_le_thang'], 3), '0')) : '—' ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Dẫn đầu các chỉ số khác -->
<?php if ($danDau): ?>
<section class="mb-4">
    <div class="tieu-de-khu">
        <h2>Dẫn đầu chỉ số</h2>
        <a href="<?= url('/thong_ke/index.php') ?>">Top cầu thủ →</a>
    </div>
    <div class="row g-3">
        <?php foreach ($danDau as $ct): ?>
            <div class="col-6 col-lg-3">
                <a class="the-cau-thu" href="<?= url('/cau_thu/ho_so.php?id=' . (int) $ct['ma_cau_thu']) ?>">
                    <span class="khung-anh" style="background: <?= e(gdMauDoi($ct['ten_viet_tat'])) ?>">
                        <?php if ($ct['so_ao'] !== null): ?><span class="so-ao-mo" aria-hidden="true"><?= (int) $ct['so_ao'] ?></span><?php endif; ?>
                        <?= gdAnhCauThu($ct['anh'], $ct['ho_ten']) ?>
                    </span>
                    <span class="noi-dung">
                        <span class="phu"><?= e($ct['ten_viet_tat'] ?? 'Tự do') ?> · <?= e($ct['vi_tri']) ?></span>
                        <span class="ten"><?= e($ct['ho_ten']) ?></span>
                        <span class="chi-so"><span class="so"><?= e($ct['gia_tri']) ?></span><small><?= e($ct['nhan']) ?></small></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
