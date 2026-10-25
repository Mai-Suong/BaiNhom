<?php
/** Admin - Thêm / sửa cầu thủ (form.php = thêm, form.php?id=5 = sửa) */
require_once __DIR__ . '/../../includes/giao_dien.php';
yeuCauAdmin();

$id = (int) ($_GET['id'] ?? 0);
$cu = $id ? layCauThu($id) : null;
if ($id && !$cu) {
    datThongBao('loi', 'Không tìm thấy cầu thủ.');
    chuyenTrang('index.php');
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
            chuyenTrang('index.php');
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
dauTrang($laSua ? 'Sửa cầu thủ' : 'Thêm cầu thủ');
?>
<div class="dau">
    <h1><?= $laSua ? 'Sửa cầu thủ: ' . e($cu['ho_ten']) : 'Thêm cầu thủ' ?></h1>
    <a class="nut phu" href="index.php">← Danh sách</a>
</div>

<?php if (isset($loi['chung'])): ?>
    <div class="thong-bao loi"><?= e($loi['chung']) ?></div>
<?php elseif ($loi): ?>
    <div class="thong-bao loi">Vui lòng kiểm tra lại các trường được đánh dấu.</div>
<?php endif; ?>

<form class="the" method="post" enctype="multipart/form-data" novalidate>
    <?= oCsrf() ?>
    <div class="luoi-form">
        <?php oNhap('ho_ten', 'Họ tên *', $d, $loi, 'text', 'maxlength="100" required'); ?>

        <div class="truong">
            <label for="vi_tri">Vị trí *</label>
            <select id="vi_tri" name="vi_tri" required>
                <option value="">-- Chọn vị trí --</option>
                <?php foreach (VI_TRI as $ma => $ten): ?>
                    <option value="<?= $ma ?>"<?= chon($d['vi_tri'], $ma) ?>><?= e($ten) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($loi['vi_tri'])): ?><div class="loi"><?= e($loi['vi_tri']) ?></div><?php endif; ?>
        </div>

        <div class="truong">
            <label for="ma_doi">Đội bóng</label>
            <?php if ($laSua): ?>
                <input type="text" value="<?= e($cu['ten_doi'] ?? 'Tự do') ?>" disabled>
                <div class="mo" style="font-size:13px;margin-top:3px">
                    Đổi đội qua <a href="chuyen_nhuong.php?id=<?= $id ?>">Chuyển nhượng</a> để lưu lịch sử.
                </div>
            <?php else: ?>
                <select id="ma_doi" name="ma_doi">
                    <option value="">— Tự do —</option>
                    <?php foreach ($dsDoi as $doi): ?>
                        <option value="<?= $doi['ma_doi'] ?>"<?= chon($d['ma_doi'], $doi['ma_doi']) ?>><?= e($doi['ten_doi']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($loi['ma_doi'])): ?><div class="loi"><?= e($loi['ma_doi']) ?></div><?php endif; ?>
            <?php endif; ?>
        </div>

        <?php oNhap('so_ao', 'Số áo (0-99)', $d, $loi, 'number', 'min="0" max="99"'); ?>
        <?php oNhap('ngay_sinh', 'Ngày sinh', $d, $loi, 'date'); ?>
        <?php oNhap('noi_sinh', 'Nơi sinh', $d, $loi, 'text', 'maxlength="100" placeholder="VD: Akron, Ohio"'); ?>
        <?php oNhap('quoc_tich', 'Quốc tịch', $d, $loi, 'text', 'maxlength="50" placeholder="VD: Mỹ"'); ?>
        <?php oNhap('chieu_cao_cm', 'Chiều cao (cm)', $d, $loi, 'number', 'min="150" max="250"'); ?>
        <?php oNhap('can_nang_kg', 'Cân nặng (kg)', $d, $loi, 'number', 'min="50" max="200"'); ?>

        <div class="truong">
            <label for="tay_nem">Tay ném</label>
            <select id="tay_nem" name="tay_nem">
                <option value="phai"<?= chon($d['tay_nem'], 'phai') ?>>Phải</option>
                <option value="trai"<?= chon($d['tay_nem'], 'trai') ?>>Trái</option>
            </select>
        </div>

        <?php oNhap('truong_dai_hoc', 'Trường đại học', $d, $loi, 'text', 'maxlength="100"'); ?>
        <?php oNhap('nam_ra_mat_nba', 'Năm ra mắt NBA', $d, $loi, 'number', 'min="1946" max="' . date('Y') . '"'); ?>

        <div class="truong">
            <label for="anh">Ảnh (JPG/PNG/WEBP, tối đa 2MB)</label>
            <?php if ($laSua && $cu['anh']): ?>
                <div style="margin-bottom:6px"><?= anhCauThu($cu, 64) ?></div>
            <?php endif; ?>
            <input type="file" id="anh" name="anh" accept="image/jpeg,image/png,image/webp">
            <?php if (isset($loi['anh'])): ?><div class="loi"><?= e($loi['anh']) ?></div><?php endif; ?>
        </div>

        <?php if ($laSua): ?>
        <div class="truong">
            <label>&nbsp;</label>
            <label style="font-weight:normal">
                <input type="checkbox" name="giai_nghe" value="1"<?= $giaiNghe ? ' checked' : '' ?> style="width:auto">
                Đã giải nghệ
            </label>
        </div>
        <?php endif; ?>
    </div>

    <div style="margin-top:20px">
        <button class="nut" type="submit"><?= $laSua ? 'Lưu thay đổi' : 'Thêm cầu thủ' ?></button>
        <a class="nut phu" href="index.php">Hủy</a>
    </div>
</form>

<?php cuoiTrang();
