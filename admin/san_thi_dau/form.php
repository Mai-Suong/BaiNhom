<?php
/** Admin - Thêm / sửa sân thi đấu (form.php = thêm, form.php?id=5 = sửa) - TV2 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_doi_bong.php';

$id = (int) ($_GET['id'] ?? 0);
$cu = $id ? sanLay($id) : null;
if ($id && !$cu) {
    datThongBao('loi', 'Không tìm thấy sân.');
    chuyenTrang(url('/admin/san_thi_dau/index.php'));
}
$laSua = $cu !== null;

$d = $cu ?? ['ten_san' => '', 'thanh_pho' => '', 'tieu_bang' => '', 'suc_chua' => '', 'nam_khanh_thanh' => ''];
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $d   = sanChuanHoa($_POST);
    $loi = sanKiemTra($d);

    $anhMoi = null;
    if (!$loi) {
        try {
            $anhMoi = tv2LuuAnh($_FILES['hinh_anh'] ?? null, THU_MUC_ANH_SAN);
        } catch (RuntimeException $ex) {
            $loi['hinh_anh'] = $ex->getMessage();
        }
    }

    if (!$loi) {
        try {
            if ($laSua) {
                sanSua($id, $d, $anhMoi);
                if ($anhMoi !== null) {
                    tv2XoaAnh($cu['hinh_anh'], THU_MUC_ANH_SAN);
                }
                datThongBao('ok', "Đã cập nhật sân {$d['ten_san']}.");
            } else {
                sanThem($d, $anhMoi);
                datThongBao('ok', "Đã thêm sân {$d['ten_san']}.");
            }
            chuyenTrang(url('/admin/san_thi_dau/index.php'));
        } catch (PDOException $ex) {
            tv2XoaAnh($anhMoi, THU_MUC_ANH_SAN);
            $loi['chung'] = 'Không lưu được dữ liệu: ' . $ex->getMessage();
        }
    }
}

/** Ô nhập có nhãn + thông báo lỗi (Bootstrap) */
function oNhapSan(string $ten, string $nhan, array $d, array $loi, string $kieu = 'text', string $them = ''): void
{
    ?>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="<?= e($ten) ?>"><?= e($nhan) ?></label>
        <input class="form-control<?= isset($loi[$ten]) ? ' is-invalid' : '' ?>" type="<?= e($kieu) ?>"
               id="<?= e($ten) ?>" name="<?= e($ten) ?>" value="<?= e($d[$ten] ?? '') ?>" <?= $them ?>>
        <?php if (isset($loi[$ten])): ?><div class="invalid-feedback"><?= e($loi[$ten]) ?></div><?php endif; ?>
    </div>
    <?php
}

$tieuDe = $laSua ? 'Sửa sân thi đấu' : 'Thêm sân thi đấu';
$menu   = 'san';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= url('/admin/san_thi_dau/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách sân</a></nav>
<h1 class="h3 mb-3"><?= e($tieuDe) ?></h1>

<?php if (isset($loi['chung'])): ?>
    <div class="alert alert-danger"><?= e($loi['chung']) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" novalidate>
            <?= oCsrf() ?>
            <div class="row g-3">
                <?php oNhapSan('ten_san', 'Tên sân *', $d, $loi, 'text', 'maxlength="100" required'); ?>
                <?php oNhapSan('thanh_pho', 'Thành phố *', $d, $loi, 'text', 'maxlength="100" required'); ?>
                <?php oNhapSan('tieu_bang', 'Bang / tỉnh', $d, $loi, 'text', 'maxlength="50"'); ?>
                <?php oNhapSan('suc_chua', 'Sức chứa (chỗ ngồi)', $d, $loi, 'number', 'min="1" max="100000"'); ?>
                <?php oNhapSan('nam_khanh_thanh', 'Năm khánh thành', $d, $loi, 'number', 'min="1901" max="' . ((int) date('Y') + 5) . '"'); ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="hinh_anh">Ảnh sân (JPG, PNG, WEBP, tối đa 2MB)</label>
                    <input class="form-control<?= isset($loi['hinh_anh']) ? ' is-invalid' : '' ?>" type="file"
                           id="hinh_anh" name="hinh_anh" accept="image/jpeg,image/png,image/webp">
                    <?php if (isset($loi['hinh_anh'])): ?><div class="invalid-feedback"><?= e($loi['hinh_anh']) ?></div><?php endif; ?>
                    <?php if ($laSua && $cu['hinh_anh']): ?>
                        <div class="mt-2">
                            <img src="<?= e(url('/' . $cu['hinh_anh'])) ?>" alt="" height="80" class="rounded">
                            <div class="form-text">Chọn ảnh mới để thay ảnh hiện tại.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Lưu</button>
                <a class="btn btn-outline-secondary" href="<?= url('/admin/san_thi_dau/index.php') ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
