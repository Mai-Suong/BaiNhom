<?php
require_once __DIR__ . '/../includes/init.php';
yeuCauAdmin(); // BẮT BUỘC ở đầu mọi trang admin
require_once __DIR__ . '/../includes/ham_dashboard.php'; // Dashboard (TV4): SQL nằm trong file này

// Số liệu nhanh toàn hệ thống (giữ nguyên 4 ô của TV1)
$soLieu = [
    ['Đội bóng',      'SELECT COUNT(*) FROM doi_bong',                               'shield',      'primary'],
    ['Cầu thủ',       'SELECT COUNT(*) FROM cau_thu',                                'people',      'success'],
    ['Trận chưa đấu', "SELECT COUNT(*) FROM tran_dau WHERE trang_thai = 'chua_dau'", 'calendar3',   'warning'],
    ['Tài khoản',     'SELECT COUNT(*) FROM tai_khoan',                              'person-lock', 'secondary'],
];

// Dashboard mùa hiện tại (TV4)
$mua = dsbMuaHienTai();
if ($mua) {
    $maMua     = (int) $mua['ma_mua_giai'];
    $soMua     = dsbSoLieuMua($maMua);
    $choKetQua = dsbTranChoKetQua($maMua);
    $ketQuaMoi = dsbKetQuaMoi($maMua);
    $topDoi    = dsbTopDoi($maMua);
    $theoNgay  = dsbTranTheoNgay($maMua);
}

$tieuDe = 'Dashboard';
$menu   = 'dashboard';
require __DIR__ . '/../includes/admin_header.php';
?>

<h1 class="h3 mb-4">Dashboard</h1>

<div class="row g-3">
    <?php foreach ($soLieu as [$ten, $sql, $icon, $mau]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-start border-4 border-<?= $mau ?>">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small"><?= e($ten) ?></div>
                        <div class="fs-3 fw-bold"><?= (int) $pdo->query($sql)->fetchColumn() ?></div>
                    </div>
                    <i class="bi bi-<?= $icon ?> fs-1 text-<?= $mau ?>"></i>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!$mua): ?>
    <div class="alert alert-info mt-4">
        Chưa có mùa giải hiện tại. Vào <a href="<?= url('/admin/mua_giai/index.php') ?>">Mùa giải</a> để thêm hoặc đặt mùa hiện tại.
    </div>
<?php else: ?>
    <h2 class="h5 mt-4 mb-3">Mùa <?= e($mua['ten_mua_giai']) ?> <span class="text-muted fw-normal small">(mùa hiện tại)</span></h2>

    <?php
    $phanTram = $soMua['tong'] ? round(100 * $soMua['da_dau'] / $soMua['tong']) : 0;
    $oMua = [
        ['Trận đã đấu', $soMua['da_dau'] . ' / ' . $soMua['tong'], 'check2-circle', 'success', '/admin/ket_qua/index.php?loai=da'],
        ['Chờ nhập kết quả', $soMua['cho_ket_qua'], 'clipboard-check', $soMua['cho_ket_qua'] ? 'danger' : 'secondary', '/admin/ket_qua/index.php'],
        ['Trận hoãn', $soMua['hoan'], 'pause-circle', $soMua['hoan'] ? 'warning' : 'secondary', '/admin/lich_thi_dau/index.php?trang_thai=hoan'],
        ['Thiếu trọng tài', $soMua['thieu_trong_tai'], 'flag', $soMua['thieu_trong_tai'] ? 'warning' : 'secondary', '/admin/lich_thi_dau/index.php'],
    ];
    ?>
    <div class="row g-3">
        <?php foreach ($oMua as [$ten, $giaTri, $icon, $mau, $link]): ?>
            <div class="col-sm-6 col-xl-3">
                <a class="card shadow-sm h-100 text-decoration-none text-reset border-start border-4 border-<?= $mau ?>" href="<?= url($link) ?>">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small"><?= e($ten) ?></div>
                            <div class="fs-3 fw-bold"><?= e($giaTri) ?></div>
                        </div>
                        <i class="bi bi-<?= $icon ?> fs-1 text-<?= $mau ?>"></i>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="progress mt-3" role="progressbar" aria-label="Tiến độ mùa giải" aria-valuenow="<?= $phanTram ?>" aria-valuemin="0" aria-valuemax="100" style="height: 8px">
        <div class="progress-bar bg-success" style="width: <?= $phanTram ?>%"></div>
    </div>
    <div class="small text-muted mt-1">Đã đấu <?= $phanTram ?>% số trận đã lên lịch của mùa.</div>

    <div class="row g-3 mt-1">
        <!-- Trận chờ nhập kết quả -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Trận chờ nhập kết quả</span>
                    <a class="small" href="<?= url('/admin/ket_qua/index.php') ?>">Xem tất cả</a>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (!$choKetQua): ?>
                        <li class="list-group-item text-muted">Không có trận nào đang chờ. 👍</li>
                    <?php endif; ?>
                    <?php foreach ($choKetQua as $t): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <span class="text-muted small me-2"><?= e(dsbThoiGian($t['thoi_gian'])) ?></span>
                                <?= e($t['vt_khach']) ?> <span class="text-muted">@</span> <span class="fw-semibold"><?= e($t['vt_nha']) ?></span>
                            </span>
                            <a class="btn btn-sm btn-primary" href="<?= url('/admin/ket_qua/nhap.php?id=' . (int) $t['ma_tran']) ?>">Nhập</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Kết quả mới nhất -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Kết quả mới nhất</span>
                    <a class="small" href="<?= url('/ket_qua/index.php') ?>">Trang kết quả</a>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (!$ketQuaMoi): ?>
                        <li class="list-group-item text-muted">Chưa có trận nào có kết quả.</li>
                    <?php endif; ?>
                    <?php foreach ($ketQuaMoi as $t): ?>
                        <?php $khachThang = (int) $t['diem_doi_khach'] > (int) $t['diem_doi_nha']; ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted small"><?= e(dsbThoiGian($t['thoi_gian'])) ?></span>
                            <span class="text-nowrap">
                                <span class="<?= $khachThang ? 'fw-bold' : '' ?>"><?= e($t['vt_khach']) ?> <?= (int) $t['diem_doi_khach'] ?></span>
                                <span class="text-muted">-</span>
                                <span class="<?= $khachThang ? '' : 'fw-bold' ?>"><?= (int) $t['diem_doi_nha'] ?> <?= e($t['vt_nha']) ?></span>
                            </span>
                            <a class="small" href="<?= url('/ket_qua/index.php?tran=' . (int) $t['ma_tran']) ?>">Chi tiết</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Top đội mỗi miền -->
        <?php foreach ($topDoi as $tenHn => $dsDoi): ?>
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Top <?= count($dsDoi) ?> <?= e($tenHn) ?></span>
                        <a class="small" href="<?= url('/bang_xep_hang/index.php?hn=' . (int) $dsDoi[0]['ma_hoi_nghi']) ?>">Bảng xếp hạng</a>
                    </div>
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light small"><tr><th class="ps-3">#</th><th>Đội</th><th class="text-end">T</th><th class="text-end">B</th><th class="text-end pe-3">Tỉ lệ</th></tr></thead>
                        <tbody>
                        <?php foreach ($dsDoi as $i => $d): ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $i + 1 ?></td>
                                <td><?= e($d['ten_doi']) ?></td>
                                <td class="text-end"><?= (int) $d['thang'] ?></td>
                                <td class="text-end"><?= (int) $d['thua'] ?></td>
                                <td class="text-end pe-3"><?= $d['ti_le_thang'] !== null ? e(number_format((float) $d['ti_le_thang'], 3)) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$topDoi): ?>
            <div class="col-12"><div class="alert alert-info mb-0">Mùa này chưa phân bảng nên chưa có bảng xếp hạng. Vào <a href="<?= url('/admin/phan_bang/index.php') ?>">Phân bảng</a>.</div></div>
        <?php endif; ?>

        <!-- Biểu đồ số trận theo ngày -->
        <?php if ($theoNgay): ?>
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">Số trận theo ngày</div>
                    <div class="card-body"><div style="height: 260px"><canvas id="bieuDoTheoNgay" aria-label="Biểu đồ số trận theo ngày" role="img"></canvas></div></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($theoNgay): ?>
        <!-- Chart.js chỉ nạp ở trang Dashboard, không sửa header chung -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
        (function () {
            if (typeof Chart === 'undefined') { return; } // mất mạng thì bỏ qua biểu đồ
            var du = <?= json_encode(array_map(fn($r) => [
                'nhan' => date('d/m', strtotime($r['ngay'])), 'da' => (int) $r['da_dau'], 'chua' => (int) $r['chua_dau'],
            ], $theoNgay)) ?>;
            new Chart(document.getElementById('bieuDoTheoNgay'), {
                type: 'bar',
                data: {
                    labels: du.map(function (r) { return r.nhan; }),
                    datasets: [
                        { label: 'Đã đấu', data: du.map(function (r) { return r.da; }), backgroundColor: '#17408b' },
                        { label: 'Chưa đấu / hoãn', data: du.map(function (r) { return r.chua; }), backgroundColor: '#c9cfd8' }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        })();
        </script>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
