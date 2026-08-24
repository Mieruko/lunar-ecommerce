# Chatbot và hộp thư chăm sóc khách hàng

## Quyết định kiến trúc

Phiên bản đầu tiên dùng **bot quyết định theo FAQ/ý định + chuyển tiếp nhân viên** thay vì gọi mô hình AI bên ngoài. Bot chỉ đọc dữ liệu đã được phép, đưa ra câu trả lời xác định trước và giữ nguyên toàn bộ lịch sử khi chuyển sang nhân viên.

Lựa chọn này phù hợp với dự án hiện tại vì:

- Laravel/Filament đã có tài khoản, vai trò `customer-service`, đơn hàng, thông báo và nhật ký quản trị.
- Không cần khóa API, chi phí theo lượt chat hoặc hạ tầng websocket mới.
- Các câu trả lời về đơn hàng có thể kiểm thử ổn định và không bị mô hình sinh sai thông tin.
- Có thể bổ sung LLM hoặc Laravel Reverb sau mà không phải thay đổi mô hình hội thoại.

## Luồng khách hàng

1. Khách mở nút **Lunar Care** trên mọi trang cửa hàng.
2. Bot gợi ý các chủ đề: đơn hàng, giao hàng, thanh toán, đổi trả, bảo hành, sản phẩm và gặp nhân viên.
3. Với khách đã đăng nhập, bot có thể đọc trạng thái đơn gần nhất hoặc mã đơn thuộc đúng tài khoản. Khách vãng lai được dẫn đến trang tra cứu bằng mã đơn và số điện thoại.
4. Khi khách yêu cầu người thật hoặc bot không hiểu hai lần liên tiếp, hội thoại chuyển sang hàng đợi nhân viên và bot dừng trả lời tự động.
5. Nhân viên có thể đính kèm tối đa ba sản phẩm; khách nhìn thấy ảnh, giá, tình trạng hàng và liên kết mở trang sản phẩm ngay trong chat.
6. Tin nhắn của nhân viên xuất hiện trong cùng cửa sổ chat; hội thoại có thể được mở lại sau khi đã giải quyết.

Bot không tự hủy đơn, hoàn tiền, thay đổi địa chỉ, cập nhật thanh toán hay thực hiện bất kỳ thao tác vận hành nào.

## Công cụ cho nhân viên CSKH

Hộp thư trong Filament thuộc nhóm **Khách hàng & Hậu mãi** cung cấp:

- hàng đợi chưa phân công và bộ lọc theo trạng thái, mức ưu tiên, nhân viên phụ trách;
- nhận hội thoại, trả lời khách, ghi chú nội bộ, đóng và mở lại;
- tìm theo tên/SKU và đẩy thẻ sản phẩm trực tiếp vào phản hồi;
- câu trả lời nhanh có thể quản lý;
- quản lý FAQ/keyword mà bot sử dụng;
- ngữ cảnh khách hàng và đơn hàng ở mức tối thiểu cần thiết;
- nhật ký các hành động quản trị mà không ghi lại nội dung tin nhắn hoặc token truy cập.

Các quyền được tách riêng: `support.view`, `support.reply`, `support.assign`, `support.resolve` và `support.manage_knowledge`.

## An toàn và quyền riêng tư

- Hội thoại khách vãng lai được gắn với token ngẫu nhiên trong session; cơ sở dữ liệu chỉ lưu SHA-256 của token.
- Mọi truy vấn đơn hàng trong chatbot của tài khoản đăng nhập đều có điều kiện `orders.user_id = auth()->id()`.
- Client không được truyền ID hội thoại hay ID đơn để quyết định quyền truy cập.
- Nội dung được giới hạn độ dài, bảo vệ CSRF, rate-limit và hiển thị dưới dạng text để tránh XSS.
- Polling chỉ hoạt động khi cửa sổ chat đang mở và trang đang hiển thị.
- Transcript đã giải quyết phải được xóa theo thời hạn lưu giữ đã cấu hình; cần được chủ cửa hàng rà soát cùng chính sách quyền riêng tư thực tế.

## Vận hành và hướng nâng cấp

Transcript đã giải quyết có thể được kiểm tra và xóa theo cấu hình:

```powershell
php artisan support:purge-resolved --dry-run
php artisan support:purge-resolved
```

Mặc định, lệnh chỉ chọn các hội thoại `resolved` cũ hơn `SUPPORT_CHAT_RETENTION_DAYS=180`. Hãy chạy `--dry-run` trước và chỉ lập lịch xóa sau khi thời hạn đã được duyệt trong chính sách quyền riêng tư.

MVP dùng polling, phù hợp với quy mô cửa hàng nhỏ và không yêu cầu dịch vụ nền bổ sung. Khi lưu lượng tăng, có thể chuyển phần vận chuyển tin nhắn sang Laravel Reverb, giữ nguyên database, API và giao diện.

LLM chỉ nên được bổ sung như lớp gợi ý câu trả lời sau khi có bộ đánh giá riêng. Không gửi dữ liệu thanh toán, địa chỉ đầy đủ, token, ghi chú nội bộ hoặc dữ liệu đơn hàng không cần thiết cho nhà cung cấp mô hình.

## Nguồn tham khảo

- [Shopify Inbox: instant answers](https://help.shopify.com/en/manual/inbox/chat-settings-and-appearance/instant-answers)
- [Shopify Inbox: quản lý và phân công hội thoại](https://help.shopify.com/en/manual/inbox/conversations)
- [Shopify Inbox: quick replies](https://help.shopify.com/en/manual/inbox/configure-inbox/quick-replies)
- [Google Agent Assist: vòng đời virtual agent và human handoff](https://docs.cloud.google.com/gemini-enterprise-cx/agent-assist/basics)
- [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html)
- [OWASP Logging Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html)
- [Luật Bảo vệ dữ liệu cá nhân 91/2025/QH15](https://vanban.chinhphu.vn/?classid=1&docid=214590&pageid=27160&typegroup=)
