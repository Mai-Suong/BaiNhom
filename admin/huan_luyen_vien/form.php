<?php
/** Admin - Thêm / sửa huấn luyện viên (form.php = thêm, form.php?id=5 = sửa) - TV2 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_doi_bong.php';

$id = (int) ($_GET['id'] ?? 0);
$cu = $id ? hlvLay($id) : null;
if ($id && !$cu) {
    datThongBao('loi', 'Không tìm thấy HLV.');
    chuyenTrang(url('/admin/huan_luyen_vien/index.php'));
}
$laSua = $cu !== null;

$d = $cu ?? ['ho_ten' => '', 'ngay_sinh' => '', 'quoc_tich' => '', 'ma_doi' => '', 'vai_tro' => 'pho', 'ngay_bat_dau' => ''];
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $d   = hlvChuanHoa($_POST);
    $loi = hlvKiemTra($d, $id);

    $anhMoi = null;
    if (!$loi) {
        try {
            $anhMoi = tv2LuuAnh($_FILES['anh'] ?? null, THU_MUC_ANH_HLV);
        } catch (RuntimeException $ex) {
            $loi['anh'] = $ex->getMessage();
        }
    }

    if (!$loi) {
        try {
            if ($laSua) {
                hlvSua($id, $d, $anhMoi);
                if ($anhMoi !== null) {
                    tv2XoaAnh($cu['anh'], THU_MUC_ANH_HLV);
                }
                datThongBao('ok', "Đã cập nhật HLV {$d['ho_ten']}.");
            } else {
                hlvThem($d, $anhMoi);
                datThongBao('ok', "Đã thêm HLV {$d['ho_ten']}.");
            }
            chuyenTrang(url('/admin/huan_luyen_vien/index.php'));
        } catch (PDOException $ex) {
            tv2XoaAnh($anhMoi, THU_MUC_ANH_HLV);
            $loi['chung'] = 'Không lưu được dữ liệu: ' . $ex->getMessage();
        }
    }
}

$dsDoi = dbLayDanhSach();

/** Ô nhập có nhãn + thông báo lỗi (Bootstrap) */
function oNhapHlv(string $ten, string $nhan, array $d, array $loi, string $kieu = 'text', string $them = ''): void
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

$tieuDe = $laSua ? 'Sửa huấn luyện viên' : 'Thêm huấn luyện viên';
$menu   = 'hlv';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= url('/admin/huan_luyen_vien/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách HLV</a></nav>
<h1 class="h3 mb-3"><?= e($tieuDe) ?></h1>

<?php if (isset($loi['chung'])): ?>
    <div class="alert alert-danger"><?= e($loi['chung']) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" novalidate>
            <?= oCsrf() ?>
            <div class="row g-3">
                <?php oNhapHlv('ho_ten', 'Họ tên *', $d, $loi, 'text', 'maxlength="100" required'); ?>
                <?php oNhapHlv('quoc_tich', 'Quốc tịch', $d, $loi, 'text', 'maxlength="50" placeholder="VD: Mỹ"'); ?>
                <?php oNhapHlv('ngay_sinh', 'Ngày sinh', $d, $loi, 'date', 'max="' . date('Y-m-d') . '"'); ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="ma_doi">Đội</label>
                    <select class="form-select<?= isset($loi['ma_doi']) ? ' is-invalid' : '' ?>" id="ma_doi" name="ma_doi">
                        <option value="">-- Tự do (chưa có đội) --</option>
                        <?php foreach ($dsDoi as $doi): ?>
                            <option value="<?= (int) $doi['ma_doi'] ?>"<?= (string) $doi['ma_doi'] === (string) ($d['ma_doi'] ?? '') ? ' selected' : '' ?>>
                                <?= e($doi['ten_doi']) ?><?= $doi['hlv_truong'] !== null ? ' (HLV trưởng: ' . e($doi['hlv_truong']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($loi['ma_doi'])): ?><div class="invalid-feedback"><?= e($loi['ma_doi']) ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="vai_tro">Vai trò *</label>
                    <select class="form-select<?= isset($loi['vai_tro']) ? ' is-invalid' : '' ?>" id="vai_tro" name="vai_tro">
                        <?php foreach (VAI_TRO_HLV as $ma => $ten): ?>
                            <option value="<?= e($ma) ?>"<?= ($d['vai_tro'] ?? '') === $ma ? ' selected' : '' ?>><?= e($ten) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($loi['vai_tro'])): ?><div class="invalid-feedback"><?= e($loi['vai_tro']) ?></div><?php endif; ?>
                </div>

                <?php oNhapHlv('ngay_bat_dau', 'Ngày bắt đầu dẫn dắt đội hiện tại', $d, $loi, 'date'); ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="anh">Ảnh (JPG, PNG, WEBP, tối đa 2MB)</label>
                    <input class="form-control<?= isset($loi['anh']) ? ' is-invalid' : '' ?>" type="file"
                           id="anh" name="anh" accept="image/jpeg,image/png,image/webp">
                    <?php if (isset($loi['anh'])): ?><div class="invalid-feedback"><?= e($loi['anh']) ?></div><?php endif; ?>
                    <?php if ($laSua && $cu['anh']): ?>
                        <div class="mt-2">
                            <img src="<?= e(url('/' . $cu['anh'])) ?>" alt="" height="80" class="rounded">
                            <div class="form-text">Chọn ảnh mới để thay ảnh hiện tại.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Lưu</button>
                <a class="btn btn-outline-secondary" href="<?= url('/admin/huan_luyen_vien/index.php') ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
