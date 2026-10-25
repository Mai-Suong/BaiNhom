<?php
/**
 * HÀM DÙNG CHUNG CỦA TV2 (upload ảnh, kiểm tra ngày, mùa hiện tại, vai trò HLV).
 * File này GIỐNG HỆT NHAU ở mọi nhánh feature/tv2-... nên merge nhiều nhánh cùng lúc không bị xung đột.
 * Muốn sửa file này: sửa trong 1 PR riêng, đừng sửa kèm PR module.
 */
require_once __DIR__ . '/../config/db.php';

const TV2_ANH_TOI_DA = 2 * 1024 * 1024; // 2MB

/* =====================================================================
 * ẢNH (dùng chung cho ảnh sân, logo đội, ảnh HLV)
 * ===================================================================== */

/**
 * Lưu ảnh tải lên vào uploads/<$thuMuc>/. Trả về đường dẫn tương đối để lưu vào CSDL,
 * NULL nếu người dùng không chọn ảnh. Ném RuntimeException nếu ảnh không hợp lệ.
 */
function tv2LuuAnh(?array $file, string $thuMuc): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        || ($file['error'] === UPLOAD_ERR_OK && $file['size'] > TV2_ANH_TOI_DA)) {
        throw new RuntimeException('Ảnh vượt quá dung lượng cho phép (2MB).');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Tải ảnh thất bại (mã lỗi ' . $file['error'] . ').');
    }

    // Kiểm tra nội dung thật của file, không tin vào đuôi file
    $info = @getimagesize($file['tmp_name']);
    $duoi = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
    if ($duoi === null) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
    }

    $tuongDoi = 'uploads/' . $thuMuc . '/';
    $tuyetDoi = __DIR__ . '/../' . $tuongDoi;
    if (!is_dir($tuyetDoi) && !mkdir($tuyetDoi, 0775, true)) {
        throw new RuntimeException('Không tạo được thư mục lưu ảnh.');
    }
    // uploads/ không đưa lên GitHub nên tự tạo file chặn chạy PHP trong thư mục ảnh
    if (!is_file($tuyetDoi . '.htaccess')) {
        @file_put_contents(
            $tuyetDoi . '.htaccess',
            "<FilesMatch \"\\.(php|phtml|phar|php\\d)$\">\n    Require all denied\n</FilesMatch>\nOptions -Indexes\n"
        );
    }

    $tenFile = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $duoi;
    if (!move_uploaded_file($file['tmp_name'], $tuyetDoi . $tenFile)) {
        throw new RuntimeException('Không lưu được ảnh.');
    }
    return $tuongDoi . $tenFile;
}

/** Xóa file ảnh cũ (chỉ xóa file nằm trong uploads/<$thuMuc>/) */
function tv2XoaAnh(?string $duongDan, string $thuMuc): void
{
    $tienTo = 'uploads/' . $thuMuc . '/';
    if (!$duongDan || strpos($duongDan, $tienTo) !== 0 || strpos($duongDan, '..') !== false) {
        return;
    }
    $file = __DIR__ . '/../' . $duongDan;
    if (is_file($file)) {
        @unlink($file);
    }
}

/* =====================================================================
 * NGÀY, MÙA GIẢI, HẰNG SỐ
 * ===================================================================== */

/** Kiểm tra chuỗi ngày dạng YYYY-MM-DD có thật (VD 2025-02-30 là sai) */
function tv2NgayHopLe(string $s): bool
{
    $dt = DateTime::createFromFormat('!Y-m-d', $s);
    return $dt !== false && $dt->format('Y-m-d') === $s;
}

/** Mùa giải đang diễn ra (la_mua_hien_tai = 1), NULL nếu chưa có */
function tv2LayMuaHienTai(): ?array
{
    $st = ketNoi()->query('SELECT * FROM mua_giai WHERE la_mua_hien_tai = 1 ORDER BY ngay_bat_dau DESC LIMIT 1');
    return $st->fetch() ?: null;
}

/** Vai trò HLV (dùng ở trang quản trị HLV và trang chi tiết đội) */
const VAI_TRO_HLV = ['truong' => 'HLV trưởng', 'pho' => 'HLV phó'];
