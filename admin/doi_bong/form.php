<?php
/** Admin - Thêm / sửa đội bóng (form.php = thêm, form.php?id=5 = sửa) - TV2 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_doi_bong.php';

$id = (int) ($_GET['id'] ?? 0);
$cu = $id ? dbLay($id) : null;
if ($id && !$cu) {
    datThongBao('loi', 'Không tìm thấy đội.');
    chuyenTrang(url('/admin/doi_bong/index.php'));
}
$laSua = $cu !== null;

$d = $cu ?? ['ten_doi' => '', 'ten_viet_tat' => '', 'thanh_pho' => '', 'ma_san' => '', 'nam_thanh_lap' => '', 'mo_ta' => ''];
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $d   = dbChuanHoa($_POST);
    $loi = dbKiemTra($d, $id);

    $logoMoi = null;
    if (!$loi) {
        try {
            $logoMoi = tv2LuuAnh($_FILES['logo'] ?? null, THU_MUC_LOGO);
        } catch (RuntimeException $ex) {
            $loi['logo'] = $ex->getMessage();
        }
    }

    if (!$loi) {
        try {
            if ($laSua) {
                dbSua($id, $d, $logoMoi);
                if ($logoMoi !== null) {
                    tv2XoaAnh($cu['logo'], THU_MUC_LOGO);
                }
                datThongBao('ok', "Đã cập nhật đội {$d['ten_doi']}.");
            } else {
                dbThem($d, $logoMoi);
                datThongBao('ok', "Đã thêm đội {$d['ten_doi']}.");
            }
            chuyenTrang(url('/admin/doi_bong/index.php'));
        } catch (PDOException $ex) {
            tv2XoaAnh($logoMoi, THU_MUC_LOGO);
            $loi['chung'] = 'Không lưu được dữ liệu: ' . $ex->getMessage();
        }
    }
}

$dsSan = dbLaySanLuaChon();

/** Ô nhập có nhãn + thông báo lỗi (Bootstrap) */
function oNhapDoi(string $ten, string $nhan, array $d, array $loi, string $kieu = 'text', string $them = ''): void
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

$tieuDe = $laSua ? 'Sửa đội bóng' : 'Thêm đội bóng';
$menu   = 'doi_bong';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= url('/admin/doi_bong/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách đội</a></nav>
<h1 class="h3 mb-3"><?= e($tieuDe) ?></h1>

<?php if (isset($loi['chung'])): ?>
    <div class="alert alert-danger"><?= e($loi['chung']) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" novalidate>
            <?= oCsrf() ?>
            <div class="row g-3">
                <?php oNhapDoi('ten_doi', 'Tên đội *', $d, $loi, 'text', 'maxlength="100" required placeholder="VD: New York Knicks"'); ?>
                <?php oNhapDoi('ten_viet_tat', 'Tên viết tắt *', $d, $loi, 'text', 'maxlength="5" required placeholder="VD: NYK" style="text-transform: uppercase"'); ?>
                <?php oNhapDoi('thanh_pho', 'Thành phố *', $d, $loi, 'text', 'maxlength="100" required'); ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="ma_san">Sân nhà</label>
                    <select class="form-select<?= isset($loi['ma_san']) ? ' is-invalid' : '' ?>" id="ma_san" name="ma_san">
                        <option value="">-- Chưa có sân nhà --</option>
                        <?php foreach ($dsSan as $san): ?>
                            <option value="<?= (int) $san['ma_san'] ?>"<?= (string) $san['ma_san'] === (string) ($d['ma_san'] ?? '') ? ' selected' : '' ?>>
                                <?= e($san['ten_san']) ?> (<?= e($san['thanh_pho']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($loi['ma_san'])): ?><div class="invalid-feedback"><?= e($loi['ma_san']) ?></div><?php endif; ?>
                </div>

                <?php oNhapDoi('nam_thanh_lap', 'Năm thành lập', $d, $loi, 'number', 'min="1901" max="' . (int) date('Y') . '"'); ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="logo">Logo (JPG, PNG, WEBP, tối đa 2MB)</label>
                    <input class="form-control<?= isset($loi['logo']) ? ' is-invalid' : '' ?>" type="file"
                           id="logo" name="logo" accept="image/jpeg,image/png,image/webp">
                    <?php if (isset($loi['logo'])): ?><div class="invalid-feedback"><?= e($loi['logo']) ?></div><?php endif; ?>
                    <?php if ($laSua && $cu['logo']): ?>
                        <div class="mt-2">
                            <img src="<?= e(url('/' . $cu['logo'])) ?>" alt="" height="64">
                            <div class="form-text">Chọn ảnh mới để thay logo hiện tại.</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold" for="mo_ta">Mô tả</label>
                    <textarea class="form-control<?= isset($loi['mo_ta']) ? ' is-invalid' : '' ?>" id="mo_ta" name="mo_ta"
                              rows="3" maxlength="2000"><?= e($d['mo_ta'] ?? '') ?></textarea>
                    <?php if (isset($loi['mo_ta'])): ?><div class="invalid-feedback"><?= e($loi['mo_ta']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Lưu</button>
                <a class="btn btn-outline-secondary" href="<?= url('/admin/doi_bong/index.php') ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
