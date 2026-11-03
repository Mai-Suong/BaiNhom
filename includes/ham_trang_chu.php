<?php
/**
 * TRANG CHỦ (TV2) - dữ liệu cho index.php: trận sắp tới / kết quả, carousel nổi bật, mục Điểm nhấn.
 * Các trang chỉ gọi hàm ở đây, không viết SQL trực tiếp trong trang.
 *
 * Bảng dùng (chỉ đọc): tran_dau, doi_bong, san_thi_dau, cau_thu, huan_luyen_vien, mua_giai, hoi_nghi,
 *   chi_so_tran_dau, diem_hiep, trao_giai, giai_thuong, nhiem_ky_hlv, chuyen_nhuong, chan_thuong,
 *   view v_chi_so_trung_binh, v_bang_xep_hang
 *
 * Mỗi module của TV2 nằm trong 1 file riêng để các Pull Request độc lập, merge thứ tự nào cũng được.
 * Tên hàm có tiền tố riêng của từng module, không trùng hàm của TV3 (ham_cau_thu.php).
 */
require_once __DIR__ . '/tv2_chung.php';

/* =====================================================================
 * TRANG CHỦ: trận sắp diễn ra, kết quả mới nhất (tran_dau - TV4),
 * top cầu thủ (view v_chi_so_trung_binh - dữ liệu box score của TV5).
 * Cả 3 khung đều lấy theo mùa hiện tại (nhóm xem nhiều mùa giải).
 * Chưa có mùa / bảng trống thì trả về mảng rỗng, trang chủ tự hiện "Chưa có dữ liệu".
 * ===================================================================== */

/**
 * Các trận chưa đấu / hoãn của mùa $maMuaGiai, trận sớm nhất trước.
 * Không so với ngày hôm nay: dữ liệu mẫu là lịch của mùa đã qua,
 * nên "sắp diễn ra" = các trận chưa có kết quả trong mùa (giống trang chi tiết đội).
 */
function tcTranSapToi(?int $maMuaGiai, int $soTran = 5): array
{
    if ($maMuaGiai === null) {
        return [];
    }
    $soTran = max(1, $soTran);
    $st = ketNoi()->prepare(
        "SELECT td.ma_tran, td.thoi_gian, td.trang_thai, s.ten_san,
                dn.ma_doi AS ma_doi_nha, dn.ten_doi AS ten_doi_nha, dn.ten_viet_tat AS vt_nha, dn.logo AS logo_nha,
                dk.ma_doi AS ma_doi_khach, dk.ten_doi AS ten_doi_khach, dk.ten_viet_tat AS vt_khach, dk.logo AS logo_khach
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
           LEFT JOIN san_thi_dau s ON s.ma_san = td.ma_san
          WHERE td.ma_mua_giai = ? AND td.trang_thai IN ('chua_dau', 'hoan')
          ORDER BY td.thoi_gian, td.ma_tran
          LIMIT $soTran"
    );
    $st->execute([$maMuaGiai]);
    return $st->fetchAll();
}

/** Các trận đã đấu gần nhất của mùa $maMuaGiai */
function tcKetQuaMoiNhat(?int $maMuaGiai, int $soTran = 5): array
{
    if ($maMuaGiai === null) {
        return [];
    }
    $soTran = max(1, $soTran);
    $st = ketNoi()->prepare(
        "SELECT td.ma_tran, td.thoi_gian, td.diem_doi_nha, td.diem_doi_khach,
                dn.ma_doi AS ma_doi_nha, dn.ten_doi AS ten_doi_nha, dn.ten_viet_tat AS vt_nha, dn.logo AS logo_nha,
                dk.ma_doi AS ma_doi_khach, dk.ten_doi AS ten_doi_khach, dk.ten_viet_tat AS vt_khach, dk.logo AS logo_khach
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
          WHERE td.ma_mua_giai = ? AND td.trang_thai = 'da_dau'
          ORDER BY td.thoi_gian DESC, td.ma_tran DESC
          LIMIT $soTran"
    );
    $st->execute([$maMuaGiai]);
    return $st->fetchAll();
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

/* =====================================================================
 * CAROUSEL NỔI BẬT VÀ MỤC "ĐIỂM NHẤN" (trang chủ kiểu nba.com)
 * ===================================================================== */

/** Cầu thủ phải đá ít nhất tỉ lệ này so với người đá nhiều trận nhất mùa thì mới được xếp hạng (như NBA) */
const TC_TI_LE_TRAN_TOI_THIEU = 0.5;

/** Cột trung bình được phép xếp hạng (view v_chi_so_trung_binh) */
const TC_COT_CHI_SO = ['diem_tb', 'bat_bong_tb', 'kien_tao_tb', 'cuop_bong_tb', 'chan_bong_tb'];

/** Số trận tối thiểu để được xếp hạng ở mùa thường: 50% số trận của người đá nhiều nhất, ít nhất 1 */
function tcSoTranToiThieu(?int $maMuaGiai): int
{
    if ($maMuaGiai === null) {
        return 1;
    }
    $st = ketNoi()->prepare(
        "SELECT MAX(so_tran) FROM v_chi_so_trung_binh WHERE ma_mua_giai = ? AND giai_doan = 'thuong'"
    );
    $st->execute([$maMuaGiai]);
    return max(1, (int) ceil((int) $st->fetchColumn() * TC_TI_LE_TRAN_TOI_THIEU));
}

/**
 * Cầu thủ dẫn đầu một chỉ số trung bình ở mùa thường, CHỈ tính người đá đủ số trận tối thiểu
 * (tránh trường hợp đá 1 trận ghi 40 điểm đã đứng đầu). Cột 'gia_tri' = giá trị chỉ số $cot.
 */
function tcDanDau(?int $maMuaGiai, string $cot, int $soNguoi = 1): array
{
    if ($maMuaGiai === null || !in_array($cot, TC_COT_CHI_SO, true)) {
        return [];
    }
    $soNguoi = max(1, $soNguoi);
    $st = ketNoi()->prepare(
        "SELECT v.ma_cau_thu, v.so_tran, v.diem_tb, v.bat_bong_tb, v.kien_tao_tb, v.$cot AS gia_tri,
                ct.ho_ten, ct.anh, ct.vi_tri, ct.so_ao,
                d.ma_doi, d.ten_doi, d.ten_viet_tat, d.logo
           FROM v_chi_so_trung_binh v
           JOIN cau_thu ct      ON ct.ma_cau_thu = v.ma_cau_thu
           LEFT JOIN doi_bong d ON d.ma_doi = ct.ma_doi
          WHERE v.ma_mua_giai = ? AND v.giai_doan = 'thuong' AND v.so_tran >= ?
          ORDER BY v.$cot DESC, v.so_tran DESC, ct.ho_ten
          LIMIT $soNguoi"
    );
    $st->bindValue(1, $maMuaGiai, PDO::PARAM_INT);
    $st->bindValue(2, tcSoTranToiThieu($maMuaGiai), PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/**
 * Màn trình diễn ghi nhiều điểm nhất trong 1 trận của mùa (box score), mỗi cầu thủ chỉ lấy trận cao nhất.
 * Mỗi dòng có thêm: ti_so ("120-110" - đội của cầu thủ trước), thang (bool).
 */
function tcManTrinhDien(?int $maMuaGiai, int $soTran = 3): array
{
    if ($maMuaGiai === null) {
        return [];
    }
    $soTran = max(1, $soTran);
    $st = ketNoi()->prepare(
        "SELECT cs.diem, cs.bat_bong_tan_cong + cs.bat_bong_phong_thu AS bat_bong, cs.kien_tao, cs.ma_doi,
                td.ma_tran, td.thoi_gian, td.ma_doi_nha, td.diem_doi_nha, td.diem_doi_khach,
                ct.ma_cau_thu, ct.ho_ten, ct.anh, ct.vi_tri, ct.so_ao,
                d.ten_doi, d.ten_viet_tat, d.logo,
                dt.ten_doi AS ten_doi_thu, dt.ten_viet_tat AS vt_doi_thu, dt.logo AS logo_doi_thu
           FROM chi_so_tran_dau cs
           JOIN tran_dau td ON td.ma_tran = cs.ma_tran
           JOIN cau_thu ct  ON ct.ma_cau_thu = cs.ma_cau_thu
           JOIN doi_bong d  ON d.ma_doi = cs.ma_doi
           JOIN doi_bong dt ON dt.ma_doi = CASE WHEN td.ma_doi_nha = cs.ma_doi THEN td.ma_doi_khach ELSE td.ma_doi_nha END
          WHERE td.ma_mua_giai = ? AND td.trang_thai = 'da_dau' AND cs.trang_thai = 'thi_dau'
          ORDER BY cs.diem DESC, td.thoi_gian DESC, ct.ho_ten
          LIMIT " . ($soTran * 10)
    );
    $st->execute([$maMuaGiai]);
    $ds = [];
    foreach ($st->fetchAll() as $r) {   // giữ trận cao nhất của mỗi cầu thủ
        $ds[$r['ma_cau_thu']] ??= $r;
    }
    $ds = array_slice(array_values($ds), 0, $soTran);
    foreach ($ds as &$r) {
        $laNha = (int) $r['ma_doi_nha'] === (int) $r['ma_doi'];
        $diemMinh = (int) ($laNha ? $r['diem_doi_nha'] : $r['diem_doi_khach']);
        $diemBan  = (int) ($laNha ? $r['diem_doi_khach'] : $r['diem_doi_nha']);
        $r['ti_so'] = $diemMinh . '-' . $diemBan;
        $r['thang'] = $diemMinh > $diemBan;
    }
    return $ds;
}

/** Đội có tỉ lệ thắng cao nhất mùa (kèm hội nghị, sân, HLV trưởng, cầu thủ ghi điểm nhiều nhất của đội) */
function tcDoiDauBang(?int $maMuaGiai): ?array
{
    if ($maMuaGiai === null) {
        return null;
    }
    $pdo = ketNoi();
    $st = $pdo->prepare(
        "SELECT v.thang, v.thua, v.ti_le_thang, v.thang_san_nha, v.thua_san_nha, v.thang_san_khach, v.thua_san_khach,
                d.ma_doi, d.ten_doi, d.ten_viet_tat, d.thanh_pho, d.logo, hn.ten_hoi_nghi, s.ten_san,
                (SELECT h.ho_ten FROM huan_luyen_vien h
                  WHERE h.ma_doi = d.ma_doi AND h.vai_tro = 'truong' LIMIT 1) AS hlv_truong
           FROM v_bang_xep_hang v
           JOIN doi_bong d       ON d.ma_doi = v.ma_doi
           JOIN hoi_nghi hn      ON hn.ma_hoi_nghi = v.ma_hoi_nghi
           LEFT JOIN san_thi_dau s ON s.ma_san = d.ma_san
          WHERE v.ma_mua_giai = ? AND v.thang + v.thua > 0
          ORDER BY v.thang * 1.0 / (v.thang + v.thua) DESC, v.thang DESC, v.tong_diem_ghi - v.tong_diem_bi_ghi DESC, d.ten_doi
          LIMIT 1"
    );
    $st->execute([$maMuaGiai]);
    $doi = $st->fetch();
    if (!$doi) {
        return null;
    }
    $st = $pdo->prepare(
        "SELECT ct.ma_cau_thu, ct.ho_ten, ROUND(AVG(cs.diem), 1) AS diem_tb
           FROM chi_so_tran_dau cs
           JOIN tran_dau td ON td.ma_tran = cs.ma_tran
           JOIN cau_thu ct  ON ct.ma_cau_thu = cs.ma_cau_thu
          WHERE cs.ma_doi = ? AND td.ma_mua_giai = ? AND td.trang_thai = 'da_dau' AND cs.trang_thai = 'thi_dau'
          GROUP BY ct.ma_cau_thu, ct.ho_ten
          ORDER BY diem_tb DESC, ct.ho_ten
          LIMIT 1"
    );
    $st->execute([(int) $doi['ma_doi'], $maMuaGiai]);
    $doi['ngoi_sao'] = $st->fetch() ?: null;
    return $doi;
}

/**
 * Các giải thưởng của mùa liền trước mùa $mua (bảng trao_giai của TV3).
 * Trả về ['mua' => mùa trước, 'mvp' => dòng MVP (hoặc giải đầu tiên, kèm 'cac_giai'), 'khac' => giải của người khác]
 * hoặc NULL nếu chưa có.
 */
function tcVinhDanhMuaTruoc(?array $mua): ?array
{
    if ($mua === null) {
        return null;
    }
    $pdo = ketNoi();
    $st = $pdo->prepare('SELECT * FROM mua_giai WHERE ngay_bat_dau < ? ORDER BY ngay_bat_dau DESC LIMIT 1');
    $st->execute([$mua['ngay_bat_dau']]);
    $muaTruoc = $st->fetch();
    if (!$muaTruoc) {
        return null;
    }
    $st = $pdo->prepare(
        "SELECT gt.ten_viet_tat AS ma_giai, gt.ten_giai,
                ct.ma_cau_thu, COALESCE(ct.ho_ten, h.ho_ten) AS ho_ten, COALESCE(ct.anh, h.anh) AS anh,
                ct.vi_tri, ct.so_ao, h.ma_hlv, d.ten_doi, d.ten_viet_tat
           FROM trao_giai tg
           JOIN giai_thuong gt         ON gt.ma_giai_thuong = tg.ma_giai_thuong
           LEFT JOIN cau_thu ct        ON ct.ma_cau_thu = tg.ma_cau_thu
           LEFT JOIN huan_luyen_vien h ON h.ma_hlv = tg.ma_hlv
           LEFT JOIN doi_bong d        ON d.ma_doi = COALESCE(ct.ma_doi, h.ma_doi)
          WHERE tg.ma_mua_giai = ?
          ORDER BY gt.ma_giai_thuong"
    );
    $st->execute([(int) $muaTruoc['ma_mua_giai']]);
    $ds = $st->fetchAll();
    if (!$ds) {
        return null;
    }
    $mvp = $ds[0];
    foreach ($ds as $r) {
        if ($r['ma_giai'] === 'MVP') {
            $mvp = $r;
        }
    }
    // Giải khác của chính người này gộp vào nhãn (VD "MVP · FMVP"), 'khac' = giải của người khác
    $mvp['cac_giai'] = [];
    $khac = [];
    foreach ($ds as $r) {
        $cungNguoi = ($r['ma_cau_thu'] !== null && $r['ma_cau_thu'] === $mvp['ma_cau_thu'])
                  || ($r['ma_hlv'] !== null && $r['ma_hlv'] === $mvp['ma_hlv']);
        if ($cungNguoi) {
            $mvp['cac_giai'][] = $r['ma_giai'];
        } else {
            $khac[] = $r;
        }
    }
    return ['mua' => $muaTruoc, 'mvp' => $mvp, 'khac' => $khac];
}

/** Trận chưa đấu đáng xem nhất của mùa: tổng tỉ lệ thắng 2 đội cao nhất (kèm thành tích 2 đội) */
function tcTranTamDiem(?int $maMuaGiai): ?array
{
    if ($maMuaGiai === null) {
        return null;
    }
    $st = ketNoi()->prepare(
        "SELECT td.ma_tran, td.thoi_gian, s.ten_san,
                dn.ma_doi AS ma_doi_nha, dn.ten_doi AS ten_doi_nha, dn.ten_viet_tat AS vt_nha, dn.logo AS logo_nha,
                dk.ma_doi AS ma_doi_khach, dk.ten_doi AS ten_doi_khach, dk.ten_viet_tat AS vt_khach, dk.logo AS logo_khach,
                bn.thang AS thang_nha, bn.thua AS thua_nha, bk.thang AS thang_khach, bk.thua AS thua_khach
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
           LEFT JOIN san_thi_dau s      ON s.ma_san = td.ma_san
           LEFT JOIN v_bang_xep_hang bn ON bn.ma_doi = td.ma_doi_nha AND bn.ma_mua_giai = td.ma_mua_giai
           LEFT JOIN v_bang_xep_hang bk ON bk.ma_doi = td.ma_doi_khach AND bk.ma_mua_giai = td.ma_mua_giai
          WHERE td.ma_mua_giai = ? AND td.trang_thai = 'chua_dau'
          ORDER BY COALESCE(bn.thang * 1.0 / NULLIF(bn.thang + bn.thua, 0), 0)
                 + COALESCE(bk.thang * 1.0 / NULLIF(bk.thang + bk.thua, 0), 0) DESC, td.thoi_gian, td.ma_tran
          LIMIT 1"
    );
    $st->execute([$maMuaGiai]);
    return $st->fetch() ?: null;
}

/**
 * Các trận đã đấu của mùa cho mục Điểm nhấn. $kieu:
 *   'kich_tinh' = có hiệp phụ trước, rồi cách biệt ít nhất;  'thang_dam' = cách biệt lớn nhất.
 * Mỗi dòng có: cach_biet, so_hiep (số hiệp có điểm, > 4 là có hiệp phụ).
 */
function tcTranDiemNhan(?int $maMuaGiai, string $kieu, int $soTran = 2): array
{
    $thuTu = [
        'kich_tinh' => 'so_hiep DESC, cach_biet ASC, td.thoi_gian DESC',
        'thang_dam' => 'cach_biet DESC, td.thoi_gian DESC',
    ][$kieu] ?? null;
    if ($maMuaGiai === null || $thuTu === null) {
        return [];
    }
    $soTran = max(1, $soTran);
    $st = ketNoi()->prepare(
        "SELECT td.ma_tran, td.thoi_gian, td.diem_doi_nha, td.diem_doi_khach,
                ABS(td.diem_doi_nha - td.diem_doi_khach) AS cach_biet,
                COALESCE((SELECT MAX(dh.hiep) FROM diem_hiep dh WHERE dh.ma_tran = td.ma_tran), 4) AS so_hiep,
                dn.ma_doi AS ma_doi_nha, dn.ten_doi AS ten_doi_nha, dn.ten_viet_tat AS vt_nha, dn.logo AS logo_nha,
                dk.ma_doi AS ma_doi_khach, dk.ten_doi AS ten_doi_khach, dk.ten_viet_tat AS vt_khach, dk.logo AS logo_khach
           FROM tran_dau td
           JOIN doi_bong dn ON dn.ma_doi = td.ma_doi_nha
           JOIN doi_bong dk ON dk.ma_doi = td.ma_doi_khach
          WHERE td.ma_mua_giai = ? AND td.trang_thai = 'da_dau'
          ORDER BY $thuTu, td.ma_tran
          LIMIT $soTran"
    );
    $st->execute([$maMuaGiai]);
    return $st->fetchAll();
}

/**
 * Link tìm video highlight trận đấu trên YouTube (web không lưu / nhúng video có bản quyền).
 * VD "Golden State Warriors vs Indiana Pacers highlights Oct 28, 2025"
 */
function tcLinkHighlight(string $doiKhach, string $doiNha, string $thoiGian): string
{
    $tuKhoa = $doiKhach . ' vs ' . $doiNha . ' highlights ' . date('M j, Y', strtotime($thoiGian));
    return 'https://www.youtube.com/results?search_query=' . rawurlencode($tuKhoa);
}

/**
 * Top cầu thủ theo chỉ số hiệu quả EFF trung bình mỗi trận (mùa thường, đủ số trận tối thiểu):
 * EFF = điểm + bắt bóng + kiến tạo + cướp bóng + chặn bóng - ném trượt - ném phạt trượt - mất bóng.
 */
function tcTopHieuSuat(?int $maMuaGiai, int $soNguoi = 10): array
{
    if ($maMuaGiai === null) {
        return [];
    }
    $soNguoi = max(1, $soNguoi);
    $st = ketNoi()->prepare(
        "SELECT ct.ma_cau_thu, ct.ho_ten, ct.anh, ct.vi_tri, ct.so_ao, d.ten_doi, d.ten_viet_tat, d.logo,
                COUNT(*) AS so_tran,
                ROUND(AVG(cs.diem), 1) AS diem_tb,
                ROUND(AVG(cs.diem + cs.bat_bong_tan_cong + cs.bat_bong_phong_thu + cs.kien_tao + cs.cuop_bong
                          + cs.chan_bong - (cs.nem_thu - cs.nem_trung) - (cs.phat_thu - cs.phat_trung) - cs.mat_bong), 1) AS eff
           FROM chi_so_tran_dau cs
           JOIN tran_dau td     ON td.ma_tran = cs.ma_tran
           JOIN cau_thu ct      ON ct.ma_cau_thu = cs.ma_cau_thu
           LEFT JOIN doi_bong d ON d.ma_doi = ct.ma_doi
          WHERE td.ma_mua_giai = ? AND td.giai_doan = 'thuong' AND td.trang_thai = 'da_dau' AND cs.trang_thai = 'thi_dau'
          GROUP BY ct.ma_cau_thu, ct.ho_ten, ct.anh, ct.vi_tri, ct.so_ao, d.ten_doi, d.ten_viet_tat, d.logo
         HAVING COUNT(*) >= ?
          ORDER BY eff DESC, so_tran DESC, ct.ho_ten
          LIMIT $soNguoi"
    );
    $st->bindValue(1, $maMuaGiai, PDO::PARAM_INT);
    $st->bindValue(2, tcSoTranToiThieu($maMuaGiai), PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/**
 * Khoảnh khắc kịch tính nhất mùa: trận có hiệp phụ / cách biệt ít nhất (tcTranDiemNhan 'kich_tinh'),
 * kèm 'nguoi_hung' = cầu thủ ghi nhiều điểm nhất của đội thắng trong trận đó (NULL nếu chưa có box score).
 */
function tcKhoanhKhacKichTinh(?int $maMuaGiai): ?array
{
    $td = tcTranDiemNhan($maMuaGiai, 'kich_tinh', 1)[0] ?? null;
    if ($td === null) {
        return null;
    }
    $doiThang = (int) $td['diem_doi_nha'] > (int) $td['diem_doi_khach'] ? (int) $td['ma_doi_nha'] : (int) $td['ma_doi_khach'];
    $st = ketNoi()->prepare(
        "SELECT ct.ma_cau_thu, ct.ho_ten, ct.anh, ct.vi_tri, ct.so_ao, cs.diem,
                cs.bat_bong_tan_cong + cs.bat_bong_phong_thu AS bat_bong, cs.kien_tao
           FROM chi_so_tran_dau cs
           JOIN cau_thu ct ON ct.ma_cau_thu = cs.ma_cau_thu
          WHERE cs.ma_tran = ? AND cs.ma_doi = ? AND cs.trang_thai = 'thi_dau'
          ORDER BY cs.diem DESC, ct.ho_ten
          LIMIT 1"
    );
    $st->execute([(int) $td['ma_tran'], $doiThang]);
    $td['nguoi_hung'] = $st->fetch() ?: null;
    $td['vt_thang'] = $doiThang === (int) $td['ma_doi_nha'] ? $td['vt_nha'] : $td['vt_khach'];
    $td['ten_thang'] = $doiThang === (int) $td['ma_doi_nha'] ? $td['ten_doi_nha'] : $td['ten_doi_khach'];
    return $td;
}

/**
 * Bảng xếp hạng sức mạnh (Power Rankings) của mùa, tính từ các trận đã đấu:
 *   điểm sức mạnh = 100 x tỉ lệ thắng + hiệu số trung bình mỗi trận + 2 x (số trận thắng trong 5 trận gần nhất).
 * Mỗi đội có: thang, thua, hieu_so_tb, phong_do (chuỗi 'TTBTT' của 5 trận gần nhất, mới nhất ở cuối), diem_suc_manh.
 */
function tcSucManh(?int $maMuaGiai, int $soDoi = 5): array
{
    if ($maMuaGiai === null) {
        return [];
    }
    $st = ketNoi()->prepare(
        "SELECT td.ma_doi_nha, td.ma_doi_khach, td.diem_doi_nha, td.diem_doi_khach
           FROM tran_dau td
          WHERE td.ma_mua_giai = ? AND td.trang_thai = 'da_dau' AND td.giai_doan = 'thuong'
          ORDER BY td.thoi_gian, td.ma_tran"
    );
    $st->execute([$maMuaGiai]);
    $doi = [];
    foreach ($st->fetchAll() as $t) {
        foreach ([[$t['ma_doi_nha'], $t['diem_doi_nha'], $t['diem_doi_khach']],
                  [$t['ma_doi_khach'], $t['diem_doi_khach'], $t['diem_doi_nha']]] as [$ma, $ghi, $mat]) {
            $ma = (int) $ma;
            $doi[$ma] ??= ['thang' => 0, 'thua' => 0, 'hieu_so' => 0, 'ket_qua' => ''];
            $thang = (int) $ghi > (int) $mat;
            $doi[$ma][$thang ? 'thang' : 'thua']++;
            $doi[$ma]['hieu_so'] += (int) $ghi - (int) $mat;
            $doi[$ma]['ket_qua'] .= $thang ? 'T' : 'B';
        }
    }
    if (!$doi) {
        return [];
    }
    foreach ($doi as $ma => &$d) {
        $soTran = $d['thang'] + $d['thua'];
        $d['phong_do'] = substr($d['ket_qua'], -5);
        $d['hieu_so_tb'] = round($d['hieu_so'] / $soTran, 1);
        $d['diem_suc_manh'] = round(100 * $d['thang'] / $soTran + $d['hieu_so_tb'] + 2 * substr_count($d['phong_do'], 'T'), 1);
        $d['ma_doi'] = $ma;
    }
    unset($d);
    usort($doi, fn($a, $b) => [$b['diem_suc_manh'], $b['thang']] <=> [$a['diem_suc_manh'], $a['thang']]);
    $doi = array_slice($doi, 0, max(1, $soDoi));

    $st = ketNoi()->prepare('SELECT ma_doi, ten_doi, ten_viet_tat, logo FROM doi_bong WHERE ma_doi = ?');
    foreach ($doi as &$d) {
        $st->execute([$d['ma_doi']]);
        $d += $st->fetch() ?: ['ten_doi' => '?', 'ten_viet_tat' => '?', 'logo' => null];
    }
    unset($d);
    return $doi;
}

/** Nhãn loại chuyển nhượng (bảng chuyen_nhuong của TV3) */
const TC_LOAI_CHUYEN_NHUONG = [
    'trao_doi' => 'được trao đổi', 'ky_hop_dong' => 'ký hợp đồng', 'giai_phong' => 'bị giải phóng',
    'tuyen_chon_draft' => 'được chọn ở Draft',
];

/**
 * Tin tức tự sinh từ dữ liệu, mới nhất trước: chuyển nhượng (TV3), thay HLV giữa mùa (nhiem_ky_hlv),
 * chấn thương (TV3). Mỗi tin: ['ngay', 'loai' (chuyen_nhuong|hlv|chan_thuong), 'tieu_de', 'anh', 'ten', 'vt', 'link'].
 */
function tcTinTuc(int $soTin = 5): array
{
    $pdo = ketNoi();
    $tin = [];

    foreach ($pdo->query(
        "SELECT cn.ngay_chuyen, cn.loai, ct.ma_cau_thu, ct.ho_ten, ct.anh,
                dc.ten_doi AS doi_cu, dm.ten_doi AS doi_moi, dm.ten_viet_tat AS vt_moi, dc.ten_viet_tat AS vt_cu
           FROM chuyen_nhuong cn
           JOIN cau_thu ct       ON ct.ma_cau_thu = cn.ma_cau_thu
           LEFT JOIN doi_bong dc ON dc.ma_doi = cn.ma_doi_cu
           LEFT JOIN doi_bong dm ON dm.ma_doi = cn.ma_doi_moi
          ORDER BY cn.ngay_chuyen DESC, cn.ma_chuyen_nhuong DESC
          LIMIT $soTin"
    ) as $r) {
        $viec = TC_LOAI_CHUYEN_NHUONG[$r['loai']] ?? 'chuyển đội';
        $tieuDe = $r['ho_ten'] . ' ' . $viec
                . ($r['doi_moi'] ? ' với ' . $r['doi_moi'] : '')
                . ($r['doi_cu'] && $r['loai'] !== 'giai_phong' ? ' (rời ' . $r['doi_cu'] . ')' : '')
                . ($r['loai'] === 'giai_phong' && $r['doi_cu'] ? ' khỏi ' . $r['doi_cu'] : '');
        $tin[] = ['ngay' => $r['ngay_chuyen'], 'loai' => 'chuyen_nhuong', 'tieu_de' => $tieuDe, 'anh' => $r['anh'],
                  'ten' => $r['ho_ten'], 'vt' => $r['vt_moi'] ?? $r['vt_cu'], 'link' => url('/cau_thu/ho_so.php?id=' . (int) $r['ma_cau_thu'])];
    }

    foreach ($pdo->query(
        "SELECT nk.tu_ngay, nk.tam_quyen, h.ho_ten, h.anh, d.ma_doi, d.ten_doi, d.ten_viet_tat
           FROM nhiem_ky_hlv nk
           JOIN mua_giai mg        ON mg.ma_mua_giai = nk.ma_mua_giai
           JOIN huan_luyen_vien h  ON h.ma_hlv = nk.ma_hlv
           JOIN doi_bong d         ON d.ma_doi = nk.ma_doi
          WHERE nk.vai_tro = 'truong' AND nk.tu_ngay > mg.ngay_bat_dau
          ORDER BY nk.tu_ngay DESC
          LIMIT $soTin"
    ) as $r) {
        $tin[] = ['ngay' => $r['tu_ngay'], 'loai' => 'hlv',
                  'tieu_de' => $r['ten_doi'] . ' thay HLV: ' . $r['ho_ten'] . ((int) $r['tam_quyen'] === 1 ? ' tạm quyền dẫn dắt' : ' nắm quyền'),
                  'anh' => $r['anh'], 'ten' => $r['ho_ten'], 'vt' => $r['ten_viet_tat'],
                  'link' => url('/doi_bong/chi_tiet.php?id=' . (int) $r['ma_doi'])];
    }

    foreach ($pdo->query(
        "SELECT cht.ngay_bi, cht.loai_chan_thuong, ct.ma_cau_thu, ct.ho_ten, ct.anh, d.ten_viet_tat
           FROM chan_thuong cht
           JOIN cau_thu ct      ON ct.ma_cau_thu = cht.ma_cau_thu
           LEFT JOIN doi_bong d ON d.ma_doi = ct.ma_doi
          ORDER BY cht.ngay_bi DESC
          LIMIT $soTin"
    ) as $r) {
        $tin[] = ['ngay' => $r['ngay_bi'], 'loai' => 'chan_thuong',
                  'tieu_de' => $r['ho_ten'] . ' chấn thương: ' . mb_strtolower($r['loai_chan_thuong']),
                  'anh' => $r['anh'], 'ten' => $r['ho_ten'], 'vt' => $r['ten_viet_tat'],
                  'link' => url('/cau_thu/ho_so.php?id=' . (int) $r['ma_cau_thu'])];
    }

    usort($tin, fn($a, $b) => strcmp($b['ngay'], $a['ngay']));
    return array_slice($tin, 0, max(1, $soTin));
}

/** Link tìm video highlight của một cầu thủ trong 1 trận (VD "Josh Giddey 46 points vs Atlanta Hawks Oct 22, 2025") */
function tcLinkHighlightCauThu(string $hoTen, int $diem, string $doiThu, string $thoiGian): string
{
    $tuKhoa = $hoTen . ' ' . $diem . ' points vs ' . $doiThu . ' highlights ' . date('M j, Y', strtotime($thoiGian));
    return 'https://www.youtube.com/results?search_query=' . rawurlencode($tuKhoa);
}
