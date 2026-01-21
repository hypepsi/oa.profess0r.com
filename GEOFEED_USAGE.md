# GeoFeed 自动同步系统使用说明

## 核心概念

### 当前配置（默认）
- **操作文件**: `geofeed.test.csv`（用于测试和验证）
- **同步方式**: HTTP + Token 认证
- **远端地址**: `https://bunnycommunications.com/geofeed.test.csv`
- **自动同步**: 每天凌晨 3:05 AM

### 切换到生产环境
只需修改配置文件 `/var/www/oa/.env`：

```bash
# 当前（测试环境）
GEOFEED_REMOTE_URL=https://bunnycommunications.com/geofeed.test.csv
GEOFEED_UPLOAD_URL=https://bunnycommunications.com/geofeed-upload.php?token=xxx

# 切换到生产（修改URL即可）
GEOFEED_REMOTE_URL=https://bunnycommunications.com/geofeed.csv
GEOFEED_UPLOAD_URL=https://bunnycommunications.com/geofeed-upload-prod.php?token=xxx
```

**无需修改任何代码！**

---

## 日常操作流程

### 场景：子网位置变更

#### 1. 员工更新IP资产
```
登录 OA → IP Assets → 选择 103.111.222.0/24
修改:
- Client: IPIDEA → Ucloud
- Location: Equinix-LA3 → Equinix-HK2  
- Geo Location: US, US-CA, Los Angeles → HK, Hong Kong
→ 保存
```

#### 2. 系统自动处理
```
保存时:
- ✅ 记录所有字段变更历史
- ✅ 更新数据库
- ✅ 显示"Different"状态（与远端不一致）

第二天凌晨 3:05 AM:
- ✅ 自动生成最新 GeoFeed CSV
- ✅ 上传到 bunnycommunications.com
- ✅ 全球用户看到新的地理位置
```

#### 3. 验证（可选）
```
刷新 IP Assets 列表
→ "GeoFeed Sync" 列显示 "Synced" ✅
```

---

## OA 界面功能

### IP Assets 页面

#### "Sync GeoFeed" 按钮（管理员可见）
- **作用**: 立即同步到远端（不等待自动同步）
- **适用场景**: 
  - 紧急变更需要立即生效
  - 测试同步功能是否正常
- **操作**: 点击 → 确认 → 等待3秒 → 完成

### Dashboard 页面

#### System Backup 区域
- **Backup Now**: 备份所有系统数据到 Excel
- **Download**: 下载最新的系统备份

#### GeoFeed Backup 区域

##### "From Database" 按钮
- **作用**: 从OA数据库生成并下载 GeoFeed CSV
- **用途**: 
  - 检查当前数据库的 GeoFeed 内容
  - 本地保存一份副本
- **文件名**: `geofeed_from_database_YYYY-MM-DD_HHMMSS.csv`

##### "From Remote" 按钮  
- **作用**: 从远端下载 GeoFeed 并保存到本地 backup
- **用途**:
  - 备份远端的 GeoFeed 文件
  - 对比本地和远端的差异
- **保存位置**: `/storage/app/backups/geofeed/`
- **文件名**: `geofeed_from_remote_YYYY-MM-DD_HHMMSS.csv`

---

## 技术细节

### 同步机制

#### HTTP + Token 方式
```php
// 上传请求
PUT https://bunnycommunications.com/geofeed-upload.php?token=xxx
Content-Type: text/csv
Body: [GeoFeed CSV content]

// Token 验证
Bearer c2b1f4b0a9c74e1c8f3a6d12b7f8e5c1
```

#### 为什么不用 FTP？
- HTTP 更简单、更快速
- Token 认证更安全
- 不需要额外的 FTP 服务器配置
- 支持 Cloudflare CDN 缓存

### 缓存机制

#### 远端 GeoFeed 缓存
- **缓存时间**: 10分钟
- **目的**: 避免频繁请求远端
- **清除时机**: 每次同步成功后自动清除

#### Cloudflare 缓存
- **问题**: 远端文件更新后，Cloudflare 可能显示旧内容
- **解决**: 
  - 等待自然过期（通常几分钟）
  - 或手动清除 Cloudflare 缓存

---

## 切换到生产环境

### 步骤1: 充分测试
```bash
# 1. 测试同步功能
在OA点击 "Sync GeoFeed" → 确认上传成功

# 2. 验证远端文件
curl https://bunnycommunications.com/geofeed.test.csv

# 3. 检查格式正确性
下载文件 → 用Excel打开 → 检查字段
```

### 步骤2: 准备生产环境上传脚本
```bash
# SSH到 bunnycommunications.com
ssh root@159.65.253.116

# 复制test脚本为production版本
cp /var/www/html/wordpress/geofeed-upload.php \
   /var/www/html/wordpress/geofeed-upload-prod.php

# 修改target file
sed -i 's/geofeed.test.csv/geofeed.csv/g' \
    /var/www/html/wordpress/geofeed-upload-prod.php
```

### 步骤3: 修改OA配置
```bash
# SSH到 OA服务器
ssh root@oa.profess0r.com

# 编辑配置
nano /var/www/oa/.env

# 修改这两行:
GEOFEED_REMOTE_URL=https://bunnycommunications.com/geofeed.csv
GEOFEED_UPLOAD_URL=https://bunnycommunications.com/geofeed-upload-prod.php?token=c2b1f4b0a9c74e1c8f3a6d12b7f8e5c1

# 清除缓存
cd /var/www/oa
php artisan optimize:clear
```

### 步骤4: 测试生产同步
```bash
# 命令行测试
php artisan geofeed:sync-remote --mode=test

# 或者在OA界面点击 "Sync GeoFeed"

# 验证
curl https://bunnycommunications.com/geofeed.csv
```

### 步骤5: 启用自动同步
配置已经自动启用，每天凌晨 3:05 AM 自动同步。

---

## 故障排除

### 问题1: 同步失败
**检查项**:
```bash
# 1. 检查配置
cat /var/www/oa/.env | grep GEOFEED

# 2. 检查Token是否正确
curl -X PUT -H "Authorization: Bearer xxx" \
  --data-binary @test.csv \
  https://bunnycommunications.com/geofeed-upload.php

# 3. 检查日志
tail -f /var/www/oa/storage/logs/laravel.log
```

### 问题2: 远端显示旧数据
**原因**: Cloudflare 缓存  
**解决**: 
- 登录 Cloudflare Dashboard
- 进入 `bunnycommunications.com`
- Caching → Purge Cache → 选择 `geofeed.csv`

### 问题3: GeoFeed Sync 显示 "Different"
**原因**: 本地和远端不一致  
**解决**: 点击 "Sync GeoFeed" 立即同步

### 问题4: 自动同步没有执行
**检查cron**:
```bash
# 查看定时任务
php artisan schedule:list

# 手动运行一次
php artisan schedule:run

# 检查cron是否启动
systemctl status cron
```

---

## 最佳实践

1. **每次修改后验证**: 
   - 修改 IP 资产后，检查 "GeoFeed Sync" 状态
   - Different = 需要同步，Synced = 已同步

2. **定期备份**:
   - 每周从 Dashboard 下载一次远端 GeoFeed
   - 保存到本地安全位置

3. **测试后再上线**:
   - 在 test.csv 充分测试
   - 确认无误后再切换到 production

4. **监控自动同步**:
   - 每天检查 Dashboard 的 "Last Sync" 时间
   - 确保自动同步正常运行

5. **记录变更**:
   - OA 自动记录所有字段变更
   - 可在 "Change History Tracking" 查看历史

---

## 常用命令

```bash
# 手动同步到远端
php artisan geofeed:sync-remote --mode=test

# 查看定时任务
php artisan schedule:list

# 立即运行所有定时任务
php artisan schedule:run

# 清除缓存
php artisan optimize:clear

# 查看日志
tail -f /var/www/oa/storage/logs/laravel.log
```

---

## 支持

如有问题，请联系系统管理员。
