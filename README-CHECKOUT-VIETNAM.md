# Checkout Việt Nam — bản đã merge vào project mới nhất

Bản này không dùng GHN ở runtime.

## Luồng địa chỉ

Việt Nam
→ Tỉnh / Thành phố
→ Phường / Xã / Đặc khu
→ Số nhà, tên đường
→ Shipping zone trong MySQL
→ Phí vận chuyển

## Cài đặt

Chạy trong thư mục project:

```powershell
php artisan migrate
php artisan lunar:sync-vietnam-addresses
php artisan optimize:clear
npm run dev
```

Lệnh `lunar:sync-vietnam-addresses` import 34 Tỉnh/Thành và 3.321 đơn vị cấp xã
vào MySQL. Sau khi import, checkout chỉ đọc database nội bộ.

Nếu máy không tải được file dữ liệu khi chạy command, tải JSON về máy rồi dùng:

```powershell
php artisan lunar:sync-vietnam-addresses --file="C:\duong-dan\vietnam_units.json"
```

## Shipping zone mẫu

- near: 25.000đ
- standard: 30.000đ
- remote: 40.000đ
- special: 50.000đ

Đây là giá mẫu nghiệp vụ, không phải bảng giá của hãng vận chuyển.
Tất cả tỉnh ban đầu dùng `standard`; có thể cấu hình lại trong database/admin sau.

## Bảo mật

Browser chỉ gửi `province_code` và `ward_code`.
Phí ship được backend tự tính và xác nhận lại trước khi tạo Order.
