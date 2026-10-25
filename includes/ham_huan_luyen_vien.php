<?php
/**
 * MODULE HUẤN LUYỆN VIÊN (TV2) - hàm truy vấn và nghiệp vụ.
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng: huan_luyen_vien (ghi); doi_bong, trao_giai (chỉ đọc)
 *
 * Mỗi module của TV2 nằm trong 1 file riêng để các Pull Request độc lập, merge thứ tự nào cũng được.
 * Tên hàm có tiền tố riêng của từng module, không trùng hàm của TV3 (ham_cau_thu.php).
 */
require_once __DIR__ . '/tv2_chung.php';

/* =====================================================================
 * HUẤN LUYỆN VIÊN
 * ===================================================================== */

const THU_MUC_ANH_HLV = 'hlv';

/**
 * Danh sách HLV, tìm theo tên.
 * $locDoi: '' = tất cả, 'tu_do' = chưa có đội, số = mã đội.
 */
function hlvLayDanhSach(string $tuKhoa = '', string $locDoi = ''): array
{
    $sql = 'SELECT h.*, d.ten_doi, d.ten_viet_tat
              FROM huan_luyen_vien h
              LEFT JOIN doi_bong d ON d.ma_doi = h.ma_doi
             WHERE 1 = 1';
    $thamSo = [];
    if ($tuKhoa !== '') {
        $sql .= ' AND h.ho_ten LIKE ?';
        $thamSo[] = '%' . $tuKhoa . '%';
    }
    if ($locDoi === 'tu_do') {
        $sql .= ' AND h.ma_doi IS NULL';
    } elseif (ctype_digit($locDoi)) {
        $sql .= ' AND h.ma_doi = ?';
        $thamSo[] = (int) $locDoi;
    }
    $st = ketNoi()->prepare($sql . " ORDER BY d.ten_doi IS NULL, d.ten_doi, h.vai_tro = 'pho', h.ho_ten");
    $st->execute($thamSo);
    return $st->fetchAll();
}

/** Danh sách đội cho ô lọc / ô chọn đội, kèm tên HLV trưởng hiện tại */
function hlvLayDoiLuaChon(): array
{
    return ketNoi()->query(
        "SELECT d.ma_doi, d.ten_doi,
                (SELECT h.ho_ten FROM huan_luyen_vien h
                  WHERE h.ma_doi = d.ma_doi AND h.vai_tro = 'truong' LIMIT 1) AS hlv_truong
           FROM doi_bong d
          ORDER BY d.ten_doi"
    )->fetchAll();
}

function hlvLay(int $maHlv): ?array
{
    $st = ketNoi()->prepare('SELECT * FROM huan_luyen_vien WHERE ma_hlv = ?');
    $st->execute([$maHlv]);
    return $st->fetch() ?: null;
}

/** Lấy dữ liệu từ form: cắt khoảng trắng, ô tùy chọn để trống thành NULL */
function hlvChuanHoa(array $p): array
{
    $hoacNull = function ($v) {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : $v;
    };
    return [
        'ho_ten'       => trim((string) ($p['ho_ten'] ?? '')),
        'ngay_sinh'    => $hoacNull($p['ngay_sinh'] ?? null),
        'quoc_tich'    => $hoacNull($p['quoc_tich'] ?? null),
        'ma_doi'       => $hoacNull($p['ma_doi'] ?? null),
        'vai_tro'      => (string) ($p['vai_tro'] ?? ''),
        'ngay_bat_dau' => $hoacNull($p['ngay_bat_dau'] ?? null),
    ];
}

/**
 * Trả về mảng lỗi [tên ô => thông báo]; rỗng = hợp lệ.
 * Quy tắc "mỗi đội chỉ một HLV trưởng" kiểm tra ở đây (CSDL không ràng buộc).
 * $boQuaMa: mã HLV đang sửa (không báo trùng với chính mình).
 */
function hlvKiemTra(array &$d, int $boQuaMa = 0): array
{
    $loi = [];
    $homNay = date('Y-m-d');

    if ($d['ho_ten'] === '') {
        $loi['ho_ten'] = 'Vui lòng nhập họ tên.';
    } elseif (mb_strlen($d['ho_ten']) > 100) {
        $loi['ho_ten'] = 'Họ tên tối đa 100 ký tự.';
    }

    if ($d['ngay_sinh'] !== null && (!tv2NgayHopLe($d['ngay_sinh']) || $d['ngay_sinh'] >= $homNay)) {
        $loi['ngay_sinh'] = 'Ngày sinh không hợp lệ.';
    }

    if ($d['quoc_tich'] !== null && mb_strlen($d['quoc_tich']) > 50) {
        $loi['quoc_tich'] = 'Quốc tịch tối đa 50 ký tự.';
    }

    if (!isset(VAI_TRO_HLV[$d['vai_tro']])) {
        $loi['vai_tro'] = 'Vui lòng chọn vai trò.';
    }

    $doi = null;
    if ($d['ma_doi'] !== null) {
        $doi = null;
        if (ctype_digit($d['ma_doi'])) {
            $st = ketNoi()->prepare('SELECT ma_doi, ten_doi FROM doi_bong WHERE ma_doi = ?');
            $st->execute([(int) $d['ma_doi']]);
            $doi = $st->fetch() ?: null;
        }
        if (!$doi) {
            $loi['ma_doi'] = 'Đội không hợp lệ.';
        } else {
            $d['ma_doi'] = (int) $d['ma_doi'];
        }
    }

    if ($doi && $d['vai_tro'] === 'truong') {
        $st = ketNoi()->prepare(
            "SELECT ho_ten FROM huan_luyen_vien WHERE ma_doi = ? AND vai_tro = 'truong' AND ma_hlv <> ?"
        );
        $st->execute([$d['ma_doi'], $boQuaMa]);
        if ($ten = $st->fetchColumn()) {
            $loi['vai_tro'] = "{$doi['ten_doi']} đã có HLV trưởng $ten. Mỗi đội chỉ một HLV trưởng: "
                            . 'hãy chuyển người cũ sang HLV phó hoặc cho rời đội trước.';
        }
    }

    if ($d['ngay_bat_dau'] !== null) {
        if (!tv2NgayHopLe($d['ngay_bat_dau'])) {
            $loi['ngay_bat_dau'] = 'Ngày bắt đầu không hợp lệ.';
        } elseif ($d['ngay_sinh'] !== null && !isset($loi['ngay_sinh']) && $d['ngay_bat_dau'] <= $d['ngay_sinh']) {
            $loi['ngay_bat_dau'] = 'Ngày bắt đầu phải sau ngày sinh.';
        }
    }
    return $loi;
}

function hlvThem(array $d, ?string $anh): int
{
    $pdo = ketNoi();
    $st = $pdo->prepare(
        'INSERT INTO huan_luyen_vien (ho_ten, ngay_sinh, quoc_tich, ma_doi, vai_tro, ngay_bat_dau, anh)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute([$d['ho_ten'], $d['ngay_sinh'], $d['quoc_tich'], $d['ma_doi'], $d['vai_tro'], $d['ngay_bat_dau'], $anh]);
    return (int) $pdo->lastInsertId();
}

/** Sửa HLV. $anhMoi = NULL nghĩa là giữ ảnh cũ. */
function hlvSua(int $maHlv, array $d, ?string $anhMoi): void
{
    $sql = 'UPDATE huan_luyen_vien SET ho_ten = ?, ngay_sinh = ?, quoc_tich = ?, ma_doi = ?, vai_tro = ?, ngay_bat_dau = ?';
    $thamSo = [$d['ho_ten'], $d['ngay_sinh'], $d['quoc_tich'], $d['ma_doi'], $d['vai_tro'], $d['ngay_bat_dau']];
    if ($anhMoi !== null) {
        $sql .= ', anh = ?';
        $thamSo[] = $anhMoi;
    }
    $thamSo[] = $maHlv;
    ketNoi()->prepare($sql . ' WHERE ma_hlv = ?')->execute($thamSo);
}

/**
 * Xóa HLV. Trả về NULL nếu xóa được, hoặc câu thông báo lý do bị chặn.
 * HLV đã nhận giải (bảng trao_giai, ON DELETE RESTRICT) thì không xóa được.
 */
function hlvXoa(int $maHlv): ?string
{
    $hlv = hlvLay($maHlv);
    if (!$hlv) {
        return 'Không tìm thấy HLV.';
    }
    $pdo = ketNoi();
    $st = $pdo->prepare('SELECT COUNT(*) FROM trao_giai WHERE ma_hlv = ?');
    $st->execute([$maHlv]);
    if ($soGiai = (int) $st->fetchColumn()) {
        return "Không thể xóa HLV {$hlv['ho_ten']}: đã nhận $soGiai giải thưởng.";
    }
    $pdo->prepare('DELETE FROM huan_luyen_vien WHERE ma_hlv = ?')->execute([$maHlv]);
    tv2XoaAnh($hlv['anh'], THU_MUC_ANH_HLV);
    return null;
}
