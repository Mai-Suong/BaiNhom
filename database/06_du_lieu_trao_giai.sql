-- =====================================================================
--  File: database/06_du_lieu_trao_giai.sql  (TV3 - Nguyễn Đỗ Thảo Nguyên)
--  Dữ liệu mẫu: trao_giai - giải thưởng THẬT của NBA 3 mùa 2023-24, 2024-25, 2025-26
--  (đủ 8 giải mỗi mùa: MVP, FMVP, DPOY, ROY, SMOY, MIP, CPOY, COY).
--  Import SAU 04_du_lieu_tran_dau.sql (TV4), vì cần các mùa giải trong bảng mua_giai.
--  Mùa giải tìm theo TÊN ('2023-24'...) nên không phụ thuộc ma_mua_giai TV4 đặt.
--  Mùa nào chưa có trong mua_giai thì các dòng của mùa đó được bỏ qua (không báo lỗi).
--  ma_cau_thu theo 03_du_lieu_cau_thu.sql, ma_hlv theo 02_du_lieu_doi_bong.sql.
--  Nguồn: NBA Communications (pr.nba.com) - kết quả bình chọn giải thưởng các mùa.
-- =====================================================================

USE qlbongro;

INSERT INTO trao_giai (ma_mua_giai, ma_giai_thuong, ma_cau_thu, ma_hlv)
SELECT mg.ma_mua_giai, gt.ma_giai_thuong, t.ma_cau_thu, t.ma_hlv
  FROM (
        -- Mùa 2023-24 (Celtics vô địch)
        SELECT '2023-24' AS mua, 'MVP' AS giai, 57 AS ma_cau_thu, NULL AS ma_hlv  -- Nikola Jokić (DEN)
        UNION ALL SELECT '2023-24', 'FMVP',  10, NULL  -- Jaylen Brown (BOS)
        UNION ALL SELECT '2023-24', 'DPOY', 139, NULL  -- Rudy Gobert (MIN)
        UNION ALL SELECT '2023-24', 'ROY',  209, NULL  -- Victor Wembanyama (SAS)
        UNION ALL SELECT '2023-24', 'SMOY', 141, NULL  -- Naz Reid (MIN)
        UNION ALL SELECT '2023-24', 'MIP',  178, NULL  -- Tyrese Maxey (PHI)
        UNION ALL SELECT '2023-24', 'CPOY',  73, NULL  -- Stephen Curry (GSW)
        UNION ALL SELECT '2023-24', 'COY',  NULL, 21   -- Mark Daigneault (OKC, HLV)
        -- Mùa 2024-25 (Thunder vô địch)
        UNION ALL SELECT '2024-25', 'MVP',  161, NULL  -- Shai Gilgeous-Alexander (OKC)
        UNION ALL SELECT '2024-25', 'FMVP', 161, NULL  -- Shai Gilgeous-Alexander (OKC)
        UNION ALL SELECT '2024-25', 'DPOY',  43, NULL  -- Evan Mobley (CLE)
        UNION ALL SELECT '2024-25', 'ROY',  211, NULL  -- Stephon Castle (SAS)
        UNION ALL SELECT '2024-25', 'SMOY',  12, NULL  -- Payton Pritchard (BOS)
        UNION ALL SELECT '2024-25', 'MIP',    2, NULL  -- Dyson Daniels (ATL)
        UNION ALL SELECT '2024-25', 'CPOY', 153, NULL  -- Jalen Brunson (NYK)
        UNION ALL SELECT '2024-25', 'COY',  NULL, 6    -- Kenny Atkinson (CLE, HLV)
        -- Mùa 2025-26 (Knicks vô địch, thắng Spurs 4-1)
        UNION ALL SELECT '2025-26', 'MVP',  161, NULL  -- Shai Gilgeous-Alexander (OKC)
        UNION ALL SELECT '2025-26', 'FMVP', 153, NULL  -- Jalen Brunson (NYK)
        UNION ALL SELECT '2025-26', 'DPOY', 209, NULL  -- Victor Wembanyama (SAS)
        UNION ALL SELECT '2025-26', 'ROY',   51, NULL  -- Cooper Flagg (DAL)
        UNION ALL SELECT '2025-26', 'SMOY', 215, NULL  -- Keldon Johnson (SAS)
        UNION ALL SELECT '2025-26', 'MIP',    7, NULL  -- Nickeil Alexander-Walker (ATL)
        UNION ALL SELECT '2025-26', 'CPOY', 161, NULL  -- Shai Gilgeous-Alexander (OKC)
        UNION ALL SELECT '2025-26', 'COY',  NULL, 2    -- Joe Mazzulla (BOS, HLV)
       ) t
  JOIN mua_giai mg    ON mg.ten_mua_giai = t.mua
  JOIN giai_thuong gt ON gt.ten_viet_tat = t.giai;
