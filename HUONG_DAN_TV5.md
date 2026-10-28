# Module Thống kê, Bảng xếp hạng, Playoff (TV5)

## Các file và chức năng

| File | Chức năng trong bảng phân công |
|---|---|
| `includes/ham_thong_ke.php` | Hàm dùng chung (tiền tố `tk`): truy vấn PDO, chọn mùa giải, dựng bảng box score, toàn bộ nghiệp vụ Playoff (tạo vòng 1 từ bảng xếp hạng, đội thắng loạt, tạo các vòng kế tiếp, sơ đồ nhánh) |
| `assets/css/thong_ke.css` | CSS riêng của TV5 (bảng số liệu, bảng điểm, bracket), dùng kèm Bootstrap |
| `bang_xep_hang/index.php` | Bảng xếp hạng theo hội nghị (hạng 1-6 Playoff, 7-10 Play-in) hoặc theo khu vực, đọc từ view `v_bang_xep_hang` |
| `thong_ke/index.php` | Top cầu thủ: 5 ô dẫn đầu + top 10, lọc mùa thường / playoff, đọc từ view `v_chi_so_trung_binh` |
| `thong_ke/so_sanh.php` | Biểu đồ phong độ 10 trận gần nhất và so sánh 2 cầu thủ (Chart.js: đường, radar, thanh) |
| `thong_ke/box_score.php` | Danh sách trận đã đấu và box score từng trận (điểm theo hiệp, trọng tài, xuất phát / dự bị, tổng đội) |
| `thong_ke/playoff.php` | Sơ đồ nhánh Playoff cho Khách xem |
| `admin/box_score/index.php` | Nhập / sửa box score (chỉ admin), tự tính điểm, kiểm tra theo ràng buộc CHECK của database |
| `admin/playoff/index.php` | Tạo vòng 1 từ bảng xếp hạng, cập nhật đội thắng loạt, tạo bán kết / chung kết miền / chung kết NBA |

Menu có sẵn link tới `bang_xep_hang/index.php`, `thong_ke/index.php`, `admin/box_score/`, `admin/playoff/`. Các trang còn lại chuyển qua thanh nút trong trang Thống kê.

## Quy tắc nghiệp vụ đã cài

- Điểm cầu thủ = 2 × ném trúng + 3 điểm trúng + phạt trúng, tự tính, khớp ràng buộc CHECK. Tổng điểm cầu thủ mỗi đội phải bằng tỉ số đội, nếu lệch thì không cho lưu.
- Chỉ nhập box score cho trận đã đấu (đã có tỉ số). Cầu thủ chọn "— không ghi —" sẽ không có dòng, nếu đã có thì dòng bị xóa. Lưu bằng transaction.
- Số trận thắng của mỗi đội trong một loạt Playoff đếm từ `tran_dau` theo `ma_loat`, không lưu trong `loat_playoff`. Thắng 4 trận là thắng loạt.
- Vòng 1: lấy 8 đội đầu bảng xếp hạng mỗi miền, ghép 1-8, 4-5, 3-6, 2-7. Bản này chưa tách Play-in riêng.

## Cần thống nhất với nhóm

- **TV4**: trận Playoff cần có `giai_doan = 'playoff'`, `ma_loat`, `tran_so_trong_loat` để trang Playoff đếm được tỉ số. `tran_dau.ma_loat` tham chiếu `loat_playoff` nên phải tạo loạt (trang admin Playoff của TV5) trước, rồi TV4 mới gắn trận vào loạt. Thứ tự import file mẫu 04 → 05 cần tính đến điều này.
- **TV3**: trang cầu thủ `cau_thu/ho_so.php?id=` được link từ Top cầu thủ và box score.
- Chart.js tải từ CDN (`cdn.jsdelivr.net`), máy cần có internet khi xem trang So sánh.

## Chạy thử

1. Import `database/qlbongro.sql`, rồi (nếu chưa có dữ liệu của nhóm) `test_tv5.sql` để có 30 đội, 270 cầu thủ, 300 trận và box score đầy đủ. **`test_tv5.sql` chỉ để thử trên máy, KHÔNG đưa vào PR.**
2. Đăng nhập `admin` / `admin123`, mở:
   - `http://localhost/BaiNhom/bang_xep_hang/index.php`
   - `http://localhost/BaiNhom/thong_ke/index.php`
   - `http://localhost/BaiNhom/thong_ke/so_sanh.php?a=1&b=2`
   - `http://localhost/BaiNhom/thong_ke/box_score.php?id=1`
   - `http://localhost/BaiNhom/admin/box_score/index.php`
   - `http://localhost/BaiNhom/admin/playoff/index.php`
