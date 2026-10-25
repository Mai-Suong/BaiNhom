<?php
/**
 * MODULE ĐỘI BÓNG (TV2) - toàn bộ hàm truy vấn và nghiệp vụ.
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng: san_thi_dau, doi_bong, huan_luyen_vien, trong_tai, tran_dau
 *
 * Tên hàm có tiền tố riêng (san..., tv2...) để KHÔNG trùng hàm của TV3
 * (includes/ham_cau_thu.php đã có layDanhSachDoi(), doiTonTai(), xuLyUploadAnh(), xoaAnh()).
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
 * SÂN THI ĐẤU
 * ===================================================================== */

const THU_MUC_ANH_SAN = 'san_thi_dau';

/** Danh sách sân (tìm theo tên sân / thành phố), kèm số đội đang dùng làm sân nhà */
function sanLayDanhSach(string $tuKhoa = ''): array
{
    $sql = 'SELECT s.*,
                   (SELECT COUNT(*) FROM doi_bong d WHERE d.ma_san = s.ma_san) AS so_doi
              FROM san_thi_dau s';
    $thamSo = [];
    if ($tuKhoa !== '') {
        $sql .= ' WHERE s.ten_san LIKE ? OR s.thanh_pho LIKE ?';
        $thamSo = ['%' . $tuKhoa . '%', '%' . $tuKhoa . '%'];
    }
    $st = ketNoi()->prepare($sql . ' ORDER BY s.ten_san');
    $st->execute($thamSo);
    return $st->fetchAll();
}

function sanLay(int $maSan): ?array
{
    $st = ketNoi()->prepare('SELECT * FROM san_thi_dau WHERE ma_san = ?');
    $st->execute([$maSan]);
    return $st->fetch() ?: null;
}

/** Lấy dữ liệu từ form: cắt khoảng trắng, ô số để trống thành NULL */
function sanChuanHoa(array $p): array
{
    $hoacNull = function ($v) {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : $v;
    };
    return [
        'ten_san'         => trim((string) ($p['ten_san'] ?? '')),
        'thanh_pho'       => trim((string) ($p['thanh_pho'] ?? '')),
        'tieu_bang'       => $hoacNull($p['tieu_bang'] ?? null),
        'suc_chua'        => $hoacNull($p['suc_chua'] ?? null),
        'nam_khanh_thanh' => $hoacNull($p['nam_khanh_thanh'] ?? null),
    ];
}

/** Trả về mảng lỗi [tên ô => thông báo]; rỗng = hợp lệ. Đổi ô số sang int khi hợp lệ. */
function sanKiemTra(array &$d): array
{
    $loi = [];
    if ($d['ten_san'] === '') {
        $loi['ten_san'] = 'Vui lòng nhập tên sân.';
    } elseif (mb_strlen($d['ten_san']) > 100) {
        $loi['ten_san'] = 'Tên sân tối đa 100 ký tự.';
    }
    if ($d['thanh_pho'] === '') {
        $loi['thanh_pho'] = 'Vui lòng nhập thành phố.';
    } elseif (mb_strlen($d['thanh_pho']) > 100) {
        $loi['thanh_pho'] = 'Thành phố tối đa 100 ký tự.';
    }
    if ($d['tieu_bang'] !== null && mb_strlen($d['tieu_bang']) > 50) {
        $loi['tieu_bang'] = 'Bang / tỉnh tối đa 50 ký tự.';
    }
    if ($d['suc_chua'] !== null) {
        if (!ctype_digit($d['suc_chua']) || (int) $d['suc_chua'] < 1 || (int) $d['suc_chua'] > 100000) {
            $loi['suc_chua'] = 'Sức chứa phải là số nguyên từ 1 đến 100.000.';
        } else {
            $d['suc_chua'] = (int) $d['suc_chua'];
        }
    }
    if ($d['nam_khanh_thanh'] !== null) {
        $namMax = (int) date('Y') + 5;
        if (!ctype_digit($d['nam_khanh_thanh']) || (int) $d['nam_khanh_thanh'] < 1901
            || (int) $d['nam_khanh_thanh'] > $namMax) {
            $loi['nam_khanh_thanh'] = "Năm khánh thành phải từ 1901 đến $namMax.";
        } else {
            $d['nam_khanh_thanh'] = (int) $d['nam_khanh_thanh'];
        }
    }
    return $loi;
}

function sanThem(array $d, ?string $anh): int
{
    $pdo = ketNoi();
    $st = $pdo->prepare(
        'INSERT INTO san_thi_dau (ten_san, thanh_pho, tieu_bang, suc_chua, nam_khanh_thanh, hinh_anh)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $st->execute([$d['ten_san'], $d['thanh_pho'], $d['tieu_bang'], $d['suc_chua'], $d['nam_khanh_thanh'], $anh]);
    return (int) $pdo->lastInsertId();
}

/** Sửa sân. $anhMoi = NULL nghĩa là giữ ảnh cũ. */
function sanSua(int $maSan, array $d, ?string $anhMoi): void
{
    $sql = 'UPDATE san_thi_dau SET ten_san = ?, thanh_pho = ?, tieu_bang = ?, suc_chua = ?, nam_khanh_thanh = ?';
    $thamSo = [$d['ten_san'], $d['thanh_pho'], $d['tieu_bang'], $d['suc_chua'], $d['nam_khanh_thanh']];
    if ($anhMoi !== null) {
        $sql .= ', hinh_anh = ?';
        $thamSo[] = $anhMoi;
    }
    $thamSo[] = $maSan;
    ketNoi()->prepare($sql . ' WHERE ma_san = ?')->execute($thamSo);
}

/**
 * Xóa sân. Trả về NULL nếu xóa được, hoặc câu thông báo lý do bị chặn.
 * CSDL đặt ON DELETE SET NULL nên nếu không chặn ở đây, đội và trận dùng sân này
 * sẽ âm thầm mất thông tin sân.
 */
function sanXoa(int $maSan): ?string
{
    $san = sanLay($maSan);
    if (!$san) {
        return 'Không tìm thấy sân.';
    }
    $pdo = ketNoi();
    $st = $pdo->prepare('SELECT COUNT(*) FROM doi_bong WHERE ma_san = ?');
    $st->execute([$maSan]);
    $soDoi = (int) $st->fetchColumn();
    $st = $pdo->prepare('SELECT COUNT(*) FROM tran_dau WHERE ma_san = ?');
    $st->execute([$maSan]);
    $soTran = (int) $st->fetchColumn();

    if ($soDoi > 0 || $soTran > 0) {
        $lyDo = [];
        if ($soDoi > 0) {
            $lyDo[] = "là sân nhà của $soDoi đội";
        }
        if ($soTran > 0) {
            $lyDo[] = "đã có $soTran trận đấu";
        }
        return 'Không thể xóa sân "' . $san['ten_san'] . '": sân ' . implode(' và ', $lyDo) . '.';
    }

    $pdo->prepare('DELETE FROM san_thi_dau WHERE ma_san = ?')->execute([$maSan]);
    tv2XoaAnh($san['hinh_anh'], THU_MUC_ANH_SAN);
    return null;
}
