<?php
/**
 * MODULE ĐỘI BÓNG (TV2) - hàm truy vấn và nghiệp vụ trang quản trị đội.
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng: doi_bong (ghi); san_thi_dau, cau_thu, huan_luyen_vien, tran_dau (chỉ đọc)
 *
 * Mỗi module của TV2 nằm trong 1 file riêng để các Pull Request độc lập, merge thứ tự nào cũng được.
 * Tên hàm có tiền tố riêng của từng module, không trùng hàm của TV3 (ham_cau_thu.php).
 * (ham_cau_thu.php đã có layDanhSachDoi(), doiTonTai() nên ở đây dùng tiền tố db...)
 */
require_once __DIR__ . '/tv2_chung.php';

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

/** Danh sách sân cho ô chọn "Sân nhà" ở form đội */
function dbLaySanLuaChon(): array
{
    return ketNoi()->query('SELECT ma_san, ten_san, thanh_pho FROM san_thi_dau ORDER BY ten_san')->fetchAll();
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
        $sanTonTai = false;
        if (ctype_digit($d['ma_san'])) {
            $st = $pdo->prepare('SELECT 1 FROM san_thi_dau WHERE ma_san = ?');
            $st->execute([(int) $d['ma_san']]);
            $sanTonTai = (bool) $st->fetchColumn();
        }
        if (!$sanTonTai) {
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
