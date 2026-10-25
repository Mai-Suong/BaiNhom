<?php
/**
 * TRANG MẪU (TV1) - cả nhóm xem để làm theo cùng một kiểu khi dùng giao diện chung.
 * Hiển thị 2 hội nghị và 6 khu vực (dữ liệu có sẵn trong database).
 * Xem: http://localhost/BaiNhom/khu_vuc/index.php
 */

// 1. Luôn require init.php đầu tiên (có $pdo, session, các hàm dùng chung)
require_once __DIR__ . '/../includes/init.php';

// 2. Lấy dữ liệu. Có tham số thì PHẢI dùng prepare + dấu ?
$stmt = $pdo->prepare(
    'SELECT kv.ma_khu_vuc, kv.ten_khu_vuc
     FROM khu_vuc kv
     WHERE kv.ma_hoi_nghi = ?
     ORDER BY kv.ma_khu_vuc'
);
$theoMien = [];
foreach ($pdo->query('SELECT ma_hoi_nghi, ten_hoi_nghi FROM hoi_nghi ORDER BY ma_hoi_nghi') as $hn) {
    $stmt->execute([$hn['ma_hoi_nghi']]);
    $theoMien[$hn['ten_hoi_nghi']] = $stmt->fetchAll();
}

// 3. Đặt tiêu đề rồi include header
$tieuDe = 'Hội nghị và khu vực';
require __DIR__ . '/../includes/header.php';
?>

<!-- 4. Phần HTML. In dữ liệu luôn dùng e() -->
<h1 class="h3 mb-4">Hội nghị và khu vực</h1>

<div class="row g-4">
    <?php foreach ($theoMien as $tenMien => $dsKhuVuc): ?>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold"><?= e($tenMien) ?></div>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Mã</th><th>Khu vực</th></tr></thead>
                    <tbody>
                    <?php foreach ($dsKhuVuc as $kv): ?>
                        <tr>
                            <td><?= e($kv['ma_khu_vuc']) ?></td>
                            <td><?= e($kv['ten_khu_vuc']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
// 5. Include footer ở cuối
require __DIR__ . '/../includes/footer.php';
