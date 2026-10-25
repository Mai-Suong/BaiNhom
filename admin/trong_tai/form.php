<?php
/** Admin - Thêm / sửa trọng tài (form.php = thêm, form.php?id=5 = sửa) - TV2 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_trong_tai.php';

$id = (int) ($_GET['id'] ?? 0);
$cu = $id ? ttLay($id) : null;
if ($id && !$cu) {
    datThongBao('loi', 'Không tìm thấy trọng tài.');
    chuyenTrang(url('/admin/trong_tai/index.php'));
}
$laSua = $cu !== null;

$d = $cu ?? ['ho_ten' => '', 'so_ao' => '', 'nam_bat_dau' => ''];
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $d   = ttChuanHoa($_POST);
    $loi = ttKiemTra($d, $id);

    if (!$loi) {
        try {
            if ($laSua) {
                ttSua($id, $d);
                datThongBao('ok', "Đã cập nhật trọng tài {$d['ho_ten']}.");
            } else {
                ttThem($d);
                datThongBao('ok', "Đã thêm trọng tài {$d['ho_ten']}.");
            }
            chuyenTrang(url('/admin/trong_tai/index.php'));
        } catch (PDOException $ex) {
            $loi['chung'] = 'Không lưu được dữ liệu: ' . $ex->getMessage();
        }
    }
}

/** Ô nhập có nhãn + thông báo lỗi (Bootstrap) */
function oNhapTt(string $ten, string $nhan, array $d, array $loi, string $kieu = 'text', string $them = ''): void
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

$tieuDe = $laSua ? 'Sửa trọng tài' : 'Thêm trọng tài';
$menu   = 'trong_tai';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= url('/admin/trong_tai/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách trọng tài</a></nav>
<h1 class="h3 mb-3"><?= e($tieuDe) ?></h1>

<?php if (isset($loi['chung'])): ?>
    <div class="alert alert-danger"><?= e($loi['chung']) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" novalidate>
            <?= oCsrf() ?>
            <div class="row g-3">
                <?php oNhapTt('ho_ten', 'Họ tên *', $d, $loi, 'text', 'maxlength="100" required'); ?>
                <?php oNhapTt('so_ao', 'Số áo', $d, $loi, 'number', 'min="0" max="99"'); ?>
                <?php oNhapTt('nam_bat_dau', 'Năm bắt đầu bắt ở NBA', $d, $loi, 'number', 'min="1946" max="' . (int) date('Y') . '"'); ?>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Lưu</button>
                <a class="btn btn-outline-secondary" href="<?= url('/admin/trong_tai/index.php') ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
