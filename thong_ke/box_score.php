<?php
/**
 * Box score một trận (TV5).
 *   /thong_ke/box_score.php          -> danh sách các trận đã đấu để chọn
 *   /thong_ke/box_score.php?id=12    -> box score trận có ma_tran = 12
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ham_thong_ke.php';

$id = (int) ($_GET['id'] ?? 0);
$menu = 'thong_ke';
$thu = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];

// ---- Chưa chọn trận: hiện danh sách ----
if ($id === 0) {
    $ds = tkTruyVan($pdo,
        "SELECT t.ma_tran, t.thoi_gian, t.diem_doi_nha, t.diem_doi_khach, n.ten_viet_tat vt_nha, k.ten_viet_tat vt_khach
         FROM tran_dau t
         JOIN doi_bong n ON n.ma_doi = t.ma_doi_nha
         JOIN doi_bong k ON k.ma_doi = t.ma_doi_khach
         WHERE t.trang_thai = 'da_dau'
         ORDER BY t.thoi_gian DESC LIMIT 40");
    $tieuDe = 'Box score';
    require __DIR__ . '/../includes/header.php';
    echo tkCss();
    ?>
    <h1 class="h3 mb-1">Thống kê</h1>
    <p class="text-muted">Box score · chọn một trận đã đấu để xem chi tiết</p>
    <?= tkMenu('box_score') ?>
    <?php if (!$ds): ?>
        <div class="alert alert-info">Chưa có trận nào đã đấu. Cần nhập kết quả trận trước.</div>
    <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($ds as $t): ?>
                <a class="list-group-item list-group-item-action d-flex flex-wrap justify-content-between gap-2" href="?id=<?= (int) $t['ma_tran'] ?>">
                    <span class="text-muted"><?= e(date('d/m/Y H:i', strtotime($t['thoi_gian']))) ?></span>
                    <strong><?= e($t['vt_khach']) ?> <?= (int) $t['diem_doi_khach'] ?> – <?= (int) $t['diem_doi_nha'] ?> <?= e($t['vt_nha']) ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// ---- Có chọn trận ----
$tran = tkTruyVan($pdo,
    'SELECT t.*, n.ten_doi ten_nha, n.ten_viet_tat vt_nha, k.ten_doi ten_khach, k.ten_viet_tat vt_khach,
            s.ten_san, m.ten_mua_giai
     FROM tran_dau t
     JOIN doi_bong n ON n.ma_doi = t.ma_doi_nha
     JOIN doi_bong k ON k.ma_doi = t.ma_doi_khach
     LEFT JOIN san_thi_dau s ON s.ma_san = t.ma_san
     JOIN mua_giai m ON m.ma_mua_giai = t.ma_mua_giai
     WHERE t.ma_tran = ?', [$id]);

if (!$tran) {
    http_response_code(404);
    $tieuDe = 'Không tìm thấy trận';
    require __DIR__ . '/../includes/header.php';
    echo '<h1 class="h3">Không tìm thấy trận</h1><p><a href="' . url('/thong_ke/box_score.php') . '">← Về danh sách trận</a></p>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}
$t = $tran[0];

$hiep     = tkTruyVan($pdo, 'SELECT ma_doi, hiep, diem FROM diem_hiep WHERE ma_tran = ? ORDER BY hiep', [$id]);
$trongTai = tkTruyVan($pdo,
    'SELECT tt.ho_ten FROM phan_cong_trong_tai p JOIN trong_tai tt ON tt.ma_trong_tai = p.ma_trong_tai
     WHERE p.ma_tran = ? ORDER BY tt.ho_ten', [$id]);
$cauThu   = tkTruyVan($pdo,
    'SELECT cs.*, c.ho_ten, c.vi_tri
     FROM chi_so_tran_dau cs JOIN cau_thu c ON c.ma_cau_thu = cs.ma_cau_thu
     WHERE cs.ma_tran = ?
     ORDER BY cs.xuat_phat DESC, cs.so_phut DESC, c.ho_ten', [$id]);

$theoDoi = [(int) $t['ma_doi_nha'] => [], (int) $t['ma_doi_khach'] => []];
foreach ($cauThu as $r) {
    $theoDoi[(int) $r['ma_doi']][] = $r;
}
$dongNha   = $theoDoi[(int) $t['ma_doi_nha']];
$dongKhach = $theoDoi[(int) $t['ma_doi_khach']];

// Điểm theo hiệp
$dsHiep = [];
$diemHiep = [];
foreach ($hiep as $r) {
    $dsHiep[(int) $r['hiep']] = true;
    $diemHiep[(int) $r['ma_doi']][(int) $r['hiep']] = (int) $r['diem'];
}
ksort($dsHiep);

$daDau    = $t['trang_thai'] === 'da_dau' && $t['diem_doi_nha'] !== null;
$dN       = $daDau ? (int) $t['diem_doi_nha'] : null;
$dK       = $daDau ? (int) $t['diem_doi_khach'] : null;
$nhaThang = $daDau && $dN > $dK;
$giaiDoan = ['thuong' => 'Mùa thường', 'playin' => 'Play-in', 'playoff' => 'Playoff'][$t['giai_doan']] ?? '';
if ($t['giai_doan'] === 'playoff' && $t['tran_so_trong_loat']) {
    $giaiDoan .= ' · Trận ' . (int) $t['tran_so_trong_loat'];
}
$trangThai = ['chua_dau' => 'Chưa đấu', 'da_dau' => 'Kết thúc', 'hoan' => 'Hoãn'][$t['trang_thai']] ?? '';
$ts = strtotime($t['thoi_gian']);

// Kiểm tra tổng điểm cầu thủ khớp tỉ số đội
[, , , $tongNha]   = tkTinhDoi($dongNha);
[, , , $tongKhach] = tkTinhDoi($dongKhach);
$coBox = (bool) $cauThu;
$khop  = $daDau && $coBox && $tongNha['diem'] === $dN && $tongKhach['diem'] === $dK;

$tieuDe = $t['vt_khach'] . ' @ ' . $t['vt_nha'] . ' · Box score';
require __DIR__ . '/../includes/header.php';
echo tkCss();
?>

<a class="text-decoration-none fw-semibold" href="<?= url('/thong_ke/box_score.php') ?>">← Về danh sách trận</a>

<div class="card shadow-sm mt-2 mb-3 overflow-hidden">
    <div class="bang-diem">
        <div class="doi-lon">
            <span class="huy-hieu"><?= e($t['vt_khach']) ?></span>
            <div><div class="ten-lon"><?= e($t['ten_khach']) ?></div><div class="vai-tro">Đội khách</div></div>
            <span class="diem-lon ms-auto<?= $daDau && $nhaThang ? ' thua' : '' ?>"><?= $daDau ? $dK : '–' ?></span>
        </div>
        <div class="giua-bang">
            <span class="trang-thai"><?= e($trangThai) ?></span>
            <span class="vai-tro"><?= e($giaiDoan) ?> <?= e($t['ten_mua_giai']) ?></span>
        </div>
        <div class="doi-lon phai">
            <span class="huy-hieu"><?= e($t['vt_nha']) ?></span>
            <div><div class="ten-lon"><?= e($t['ten_nha']) ?></div><div class="vai-tro">Đội chủ nhà</div></div>
            <span class="diem-lon me-auto<?= $daDau && !$nhaThang ? ' thua' : '' ?>"><?= $daDau ? $dN : '–' ?></span>
        </div>
    </div>

    <?php if ($dsHiep): ?>
        <div class="table-responsive px-4 pt-3">
            <table class="table table-sm bang-so mb-2" style="max-width:640px">
                <thead><tr>
                    <th class="trai">Điểm theo hiệp</th>
                    <?php foreach ($dsHiep as $so => $_): ?>
                        <th><?= $so <= 4 ? 'Hiệp ' . $so : 'HP' . ($so - 4) ?></th>
                    <?php endforeach; ?>
                    <th>Tổng</th>
                </tr></thead>
                <tbody>
                <?php foreach ([[$t['vt_khach'], (int) $t['ma_doi_khach'], $dK], [$t['vt_nha'], (int) $t['ma_doi_nha'], $dN]] as [$vt, $maDoi, $tongDiem]): ?>
                    <tr>
                        <td class="trai"><strong><?= e($vt) ?></strong></td>
                        <?php foreach ($dsHiep as $so => $_): ?>
                            <td><?= $diemHiep[$maDoi][$so] ?? '–' ?></td>
                        <?php endforeach; ?>
                        <td><strong><?= $tongDiem === null ? '–' : $tongDiem ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="luoi-thong-tin">
        <div><span class="tieu-nho">Thời gian</span><?= e($thu[(int) date('w', $ts)] . ', ' . date('d/m/Y · H:i', $ts)) ?></div>
        <div><span class="tieu-nho">Sân thi đấu</span><?= e($t['ten_san'] ?? '—') ?></div>
        <div><span class="tieu-nho">Khán giả</span><?= $t['khan_gia'] === null ? '—' : e(number_format((int) $t['khan_gia'], 0, ',', '.')) ?></div>
        <div><span class="tieu-nho">Trọng tài</span><?= $trongTai ? e(implode(', ', array_column($trongTai, 'ho_ten'))) : '—' ?></div>
    </div>
</div>

<?php if ($coBox && $daDau): ?>
    <div class="dong-kiem-tra d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <?php if ($khop): ?>
            <span class="khop">✓ Tổng điểm cầu thủ khớp tỉ số của cả hai đội</span>
        <?php else: ?>
            <span class="lech">! Tổng điểm cầu thủ (<?= $tongKhach['diem'] ?> – <?= $tongNha['diem'] ?>) chưa khớp tỉ số đội (<?= $dK ?> – <?= $dN ?>)</span>
        <?php endif; ?>
        <?php if (laAdmin()): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/box_score/index.php?id=' . $id) ?>">Sửa box score trận này</a>
        <?php endif; ?>
    </div>
    <?= tkBangDoi($t['ten_nha'], $t['vt_nha'], $dongNha) ?>
    <?= tkBangDoi($t['ten_khach'], $t['vt_khach'], $dongKhach) ?>
    <p class="small text-muted">BBTC: bắt bóng tấn công · BBPT: bắt bóng phòng thủ · KT: kiến tạo. Tỉ lệ ném và tổng bóng bắt tính khi hiển thị, không lưu trong database.</p>
<?php else: ?>
    <div class="alert alert-info">
        <?= $daDau ? 'Trận này chưa có box score.' : 'Trận này chưa đấu nên chưa có box score.' ?>
        <?php if (laAdmin() && $daDau): ?>
            <a class="alert-link" href="<?= url('/admin/box_score/index.php?id=' . $id) ?>">Nhập box score →</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
