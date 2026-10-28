<?php
/**
 * Admin - Playoff (TV5): tạo các loạt từ bảng xếp hạng, xếp hạt giống, cập nhật đội thắng loạt.
 *   /admin/playoff/index.php           -> mùa hiện tại
 *   /admin/playoff/index.php?mua=2     -> mùa có ma_mua_giai = 2
 *
 * Số trận thắng mỗi đội trong loạt KHÔNG lưu trong loat_playoff; đếm từ tran_dau theo ma_loat
 * (các trận playoff do TV4 tạo và gắn vào loạt).
 * Ghép cặp vòng 1: hạt giống 1-8, 4-5, 3-6, 2-7 (lấy 8 đội đầu bảng xếp hạng mỗi miền).
 */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_thong_ke.php';

[$dsMua, $maMua] = tkLayMua($pdo);

// ---- Xử lý nút của admin (POST rồi chuyển trang để F5 không gửi lại) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $maMuaGui = (int) ($_POST['mua'] ?? $maMua);
    $hd = $_POST['hanh_dong'] ?? '';
    try {
        $pdo->beginTransaction();
        if ($hd === 'tao_vong_1') {
            $kq = tkTaoVong1($pdo, $maMuaGui);
        } elseif (in_array($hd, ['tao_ban_ket_mien', 'tao_chung_ket_mien', 'tao_chung_ket'], true)) {
            $kq = tkTaoVongKe($pdo, $maMuaGui, substr($hd, 4));
        } elseif ($hd === 'cap_nhat_thang') {
            $kq = 'Đã cập nhật đội thắng cho ' . tkCapNhatDoiThang($pdo, $maMuaGui) . ' loạt.';
        } else {
            throw new RuntimeException('Hành động không hợp lệ.');
        }
        $pdo->commit();
        datThongBao('ok', $kq);
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        datThongBao('loi', $ex->getMessage());
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        datThongBao('loi', 'Không thực hiện được: ' . $ex->getMessage());
    }
    chuyenTrang(url('/admin/playoff/index.php?mua=' . $maMuaGui));
}

$loat   = tkLayLoat($pdo, $maMua);
$tenMua = array_column($dsMua, 'ten_mua_giai', 'ma_mua_giai')[$maMua] ?? '';

$tieuDe = 'Quản lý Playoff';
$menu   = 'playoff';
require __DIR__ . '/../../includes/admin_header.php';
echo tkCss();
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Playoff <?= e($tenMua) ?></h1>
        <p class="text-muted mb-0">Nhánh đấu và tỉ số từng loạt</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-3">
        <?= tkChonMua($dsMua, $maMua) ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/thong_ke/playoff.php?mua=' . $maMua) ?>">Xem trang công khai</a>
    </div>
</div>

<form method="post" action="<?= url('/admin/playoff/index.php?mua=' . (int) $maMua) ?>" class="card shadow-sm card-body mb-4">
    <?= oCsrf() ?>
    <input type="hidden" name="mua" value="<?= (int) $maMua ?>">
    <div class="row g-3 align-items-end">
        <div class="col-lg-6">
            <h2 class="h6 mb-1">Dành cho quản trị viên</h2>
            <p class="small text-muted mb-0">Tạo loạt từ bảng xếp hạng (hạt giống 1–8, 4–5, 3–6, 2–7 mỗi miền), rồi tạo các vòng kế tiếp khi vòng trước đã có đội thắng.</p>
        </div>
        <div class="col-lg-4">
            <label for="hd" class="form-label small fw-semibold">Thao tác</label>
            <select id="hd" name="hanh_dong" class="form-select">
                <option value="tao_vong_1">Tạo vòng 1 từ bảng xếp hạng</option>
                <option value="cap_nhat_thang">Cập nhật đội thắng loạt</option>
                <option value="tao_ban_ket_mien">Tạo bán kết miền</option>
                <option value="tao_chung_ket_mien">Tạo chung kết miền</option>
                <option value="tao_chung_ket">Tạo chung kết NBA</option>
            </select>
        </div>
        <div class="col-lg-2 d-grid"><button class="btn btn-nba" type="submit">Thực hiện</button></div>
    </div>
</form>

<?php if (!$loat): ?>
    <div class="alert alert-info">Chưa có loạt playoff nào trong mùa này. Dùng khung phía trên để tạo vòng 1 từ bảng xếp hạng.</div>
<?php endif; ?>

<?= tkSoDoNhanh($loat) ?>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
