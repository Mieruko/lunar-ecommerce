# Lunar Ecommerce

Website thương mại điện tử đồng hồ và trang sức của **Lunar Jewels**, xây dựng bằng Laravel và Filament. Dự án gồm cửa hàng dành cho khách mua, khu vực thành viên và hệ thống quản trị vận hành tại `/admin`.

## Chức năng chính

- Danh mục đồng hồ, trang sức, thương hiệu, bộ sưu tập và biến thể sản phẩm.
- Giỏ hàng tách biệt theo tài khoản/phiên truy cập, mã giảm giá và phí vận chuyển theo khu vực.
- Thanh toán COD, chuyển khoản VietQR, PayPal Sandbox và VNPAY Sandbox.
- Theo dõi đơn hàng, thông báo khách hàng, danh sách yêu thích và quản lý địa chỉ.
- Đổi trả, bảo hành, đánh giá, vận đơn, tồn kho, serial number và hoàn tiền thủ công.
- Admin Filament với vai trò/quyền, nhật ký hoạt động, báo cáo và xuất CSV.

## Công nghệ

- PHP 8.3+, Laravel 13
- Filament 5
- MySQL 8/MariaDB hoặc SQLite
- Node.js 20.19+ hoặc 22.12+, Vite 8, Tailwind CSS 4

## Cài đặt trên Windows/XAMPP

### 1. Lấy mã nguồn

```powershell
git clone https://github.com/Mieruko/lunar-ecommerce.git
cd lunar-ecommerce
composer install
npm ci
```

### 2. Tạo cấu hình môi trường

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Tạo database MySQL tên `lunar_ecommerce`, sau đó sửa phần database trong `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lunar_ecommerce
DB_USERNAME=root
DB_PASSWORD=
```

Không commit `.env` lên GitHub. Các khóa PayPal, VNPAY, R2 và thông tin tài khoản ngân hàng chỉ được đặt trong `.env` của từng môi trường.

### 3. Tạo dữ liệu và liên kết storage

```powershell
php artisan migrate --seed
php artisan storage:link
```

Dữ liệu mẫu tạo một tài khoản quản trị dành cho môi trường phát triển:

```text
URL:      http://127.0.0.1:8000/admin
Email:    admin@lunarjewels.test
Password: password
```

Hãy đổi mật khẩu ngay nếu môi trường có thể được truy cập từ Internet. Không chạy dữ liệu mẫu này trên production khi chưa thay thông tin quản trị.

### 4. Build giao diện và chạy ứng dụng

```powershell
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Mở `http://127.0.0.1:8000`. Khi cần phát triển CSS/JavaScript với hot reload, chạy `npm run dev` ở một terminal riêng.

## Chạy tác vụ nền

Mở thêm terminal cho queue và scheduler khi kiểm thử đầy đủ luồng thông báo/thanh toán:

```powershell
php artisan queue:work
php artisan schedule:work
```

Scheduler có nhiệm vụ xử lý các đơn thanh toán online quá hạn và giải phóng tồn kho/mã giảm giá liên quan.

## Cấu hình thanh toán

### PayPal Sandbox

Điền các biến sau trong `.env`:

```env
PAYPAL_CLIENT_ID=
PAYPAL_SECRET=
PAYPAL_BASE_URL=https://api-m.sandbox.paypal.com
PAYPAL_WEBHOOK_ID=
```

Webhook PayPal:

```text
https://your-domain.example/webhooks/paypal
```

### VNPAY Sandbox

```env
VNPAY_TMN_CODE=
VNPAY_HASH_SECRET=
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
```

Webhook/IPN VNPAY:

```text
https://your-domain.example/webhooks/vnpay
```

### Chuyển khoản VietQR

```env
BANK_TRANSFER_BANK_CODE=
BANK_TRANSFER_BANK_NAME=
BANK_TRANSFER_ACCOUNT_NUMBER=
BANK_TRANSFER_ACCOUNT_NUMBER_DISPLAY=
BANK_TRANSFER_ACCOUNT_NAME=
VIETQR_IMAGE_URL=https://img.vietqr.io/image
VIETQR_TEMPLATE=compact2
```

Sau mỗi lần thay `.env`, chạy:

```powershell
php artisan optimize:clear
```

## Chia sẻ website local qua Cloudflare Tunnel

Build frontend trước để máy khác không phụ thuộc Vite dev server:

```powershell
npm run build
php artisan serve --host=127.0.0.1 --port=8000
cloudflared tunnel --url http://127.0.0.1:8000
```

Đổi `APP_URL` sang URL HTTPS mà Cloudflare cung cấp rồi chạy `php artisan optimize:clear`. Quick Tunnel tạo URL tạm mới sau mỗi lần khởi động; webhook thanh toán cũng phải được cập nhật theo URL đó. Muốn URL cố định, hãy dùng Cloudflare Named Tunnel với tên miền riêng.

Không chạy `npm run dev` khi chia sẻ Quick Tunnel. Nếu `public/hot` còn tồn tại, Laravel sẽ trỏ asset tới Vite trên localhost và máy khác sẽ mất CSS/JavaScript.

## Kiểm thử

```powershell
composer test
npm run build
```

## Dữ liệu không đưa lên GitHub

Repository chủ động loại trừ:

- `.env` và các biến thể chứa bí mật.
- `vendor`, `node_modules`, frontend build và asset Filament sinh tự động.
- Database SQLite, file SQL/dump, log, cache, session và file upload local.
- File Vite hot reload, báo cáo coverage, cấu hình IDE và script tạm.

## Lưu ý production

- Dùng `APP_ENV=production`, `APP_DEBUG=false` và HTTPS.
- Không dùng tài khoản/mật khẩu seed mặc định.
- Cấu hình queue worker, scheduler và backup database bằng process manager phù hợp.
- Chỉ lưu khóa thanh toán trên server; không đặt secret trong JavaScript hoặc repository.
- Webhook phải dùng URL HTTPS cố định và phải xác thực chữ ký từ nhà cung cấp.

## Giấy phép

Dự án sử dụng mã nguồn Laravel theo giấy phép MIT. Nội dung, dữ liệu sản phẩm và nhận diện Lunar Jewels thuộc chủ sở hữu dự án.
