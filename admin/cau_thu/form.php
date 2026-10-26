<?php
/** Admin - Thêm / sửa cầu thủ (form.php = thêm, form.php?id=5 = sửa) (TV3) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/giao_dien.php';

$id = (int) ($_GET['id'] ?? 0);
$cu = $id ? layCauThu($id) : null;
if ($id && !$cu) {
    datThongBao('loi', 'Không tìm thấy cầu thủ.');
    chuyenTrang(url('/admin/cau_thu/index.php'));
}
$laSua = $cu !== null;

$d = $cu ?? [
    'ho_ten' => '', 'ngay_sinh' => '', 'noi_sinh' => '', 'quoc_tich' => '',
    'chieu_cao_cm' => '', 'can_nang_kg' => '', 'vi_tri' => '', 'tay_nem' => 'phai',
    'so_ao' => '', 'truong_dai_hoc' => '', 'nam_ra_mat_nba' => '', 'ma_doi' => '',
];
$giaiNghe = $laSua && $cu['trang_thai'] === 'giai_nghe';
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $d = chuanHoaDuLieuCauThu($_POST);
    if ($laSua) {
        // Không đổi đội ở form sửa, phải dùng chức năng Chuyển nhượng
        $d['ma_doi'] = $cu['ma_doi'] === null ? null : (int) $cu['ma_doi'];
    }
    $giaiNghe = $laSua && !empty($_POST['giai_nghe']);

    $loi = kiemTraDuLieuCauThu($d);

    if (!$loi && ($trung = kiemTraTrungSoAo($d['ma_doi'], $d['so_ao'], $id))) {
        $loi['so_ao'] = "Số áo {$d['so_ao']} đã thuộc về {$trung['ho_ten']} trong đội này.";
    }

    $anhMoi = null;
    if (!$loi) {
        try {
            $anhMoi = xuLyUploadAnh($_FILES['anh'] ?? null);
        } catch (RuntimeException $ex) {
            $loi['anh'] = $ex->getMessage();
        }
    }

    if (!$loi) {
        try {
            if ($laSua) {
                suaCauThu($id, $d, $giaiNghe, $anhMoi);
                if ($anhMoi !== null) {
                    xoaAnh($cu['anh']);
                }
                datThongBao('ok', "Đã cập nhật cầu thủ {$d['ho_ten']}.");
            } else {
                themCauThu($d, $anhMoi);
                datThongBao('ok', "Đã thêm cầu thủ {$d['ho_ten']}.");
            }
            chuyenTrang(url('/admin/cau_thu/index.php'));
        } catch (PDOException $ex) {
            xoaAnh($anhMoi);
            if ($ex->getCode() === '23000') {
                $loi['so_ao'] = 'Số áo bị trùng trong đội (có thể vừa có người khác cập nhật).';
            } else {
                $loi['chung'] = 'Lỗi CSDL: ' . $ex->getMessage();
            }
        }
    }
}

$dsDoi = layDanhSachDoi();

$tieuDe = $laSua ? 'Sửa cầu thủ' : 'Thêm cầu thủ';
$menu   = 'cau_thu';
require __DIR__ . '/../../includes/admin_header.php';
?>

<nav class="mb-2"><a href="<?= url('/admin/cau_thu/index.php') ?>"><i class="bi bi-arrow-left"></i> Danh sách cầu thủ</a></nav>
<h1 class="h3 mb-3"><?= $laSua ? 'Sửa cầu thủ: ' . e($cu['ho_ten']) : 'Thêm cầu thủ' ?></h1>

<?php if (isset($loi['chung'])): ?>
    <div class="alert alert-danger"><?= e($loi['chung']) ?></div>
<?php elseif ($loi): ?>
    <div class="alert alert-danger">Vui lòng kiểm tra lại các trường được đánh dấu.</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" novalidate>
            <?= oCsrf() ?>
            <div class="row g-3">
                <?php oNhap('ho_ten', 'Họ tên *', $d, $loi, 'text', 'maxlength="100" required'); ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="vi_tri">Vị trí *</label>
                    <select class="form-select<?= isset($loi['vi_tri']) ? ' is-invalid' : '' ?>" id="vi_tri" name="vi_tri" required>
                        <option value="">-- Chọn vị trí --</option>
                        <?php foreach (VI_TRI as $ma => $ten): ?>
                            <option value="<?= $ma ?>"<?= chon($d['vi_tri'], $ma) ?>><?= e($ten) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($loi['vi_tri'])): ?><div class="invalid-feedback"><?= e($loi['vi_tri']) ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="ma_doi">Đội bóng</label>
                    <?php if ($laSua): ?>
                        <input class="form-control" id="ma_doi" type="text" value="<?= e($cu['ten_doi'] ?? 'Không có đội') ?>" disabled>
                        <div class="form-text">
                            Đổi đội qua <a href="<?= url('/admin/cau_thu/chuyen_nhuong.php?id=' . $id) ?>">Chuyển nhượng</a> để lưu lịch sử.
                        </div>
                    <?php else: ?>
                        <select class="form-select<?= isset($loi['ma_doi']) ? ' is-invalid' : '' ?>" id="ma_doi" name="ma_doi">
                            <option value="">-- Cầu thủ tự do --</option>
                            <?php foreach ($dsDoi as $doi): ?>
                                <option value="<?= (int) $doi['ma_doi'] ?>"<?= chon($d['ma_doi'], $doi['ma_doi']) ?>><?= e($doi['ten_doi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($loi['ma_doi'])): ?><div class="invalid-feedback"><?= e($loi['ma_doi']) ?></div><?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php oNhap('so_ao', 'Số áo (0-99)', $d, $loi, 'number', 'min="0" max="99"'); ?>
                <?php oNhap('ngay_sinh', 'Ngày sinh', $d, $loi, 'date'); ?>
                <?php oNhap('noi_sinh', 'Nơi sinh', $d, $loi, 'text', 'maxlength="100" placeholder="VD: Akron, Ohio"'); ?>
                <?php oNhap('quoc_tich', 'Quốc tịch', $d, $loi, 'text', 'maxlength="50" placeholder="VD: Mỹ"'); ?>
                <?php oNhap('chieu_cao_cm', 'Chiều cao (cm)', $d, $loi, 'number', 'min="150" max="250"'); ?>
                <?php oNhap('can_nang_kg', 'Cân nặng (kg)', $d, $loi, 'number', 'min="50" max="200"'); ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="tay_nem">Tay ném</label>
                    <select class="form-select" id="tay_nem" name="tay_nem">
                        <option value="phai"<?= chon($d['tay_nem'], 'phai') ?>>Phải</option>
                        <option value="trai"<?= chon($d['tay_nem'], 'trai') ?>>Trái</option>
                    </select>
                </div>

                <?php oNhap('truong_dai_hoc', 'Trường đại học', $d, $loi, 'text', 'maxlength="100"'); ?>
                <?php oNhap('nam_ra_mat_nba', 'Năm ra mắt NBA', $d, $loi, 'number', 'min="1946" max="' . date('Y') . '"'); ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="anh">Ảnh (JPG, PNG, WEBP, tối đa 2MB)</label>
                    <input class="form-control<?= isset($loi['anh']) ? ' is-invalid' : '' ?>" type="file"
                           id="anh" name="anh" accept="image/jpeg,image/png,image/webp">
                    <?php if (isset($loi['anh'])): ?><div class="invalid-feedback"><?= e($loi['anh']) ?></div><?php endif; ?>
                    <?php if ($laSua && $cu['anh']): ?>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <?= anhCauThu($cu, 64) ?>
                            <span class="form-text m-0">Chọn ảnh mới để thay ảnh hiện tại.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($laSua): ?>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="giai_nghe" name="giai_nghe" value="1"<?= $giaiNghe ? ' checked' : '' ?>>
                            <label class="form-check-label" for="giai_nghe">Đã giải nghệ</label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> <?= $laSua ? 'Lưu thay đổi' : 'Thêm cầu thủ' ?></button>
                <a class="btn btn-outline-secondary" href="<?= url('/admin/cau_thu/index.php') ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
