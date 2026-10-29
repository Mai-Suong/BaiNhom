-- =====================================================================
--  File: database/07_du_lieu_nhiem_ky_hlv.sql  (TV2 - Đặng Ngọc Nhật Duy)
--  Dữ liệu mẫu: nhiem_ky_hlv - HLV trưởng THẬT của 30 đội trong 3 mùa 2023-24, 2024-25, 2025-26,
--  kể cả các lần thay HLV giữa mùa và HLV tạm quyền.
--  Import SAU 04_du_lieu_tran_dau.sql (cần bảng mua_giai) - xếp sau cùng: ... -> 06 -> 07.
--  Mùa giải tìm theo TÊN ('2023-24'...) nên không phụ thuộc ma_mua_giai TV4 đặt.
--  Mùa nào chưa có trong mua_giai thì các dòng của mùa đó được bỏ qua (không báo lỗi).
--  ma_hlv, ma_doi theo 02_du_lieu_doi_bong.sql.
--  tu_ngay: ngày khai mạc mùa (24/10/2023, 22/10/2024, 21/10/2025) hoặc ngày bắt đầu chỉ đạo;
--  den_ngay: NULL = đến hết mùa. Hai nhiệm kỳ HLV trưởng cùng đội không trùng ngày nhau.
--  Nguồn: Wikipedia (2024-25 NBA season, 2025-26 NBA season), HoopsRumors (2023 coaching tracker),
--         báo chí ngày thay HLV (Bucks 23/01/2024, Wizards 25/01/2024, Nets 19/02/2024).
--  Thay HLV giữa mùa:
--    2023-24  BRK Vaughn -> Ollie (tạm quyền) | MIL Griffin -> Prunty (tạm quyền) -> Rivers
--             WAS Unseld Jr. -> Keefe (tạm quyền)
--    2024-25  SAS Popovich -> M. Johnson (tạm quyền) | SAC M. Brown -> Christie (tạm quyền)
--             MEM Jenkins -> Iisalo (tạm quyền)     | DEN Malone -> Adelman (tạm quyền)
--    2025-26  POR Billups -> Splitter (tạm quyền)   | NOP Green -> Borrego (tạm quyền)
-- =====================================================================
USE qlbongro;
SET NAMES utf8mb4;

INSERT INTO nhiem_ky_hlv (ma_hlv, ma_doi, ma_mua_giai, vai_tro, tam_quyen, tu_ngay, den_ngay)
SELECT t.ma_hlv, t.ma_doi, mg.ma_mua_giai, 'truong', t.tam_quyen, t.tu_ngay, t.den_ngay
  FROM (
        SELECT  1 AS ma_hlv,  1 AS ma_doi, '2023-24' AS mua, 0 AS tam_quyen, '2023-10-24' AS tu_ngay, CAST(NULL AS DATE) AS den_ngay  -- ATL Quin Snyder
        UNION ALL SELECT  2,  2, '2023-24', 0, '2023-10-24', NULL  -- BOS Joe Mazzulla
        UNION ALL SELECT 42,  3, '2023-24', 0, '2023-10-24', '2024-02-19'  -- BRK Jacque Vaughn
        UNION ALL SELECT 43,  3, '2023-24', 1, '2024-02-20', NULL  -- BRK Kevin Ollie (tạm quyền)
        UNION ALL SELECT 41,  4, '2023-24', 0, '2023-10-24', NULL  -- CHO Steve Clifford
        UNION ALL SELECT  5,  5, '2023-24', 0, '2023-10-24', NULL  -- CHI Billy Donovan
        UNION ALL SELECT  9,  6, '2023-24', 0, '2023-10-24', NULL  -- CLE J. B. Bickerstaff
        UNION ALL SELECT  7,  7, '2023-24', 0, '2023-10-24', NULL  -- DAL Jason Kidd
        UNION ALL SELECT 37,  8, '2023-24', 0, '2023-10-24', NULL  -- DEN Michael Malone
        UNION ALL SELECT 39,  9, '2023-24', 0, '2023-10-24', NULL  -- DET Monty Williams
        UNION ALL SELECT 10, 10, '2023-24', 0, '2023-10-24', NULL  -- GSW Steve Kerr
        UNION ALL SELECT 11, 11, '2023-24', 0, '2023-10-24', NULL  -- HOU Ime Udoka
        UNION ALL SELECT 12, 12, '2023-24', 0, '2023-10-24', NULL  -- IND Rick Carlisle
        UNION ALL SELECT 13, 13, '2023-24', 0, '2023-10-24', NULL  -- LAC Tyronn Lue
        UNION ALL SELECT 40, 14, '2023-24', 0, '2023-10-24', NULL  -- LAL Darvin Ham
        UNION ALL SELECT 31, 15, '2023-24', 0, '2023-10-24', NULL  -- MEM Taylor Jenkins
        UNION ALL SELECT 16, 16, '2023-24', 0, '2023-10-24', NULL  -- MIA Erik Spoelstra
        UNION ALL SELECT 44, 17, '2023-24', 0, '2023-10-24', '2024-01-23'  -- MIL Adrian Griffin
        UNION ALL SELECT 45, 17, '2023-24', 1, '2024-01-24', '2024-01-28'  -- MIL Joe Prunty (tạm quyền)
        UNION ALL SELECT 17, 17, '2023-24', 0, '2024-01-29', NULL  -- MIL Doc Rivers
        UNION ALL SELECT 18, 18, '2023-24', 0, '2023-10-24', NULL  -- MIN Chris Finch
        UNION ALL SELECT 32, 19, '2023-24', 0, '2023-10-24', NULL  -- NOP Willie Green
        UNION ALL SELECT 34, 20, '2023-24', 0, '2023-10-24', NULL  -- NYK Tom Thibodeau
        UNION ALL SELECT 21, 21, '2023-24', 0, '2023-10-24', NULL  -- OKC Mark Daigneault
        UNION ALL SELECT 22, 22, '2023-24', 0, '2023-10-24', NULL  -- ORL Jamahl Mosley
        UNION ALL SELECT 23, 23, '2023-24', 0, '2023-10-24', NULL  -- PHI Nick Nurse
        UNION ALL SELECT 36, 24, '2023-24', 0, '2023-10-24', NULL  -- PHO Frank Vogel
        UNION ALL SELECT 33, 25, '2023-24', 0, '2023-10-24', NULL  -- POR Chauncey Billups
        UNION ALL SELECT 20, 26, '2023-24', 0, '2023-10-24', NULL  -- SAC Mike Brown
        UNION ALL SELECT 38, 27, '2023-24', 0, '2023-10-24', NULL  -- SAS Gregg Popovich
        UNION ALL SELECT 28, 28, '2023-24', 0, '2023-10-24', NULL  -- TOR Darko Rajaković
        UNION ALL SELECT 29, 29, '2023-24', 0, '2023-10-24', NULL  -- UTA Will Hardy
        UNION ALL SELECT 46, 30, '2023-24', 0, '2023-10-24', '2024-01-24'  -- WAS Wes Unseld Jr.
        UNION ALL SELECT 30, 30, '2023-24', 1, '2024-01-25', NULL  -- WAS Brian Keefe (tạm quyền)
        UNION ALL SELECT  1,  1, '2024-25', 0, '2024-10-22', NULL  -- ATL Quin Snyder
        UNION ALL SELECT  2,  2, '2024-25', 0, '2024-10-22', NULL  -- BOS Joe Mazzulla
        UNION ALL SELECT  3,  3, '2024-25', 0, '2024-10-22', NULL  -- BRK Jordi Fernández
        UNION ALL SELECT  4,  4, '2024-25', 0, '2024-10-22', NULL  -- CHO Charles Lee
        UNION ALL SELECT  5,  5, '2024-25', 0, '2024-10-22', NULL  -- CHI Billy Donovan
        UNION ALL SELECT  6,  6, '2024-25', 0, '2024-10-22', NULL  -- CLE Kenny Atkinson
        UNION ALL SELECT  7,  7, '2024-25', 0, '2024-10-22', NULL  -- DAL Jason Kidd
        UNION ALL SELECT 37,  8, '2024-25', 0, '2024-10-22', '2025-04-07'  -- DEN Michael Malone
        UNION ALL SELECT  8,  8, '2024-25', 1, '2025-04-08', NULL  -- DEN David Adelman (tạm quyền)
        UNION ALL SELECT  9,  9, '2024-25', 0, '2024-10-22', NULL  -- DET J. B. Bickerstaff
        UNION ALL SELECT 10, 10, '2024-25', 0, '2024-10-22', NULL  -- GSW Steve Kerr
        UNION ALL SELECT 11, 11, '2024-25', 0, '2024-10-22', NULL  -- HOU Ime Udoka
        UNION ALL SELECT 12, 12, '2024-25', 0, '2024-10-22', NULL  -- IND Rick Carlisle
        UNION ALL SELECT 13, 13, '2024-25', 0, '2024-10-22', NULL  -- LAC Tyronn Lue
        UNION ALL SELECT 14, 14, '2024-25', 0, '2024-10-22', NULL  -- LAL JJ Redick
        UNION ALL SELECT 31, 15, '2024-25', 0, '2024-10-22', '2025-03-27'  -- MEM Taylor Jenkins
        UNION ALL SELECT 15, 15, '2024-25', 1, '2025-03-28', NULL  -- MEM Tuomas Iisalo (tạm quyền)
        UNION ALL SELECT 16, 16, '2024-25', 0, '2024-10-22', NULL  -- MIA Erik Spoelstra
        UNION ALL SELECT 17, 17, '2024-25', 0, '2024-10-22', NULL  -- MIL Doc Rivers
        UNION ALL SELECT 18, 18, '2024-25', 0, '2024-10-22', NULL  -- MIN Chris Finch
        UNION ALL SELECT 32, 19, '2024-25', 0, '2024-10-22', NULL  -- NOP Willie Green
        UNION ALL SELECT 34, 20, '2024-25', 0, '2024-10-22', NULL  -- NYK Tom Thibodeau
        UNION ALL SELECT 21, 21, '2024-25', 0, '2024-10-22', NULL  -- OKC Mark Daigneault
        UNION ALL SELECT 22, 22, '2024-25', 0, '2024-10-22', NULL  -- ORL Jamahl Mosley
        UNION ALL SELECT 23, 23, '2024-25', 0, '2024-10-22', NULL  -- PHI Nick Nurse
        UNION ALL SELECT 35, 24, '2024-25', 0, '2024-10-22', NULL  -- PHO Mike Budenholzer
        UNION ALL SELECT 33, 25, '2024-25', 0, '2024-10-22', NULL  -- POR Chauncey Billups
        UNION ALL SELECT 20, 26, '2024-25', 0, '2024-10-22', '2024-12-26'  -- SAC Mike Brown
        UNION ALL SELECT 26, 26, '2024-25', 1, '2024-12-27', NULL  -- SAC Doug Christie (tạm quyền)
        UNION ALL SELECT 38, 27, '2024-25', 0, '2024-10-22', '2024-11-01'  -- SAS Gregg Popovich
        UNION ALL SELECT 27, 27, '2024-25', 1, '2024-11-02', NULL  -- SAS Mitch Johnson (tạm quyền)
        UNION ALL SELECT 28, 28, '2024-25', 0, '2024-10-22', NULL  -- TOR Darko Rajaković
        UNION ALL SELECT 29, 29, '2024-25', 0, '2024-10-22', NULL  -- UTA Will Hardy
        UNION ALL SELECT 30, 30, '2024-25', 0, '2024-10-22', NULL  -- WAS Brian Keefe
        UNION ALL SELECT  1,  1, '2025-26', 0, '2025-10-21', NULL  -- ATL Quin Snyder
        UNION ALL SELECT  2,  2, '2025-26', 0, '2025-10-21', NULL  -- BOS Joe Mazzulla
        UNION ALL SELECT  3,  3, '2025-26', 0, '2025-10-21', NULL  -- BRK Jordi Fernández
        UNION ALL SELECT  4,  4, '2025-26', 0, '2025-10-21', NULL  -- CHO Charles Lee
        UNION ALL SELECT  5,  5, '2025-26', 0, '2025-10-21', NULL  -- CHI Billy Donovan
        UNION ALL SELECT  6,  6, '2025-26', 0, '2025-10-21', NULL  -- CLE Kenny Atkinson
        UNION ALL SELECT  7,  7, '2025-26', 0, '2025-10-21', NULL  -- DAL Jason Kidd
        UNION ALL SELECT  8,  8, '2025-26', 0, '2025-10-21', NULL  -- DEN David Adelman
        UNION ALL SELECT  9,  9, '2025-26', 0, '2025-10-21', NULL  -- DET J. B. Bickerstaff
        UNION ALL SELECT 10, 10, '2025-26', 0, '2025-10-21', NULL  -- GSW Steve Kerr
        UNION ALL SELECT 11, 11, '2025-26', 0, '2025-10-21', NULL  -- HOU Ime Udoka
        UNION ALL SELECT 12, 12, '2025-26', 0, '2025-10-21', NULL  -- IND Rick Carlisle
        UNION ALL SELECT 13, 13, '2025-26', 0, '2025-10-21', NULL  -- LAC Tyronn Lue
        UNION ALL SELECT 14, 14, '2025-26', 0, '2025-10-21', NULL  -- LAL JJ Redick
        UNION ALL SELECT 15, 15, '2025-26', 0, '2025-10-21', NULL  -- MEM Tuomas Iisalo
        UNION ALL SELECT 16, 16, '2025-26', 0, '2025-10-21', NULL  -- MIA Erik Spoelstra
        UNION ALL SELECT 17, 17, '2025-26', 0, '2025-10-21', NULL  -- MIL Doc Rivers
        UNION ALL SELECT 18, 18, '2025-26', 0, '2025-10-21', NULL  -- MIN Chris Finch
        UNION ALL SELECT 32, 19, '2025-26', 0, '2025-10-21', '2025-11-14'  -- NOP Willie Green
        UNION ALL SELECT 19, 19, '2025-26', 1, '2025-11-15', NULL  -- NOP James Borrego (tạm quyền)
        UNION ALL SELECT 20, 20, '2025-26', 0, '2025-10-21', NULL  -- NYK Mike Brown
        UNION ALL SELECT 21, 21, '2025-26', 0, '2025-10-21', NULL  -- OKC Mark Daigneault
        UNION ALL SELECT 22, 22, '2025-26', 0, '2025-10-21', NULL  -- ORL Jamahl Mosley
        UNION ALL SELECT 23, 23, '2025-26', 0, '2025-10-21', NULL  -- PHI Nick Nurse
        UNION ALL SELECT 24, 24, '2025-26', 0, '2025-10-21', NULL  -- PHO Jordan Ott
        UNION ALL SELECT 33, 25, '2025-26', 0, '2025-10-21', '2025-10-22'  -- POR Chauncey Billups
        UNION ALL SELECT 25, 25, '2025-26', 1, '2025-10-23', NULL  -- POR Tiago Splitter (tạm quyền)
        UNION ALL SELECT 26, 26, '2025-26', 0, '2025-10-21', NULL  -- SAC Doug Christie
        UNION ALL SELECT 27, 27, '2025-26', 0, '2025-10-21', NULL  -- SAS Mitch Johnson
        UNION ALL SELECT 28, 28, '2025-26', 0, '2025-10-21', NULL  -- TOR Darko Rajaković
        UNION ALL SELECT 29, 29, '2025-26', 0, '2025-10-21', NULL  -- UTA Will Hardy
        UNION ALL SELECT 30, 30, '2025-26', 0, '2025-10-21', NULL  -- WAS Brian Keefe
       ) t
  JOIN mua_giai mg ON mg.ten_mua_giai = t.mua;
