-- =====================================================================
--  File: database/02_du_lieu_doi_bong.sql  (TV2 - Đặng Ngọc Nhật Duy)
--  Dữ liệu mẫu: san_thi_dau, doi_bong, huan_luyen_vien, trong_tai
--  Import SAU database/qlbongro.sql, TRƯỚC 03_du_lieu_cau_thu.sql
--  Nguồn: danh sách sân & HLV trưởng NBA mùa 2026-27 (Wikipedia, NBA.com)
-- =====================================================================
USE qlbongro;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  BẢNG MÃ ĐỘI (cả nhóm dùng chung - TV3, TV4, TV5 điền ma_doi theo bảng này)
--  ma_doi = ma_san (sân nhà của đội có cùng số)
--   1 ATL Atlanta Hawks            16 MIA Miami Heat
--   2 BOS Boston Celtics           17 MIL Milwaukee Bucks
--   3 BRK Brooklyn Nets            18 MIN Minnesota Timberwolves
--   4 CHO Charlotte Hornets        19 NOP New Orleans Pelicans
--   5 CHI Chicago Bulls            20 NYK New York Knicks
--   6 CLE Cleveland Cavaliers      21 OKC Oklahoma City Thunder
--   7 DAL Dallas Mavericks         22 ORL Orlando Magic
--   8 DEN Denver Nuggets           23 PHI Philadelphia 76ers
--   9 DET Detroit Pistons          24 PHO Phoenix Suns
--  10 GSW Golden State Warriors    25 POR Portland Trail Blazers
--  11 HOU Houston Rockets          26 SAC Sacramento Kings
--  12 IND Indiana Pacers           27 SAS San Antonio Spurs
--  13 LAC Los Angeles Clippers     28 TOR Toronto Raptors
--  14 LAL Los Angeles Lakers       29 UTA Utah Jazz
--  15 MEM Memphis Grizzlies        30 WAS Washington Wizards
--
--  Gợi ý cho TV4 (phan_bang) - khu_vuc có sẵn trong qlbongro.sql:
--   1 Đại Tây Dương : BOS 2, BRK 3, NYK 20, PHI 23, TOR 28
--   2 Trung Tâm     : CHI 5, CLE 6, DET 9, IND 12, MIL 17
--   3 Đông Nam      : ATL 1, CHO 4, MIA 16, ORL 22, WAS 30
--   4 Tây Bắc       : DEN 8, MIN 18, OKC 21, POR 25, UTA 29
--   5 Thái Bình Dương: GSW 10, LAC 13, LAL 14, PHO 24, SAC 26
--   6 Tây Nam       : DAL 7, HOU 11, MEM 15, NOP 19, SAS 27
-- ---------------------------------------------------------------------

-- 1. SÂN THI ĐẤU (30 sân) - hinh_anh để trống, thêm ảnh qua trang quản trị
INSERT INTO san_thi_dau (ma_san, ten_san, thanh_pho, tieu_bang, suc_chua, nam_khanh_thanh) VALUES
    ( 1, 'State Farm Arena',         'Atlanta',        'Georgia',        17044, 1999),
    ( 2, 'TD Garden',                'Boston',         'Massachusetts',  18624, 1995),
    ( 3, 'Barclays Center',          'Brooklyn',       'New York',       17732, 2012),
    ( 4, 'Spectrum Center',          'Charlotte',      'North Carolina', 19077, 2005),
    ( 5, 'United Center',            'Chicago',        'Illinois',       20917, 1994),
    ( 6, 'Rocket Arena',             'Cleveland',      'Ohio',           19432, 1994),
    ( 7, 'American Airlines Center', 'Dallas',         'Texas',          19200, 2001),
    ( 8, 'Ball Arena',               'Denver',         'Colorado',       19520, 1999),
    ( 9, 'Little Caesars Arena',     'Detroit',        'Michigan',       20332, 2017),
    (10, 'Chase Center',             'San Francisco',  'California',     18064, 2019),
    (11, 'Toyota Center',            'Houston',        'Texas',          18055, 2003),
    (12, 'Gainbridge Fieldhouse',    'Indianapolis',   'Indiana',        17923, 1999),
    (13, 'Intuit Dome',              'Inglewood',      'California',     18000, 2024),
    (14, 'Crypto.com Arena',         'Los Angeles',    'California',     18997, 1999),
    (15, 'FedExForum',               'Memphis',        'Tennessee',      17794, 2004),
    (16, 'Kaseya Center',            'Miami',          'Florida',        19600, 1999),
    (17, 'Fiserv Forum',             'Milwaukee',      'Wisconsin',      17341, 2018),
    (18, 'Target Center',            'Minneapolis',    'Minnesota',      18024, 1990),
    (19, 'Smoothie King Center',     'New Orleans',    'Louisiana',      16867, 1999),
    (20, 'Madison Square Garden',    'New York',       'New York',       19812, 1968),
    (21, 'Paycom Center',            'Oklahoma City',  'Oklahoma',       18203, 2002),
    (22, 'Kia Center',               'Orlando',        'Florida',        18846, 2010),
    (23, 'Xfinity Mobile Arena',     'Philadelphia',   'Pennsylvania',   20007, 1996),
    (24, 'Mortgage Matchup Center',  'Phoenix',        'Arizona',        17071, 1992),
    (25, 'Moda Center',              'Portland',       'Oregon',         19411, 1995),
    (26, 'Golden 1 Center',          'Sacramento',     'California',     17611, 2016),
    (27, 'Frost Bank Center',        'San Antonio',    'Texas',          18354, 2002),
    (28, 'Scotiabank Arena',         'Toronto',        'Ontario',        19800, 1999),
    (29, 'Delta Center',             'Salt Lake City', 'Utah',           18306, 1991),
    (30, 'Capital One Arena',        'Washington',     'D.C.',           20333, 1997);

-- 2. ĐỘI BÓNG (30 đội) - logo để trống, upload qua trang quản trị
INSERT INTO doi_bong (ma_doi, ten_doi, ten_viet_tat, thanh_pho, ma_san, nam_thanh_lap, mo_ta) VALUES
    ( 1, 'Atlanta Hawks',          'ATL', 'Atlanta',        1, 1946, 'Thành lập năm 1946 với tên Tri-Cities Blackhawks, chuyển về Atlanta từ 1968.'),
    ( 2, 'Boston Celtics',         'BOS', 'Boston',         2, 1946, 'Một trong những đội giàu truyền thống nhất NBA, nhiều chức vô địch nhất giải.'),
    ( 3, 'Brooklyn Nets',          'BRK', 'Brooklyn',       3, 1967, 'Khởi đầu ở giải ABA, chuyển về Brooklyn từ năm 2012.'),
    ( 4, 'Charlotte Hornets',      'CHO', 'Charlotte',      4, 1988, 'Đội bóng của bang North Carolina, gia nhập NBA năm 1988.'),
    ( 5, 'Chicago Bulls',          'CHI', 'Chicago',        5, 1966, 'Triều đại 6 chức vô địch thập niên 1990 cùng Michael Jordan.'),
    ( 6, 'Cleveland Cavaliers',    'CLE', 'Cleveland',      6, 1970, 'Vô địch NBA năm 2016.'),
    ( 7, 'Dallas Mavericks',       'DAL', 'Dallas',         7, 1980, 'Vô địch NBA năm 2011.'),
    ( 8, 'Denver Nuggets',         'DEN', 'Denver',         8, 1967, 'Khởi đầu ở giải ABA, vô địch NBA năm 2023.'),
    ( 9, 'Detroit Pistons',        'DET', 'Detroit',        9, 1941, 'Khởi đầu với tên Fort Wayne Zollner Pistons, chuyển về Detroit năm 1957.'),
    (10, 'Golden State Warriors',  'GSW', 'San Francisco', 10, 1946, 'Thành lập ở Philadelphia, hiện thi đấu tại San Francisco.'),
    (11, 'Houston Rockets',        'HOU', 'Houston',       11, 1967, 'Vô địch NBA hai năm liên tiếp 1994 và 1995.'),
    (12, 'Indiana Pacers',         'IND', 'Indianapolis',  12, 1967, 'Khởi đầu ở giải ABA, gia nhập NBA năm 1976.'),
    (13, 'Los Angeles Clippers',   'LAC', 'Los Angeles',   13, 1970, 'Thành lập với tên Buffalo Braves, từ 2024 thi đấu tại Intuit Dome.'),
    (14, 'Los Angeles Lakers',     'LAL', 'Los Angeles',   14, 1947, 'Thành lập ở Minneapolis, một trong những đội nhiều chức vô địch nhất.'),
    (15, 'Memphis Grizzlies',      'MEM', 'Memphis',       15, 1995, 'Thành lập ở Vancouver, chuyển về Memphis năm 2001.'),
    (16, 'Miami Heat',             'MIA', 'Miami',         16, 1988, 'Vô địch NBA các năm 2006, 2012 và 2013.'),
    (17, 'Milwaukee Bucks',        'MIL', 'Milwaukee',     17, 1968, 'Vô địch NBA năm 1971 và 2021.'),
    (18, 'Minnesota Timberwolves', 'MIN', 'Minneapolis',   18, 1989, 'Đội bóng của bang Minnesota, gia nhập NBA năm 1989.'),
    (19, 'New Orleans Pelicans',   'NOP', 'New Orleans',   19, 2002, 'Thành lập năm 2002, mang tên Pelicans từ 2013.'),
    (20, 'New York Knicks',        'NYK', 'New York',      20, 1946, 'Thi đấu tại Madison Square Garden, sân lâu đời nhất NBA.'),
    (21, 'Oklahoma City Thunder',  'OKC', 'Oklahoma City', 21, 1967, 'Thành lập với tên Seattle SuperSonics, chuyển về Oklahoma City năm 2008.'),
    (22, 'Orlando Magic',          'ORL', 'Orlando',       22, 1989, 'Đội bóng của bang Florida, gia nhập NBA năm 1989.'),
    (23, 'Philadelphia 76ers',     'PHI', 'Philadelphia',  23, 1946, 'Thành lập với tên Syracuse Nationals, chuyển về Philadelphia năm 1963.'),
    (24, 'Phoenix Suns',           'PHO', 'Phoenix',       24, 1968, 'Đội bóng của bang Arizona, gia nhập NBA năm 1968.'),
    (25, 'Portland Trail Blazers', 'POR', 'Portland',      25, 1970, 'Vô địch NBA năm 1977.'),
    (26, 'Sacramento Kings',       'SAC', 'Sacramento',    26, 1945, 'Thành lập với tên Rochester Royals, chuyển về Sacramento năm 1985.'),
    (27, 'San Antonio Spurs',      'SAS', 'San Antonio',   27, 1967, 'Khởi đầu ở giải ABA, 5 lần vô địch NBA.'),
    (28, 'Toronto Raptors',        'TOR', 'Toronto',       28, 1995, 'Đội bóng duy nhất ở Canada, vô địch NBA năm 2019.'),
    (29, 'Utah Jazz',              'UTA', 'Salt Lake City',29, 1974, 'Thành lập ở New Orleans, chuyển về Utah năm 1979.'),
    (30, 'Washington Wizards',     'WAS', 'Washington',    30, 1961, 'Thành lập với tên Chicago Packers, vô địch NBA năm 1978.');

-- 3. HUẤN LUYỆN VIÊN
--    30 HLV trưởng mùa 2026-27 (mỗi đội đúng 1 người, ngay_bat_dau = ngày nhận việc ở đội hiện tại)
--    ngay_sinh để trống (chưa đối chiếu nguồn); HLV phó thêm qua trang quản trị.
INSERT INTO huan_luyen_vien (ma_hlv, ho_ten, quoc_tich, ma_doi, vai_tro, ngay_bat_dau) VALUES
    ( 1, 'Quin Snyder',       'Mỹ',        1, 'truong', '2023-02-26'),
    ( 2, 'Joe Mazzulla',      'Mỹ',        2, 'truong', '2023-02-16'),
    ( 3, 'Jordi Fernández',   'Tây Ban Nha', 3, 'truong', '2024-04-22'),
    ( 4, 'Charles Lee',       'Mỹ',        4, 'truong', '2024-05-09'),
    ( 5, 'Tiago Splitter',    'Brazil',    5, 'truong', '2026-06-16'),
    ( 6, 'Kenny Atkinson',    'Mỹ',        6, 'truong', '2024-06-28'),
    ( 7, 'Dusty May',         'Mỹ',        7, 'truong', '2026-06-23'),
    ( 8, 'David Adelman',     'Mỹ',        8, 'truong', '2025-04-08'),
    ( 9, 'J. B. Bickerstaff', 'Mỹ',        9, 'truong', '2024-07-03'),
    (10, 'Steve Kerr',        'Mỹ',       10, 'truong', '2014-05-19'),
    (11, 'Ime Udoka',         'Mỹ',       11, 'truong', '2023-04-25'),
    (12, 'Rick Carlisle',     'Mỹ',       12, 'truong', '2021-06-24'),
    (13, 'Tyronn Lue',        'Mỹ',       13, 'truong', '2020-10-20'),
    (14, 'JJ Redick',         'Mỹ',       14, 'truong', '2024-06-24'),
    (15, 'Tuomas Iisalo',     'Phần Lan', 15, 'truong', '2025-03-28'),
    (16, 'Erik Spoelstra',    'Mỹ',       16, 'truong', '2008-04-28'),
    (17, 'Taylor Jenkins',    'Mỹ',       17, 'truong', '2026-04-30'),
    (18, 'Chris Finch',       'Mỹ',       18, 'truong', '2021-02-22'),
    (19, 'Jamahl Mosley',     'Mỹ',       19, 'truong', '2026-05-18'),
    (20, 'Mike Brown',        'Mỹ',       20, 'truong', '2025-07-07'),
    (21, 'Mark Daigneault',   'Mỹ',       21, 'truong', '2020-11-11'),
    (22, 'Sean Sweeney',      'Mỹ',       22, 'truong', '2026-06-01'),
    (23, 'Nick Nurse',        'Mỹ',       23, 'truong', '2023-06-01'),
    (24, 'Jordan Ott',        'Mỹ',       24, 'truong', '2025-06-04'),
    (25, 'Micah Nori',        'Mỹ',       25, 'truong', '2026-06-23'),
    (26, 'Doug Christie',     'Mỹ',       26, 'truong', '2024-12-27'),
    (27, 'Mitch Johnson',     'Mỹ',       27, 'truong', '2025-05-02'),
    (28, 'Darko Rajaković',   'Serbia',   28, 'truong', '2023-06-13'),
    (29, 'Will Hardy',        'Mỹ',       29, 'truong', '2022-06-29'),
    (30, 'Brian Keefe',       'Mỹ',       30, 'truong', '2024-05-29');

-- HLV đang tự do (rời ghế sau mùa 2025-26) - dùng để test gán HLV / kiểm tra "1 HLV trưởng"
INSERT INTO huan_luyen_vien (ma_hlv, ho_ten, quoc_tich, ma_doi, vai_tro, ngay_bat_dau) VALUES
    (31, 'Billy Donovan',     'Mỹ', NULL, 'truong', NULL),
    (32, 'Jason Kidd',        'Mỹ', NULL, 'truong', NULL),
    (33, 'Doc Rivers',        'Mỹ', NULL, 'truong', NULL),
    (34, 'Willie Green',      'Mỹ', NULL, 'truong', NULL);

-- 4. TRỌNG TÀI (danh sách tham khảo; nam_bat_dau để trống, bổ sung qua trang quản trị)
INSERT INTO trong_tai (ma_trong_tai, ho_ten, so_ao) VALUES
    ( 1, 'Marc Davis',        8),
    ( 2, 'Sean Wright',       4),
    ( 3, 'Kane Fitzgerald',   5),
    ( 4, 'John Goble',       10),
    ( 5, 'Ed Malloy',        14),
    ( 6, 'Zach Zarba',       15),
    ( 7, 'David Guthrie',    16),
    ( 8, 'James Capers',     19),
    ( 9, 'Kevin Scott',      24),
    (10, 'Tony Brothers',    25),
    (11, 'Pat Fraher',       26),
    (12, 'Mitchell Ervin',   27),
    (13, 'Eric Lewis',       42),
    (14, 'Brian Forte',      45),
    (15, 'Ben Taylor',       46),
    (16, 'Scott Foster',     48),
    (17, 'Bill Kennedy',     55),
    (18, 'Josh Tiven',       58),
    (19, 'Courtney Kirkland',61),
    (20, 'Rodney Mott',      71);
