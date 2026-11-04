<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/ham_trang_chu.php';   // TV2: trận đấu, carousel nổi bật, điểm nhấn
require_once __DIR__ . '/includes/giao_dien_moi.php';   // giao diện mới: màu đội, huy hiệu, ảnh, BXH gọn

$mua        = tv2LayMuaHienTai();
$maMua      = $mua ? (int) $mua['ma_mua_giai'] : null;
$tranSapToi = tcTranSapToi($maMua, 4);
$ketQua     = tcKetQuaMoiNhat($maMua, 6);
$soTranMin  = tcSoTranToiThieu($maMua);
$topDiem    = tcDanDau($maMua, 'diem_tb', 5);   // chỉ cầu thủ đá đủ số trận tối thiểu
$bxhGon     = gdBxhGon($maMua, 5);
$fmtNgay    = fn($s) => date('d/m H:i', strtotime($s));
$ngayDu     = fn($s) => date('d/m/Y', strtotime($s));

// 4 thẻ "Dẫn đầu chỉ số": [cột trong view, nhãn]
$dsChiSo = [
    ['bat_bong_tb',  'bắt bóng/trận'],
    ['kien_tao_tb',  'kiến tạo/trận'],
    ['cuop_bong_tb', 'cướp bóng/trận'],
    ['chan_bong_tb', 'chặn bóng/trận'],
];
$danDau = [];
foreach ($dsChiSo as [$cot, $nhan]) {
    $r = tcDanDau($maMua, $cot, 1);
    if ($r) {
        $danDau[] = $r[0] + ['nhan' => $nhan];
    }
}

/* ---- Các slide của carousel nổi bật (slide nào không có dữ liệu thì bỏ) ---- */
$dsSlide = [];
if ($topDiem) {
    $dsSlide[] = ['loai' => 'vua_ghi_diem', 'ct' => $topDiem[0]];
}
if ($top10 = tcTopHieuSuat($maMua, 10)) {
    $dsSlide[] = ['loai' => 'top_10', 'ds' => $top10];
}
if ($manTrinhDien = tcManTrinhDien($maMua, 5)) {
    $dsSlide[] = ['loai' => 'man_trinh_dien', 'ct' => $manTrinhDien[0]];
}
if ($khoanhKhac = tcKhoanhKhacKichTinh($maMua)) {
    $dsSlide[] = ['loai' => 'kich_tinh', 'td' => $khoanhKhac];
}
if ($sucManh = tcSucManh($maMua, 5)) {
    $dsSlide[] = ['loai' => 'suc_manh', 'ds' => $sucManh];
}
if ($tinTuc = tcTinTuc(5)) {
    $dsSlide[] = ['loai' => 'tin_tuc', 'ds' => $tinTuc];
}
if ($doiDauBang = tcDoiDauBang($maMua)) {
    $dsSlide[] = ['loai' => 'dau_bang', 'doi' => $doiDauBang];
}
if ($vinhDanh = tcVinhDanhMuaTruoc($mua)) {
    $dsSlide[] = ['loai' => 'vinh_danh', 'vd' => $vinhDanh];
}
if ($tamDiem = tcTranTamDiem($maMua)) {
    $dsSlide[] = ['loai' => 'tam_diem', 'td' => $tamDiem];
}

/* ---- Mục Điểm nhấn: băng chuyền trượt ngang, xen kẽ thẻ trận đấu và thẻ màn trình diễn ---- */
$theTran = [];   // ['tran', nhãn loại, mô tả, trận]
foreach (tcTranDiemNhan($maMua, 'kich_tinh', 3) as $td) {
    $nhan = $td['so_hiep'] > 4 ? 'Hiệp phụ' . ($td['so_hiep'] > 5 ? ' ' . ($td['so_hiep'] - 4) : '') : 'Cách ' . (int) $td['cach_biet'] . ' điểm';
    $theTran[] = ['tran', 'Trận kịch tính', $nhan, $td];
}
foreach (tcTranDiemNhan($maMua, 'thang_dam', 2) as $td) {
    $theTran[] = ['tran', 'Thắng đậm', 'Cách ' . (int) $td['cach_biet'] . ' điểm', $td];
}
$theMtd = array_map(fn($ct) => ['cau_thu', 'Màn trình diễn xuất sắc', '', $ct], $manTrinhDien);
$dsTheDiemNhan = [];
for ($i = 0; $i < max(count($theTran), count($theMtd)); $i++) {
    foreach ([$theTran[$i] ?? null, $theMtd[$i] ?? null] as $the) {
        if ($the) {
            $dsTheDiemNhan[] = $the;
        }
    }
}
$loaiTin = ['chuyen_nhuong' => ['bi-arrow-left-right', 'Chuyển nhượng'], 'hlv' => ['bi-clipboard-check', 'Ban huấn luyện'],
            'chan_thuong' => ['bi-bandaid', 'Chấn thương']];

/** Link lịch thi đấu đã lọc sẵn mùa + đội (trang của TV4) */
$linkLich = fn(int $maDoi) => url('/lich_thi_dau/index.php') . '?' . http_build_query(['mua' => $maMua, 'doi' => $maDoi]);

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
                <a class="the-tran" href="<?= e($linkLich((int) $td['ma_doi_nha'])) ?>">
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

<?php // CSS riêng của trang chủ (TV2), không sửa style.css chung ?>
<link href="<?= url('/assets/css/trang_chu.css') ?>?v=<?= (int) @filemtime(__DIR__ . '/assets/css/trang_chu.css') ?>" rel="stylesheet">

<!-- Carousel nổi bật (kiểu nba.com) + top ghi điểm -->
<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <?php if (!$dsSlide): ?>
            <div class="noi-bat p-5 flex-column justify-content-center">
                <h1 class="noi-bat-ten">Giải Bóng rổ Nhà nghề Mỹ</h1>
                <p class="noi-bat-phu mb-0 mt-3">Thông tin đội bóng, cầu thủ, lịch thi đấu, kết quả và bảng xếp hạng NBA.
                    Khi có trận đấu và box score của mùa hiện tại, các tin nổi bật sẽ hiện ở đây.</p>
            </div>
        <?php else: ?>
        <div id="tcCarousel" class="carousel slide tc-carousel" data-bs-ride="carousel" data-bs-interval="6000" aria-label="Tin nổi bật">
            <?php if (count($dsSlide) > 1): ?>
                <div class="carousel-indicators">
                    <?php foreach ($dsSlide as $i => $sl): ?>
                        <button type="button" data-bs-target="#tcCarousel" data-bs-slide-to="<?= $i ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?>
                                aria-label="Tin <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="carousel-inner">
            <?php foreach ($dsSlide as $i => $sl): ?>
                <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                <?php if ($sl['loai'] === 'vua_ghi_diem'): $ct = $sl['ct']; ?>
                    <article class="noi-bat">
                        <div class="noi-bat-anh" style="background: <?= e(gdMauDoi($ct['ten_viet_tat'])) ?>">
                            <?php if ($ct['so_ao'] !== null): ?><span class="so-ao-mo" aria-hidden="true"><?= (int) $ct['so_ao'] ?></span><?php endif; ?>
                            <?= gdAnhCauThu($ct['anh'], $ct['ho_ten']) ?>
                        </div>
                        <div class="noi-bat-noi-dung">
                            <span class="noi-bat-nhan">Vua ghi điểm mùa <?= e($mua['ten_mua_giai']) ?></span>
                            <h2 class="noi-bat-ten"><?= e($ct['ho_ten']) ?></h2>
                            <span class="noi-bat-phu"><?= e($ct['ten_doi'] ?? 'Tự do') ?> · <?= e($ct['vi_tri']) ?><?= $ct['so_ao'] !== null ? ' · #' . (int) $ct['so_ao'] : '' ?> · <?= (int) $ct['so_tran'] ?> trận</span>
                            <div class="noi-bat-so">
                                <div><div class="so"><?= e($ct['diem_tb']) ?></div><div class="nhan">Điểm/trận</div></div>
                                <div><div class="so"><?= e($ct['kien_tao_tb']) ?></div><div class="nhan">Kiến tạo</div></div>
                                <div><div class="so"><?= e($ct['bat_bong_tb']) ?></div><div class="nhan">Bắt bóng</div></div>
                            </div>
                            <a class="btn btn-nba" href="<?= url('/cau_thu/ho_so.php?id=' . (int) $ct['ma_cau_thu']) ?>">Xem hồ sơ cầu thủ</a>
                        </div>
                    </article>
                <?php elseif ($sl['loai'] === 'top_10'): $ct = $sl['ds'][0]; ?>
                    <article class="noi-bat tc-slide-ds">
                        <div class="noi-bat-anh" style="background: <?= e(gdMauDoi($ct['ten_viet_tat'])) ?>">
                            <span class="so-ao-mo tc-chu-nho" aria-hidden="true">TOP 10</span>
                            <?= gdAnhCauThu($ct['anh'], $ct['ho_ten']) ?>
                        </div>
                        <div class="noi-bat-noi-dung">
                            <span class="noi-bat-nhan">Top 10 cầu thủ xuất sắc · mùa <?= e($mua['ten_mua_giai']) ?></span>
                            <h2 class="noi-bat-ten tc-ten-vua">Hiệu suất (EFF)</h2>
                            <ol class="tc-ds-top10">
                                <?php foreach ($sl['ds'] as $i => $c): ?>
                                    <li><a href="<?= url('/cau_thu/ho_so.php?id=' . (int) $c['ma_cau_thu']) ?>">
                                        <span class="hang"><?= $i + 1 ?></span>
                                        <span class="ten"><?= e($c['ho_ten']) ?> <small><?= e($c['ten_viet_tat'] ?? 'Tự do') ?></small></span>
                                        <span class="so"><?= e($c['eff']) ?></span>
                                    </a></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    </article>
                <?php elseif ($sl['loai'] === 'kich_tinh'): $td = $sl['td']; $ct = $td['nguoi_hung']; ?>
                    <article class="noi-bat">
                        <div class="noi-bat-anh" style="background: <?= e(gdMauDoi($td['vt_thang'])) ?>">
                            <span class="so-ao-mo tc-chu-nho" aria-hidden="true"><?= $td['so_hiep'] > 4 ? 'OT' : '+' . (int) $td['cach_biet'] ?></span>
                            <?php if ($ct): ?>
                                <?= gdAnhCauThu($ct['anh'], $ct['ho_ten']) ?>
                            <?php else: ?>
                                <?= gdHuyHieu(null, $td['vt_thang'], $td['ten_thang'], 150) ?>
                            <?php endif; ?>
                        </div>
                        <div class="noi-bat-noi-dung">
                            <span class="noi-bat-nhan">Khoảnh khắc kịch tính · <?= $td['so_hiep'] > 4 ? 'Hiệp phụ' : 'Cách ' . (int) $td['cach_biet'] . ' điểm' ?></span>
                            <h2 class="noi-bat-ten"><?= e($td['vt_khach']) ?> <?= (int) $td['diem_doi_khach'] ?> – <?= (int) $td['diem_doi_nha'] ?> <?= e($td['vt_nha']) ?></h2>
                            <span class="noi-bat-phu">
                                <?= e($td['ten_doi_khach']) ?> gặp <?= e($td['ten_doi_nha']) ?> · <?= e($ngayDu($td['thoi_gian'])) ?>
                                <?php if ($ct): ?><br>Người hùng: <strong class="text-white"><?= e($ct['ho_ten']) ?></strong> giúp <?= e($td['ten_thang']) ?> chiến thắng<?php endif; ?>
                            </span>
                            <?php if ($ct): ?>
                                <div class="noi-bat-so">
                                    <div><div class="so"><?= (int) $ct['diem'] ?></div><div class="nhan">Điểm</div></div>
                                    <div><div class="so"><?= (int) $ct['bat_bong'] ?></div><div class="nhan">Bắt bóng</div></div>
                                    <div><div class="so"><?= (int) $ct['kien_tao'] ?></div><div class="nhan">Kiến tạo</div></div>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-nba" href="<?= e(tcLinkHighlight($td['ten_doi_khach'], $td['ten_doi_nha'], $td['thoi_gian'])) ?>" target="_blank" rel="noopener">
                                    <i class="bi bi-play-circle-fill"></i> Xem highlight</a>
                                <a class="btn btn-outline-light" href="<?= url('/thong_ke/box_score.php?id=' . (int) $td['ma_tran']) ?>">Box score</a>
                            </div>
                        </div>
                    </article>
                <?php elseif ($sl['loai'] === 'suc_manh'): $d1 = $sl['ds'][0]; ?>
                    <article class="noi-bat tc-slide-ds">
                        <div class="noi-bat-anh tc-anh-logo" style="background: <?= e(gdMauDoi($d1['ten_viet_tat'])) ?>">
                            <span class="so-ao-mo" aria-hidden="true">#1</span>
                            <?= gdHuyHieu($d1['logo'], $d1['ten_viet_tat'], $d1['ten_doi'], 170) ?>
                        </div>
                        <div class="noi-bat-noi-dung">
                            <span class="noi-bat-nhan">Bảng xếp hạng sức mạnh · mùa <?= e($mua['ten_mua_giai']) ?></span>
                            <h2 class="noi-bat-ten tc-ten-vua">Power Rankings</h2>
                            <ol class="tc-ds-suc-manh">
                                <?php foreach ($sl['ds'] as $i => $d): ?>
                                    <li><a href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $d['ma_doi']) ?>">
                                        <span class="hang"><?= $i + 1 ?></span>
                                        <?= gdHuyHieu($d['logo'], $d['ten_viet_tat'], $d['ten_doi'], 30) ?>
                                        <span class="ten"><?= e($d['ten_doi']) ?> <small><?= (int) $d['thang'] ?>-<?= (int) $d['thua'] ?> · <?= $d['hieu_so_tb'] > 0 ? '+' : '' ?><?= e($d['hieu_so_tb']) ?>/trận</small></span>
                                        <span class="phong-do" title="5 trận gần nhất"><?php foreach (str_split($d['phong_do']) as $kq): ?><b class="<?= $kq === 'T' ? 'thang' : 'thua' ?>"><?= $kq ?></b><?php endforeach; ?></span>
                                    </a></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    </article>
                <?php elseif ($sl['loai'] === 'tin_tuc'): $t1 = $sl['ds'][0]; ?>
                    <article class="noi-bat tc-slide-ds">
                        <div class="noi-bat-anh" style="background: <?= e(gdMauDoi($t1['vt'])) ?>">
                            <span class="so-ao-mo tc-chu-nho" aria-hidden="true">TIN</span>
                            <?= gdAnhCauThu($t1['anh'], $t1['ten']) ?>
                        </div>
                        <div class="noi-bat-noi-dung">
                            <span class="noi-bat-nhan">Tin tức mới nhất</span>
                            <h2 class="noi-bat-ten tc-ten-tin"><a href="<?= e($t1['link']) ?>"><?= e($t1['tieu_de']) ?></a></h2>
                            <span class="noi-bat-phu"><i class="bi <?= $loaiTin[$t1['loai']][0] ?>"></i> <?= $loaiTin[$t1['loai']][1] ?> · <?= e($ngayDu($t1['ngay'])) ?></span>
                            <ul class="tc-ds-tin">
                                <?php foreach (array_slice($sl['ds'], 1) as $t): ?>
                                    <li><a href="<?= e($t['link']) ?>">
                                        <i class="bi <?= $loaiTin[$t['loai']][0] ?>" title="<?= $loaiTin[$t['loai']][1] ?>"></i>
                                        <span title="<?= e($t['tieu_de']) ?>"><?= e($t['tieu_de']) ?></span><small><?= e(date('d/m/Y', strtotime($t['ngay']))) ?></small>
                                    </a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </article>
                <?php elseif ($sl['loai'] === 'man_trinh_dien'): $ct = $sl['ct']; ?>
                    <article class="noi-bat">
                        <div class="noi-bat-anh" style="background: <?= e(gdMauDoi($ct['ten_viet_tat'])) ?>">
                            <span class="so-ao-mo" aria-hidden="true"><?= (int) $ct['diem'] ?></span>
                            <?= gdAnhCauThu($ct['anh'], $ct['ho_ten']) ?>
                        </div>
                        <div class="noi-bat-noi-dung">
                            <span class="noi-bat-nhan">Màn trình diễn đỉnh nhất mùa</span>
                            <h2 class="noi-bat-ten"><?= e($ct['ho_ten']) ?></h2>
                            <span class="noi-bat-phu">
                                <?= e($ct['ten_viet_tat']) ?> gặp <?= e($ct['ten_doi_thu']) ?> · <?= e($ngayDu($ct['thoi_gian'])) ?>
                                · <span class="tc-nhan-kq <?= $ct['thang'] ? 'thang' : 'thua' ?>"><?= $ct['thang'] ? 'Thắng' : 'Thua' ?> <?= e($ct['ti_so']) ?></span>
                            </span>
                            <div class="noi-bat-so">
                                <div><div class="so"><?= (int) $ct['diem'] ?></div><div class="nhan">Điểm</div></div>
                                <div><div class="so"><?= (int) $ct['bat_bong'] ?></div><div class="nhan">Bắt bóng</div></div>
                                <div><div class="so"><?= (int) $ct['kien_tao'] ?></div><div class="nhan">Kiến tạo</div></div>
                            </div>
                            <a class="btn btn-nba" href="<?= url('/thong_ke/box_score.php?id=' . (int) $ct['ma_tran']) ?>">Xem box score</a>
                        </div>
                    </article>
                <?php elseif ($sl['loai'] === 'dau_bang'): $d = $sl['doi']; ?>
                    <article class="noi-bat">
                        <div class="noi-bat-anh tc-anh-logo" style="background: <?= e(gdMauDoi($d['ten_viet_tat'])) ?>">
                            <span class="so-ao-mo" aria-hidden="true">#1</span>
                            <?= gdHuyHieu($d['logo'], $d['ten_viet_tat'], $d['ten_doi'], 170) ?>
                        </div>
                        <div class="noi-bat-noi-dung">
                            <span class="noi-bat-nhan">Dẫn đầu giải · mùa <?= e($mua['ten_mua_giai']) ?></span>
                            <h2 class="noi-bat-ten"><?= e($d['ten_doi']) ?></h2>
                            <span class="noi-bat-phu">
                                <?= e($d['ten_hoi_nghi']) ?><?= $d['hlv_truong'] ? ' · HLV ' . e($d['hlv_truong']) : '' ?>
                                <?= $d['ngoi_sao'] ? '· Ngôi sao: ' . e($d['ngoi_sao']['ho_ten']) . ' (' . e($d['ngoi_sao']['diem_tb']) . ' điểm)' : '' ?>
                            </span>
                            <div class="noi-bat-so">
                                <div><div class="so"><?= (int) $d['thang'] ?>-<?= (int) $d['thua'] ?></div><div class="nhan">Thắng - thua</div></div>
                                <div><div class="so"><?= (int) $d['thang_san_nha'] ?>-<?= (int) $d['thua_san_nha'] ?></div><div class="nhan">Sân nhà</div></div>
                                <div><div class="so"><?= (int) $d['thang_san_khach'] ?>-<?= (int) $d['thua_san_khach'] ?></div><div class="nhan">Sân khách</div></div>
                            </div>
                            <a class="btn btn-nba" href="<?= url('/doi_bong/chi_tiet.php?id=' . (int) $d['ma_doi']) ?>">Xem đội bóng</a>
                        </div>
                    </article>
                <?php elseif ($sl['loai'] === 'vinh_danh'): $vd = $sl['vd']; $ct = $vd['mvp']; ?>
                    <article class="noi-bat">
                        <div class="noi-bat-anh" style="background: <?= e(gdMauDoi($ct['ten_viet_tat'])) ?>">
                            <span class="so-ao-mo tc-chu-nho" aria-hidden="true"><?= e($ct['ma_giai']) ?></span>
                            <?= gdAnhCauThu($ct['anh'], $ct['ho_ten']) ?>
                        </div>
                        <div class="noi-bat-noi-dung">
                            <span class="noi-bat-nhan"><?= e(implode(' · ', $ct['cac_giai'])) ?> mùa <?= e($vd['mua']['ten_mua_giai']) ?></span>
                            <h2 class="noi-bat-ten"><?= e($ct['ho_ten']) ?></h2>
                            <span class="noi-bat-phu"><?= e($ct['ten_giai']) ?><?= $ct['ten_doi'] ? ' · ' . e($ct['ten_doi']) : '' ?></span>
                            <ul class="tc-ds-giai">
                                <?php foreach ($vd['khac'] as $g): ?>
                                    <li title="<?= e($g['ten_giai']) ?>"><strong><?= e($g['ma_giai']) ?></strong> <?= e($g['ho_ten']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($ct['ma_cau_thu']): ?>
                                <a class="btn btn-nba" href="<?= url('/cau_thu/ho_so.php?id=' . (int) $ct['ma_cau_thu']) ?>">Xem hồ sơ cầu thủ</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php elseif ($sl['loai'] === 'tam_diem'): $td = $sl['td']; ?>
                    <article class="noi-bat">
                        <div class="noi-bat-anh tc-anh-doi-dau"
                             style="background: linear-gradient(115deg, <?= e(gdMauDoi($td['vt_khach'])) ?> 50%, <?= e(gdMauDoi($td['vt_nha'])) ?> 50%)">
                            <?= gdHuyHieu($td['logo_khach'], $td['vt_khach'], $td['ten_doi_khach'], 110) ?>
                            <span class="tc-vs">VS</span>
                            <?= gdHuyHieu($td['logo_nha'], $td['vt_nha'], $td['ten_doi_nha'], 110) ?>
                        </div>
                        <div class="noi-bat-noi-dung">
                            <span class="noi-bat-nhan">Trận tâm điểm sắp tới</span>
                            <h2 class="noi-bat-ten"><?= e($td['vt_khach']) ?> @ <?= e($td['vt_nha']) ?></h2>
                            <span class="noi-bat-phu">
                                <?= e($td['ten_doi_khach']) ?> gặp <?= e($td['ten_doi_nha']) ?> · <?= e($fmtNgay($td['thoi_gian'])) ?><?= $td['ten_san'] ? ' · ' . e($td['ten_san']) : '' ?>
                            </span>
                            <div class="noi-bat-so">
                                <div><div class="so"><?= (int) $td['thang_khach'] ?>-<?= (int) $td['thua_khach'] ?></div><div class="nhan"><?= e($td['vt_khach']) ?> mùa này</div></div>
                                <div><div class="so"><?= (int) $td['thang_nha'] ?>-<?= (int) $td['thua_nha'] ?></div><div class="nhan"><?= e($td['vt_nha']) ?> mùa này</div></div>
                                <div><div class="so"><?= e(date('H:i', strtotime($td['thoi_gian']))) ?></div><div class="nhan"><?= e(date('d/m', strtotime($td['thoi_gian']))) ?></div></div>
                            </div>
                            <a class="btn btn-nba" href="<?= e($linkLich((int) $td['ma_doi_nha'])) ?>">Xem lịch thi đấu</a>
                        </div>
                    </article>
                <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
            <?php if (count($dsSlide) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#tcCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Tin trước</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#tcCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Tin sau</span>
                </button>
            <?php endif; ?>
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

<!-- Điểm nhấn (Highlights): băng chuyền trượt ngang - trận kịch tính, thắng đậm, màn trình diễn xuất sắc -->
<?php if ($dsTheDiemNhan): ?>
<section class="mb-5 tc-diem-nhan">
    <div class="tieu-de-khu">
        <h2>Điểm nhấn<?= $mua ? ' mùa ' . e($mua['ten_mua_giai']) : '' ?></h2>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('/ket_qua/index.php') ?>" class="me-2">Tất cả kết quả →</a>
            <button type="button" class="tc-nut-truot" data-truot="-1" aria-label="Thẻ trước">&#8249;</button>
            <button type="button" class="tc-nut-truot" data-truot="1" aria-label="Thẻ sau">&#8250;</button>
        </div>
    </div>
    <div class="tc-bang-chuyen" tabindex="0" aria-label="Điểm nhấn mùa giải (trượt ngang)">
        <?php foreach ($dsTheDiemNhan as [$kieu, $loai, $nhan, $x]): ?>
            <?php if ($kieu === 'tran'): $td = $x; $khachThang = (int) $td['diem_doi_khach'] > (int) $td['diem_doi_nha']; ?>
                <div class="tc-the">
                    <span class="tc-loai"><?= e($loai) ?></span>
                    <span class="tc-nhan"><?= e($nhan) ?> · <?= e($ngayDu($td['thoi_gian'])) ?></span>
                    <div class="tc-doi<?= $khachThang ? ' thang' : '' ?>">
                        <?= gdHuyHieu($td['logo_khach'], $td['vt_khach'], $td['ten_doi_khach'], 34) ?>
                        <span class="ten"><?= e($td['ten_doi_khach']) ?></span><span class="diem"><?= (int) $td['diem_doi_khach'] ?></span>
                    </div>
                    <div class="tc-doi<?= !$khachThang ? ' thang' : '' ?>">
                        <?= gdHuyHieu($td['logo_nha'], $td['vt_nha'], $td['ten_doi_nha'], 34) ?>
                        <span class="ten"><?= e($td['ten_doi_nha']) ?></span><span class="diem"><?= (int) $td['diem_doi_nha'] ?></span>
                    </div>
                    <div class="tc-nut">
                        <a class="btn btn-sm btn-nba" href="<?= e(tcLinkHighlight($td['ten_doi_khach'], $td['ten_doi_nha'], $td['thoi_gian'])) ?>"
                           target="_blank" rel="noopener"><i class="bi bi-play-circle-fill"></i> Xem highlight</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/thong_ke/box_score.php?id=' . (int) $td['ma_tran']) ?>">Box score</a>
                    </div>
                </div>
            <?php else: $ct = $x; ?>
                <div class="tc-the tc-the-cau-thu">
                    <div class="tc-the-anh" style="background: <?= e(gdMauDoi($ct['ten_viet_tat'])) ?>">
                        <span class="tc-the-diem"><?= (int) $ct['diem'] ?><small>điểm</small></span>
                        <?= gdAnhCauThu($ct['anh'], $ct['ho_ten']) ?>
                    </div>
                    <span class="tc-loai"><?= e($loai) ?></span>
                    <strong class="tc-the-ten"><?= e($ct['ho_ten']) ?></strong>
                    <span class="tc-nhan"><?= e($ct['ten_viet_tat']) ?> gặp <?= e($ct['vt_doi_thu']) ?> · <?= e($ngayDu($ct['thoi_gian'])) ?> ·
                        <span class="<?= $ct['thang'] ? 'text-success' : 'text-danger' ?> fw-semibold"><?= $ct['thang'] ? 'Thắng' : 'Thua' ?> <?= e($ct['ti_so']) ?></span></span>
                    <span class="tc-nhan"><?= (int) $ct['bat_bong'] ?> bắt bóng · <?= (int) $ct['kien_tao'] ?> kiến tạo</span>
                    <div class="tc-nut">
                        <a class="btn btn-sm btn-nba" href="<?= e(tcLinkHighlightCauThu($ct['ho_ten'], (int) $ct['diem'], $ct['ten_doi_thu'], $ct['thoi_gian'])) ?>"
                           target="_blank" rel="noopener"><i class="bi bi-play-circle-fill"></i> Xem highlight</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/thong_ke/box_score.php?id=' . (int) $ct['ma_tran']) ?>">Box score</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<script src="<?= url('/assets/js/trang_chu.js') ?>?v=<?= (int) @filemtime(__DIR__ . '/assets/js/trang_chu.js') ?>" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
