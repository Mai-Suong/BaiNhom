<?php
/** Admin - Danh sách tài khoản, tìm kiếm, khóa / mở khóa (TV1) */
require_once __DIR__ . '/../../includes/init.php';
yeuCauAdmin();
require_once __DIR__ . '/../../includes/ham_tai_khoan.php';

$tuKhoa = trim((string) ($_GET['q'] ?? ''));
$dsTk   = tkLayDanhSach($tuKhoa);
$maToi  = (int) $_SESSION['ma_tai_khoan'];

$tieuDe = 'Quản lý tài khoản';
$menu   = 'tai_khoan';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Tài khoản</h1>
    <a class="btn btn-primary" href="<?= url('/admin/tai_khoan/form.php') ?>"><i class="bi bi-plus-lg"></i> Tạo tài khoản cầu thủ</a>
</div>

<form class="d-flex flex-wrap gap-2 mb-3" method="get">
    <input class="form-control" style="max-width: 320px" type="search" name="q"
           value="<?= e($tuKhoa) ?>" placeholder="Tìm theo tên đăng nhập hoặc tên cầu thủ">
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Tìm</button>
    <?php if ($tuKhoa !== ''): ?>
        <a class="btn btn-link" href="<?= url('/admin/tai_khoan/index.php') ?>">Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>Tên đăng nhập</th>
                <th>Vai trò</th>
                <th>Cầu thủ</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th class="text-end" style="width: 260px"></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$dsTk): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">
                    <?= $tuKhoa !== '' ? 'Không tìm thấy tài khoản phù hợp.' : 'Chưa có tài khoản nào.' ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($dsTk as $tk): ?>
                <?php $laToi = (int) $tk['ma_tai_khoan'] === $maToi; ?>
                <tr>
                    <td class="fw-semibold">
                        <?= e($tk['ten_dang_nhap']) ?>
                        <?php if ($laToi): ?><span class="badge text-bg-light border">Bạn</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tk['vai_tro'] === 'admin'): ?>
                            <span class="badge text-bg-dark">Admin</span>
                        <?php else: ?>
                            <span class="badge text-bg-info">Cầu thủ</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tk['ma_cau_thu']): ?>
                            <a href="<?= url('/cau_thu/ho_so.php?id=' . (int) $tk['ma_cau_thu']) ?>"><?= e($tk['ten_cau_thu']) ?></a>
                            <?php if ($tk['doi']): ?><span class="text-muted small">(<?= e($tk['doi']) ?>)</span><?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tk['bi_khoa']): ?>
                            <span class="badge text-bg-danger"><i class="bi bi-lock-fill"></i> Đã khóa</span>
                        <?php else: ?>
                            <span class="badge text-bg-success">Hoạt động</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= e(date('d/m/Y', strtotime($tk['ngay_tao']))) ?></td>
                    <td class="text-end">
                        <?php if ($laToi): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= url('/doi_mat_khau.php') ?>"><i class="bi bi-key"></i> Đổi mật khẩu</a>
                        <?php else: ?>
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= url('/admin/tai_khoan/dat_lai_mat_khau.php?id=' . (int) $tk['ma_tai_khoan']) ?>">
                                <i class="bi bi-arrow-repeat"></i> Đặt lại mật khẩu
                            </a>
                            <?php if ($tk['vai_tro'] !== 'admin'): ?>
                                <form class="d-inline" method="post" action="<?= url('/admin/tai_khoan/khoa.php') ?>">
                                    <?= oCsrf() ?>
                                    <input type="hidden" name="id" value="<?= (int) $tk['ma_tai_khoan'] ?>">
                                    <?php if ($tk['bi_khoa']): ?>
                                        <input type="hidden" name="khoa" value="0">
                                        <button class="btn btn-sm btn-outline-success" type="submit"><i class="bi bi-unlock"></i> Mở khóa</button>
                                    <?php else: ?>
                                        <input type="hidden" name="khoa" value="1">
                                        <button class="btn btn-sm btn-outline-danger" type="submit"
                                                onclick="return confirm('Khóa tài khoản <?= e($tk['ten_dang_nhap']) ?>? Người này sẽ không đăng nhập được.')">
                                            <i class="bi bi-lock"></i> Khóa
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">
    Tổng: <?= count($dsTk) ?> tài khoản. Tài khoản quản trị không khóa được; bạn không thể tự khóa tài khoản đang đăng nhập.
</p>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
