<?php
/**
 * TRAO GIẢI (TV3) - bảng trao_giai
 *
 * Quy tắc:
 *  - Mỗi giải chỉ trao cho 1 người trong 1 mùa (CSDL chặn bằng uq_mua_giai_thuong).
 *    Trao lại cho người khác = thay người nhận.
 *  - Giải có doi_tuong = 'cau_thu' chỉ trao cho cầu thủ, 'hlv' chỉ trao cho HLV.
 *    CSDL KHÔNG tự kiểm tra điều này nên PHP phải kiểm tra.
 *  - Dữ liệu dùng chung: mua_giai (TV4), giai_thuong (có sẵn), huan_luyen_vien (TV2).
 */
require_once __DIR__ . '/ham_cau_thu.php';

/* ---------- Danh mục ---------- */

function layDanhSachMuaGiai(): array
{
    return ketNoi()->query(
        'SELECT ma_mua_giai, ten_mua_giai, la_mua_hien_tai
           FROM mua_giai ORDER BY ngay_bat_dau DESC'
    )->fetchAll();
}

/** Mùa mặc định khi mở trang: mùa hiện tại, không có thì mùa mới nhất */
function layMuaGiaiMacDinh(array $dsMua): ?int
{
    foreach ($dsMua as $m) {
        if ((int) $m['la_mua_hien_tai'] === 1) {
            return (int) $m['ma_mua_giai'];
        }
    }
    return $dsMua ? (int) $dsMua[0]['ma_mua_giai'] : null;
}

function layDanhSachGiaiThuong(): array
{
    return ketNoi()->query(
        "SELECT * FROM giai_thuong
          ORDER BY FIELD(ten_viet_tat, 'MVP','FMVP','DPOY','ROY','SMOY','MIP','CPOY','COY'), ma_giai_thuong"
    )->fetchAll();
}

/** Cầu thủ để chọn trao giải (bỏ qua cầu thủ đã giải nghệ) */
function layCauThuDeTraoGiai(): array
{
    return ketNoi()->query(
        "SELECT ct.ma_cau_thu, ct.ho_ten, ct.vi_tri, d.ten_doi
           FROM cau_thu ct
           LEFT JOIN doi_bong d ON d.ma_doi = ct.ma_doi
          WHERE ct.trang_thai <> 'giai_nghe'
          ORDER BY d.ten_doi IS NULL, d.ten_doi, ct.ho_ten"
    )->fetchAll();
}

function layHLVDeTraoGiai(): array
{
    return ketNoi()->query(
        'SELECT h.ma_hlv, h.ho_ten, h.vai_tro, d.ten_doi
           FROM huan_luyen_vien h
           LEFT JOIN doi_bong d ON d.ma_doi = h.ma_doi
          ORDER BY d.ten_doi IS NULL, d.ten_doi, h.ho_ten'
    )->fetchAll();
}

/* ---------- Đọc kết quả trao giải ---------- */

/** Tất cả giải thưởng của 1 mùa, kèm người nhận (NULL nếu chưa trao) */
function layKetQuaTraoGiai(int $maMuaGiai): array
{
    $st = ketNoi()->prepare(
        "SELECT gt.ma_giai_thuong, gt.ten_viet_tat, gt.ten_giai, gt.doi_tuong,
                tg.ma_trao_giai, tg.ma_cau_thu, tg.ma_hlv,
                COALESCE(ct.ho_ten, h.ho_ten) AS nguoi_nhan,
                COALESCE(dct.ten_doi, dh.ten_doi) AS ten_doi
           FROM giai_thuong gt
           LEFT JOIN trao_giai tg
                  ON tg.ma_giai_thuong = gt.ma_giai_thuong AND tg.ma_mua_giai = ?
           LEFT JOIN cau_thu ct          ON ct.ma_cau_thu = tg.ma_cau_thu
           LEFT JOIN doi_bong dct        ON dct.ma_doi = ct.ma_doi
           LEFT JOIN huan_luyen_vien h   ON h.ma_hlv = tg.ma_hlv
           LEFT JOIN doi_bong dh         ON dh.ma_doi = h.ma_doi
          ORDER BY FIELD(gt.ten_viet_tat, 'MVP','FMVP','DPOY','ROY','SMOY','MIP','CPOY','COY'),
                   gt.ma_giai_thuong"
    );
    $st->execute([$maMuaGiai]);
    return $st->fetchAll();
}

/** Danh hiệu của 1 cầu thủ (dùng cho hồ sơ cầu thủ và trang "Của tôi") */
function layDanhHieuCauThu(int $maCauThu): array
{
    $st = ketNoi()->prepare(
        'SELECT gt.ten_viet_tat, gt.ten_giai, mg.ten_mua_giai
           FROM trao_giai tg
           JOIN giai_thuong gt ON gt.ma_giai_thuong = tg.ma_giai_thuong
           JOIN mua_giai mg    ON mg.ma_mua_giai = tg.ma_mua_giai
          WHERE tg.ma_cau_thu = ?
          ORDER BY mg.ngay_bat_dau DESC, gt.ten_viet_tat'
    );
    $st->execute([$maCauThu]);
    return $st->fetchAll();
}

/** Danh hiệu của 1 HLV (TV2 có thể dùng cho trang chi tiết HLV / đội) */
function layDanhHieuHLV(int $maHlv): array
{
    $st = ketNoi()->prepare(
        'SELECT gt.ten_viet_tat, gt.ten_giai, mg.ten_mua_giai
           FROM trao_giai tg
           JOIN giai_thuong gt ON gt.ma_giai_thuong = tg.ma_giai_thuong
           JOIN mua_giai mg    ON mg.ma_mua_giai = tg.ma_mua_giai
          WHERE tg.ma_hlv = ?
          ORDER BY mg.ngay_bat_dau DESC'
    );
    $st->execute([$maHlv]);
    return $st->fetchAll();
}

/* ---------- Ghi ---------- */

/**
 * Trao giải (hoặc thay người nhận nếu giải mùa đó đã trao).
 * Ném RuntimeException kèm thông báo dễ hiểu nếu dữ liệu không hợp lệ.
 */
function traoGiai(int $maMuaGiai, int $maGiaiThuong, int $maNguoiNhan): void
{
    $pdo = ketNoi();

    $st = $pdo->prepare('SELECT ten_mua_giai FROM mua_giai WHERE ma_mua_giai = ?');
    $st->execute([$maMuaGiai]);
    if (!$st->fetchColumn()) {
        throw new RuntimeException('Mùa giải không tồn tại.');
    }

    $st = $pdo->prepare('SELECT * FROM giai_thuong WHERE ma_giai_thuong = ?');
    $st->execute([$maGiaiThuong]);
    $giai = $st->fetch();
    if (!$giai) {
        throw new RuntimeException('Giải thưởng không tồn tại.');
    }

    if ($maNguoiNhan <= 0) {
        throw new RuntimeException(
            $giai['doi_tuong'] === 'hlv' ? 'Vui lòng chọn huấn luyện viên.' : 'Vui lòng chọn cầu thủ.'
        );
    }

    if ($giai['doi_tuong'] === 'hlv') {
        $st = $pdo->prepare('SELECT 1 FROM huan_luyen_vien WHERE ma_hlv = ?');
        $st->execute([$maNguoiNhan]);
        if (!$st->fetchColumn()) {
            throw new RuntimeException("Giải {$giai['ten_viet_tat']} chỉ trao cho huấn luyện viên.");
        }
        $maCauThu = null;
        $maHlv    = $maNguoiNhan;
    } else {
        $st = $pdo->prepare('SELECT 1 FROM cau_thu WHERE ma_cau_thu = ?');
        $st->execute([$maNguoiNhan]);
        if (!$st->fetchColumn()) {
            throw new RuntimeException("Giải {$giai['ten_viet_tat']} chỉ trao cho cầu thủ.");
        }
        $maCauThu = $maNguoiNhan;
        $maHlv    = null;
    }

    // Đã trao giải này trong mùa -> thay người nhận; chưa trao -> thêm mới
    $pdo->prepare(
        'INSERT INTO trao_giai (ma_mua_giai, ma_giai_thuong, ma_cau_thu, ma_hlv)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE ma_cau_thu = VALUES(ma_cau_thu), ma_hlv = VALUES(ma_hlv)'
    )->execute([$maMuaGiai, $maGiaiThuong, $maCauThu, $maHlv]);
}

function huyTraoGiai(int $maMuaGiai, int $maGiaiThuong): void
{
    ketNoi()->prepare('DELETE FROM trao_giai WHERE ma_mua_giai = ? AND ma_giai_thuong = ?')
        ->execute([$maMuaGiai, $maGiaiThuong]);
}
