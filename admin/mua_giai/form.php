<?php
/** Admin - Thêm / sửa mùa giải (form.php = thêm, form.php?id=1 = sửa) - TV4 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_mua_giai.php';

$id = (int) ($_GET['id'] ?? 0);
$cu = $id ? mgLay($id) : null;
if ($id && !$cu) {
    datThongBao('loi', 'Không tìm thấy mùa giải.');
    chuyenTrang(url('/admin/mua_giai/index.php'));
}
$laSua = $cu !== null;

$d = $cu ?? ['ten_mua_giai' => '', 'ngay_bat_dau' => '', 'ngay_ket_thuc' => '', 'la_mua_hien_tai' => 0];
$d['la_mua_hien_tai'] = (int) $d['la_mua_hien_tai'];
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $d   = mgChuanHoa($_POST);
    $loi = mgKiemTra($d, $laSua ? $id : null);

    if (!$loi) {
        try {
            if ($laSua) {
                mgSua($id, $d);
                datThongBao('ok', "Đã cập nhật mùa giải {$d['ten_mua_giai']}.");
            } else {
                mgThem($d);
                datThongBao('ok', "Đã thêm mùa giải {$d['ten_mua_giai']}.");
            }
            chuyenTrang(url('/admin/mua_giai/index.php'));
        } catch (PDOException $ex) {
            $loi['chung'] = 'Không lưu được dữ liệu: ' . $ex->getMessage();
        }
    }
}

/** Ô nhập có nhãn + thông báo lỗi (Bootstrap) */
function oNhapMua(string $ten, string $nhan, array $d, array $loi, string $kieu = 'text', string $them = ''): void
{
    ?>
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="<?= e($ten) ?>"><?= e($nhan) ?></label>
        <input class="form-control<?= isset($loi[$ten]) ? ' is-invalid' : '' ?>" type="<?= e($kieu) ?>"
               id="<?= e($ten) ?>" name="<?= e($ten) ?>" value="<?= e($d[$ten] ?? '') ?>" <?= $them ?>>
        <?php if (isset($loi[$ten])): ?><div class="invalid-feedback"><?= e($loi[$ten]) ?></div><?php endif; ?>
    </div>
    <?php
}

$tieuDe = $laSua ? 'Sửa mùa giải' : 'Thêm mùa giải';
$menu   = 'mua_giai';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= url('/admin/mua_giai/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách mùa giải</a></nav>
<h1 class="h3 mb-3"><?= e($tieuDe) ?></h1>

<?php if (isset($loi['chung'])): ?>
    <div class="alert alert-danger"><?= e($loi['chung']) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" novalidate>
            <?= oCsrf() ?>
            <div class="row g-3">
                <?php oNhapMua('ten_mua_giai', 'Tên mùa giải *', $d, $loi, 'text', 'maxlength="7" placeholder="VD: 2025-26" required'); ?>
                <?php oNhapMua('ngay_bat_dau', 'Ngày bắt đầu *', $d, $loi, 'date', 'required'); ?>
                <?php oNhapMua('ngay_ket_thuc', 'Ngày kết thúc *', $d, $loi, 'date', 'required'); ?>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input<?= isset($loi['la_mua_hien_tai']) ? ' is-invalid' : '' ?>"
                               type="checkbox" id="la_mua_hien_tai" name="la_mua_hien_tai" value="1"
                               <?= $d['la_mua_hien_tai'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="la_mua_hien_tai">Đặt làm mùa hiện tại</label>
                        <?php if (isset($loi['la_mua_hien_tai'])): ?>
                            <div class="invalid-feedback"><?= e($loi['la_mua_hien_tai']) ?></div>
                        <?php endif; ?>
                        <div class="form-text">Chỉ có 1 mùa hiện tại. Chọn ô này thì mùa hiện tại cũ sẽ tự bỏ chọn.</div>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Lưu</button>
                <a class="btn btn-outline-secondary" href="<?= url('/admin/mua_giai/index.php') ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
