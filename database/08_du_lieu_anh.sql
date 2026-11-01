-- =====================================================================
--  File: database/08_du_lieu_anh.sql  (tham khảo - TV5 Nguyễn Tiến Đạt)
--  Gán ẢNH cho cầu thủ (235/242), HLV (40/46), sân thi đấu (30/30) và LOGO 30 đội bằng LINK ẢNH TRÊN MẠNG.
--  Nguồn ảnh: Wikimedia Commons (ảnh có giấy phép tự do, là ảnh chính trong bài Wikipedia
--  tiếng Anh của từng người / từng sân). Link dạng Special:FilePath/<tên file>?width=400.
--  KHÔNG dùng ảnh của NBA.com (có bản quyền).
--  LƯU Ý LOGO ĐỘI: logo NBA là nhãn hiệu có bản quyền. Phần lớn file logo trên Wikipedia
--  (upload.wikimedia.org/wikipedia/en/...) là ảnh KHÔNG tự do, Wikipedia chỉ được dùng trong bài viết
--  của họ. Ở đây chỉ dùng cho bài tập môn học, không đưa web lên công khai.
--
--  Import SAU 02_du_lieu_doi_bong.sql và 03_du_lieu_cau_thu.sql (xếp cuối: ... -> 07 -> 08).
--  Chỉ gán khi cột ảnh đang TRỐNG -> không ghi đè ảnh admin đã tải lên (uploads/...).
--  Web hiển thị được cả link https://... lẫn file uploads/ nhờ hàm urlAnh() (includes/ham_chung.php).
--  Cần Internet khi xem web. Mất mạng hoặc người chưa có ảnh -> hiện chữ viết tắt tên (anhTron)
--  hoặc khung biểu tượng sân (anhSan).
--
--  Chưa tìm được ảnh tự do (hiện chữ viết tắt):
--    Cầu thủ: Dereck Lively II, Brandin Podziemski, Kel'el Ware, Trey Murphy III, Yang Hansen, Ace Bailey, Isaiah Collier
--    HLV    : Charles Lee, James Borrego, Jamahl Mosley, Jordan Ott, Taylor Jenkins, Joe Prunty
-- =====================================================================
USE qlbongro;

-- 1. Cầu thủ (ma_cau_thu theo 03_du_lieu_cau_thu.sql)
DROP TEMPORARY TABLE IF EXISTS tmp_anh;
CREATE TEMPORARY TABLE tmp_anh (ma INT PRIMARY KEY, anh VARCHAR(255) NOT NULL);
INSERT INTO tmp_anh (ma, anh) VALUES
    (  1, 'https://commons.wikimedia.org/wiki/Special:FilePath/Trae_Young_%282022_All-Star_Weekend%29_%28cropped%29.jpg?width=400'),  -- Trae Young
    (  2, 'https://commons.wikimedia.org/wiki/Special:FilePath/Dyson_Daniels_%282025%29.jpg?width=400'),  -- Dyson Daniels
    (  3, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jalen-Johnson.jpg?width=400'),  -- Jalen Johnson
    (  4, 'https://commons.wikimedia.org/wiki/Special:FilePath/Celtics_at_Wizards_2024-12-005_%28cropped%29.jpg?width=400'),  -- Kristaps Porziņģis
    (  5, 'https://commons.wikimedia.org/wiki/Special:FilePath/Onyeka_Okongwu_jump_ball_%28cropped%29.jpg?width=400'),  -- Onyeka Okongwu
    (  6, 'https://commons.wikimedia.org/wiki/Special:FilePath/Zaccharie_Risacher_All_Star_Game_%28cropped%29.jpg?width=400'),  -- Zaccharie Risacher
    (  7, 'https://commons.wikimedia.org/wiki/Special:FilePath/2023-08-09_Deutschland_gegen_Kanada_%28Basketball-L%C3%A4nderspiel%29_by_Sandro_Halank%E2%80%93067.jpg?width=400'),  -- Nickeil Alexander-Walker
    (  8, 'https://commons.wikimedia.org/wiki/Special:FilePath/Wizards_vs._Clippers_-_51848728899_%28cropped%29.jpg?width=400'),  -- Luke Kennard
    (  9, 'https://commons.wikimedia.org/wiki/Special:FilePath/Celtics_at_Wizards_2024-12-044_%28cropped_2%29.jpg?width=400'),  -- Jayson Tatum
    ( 10, 'https://commons.wikimedia.org/wiki/Special:FilePath/Celtics_at_Wizards_2024-12-015_%28cropped%29_%28cropped%29.jpg?width=400'),  -- Jaylen Brown
    ( 11, 'https://commons.wikimedia.org/wiki/Special:FilePath/Boston_Celtics_player_Derrick_White_on_November_21%2C_2024_at_the_White_House_%28cropped%29.jpg?width=400'),  -- Derrick White
    ( 12, 'https://commons.wikimedia.org/wiki/Special:FilePath/Celtics_at_Wizards_2024-12-027_%28cropped%29.jpg?width=400'),  -- Payton Pritchard
    ( 13, 'https://commons.wikimedia.org/wiki/Special:FilePath/AnferneeSimons_%28cropped%29.jpg?width=400'),  -- Anfernee Simons
    ( 14, 'https://commons.wikimedia.org/wiki/Special:FilePath/Celtics_at_Wizards_2024-12-024_%28cropped%29.jpg?width=400'),  -- Sam Hauser
    ( 15, 'https://commons.wikimedia.org/wiki/Special:FilePath/Celtics_at_Wizards_2024-12-022_%28cropped%29.jpg?width=400'),  -- Neemias Queta
    ( 16, 'https://commons.wikimedia.org/wiki/Special:FilePath/Hugo_Gonz%C3%A1lez_9_Real_Madrid_Baloncesto_Euroleague_20250206_%286%29.jpg?width=400'),  -- Hugo González
    ( 17, 'https://commons.wikimedia.org/wiki/Special:FilePath/Michael_Porter_Nuggets_%28cropped%29.jpg?width=400'),  -- Michael Porter Jr.
    ( 18, 'https://commons.wikimedia.org/wiki/Special:FilePath/Cameron_Thomas_%2851874870188%29_%28cropped%29.jpg?width=400'),  -- Cam Thomas
    ( 19, 'https://commons.wikimedia.org/wiki/Special:FilePath/Nicolas_Claxton_%2849165813553%29_%28cropped%29.jpg?width=400'),  -- Nic Claxton
    ( 20, 'https://commons.wikimedia.org/wiki/Special:FilePath/Noah_Clowney_%28BkrQTK80TE8%29.png?width=400'),  -- Noah Clowney
    ( 21, 'https://commons.wikimedia.org/wiki/Special:FilePath/Tmann.jpg?width=400'),  -- Terance Mann
    ( 22, 'https://commons.wikimedia.org/wiki/Special:FilePath/Dayron_Sharpe_%2851833854120%29_%28cropped%29.jpg?width=400'),  -- Day'Ron Sharpe
    ( 23, 'https://commons.wikimedia.org/wiki/Special:FilePath/Egor_Demin_-_BYU_Blue_vs._White_2024_%28cropped%29.jpg?width=400'),  -- Egor Dëmin
    ( 24, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jalen_Wilson_%28cropped%29.jpg?width=400'),  -- Jalen Wilson
    ( 25, 'https://commons.wikimedia.org/wiki/Special:FilePath/LaMelo_Ball_%28cropped%29.jpg?width=400'),  -- LaMelo Ball
    ( 26, 'https://commons.wikimedia.org/wiki/Special:FilePath/Brandon_Miller_Alabama_%28cropped%29.jpg?width=400'),  -- Brandon Miller
    ( 27, 'https://commons.wikimedia.org/wiki/Special:FilePath/Miles_Bridges_2019.jpg?width=400'),  -- Miles Bridges
    ( 28, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kon_Knueppel.jpg?width=400'),  -- Kon Knueppel
    ( 29, 'https://commons.wikimedia.org/wiki/Special:FilePath/Collin_Sexton_%2851658041101%29_%28cropped2%29.jpg?width=400'),  -- Collin Sexton
    ( 30, 'https://commons.wikimedia.org/wiki/Special:FilePath/Moussa_Diabate_2022_%28cropped%29.jpg?width=400'),  -- Moussa Diabaté
    ( 31, 'https://commons.wikimedia.org/wiki/Special:FilePath/Josh_Green_2021_%28cropped%29.jpg?width=400'),  -- Josh Green
    ( 32, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ryan_Kalkbrenner_2024.jpg?width=400'),  -- Ryan Kalkbrenner
    ( 33, 'https://commons.wikimedia.org/wiki/Special:FilePath/Josh_Giddey_2022.jpg?width=400'),  -- Josh Giddey
    ( 34, 'https://commons.wikimedia.org/wiki/Special:FilePath/White_with_the_Chicago_Bulls_on_January_2022.jpg?width=400'),  -- Coby White
    ( 35, 'https://commons.wikimedia.org/wiki/Special:FilePath/Nikola_Vu%C4%8Devi%C4%87_4_Montenegro_20250823_%285%29_%28cropped%29.jpg?width=400'),  -- Nikola Vučević
    ( 36, 'https://commons.wikimedia.org/wiki/Special:FilePath/Matas_Buzelis_Jan_14_2026.jpg?width=400'),  -- Matas Buzelis
    ( 37, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ayo_Dosunmu_with_the_Chicago_Bulls_in_January_1%2C_2022.jpg?width=400'),  -- Ayo Dosunmu
    ( 38, 'https://commons.wikimedia.org/wiki/Special:FilePath/PatrickWilliamsFSU2019_%28cropped%29.jpg?width=400'),  -- Patrick Williams
    ( 39, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kevin_Huerter_2020_%28cropped%29.jpg?width=400'),  -- Kevin Huerter
    ( 40, 'https://commons.wikimedia.org/wiki/Special:FilePath/Isaac_Okoro_%2851175618087%29_%28cropped%29.jpg?width=400'),  -- Isaac Okoro
    ( 41, 'https://commons.wikimedia.org/wiki/Special:FilePath/Donovan_Mitchell_Pregame.jpg?width=400'),  -- Donovan Mitchell
    ( 42, 'https://commons.wikimedia.org/wiki/Special:FilePath/Darius_Garland_2021-2022.jpg?width=400'),  -- Darius Garland
    ( 43, 'https://commons.wikimedia.org/wiki/Special:FilePath/Evan_Mobley_%28cropped%29.jpg?width=400'),  -- Evan Mobley
    ( 44, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jarrett_Allen_%2851657225262%29_%28cropped%29.jpg?width=400'),  -- Jarrett Allen
    ( 45, 'https://commons.wikimedia.org/wiki/Special:FilePath/De%27Andre_Hunter_%2851636889796%29.jpg?width=400'),  -- De'Andre Hunter
    ( 46, 'https://commons.wikimedia.org/wiki/Special:FilePath/Max_Strus_-_53368732580_%28cropped%29.jpg?width=400'),  -- Max Strus
    ( 47, 'https://commons.wikimedia.org/wiki/Special:FilePath/Sam_Merrill_%28cropped%29.jpg?width=400'),  -- Sam Merrill
    ( 48, 'https://commons.wikimedia.org/wiki/Special:FilePath/Lonzo_Ball_%2849487881388%29_%28cropped%29.jpg?width=400'),  -- Lonzo Ball
    ( 49, 'https://commons.wikimedia.org/wiki/Special:FilePath/Anthony_Davis_pre-game_%28cropped%29.jpg?width=400'),  -- Anthony Davis
    ( 50, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kyrie_Irving_%2851830909437%29_%28cropped%29.jpg?width=400'),  -- Kyrie Irving
    ( 51, 'https://commons.wikimedia.org/wiki/Special:FilePath/Duke_at_UNC%2C_Mar_2025%2C_Flagg.jpg?width=400'),  -- Cooper Flagg
    ( 52, 'https://commons.wikimedia.org/wiki/Special:FilePath/Klay_Thompson_%28cropped%29.jpg?width=400'),  -- Klay Thompson
    ( 53, 'https://commons.wikimedia.org/wiki/Special:FilePath/P._J._Washington_Jr._%28cropped%29.jpg?width=400'),  -- P.J. Washington
    ( 55, 'https://commons.wikimedia.org/wiki/Special:FilePath/WizardsRaptors_35_%2851589304503%29_%28cropped%29.jpg?width=400'),  -- Daniel Gafford
    ( 56, 'https://commons.wikimedia.org/wiki/Special:FilePath/D%27Angelo_Russell_%2851733686752%29_%28cropped%29_%28cropped%29.jpg?width=400'),  -- D'Angelo Russell
    ( 57, 'https://commons.wikimedia.org/wiki/Special:FilePath/Nikola_Jokic_free_throw_%28cropped%29.jpg?width=400'),  -- Nikola Jokić
    ( 58, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jamal_Murray_free_throw_%28cropped%29.jpg?width=400'),  -- Jamal Murray
    ( 59, 'https://commons.wikimedia.org/wiki/Special:FilePath/Aaron_Gordon_2019_%28cropped%29.jpg?width=400'),  -- Aaron Gordon
    ( 60, 'https://commons.wikimedia.org/wiki/Special:FilePath/Christian_Braun_2023_parade_%28cropped%29.jpg?width=400'),  -- Christian Braun
    ( 61, 'https://commons.wikimedia.org/wiki/Special:FilePath/CamJohnsonCC_%28cropped%29.png?width=400'),  -- Cameron Johnson
    ( 62, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jonas_Valanciunas_2022_by_Augustas_Didzgalvis_%28cropped%29.jpg?width=400'),  -- Jonas Valančiūnas
    ( 63, 'https://commons.wikimedia.org/wiki/Special:FilePath/Bruce_Brown_2021.png?width=400'),  -- Bruce Brown
    ( 64, 'https://commons.wikimedia.org/wiki/Special:FilePath/Peyton_Watson_2025_%2854856827608%29_%28cropped%29.jpg?width=400'),  -- Peyton Watson
    ( 65, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_cade_cunningham_2024.jpg?width=400'),  -- Cade Cunningham
    ( 66, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jalen_Duren_Free_Throw_01252026_%28cropped%29_2.jpg?width=400'),  -- Jalen Duren
    ( 67, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ausar_Thompson_free_throw_1252026_%28cropped%29.jpg?width=400'),  -- Ausar Thompson
    ( 68, 'https://commons.wikimedia.org/wiki/Special:FilePath/Tobias_Harris_%2837823548241%29_%28cropped%29.jpg?width=400'),  -- Tobias Harris
    ( 69, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ivey_Pistons.jpg?width=400'),  -- Jaden Ivey
    ( 70, 'https://commons.wikimedia.org/wiki/Special:FilePath/Isaiah_Stewart_2022_%28cropped%29.jpg?width=400'),  -- Isaiah Stewart
    ( 71, 'https://commons.wikimedia.org/wiki/Special:FilePath/Heat_Duncan_Robinson_%28cropped%29.jpg?width=400'),  -- Duncan Robinson
    ( 72, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ron_Holland_%28241117-F-XI802-1065%29_%28cropped%29.jpg?width=400'),  -- Ron Holland
    ( 73, 'https://commons.wikimedia.org/wiki/Special:FilePath/Stephen_Curry%2C_Olympic_Games_2024_%28cropped%29.jpg?width=400'),  -- Stephen Curry
    ( 74, 'https://commons.wikimedia.org/wiki/Special:FilePath/CES_2026_-_Jimmy_Butler_01_%28cropped%29.jpg?width=400'),  -- Jimmy Butler
    ( 75, 'https://commons.wikimedia.org/wiki/Special:FilePath/Draymond_Green_2022.jpg?width=400'),  -- Draymond Green
    ( 76, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jonathan_Kuminga_2_%28cropped%29.jpg?width=400'),  -- Jonathan Kuminga
    ( 78, 'https://commons.wikimedia.org/wiki/Special:FilePath/Moses_Moody_White_House_2023.jpg?width=400'),  -- Moses Moody
    ( 79, 'https://commons.wikimedia.org/wiki/Special:FilePath/Buddy_Playing_for_the_Bahamas_2023_%28cropped%29.jpg?width=400'),  -- Buddy Hield
    ( 80, 'https://commons.wikimedia.org/wiki/Special:FilePath/Al_Horford_of_the_Boston_Celtics_during_an_event_celebrating_the_team%E2%80%99s_2024_NBA_Championship_on_November_21%2C_2024_on_the_South_Lawn_of_the_White_House_%28cropped%29.jpg?width=400'),  -- Al Horford
    ( 81, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kevin_Durant%2C_Paris_2024_%28cropped%29.jpg?width=400'),  -- Kevin Durant
    ( 82, 'https://commons.wikimedia.org/wiki/Special:FilePath/Alperen_%C5%9Eeng%C3%BCn_23_T%C3%BCrkiye_20250823_%281%29.jpg?width=400'),  -- Alperen Şengün
    ( 83, 'https://commons.wikimedia.org/wiki/Special:FilePath/Amen_Thompson_2026.jpg?width=400'),  -- Amen Thompson
    ( 84, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_jabari_smith_jr.jpg?width=400'),  -- Jabari Smith Jr.
    ( 85, 'https://commons.wikimedia.org/wiki/Special:FilePath/Fred_VanVleet_%2851915270294%29_%28cropped%29.jpg?width=400'),  -- Fred VanVleet
    ( 86, 'https://commons.wikimedia.org/wiki/Special:FilePath/Steven_Adams_%2832049572304%29.jpg?width=400'),  -- Steven Adams
    ( 87, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_tari_eason.jpg?width=400'),  -- Tari Eason
    ( 88, 'https://commons.wikimedia.org/wiki/Special:FilePath/Reed_Sheppard_Kentucky_v._Kansas.jpg?width=400'),  -- Reed Sheppard
    ( 89, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_tyrese_haliburton_2025_%28cropped_2%29.jpg?width=400'),  -- Tyrese Haliburton
    ( 90, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_pascal_siakam_2025_%28cropped%29.jpg?width=400'),  -- Pascal Siakam
    ( 91, 'https://commons.wikimedia.org/wiki/Special:FilePath/Andrew_Nembhard_Gonzaga_%28cropped%29.jpg?width=400'),  -- Andrew Nembhard
    ( 92, 'https://commons.wikimedia.org/wiki/Special:FilePath/Aaron_Nesmith_%2851688510120%29_%28cropped%29.jpg?width=400'),  -- Aaron Nesmith
    ( 93, 'https://commons.wikimedia.org/wiki/Special:FilePath/Obi_Toppin_%282022_All-Star_Weekend%29_%28cropped%29.jpg?width=400'),  -- Obi Toppin
    ( 94, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_tj_mcconnell_2025_%28cropped%29.jpg?width=400'),  -- T.J. McConnell
    ( 95, 'https://commons.wikimedia.org/wiki/Special:FilePath/Isaiah_Jackson_%28cropped%29.jpg?width=400'),  -- Isaiah Jackson
    ( 96, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_jarace_walker_2025.jpg?width=400'),  -- Jarace Walker
    ( 97, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kawhi_Leonard_%287440607%29_%28cropped%29.jpg?width=400'),  -- Kawhi Leonard
    ( 98, 'https://commons.wikimedia.org/wiki/Special:FilePath/Harden_dribbling_midcourt%2C_Cavaliers_vs_Nets_on_January_17%2C_2022_%28cropped%29.jpg?width=400'),  -- James Harden
    ( 99, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ivica_Zubac_%28cropped2%29.jpg?width=400'),  -- Ivica Zubac
    (100, 'https://commons.wikimedia.org/wiki/Special:FilePath/Bradley_Beal_WSH_Wizards_2022_%28croppedface%29.jpg?width=400'),  -- Bradley Beal
    (101, 'https://commons.wikimedia.org/wiki/Special:FilePath/John_Collins_%285397460%29_%28cropped%29.jpg?width=400'),  -- John Collins
    (102, 'https://commons.wikimedia.org/wiki/Special:FilePath/Derrick_Jones_Jr_2022.jpg?width=400'),  -- Derrick Jones Jr.
    (103, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kris_Dunn.jpg?width=400'),  -- Kris Dunn
    (104, 'https://commons.wikimedia.org/wiki/Special:FilePath/Chris_Paul_%282022_All-Star_Weekend%29_%28cropped%29.jpg?width=400'),  -- Chris Paul
    (105, 'https://commons.wikimedia.org/wiki/Special:FilePath/Luka_Don%C4%8Di%C4%87_and_Marines%2C_2026_%28cropped%29.jpg?width=400'),  -- Luka Dončić
    (106, 'https://commons.wikimedia.org/wiki/Special:FilePath/LeBron_James_%2851959977144%29_%28cropped2%29.jpg?width=400'),  -- LeBron James
    (107, 'https://commons.wikimedia.org/wiki/Special:FilePath/Austin_Reaves_drives.jpg?width=400'),  -- Austin Reaves
    (108, 'https://commons.wikimedia.org/wiki/Special:FilePath/Deande_Ayton_Bahamas.jpg?width=400'),  -- Deandre Ayton
    (109, 'https://commons.wikimedia.org/wiki/Special:FilePath/Rui_Hachimura_%2852381582438%29.jpg?width=400'),  -- Rui Hachimura
    (110, 'https://commons.wikimedia.org/wiki/Special:FilePath/MarcusSmart2021_%28cropped%29.jpg?width=400'),  -- Marcus Smart
    (111, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jaxson_hayes.jpg?width=400'),  -- Jaxson Hayes
    (112, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jake_LaRavia_%28cropped%29.jpg?width=400'),  -- Jake LaRavia
    (113, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ja_Morant_2021.jpg?width=400'),  -- Ja Morant
    (114, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jaren_Jackson_%2851814052094%29_%28cropped%29.jpg?width=400'),  -- Jaren Jackson Jr.
    (115, 'https://commons.wikimedia.org/wiki/Special:FilePath/Zach_Edey_2_%28cropped%29.jpg?width=400'),  -- Zach Edey
    (116, 'https://commons.wikimedia.org/wiki/Special:FilePath/Santi_Aldama_%2851812767732%29_%28cropped%29.jpg?width=400'),  -- Santi Aldama
    (117, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jaylen_Wells_%28240910-F-UM667-4692%29_%28cropped%29.jpg?width=400'),  -- Jaylen Wells
    (118, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kentavious_Caldwell_Pope_%2851874793366%29_%28cropped%29.jpg?width=400'),  -- Kentavious Caldwell-Pope
    (119, 'https://commons.wikimedia.org/wiki/Special:FilePath/TY_JEROME_%28cropped%29.jpg?width=400'),  -- Ty Jerome
    (120, 'https://commons.wikimedia.org/wiki/Special:FilePath/Brandon_Clarke_in_2020.jpg?width=400'),  -- Brandon Clarke
    (121, 'https://commons.wikimedia.org/wiki/Special:FilePath/Bam_Adebayo_%28cropped%29.jpg?width=400'),  -- Bam Adebayo
    (122, 'https://commons.wikimedia.org/wiki/Special:FilePath/Tyler_Herro_%28cropped%29.jpg?width=400'),  -- Tyler Herro
    (123, 'https://commons.wikimedia.org/wiki/Special:FilePath/Andrew_Wiggins_2022.jpg?width=400'),  -- Andrew Wiggins
    (124, 'https://commons.wikimedia.org/wiki/Special:FilePath/Norman_Powell_at_2017_Toronto_Film_Festival.jpg?width=400'),  -- Norman Powell
    (126, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jaime_Jaquez_Jr_2021_%28cropped%29.jpg?width=400'),  -- Jaime Jaquez Jr.
    (127, 'https://commons.wikimedia.org/wiki/Special:FilePath/Davion_Mitchell.jpg?width=400'),  -- Davion Mitchell
    (128, 'https://commons.wikimedia.org/wiki/Special:FilePath/Nikola-Jovic-2024.jpg?width=400'),  -- Nikola Jović
    (129, 'https://commons.wikimedia.org/wiki/Special:FilePath/Giannis_Antetokounmpo_%2851915153421%29_%28cropped%29.jpg?width=400'),  -- Giannis Antetokounmpo
    (130, 'https://commons.wikimedia.org/wiki/Special:FilePath/Myles_Turner_%2839916223764%29_%28cropped%29.jpg?width=400'),  -- Myles Turner
    (131, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kyle_Kuzma_%2851815879993%29_%28cropped%29.jpg?width=400'),  -- Kyle Kuzma
    (132, 'https://commons.wikimedia.org/wiki/Special:FilePath/Bobby_Portis_2021_%28cropped%29.jpg?width=400'),  -- Bobby Portis
    (133, 'https://commons.wikimedia.org/wiki/Special:FilePath/KPJ_%28cropped%29.jpg?width=400'),  -- Kevin Porter Jr.
    (134, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ryan_Rollins%2C_Toledo_Rockets_MBB%2C_February_2022.jpg?width=400'),  -- Ryan Rollins
    (135, 'https://commons.wikimedia.org/wiki/Special:FilePath/AJ_Green_Basketball_Picture.jpg?width=400'),  -- AJ Green
    (136, 'https://commons.wikimedia.org/wiki/Special:FilePath/WizardsRaptors_114_%2851589980770%29_%28cropped%29.jpg?width=400'),  -- Gary Trent Jr.
    (137, 'https://commons.wikimedia.org/wiki/Special:FilePath/Anthony_Edwards_Argentina_v_Egypt_7_July_2026-069_%28cropped%29.jpg?width=400'),  -- Anthony Edwards
    (138, 'https://commons.wikimedia.org/wiki/Special:FilePath/Julius_Randle_with_Lakers.jpg?width=400'),  -- Julius Randle
    (139, 'https://commons.wikimedia.org/wiki/Special:FilePath/Rudy_Gobert.jpg?width=400'),  -- Rudy Gobert
    (140, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jaden_McDaniels_2023_Crawsover_Pro-Am_02.png?width=400'),  -- Jaden McDaniels
    (141, 'https://commons.wikimedia.org/wiki/Special:FilePath/Naz_Reid_%2851733687822%29_%28cropped%29.jpg?width=400'),  -- Naz Reid
    (142, 'https://commons.wikimedia.org/wiki/Special:FilePath/Donte_DiVincenzo_%28White_House_visit%29.jpg?width=400'),  -- Donte DiVincenzo
    (143, 'https://commons.wikimedia.org/wiki/Special:FilePath/Mike_Conley_cropped_20131118_Clippers_v_Grizzles.jpg?width=400'),  -- Mike Conley
    (144, 'https://commons.wikimedia.org/wiki/Special:FilePath/Rob_Dillingham_Kentucky_v._Kansas.jpg?width=400'),  -- Rob Dillingham
    (145, 'https://commons.wikimedia.org/wiki/Special:FilePath/Zion_Williamson_2020_%28cropped%29.jpg?width=400'),  -- Zion Williamson
    (147, 'https://commons.wikimedia.org/wiki/Special:FilePath/Herb_Jones.jpg?width=400'),  -- Herbert Jones
    (148, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_jordan_poole_2024.jpg?width=400'),  -- Jordan Poole
    (149, 'https://commons.wikimedia.org/wiki/Special:FilePath/Yves_Missi.jpg?width=400'),  -- Yves Missi
    (150, 'https://commons.wikimedia.org/wiki/Special:FilePath/Saddiq_Bey_20260826_%28cropped%29.JPG?width=400'),  -- Saddiq Bey
    (151, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jeremiah_Fears_at_Oklahoma.png?width=400'),  -- Jeremiah Fears
    (152, 'https://commons.wikimedia.org/wiki/Special:FilePath/Derik_Queen_%2854424016534%29_%28cropped%29.jpg?width=400'),  -- Derik Queen
    (153, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jalen_Brunson_2023_%28cropped%29.jpg?width=400'),  -- Jalen Brunson
    (154, 'https://commons.wikimedia.org/wiki/Special:FilePath/Karl-Anthony_Towns_%2851914283512%29_%28cropped%29_%28cropped%29.jpg?width=400'),  -- Karl-Anthony Towns
    (155, 'https://commons.wikimedia.org/wiki/Special:FilePath/Mikal_Bridges%2C_April_2024.jpg?width=400'),  -- Mikal Bridges
    (156, 'https://commons.wikimedia.org/wiki/Special:FilePath/OG_Anunoby_%2841708749222%29_%28cropped%29.jpg?width=400'),  -- OG Anunoby
    (157, 'https://commons.wikimedia.org/wiki/Special:FilePath/Josh_Hart_-_52977163784_%28cropped%29.jpg?width=400'),  -- Josh Hart
    (158, 'https://commons.wikimedia.org/wiki/Special:FilePath/Mitchell_Robinson_-_52976499477_%28cropped%29.jpg?width=400'),  -- Mitchell Robinson
    (159, 'https://commons.wikimedia.org/wiki/Special:FilePath/Deuce_McBride_interview%2C_2025_%2801%29.png?width=400'),  -- Miles McBride
    (160, 'https://commons.wikimedia.org/wiki/Special:FilePath/Guerschon_Yabusele_Roanne.jpg?width=400'),  -- Guerschon Yabusele
    (161, 'https://commons.wikimedia.org/wiki/Special:FilePath/2023-08-09_Deutschland_gegen_Kanada_%28Basketball-L%C3%A4nderspiel%29_by_Sandro_Halank%E2%80%93109.jpg?width=400'),  -- Shai Gilgeous-Alexander
    (162, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jalen_Williams_OKCThunder_2025_NBA_Cup_%28cropped%29_%28cropped%29.jpg?width=400'),  -- Jalen Williams
    (163, 'https://commons.wikimedia.org/wiki/Special:FilePath/Chet_Holmgren.jpg?width=400'),  -- Chet Holmgren
    (164, 'https://commons.wikimedia.org/wiki/Special:FilePath/Isaiah_Hartenstein_OKC_Thunder_2025_NBA_Cup_%28cropped%29.jpg?width=400'),  -- Isaiah Hartenstein
    (165, 'https://commons.wikimedia.org/wiki/Special:FilePath/2023-08-09_Deutschland_gegen_Kanada_%28Basketball-L%C3%A4nderspiel%29_by_Sandro_Halank%E2%80%93062.jpg?width=400'),  -- Luguentz Dort
    (166, 'https://commons.wikimedia.org/wiki/Special:FilePath/Alex_Caruso_%2852480104636%29_%28cropped%29.jpg?width=400'),  -- Alex Caruso
    (167, 'https://commons.wikimedia.org/wiki/Special:FilePath/Cason_Wallace_-_MBB_v._Vanderbilt_-_March_01%2C_2023_by_Brady_Saylor_-_0099.jpg?width=400'),  -- Cason Wallace
    (168, 'https://commons.wikimedia.org/wiki/Special:FilePath/Aaron_Wiggins_vs._Michigan_%28cropped%29.jpg?width=400'),  -- Aaron Wiggins
    (169, 'https://commons.wikimedia.org/wiki/Special:FilePath/Paolo_Banchero.png?width=400'),  -- Paolo Banchero
    (170, 'https://commons.wikimedia.org/wiki/Special:FilePath/2023-08-09_Deutschland_gegen_Kanada_%28Basketball-L%C3%A4nderspiel%29_by_Sandro_Halank%E2%80%93138.jpg?width=400'),  -- Franz Wagner
    (171, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jalen_Suggs_20260826_%282%29_%28cropped%29.JPG?width=400'),  -- Jalen Suggs
    (172, 'https://commons.wikimedia.org/wiki/Special:FilePath/Desmond_Bane%2C_Magic_vs_Raptors.png?width=400'),  -- Desmond Bane
    (173, 'https://commons.wikimedia.org/wiki/Special:FilePath/Wendell_Carter_Jr._%28cropped%29.jpg?width=400'),  -- Wendell Carter Jr.
    (174, 'https://commons.wikimedia.org/wiki/Special:FilePath/Anthony_Black.jpg?width=400'),  -- Anthony Black
    (175, 'https://commons.wikimedia.org/wiki/Special:FilePath/Goga_Bitadze_Orlando_Magic_2025_%28cropped%29.jpg?width=400'),  -- Goga Bitadze
    (176, 'https://commons.wikimedia.org/wiki/Special:FilePath/Tyus_Jones_2021_%28cropped%29.jpg?width=400'),  -- Tyus Jones
    (177, 'https://commons.wikimedia.org/wiki/Special:FilePath/Joel_Embiid_2019.jpg?width=400'),  -- Joel Embiid
    (178, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_tyrese_maxey_2026.jpg?width=400'),  -- Tyrese Maxey
    (179, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_paul_george_2026_%28cropped%29.jpg?width=400'),  -- Paul George
    (180, 'https://commons.wikimedia.org/wiki/Special:FilePath/VJ_BAHAMAS_OQT_VALENCIA.jpg?width=400'),  -- VJ Edgecombe
    (181, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kelly_Oubre_Jr.%284%29_%2850595057598%29.jpg?width=400'),  -- Kelly Oubre Jr.
    (182, 'https://commons.wikimedia.org/wiki/Special:FilePath/Quentin_Grimes_%28cropped%29.jpg?width=400'),  -- Quentin Grimes
    (183, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_andre_drummond_2026.jpg?width=400'),  -- Andre Drummond
    (184, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jared_McCain_Duke_v._Michigan_State.jpg?width=400'),  -- Jared McCain
    (185, 'https://commons.wikimedia.org/wiki/Special:FilePath/Devin_Booker%2C_Olympic_Games_2024_%28cropped%29.jpg?width=400'),  -- Devin Booker
    (186, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jalen_Green_2022_%28cropped%29.jpg?width=400'),  -- Jalen Green
    (187, 'https://commons.wikimedia.org/wiki/Special:FilePath/2023-08-09_Deutschland_gegen_Kanada_%28Basketball-L%C3%A4nderspiel%29_by_Sandro_Halank%E2%80%93091.jpg?width=400'),  -- Dillon Brooks
    (188, 'https://commons.wikimedia.org/wiki/Special:FilePath/Mark_Williams.png?width=400'),  -- Mark Williams
    (189, 'https://commons.wikimedia.org/wiki/Special:FilePath/Grayson_Allen%2C_Wizards_vs._Bucks_on_November_7%2C_2021.jpg?width=400'),  -- Grayson Allen
    (190, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ryan_Dunn_%28cropped%29.jpg?width=400'),  -- Ryan Dunn
    (191, 'https://commons.wikimedia.org/wiki/Special:FilePath/Collin_Gillespie_2021_%28cropped%29.jpg?width=400'),  -- Collin Gillespie
    (192, 'https://commons.wikimedia.org/wiki/Special:FilePath/Duke_at_UNC%2C_Mar_2025%2C_Maluach.jpg?width=400'),  -- Khaman Maluach
    (193, 'https://commons.wikimedia.org/wiki/Special:FilePath/Deni_Avdija_2022_%2852380408867%29_%28cropped%29.jpg?width=400'),  -- Deni Avdija
    (194, 'https://commons.wikimedia.org/wiki/Special:FilePath/Shaedon_Sharpe_2022_%28cropped%29.jpg?width=400'),  -- Shaedon Sharpe
    (195, 'https://commons.wikimedia.org/wiki/Special:FilePath/Celtics_at_Wizards_2024-12-021_%28cropped%29.jpg?width=400'),  -- Jrue Holiday
    (196, 'https://commons.wikimedia.org/wiki/Special:FilePath/Donovan_Clingan_White_House_%28cropped%29.jpg?width=400'),  -- Donovan Clingan
    (197, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jerami_Grant_Free_Throw.jpg?width=400'),  -- Jerami Grant
    (198, 'https://commons.wikimedia.org/wiki/Special:FilePath/Toumani_Camara_2024_%28cropped2%29.jpg?width=400'),  -- Toumani Camara
    (200, 'https://commons.wikimedia.org/wiki/Special:FilePath/Damian_Lillard_%282021%29_%28cropped%29.jpg?width=400'),  -- Damian Lillard
    (201, 'https://commons.wikimedia.org/wiki/Special:FilePath/Domantas_Sabonis_by_Augustas_Didzgalvis_%28cropped%29.jpg?width=400'),  -- Domantas Sabonis
    (202, 'https://commons.wikimedia.org/wiki/Special:FilePath/Zach_LaVine_%282022_All-Star_Weekend%29.jpg?width=400'),  -- Zach LaVine
    (203, 'https://commons.wikimedia.org/wiki/Special:FilePath/DeMar_DeRozan_2022.jpg?width=400'),  -- DeMar DeRozan
    (204, 'https://commons.wikimedia.org/wiki/Special:FilePath/Keegan_Murray_2022_%28cropped%29.jpg?width=400'),  -- Keegan Murray
    (205, 'https://commons.wikimedia.org/wiki/Special:FilePath/Malik_Monk_-_51960020214_%28cropped2%29.jpg?width=400'),  -- Malik Monk
    (206, 'https://commons.wikimedia.org/wiki/Special:FilePath/2022-07-03_Basketball%2C_M%C3%A4nner%2C_European_Qualifiers%2C_Deutschland_-_Polen_1DX_1386_by_Stepro_%28cropped%29.jpg?width=400'),  -- Dennis Schröder
    (207, 'https://commons.wikimedia.org/wiki/Special:FilePath/Russell_Westbrook_%28March_21%2C_2022%29_%28cropped%29.jpg?width=400'),  -- Russell Westbrook
    (208, 'https://commons.wikimedia.org/wiki/Special:FilePath/Keon_Ellis.jpg?width=400'),  -- Keon Ellis
    (209, 'https://commons.wikimedia.org/wiki/Special:FilePath/Victor_Wembanyama_San_Antonio_Spurs_2024.jpg?width=400'),  -- Victor Wembanyama
    (210, 'https://commons.wikimedia.org/wiki/Special:FilePath/De%27Aaron_Fox_%28cropped%29.jpg?width=400'),  -- De'Aaron Fox
    (211, 'https://commons.wikimedia.org/wiki/Special:FilePath/Stephon_Castle_in_2026_Western_Conference_Finals_%28cropped%29.jpg?width=400'),  -- Stephon Castle
    (212, 'https://commons.wikimedia.org/wiki/Special:FilePath/Dylan_Harper_in_2026_%28cropped%29.jpg?width=400'),  -- Dylan Harper
    (213, 'https://commons.wikimedia.org/wiki/Special:FilePath/Devin_Vassell_with_the_Spurs_in_2026.jpg?width=400'),  -- Devin Vassell
    (214, 'https://commons.wikimedia.org/wiki/Special:FilePath/Harrison_Barnes_in_2019_%28cropped%29.jpg?width=400'),  -- Harrison Barnes
    (215, 'https://commons.wikimedia.org/wiki/Special:FilePath/Keldon_Johnson_in_2026_Western_Conference_Finals.jpg?width=400'),  -- Keldon Johnson
    (216, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jeremy_Sochan_San_Antonio_Spurs_2024.jpg?width=400'),  -- Jeremy Sochan
    (217, 'https://commons.wikimedia.org/wiki/Special:FilePath/Scottie_Barnes%2C_Wizards_vs_Raptors_on_October_12%2C_2021.jpg?width=400'),  -- Scottie Barnes
    (218, 'https://commons.wikimedia.org/wiki/Special:FilePath/Brandon_Ingram_2020_%28cropped2%29.jpg?width=400'),  -- Brandon Ingram
    (219, 'https://commons.wikimedia.org/wiki/Special:FilePath/2023-08-09_Deutschland_gegen_Kanada_%28Basketball-L%C3%A4nderspiel%29_by_Sandro_Halank%E2%80%93063.jpg?width=400'),  -- RJ Barrett
    (220, 'https://commons.wikimedia.org/wiki/Special:FilePath/Immanuel_Quickley_FanDuel_interview_2.png?width=400'),  -- Immanuel Quickley
    (221, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jakob_Poeltl_%2839692527595%29.jpg?width=400'),  -- Jakob Poeltl
    (222, 'https://commons.wikimedia.org/wiki/Special:FilePath/Gradey_Dick.jpg?width=400'),  -- Gradey Dick
    (223, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ja%27Kobe_Walter.jpg?width=400'),  -- Ja'Kobe Walter
    (224, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jamal_Shead%2C_2025_%28cropped%29.png?width=400'),  -- Jamal Shead
    (225, 'https://commons.wikimedia.org/wiki/Special:FilePath/Lauri_Markkanen%2C_Cavaliers_vs_Rockets_on_December_15%2C_2021_%28cropped%29.jpg?width=400'),  -- Lauri Markkanen
    (226, 'https://commons.wikimedia.org/wiki/Special:FilePath/Keyonte_George_Jan_14_2026.jpg?width=400'),  -- Keyonte George
    (227, 'https://commons.wikimedia.org/wiki/Special:FilePath/Walker_Kessler_%28cropped%29.jpg?width=400'),  -- Walker Kessler
    (230, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jusuf_Nurki%C4%87_2021.jpg?width=400'),  -- Jusuf Nurkić
    (231, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kyle_Filipowski_11_14_2023.jpg?width=400'),  -- Kyle Filipowski
    (232, 'https://commons.wikimedia.org/wiki/Special:FilePath/Taylor_Hendricks_Jan_14_2026.jpg?width=400'),  -- Taylor Hendricks
    (233, 'https://commons.wikimedia.org/wiki/Special:FilePath/2025_Washington_Wizards_Media_Day_13.jpg?width=400'),  -- Alex Sarr
    (234, 'https://commons.wikimedia.org/wiki/Special:FilePath/2025_Washington_Wizards_Media_Day_61.jpg?width=400'),  -- Bub Carrington
    (235, 'https://commons.wikimedia.org/wiki/Special:FilePath/2025_Washington_Wizards_Media_Day_56.jpg?width=400'),  -- Bilal Coulibaly
    (236, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kyshawn_George_2025_Washington_Wizards_Media_Day.jpg?width=400'),  -- Kyshawn George
    (237, 'https://commons.wikimedia.org/wiki/Special:FilePath/2025_C.J._McCollum_%28cropped%29.jpg?width=400'),  -- CJ McCollum
    (238, 'https://commons.wikimedia.org/wiki/Special:FilePath/2025_Khris_Middleton_%28cropped%29.jpg?width=400'),  -- Khris Middleton
    (239, 'https://commons.wikimedia.org/wiki/Special:FilePath/Corey_Kispert_2025_Washington_Wizards_Media_Day_55.jpg?width=400'),  -- Corey Kispert
    (240, 'https://commons.wikimedia.org/wiki/Special:FilePath/Tre_Johnson_Media_Day.jpg?width=400'),  -- Tre Johnson
    (241, 'https://commons.wikimedia.org/wiki/Special:FilePath/Derrick_Rose_03.jpg?width=400'),  -- Derrick Rose
    (242, 'https://commons.wikimedia.org/wiki/Special:FilePath/Blake_Griffin_with_ball_20131118_Clippers_v_Grizzles.jpg?width=400');  -- Blake Griffin
UPDATE cau_thu c JOIN tmp_anh t ON t.ma = c.ma_cau_thu
SET c.anh = t.anh
WHERE c.anh IS NULL OR c.anh = '';

-- 2. Huấn luyện viên (ma_hlv theo 02_du_lieu_doi_bong.sql)
TRUNCATE TABLE tmp_anh;
INSERT INTO tmp_anh (ma, anh) VALUES
    (  1, 'https://commons.wikimedia.org/wiki/Special:FilePath/Head_Coach_Quin_Snyder.jpg?width=400'),  -- Quin Snyder
    (  2, 'https://commons.wikimedia.org/wiki/Special:FilePath/Celtics_at_Wizards_2024-12-046_%28cropped%29_%28cropped%29.jpg?width=400'),  -- Joe Mazzulla
    (  3, 'https://commons.wikimedia.org/wiki/Special:FilePath/2023-08-09_Deutschland_gegen_Kanada_%28Basketball-L%C3%A4nderspiel%29_by_Sandro_Halank%E2%80%93161.jpg?width=400'),  -- Jordi Fernández
    (  5, 'https://commons.wikimedia.org/wiki/Special:FilePath/Billy_Donovan_2014.jpg?width=400'),  -- Billy Donovan
    (  6, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kenny_Atkinson_20260826_%283%29_%28cropped%29.JPG?width=400'),  -- Kenny Atkinson
    (  7, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jason_Kidd_Nets_coach_cropped.jpg?width=400'),  -- Jason Kidd
    (  8, 'https://commons.wikimedia.org/wiki/Special:FilePath/David_Adelman_interview%2C_2025.png?width=400'),  -- David Adelman
    (  9, 'https://commons.wikimedia.org/wiki/Special:FilePath/J.B._Bickerstaff_%28cropped%29.jpg?width=400'),  -- J. B. Bickerstaff
    ( 10, 'https://commons.wikimedia.org/wiki/Special:FilePath/Joe_Biden_Steph_Curry_Steve_Kerr_P20230117AS-1347_%28cropped%29_%28cropped%29.jpg?width=400'),  -- Steve Kerr
    ( 11, 'https://commons.wikimedia.org/wiki/Special:FilePath/ImeUdoka2022.jpg?width=400'),  -- Ime Udoka
    ( 12, 'https://commons.wikimedia.org/wiki/Special:FilePath/Rick_Carlisle_OPHS_Drive_and_Dish_Presser_2023-11-15_%28cropped2%29.jpg?width=400'),  -- Rick Carlisle
    ( 13, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ty_Lue_Sideline_2022_%28cropped%29.jpg?width=400'),  -- Tyronn Lue
    ( 14, 'https://commons.wikimedia.org/wiki/Special:FilePath/Tomas_Satoransky%2C_JJ_Redick_%28cropped%29.jpg?width=400'),  -- JJ Redick
    ( 15, 'https://commons.wikimedia.org/wiki/Special:FilePath/2019-10-26_Basketball%2C_easyCredit_Basketball-Bundesliga%2C_Syntainics_MBC_%28Mitteldeutscher_BC%29_-_Hakro_Merlins_Crailsheim_1DX_2926_by_Stepro.jpg?width=400'),  -- Tuomas Iisalo
    ( 16, 'https://commons.wikimedia.org/wiki/Special:FilePath/Erik_Spoelstra_2022_%28cropped%29.jpg?width=400'),  -- Erik Spoelstra
    ( 17, 'https://commons.wikimedia.org/wiki/Special:FilePath/Rivers_76ers.jpg?width=400'),  -- Doc Rivers
    ( 18, 'https://commons.wikimedia.org/wiki/Special:FilePath/Chris_Finch_interview%2C_Dec_28_2024_%2801%29.png?width=400'),  -- Chris Finch
    ( 20, 'https://commons.wikimedia.org/wiki/Special:FilePath/Mike_Brown_NBA_cropped.jpg?width=400'),  -- Mike Brown
    ( 21, 'https://commons.wikimedia.org/wiki/Special:FilePath/Mark_Daigneault.png?width=400'),  -- Mark Daigneault
    ( 23, 'https://commons.wikimedia.org/wiki/Special:FilePath/1_nick_nurse_2026.jpg?width=400'),  -- Nick Nurse
    ( 25, 'https://commons.wikimedia.org/wiki/Special:FilePath/2024-10-29_ALBA_Berlin_gegen_Paris_Basketball_%28EuroLeague_2024-25%29_by_Sandro_Halank%E2%80%93132.jpg?width=400'),  -- Tiago Splitter
    ( 26, 'https://commons.wikimedia.org/wiki/Special:FilePath/Doug_Christie_cropped.jpg?width=400'),  -- Doug Christie
    ( 27, 'https://commons.wikimedia.org/wiki/Special:FilePath/Mitch_Johnson_%28cropped%29.png?width=400'),  -- Mitch Johnson
    ( 28, 'https://commons.wikimedia.org/wiki/Special:FilePath/Darko_Rajakovi%C4%87_pregame_interview.png?width=400'),  -- Darko Rajaković
    ( 29, 'https://commons.wikimedia.org/wiki/Special:FilePath/WillHardy_2023_%28cropped%29.jpg?width=400'),  -- Will Hardy
    ( 30, 'https://commons.wikimedia.org/wiki/Special:FilePath/2025_Washington_Wizards_Media_Day_10.jpg?width=400'),  -- Brian Keefe
    ( 32, 'https://commons.wikimedia.org/wiki/Special:FilePath/Willie_Green_Clippers.jpg?width=400'),  -- Willie Green
    ( 33, 'https://commons.wikimedia.org/wiki/Special:FilePath/Billups_coach_%28cropped%29.jpg?width=400'),  -- Chauncey Billups
    ( 34, 'https://commons.wikimedia.org/wiki/Special:FilePath/Tom_Thibodeau_-_52977484738_%28cropped%29.jpg?width=400'),  -- Tom Thibodeau
    ( 35, 'https://commons.wikimedia.org/wiki/Special:FilePath/CMuwGzp.jpg?width=400'),  -- Mike Budenholzer
    ( 36, 'https://commons.wikimedia.org/wiki/Special:FilePath/Frank_Vogel_NBA_TV_%28cropped%29.jpeg?width=400'),  -- Frank Vogel
    ( 37, 'https://commons.wikimedia.org/wiki/Special:FilePath/Michael_Malone_2026.png?width=400'),  -- Michael Malone
    ( 38, 'https://commons.wikimedia.org/wiki/Special:FilePath/Gregg_Popovich_speaks_at_the_White_House_2015-01-12_%28cropped%29.jpg?width=400'),  -- Gregg Popovich
    ( 39, 'https://commons.wikimedia.org/wiki/Special:FilePath/Monty_Williams_%28cropped%29.jpg?width=400'),  -- Monty Williams
    ( 40, 'https://commons.wikimedia.org/wiki/Special:FilePath/Darvin_Ham_White_House_2021.jpg?width=400'),  -- Darvin Ham
    ( 41, 'https://commons.wikimedia.org/wiki/Special:FilePath/Steve_Clifford_in_2020.jpg?width=400'),  -- Steve Clifford
    ( 42, 'https://commons.wikimedia.org/wiki/Special:FilePath/Jacque_Vaughn_%28Feb_2024%29_01.png?width=400'),  -- Jacque Vaughn
    ( 43, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kevin_Ollie_140507-D-HU462-369_%28cropped%29.jpg?width=400'),  -- Kevin Ollie
    ( 44, 'https://commons.wikimedia.org/wiki/Special:FilePath/Adrian_Griffin.jpg?width=400'),  -- Adrian Griffin
    ( 46, 'https://commons.wikimedia.org/wiki/Special:FilePath/Wes_Unseld_Jr_%28cropped%29.jpg?width=400');  -- Wes Unseld Jr.
UPDATE huan_luyen_vien h JOIN tmp_anh t ON t.ma = h.ma_hlv
SET h.anh = t.anh
WHERE h.anh IS NULL OR h.anh = '';

-- 3. Sân thi đấu (ma_san theo 02_du_lieu_doi_bong.sql)
TRUNCATE TABLE tmp_anh;
INSERT INTO tmp_anh (ma, anh) VALUES
    (  1, 'https://commons.wikimedia.org/wiki/Special:FilePath/State_Farm_%28Philips%29_Arena%2C_Atlanta%2C_GA_%2846558861525%29_-_2019.jpg?width=400'),  -- State Farm Arena
    (  2, 'https://commons.wikimedia.org/wiki/Special:FilePath/TD_Garden_%2854960947755%29.jpg?width=400'),  -- TD Garden
    (  3, 'https://commons.wikimedia.org/wiki/Special:FilePath/Barclays_Center_-_2026_%2855268559424%29.jpg?width=400'),  -- Barclays Center
    (  4, 'https://commons.wikimedia.org/wiki/Special:FilePath/Spectrum_Center_2018.jpg?width=400'),  -- Spectrum Center
    (  5, 'https://commons.wikimedia.org/wiki/Special:FilePath/United_Center_1.jpg?width=400'),  -- United Center
    (  6, 'https://commons.wikimedia.org/wiki/Special:FilePath/Rocket_Mortgage_FieldHouse_2022_%28cropped%29.png?width=400'),  -- Rocket Arena
    (  7, 'https://commons.wikimedia.org/wiki/Special:FilePath/American_Airlines_Center_August_2015.jpg?width=400'),  -- American Airlines Center
    (  8, 'https://commons.wikimedia.org/wiki/Special:FilePath/Ball_Arena_exterior_2022.jpg?width=400'),  -- Ball Arena
    (  9, 'https://commons.wikimedia.org/wiki/Special:FilePath/Detroit_August_2025_07_%28Little_Caesars_Arena%29.jpg?width=400'),  -- Little Caesars Arena
    ( 10, 'https://commons.wikimedia.org/wiki/Special:FilePath/Chase_Center.jpg?width=400'),  -- Chase Center
    ( 11, 'https://commons.wikimedia.org/wiki/Special:FilePath/Toyota_Center_entr.jpg?width=400'),  -- Toyota Center
    ( 12, 'https://commons.wikimedia.org/wiki/Special:FilePath/Bankers_Life_Fieldhouse%2C_Indian%C3%A1polis%2C_Estados_Unidos%2C_2012-10-22%2C_DD_02.jpg?width=400'),  -- Gainbridge Fieldhouse
    ( 13, 'https://commons.wikimedia.org/wiki/Special:FilePath/Intuit_Dome_Fa%C3%A7ade.jpg?width=400'),  -- Intuit Dome
    ( 14, 'https://commons.wikimedia.org/wiki/Special:FilePath/Crypto.com_Arena_exterior_2023.jpg?width=400'),  -- Crypto.com Arena
    ( 15, 'https://commons.wikimedia.org/wiki/Special:FilePath/FedExForum_at_night.jpg?width=400'),  -- FedExForum
    ( 16, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kaseya_Center_Downtown_Miami_FL%2C_5_April_2024.jpg?width=400'),  -- Kaseya Center
    ( 17, 'https://commons.wikimedia.org/wiki/Special:FilePath/Milwaukee_Fiserv_Forum.jpg?width=400'),  -- Fiserv Forum
    ( 18, 'https://commons.wikimedia.org/wiki/Special:FilePath/TargetCenter.jpg?width=400'),  -- Target Center
    ( 19, 'https://commons.wikimedia.org/wiki/Special:FilePath/New_Orleans_Arena%2C_exterior_view%2C_10_January_2022_%28cropped%29.jpg?width=400'),  -- Smoothie King Center
    ( 20, 'https://commons.wikimedia.org/wiki/Special:FilePath/Madison_Square_Garden_%28MSG%29_-_Full_%2848124330357%29.jpg?width=400'),  -- Madison Square Garden
    ( 21, 'https://commons.wikimedia.org/wiki/Special:FilePath/Paycom_Center_exterior_aerial_view.jpg?width=400'),  -- Paycom Center
    ( 22, 'https://commons.wikimedia.org/wiki/Special:FilePath/Kia_Center_12-22-24.jpg?width=400'),  -- Kia Center
    ( 23, 'https://commons.wikimedia.org/wiki/Special:FilePath/Wells_Fargo_Center_-_2019_OWL_Grand_Finals.jpg?width=400'),  -- Xfinity Mobile Arena
    ( 24, 'https://commons.wikimedia.org/wiki/Special:FilePath/Mortgage_Matchup_Center.webp?width=400'),  -- Mortgage Matchup Center
    ( 25, 'https://commons.wikimedia.org/wiki/Special:FilePath/Modacenter2019.jpg?width=400'),  -- Moda Center
    ( 26, 'https://commons.wikimedia.org/wiki/Special:FilePath/Golden_1_Center_aerial_view.jpg?width=400'),  -- Golden 1 Center
    ( 27, 'https://commons.wikimedia.org/wiki/Special:FilePath/Texasdd.JPG?width=400'),  -- Frost Bank Center
    ( 28, 'https://commons.wikimedia.org/wiki/Special:FilePath/Scotiabank_Arena_summer_2022.jpg?width=400'),  -- Scotiabank Arena
    ( 29, 'https://commons.wikimedia.org/wiki/Special:FilePath/Delta_Center_2023.jpg?width=400'),  -- Delta Center
    ( 30, 'https://commons.wikimedia.org/wiki/Special:FilePath/Capital_One_Arena_-_Washington%2C_D.C.jpg?width=400');  -- Capital One Arena
UPDATE san_thi_dau s JOIN tmp_anh t ON t.ma = s.ma_san
SET s.hinh_anh = t.anh
WHERE s.hinh_anh IS NULL OR s.hinh_anh = '';

-- 4. Logo đội bóng (ma_doi theo 02_du_lieu_doi_bong.sql) - file SVG trên Wikipedia
TRUNCATE TABLE tmp_anh;
INSERT INTO tmp_anh (ma, anh) VALUES
    ( 1, 'https://upload.wikimedia.org/wikipedia/en/2/24/Atlanta_Hawks_logo.svg'),  -- Atlanta Hawks
    ( 2, 'https://upload.wikimedia.org/wikipedia/en/8/8f/Boston_Celtics.svg'),  -- Boston Celtics
    ( 3, 'https://upload.wikimedia.org/wikipedia/en/4/40/Brooklyn_Nets_primary_icon_logo_2024.svg'),  -- Brooklyn Nets
    ( 4, 'https://upload.wikimedia.org/wikipedia/en/c/c4/Charlotte_Hornets_%282014%29.svg'),  -- Charlotte Hornets
    ( 5, 'https://upload.wikimedia.org/wikipedia/commons/0/00/Logo_of_Chicago_Bulls.svg'),  -- Chicago Bulls
    ( 6, 'https://upload.wikimedia.org/wikipedia/commons/4/4b/Cleveland_Cavaliers_logo.svg'),  -- Cleveland Cavaliers
    ( 7, 'https://upload.wikimedia.org/wikipedia/en/9/97/Dallas_Mavericks_logo.svg'),  -- Dallas Mavericks
    ( 8, 'https://upload.wikimedia.org/wikipedia/en/7/76/Denver_Nuggets.svg'),  -- Denver Nuggets
    ( 9, 'https://upload.wikimedia.org/wikipedia/commons/c/c9/Logo_of_the_Detroit_Pistons.svg'),  -- Detroit Pistons
    (10, 'https://upload.wikimedia.org/wikipedia/en/0/01/Golden_State_Warriors_logo.svg'),  -- Golden State Warriors
    (11, 'https://upload.wikimedia.org/wikipedia/en/2/28/Houston_Rockets.svg'),  -- Houston Rockets
    (12, 'https://upload.wikimedia.org/wikipedia/en/1/1b/Indiana_Pacers.svg'),  -- Indiana Pacers
    (13, 'https://upload.wikimedia.org/wikipedia/en/e/ed/Los_Angeles_Clippers_%282024%29.svg'),  -- Los Angeles Clippers
    (14, 'https://upload.wikimedia.org/wikipedia/commons/3/3c/Los_Angeles_Lakers_logo.svg'),  -- Los Angeles Lakers
    (15, 'https://upload.wikimedia.org/wikipedia/en/f/f1/Memphis_Grizzlies.svg'),  -- Memphis Grizzlies
    (16, 'https://upload.wikimedia.org/wikipedia/en/f/fb/Miami_Heat_logo.svg'),  -- Miami Heat
    (17, 'https://upload.wikimedia.org/wikipedia/en/4/4a/Milwaukee_Bucks_logo.svg'),  -- Milwaukee Bucks
    (18, 'https://upload.wikimedia.org/wikipedia/en/c/c2/Minnesota_Timberwolves_logo.svg'),  -- Minnesota Timberwolves
    (19, 'https://upload.wikimedia.org/wikipedia/en/0/0d/New_Orleans_Pelicans_logo.svg'),  -- New Orleans Pelicans
    (20, 'https://upload.wikimedia.org/wikipedia/en/2/25/New_York_Knicks_logo.svg'),  -- New York Knicks
    (21, 'https://upload.wikimedia.org/wikipedia/en/5/5d/Oklahoma_City_Thunder.svg'),  -- Oklahoma City Thunder
    (22, 'https://upload.wikimedia.org/wikipedia/en/1/10/Orlando_Magic_logo.svg'),  -- Orlando Magic
    (23, 'https://upload.wikimedia.org/wikipedia/en/0/0e/Philadelphia_76ers_logo.svg'),  -- Philadelphia 76ers
    (24, 'https://upload.wikimedia.org/wikipedia/en/d/dc/Phoenix_Suns_logo.svg'),  -- Phoenix Suns
    (25, 'https://upload.wikimedia.org/wikipedia/en/2/21/Portland_Trail_Blazers_logo.svg'),  -- Portland Trail Blazers
    (26, 'https://upload.wikimedia.org/wikipedia/en/c/c7/SacramentoKings.svg'),  -- Sacramento Kings
    (27, 'https://upload.wikimedia.org/wikipedia/en/a/a2/San_Antonio_Spurs.svg'),  -- San Antonio Spurs
    (28, 'https://upload.wikimedia.org/wikipedia/en/3/36/Toronto_Raptors_logo.svg'),  -- Toronto Raptors
    (29, 'https://upload.wikimedia.org/wikipedia/en/7/77/Utah_Jazz_logo_2025.svg'),  -- Utah Jazz
    (30, 'https://upload.wikimedia.org/wikipedia/en/0/02/Washington_Wizards_logo.svg');  -- Washington Wizards
UPDATE doi_bong d JOIN tmp_anh t ON t.ma = d.ma_doi
SET d.logo = t.anh
WHERE d.logo IS NULL OR d.logo = '';

DROP TEMPORARY TABLE IF EXISTS tmp_anh;
