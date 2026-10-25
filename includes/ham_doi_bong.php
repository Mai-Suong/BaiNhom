<?php
/**
 * MODULE ĐỘI BÓNG (TV2) - toàn bộ hàm truy vấn và nghiệp vụ.
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng: san_thi_dau, doi_bong, huan_luyen_vien, trong_tai, tran_dau, cau_thu (chỉ đọc)
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

/* =====================================================================
 * ĐỘI BÓNG
 * ===================================================================== */

const THU_MUC_LOGO = 'doi_bong';

/** Danh sách đội (tìm theo tên / tên viết tắt / thành phố), kèm sân nhà, số cầu thủ, HLV trưởng */
function dbLayDanhSach(string $tuKhoa = ''): array
{
    $sql = "SELECT d.*, s.ten_san,
                   (SELECT COUNT(*) FROM cau_thu ct WHERE ct.ma_doi = d.ma_doi) AS so_cau_thu,
                   (SELECT h.ho_ten FROM huan_luyen_vien h
                     WHERE h.ma_doi = d.ma_doi AND h.vai_tro = 'truong' LIMIT 1) AS hlv_truong
              FROM doi_bong d
              LEFT JOIN san_thi_dau s ON s.ma_san = d.ma_san";
    $thamSo = [];
    if ($tuKhoa !== '') {
        $sql .= ' WHERE d.ten_doi LIKE ? OR d.ten_viet_tat LIKE ? OR d.thanh_pho LIKE ?';
        $thamSo = array_fill(0, 3, '%' . $tuKhoa . '%');
    }
    $st = ketNoi()->prepare($sql . ' ORDER BY d.ten_doi');
    $st->execute($thamSo);
    return $st->fetchAll();
}

function dbLay(int $maDoi): ?array
{
    $st = ketNoi()->prepare('SELECT * FROM doi_bong WHERE ma_doi = ?');
    $st->execute([$maDoi]);
    return $st->fetch() ?: null;
}

/** Lấy dữ liệu từ form: cắt khoảng trắng, tên viết tắt viết hoa, ô tùy chọn để trống thành NULL */
function dbChuanHoa(array $p): array
{
    $hoacNull = function ($v) {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : $v;
    };
    return [
        'ten_doi'       => trim((string) ($p['ten_doi'] ?? '')),
        'ten_viet_tat'  => strtoupper(trim((string) ($p['ten_viet_tat'] ?? ''))),
        'thanh_pho'     => trim((string) ($p['thanh_pho'] ?? '')),
        'ma_san'        => $hoacNull($p['ma_san'] ?? null),
        'nam_thanh_lap' => $hoacNull($p['nam_thanh_lap'] ?? null),
        'mo_ta'         => $hoacNull($p['mo_ta'] ?? null),
    ];
}

/**
 * Trả về mảng lỗi [tên ô => thông báo]; rỗng = hợp lệ. Đổi ô số sang int khi hợp lệ.
 * $boQuaMa: mã đội đang sửa (không báo trùng với chính nó).
 */
function dbKiemTra(array &$d, int $boQuaMa = 0): array
{
    $loi = [];
    $pdo = ketNoi();

    if ($d['ten_doi'] === '') {
        $loi['ten_doi'] = 'Vui lòng nhập tên đội.';
    } elseif (mb_strlen($d['ten_doi']) > 100) {
        $loi['ten_doi'] = 'Tên đội tối đa 100 ký tự.';
    } else {
        $st = $pdo->prepare('SELECT 1 FROM doi_bong WHERE ten_doi = ? AND ma_doi <> ?');
        $st->execute([$d['ten_doi'], $boQuaMa]);
        if ($st->fetchColumn()) {
            $loi['ten_doi'] = 'Tên đội đã tồn tại.';
        }
    }

    if (!preg_match('/^[A-Z]{2,5}$/', $d['ten_viet_tat'])) {
        $loi['ten_viet_tat'] = 'Tên viết tắt gồm 2 đến 5 chữ cái (VD: NYK).';
    } else {
        $st = $pdo->prepare('SELECT ten_doi FROM doi_bong WHERE ten_viet_tat = ? AND ma_doi <> ?');
        $st->execute([$d['ten_viet_tat'], $boQuaMa]);
        if ($ten = $st->fetchColumn()) {
            $loi['ten_viet_tat'] = "Tên viết tắt đã được dùng cho $ten.";
        }
    }

    if ($d['thanh_pho'] === '') {
        $loi['thanh_pho'] = 'Vui lòng nhập thành phố.';
    } elseif (mb_strlen($d['thanh_pho']) > 100) {
        $loi['thanh_pho'] = 'Thành phố tối đa 100 ký tự.';
    }

    if ($d['ma_san'] !== null) {
        if (!ctype_digit($d['ma_san']) || !sanLay((int) $d['ma_san'])) {
            $loi['ma_san'] = 'Sân nhà không hợp lệ.';
        } else {
            $d['ma_san'] = (int) $d['ma_san'];
        }
    }

    if ($d['nam_thanh_lap'] !== null) {
        $namNay = (int) date('Y');
        if (!ctype_digit($d['nam_thanh_lap']) || (int) $d['nam_thanh_lap'] < 1901
            || (int) $d['nam_thanh_lap'] > $namNay) {
            $loi['nam_thanh_lap'] = "Năm thành lập phải từ 1901 đến $namNay.";
        } else {
            $d['nam_thanh_lap'] = (int) $d['nam_thanh_lap'];
        }
    }

    if ($d['mo_ta'] !== null && mb_strlen($d['mo_ta']) > 2000) {
        $loi['mo_ta'] = 'Mô tả tối đa 2000 ký tự.';
    }
    return $loi;
}

function dbThem(array $d, ?string $logo): int
{
    $pdo = ketNoi();
    $st = $pdo->prepare(
        'INSERT INTO doi_bong (ten_doi, ten_viet_tat, thanh_pho, ma_san, nam_thanh_lap, logo, mo_ta)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute([$d['ten_doi'], $d['ten_viet_tat'], $d['thanh_pho'], $d['ma_san'],
                  $d['nam_thanh_lap'], $logo, $d['mo_ta']]);
    return (int) $pdo->lastInsertId();
}

/** Sửa đội. $logoMoi = NULL nghĩa là giữ logo cũ. */
function dbSua(int $maDoi, array $d, ?string $logoMoi): void
{
    $sql = 'UPDATE doi_bong SET ten_doi = ?, ten_viet_tat = ?, thanh_pho = ?, ma_san = ?, nam_thanh_lap = ?, mo_ta = ?';
    $thamSo = [$d['ten_doi'], $d['ten_viet_tat'], $d['thanh_pho'], $d['ma_san'], $d['nam_thanh_lap'], $d['mo_ta']];
    if ($logoMoi !== null) {
        $sql .= ', logo = ?';
        $thamSo[] = $logoMoi;
    }
    $thamSo[] = $maDoi;
    ketNoi()->prepare($sql . ' WHERE ma_doi = ?')->execute($thamSo);
}

/**
 * Xóa đội. Trả về NULL nếu xóa được, hoặc câu thông báo lý do bị chặn.
 * - Chặn khi đội còn cầu thủ: CSDL đặt ON DELETE SET NULL nên không tự chặn.
 * - Chặn khi đội đã có trận đấu.
 * - Các ràng buộc RESTRICT khác (phân bảng, playoff, box score) bắt qua PDOException.
 * HLV của đội tự chuyển về "tự do" (CSDL đặt ON DELETE SET NULL).
 */
function dbXoa(int $maDoi): ?string
{
    $doi = dbLay($maDoi);
    if (!$doi) {
        return 'Không tìm thấy đội.';
    }
    $pdo = ketNoi();
    $st = $pdo->prepare('SELECT COUNT(*) FROM cau_thu WHERE ma_doi = ?');
    $st->execute([$maDoi]);
    $soCauThu = (int) $st->fetchColumn();
    $st = $pdo->prepare('SELECT COUNT(*) FROM tran_dau WHERE ma_doi_nha = ? OR ma_doi_khach = ?');
    $st->execute([$maDoi, $maDoi]);
    $soTran = (int) $st->fetchColumn();

    if ($soCauThu > 0 || $soTran > 0) {
        $lyDo = [];
        if ($soCauThu > 0) {
            $lyDo[] = "còn $soCauThu cầu thủ";
        }
        if ($soTran > 0) {
            $lyDo[] = "đã có $soTran trận đấu";
        }
        return 'Không thể xóa đội ' . $doi['ten_doi'] . ': đội ' . implode(' và ', $lyDo) . '.';
    }

    try {
        $pdo->prepare('DELETE FROM doi_bong WHERE ma_doi = ?')->execute([$maDoi]);
    } catch (PDOException $ex) {
        if ($ex->getCode() === '23000') {
            return 'Không thể xóa đội ' . $doi['ten_doi']
                 . ': đội đang được dùng ở dữ liệu khác (phân bảng, playoff hoặc thống kê).';
        }
        throw $ex;
    }
    tv2XoaAnh($doi['logo'], THU_MUC_LOGO);
    return null;
}
