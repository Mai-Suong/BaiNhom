<?php
/**
 * TRANG CHỦ (TV2) - 3 khung dữ liệu của index.php.
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng: tran_dau, doi_bong, san_thi_dau, cau_thu, view v_chi_so_trung_binh (chỉ đọc)
 *
 * Mỗi module của TV2 nằm trong 1 file riêng để các Pull Request độc lập, merge thứ tự nào cũng được.
 * Tên hàm có tiền tố riêng của từng module, không trùng hàm của TV3 (ham_cau_thu.php).
 */
require_once __DIR__ . '/tv2_chung.php';

/* =====================================================================
 * TRANG CHỦ: trận sắp diễn ra, kết quả mới nhất (tran_dau - TV4),
 * top cầu thủ (view v_chi_so_trung_binh - dữ liệu box score của TV5).
 * Bảng trống thì trả về mảng rỗng, trang chủ tự hiện "Chưa có dữ liệu".
 * ===================================================================== */

/** Các trận chưa đấu / hoãn từ thời điểm hiện tại trở đi */
function tcTranSapToi(int $soTran = 5): array
{
    $soTran = max(1, $soTran);
    return ketNoi()->query(
        "SELECT td.ma_tran, td.thoi_gian, td.trang_thai, s.ten_san,
                dn.ma_doi AS ma_doi_nha, dn.ten_doi AS ten_doi_nha, dn.ten_viet_tat AS vt_nha, dn.logo AS logo_nha,
                dk.ma_doi AS ma_doi_khach, dk.ten_doi AS ten_doi_khach, dk.ten_viet_tat AS vt_khach, dk.logo AS logo_khach
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
           LEFT JOIN san_thi_dau s ON s.ma_san = td.ma_san
          WHERE td.trang_thai IN ('chua_dau', 'hoan') AND td.thoi_gian >= NOW()
          ORDER BY td.thoi_gian
          LIMIT $soTran"
    )->fetchAll();
}

/** Các trận đã đấu gần nhất */
function tcKetQuaMoiNhat(int $soTran = 5): array
{
    $soTran = max(1, $soTran);
    return ketNoi()->query(
        "SELECT td.ma_tran, td.thoi_gian, td.diem_doi_nha, td.diem_doi_khach,
                dn.ma_doi AS ma_doi_nha, dn.ten_doi AS ten_doi_nha, dn.ten_viet_tat AS vt_nha, dn.logo AS logo_nha,
                dk.ma_doi AS ma_doi_khach, dk.ten_doi AS ten_doi_khach, dk.ten_viet_tat AS vt_khach, dk.logo AS logo_khach
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
          WHERE td.trang_thai = 'da_dau'
          ORDER BY td.thoi_gian DESC
          LIMIT $soTran"
    )->fetchAll();
}

/** Top cầu thủ ghi điểm trung bình cao nhất ở mùa thường của mùa $maMuaGiai */
function tcTopCauThu(?int $maMuaGiai, int $soNguoi = 5): array
{
    if ($maMuaGiai === null) {
        return [];
    }
    $soNguoi = max(1, $soNguoi);
    $st = ketNoi()->prepare(
        "SELECT v.ma_cau_thu, v.so_tran, v.diem_tb, v.bat_bong_tb, v.kien_tao_tb,
                ct.ho_ten, ct.anh, d.ten_viet_tat
           FROM v_chi_so_trung_binh v
           JOIN cau_thu ct       ON ct.ma_cau_thu = v.ma_cau_thu
           LEFT JOIN doi_bong d  ON d.ma_doi = ct.ma_doi
          WHERE v.ma_mua_giai = ? AND v.giai_doan = 'thuong'
          ORDER BY v.diem_tb DESC, v.so_tran DESC
          LIMIT $soNguoi"
    );
    $st->execute([$maMuaGiai]);
    return $st->fetchAll();
}
