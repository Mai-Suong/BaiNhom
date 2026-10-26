-- =====================================================================
--  File: database/06_du_lieu_trao_giai.sql  (TV3 - Nguyễn Đỗ Thảo Nguyên)
--  Dữ liệu mẫu: trao_giai - giải thưởng thật của NBA mùa 2024-25
--  Import CUỐI CÙNG (sau 04 của TV4), vì cần mùa giải '2024-25' trong bảng mua_giai.
--  Mùa giải tìm theo tên '2024-25' nên không phụ thuộc ma_mua_giai TV4 đặt.
--  Chưa có mùa '2024-25' thì file chạy không lỗi nhưng không thêm dòng nào.
--  ma_cau_thu theo 03_du_lieu_cau_thu.sql, ma_hlv theo 02_du_lieu_doi_bong.sql.
-- =====================================================================

USE qlbongro;

INSERT INTO trao_giai (ma_mua_giai, ma_giai_thuong, ma_cau_thu, ma_hlv)
SELECT mg.ma_mua_giai, gt.ma_giai_thuong, t.ma_cau_thu, t.ma_hlv
  FROM (SELECT 'MVP' AS giai, 161 AS ma_cau_thu, NULL AS ma_hlv  -- Shai Gilgeous-Alexander
        UNION ALL SELECT 'FMVP' AS giai, 161 AS ma_cau_thu, NULL AS ma_hlv  -- Shai Gilgeous-Alexander
        UNION ALL SELECT 'DPOY' AS giai, 43 AS ma_cau_thu, NULL AS ma_hlv  -- Evan Mobley
        UNION ALL SELECT 'ROY' AS giai, 211 AS ma_cau_thu, NULL AS ma_hlv  -- Stephon Castle
        UNION ALL SELECT 'SMOY' AS giai, 12 AS ma_cau_thu, NULL AS ma_hlv  -- Payton Pritchard
        UNION ALL SELECT 'MIP' AS giai, 2 AS ma_cau_thu, NULL AS ma_hlv  -- Dyson Daniels
        UNION ALL SELECT 'CPOY' AS giai, 153 AS ma_cau_thu, NULL AS ma_hlv  -- Jalen Brunson
        UNION ALL SELECT 'COY' AS giai, NULL AS ma_cau_thu, 6 AS ma_hlv  -- Kenny Atkinson (HLV)
       ) t
  JOIN mua_giai mg    ON mg.ten_mua_giai = '2024-25'
  JOIN giai_thuong gt ON gt.ten_viet_tat = t.giai;
