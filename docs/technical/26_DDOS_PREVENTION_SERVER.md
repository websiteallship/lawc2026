# Hướng dẫn chống DDoS - Tầng Server (Nginx & Hệ điều hành)

Tài liệu này hướng dẫn cấu hình chống DDoS ở tầng Server, chủ yếu tập trung vào Nginx và tường lửa.

## 1. Nginx Rate Limiting

Sử dụng `limit_req_module` của Nginx để giới hạn số lượng request từ một địa chỉ IP.

### Cấu hình cơ bản

Thêm vào file `nginx.conf` (trong block `http {}`):

```nginx
# Giới hạn 10 requests / giây cho mỗi IP. Bộ nhớ 10MB lưu trữ state.
limit_req_zone $binary_remote_addr zone=mylimit:10m rate=10r/s;
```

Thêm vào file cấu hình site (vhost) (trong block `server {}` hoặc `location / {}`):

```nginx
server {
    ...
    location / {
        # Áp dụng zone mylimit. burst=20 cho phép dồn tối đa 20 req. nodelay xử lý ngay lập tức các req trong burst.
        limit_req zone=mylimit burst=20 nodelay;
        
        # Cấu hình proxy_pass hoặc try_files cho Laravel
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

## 2. Nginx Connection Limiting

Giới hạn số lượng kết nối đồng thời từ một IP (chống Slowloris).

Thêm vào file `nginx.conf`:

```nginx
limit_conn_zone $binary_remote_addr zone=addr:10m;
```

Thêm vào block `server {}`:

```nginx
limit_conn addr 50; # Tối đa 50 kết nối đồng thời trên mỗi IP
```

## 3. Cài đặt Fail2Ban

Fail2Ban phân tích log Nginx và tự động cấu hình tường lửa (iptables/UFW) chặn các IP có dấu hiệu spam request.

### Cài đặt:
```bash
sudo apt-get install fail2ban
```

### Cấu hình jail cho Nginx (`/etc/fail2ban/jail.local`):

```ini
[nginx-limit-req]
enabled = true
port    = http,https
filter  = nginx-limit-req
logpath = /var/log/nginx/error.log
findtime = 600
bantime = 7200
maxretry = 5
```
Khởi động lại Fail2Ban: `sudo systemctl restart fail2ban`.

## 4. Tường lửa UFW / iptables

Chỉ mở các cổng cần thiết (80, 443, 22).

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp # Đổi port SSH nếu cần
sudo ufw enable
```
