# Bunny Communications OA

Bunny Communications 内部运营管理系统，负责管理 IP 资产、客户计费、供应商支出、员工绩效和工单流程。

**线上地址**：https://oa.profess0r.com

---

## 技术栈

| 层级 | 技术 | 版本 |
|------|------|------|
| 后端框架 | Laravel | 12.x |
| 管理面板 | Filament | 3.x |
| 响应式组件 | Livewire | 3.x |
| CSS 框架 | Tailwind CSS | 4.x |
| 前端构建 | Vite | 7.x |
| PHP | PHP | 8.3 |
| 数据库 | MySQL | 8.0 |
| Excel 导出 | Maatwebsite Excel | 3.x |

---

## 核心功能模块

### IP 资产管理
- 管理公司持有的 IP 地址块（CIDR），支持 BGP / ISP ASN 类型
- 状态跟踪：Active / Reserved / Released
- 变更历史自动记录（客户归属、成本、价格变更）
- **IP 地址搜索**：搜索栏直接输入单个 IP（如 `192.168.1.1`），自动匹配所属子网
- GeoFeed（RFC 8805）自动同步至远端服务器

### 客户计费（Income）
- 月度账单自动生成（基于活跃 IP 价格）
- 附加项（Add-ons）管理
- 其他收入记录（支持 CNY/USD 双币种）
- 逾期判定（当月 20 日后）

### 供应商支出（Expense）
- 三类供应商：IP 供应商 / 线路供应商（IPT）/ 数据中心
- 月度支出记录与付款流水

### 员工绩效与薪酬
- 薪酬配置：底薪 + 提成比例
- 月度绩效自动计算：收入 - 成本 - 工单扣款 = 净利润
- 总薪酬 = 净利润 × 提成比例 + 底薪

### 工单系统（Workflows）
- 优先级：Low / Normal / High / Urgent
- 状态流转：Open → Updated → Approved / Overdue / Cancelled
- 多人指派，支持证据附件上传
- 逾期工单可配置薪资扣款

### 其他
- 文档管理（PDF、Office、图片、压缩包，50MB 上限）
- GeoFeed 地理位置库管理
- 操作审计日志（Activity Log，90 天自动清理）
- 数据备份导出（Excel）

---

## 用户权限

| 角色 | 权限 |
|------|------|
| `admin` | 全部功能，含薪酬、绩效、审计日志、审批工单 |
| `employee` | Workflows、IP Assets、Customers、Documents、Providers、Locations |

> User 账号通过 **email** 与 Employee 档案关联（非外键）。

---

## 本地部署

### 环境要求

- PHP 8.3+（需要 `ext-bcmath`、`ext-mbstring`、`ext-pdo_mysql`）
- MySQL 8.0+
- Node.js 20+
- Composer 2.x

### 安装步骤

```bash
# 1. 克隆项目
git clone https://github.com/hypepsi/oa.profess0r.com.git
cd oa.profess0r.com

# 2. 安装依赖
composer install
npm install

# 3. 配置环境
cp .env.example .env
php artisan key:generate

# 4. 配置数据库（编辑 .env 填入 DB_* 参数）

# 5. 迁移数据库
php artisan migrate

# 6. 创建管理员账号
php artisan make:filament-user

# 7. 构建前端
npm run build

# 8. 配置 storage 权限
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

### 定时任务

```bash
# 添加到 crontab
* * * * * cd /var/www/oa && php artisan schedule:run >> /dev/null 2>&1
```

| 任务 | 时间 | 说明 |
|------|------|------|
| `backup:data` | 每天 03:00 | 全量数据备份至 Excel |
| `geofeed:sync-remote --mode=test` | 每天 03:05 | 同步 GeoFeed 至远端服务器 |
| `activity-logs:clean` | 每天 02:00 | 清理 90 天前的审计日志 |

---

## 常用命令

```bash
# 清理所有缓存
php artisan optimize:clear

# 手动触发数据备份
php artisan backup:data

# 手动同步 GeoFeed（测试模式）
php artisan geofeed:sync-remote --mode=test

# 手动同步 GeoFeed（生产模式）
php artisan geofeed:sync-remote --mode=production

# 查看定时任务列表
php artisan schedule:list

# 查看最新错误日志
tail -100 storage/logs/laravel.log
```

---

## GeoFeed

当前运行于 **Test 模式**，同步至 `geofeed.test.csv`。

切换生产模式只需修改 `.env`：

```env
GEOFEED_REMOTE_URL=https://bunnycommunications.com/geofeed.csv
GEOFEED_UPLOAD_URL=https://bunnycommunications.com/geofeed-upload-prod.php?token=xxx
```

同时将 `routes/console.php` 中的 `--mode=test` 改为 `--mode=production`。

---

## 开发规范

详见 [CLAUDE.md](./CLAUDE.md)，包含：
- 权限判断规范（统一使用 `isAdmin()`，禁止邮件硬编码）
- Filament 写法规范（字体、Badge 颜色语义、Section 格式）
- 样式规范（原生 Tailwind，禁止自定义 CSS 类）
- 排错流程

---

## 服务器信息

- **路径**：`/var/www/oa`
- **Web Server**：Nginx + PHP 8.3-FPM
- **PHP 配置**：`memory_limit=256M`，`max_execution_time=300`
