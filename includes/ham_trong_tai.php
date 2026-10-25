<?php
/**
 * MODULE TRỌNG TÀI (TV2) - hàm truy vấn và nghiệp vụ.
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng: trong_tai (ghi); phan_cong_trong_tai (chỉ đọc)
 *
 * Mỗi module của TV2 nằm trong 1 file riêng để các Pull Request độc lập, merge thứ tự nào cũng được.
 * Tên hàm có tiền tố riêng của từng module, không trùng hàm của TV3 (ham_cau_thu.php).
 */
require_once __DIR__ . '/../config/db.php';

/* =====================================================================
 * TRỌNG TÀI
 * ===================================================================== */

/** Danh sách trọng tài (tìm theo tên hoặc số áo), kèm số trận đã được phân công */
function ttLayDanhSach(string $tuKhoa = ''): array
{
    $sql = 'SELECT t.*,
                   (SELECT COUNT(*) FROM phan_cong_trong_tai pc WHERE pc.ma_trong_tai = t.ma_trong_tai) AS so_tran
              FROM trong_tai t';
    $thamSo = [];
    if ($tuKhoa !== '') {
        $sql .= ' WHERE t.ho_ten LIKE ? OR t.so_ao = ?';
        $thamSo = ['%' . $tuKhoa . '%', ctype_digit($tuKhoa) ? (int) $tuKhoa : -1];
    }
    $st = ketNoi()->prepare($sql . ' ORDER BY t.so_ao IS NULL, t.so_ao, t.ho_ten');
    $st->execute($thamSo);
    return $st->fetchAll();
}

function ttLay(int $maTrongTai): ?array
{
    $st = ketNoi()->prepare('SELECT * FROM trong_tai WHERE ma_trong_tai = ?');
    $st->execute([$maTrongTai]);
    return $st->fetch() ?: null;
}

function ttChuanHoa(array $p): array
{
    $hoacNull = function ($v) {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : $v;
    };
    return [
        'ho_ten'      => trim((string) ($p['ho_ten'] ?? '')),
        'so_ao'       => $hoacNull($p['so_ao'] ?? null),
        'nam_bat_dau' => $hoacNull($p['nam_bat_dau'] ?? null),
    ];
}

/**
 * Trả về mảng lỗi [tên ô => thông báo]; rỗng = hợp lệ. Đổi ô số sang int khi hợp lệ.
 * Số áo trọng tài không trùng nhau (kiểm tra bằng PHP, CSDL không ràng buộc).
 */
function ttKiemTra(array &$d, int $boQuaMa = 0): array
{
    $loi = [];
    if ($d['ho_ten'] === '') {
        $loi['ho_ten'] = 'Vui lòng nhập họ tên.';
    } elseif (mb_strlen($d['ho_ten']) > 100) {
        $loi['ho_ten'] = 'Họ tên tối đa 100 ký tự.';
    }

    if ($d['so_ao'] !== null) {
        if (!ctype_digit($d['so_ao']) || (int) $d['so_ao'] > 99) {
            $loi['so_ao'] = 'Số áo phải từ 0 đến 99.';
        } else {
            $d['so_ao'] = (int) $d['so_ao'];
            $st = ketNoi()->prepare('SELECT ho_ten FROM trong_tai WHERE so_ao = ? AND ma_trong_tai <> ?');
            $st->execute([$d['so_ao'], $boQuaMa]);
            if ($ten = $st->fetchColumn()) {
                $loi['so_ao'] = "Số áo {$d['so_ao']} đã thuộc về trọng tài $ten.";
            }
        }
    }

    if ($d['nam_bat_dau'] !== null) {
        $namNay = (int) date('Y');
        if (!ctype_digit($d['nam_bat_dau']) || (int) $d['nam_bat_dau'] < 1946 || (int) $d['nam_bat_dau'] > $namNay) {
            $loi['nam_bat_dau'] = "Năm bắt đầu phải từ 1946 đến $namNay.";
        } else {
            $d['nam_bat_dau'] = (int) $d['nam_bat_dau'];
        }
    }
    return $loi;
}

function ttThem(array $d): int
{
    $pdo = ketNoi();
    $pdo->prepare('INSERT INTO trong_tai (ho_ten, so_ao, nam_bat_dau) VALUES (?, ?, ?)')
        ->execute([$d['ho_ten'], $d['so_ao'], $d['nam_bat_dau']]);
    return (int) $pdo->lastInsertId();
}

function ttSua(int $maTrongTai, array $d): void
{
    ketNoi()->prepare('UPDATE trong_tai SET ho_ten = ?, so_ao = ?, nam_bat_dau = ? WHERE ma_trong_tai = ?')
        ->execute([$d['ho_ten'], $d['so_ao'], $d['nam_bat_dau'], $maTrongTai]);
}

/**
 * Xóa trọng tài. Trả về NULL nếu xóa được, hoặc câu thông báo lý do bị chặn.
 * Trọng tài đã được phân công trận (phan_cong_trong_tai, ON DELETE RESTRICT) thì không xóa được.
 */
function ttXoa(int $maTrongTai): ?string
{
    $tt = ttLay($maTrongTai);
    if (!$tt) {
        return 'Không tìm thấy trọng tài.';
    }
    $pdo = ketNoi();
    $st = $pdo->prepare('SELECT COUNT(*) FROM phan_cong_trong_tai WHERE ma_trong_tai = ?');
    $st->execute([$maTrongTai]);
    if ($soTran = (int) $st->fetchColumn()) {
        return "Không thể xóa trọng tài {$tt['ho_ten']}: đã được phân công $soTran trận.";
    }
    $pdo->prepare('DELETE FROM trong_tai WHERE ma_trong_tai = ?')->execute([$maTrongTai]);
    return null;
}
