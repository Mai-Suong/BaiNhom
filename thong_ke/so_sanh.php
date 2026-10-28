<?php
/**
 * Phong độ 10 trận gần nhất và so sánh 2 cầu thủ (TV5, biểu đồ Chart.js).
 *   /thong_ke/so_sanh.php                      -> chọn hai cầu thủ
 *   /thong_ke/so_sanh.php?a=3&b=7&mua=1        -> so sánh cầu thủ có ma_cau_thu 3 và 7
 *   /thong_ke/so_sanh.php?a=3&b=7&cs=kien_tao  -> phong độ theo kiến tạo (diem | bat_bong | kien_tao)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_thong_ke.php';

[$dsMua, $maMua] = tkLayMua($pdo);
$a = (int) ($_GET['a'] ?? 0);
$b = (int) ($_GET['b'] ?? 0);

$chiSo = [   // khóa => [nhãn, biểu thức SQL cho từng trận] (biểu thức cố định, không lấy từ người dùng)
    'diem'     => ['Điểm', 'cs.diem'],
    'bat_bong' => ['Bắt bóng', '(cs.bat_bong_tan_cong + cs.bat_bong_phong_thu)'],
    'kien_tao' => ['Kiến tạo', 'cs.kien_tao'],
];
$cs = isset($chiSo[$_GET['cs'] ?? '']) ? $_GET['cs'] : 'diem';

// Danh sách cầu thủ đã có số liệu trong mùa
$dsCauThu = tkTruyVan($pdo,
    "SELECT DISTINCT c.ma_cau_thu, c.ho_ten, d.ten_viet_tat
     FROM v_chi_so_trung_binh v
     JOIN cau_thu c ON c.ma_cau_thu = v.ma_cau_thu
     LEFT JOIN doi_bong d ON d.ma_doi = c.ma_doi
     WHERE v.ma_mua_giai = ? AND v.giai_doan = 'thuong'
     ORDER BY c.ho_ten", [$maMua]);

function tkTrungBinhCauThu(PDO $pdo, int $maMua, int $maCauThu): ?array
{
    $r = tkTruyVan($pdo,
        "SELECT v.*, c.ho_ten, d.ten_viet_tat
         FROM v_chi_so_trung_binh v
         JOIN cau_thu c ON c.ma_cau_thu = v.ma_cau_thu
         LEFT JOIN doi_bong d ON d.ma_doi = c.ma_doi
         WHERE v.ma_mua_giai = ? AND v.giai_doan = 'thuong' AND v.ma_cau_thu = ?", [$maMua, $maCauThu]);
    return $r ? $r[0] : null;
}

/** 10 trận gần nhất (cũ -> mới) của một cầu thủ */
function tkPhongDo(PDO $pdo, int $maMua, int $maCauThu, string $bieuThuc): array
{
    $r = tkTruyVan($pdo,
        "SELECT td.thoi_gian, $bieuThuc AS gia_tri
         FROM chi_so_tran_dau cs JOIN tran_dau td ON td.ma_tran = cs.ma_tran
         WHERE cs.ma_cau_thu = ? AND td.ma_mua_giai = ? AND td.trang_thai = 'da_dau' AND cs.trang_thai = 'thi_dau'
         ORDER BY td.thoi_gian DESC LIMIT 10", [$maCauThu, $maMua]);
    return array_reverse(array_map(function ($x) { return (int) $x['gia_tri']; }, $r));
}

$A = $a ? tkTrungBinhCauThu($pdo, $maMua, $a) : null;
$B = $b ? tkTrungBinhCauThu($pdo, $maMua, $b) : null;
$coCaHai = $A && $B && $a !== $b;

$duLieu = null;
if ($coCaHai) {
    $max = tkTruyVan($pdo,
        "SELECT MAX(diem_tb) diem, MAX(bat_bong_tb) bat_bong, MAX(kien_tao_tb) kien_tao,
                MAX(cuop_bong_tb) cuop_bong, MAX(chan_bong_tb) chan_bong, MAX(ti_le_nem) ti_le_nem
         FROM v_chi_so_trung_binh WHERE ma_mua_giai = ? AND giai_doan = 'thuong'", [$maMua])[0];

    // [nhãn, cột trong view, mức cao nhất toàn giải]
    $truc = [
        ['Điểm',      'diem_tb',      (float) $max['diem']],
        ['Bắt bóng',  'bat_bong_tb',  (float) $max['bat_bong']],
        ['Kiến tạo',  'kien_tao_tb',  (float) $max['kien_tao']],
        ['Cướp bóng', 'cuop_bong_tb', (float) $max['cuop_bong']],
        ['Chặn bóng', 'chan_bong_tb', (float) $max['chan_bong']],
        ['% ném',     'ti_le_nem',    (float) $max['ti_le_nem']],
    ];
    $chuan = function (array $row) use ($truc): array {
        $kq = [];
        foreach ($truc as [$nhan, $cot, $mx]) {
            $kq[] = $mx > 0 ? round(100 * (float) $row[$cot] / $mx, 1) : 0;
        }
        return $kq;
    };
    $thuc = function (array $row) use ($truc): array {
        $kq = [];
        foreach ($truc as [$nhan, $cot, $mx]) {
            $kq[] = $row[$cot] === null ? 0 : (float) $row[$cot];
        }
        return $kq;
    };
    $duLieu = [
        'tenA' => $A['ho_ten'], 'tenB' => $B['ho_ten'],
        'nhanChiSo' => $chiSo[$cs][0],
        'phongDoA' => tkPhongDo($pdo, $maMua, $a, $chiSo[$cs][1]),
        'phongDoB' => tkPhongDo($pdo, $maMua, $b, $chiSo[$cs][1]),
        'nhanTruc' => array_column($truc, 0),
        'radarA' => $chuan($A), 'radarB' => $chuan($B),
        'thucA' => $thuc($A), 'thucB' => $thuc($B),
    ];
}

// Các dòng thanh so sánh: [nhãn, cột, hậu tố, mức tràn thanh]
$dongSoSanh = [
    ['Điểm', 'diem_tb', '', 30], ['Bắt bóng', 'bat_bong_tb', '', 13], ['Kiến tạo', 'kien_tao_tb', '', 10],
    ['Cướp bóng', 'cuop_bong_tb', '', 2.5], ['Chặn bóng', 'chan_bong_tb', '', 2.5], ['Mất bóng', 'mat_bong_tb', '', 5],
    ['% ném', 'ti_le_nem', '%', 100], ['% 3 điểm', 'ti_le_3_diem', '%', 100], ['% phạt', 'ti_le_phat', '%', 100],
];

function tkChonCauThu(string $ten, string $nhan, array $ds, int $dang): string
{
    $h = '<label for="' . $ten . '" class="form-label fw-semibold ' . ($ten === 'a' ? 'mau-a' : 'mau-b') . '">' . e($nhan) . '</label>';
    $h .= '<select id="' . $ten . '" name="' . $ten . '" class="form-select"><option value="0">— Chọn cầu thủ —</option>';
    foreach ($ds as $c) {
        $h .= '<option value="' . (int) $c['ma_cau_thu'] . '"' . ((int) $c['ma_cau_thu'] === $dang ? ' selected' : '') . '>'
            . e($c['ho_ten'] . ($c['ten_viet_tat'] ? ' · ' . $c['ten_viet_tat'] : '')) . '</option>';
    }
    return $h . '</select>';
}

$tieuDe = 'Phong độ và so sánh cầu thủ';
$menu   = 'thong_ke';
require __DIR__ . '/../includes/header.php';
echo tkCss();
?>

<h1 class="h3 mb-1">Thống kê</h1>
<p class="text-muted">Phong độ và so sánh cầu thủ · chỉ số mùa thường, phong độ tính trên 10 trận gần nhất</p>
<?= tkMenu('so_sanh') ?>

<form method="get" class="card shadow-sm card-body mb-4">
    <input type="hidden" name="cs" value="<?= e($cs) ?>">
    <div class="row g-3 align-items-end">
        <div class="col-md-4"><?= tkChonCauThu('a', 'Cầu thủ A', $dsCauThu, $a) ?></div>
        <div class="col-md-4"><?= tkChonCauThu('b', 'Cầu thủ B', $dsCauThu, $b) ?></div>
        <div class="col-md-2">
            <label for="mua" class="form-label fw-semibold">Mùa giải</label>
            <select id="mua" name="mua" class="form-select">
                <?php foreach ($dsMua as $m): ?>
                    <option value="<?= (int) $m['ma_mua_giai'] ?>"<?= (int) $m['ma_mua_giai'] === $maMua ? ' selected' : '' ?>><?= e($m['ten_mua_giai']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-nba" type="submit">So sánh</button></div>
    </div>
</form>

<?php if (!$dsCauThu): ?>
    <div class="alert alert-info">Chưa có số liệu cầu thủ trong mùa này. Cần có box score của các trận đã đấu.</div>
<?php elseif (!$coCaHai): ?>
    <div class="alert alert-info">Hãy chọn hai cầu thủ khác nhau để so sánh.<?= ($a && !$A) || ($b && !$B) ? ' (Cầu thủ đã chọn chưa có số liệu trong mùa này.)' : '' ?></div>
<?php else: ?>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="h5 mb-0">Phong độ 10 trận gần nhất</h2>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Chỉ số phong độ">
                        <?php foreach ($chiSo as $khoa => [$nhan]): ?>
                            <a class="btn btn-outline-secondary<?= $khoa === $cs ? ' active' : '' ?>" href="<?= e(tkUrlVoi(['cs' => $khoa])) ?>"><?= e($nhan) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if (!$duLieu['phongDoA'] && !$duLieu['phongDoB']): ?>
                    <p class="text-muted mt-3 mb-0">Chưa có trận nào của hai cầu thủ này để vẽ biểu đồ.</p>
                <?php else: ?>
                    <div class="khung-bieu-do"><canvas id="bieuDoPhongDo" role="img" aria-label="Biểu đồ đường phong độ 10 trận gần nhất của hai cầu thủ"></canvas></div>
                <?php endif; ?>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h5 mb-1">So sánh 6 chỉ số</h2>
                <p class="small text-muted mb-0">Mỗi trục tính theo phần trăm của mức cao nhất toàn giải</p>
                <div class="khung-bieu-do cao"><canvas id="bieuDoRadar" role="img" aria-label="Biểu đồ radar so sánh 6 chỉ số của hai cầu thủ"></canvas></div>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm mb-3"><div class="card-body">
        <div class="so-sanh-hang" style="border-top:0">
            <strong class="mau-a text-end fs-5"><?= e($A['ho_ten']) ?></strong>
            <span class="nhan-giua small text-uppercase fw-semibold">Chỉ số trung bình</span>
            <strong class="mau-b fs-5"><?= e($B['ho_ten']) ?></strong>
        </div>
        <?php foreach ($dongSoSanh as [$nhan, $cot, $hauTo, $mucTran]):
            $va  = $A[$cot] === null ? 0 : (float) $A[$cot];
            $vb  = $B[$cot] === null ? 0 : (float) $B[$cot];
            $tot = ($cot === 'mat_bong_tb') ? [$va <= $vb, $vb <= $va] : [$va >= $vb, $vb >= $va];   // mất bóng: thấp hơn là tốt hơn
        ?>
            <div class="so-sanh-hang">
                <div class="ben trai">
                    <span class="fs-6" style="font-weight:<?= $tot[0] ? 700 : 400 ?>"><?= tkSo($va) . $hauTo ?></span>
                    <div class="thanh-bar"><i style="width:<?= min(100, round(100 * $va / $mucTran)) ?>%; background:#c2410c"></i></div>
                </div>
                <div class="nhan-giua"><?= e($nhan) ?></div>
                <div class="ben">
                    <div class="thanh-bar"><i style="width:<?= min(100, round(100 * $vb / $mucTran)) ?>%; background:#2b5fa8"></i></div>
                    <span class="fs-6" style="font-weight:<?= $tot[1] ? 700 : 400 ?>"><?= tkSo($vb) . $hauTo ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        <p class="small text-muted mt-3 mb-0">Chỉ số đậm là bên tốt hơn (với mất bóng, thấp hơn là tốt hơn).</p>
    </div></div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        var d = <?= json_encode($duLieu, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        if (typeof Chart === 'undefined') { return; }   // không tải được Chart.js (mất mạng)
        var CAM = '#c2410c', XANH = '#2b5fa8';
        Chart.defaults.font.size = 13;

        var elPD = document.getElementById('bieuDoPhongDo');
        if (elPD) {
            var n = Math.max(d.phongDoA.length, d.phongDoB.length), nhan = [];
            for (var i = 1; i <= n; i++) { nhan.push('Trận ' + i); }
            new Chart(elPD, {
                type: 'line',
                data: { labels: nhan, datasets: [
                    { label: d.tenA, data: d.phongDoA, borderColor: CAM, backgroundColor: CAM, pointStyle: 'circle', pointRadius: 5, tension: 0.2 },
                    { label: d.tenB, data: d.phongDoB, borderColor: XANH, backgroundColor: XANH, pointStyle: 'rect', pointRadius: 5, borderDash: [8, 5], tension: 0.2 }
                ] },
                options: { responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
                    scales: { y: { beginAtZero: true, title: { display: true, text: d.nhanChiSo + ' mỗi trận' } },
                              x: { title: { display: true, text: '10 trận gần nhất (cũ đến mới)' } } } }
            });
        }

        new Chart(document.getElementById('bieuDoRadar'), {
            type: 'radar',
            data: { labels: d.nhanTruc, datasets: [
                { label: d.tenA, data: d.radarA, thuc: d.thucA, borderColor: CAM, backgroundColor: 'rgba(194,65,12,0.18)', borderWidth: 3 },
                { label: d.tenB, data: d.radarB, thuc: d.thucB, borderColor: XANH, backgroundColor: 'rgba(43,95,168,0.18)', borderWidth: 3, borderDash: [8, 5] }
            ] },
            options: { responsive: true, maintainAspectRatio: false,
                scales: { r: { min: 0, max: 100, ticks: { display: false } } },
                plugins: { legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + c.dataset.thuc[c.dataIndex]; } } } } }
        });
    })();
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
