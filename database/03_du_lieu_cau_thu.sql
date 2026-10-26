-- =====================================================================
--  File: database/03_du_lieu_cau_thu.sql  (TV3 - Nguyễn Đỗ Thảo Nguyên)
--  Dữ liệu mẫu: cau_thu, chuyen_nhuong, chan_thuong
--  Import SAU 02_du_lieu_doi_bong.sql (TV2), TRƯỚC 04_du_lieu_tran_dau.sql
--  Nguồn: đội hình NBA đầu mùa 2025-26 (8 cầu thủ chính mỗi đội) và các giao dịch thật năm 2025.
--  Ngày sinh / nơi sinh để NULL khi chưa đối chiếu được nguồn. Số áo, chiều cao, cân nặng
--  theo danh sách đội đầu mùa 2025-26, có thể lệch với thực tế mùa 2026-27.
-- =====================================================================

USE qlbongro;

-- ---------------------------------------------------------------------
--  BẢNG MÃ CẦU THỦ (TV4, TV5 điền ma_cau_thu theo bảng này)
--  Mỗi đội 8 cầu thủ, mã cầu thủ của đội có ma_doi = N là từ (N-1)*8+1 đến N*8.
--  VD: ATL (ma_doi 1) = 1..8, BOS (2) = 9..16, ..., WAS (30) = 233..240.
--  241, 242 = cầu thủ đã giải nghệ (không có đội).
--  Trong mỗi đội, 5 cầu thủ đầu tiên là đội hình xuất phát gợi ý (dùng cho box score).

--   1 ATL Atlanta Hawks             1-  8: 1 Trae Young, 2 Dyson Daniels, 3 Jalen Johnson, ...
--   2 BOS Boston Celtics            9- 16: 9 Jayson Tatum, 10 Jaylen Brown, 11 Derrick White, ...
--   3 BRK Brooklyn Nets            17- 24: 17 Michael Porter Jr., 18 Cam Thomas, 19 Nic Claxton, ...
--   4 CHO Charlotte Hornets        25- 32: 25 LaMelo Ball, 26 Brandon Miller, 27 Miles Bridges, ...
--   5 CHI Chicago Bulls            33- 40: 33 Josh Giddey, 34 Coby White, 35 Nikola Vučević, ...
--   6 CLE Cleveland Cavaliers      41- 48: 41 Donovan Mitchell, 42 Darius Garland, 43 Evan Mobley, ...
--   7 DAL Dallas Mavericks         49- 56: 49 Anthony Davis, 50 Kyrie Irving, 51 Cooper Flagg, ...
--   8 DEN Denver Nuggets           57- 64: 57 Nikola Jokić, 58 Jamal Murray, 59 Aaron Gordon, ...
--   9 DET Detroit Pistons          65- 72: 65 Cade Cunningham, 66 Jalen Duren, 67 Ausar Thompson, ...
--  10 GSW Golden State Warriors    73- 80: 73 Stephen Curry, 74 Jimmy Butler, 75 Draymond Green, ...
--  11 HOU Houston Rockets          81- 88: 81 Kevin Durant, 82 Alperen Şengün, 83 Amen Thompson, ...
--  12 IND Indiana Pacers           89- 96: 89 Tyrese Haliburton, 90 Pascal Siakam, 91 Andrew Nembhard, ...
--  13 LAC Los Angeles Clippers     97-104: 97 Kawhi Leonard, 98 James Harden, 99 Ivica Zubac, ...
--  14 LAL Los Angeles Lakers      105-112: 105 Luka Dončić, 106 LeBron James, 107 Austin Reaves, ...
--  15 MEM Memphis Grizzlies       113-120: 113 Ja Morant, 114 Jaren Jackson Jr., 115 Zach Edey, ...
--  16 MIA Miami Heat              121-128: 121 Bam Adebayo, 122 Tyler Herro, 123 Andrew Wiggins, ...
--  17 MIL Milwaukee Bucks         129-136: 129 Giannis Antetokounmpo, 130 Myles Turner, 131 Kyle Kuzma, ...
--  18 MIN Minnesota Timberwolves  137-144: 137 Anthony Edwards, 138 Julius Randle, 139 Rudy Gobert, ...
--  19 NOP New Orleans Pelicans    145-152: 145 Zion Williamson, 146 Trey Murphy III, 147 Herbert Jones, ...
--  20 NYK New York Knicks         153-160: 153 Jalen Brunson, 154 Karl-Anthony Towns, 155 Mikal Bridges, ...
--  21 OKC Oklahoma City Thunder   161-168: 161 Shai Gilgeous-Alexander, 162 Jalen Williams, 163 Chet Holmgren, ...
--  22 ORL Orlando Magic           169-176: 169 Paolo Banchero, 170 Franz Wagner, 171 Jalen Suggs, ...
--  23 PHI Philadelphia 76ers      177-184: 177 Joel Embiid, 178 Tyrese Maxey, 179 Paul George, ...
--  24 PHO Phoenix Suns            185-192: 185 Devin Booker, 186 Jalen Green, 187 Dillon Brooks, ...
--  25 POR Portland Trail Blazers  193-200: 193 Deni Avdija, 194 Shaedon Sharpe, 195 Jrue Holiday, ...
--  26 SAC Sacramento Kings        201-208: 201 Domantas Sabonis, 202 Zach LaVine, 203 DeMar DeRozan, ...
--  27 SAS San Antonio Spurs       209-216: 209 Victor Wembanyama, 210 De'Aaron Fox, 211 Stephon Castle, ...
--  28 TOR Toronto Raptors         217-224: 217 Scottie Barnes, 218 Brandon Ingram, 219 RJ Barrett, ...
--  29 UTA Utah Jazz               225-232: 225 Lauri Markkanen, 226 Keyonte George, 227 Walker Kessler, ...
--  30 WAS Washington Wizards      233-240: 233 Alex Sarr, 234 Bub Carrington, 235 Bilal Coulibaly, ...
-- ---------------------------------------------------------------------

-- 1. CẦU THỦ (242 người)
INSERT INTO cau_thu (ma_cau_thu, ho_ten, ngay_sinh, noi_sinh, quoc_tich, chieu_cao_cm, can_nang_kg,
                     vi_tri, tay_nem, so_ao, truong_dai_hoc, nam_ra_mat_nba, ma_doi, trang_thai) VALUES
    -- 1 ATL Atlanta Hawks
    (1, 'Trae Young', '1998-09-19', 'Lubbock, Texas', 'Mỹ', 185, 74, 'PG', 'phai', 11, 'Oklahoma', 2018, 1, 'dang_thi_dau'),
    (2, 'Dyson Daniels', '2003-03-17', 'Bendigo', 'Úc', 198, 91, 'SG', 'phai', 5, NULL, 2022, 1, 'dang_thi_dau'),
    (3, 'Jalen Johnson', '2001-12-18', NULL, 'Mỹ', 206, 99, 'SF', 'phai', 1, 'Duke', 2021, 1, 'dang_thi_dau'),
    (4, 'Kristaps Porziņģis', '1995-08-02', 'Liepāja', 'Latvia', 218, 109, 'C', 'phai', 8, NULL, 2015, 1, 'dang_thi_dau'),
    (5, 'Onyeka Okongwu', '2000-12-11', 'Los Angeles, California', 'Mỹ', 203, 107, 'C', 'phai', 17, 'USC', 2020, 1, 'dang_thi_dau'),
    (6, 'Zaccharie Risacher', '2005-04-08', NULL, 'Pháp', 203, 91, 'SF', 'phai', 10, NULL, 2024, 1, 'dang_thi_dau'),
    (7, 'Nickeil Alexander-Walker', '1998-09-02', 'Toronto', 'Canada', 196, 93, 'SG', 'phai', 7, 'Virginia Tech', 2019, 1, 'dang_thi_dau'),
    (8, 'Luke Kennard', '1996-06-24', 'Middletown, Ohio', 'Mỹ', 196, 93, 'SG', 'trai', 3, 'Duke', 2017, 1, 'dang_thi_dau'),

    -- 2 BOS Boston Celtics
    (9, 'Jayson Tatum', '1998-03-03', 'St. Louis, Missouri', 'Mỹ', 203, 95, 'SF', 'phai', 0, 'Duke', 2017, 2, 'dang_thi_dau'),
    (10, 'Jaylen Brown', '1996-10-24', 'Marietta, Georgia', 'Mỹ', 198, 101, 'SG', 'phai', 7, 'California', 2016, 2, 'dang_thi_dau'),
    (11, 'Derrick White', '1994-07-02', 'Parker, Colorado', 'Mỹ', 193, 86, 'PG', 'phai', 9, 'Colorado', 2017, 2, 'dang_thi_dau'),
    (12, 'Payton Pritchard', '1998-01-28', NULL, 'Mỹ', 185, 88, 'PG', 'phai', 11, 'Oregon', 2020, 2, 'dang_thi_dau'),
    (13, 'Anfernee Simons', '1999-06-08', NULL, 'Mỹ', 191, 82, 'SG', 'phai', 4, NULL, 2018, 2, 'dang_thi_dau'),
    (14, 'Sam Hauser', '1997-12-08', NULL, 'Mỹ', 201, 98, 'SF', 'phai', 30, 'Virginia', 2021, 2, 'dang_thi_dau'),
    (15, 'Neemias Queta', '1999-07-13', 'Lisbon', 'Bồ Đào Nha', 213, 111, 'C', 'phai', 88, 'Utah State', 2021, 2, 'dang_thi_dau'),
    (16, 'Hugo González', NULL, 'Madrid', 'Tây Ban Nha', 198, 93, 'SF', 'phai', 28, NULL, 2025, 2, 'dang_thi_dau'),

    -- 3 BRK Brooklyn Nets
    (17, 'Michael Porter Jr.', '1998-06-29', 'Columbia, Missouri', 'Mỹ', 208, 99, 'SF', 'phai', 17, 'Missouri', 2019, 3, 'dang_thi_dau'),
    (18, 'Cam Thomas', '2001-10-13', 'Yokosuka', 'Mỹ', 191, 95, 'SG', 'phai', 24, 'LSU', 2021, 3, 'dang_thi_dau'),
    (19, 'Nic Claxton', '1999-04-17', 'Greenville, South Carolina', 'Mỹ', 211, 98, 'C', 'phai', 33, 'Georgia', 2019, 3, 'dang_thi_dau'),
    (20, 'Noah Clowney', NULL, NULL, 'Mỹ', 208, 95, 'PF', 'phai', 21, 'Alabama', 2023, 3, 'dang_thi_dau'),
    (21, 'Terance Mann', '1996-10-18', 'Brooklyn, New York', 'Mỹ', 196, 98, 'SG', 'phai', 14, 'Florida State', 2019, 3, 'dang_thi_dau'),
    (22, 'Day''Ron Sharpe', '2001-11-06', NULL, 'Mỹ', 206, 120, 'C', 'phai', 20, 'North Carolina', 2021, 3, 'dang_thi_dau'),
    (23, 'Egor Dëmin', NULL, 'Moskva', 'Nga', 206, 86, 'PG', 'phai', 8, 'BYU', 2025, 3, 'dang_thi_dau'),
    (24, 'Jalen Wilson', NULL, NULL, 'Mỹ', 198, 100, 'SF', 'phai', 22, 'Kansas', 2023, 3, 'dang_thi_dau'),

    -- 4 CHO Charlotte Hornets
    (25, 'LaMelo Ball', '2001-08-22', NULL, 'Mỹ', 201, 82, 'PG', 'phai', 1, NULL, 2020, 4, 'dang_thi_dau'),
    (26, 'Brandon Miller', '2002-11-22', NULL, 'Mỹ', 201, 91, 'SF', 'phai', 24, 'Alabama', 2023, 4, 'dang_thi_dau'),
    (27, 'Miles Bridges', '1998-03-21', 'Flint, Michigan', 'Mỹ', 201, 102, 'PF', 'phai', 0, 'Michigan State', 2018, 4, 'dang_thi_dau'),
    (28, 'Kon Knueppel', NULL, NULL, 'Mỹ', 196, 99, 'SG', 'phai', 7, 'Duke', 2025, 4, 'dang_thi_dau'),
    (29, 'Collin Sexton', '1999-01-04', 'Marietta, Georgia', 'Mỹ', 191, 86, 'PG', 'phai', 2, 'Alabama', 2018, 4, 'dang_thi_dau'),
    (30, 'Moussa Diabaté', NULL, 'Paris', 'Pháp', 208, 95, 'C', 'phai', 14, 'Michigan', 2023, 4, 'dang_thi_dau'),
    (31, 'Josh Green', '2000-11-16', 'Sydney', 'Úc', 196, 91, 'SG', 'phai', 10, 'Arizona', 2020, 4, 'dang_thi_dau'),
    (32, 'Ryan Kalkbrenner', NULL, NULL, 'Mỹ', 216, 116, 'C', 'phai', 11, 'Creighton', 2025, 4, 'dang_thi_dau'),

    -- 5 CHI Chicago Bulls
    (33, 'Josh Giddey', '2002-10-10', 'Melbourne', 'Úc', 203, 98, 'PG', 'phai', 3, NULL, 2021, 5, 'dang_thi_dau'),
    (34, 'Coby White', '2000-02-16', 'Goldsboro, North Carolina', 'Mỹ', 196, 88, 'SG', 'phai', 0, 'North Carolina', 2019, 5, 'dang_thi_dau'),
    (35, 'Nikola Vučević', '1990-10-24', 'Morges', 'Montenegro', 208, 118, 'C', 'phai', 9, 'USC', 2011, 5, 'dang_thi_dau'),
    (36, 'Matas Buzelis', NULL, NULL, 'Mỹ', 208, 89, 'PF', 'phai', 14, NULL, 2024, 5, 'dang_thi_dau'),
    (37, 'Ayo Dosunmu', '2000-01-17', 'Chicago, Illinois', 'Mỹ', 196, 91, 'SG', 'phai', 12, 'Illinois', 2021, 5, 'dang_thi_dau'),
    (38, 'Patrick Williams', '2001-08-26', 'Charlotte, North Carolina', 'Mỹ', 201, 98, 'PF', 'phai', 44, 'Florida State', 2020, 5, 'dang_thi_dau'),
    (39, 'Kevin Huerter', '1998-08-27', 'Albany, New York', 'Mỹ', 198, 90, 'SG', 'phai', 13, 'Maryland', 2018, 5, 'dang_thi_dau'),
    (40, 'Isaac Okoro', '2001-01-26', 'Atlanta, Georgia', 'Mỹ', 196, 102, 'SF', 'phai', 35, 'Auburn', 2020, 5, 'dang_thi_dau'),

    -- 6 CLE Cleveland Cavaliers
    (41, 'Donovan Mitchell', '1996-09-07', 'Elmsford, New York', 'Mỹ', 191, 97, 'SG', 'phai', 45, 'Louisville', 2017, 6, 'dang_thi_dau'),
    (42, 'Darius Garland', '2000-01-26', 'Gary, Indiana', 'Mỹ', 185, 87, 'PG', 'phai', 10, 'Vanderbilt', 2019, 6, 'dang_thi_dau'),
    (43, 'Evan Mobley', '2001-06-18', 'San Diego, California', 'Mỹ', 211, 98, 'PF', 'phai', 4, 'USC', 2021, 6, 'dang_thi_dau'),
    (44, 'Jarrett Allen', '1998-04-21', 'Round Rock, Texas', 'Mỹ', 211, 110, 'C', 'phai', 31, 'Texas', 2017, 6, 'dang_thi_dau'),
    (45, 'De''Andre Hunter', '1997-12-02', 'Philadelphia, Pennsylvania', 'Mỹ', 203, 102, 'SF', 'phai', 12, 'Virginia', 2019, 6, 'dang_thi_dau'),
    (46, 'Max Strus', '1996-03-28', 'Hickory Hills, Illinois', 'Mỹ', 196, 98, 'SF', 'phai', 1, 'DePaul', 2019, 6, 'dang_thi_dau'),
    (47, 'Sam Merrill', NULL, NULL, 'Mỹ', 193, 93, 'SG', 'phai', 5, 'Utah State', 2020, 6, 'dang_thi_dau'),
    (48, 'Lonzo Ball', '1997-10-27', 'Anaheim, California', 'Mỹ', 198, 86, 'PG', 'phai', 2, 'UCLA', 2017, 6, 'dang_thi_dau'),

    -- 7 DAL Dallas Mavericks
    (49, 'Anthony Davis', '1993-03-11', 'Chicago, Illinois', 'Mỹ', 208, 114, 'PF', 'phai', 3, 'Kentucky', 2012, 7, 'dang_thi_dau'),
    (50, 'Kyrie Irving', '1992-03-23', 'Melbourne', 'Mỹ', 188, 88, 'PG', 'phai', 11, 'Duke', 2011, 7, 'dang_thi_dau'),
    (51, 'Cooper Flagg', '2006-12-21', 'Newport, Maine', 'Mỹ', 206, 100, 'SF', 'phai', 32, 'Duke', 2025, 7, 'dang_thi_dau'),
    (52, 'Klay Thompson', '1990-02-08', 'Los Angeles, California', 'Mỹ', 198, 100, 'SG', 'phai', 31, 'Washington State', 2011, 7, 'dang_thi_dau'),
    (53, 'P.J. Washington', '1998-08-23', 'Louisville, Kentucky', 'Mỹ', 201, 104, 'PF', 'phai', 25, 'Kentucky', 2019, 7, 'dang_thi_dau'),
    (54, 'Dereck Lively II', NULL, NULL, 'Mỹ', 216, 104, 'C', 'phai', 2, 'Duke', 2023, 7, 'dang_thi_dau'),
    (55, 'Daniel Gafford', '1998-10-01', 'El Dorado, Arkansas', 'Mỹ', 208, 107, 'C', 'phai', 21, 'Arkansas', 2019, 7, 'dang_thi_dau'),
    (56, 'D''Angelo Russell', '1996-02-23', 'Louisville, Kentucky', 'Mỹ', 193, 87, 'PG', 'trai', 5, 'Ohio State', 2015, 7, 'dang_thi_dau'),

    -- 8 DEN Denver Nuggets
    (57, 'Nikola Jokić', '1995-02-19', 'Sombor', 'Serbia', 211, 129, 'C', 'phai', 15, NULL, 2015, 8, 'dang_thi_dau'),
    (58, 'Jamal Murray', '1997-02-23', 'Kitchener, Ontario', 'Canada', 193, 98, 'PG', 'phai', 27, 'Kentucky', 2016, 8, 'dang_thi_dau'),
    (59, 'Aaron Gordon', '1995-09-16', 'San Jose, California', 'Mỹ', 203, 107, 'PF', 'phai', 32, 'Arizona', 2014, 8, 'dang_thi_dau'),
    (60, 'Christian Braun', '2001-04-17', NULL, 'Mỹ', 198, 99, 'SG', 'phai', 0, 'Kansas', 2022, 8, 'dang_thi_dau'),
    (61, 'Cameron Johnson', '1996-03-03', NULL, 'Mỹ', 203, 95, 'SF', 'phai', 23, 'North Carolina', 2019, 8, 'dang_thi_dau'),
    (62, 'Jonas Valančiūnas', '1992-05-06', 'Utena', 'Litva', 211, 120, 'C', 'phai', 17, NULL, 2012, 8, 'dang_thi_dau'),
    (63, 'Bruce Brown', '1996-08-15', 'Boston, Massachusetts', 'Mỹ', 193, 91, 'SG', 'phai', 11, 'Miami', 2018, 8, 'dang_thi_dau'),
    (64, 'Peyton Watson', NULL, NULL, 'Mỹ', 201, 91, 'SF', 'phai', 8, 'UCLA', 2022, 8, 'dang_thi_dau'),

    -- 9 DET Detroit Pistons
    (65, 'Cade Cunningham', '2001-09-25', 'Arlington, Texas', 'Mỹ', 198, 100, 'PG', 'phai', 2, 'Oklahoma State', 2021, 9, 'dang_thi_dau'),
    (66, 'Jalen Duren', '2003-11-18', NULL, 'Mỹ', 208, 113, 'C', 'phai', 0, 'Memphis', 2022, 9, 'dang_thi_dau'),
    (67, 'Ausar Thompson', '2003-01-30', 'Oakland, California', 'Mỹ', 201, 93, 'SF', 'phai', 9, NULL, 2023, 9, 'dang_thi_dau'),
    (68, 'Tobias Harris', '1992-07-15', 'Islip, New York', 'Mỹ', 203, 102, 'PF', 'phai', 12, 'Tennessee', 2012, 9, 'dang_thi_dau'),
    (69, 'Jaden Ivey', '2002-02-13', 'South Bend, Indiana', 'Mỹ', 193, 88, 'SG', 'phai', 23, 'Purdue', 2022, 9, 'dang_thi_dau'),
    (70, 'Isaiah Stewart', '2001-05-22', 'Rochester, New York', 'Mỹ', 203, 113, 'C', 'phai', 28, 'Washington', 2020, 9, 'dang_thi_dau'),
    (71, 'Duncan Robinson', '1994-04-22', 'York, Maine', 'Mỹ', 201, 98, 'SF', 'phai', 55, 'Michigan', 2018, 9, 'dang_thi_dau'),
    (72, 'Ron Holland', NULL, NULL, 'Mỹ', 203, 93, 'SF', 'phai', 5, NULL, 2024, 9, 'dang_thi_dau'),

    -- 10 GSW Golden State Warriors
    (73, 'Stephen Curry', '1988-03-14', 'Akron, Ohio', 'Mỹ', 188, 84, 'PG', 'phai', 30, 'Davidson', 2009, 10, 'dang_thi_dau'),
    (74, 'Jimmy Butler', '1989-09-14', 'Houston, Texas', 'Mỹ', 201, 104, 'SF', 'phai', 10, 'Marquette', 2011, 10, 'dang_thi_dau'),
    (75, 'Draymond Green', '1990-03-04', 'Saginaw, Michigan', 'Mỹ', 198, 104, 'PF', 'phai', 23, 'Michigan State', 2012, 10, 'dang_thi_dau'),
    (76, 'Jonathan Kuminga', '2002-10-06', 'Goma', 'CHDC Congo', 201, 102, 'PF', 'phai', 1, NULL, 2021, 10, 'dang_thi_dau'),
    (77, 'Brandin Podziemski', '2003-02-25', NULL, 'Mỹ', 193, 93, 'SG', 'phai', 2, 'Santa Clara', 2023, 10, 'dang_thi_dau'),
    (78, 'Moses Moody', '2002-05-31', 'Little Rock, Arkansas', 'Mỹ', 196, 93, 'SG', 'phai', 4, 'Arkansas', 2021, 10, 'dang_thi_dau'),
    (79, 'Buddy Hield', '1992-12-17', 'Freeport', 'Bahamas', 193, 100, 'SG', 'phai', 7, 'Oklahoma', 2016, 10, 'dang_thi_dau'),
    (80, 'Al Horford', '1986-06-03', 'Puerto Plata', 'Cộng hòa Dominica', 206, 109, 'C', 'phai', 20, 'Florida', 2007, 10, 'dang_thi_dau'),

    -- 11 HOU Houston Rockets
    (81, 'Kevin Durant', '1988-09-29', 'Washington, D.C.', 'Mỹ', 211, 109, 'PF', 'phai', 7, 'Texas', 2007, 11, 'dang_thi_dau'),
    (82, 'Alperen Şengün', '2002-07-25', 'Giresun', 'Thổ Nhĩ Kỳ', 211, 110, 'C', 'phai', 28, NULL, 2021, 11, 'dang_thi_dau'),
    (83, 'Amen Thompson', '2003-01-30', 'Oakland, California', 'Mỹ', 201, 91, 'SF', 'phai', 1, NULL, 2023, 11, 'dang_thi_dau'),
    (84, 'Jabari Smith Jr.', '2003-05-13', NULL, 'Mỹ', 211, 100, 'PF', 'phai', 10, 'Auburn', 2022, 11, 'dang_thi_dau'),
    (85, 'Fred VanVleet', '1994-02-25', 'Rockford, Illinois', 'Mỹ', 185, 89, 'PG', 'phai', 5, 'Wichita State', 2016, 11, 'dang_thi_dau'),
    (86, 'Steven Adams', '1993-07-20', 'Rotorua', 'New Zealand', 211, 120, 'C', 'phai', 12, 'Pittsburgh', 2013, 11, 'dang_thi_dau'),
    (87, 'Tari Eason', '2001-05-10', NULL, 'Mỹ', 203, 98, 'PF', 'phai', 17, 'LSU', 2022, 11, 'dang_thi_dau'),
    (88, 'Reed Sheppard', '2004-06-24', NULL, 'Mỹ', 188, 83, 'PG', 'phai', 15, 'Kentucky', 2024, 11, 'dang_thi_dau'),

    -- 12 IND Indiana Pacers
    (89, 'Tyrese Haliburton', '2000-02-29', 'Oshkosh, Wisconsin', 'Mỹ', 196, 84, 'PG', 'phai', 0, 'Iowa State', 2020, 12, 'dang_thi_dau'),
    (90, 'Pascal Siakam', '1994-04-02', 'Douala', 'Cameroon', 203, 111, 'PF', 'phai', 43, 'New Mexico State', 2016, 12, 'dang_thi_dau'),
    (91, 'Andrew Nembhard', '2000-01-16', 'Aurora, Ontario', 'Canada', 196, 87, 'PG', 'phai', 2, 'Gonzaga', 2022, 12, 'dang_thi_dau'),
    (92, 'Aaron Nesmith', '1999-10-16', 'Charleston, South Carolina', 'Mỹ', 196, 98, 'SF', 'phai', 23, 'Vanderbilt', 2020, 12, 'dang_thi_dau'),
    (93, 'Obi Toppin', '1998-03-04', 'Brooklyn, New York', 'Mỹ', 206, 100, 'PF', 'phai', 1, 'Dayton', 2020, 12, 'dang_thi_dau'),
    (94, 'T.J. McConnell', '1992-03-25', 'Pittsburgh, Pennsylvania', 'Mỹ', 185, 86, 'PG', 'phai', 9, 'Arizona', 2015, 12, 'dang_thi_dau'),
    (95, 'Isaiah Jackson', NULL, NULL, 'Mỹ', 208, 93, 'C', 'phai', 22, 'Kentucky', 2021, 12, 'dang_thi_dau'),
    (96, 'Jarace Walker', NULL, NULL, 'Mỹ', 203, 109, 'PF', 'phai', 5, 'Houston', 2023, 12, 'dang_thi_dau'),

    -- 13 LAC Los Angeles Clippers
    (97, 'Kawhi Leonard', '1991-06-29', 'Los Angeles, California', 'Mỹ', 201, 102, 'SF', 'phai', 2, 'San Diego State', 2011, 13, 'dang_thi_dau'),
    (98, 'James Harden', '1989-08-26', 'Los Angeles, California', 'Mỹ', 196, 100, 'SG', 'trai', 1, 'Arizona State', 2009, 13, 'dang_thi_dau'),
    (99, 'Ivica Zubac', '1997-03-18', 'Mostar', 'Croatia', 213, 109, 'C', 'phai', 40, NULL, 2016, 13, 'dang_thi_dau'),
    (100, 'Bradley Beal', '1993-06-28', 'St. Louis, Missouri', 'Mỹ', 193, 94, 'SG', 'phai', 0, 'Florida', 2012, 13, 'dang_thi_dau'),
    (101, 'John Collins', '1997-09-23', 'Layton, Utah', 'Mỹ', 206, 102, 'PF', 'phai', 20, 'Wake Forest', 2017, 13, 'dang_thi_dau'),
    (102, 'Derrick Jones Jr.', '1997-02-15', 'Chester, Pennsylvania', 'Mỹ', 198, 95, 'SF', 'phai', 55, 'UNLV', 2016, 13, 'dang_thi_dau'),
    (103, 'Kris Dunn', '1994-03-18', 'New London, Connecticut', 'Mỹ', 191, 93, 'PG', 'phai', 8, 'Providence', 2016, 13, 'dang_thi_dau'),
    (104, 'Chris Paul', '1985-05-06', 'Winston-Salem, North Carolina', 'Mỹ', 183, 79, 'PG', 'phai', 3, 'Wake Forest', 2005, 13, 'dang_thi_dau'),

    -- 14 LAL Los Angeles Lakers
    (105, 'Luka Dončić', '1999-02-28', 'Ljubljana', 'Slovenia', 198, 104, 'PG', 'phai', 77, NULL, 2018, 14, 'dang_thi_dau'),
    (106, 'LeBron James', '1984-12-30', 'Akron, Ohio', 'Mỹ', 206, 113, 'SF', 'phai', 23, NULL, 2003, 14, 'dang_thi_dau'),
    (107, 'Austin Reaves', '1998-05-29', 'Newark, Arkansas', 'Mỹ', 196, 89, 'SG', 'phai', 15, 'Oklahoma', 2021, 14, 'dang_thi_dau'),
    (108, 'Deandre Ayton', '1998-07-23', 'Nassau', 'Bahamas', 213, 113, 'C', 'phai', 5, 'Arizona', 2018, 14, 'dang_thi_dau'),
    (109, 'Rui Hachimura', '1998-02-08', 'Toyama', 'Nhật Bản', 203, 104, 'PF', 'phai', 28, 'Gonzaga', 2019, 14, 'dang_thi_dau'),
    (110, 'Marcus Smart', '1994-03-06', 'Flower Mound, Texas', 'Mỹ', 193, 100, 'PG', 'phai', 36, 'Oklahoma State', 2014, 14, 'dang_thi_dau'),
    (111, 'Jaxson Hayes', '2000-05-23', 'Norman, Oklahoma', 'Mỹ', 213, 100, 'C', 'phai', 11, 'Texas', 2018, 14, 'dang_thi_dau'),
    (112, 'Jake LaRavia', NULL, NULL, 'Mỹ', 203, 106, 'SF', 'phai', 12, 'Wake Forest', 2022, 14, 'dang_thi_dau'),

    -- 15 MEM Memphis Grizzlies
    (113, 'Ja Morant', '1999-08-10', 'Dalzell, South Carolina', 'Mỹ', 188, 79, 'PG', 'phai', 12, 'Murray State', 2019, 15, 'dang_thi_dau'),
    (114, 'Jaren Jackson Jr.', '1999-09-15', 'Plainfield, New Jersey', 'Mỹ', 211, 109, 'PF', 'phai', 13, 'Michigan State', 2018, 15, 'dang_thi_dau'),
    (115, 'Zach Edey', '2002-05-14', 'Toronto', 'Canada', 224, 136, 'C', 'phai', 14, 'Purdue', 2024, 15, 'dang_thi_dau'),
    (116, 'Santi Aldama', '2001-01-10', 'Las Palmas', 'Tây Ban Nha', 211, 98, 'PF', 'phai', 7, 'Loyola Maryland', 2021, 15, 'dang_thi_dau'),
    (117, 'Jaylen Wells', NULL, NULL, 'Mỹ', 201, 93, 'SF', 'phai', 0, 'Washington State', 2024, 15, 'dang_thi_dau'),
    (118, 'Kentavious Caldwell-Pope', '1993-02-18', 'Thomaston, Georgia', 'Mỹ', 196, 93, 'SG', 'phai', 5, 'Georgia', 2013, 15, 'dang_thi_dau'),
    (119, 'Ty Jerome', '1997-07-08', 'New Rochelle, New York', 'Mỹ', 196, 88, 'PG', 'phai', 2, 'Virginia', 2019, 15, 'dang_thi_dau'),
    (120, 'Brandon Clarke', '1996-09-19', 'Vancouver', 'Canada', 203, 98, 'PF', 'phai', 15, 'Gonzaga', 2019, 15, 'dang_thi_dau'),

    -- 16 MIA Miami Heat
    (121, 'Bam Adebayo', '1997-07-18', 'Newark, New Jersey', 'Mỹ', 206, 116, 'C', 'phai', 13, 'Kentucky', 2017, 16, 'dang_thi_dau'),
    (122, 'Tyler Herro', '2000-01-20', 'Milwaukee, Wisconsin', 'Mỹ', 196, 88, 'SG', 'phai', 14, 'Kentucky', 2019, 16, 'dang_thi_dau'),
    (123, 'Andrew Wiggins', '1995-02-23', 'Toronto', 'Canada', 201, 89, 'SF', 'phai', 22, 'Kansas', 2014, 16, 'dang_thi_dau'),
    (124, 'Norman Powell', '1993-05-25', 'San Diego, California', 'Mỹ', 191, 98, 'SG', 'phai', 24, 'UCLA', 2015, 16, 'dang_thi_dau'),
    (125, 'Kel''el Ware', NULL, NULL, 'Mỹ', 213, 104, 'C', 'phai', 7, 'Indiana', 2024, 16, 'dang_thi_dau'),
    (126, 'Jaime Jaquez Jr.', '2001-02-18', NULL, 'Mỹ', 198, 102, 'SF', 'phai', 11, 'UCLA', 2023, 16, 'dang_thi_dau'),
    (127, 'Davion Mitchell', '1998-09-05', 'Hinesville, Georgia', 'Mỹ', 183, 92, 'PG', 'phai', 45, 'Baylor', 2021, 16, 'dang_thi_dau'),
    (128, 'Nikola Jović', '2003-06-09', 'Leicester', 'Serbia', 208, 93, 'PF', 'phai', 5, NULL, 2022, 16, 'dang_thi_dau'),

    -- 17 MIL Milwaukee Bucks
    (129, 'Giannis Antetokounmpo', '1994-12-06', 'Athens', 'Hy Lạp', 211, 110, 'PF', 'phai', 34, NULL, 2013, 17, 'dang_thi_dau'),
    (130, 'Myles Turner', '1996-03-24', 'Bedford, Texas', 'Mỹ', 211, 113, 'C', 'phai', 3, 'Texas', 2015, 17, 'dang_thi_dau'),
    (131, 'Kyle Kuzma', '1995-07-24', 'Flint, Michigan', 'Mỹ', 206, 100, 'PF', 'phai', 18, 'Utah', 2017, 17, 'dang_thi_dau'),
    (132, 'Bobby Portis', '1995-02-10', 'Little Rock, Arkansas', 'Mỹ', 208, 113, 'PF', 'phai', 9, 'Arkansas', 2015, 17, 'dang_thi_dau'),
    (133, 'Kevin Porter Jr.', '2000-05-04', 'Seattle, Washington', 'Mỹ', 193, 92, 'SG', 'phai', 7, 'USC', 2019, 17, 'dang_thi_dau'),
    (134, 'Ryan Rollins', NULL, NULL, 'Mỹ', 191, 82, 'PG', 'phai', 13, 'Toledo', 2022, 17, 'dang_thi_dau'),
    (135, 'AJ Green', NULL, NULL, 'Mỹ', 193, 86, 'SG', 'phai', 20, 'Northern Iowa', 2022, 17, 'dang_thi_dau'),
    (136, 'Gary Trent Jr.', '1999-01-18', 'Columbus, Ohio', 'Mỹ', 196, 93, 'SG', 'phai', 5, 'Duke', 2018, 17, 'dang_thi_dau'),

    -- 18 MIN Minnesota Timberwolves
    (137, 'Anthony Edwards', '2001-08-05', 'Atlanta, Georgia', 'Mỹ', 193, 102, 'SG', 'phai', 5, 'Georgia', 2020, 18, 'dang_thi_dau'),
    (138, 'Julius Randle', '1994-11-29', 'Dallas, Texas', 'Mỹ', 206, 113, 'PF', 'trai', 30, 'Kentucky', 2014, 18, 'dang_thi_dau'),
    (139, 'Rudy Gobert', '1992-06-26', 'Saint-Quentin', 'Pháp', 216, 117, 'C', 'phai', 27, NULL, 2013, 18, 'dang_thi_dau'),
    (140, 'Jaden McDaniels', '2000-09-29', NULL, 'Mỹ', 206, 84, 'SF', 'phai', 3, 'Washington', 2020, 18, 'dang_thi_dau'),
    (141, 'Naz Reid', '1999-08-26', 'Asbury Park, New Jersey', 'Mỹ', 206, 120, 'C', 'phai', 11, 'LSU', 2019, 18, 'dang_thi_dau'),
    (142, 'Donte DiVincenzo', '1997-01-31', 'Newark, Delaware', 'Mỹ', 193, 92, 'SG', 'phai', 0, 'Villanova', 2018, 18, 'dang_thi_dau'),
    (143, 'Mike Conley', '1987-10-11', 'Fayetteville, Arkansas', 'Mỹ', 185, 79, 'PG', 'phai', 10, 'Ohio State', 2007, 18, 'dang_thi_dau'),
    (144, 'Rob Dillingham', NULL, NULL, 'Mỹ', 185, 74, 'PG', 'phai', 4, 'Kentucky', 2024, 18, 'dang_thi_dau'),

    -- 19 NOP New Orleans Pelicans
    (145, 'Zion Williamson', '2000-07-06', 'Salisbury, North Carolina', 'Mỹ', 198, 129, 'PF', 'trai', 1, 'Duke', 2019, 19, 'dang_thi_dau'),
    (146, 'Trey Murphy III', '2000-06-18', NULL, 'Mỹ', 203, 93, 'SF', 'phai', 25, 'Virginia', 2021, 19, 'dang_thi_dau'),
    (147, 'Herbert Jones', '1998-10-06', 'Greensboro, Alabama', 'Mỹ', 201, 93, 'SF', 'phai', 5, 'Alabama', 2021, 19, 'dang_thi_dau'),
    (148, 'Jordan Poole', '1999-06-19', 'Milwaukee, Wisconsin', 'Mỹ', 193, 88, 'SG', 'phai', 3, 'Michigan', 2019, 19, 'dang_thi_dau'),
    (149, 'Yves Missi', NULL, NULL, 'Bỉ', 211, 107, 'C', 'phai', 21, 'Baylor', 2024, 19, 'dang_thi_dau'),
    (150, 'Saddiq Bey', '1999-04-09', NULL, 'Mỹ', 201, 98, 'SF', 'phai', 41, 'Villanova', 2020, 19, 'dang_thi_dau'),
    (151, 'Jeremiah Fears', NULL, NULL, 'Mỹ', 193, 82, 'PG', 'phai', 0, 'Oklahoma', 2025, 19, 'dang_thi_dau'),
    (152, 'Derik Queen', NULL, NULL, 'Mỹ', 208, 112, 'C', 'phai', 22, 'Maryland', 2025, 19, 'dang_thi_dau'),

    -- 20 NYK New York Knicks
    (153, 'Jalen Brunson', '1996-08-31', 'New Brunswick, New Jersey', 'Mỹ', 188, 86, 'PG', 'trai', 11, 'Villanova', 2018, 20, 'dang_thi_dau'),
    (154, 'Karl-Anthony Towns', '1995-11-15', 'Edison, New Jersey', 'Mỹ', 213, 112, 'C', 'phai', 32, 'Kentucky', 2015, 20, 'dang_thi_dau'),
    (155, 'Mikal Bridges', '1996-08-30', 'Malvern, Pennsylvania', 'Mỹ', 198, 95, 'SF', 'phai', 25, 'Villanova', 2018, 20, 'dang_thi_dau'),
    (156, 'OG Anunoby', '1997-07-17', 'London', 'Anh', 201, 109, 'SF', 'phai', 8, 'Indiana', 2017, 20, 'dang_thi_dau'),
    (157, 'Josh Hart', '1995-03-06', 'Silver Spring, Maryland', 'Mỹ', 193, 98, 'SG', 'phai', 3, 'Villanova', 2017, 20, 'dang_thi_dau'),
    (158, 'Mitchell Robinson', '1998-04-01', 'Pensacola, Florida', 'Mỹ', 213, 109, 'C', 'phai', 23, NULL, 2018, 20, 'dang_thi_dau'),
    (159, 'Miles McBride', '2000-09-08', 'Cincinnati, Ohio', 'Mỹ', 188, 91, 'PG', 'phai', 2, 'West Virginia', 2021, 20, 'dang_thi_dau'),
    (160, 'Guerschon Yabusele', '1995-12-17', 'Dreux', 'Pháp', 203, 120, 'PF', 'phai', 28, NULL, 2017, 20, 'dang_thi_dau'),

    -- 21 OKC Oklahoma City Thunder
    (161, 'Shai Gilgeous-Alexander', '1998-07-12', 'Toronto', 'Canada', 198, 88, 'PG', 'phai', 2, 'Kentucky', 2018, 21, 'dang_thi_dau'),
    (162, 'Jalen Williams', '2001-04-14', 'Denver, Colorado', 'Mỹ', 196, 95, 'SF', 'phai', 8, 'Santa Clara', 2022, 21, 'dang_thi_dau'),
    (163, 'Chet Holmgren', '2002-05-01', 'Minneapolis, Minnesota', 'Mỹ', 216, 94, 'C', 'phai', 7, 'Gonzaga', 2023, 21, 'dang_thi_dau'),
    (164, 'Isaiah Hartenstein', '1998-05-05', 'Eugene, Oregon', 'Đức', 213, 113, 'C', 'phai', 55, NULL, 2018, 21, 'dang_thi_dau'),
    (165, 'Luguentz Dort', '1999-04-19', 'Montreal', 'Canada', 193, 99, 'SG', 'phai', 5, 'Arizona State', 2019, 21, 'dang_thi_dau'),
    (166, 'Alex Caruso', '1994-02-28', 'College Station, Texas', 'Mỹ', 196, 84, 'SG', 'phai', 9, 'Texas A&M', 2017, 21, 'dang_thi_dau'),
    (167, 'Cason Wallace', '2003-11-07', NULL, 'Mỹ', 191, 88, 'PG', 'phai', 22, 'Kentucky', 2023, 21, 'dang_thi_dau'),
    (168, 'Aaron Wiggins', NULL, NULL, 'Mỹ', 196, 86, 'SG', 'phai', 21, 'Maryland', 2021, 21, 'dang_thi_dau'),

    -- 22 ORL Orlando Magic
    (169, 'Paolo Banchero', '2002-11-12', 'Seattle, Washington', 'Mỹ', 208, 113, 'PF', 'phai', 5, 'Duke', 2022, 22, 'dang_thi_dau'),
    (170, 'Franz Wagner', '2001-08-27', 'Berlin', 'Đức', 208, 100, 'SF', 'phai', 22, 'Michigan', 2021, 22, 'dang_thi_dau'),
    (171, 'Jalen Suggs', '2001-06-03', 'Saint Paul, Minnesota', 'Mỹ', 193, 93, 'PG', 'phai', 4, 'Gonzaga', 2021, 22, 'dang_thi_dau'),
    (172, 'Desmond Bane', '1998-06-25', 'Richmond, Indiana', 'Mỹ', 196, 98, 'SG', 'phai', 3, 'TCU', 2020, 22, 'dang_thi_dau'),
    (173, 'Wendell Carter Jr.', '1999-04-16', 'Atlanta, Georgia', 'Mỹ', 208, 122, 'C', 'phai', 34, 'Duke', 2018, 22, 'dang_thi_dau'),
    (174, 'Anthony Black', NULL, NULL, 'Mỹ', 201, 90, 'PG', 'phai', 0, 'Arkansas', 2023, 22, 'dang_thi_dau'),
    (175, 'Goga Bitadze', '1999-07-20', NULL, 'Gruzia', 211, 113, 'C', 'phai', 35, NULL, 2019, 22, 'dang_thi_dau'),
    (176, 'Tyus Jones', '1996-05-10', 'Burnsville, Minnesota', 'Mỹ', 183, 89, 'PG', 'phai', 10, 'Duke', 2015, 22, 'dang_thi_dau'),

    -- 23 PHI Philadelphia 76ers
    (177, 'Joel Embiid', '1994-03-16', 'Yaoundé', 'Cameroon', 213, 127, 'C', 'phai', 21, 'Kansas', 2016, 23, 'dang_thi_dau'),
    (178, 'Tyrese Maxey', '2000-11-04', 'Garland, Texas', 'Mỹ', 188, 90, 'PG', 'phai', 0, 'Kentucky', 2020, 23, 'dang_thi_dau'),
    (179, 'Paul George', '1990-05-02', 'Palmdale, California', 'Mỹ', 203, 100, 'SF', 'phai', 8, 'Fresno State', 2010, 23, 'dang_thi_dau'),
    (180, 'VJ Edgecombe', NULL, NULL, 'Bahamas', 193, 82, 'SG', 'phai', 77, 'Baylor', 2025, 23, 'dang_thi_dau'),
    (181, 'Kelly Oubre Jr.', '1995-12-09', 'New Orleans, Louisiana', 'Mỹ', 201, 92, 'SF', 'trai', 9, 'Kansas', 2015, 23, 'dang_thi_dau'),
    (182, 'Quentin Grimes', NULL, NULL, 'Mỹ', 196, 95, 'SG', 'phai', 5, 'Houston', 2021, 23, 'dang_thi_dau'),
    (183, 'Andre Drummond', '1993-08-10', 'Mount Vernon, New York', 'Mỹ', 211, 127, 'C', 'phai', 1, 'UConn', 2012, 23, 'dang_thi_dau'),
    (184, 'Jared McCain', NULL, NULL, 'Mỹ', 188, 89, 'PG', 'phai', 20, 'Duke', 2024, 23, 'dang_thi_dau'),

    -- 24 PHO Phoenix Suns
    (185, 'Devin Booker', '1996-10-30', 'Grand Rapids, Michigan', 'Mỹ', 196, 93, 'SG', 'phai', 1, 'Kentucky', 2015, 24, 'dang_thi_dau'),
    (186, 'Jalen Green', '2002-02-09', 'Merced, California', 'Mỹ', 193, 84, 'SG', 'phai', 4, NULL, 2021, 24, 'dang_thi_dau'),
    (187, 'Dillon Brooks', '1996-01-22', 'Mississauga, Ontario', 'Canada', 201, 102, 'SF', 'phai', 3, 'Oregon', 2017, 24, 'dang_thi_dau'),
    (188, 'Mark Williams', NULL, NULL, 'Mỹ', 216, 109, 'C', 'phai', 15, 'Duke', 2022, 24, 'dang_thi_dau'),
    (189, 'Grayson Allen', '1995-10-08', 'Chandler, Arizona', 'Mỹ', 193, 90, 'SG', 'phai', 8, 'Duke', 2018, 24, 'dang_thi_dau'),
    (190, 'Ryan Dunn', NULL, NULL, 'Mỹ', 203, 98, 'SF', 'phai', 0, 'Virginia', 2024, 24, 'dang_thi_dau'),
    (191, 'Collin Gillespie', NULL, NULL, 'Mỹ', 185, 88, 'PG', 'phai', 12, 'Villanova', 2022, 24, 'dang_thi_dau'),
    (192, 'Khaman Maluach', NULL, NULL, 'Nam Sudan', 218, 114, 'C', 'phai', 10, 'Duke', 2025, 24, 'dang_thi_dau'),

    -- 25 POR Portland Trail Blazers
    (193, 'Deni Avdija', '2001-01-03', 'Beit Zera', 'Israel', 206, 103, 'SF', 'phai', 8, NULL, 2020, 25, 'dang_thi_dau'),
    (194, 'Shaedon Sharpe', '2003-05-30', 'London, Ontario', 'Canada', 196, 91, 'SG', 'phai', 17, NULL, 2022, 25, 'dang_thi_dau'),
    (195, 'Jrue Holiday', '1990-06-12', 'Chatsworth, California', 'Mỹ', 193, 93, 'PG', 'phai', 5, 'UCLA', 2009, 25, 'dang_thi_dau'),
    (196, 'Donovan Clingan', '2004-02-23', 'Bristol, Connecticut', 'Mỹ', 218, 127, 'C', 'phai', 23, 'UConn', 2024, 25, 'dang_thi_dau'),
    (197, 'Jerami Grant', '1994-03-12', 'Portland, Oregon', 'Mỹ', 203, 95, 'PF', 'phai', 9, 'Syracuse', 2014, 25, 'dang_thi_dau'),
    (198, 'Toumani Camara', '2000-05-08', 'Brussels', 'Bỉ', 203, 100, 'PF', 'phai', 33, 'Dayton', 2023, 25, 'dang_thi_dau'),
    (199, 'Yang Hansen', NULL, NULL, 'Trung Quốc', 216, 113, 'C', 'phai', 16, NULL, 2025, 25, 'dang_thi_dau'),
    (200, 'Damian Lillard', '1990-07-15', 'Oakland, California', 'Mỹ', 188, 88, 'PG', 'phai', 0, 'Weber State', 2012, 25, 'dang_thi_dau'),

    -- 26 SAC Sacramento Kings
    (201, 'Domantas Sabonis', '1996-05-03', 'Portland, Oregon', 'Litva', 213, 109, 'C', 'phai', 10, 'Gonzaga', 2016, 26, 'dang_thi_dau'),
    (202, 'Zach LaVine', '1995-03-10', 'Renton, Washington', 'Mỹ', 196, 91, 'SG', 'phai', 8, 'UCLA', 2014, 26, 'dang_thi_dau'),
    (203, 'DeMar DeRozan', '1989-08-07', 'Compton, California', 'Mỹ', 198, 100, 'SF', 'phai', 9, 'USC', 2009, 26, 'dang_thi_dau'),
    (204, 'Keegan Murray', '2000-08-19', 'Cedar Rapids, Iowa', 'Mỹ', 203, 98, 'PF', 'phai', 13, 'Iowa', 2022, 26, 'dang_thi_dau'),
    (205, 'Malik Monk', '1998-02-04', NULL, 'Mỹ', 191, 91, 'SG', 'phai', 0, 'Kentucky', 2017, 26, 'dang_thi_dau'),
    (206, 'Dennis Schröder', '1993-09-15', 'Braunschweig', 'Đức', 185, 79, 'PG', 'phai', 17, NULL, 2013, 26, 'dang_thi_dau'),
    (207, 'Russell Westbrook', '1988-11-12', 'Long Beach, California', 'Mỹ', 193, 91, 'PG', 'phai', 18, 'UCLA', 2008, 26, 'dang_thi_dau'),
    (208, 'Keon Ellis', NULL, NULL, 'Mỹ', 193, 79, 'SG', 'phai', 23, 'Alabama', 2022, 26, 'dang_thi_dau'),

    -- 27 SAS San Antonio Spurs
    (209, 'Victor Wembanyama', '2004-01-04', 'Le Chesnay', 'Pháp', 224, 107, 'C', 'phai', 1, NULL, 2023, 27, 'dang_thi_dau'),
    (210, 'De''Aaron Fox', '1997-12-20', 'New Orleans, Louisiana', 'Mỹ', 191, 84, 'PG', 'phai', 4, 'Kentucky', 2017, 27, 'dang_thi_dau'),
    (211, 'Stephon Castle', '2004-11-01', 'Covington, Georgia', 'Mỹ', 198, 98, 'PG', 'phai', 5, 'UConn', 2024, 27, 'dang_thi_dau'),
    (212, 'Dylan Harper', NULL, NULL, 'Mỹ', 196, 98, 'SG', 'trai', 2, 'Rutgers', 2025, 27, 'dang_thi_dau'),
    (213, 'Devin Vassell', '2000-08-23', 'Suwanee, Georgia', 'Mỹ', 196, 91, 'SG', 'phai', 24, 'Florida State', 2020, 27, 'dang_thi_dau'),
    (214, 'Harrison Barnes', '1992-05-30', 'Ames, Iowa', 'Mỹ', 203, 102, 'PF', 'phai', 40, 'North Carolina', 2012, 27, 'dang_thi_dau'),
    (215, 'Keldon Johnson', '1999-10-11', 'Chesterfield, Virginia', 'Mỹ', 196, 100, 'SF', 'phai', 3, 'Kentucky', 2019, 27, 'dang_thi_dau'),
    (216, 'Jeremy Sochan', '2003-05-20', 'Guymon, Oklahoma', 'Ba Lan', 203, 104, 'PF', 'phai', 10, 'Baylor', 2022, 27, 'dang_thi_dau'),

    -- 28 TOR Toronto Raptors
    (217, 'Scottie Barnes', '2001-08-01', 'West Palm Beach, Florida', 'Mỹ', 201, 102, 'PF', 'phai', 4, 'Florida State', 2021, 28, 'dang_thi_dau'),
    (218, 'Brandon Ingram', '1997-09-02', 'Kinston, North Carolina', 'Mỹ', 203, 86, 'SF', 'phai', 3, 'Duke', 2016, 28, 'dang_thi_dau'),
    (219, 'RJ Barrett', '2000-06-14', 'Toronto', 'Canada', 198, 97, 'SF', 'trai', 9, 'Duke', 2019, 28, 'dang_thi_dau'),
    (220, 'Immanuel Quickley', '1999-06-17', 'Havre de Grace, Maryland', 'Mỹ', 188, 86, 'PG', 'phai', 5, 'Kentucky', 2020, 28, 'dang_thi_dau'),
    (221, 'Jakob Poeltl', '1995-10-15', 'Vienna', 'Áo', 216, 111, 'C', 'phai', 19, 'Utah', 2016, 28, 'dang_thi_dau'),
    (222, 'Gradey Dick', '2003-11-20', 'Wichita, Kansas', 'Mỹ', 198, 93, 'SG', 'phai', 1, 'Kansas', 2023, 28, 'dang_thi_dau'),
    (223, 'Ja''Kobe Walter', NULL, NULL, 'Mỹ', 196, 89, 'SG', 'phai', 14, 'Baylor', 2024, 28, 'dang_thi_dau'),
    (224, 'Jamal Shead', NULL, NULL, 'Mỹ', 185, 91, 'PG', 'phai', 23, 'Houston', 2024, 28, 'dang_thi_dau'),

    -- 29 UTA Utah Jazz
    (225, 'Lauri Markkanen', '1997-05-22', 'Vantaa', 'Phần Lan', 213, 109, 'PF', 'phai', 23, 'Arizona', 2017, 29, 'dang_thi_dau'),
    (226, 'Keyonte George', '2003-11-08', NULL, 'Mỹ', 193, 84, 'PG', 'phai', 3, 'Baylor', 2023, 29, 'dang_thi_dau'),
    (227, 'Walker Kessler', '2001-07-26', NULL, 'Mỹ', 213, 111, 'C', 'phai', 24, 'Auburn', 2022, 29, 'dang_thi_dau'),
    (228, 'Ace Bailey', NULL, NULL, 'Mỹ', 203, 91, 'SF', 'phai', 19, 'Rutgers', 2025, 29, 'dang_thi_dau'),
    (229, 'Isaiah Collier', NULL, NULL, 'Mỹ', 191, 95, 'PG', 'phai', 13, 'USC', 2024, 29, 'dang_thi_dau'),
    (230, 'Jusuf Nurkić', '1994-08-23', 'Tuzla', 'Bosnia và Herzegovina', 211, 132, 'C', 'phai', 30, NULL, 2014, 29, 'dang_thi_dau'),
    (231, 'Kyle Filipowski', NULL, NULL, 'Mỹ', 211, 112, 'C', 'phai', 22, 'Duke', 2024, 29, 'dang_thi_dau'),
    (232, 'Taylor Hendricks', NULL, NULL, 'Mỹ', 206, 95, 'PF', 'phai', 0, 'UCF', 2023, 29, 'dang_thi_dau'),

    -- 30 WAS Washington Wizards
    (233, 'Alex Sarr', '2005-04-26', 'Toulouse', 'Pháp', 213, 102, 'C', 'phai', 20, NULL, 2024, 30, 'dang_thi_dau'),
    (234, 'Bub Carrington', NULL, NULL, 'Mỹ', 193, 86, 'PG', 'phai', 8, 'Pittsburgh', 2024, 30, 'dang_thi_dau'),
    (235, 'Bilal Coulibaly', '2004-07-26', NULL, 'Pháp', 198, 88, 'SF', 'phai', 0, NULL, 2023, 30, 'dang_thi_dau'),
    (236, 'Kyshawn George', NULL, NULL, 'Thụy Sĩ', 203, 91, 'SF', 'phai', 18, 'Miami', 2024, 30, 'dang_thi_dau'),
    (237, 'CJ McCollum', '1991-09-19', 'Canton, Ohio', 'Mỹ', 191, 86, 'SG', 'phai', 3, 'Lehigh', 2013, 30, 'dang_thi_dau'),
    (238, 'Khris Middleton', '1991-08-12', 'Charleston, South Carolina', 'Mỹ', 201, 100, 'SF', 'phai', 22, 'Texas A&M', 2012, 30, 'dang_thi_dau'),
    (239, 'Corey Kispert', NULL, NULL, 'Mỹ', 198, 102, 'SF', 'phai', 24, 'Gonzaga', 2021, 30, 'dang_thi_dau'),
    (240, 'Tre Johnson', NULL, NULL, 'Mỹ', 196, 86, 'SG', 'phai', 12, 'Texas', 2025, 30, 'dang_thi_dau'),

    -- Đã giải nghệ (không có đội)
    (241, 'Derrick Rose', '1988-10-04', 'Chicago, Illinois', 'Mỹ', 191, 91, 'PG', 'phai', NULL, 'Memphis', 2008, NULL, 'giai_nghe'),
    (242, 'Blake Griffin', '1989-03-16', 'Oklahoma City, Oklahoma', 'Mỹ', 206, 113, 'PF', 'phai', NULL, 'Oklahoma', 2009, NULL, 'giai_nghe');

-- 2. CHUYỂN NHƯỢNG (theo thứ tự thời gian; giao dịch cuối của mỗi người khớp với đội hiện tại)
INSERT INTO chuyen_nhuong (ma_chuyen_nhuong, ma_cau_thu, loai, ma_doi_cu, ma_doi_moi, ngay_chuyen, ghi_chu) VALUES
    ( 1, 105, 'trao_doi', 7, 14, '2025-02-02', 'Trao đổi lấy Anthony Davis'),  -- Luka Dončić
    ( 2,  49, 'trao_doi', 14, 7, '2025-02-02', 'Trao đổi lấy Luka Dončić'),  -- Anthony Davis
    ( 3, 210, 'trao_doi', 26, 27, '2025-02-03', 'Thương vụ 3 đội'),  -- De'Aaron Fox
    ( 4, 202, 'trao_doi', 5, 26, '2025-02-03', 'Thương vụ 3 đội'),  -- Zach LaVine
    ( 5,  74, 'trao_doi', 16, 10, '2025-02-06', NULL),  -- Jimmy Butler
    ( 6, 123, 'trao_doi', 10, 16, '2025-02-06', NULL),  -- Andrew Wiggins
    ( 7, 218, 'trao_doi', 19, 28, '2025-02-06', NULL),  -- Brandon Ingram
    ( 8,  51, 'tuyen_chon_draft', NULL, 7, '2025-06-25', 'Lượt chọn số 1 Draft 2025'),  -- Cooper Flagg
    ( 9, 212, 'tuyen_chon_draft', NULL, 27, '2025-06-25', 'Lượt chọn số 2 Draft 2025'),  -- Dylan Harper
    (10, 180, 'tuyen_chon_draft', NULL, 23, '2025-06-25', 'Lượt chọn số 3 Draft 2025'),  -- VJ Edgecombe
    (11, 108, 'giai_phong', 25, NULL, '2025-06-30', 'Thỏa thuận chấm dứt hợp đồng'),  -- Deandre Ayton
    (12, 200, 'giai_phong', 17, NULL, '2025-07-01', NULL),  -- Damian Lillard
    (13,  81, 'trao_doi', 24, 11, '2025-07-06', NULL),  -- Kevin Durant
    (14, 186, 'trao_doi', 11, 24, '2025-07-06', 'Trong thương vụ Kevin Durant'),  -- Jalen Green
    (15, 187, 'trao_doi', 11, 24, '2025-07-06', 'Trong thương vụ Kevin Durant'),  -- Dillon Brooks
    (16, 172, 'trao_doi', 15, 22, '2025-07-07', NULL),  -- Desmond Bane
    (17,   4, 'trao_doi', 2, 1, '2025-07-07', NULL),  -- Kristaps Porziņģis
    (18, 195, 'trao_doi', 2, 25, '2025-07-07', NULL),  -- Jrue Holiday
    (19,  13, 'trao_doi', 25, 2, '2025-07-07', NULL),  -- Anfernee Simons
    (20,  61, 'trao_doi', 3, 8, '2025-07-07', NULL),  -- Cameron Johnson
    (21,  17, 'trao_doi', 8, 3, '2025-07-07', NULL),  -- Michael Porter Jr.
    (22, 130, 'ky_hop_dong', 12, 17, '2025-07-07', 'Ký hợp đồng khi là cầu thủ tự do'),  -- Myles Turner
    (23, 108, 'ky_hop_dong', NULL, 14, '2025-07-07', NULL),  -- Deandre Ayton
    (24, 200, 'ky_hop_dong', NULL, 25, '2025-07-19', 'Trở lại đội cũ');  -- Damian Lillard

-- 3. CHẤN THƯƠNG (lịch sử thật, đều đã hồi phục; demo "đang chấn thương" thì thêm qua trang quản trị)
INSERT INTO chan_thuong (ma_chan_thuong, ma_cau_thu, loai_chan_thuong, ngay_bi, ngay_du_kien_tro_lai, da_hoi_phuc, ghi_chu) VALUES
    (1,  50, 'Đứt dây chằng chéo trước đầu gối trái', '2025-03-03', NULL, 1, NULL),  -- Kyrie Irving
    (2, 200, 'Đứt gân Achilles trái', '2025-04-27', NULL, 1, NULL),  -- Damian Lillard
    (3,   9, 'Đứt gân Achilles phải', '2025-05-12', NULL, 1, NULL),  -- Jayson Tatum
    (4,  89, 'Đứt gân Achilles phải', '2025-06-22', NULL, 1, 'Chấn thương ở trận 7 Chung kết NBA 2025');  -- Tyrese Haliburton
