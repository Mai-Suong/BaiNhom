<?php
/**
 * Admin - Nhập / sửa box score cho một trận đã đấu (TV5).
 *   /admin/box_score/index.php           -> chọn trận
 *   /admin/box_score/index.php?id=12     -> nhập box score trận có ma_tran = 12
 *
 * Kiểm tra dữ liệu (khớp với ràng buộc CHECK trong database, thêm kiểm tra của PHP):
 *   ném trúng <= ném thử, 3 điểm trúng <= ném trúng, 3 điểm thử <= ném thử, phạt trúng <= phạt thử,
 *   phạm lỗi <= 6, số phút <= 70, điểm = 2 x ném trúng + 3 điểm trúng + phạt trúng (tự tính),
 *   và (PHP) tổng điểm cầu thủ mỗi đội = tỉ số đội.
 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_thong_ke.php';

// tên cột => [nhãn ngắn, giá trị lớn nhất]  (các cột TINYINT trong database tối đa 127)
const TK_TRUONG = [
    'so_phut'            => ['Phút', 70],
    'nem_trung'          => ['Ném trúng', 127],
    'nem_thu'            => ['Ném thử', 127],
    'ba_diem_trung'      => ['3Đ trúng', 127],
    'ba_diem_thu'        => ['3Đ thử', 127],
    'phat_trung'         => ['Phạt trúng', 127],
    'phat_thu'           => ['Phạt thử', 127],
    'bat_bong_tan_cong'  => ['BB tấn công', 127],
    'bat_bong_phong_thu' => ['BB thủ', 127],
    'kien_tao'           => ['Kiến tạo', 127],
    'cuop_bong'          => ['Cướp', 127],
    'chan_bong'          => ['Chặn', 127],
    'mat_bong'           => ['Mất bóng', 127],
    'pham_loi'           => ['Lỗi', 6],
];
const TK_TRANG_THAI_HOP_LE = ['thi_dau', 'khong_thi_dau', 'vang_mat'];

/** Đọc số nguyên trong [min, max]; trả về null nếu không hợp lệ. Ô trống = 0. */
function tkDocSo($v, int $min, int $max): ?int
{
    $v = trim((string) $v);
    if ($v === '') {
        return 0;
    }
    if (!preg_match('/^-?\d+$/', $v)) {
        return null;
    }
    $n = (int) $v;
    return ($n < $min || $n > $max) ? null : $n;
}

/** Ghi một dòng box score: đã có thì UPDATE, chưa có thì INSERT */
function tkLuuDongBoxScore(PDO $pdo, array $b): void
{
    $cotSo = array_merge(['ma_doi', 'xuat_phat', 'trang_thai'], array_keys(TK_TRUONG), ['diem', 'cong_tru']);
    $co = $pdo->prepare('SELECT COUNT(*) FROM chi_so_tran_dau WHERE ma_tran = ? AND ma_cau_thu = ?');
    $co->execute([$b['ma_tran'], $b['ma_cau_thu']]);
    $giaTri = array_map(function ($cot) use ($b) { return $b[$cot]; }, $cotSo);
    if ((int) $co->fetchColumn() > 0) {
        $sql = 'UPDATE chi_so_tran_dau SET ' . implode(' = ?, ', $cotSo) . ' = ? WHERE ma_tran = ? AND ma_cau_thu = ?';
        $pdo->prepare($sql)->execute(array_merge($giaTri, [$b['ma_tran'], $b['ma_cau_thu']]));
    } else {
        $cotGhi = array_merge(['ma_tran', 'ma_cau_thu'], $cotSo);
        $sql = 'INSERT INTO chi_so_tran_dau (' . implode(', ', $cotGhi) . ') VALUES (' . implode(', ', array_fill(0, count($cotGhi), '?')) . ')';
        $pdo->prepare($sql)->execute(array_merge([$b['ma_tran'], $b['ma_cau_thu']], $giaTri));
    }
}

$id = (int) ($_GET['id'] ?? $_POST['ma_tran'] ?? 0);

$dsTran = tkTruyVan($pdo,
    'SELECT t.ma_tran, t.thoi_gian, t.trang_thai, n.ten_viet_tat vt_nha, k.ten_viet_tat vt_khach
     FROM tran_dau t
     JOIN doi_bong n ON n.ma_doi = t.ma_doi_nha
     JOIN doi_bong k ON k.ma_doi = t.ma_doi_khach
     ORDER BY t.thoi_gian DESC LIMIT 150');

$tran = $id ? tkTruyVan($pdo,
    'SELECT t.*, n.ten_doi ten_nha, n.ten_viet_tat vt_nha, k.ten_doi ten_khach, k.ten_viet_tat vt_khach
     FROM tran_dau t
     JOIN doi_bong n ON n.ma_doi = t.ma_doi_nha
     JOIN doi_bong k ON k.ma_doi = t.ma_doi_khach
     WHERE t.ma_tran = ?', [$id]) : [];
$t = $tran ? $tran[0] : null;
$choNhap = $t && $t['trang_thai'] === 'da_dau' && $t['diem_doi_nha'] !== null;

$doi = [];   // hai đội: [ma_doi, ten, viet_tat, diem_muc_tieu, danh_sach_cau_thu]
$cu  = [];   // dữ liệu đã lưu: [ma_cau_thu => dòng]
if ($choNhap) {
    foreach (tkTruyVan($pdo, 'SELECT * FROM chi_so_tran_dau WHERE ma_tran = ?', [$id]) as $r) {
        $cu[(int) $r['ma_cau_thu']] = $r;
    }
    $dinh = [
        [(int) $t['ma_doi_nha'], $t['ten_nha'], $t['vt_nha'], (int) $t['diem_doi_nha']],
        [(int) $t['ma_doi_khach'], $t['ten_khach'], $t['vt_khach'], (int) $t['diem_doi_khach']],
    ];
    foreach ($dinh as [$maDoi, $ten, $vt, $muc]) {
        // đội hình hiện tại + những người đã có dòng trong trận này cho đội đó
        $ds = tkTruyVan($pdo,
            'SELECT c.ma_cau_thu, c.ho_ten, c.vi_tri, c.so_ao FROM cau_thu c
             WHERE c.ma_doi = ?
                OR c.ma_cau_thu IN (SELECT ma_cau_thu FROM chi_so_tran_dau WHERE ma_tran = ? AND ma_doi = ?)
             ORDER BY c.ho_ten', [$maDoi, $id, $maDoi]);
        $doi[] = [$maDoi, $ten, $vt, $muc, $ds];
    }
}

// ---- Xử lý khi bấm Lưu ----
$loi    = [];
$daGui  = ($_SERVER['REQUEST_METHOD'] === 'POST') && $choNhap;
$banGhi = [];   // các dòng sẽ ghi
$xoa    = [];   // các ma_cau_thu cần xóa dòng cũ
if ($daGui) {
    kiemTraCsrf();
    foreach ($doi as [$maDoi, $ten, $vt, $muc, $ds]) {
        $tongDiem = 0;
        foreach ($ds as $c) {
            $ma = (int) $c['ma_cau_thu'];
            $in = (isset($_POST['cs'][$ma]) && is_array($_POST['cs'][$ma])) ? $_POST['cs'][$ma] : [];
            $tt = $in['trang_thai'] ?? '';
            if (!in_array($tt, TK_TRANG_THAI_HOP_LE, true)) {
                if (isset($cu[$ma])) {
                    $xoa[] = $ma;
                }
                continue;   // không ghi dòng cho cầu thủ này
            }
            $b = ['ma_tran' => $id, 'ma_cau_thu' => $ma, 'ma_doi' => $maDoi, 'trang_thai' => $tt,
                  'xuat_phat' => (!empty($in['xuat_phat']) && $tt === 'thi_dau') ? 1 : 0];
            foreach (TK_TRUONG as $cot => [$nhan, $max]) {
                $b[$cot] = 0;
            }
            $b['diem'] = 0;
            $congTru = tkDocSo($in['cong_tru'] ?? '', -300, 300);
            $b['cong_tru'] = $congTru ?? 0;
            if ($tt === 'thi_dau') {
                $nhanLoi = $c['ho_ten'] . ' (' . $vt . ')';
                foreach (TK_TRUONG as $cot => [$nhan, $max]) {
                    $n = tkDocSo($in[$cot] ?? '', 0, $max);
                    if ($n === null) {
                        $loi[] = $nhanLoi . ': "' . $nhan . '" phải là số nguyên từ 0 đến ' . $max . '.';
                        $n = 0;
                    }
                    $b[$cot] = $n;
                }
                if ($congTru === null) {
                    $loi[] = $nhanLoi . ': "+/−" phải là số nguyên từ -300 đến 300.';
                }
                if ($b['nem_trung'] > $b['nem_thu'])         { $loi[] = $nhanLoi . ': ném trúng (' . $b['nem_trung'] . ') lớn hơn ném thử (' . $b['nem_thu'] . ').'; }
                if ($b['ba_diem_trung'] > $b['ba_diem_thu']) { $loi[] = $nhanLoi . ': 3 điểm trúng lớn hơn 3 điểm thử.'; }
                if ($b['ba_diem_trung'] > $b['nem_trung'])   { $loi[] = $nhanLoi . ': 3 điểm trúng (' . $b['ba_diem_trung'] . ') lớn hơn ném trúng (' . $b['nem_trung'] . ').'; }
                if ($b['ba_diem_thu'] > $b['nem_thu'])       { $loi[] = $nhanLoi . ': 3 điểm thử lớn hơn ném thử.'; }
                if ($b['phat_trung'] > $b['phat_thu'])       { $loi[] = $nhanLoi . ': phạt trúng lớn hơn phạt thử.'; }
                $b['diem'] = 2 * $b['nem_trung'] + $b['ba_diem_trung'] + $b['phat_trung'];
                $tongDiem += $b['diem'];
            } else {
                $b['cong_tru'] = 0;
            }
            $banGhi[] = $b;
        }
        if ($tongDiem !== $muc) {
            $loi[] = 'Tổng điểm cầu thủ ' . $vt . ' (' . $tongDiem . ') khác tỉ số đội (' . $muc . ').';
        }
    }
    if (!$banGhi && !$xoa && !$loi) {
        $loi[] = 'Chưa có cầu thủ nào được chọn trạng thái.';
    }

    if (!$loi) {
        try {
            $pdo->beginTransaction();
            foreach ($banGhi as $b) {
                tkLuuDongBoxScore($pdo, $b);
            }
            if ($xoa) {
                $xo = $pdo->prepare('DELETE FROM chi_so_tran_dau WHERE ma_tran = ? AND ma_cau_thu = ?');
                foreach ($xoa as $ma) {
                    $xo->execute([$id, $ma]);
                }
            }
            $pdo->commit();
            datThongBao('ok', 'Đã lưu box score.');
            chuyenTrang(url('/thong_ke/box_score.php?id=' . $id));
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $loi[] = 'Không lưu được: ' . $ex->getMessage();
        }
    }
}

// Giá trị hiển thị trong ô: ưu tiên dữ liệu vừa gửi (khi có lỗi), rồi dữ liệu đã lưu
function tkGiaTriO(int $ma, string $cot, array $cu, bool $daGui): string
{
    if ($daGui && isset($_POST['cs'][$ma][$cot])) { return (string) $_POST['cs'][$ma][$cot]; }
    if (!$daGui && isset($cu[$ma])) { return (string) $cu[$ma][$cot]; }
    return '';
}
function tkTrangThaiO(int $ma, array $cu, bool $daGui): string
{
    if ($daGui) { return (string) ($_POST['cs'][$ma]['trang_thai'] ?? ''); }
    return isset($cu[$ma]) ? $cu[$ma]['trang_thai'] : '';
}
function tkXuatPhatO(int $ma, array $cu, bool $daGui): bool
{
    if ($daGui) { return !empty($_POST['cs'][$ma]['xuat_phat']); }
    return isset($cu[$ma]) && (int) $cu[$ma]['xuat_phat'] === 1;
}

$tieuDe = 'Nhập box score';
$menu   = 'box_score';
require __DIR__ . '/../../includes/admin_header.php';
echo tkCss();
?>

<h1 class="h3 mb-1">Nhập box score</h1>
<p class="text-muted">Chọn trận đã đấu, nhập chỉ số từng cầu thủ của hai đội. Điểm cá nhân tự tính.</p>

<form method="get" class="card shadow-sm card-body mb-4">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <label for="id" class="fw-semibold mb-0">Trận đấu</label>
        <select id="id" name="id" class="form-select w-auto" style="min-width:320px" onchange="this.form.submit()">
            <option value="0">— Chọn trận —</option>
            <?php foreach ($dsTran as $r): ?>
                <option value="<?= (int) $r['ma_tran'] ?>"<?= (int) $r['ma_tran'] === $id ? ' selected' : '' ?>>
                    <?= e(date('d/m/Y H:i', strtotime($r['thoi_gian']))) ?> · <?= e($r['vt_khach']) ?> @ <?= e($r['vt_nha']) ?><?= $r['trang_thai'] === 'da_dau' ? '' : ' (chưa đấu)' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($t): ?>
            <a class="btn btn-outline-secondary" href="<?= url('/thong_ke/box_score.php?id=' . $id) ?>">Xem box score</a>
        <?php endif; ?>
    </div>
</form>

<?php if (!$dsTran): ?>
    <div class="alert alert-info">Chưa có trận đấu nào. Cần lập lịch và nhập kết quả trận trước (phần của TV4).</div>
<?php elseif ($id && !$t): ?>
    <div class="alert alert-danger">Không tìm thấy trận này.</div>
<?php elseif ($t && !$choNhap): ?>
    <div class="alert alert-warning">Trận này chưa có kết quả (chưa đấu hoặc đã hoãn). Cần nhập tỉ số ở phần quản lý kết quả trước, vì box score phải khớp tỉ số đội.</div>
<?php endif; ?>

<?php if ($loi): ?>
    <div class="alert alert-danger"><strong>Chưa lưu được, vui lòng sửa:</strong>
        <ul class="mb-0"><?php foreach ($loi as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if ($choNhap): ?>
<form method="post" action="<?= url('/admin/box_score/index.php?id=' . $id) ?>">
    <?= oCsrf() ?>
    <input type="hidden" name="ma_tran" value="<?= $id ?>">

    <?php foreach ($doi as $stt => [$maDoi, $ten, $vt, $muc, $ds]): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">
                <span class="vong-tron"><?= e($vt) ?></span><?= e($ten) ?>
                <small class="text-muted fw-normal">· <?= $stt === 0 ? 'chủ nhà' : 'khách' ?> · tỉ số <?= $muc ?></small>
            </div>
            <?php if (!$ds): ?>
                <p class="p-3 mb-0">Đội này chưa có cầu thủ nào (cần thêm cầu thủ trước ở mục Cầu thủ).</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0 bang-so bang-nhap">
                    <thead><tr>
                        <th class="trai">Cầu thủ</th><th>Trạng thái</th><th class="giua">Xuất phát</th>
                        <?php foreach (TK_TRUONG as $cot => [$nhan, $max]): ?><th><?= e($nhan) ?></th><?php endforeach; ?>
                        <th>Điểm</th><th>+/−</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($ds as $c): $ma = (int) $c['ma_cau_thu']; $tt = tkTrangThaiO($ma, $cu, $daGui); ?>
                        <tr data-hang="1" data-doi="<?= $stt ?>">
                            <td class="trai"><?= e($c['ho_ten']) ?> <small class="text-muted"><?= e($c['vi_tri']) ?><?= $c['so_ao'] !== null ? ' · #' . (int) $c['so_ao'] : '' ?></small></td>
                            <td>
                                <select class="form-select form-select-sm" name="cs[<?= $ma ?>][trang_thai]" aria-label="Trạng thái – <?= e($c['ho_ten']) ?>">
                                    <option value=""<?= $tt === '' ? ' selected' : '' ?>>— không ghi —</option>
                                    <option value="thi_dau"<?= $tt === 'thi_dau' ? ' selected' : '' ?>>Thi đấu</option>
                                    <option value="khong_thi_dau"<?= $tt === 'khong_thi_dau' ? ' selected' : '' ?>>Không thi đấu</option>
                                    <option value="vang_mat"<?= $tt === 'vang_mat' ? ' selected' : '' ?>>Vắng mặt</option>
                                </select>
                            </td>
                            <td class="giua"><input type="checkbox" class="form-check-input" name="cs[<?= $ma ?>][xuat_phat]" value="1"<?= tkXuatPhatO($ma, $cu, $daGui) ? ' checked' : '' ?> aria-label="Xuất phát – <?= e($c['ho_ten']) ?>"></td>
                            <?php foreach (TK_TRUONG as $cot => [$nhan, $max]): ?>
                                <td><input type="number" class="form-control form-control-sm" min="0" max="<?= $max ?>" data-f="<?= $cot ?>" name="cs[<?= $ma ?>][<?= $cot ?>]" value="<?= e(tkGiaTriO($ma, $cot, $cu, $daGui)) ?>" aria-label="<?= e($nhan) ?> – <?= e($c['ho_ten']) ?>"></td>
                            <?php endforeach; ?>
                            <td><strong class="pts">0</strong></td>
                            <td><input type="number" class="form-control form-control-sm" min="-300" max="300" name="cs[<?= $ma ?>][cong_tru]" value="<?= e(tkGiaTriO($ma, 'cong_tru', $cu, $daGui)) ?>" aria-label="Cộng trừ – <?= e($c['ho_ten']) ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="tong-doi" data-doi="<?= $stt ?>" data-muc-tieu="<?= $muc ?>">
                <span>Tổng điểm cầu thủ <?= e($vt) ?>: <b class="tong">0</b></span>
                <span>Tỉ số đội: <?= $muc ?></span>
                <span class="chenh"></span>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <button class="btn btn-nba" type="submit">Lưu box score</button>
        <a class="btn btn-outline-secondary" href="<?= url('/thong_ke/box_score.php?id=' . $id) ?>">Hủy</a>
        <span class="small text-muted">Chỉ cầu thủ có chọn trạng thái mới được ghi. Chọn "— không ghi —" sẽ xóa dòng đã lưu của cầu thủ đó.</span>
    </div>
</form>

<script>
(function () {
    function so(el) { var v = parseInt(el ? el.value : '', 10); return isNaN(v) ? 0 : v; }
    function tinh() {
        var tong = {};
        document.querySelectorAll('tr[data-hang]').forEach(function (tr) {
            var pts = 0;
            if (tr.querySelector('select').value === 'thi_dau') {
                pts = 2 * so(tr.querySelector('[data-f=nem_trung]')) + so(tr.querySelector('[data-f=ba_diem_trung]')) + so(tr.querySelector('[data-f=phat_trung]'));
            }
            tr.querySelector('.pts').textContent = pts;
            var d = tr.getAttribute('data-doi');
            tong[d] = (tong[d] || 0) + pts;
        });
        document.querySelectorAll('.tong-doi').forEach(function (box) {
            var d = box.getAttribute('data-doi'), muc = parseInt(box.getAttribute('data-muc-tieu'), 10), t = tong[d] || 0;
            box.querySelector('.tong').textContent = t;
            box.classList.toggle('khop', t === muc);
            box.classList.toggle('lech', t !== muc);
            box.querySelector('.chenh').textContent = t === muc ? '✓ Khớp tỉ số' : 'Lệch ' + Math.abs(t - muc) + ' điểm';
        });
    }
    document.addEventListener('input', tinh);
    document.addEventListener('change', tinh);
    tinh();
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
