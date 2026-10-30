<?php
/** Admin - Thêm / sửa trận và phân công trọng tài (form.php = thêm, form.php?id=5 = sửa) - TV4 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_lich_thi_dau.php';

$id = (int) ($_GET['id'] ?? 0);
$cu = $id ? ltdLay($id) : null;
if ($id && !$cu) {
    datThongBao('loi', 'Không tìm thấy trận đấu.');
    chuyenTrang(url('/admin/lich_thi_dau/index.php'));
}
$laSua = $cu !== null;
$khoa  = $laSua && ltdDaKhoa($id, $cu['trang_thai']);

$dsMua      = ltdLayDsMua();
$dsDoi      = ltdLayDsDoi();
$dsSan      = ltdLayDsSan();
$dsTrongTai = ltdLayDsTrongTai();
$dsLoat     = ltdLayDsLoat();

if ($laSua) {
    $d = $cu;
    $d['trong_tai'] = ltdLayTrongTaiCuaTran($id);
} else {
    $muaMacDinh = ltdChonMua($dsMua, (int) ($_GET['mua'] ?? 0));
    $d = [
        'ma_mua_giai' => $muaMacDinh['ma_mua_giai'] ?? null, 'ma_doi_nha' => null, 'ma_doi_khach' => null,
        'ma_san' => null, 'thoi_gian' => '', 'giai_doan' => 'thuong', 'ma_loat' => null,
        'tran_so_trong_loat' => null, 'trang_thai' => 'chua_dau', 'trong_tai' => [],
    ];
}
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $d   = ltdChuanHoa($_POST);
    $loi = ltdKiemTra($d, $cu);
    if (!$loi) {
        try {
            if ($laSua) {
                ltdSua($id, $d);
                datThongBao('ok', 'Đã cập nhật trận đấu.');
            } else {
                ltdThem($d);
                datThongBao('ok', 'Đã thêm trận đấu.');
            }
            chuyenTrang(url('/admin/lich_thi_dau/index.php?mua=' . (int) $d['ma_mua_giai']));
        } catch (PDOException $ex) {
            $loi['chung'] = 'Không lưu được dữ liệu: ' . $ex->getMessage();
        }
    }
}

/** Ô chọn có nhãn + lỗi. $ds = [giá trị => chữ hiện]. */
function oChonTran(string $ten, string $nhan, array $ds, $giaTri, array $loi, string $lop = 'col-md-6',
                   bool $khoa = false, string $trong = '', string $them = ''): void
{
    ?>
    <div class="<?= e($lop) ?>">
        <label class="form-label fw-semibold" for="<?= e($ten) ?>"><?= e($nhan) ?></label>
        <select class="form-select<?= isset($loi[$ten]) ? ' is-invalid' : '' ?>" id="<?= e($ten) ?>" name="<?= e($ten) ?>"
            <?= $khoa ? 'disabled' : '' ?> <?= $them ?>>
            <?php if ($trong !== ''): ?><option value=""><?= e($trong) ?></option><?php endif; ?>
            <?php foreach ($ds as $v => $chu): ?>
                <option value="<?= e($v) ?>" <?= (string) $giaTri === (string) $v ? 'selected' : '' ?>><?= e($chu) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($loi[$ten])): ?><div class="invalid-feedback"><?= e($loi[$ten]) ?></div><?php endif; ?>
    </div>
    <?php
}

$chonMua = [];
foreach ($dsMua as $m) {
    $chonMua[$m['ma_mua_giai']] = $m['ten_mua_giai'] . ((int) $m['la_mua_hien_tai'] === 1 ? ' (hiện tại)' : '');
}
$chonDoi = array_column($dsDoi, 'ten_doi', 'ma_doi');
$chonSan = [];
foreach ($dsSan as $s) {
    $chonSan[$s['ma_san']] = $s['ten_san'] . ' (' . $s['thanh_pho'] . ')';
}
$chonTt = [];
foreach ($dsTrongTai as $t) {
    $chonTt[$t['ma_trong_tai']] = $t['ho_ten'] . ($t['so_ao'] !== null ? ' (#' . $t['so_ao'] . ')' : '');
}
$chonLoat = [];
foreach ($dsLoat as $l) {
    $tenMua = $chonMua[$l['ma_mua_giai']] ?? '';
    $chonLoat[$l['ma_loat']] = $tenMua . ' · ' . $l['vong'] . ': ' . $l['vt_1'] . ' - ' . $l['vt_2'];
}
$chonTrangThai = $khoa ? LTD_TRANG_THAI : ['chua_dau' => LTD_TRANG_THAI['chua_dau'], 'hoan' => LTD_TRANG_THAI['hoan']];
$sanNhaTheoMua = ltdSanNhaTheoMua($dsMua, $dsDoi); // dùng cho JS tự chọn sân nhà theo mùa

$tieuDe = $laSua ? 'Sửa trận đấu' : 'Thêm trận đấu';
$menu   = 'lich';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= url('/admin/lich_thi_dau/index.php' . ($d['ma_mua_giai'] ? '?mua=' . (int) $d['ma_mua_giai'] : '')) ?>"><i class="bi bi-arrow-left"></i> Lịch thi đấu</a></nav>
<h1 class="h3 mb-3"><?= e($tieuDe) ?></h1>

<?php if (!$dsMua): ?>
    <div class="alert alert-warning">Chưa có mùa giải nào. Hãy <a href="<?= url('/admin/mua_giai/form.php') ?>">thêm mùa giải</a> trước.</div>
    <?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
    <?php return; ?>
<?php endif; ?>

<?php if ($khoa): ?>
    <div class="alert alert-info">
        Trận này đã có kết quả (hoặc điểm hiệp / box score) nên không đổi được mùa, đội, giai đoạn, trạng thái.
        Vẫn sửa được thời gian, sân và trọng tài. Tỉ số sửa ở trang Nhập kết quả.
    </div>
<?php endif; ?>
<?php if (isset($loi['chung'])): ?>
    <div class="alert alert-danger"><?= e($loi['chung']) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" novalidate>
            <?= oCsrf() ?>
            <?php if ($khoa): /* ô bị disabled không gửi lên, server tự giữ giá trị cũ */ ?>
                <input type="hidden" name="ma_mua_giai" value="<?= (int) $d['ma_mua_giai'] ?>">
            <?php endif; ?>
            <div class="row g-3">
                <?php oChonTran('ma_mua_giai', 'Mùa giải *', $chonMua, $d['ma_mua_giai'], $loi, 'col-md-4', $khoa); ?>
                <?php oChonTran('giai_doan', 'Giai đoạn *', LTD_GIAI_DOAN, $d['giai_doan'], $loi, 'col-md-4', $khoa); ?>
                <?php oChonTran('trang_thai', 'Trạng thái', $chonTrangThai, $d['trang_thai'], $loi, 'col-md-4', $khoa); ?>

                <?php oChonTran('ma_doi_khach', 'Đội khách *', $chonDoi, $d['ma_doi_khach'], $loi, 'col-md-6', $khoa, '-- Chọn đội khách --'); ?>
                <?php oChonTran('ma_doi_nha', 'Đội nhà *', $chonDoi, $d['ma_doi_nha'], $loi, 'col-md-6', $khoa, '-- Chọn đội nhà --'); ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="thoi_gian">Ngày giờ thi đấu *</label>
                    <input class="form-control<?= isset($loi['thoi_gian']) ? ' is-invalid' : '' ?>" type="datetime-local"
                           id="thoi_gian" name="thoi_gian" value="<?= e(ltdGiaTriO($d['thoi_gian']) ?: $d['thoi_gian']) ?>" required>
                    <?php if (isset($loi['thoi_gian'])): ?><div class="invalid-feedback"><?= e($loi['thoi_gian']) ?></div><?php endif; ?>
                </div>
                <?php oChonTran('ma_san', 'Sân thi đấu', $chonSan, $d['ma_san'], $loi, 'col-md-6', false, '-- Chưa chọn sân --'); ?>

                <div class="col-12" id="khung_playoff">
                    <div class="row g-3">
                        <?php oChonTran('ma_loat', 'Loạt playoff', $chonLoat, $d['ma_loat'], $loi, 'col-md-8', $khoa,
                            $chonLoat ? '-- Chọn loạt --' : '-- Chưa có loạt playoff (TV5 tạo) --'); ?>
                        <?php oChonTran('tran_so_trong_loat', 'Trận số', array_combine(range(1, 7), range(1, 7)),
                            $d['tran_so_trong_loat'], $loi, 'col-md-4', $khoa, '--'); ?>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Trọng tài (để trống hoặc chọn đủ <?= LTD_SO_TRONG_TAI ?> người)</label>
                    <div class="row g-2">
                        <?php for ($i = 0; $i < LTD_SO_TRONG_TAI; $i++): ?>
                            <div class="col-md-4">
                                <select class="form-select<?= isset($loi['trong_tai']) ? ' is-invalid' : '' ?>" name="trong_tai[]" aria-label="Trọng tài <?= $i + 1 ?>">
                                    <option value="">-- Trọng tài <?= $i + 1 ?> --</option>
                                    <?php foreach ($chonTt as $v => $chu): ?>
                                        <option value="<?= (int) $v ?>" <?= (int) ($d['trong_tai'][$i] ?? 0) === (int) $v ? 'selected' : '' ?>><?= e($chu) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <?php if (isset($loi['trong_tai'])): ?><div class="text-danger small mt-1"><?= e($loi['trong_tai']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Lưu</button>
                <a class="btn btn-outline-secondary" href="<?= url('/admin/lich_thi_dau/index.php' . ($d['ma_mua_giai'] ? '?mua=' . (int) $d['ma_mua_giai'] : '')) ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>

<script>
// Hiện ô playoff khi chọn giai đoạn Playoff; chọn đội nhà / mùa thì tự chọn sân nhà của đội trong mùa đó (nếu chưa chọn sân khác)
(function () {
    var sanNhaMua = <?= json_encode($sanNhaTheoMua, JSON_FORCE_OBJECT) ?>;
    var mua = document.getElementById('ma_mua_giai');
    var giaiDoan = document.getElementById('giai_doan');
    var khung = document.getElementById('khung_playoff');
    var doiNha = document.getElementById('ma_doi_nha');
    var san = document.getElementById('ma_san');
    function capNhatPlayoff() { khung.style.display = giaiDoan.value === 'playoff' ? '' : 'none'; }
    giaiDoan.addEventListener('change', capNhatPlayoff);
    capNhatPlayoff();
    function sanCuaDoi() { var m = sanNhaMua[mua.value] || {}; return m[doiNha.value]; }
    var sanTuDong = san.value === '' || String(sanCuaDoi()) === san.value;
    san.addEventListener('change', function () { sanTuDong = false; });
    function tuChonSan() { if (sanTuDong && sanCuaDoi()) { san.value = String(sanCuaDoi()); } }
    doiNha.addEventListener('change', tuChonSan);
    mua.addEventListener('change', tuChonSan);
})();
</script>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
