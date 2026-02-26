# Bunny Communications OA — Claude 工作指南

## 文档维护规则（重要）

**每次完成功能开发、架构调整或重要配置变更后，必须同步更新本文件。**

具体要求：
- 新增模型、字段、关联关系 → 更新「核心业务模块」和「数据库关键表一览」
- 权限逻辑变更 → 更新「用户与权限体系」
- 新增或修改定时任务 → 更新「定时任务」
- 服务器/环境配置变更 → 更新「服务器配置」
- 新增 Artisan 命令 → 更新「常用 Artisan 命令」
- 业务逻辑重构 → 更新对应模块描述

本项目**不保留其他独立的 md 文档**，所有关键信息统一维护在此文件中。

---

## 项目基本信息

- **项目名称**：Bunny Communications OA (`bunny_oa`)
- **线上地址**：`https://oa.profess0r.com`
- **项目路径**：`/var/www/oa`
- **Web Server**：Nginx + PHP 8.3-FPM
- **数据库**：MySQL 8.0，数据库名 `bunny_oa`

---

## 技术栈

| 层级 | 技术 | 版本 | 官方文档 |
|------|------|------|----------|
| 后端框架 | Laravel | 12.x | https://laravel.com/docs/12.x |
| 管理面板 | Filament | 3.x | https://filamentphp.com/docs/3.x |
| 响应式组件 | Livewire | 3.x | https://livewire.laravel.com/docs/quickstart |
| CSS 框架 | Tailwind CSS | 4.x | https://tailwindcss.com/docs/installation |
| 前端构建 | Vite | 7.x | https://vite.dev/guide/ |
| PHP | PHP | 8.2+ | https://www.php.net/docs.php |
| Excel 导出 | Maatwebsite Excel | 3.x | https://docs.laravel-excel.com/3.1/getting-started/ |

> **排错时必须参考以上官方文档**，禁止使用 Filament v2 的旧写法（如 `Filament::registerResources`）。

---

## 用户与权限体系

### User 模型（登录账号）
- 字段：`name`, `email`, `password`, `role`
- `role` 枚举：`admin`（管理员）、`employee`（员工）
- `isAdmin()` / `isEmployee()` 方法已定义在 `User` 模型中

### Employee 模型（员工档案）
- 字段：`name`, `email`, `phone`, `department`, `is_active`
- `department` 枚举：`sales`（销售）、`technical`（技术）、`owner`（老板）
- User 账号通过 **email** 与 Employee 档案关联（非外键，代码中用 `Employee::where('email', auth()->user()->email)`）

### 权限规则
- **所有 `isAdmin()` 判断**：统一使用 `auth()->user()?->isAdmin()`，**禁止**用 email 硬编码 (`=== 'admin@bunnycommunications.com'`)
- Admin 专属：Dashboard 通览、Activity Log、Employee Compensation（薪酬）、Monthly Performance（绩效）
- 员工可访问：Workflows、IP Assets、Customers、Documents、Providers、Locations 等
- Workflow 创建权限：admin 和 employee 均可创建；字段编辑权限区分 admin / 创建者 / 仅被指派者

---

## 核心业务模块

### 1. IP 资产管理（核心）
- **模型**：`IpAsset` — CIDR、状态（Active/Reserved/Released）、成本/价格、所属客户、销售员、位置
- **变更追踪**：`IpAssetObserver` 将 status/client/cost/price 变更记录进 `meta` JSON 字段
- **GeoFeed**：RFC 8805 格式，通过 `GeoFeedService` 同步地理位置至 `bunnycommunications.com`
- 关联：`Provider`（IP 供应商）、`IptProvider`（线路供应商）、`Location`、`Customer`（客户）、`Employee`（销售员）
- **IP 地址搜索**：搜索栏支持直接输入单个 IPv4 地址（如 `192.168.1.1`），自动通过 MySQL `INET_ATON()` 子网包含计算检索出所属 CIDR（如 `192.168.1.0/24`）；输入非 IP 格式（如 CIDR 前缀）时回退为 `LIKE` 模糊搜索。实现在 `IpAssetResource` CIDR 列的 `->searchable(query: ...)` 回调中。

### 2. 客户计费（Income）
- **月度账单**：`CustomerBillingPayment` — 每月自动生成，金额 = 活跃 IP 价格 + 附加项
- **附加项**：`BillingOtherItem` — 一次性或周期性额外费用
- **其他收入**：`IncomeOtherItem` — 支持 CNY/USD 双币种，含汇率
- **逾期判定**：当月 20 日后未付款视为逾期
- 计算逻辑集中在 `BillingCalculator` 服务

### 3. 供应商支出（Expense）
- 三种供应商类型（多态）：`Provider`（IP）、`IptProvider`（线路）、`DatacenterProvider`（数据中心）
- 月度记录：`ProviderExpensePayment`，逻辑与计费对称
- 计算逻辑：`ExpenseCalculator` 服务

### 4. 员工绩效与薪酬
- **薪酬配置**：`EmployeeCompensation` — 底薪 + 提成比例，支持时间段生效
- **月度绩效**：`MonthlyPerformance` — 完整记录收入、成本、净利润、提成、工单扣款
- **计算公式**：收入 - 直接成本 - 分摊共享成本 - 工单扣款 = 净利润；净利润 × 提成比例 + 底薪 = 总薪酬
- 计算逻辑：`PerformanceCalculator` 服务，使用 `updateOrCreate()` 原子写入（数据库层有 `unique(employee_id, year, month)` 约束，防止并发重复）

### 5. 工单系统（Workflow）
- **模型**：`Workflow` — 标题、客户、优先级（low/normal/high/urgent）、状态（open/updated/approved/overdue/cancelled）、截止日期、逾期扣款
- **关联**：多对多指派给 `Employee`；有 `WorkflowUpdate` 进度记录（含附件）
- **权限细分**：
  - Admin：全部操作，包括审批（Approve）、设置逾期扣款
  - 创建者（员工）：可编辑 title/client/assignees/description/due_at/priority/require_evidence
  - 被指派者（员工）：只读 + 可添加 update
  - Status 字段永远只有 admin 可改
  - `is_overdue` / `deduction_amount` 对员工完全隐藏

### 6. 文档管理
- **模型**：`Document` — 支持 PDF、Office 文档、图片、压缩包，50MB 上限
- 分类：Contract、Invoice、Agreement、Policy、Report、Certificate、Other

### 7. 审计日志
- **模型**：`ActivityLog` — 记录所有用户操作，含 IP、User Agent
- `ActivityLogObserver` 全局监听模型变更
- Admin 专属查看，员工不可见

---

## 数据库关键表一览

| 表名 | 用途 |
|------|------|
| `users` | 登录账号（含 role） |
| `employees` | 员工档案（含 department） |
| `customers` | 客户信息 |
| `providers` | IP 供应商 |
| `ipt_providers` | 线路供应商 |
| `datacenter_providers` | 数据中心供应商 |
| `locations` | 机房位置 |
| `ip_assets` | IP 资产（含 meta JSON 变更历史） |
| `devices` | 网络设备 |
| `workflows` | 工单 |
| `workflow_assignees` | 工单指派（多对多） |
| `workflow_updates` | 工单进度记录 |
| `customer_billing_payments` | 客户月度账单 |
| `billing_payment_records` | 收款流水 |
| `billing_other_items` | 计费附加项 |
| `income_other_items` | 其他收入 |
| `provider_expense_payments` | 供应商月度支出 |
| `expense_payment_records` | 付款流水 |
| `employee_compensations` | 薪酬配置 |
| `monthly_performances` | 月度绩效计算结果 |
| `documents` | 文档文件库 |
| `activity_logs` | 操作审计日志 |
| `geofeed_locations` | RFC 8805 地理位置库 |

---

## 沟通语言

- **永远用中文回答用户**，无论用户用什么语言提问

---

## UI 设计规范（Filament 3 官方最佳实践）

### 核心原则
Filament 控制了绝大部分 UI 输出，**不要手写 HTML/CSS 来实现 Filament 已有的功能**。所有视觉增强都应通过 Filament API 链式调用实现，这样能自动适配深色模式、响应式和主题色。

### 已落地的改动（参照标准，后续保持一致）

#### 表格列（TextColumn）
| 场景 | 用法 |
|------|------|
| IP/CIDR/ASN 等技术标识符 | `->fontFamily(FontFamily::Mono)` |
| 金额列 | `->fontFamily(FontFamily::Mono)->prefix('$')` |
| 主要标识列（客户名、工单标题） | `->weight(FontWeight::Medium)` |
| 副信息（描述预览） | `->description(fn($r) => Str::limit($r->description ?? '', 60))` |
| 可复制内容（邮箱、CIDR） | `->copyable()->copyMessage('Copied!')` |
| 状态字段 | `->badge()->color(fn($state) => match($state) { ... })` |
| 默认隐藏但可切换的列 | `->toggleable(isToggledHiddenByDefault: true)` |

**注意**：`weight()` 必须传枚举 `FontWeight::Medium`，**不要**传字符串 `'medium'`（字符串在 Filament 3 已废弃）。

#### 表单 Section
- 每个 `Section` 必须加 `->icon('heroicon-o-xxx')` 提升视觉导航感
- `->description()` 写简短说明，帮助用户理解这一组字段的用途
- 优先用 `->columns(2)` 双列布局，长文本（Textarea）用 `->columnSpanFull()`

#### Badge 颜色语义（统一标准）
| 语义 | 颜色 |
|------|------|
| 成功 / 活跃 / 已审批 | `success`（绿） |
| 警告 / 待处理 / 已更新 | `warning`（黄） |
| 危险 / 逾期 / 已取消 | `danger`（红） |
| 中性 / 已释放 / 无操作 | `gray` |
| 信息 / 普通优先级 | `info`（蓝） |

#### 需要用到的 use 引入
```php
use Filament\Support\Enums\FontFamily;   // 等宽字体
use Filament\Support\Enums\FontWeight;   // 字重（Medium/Bold等）
use Illuminate\Support\Str;              // Str::limit() 用于 description 截断
```

### 当前各资源 UI 状态

**IpAssetResource**
- CIDR：Mono 字体 + 可复制
- ASN：Mono 字体
- Status：Badge（green/yellow/gray）
- Cost/Price：Mono 字体 + `$` 前缀（默认隐藏）
- GeoFeed Sync：Badge（可切换显示）

**CustomerResource**
- Name：FontWeight::Medium
- Contact Email / Abuse Email：可复制

**WorkflowResource（表格）**
- Title：FontWeight::Medium + description 预览
- Priority / Status：Badge + 语义颜色

**WorkflowResource（表单）**
- Basic Information Section：`heroicon-o-document-text`
- Status & Priority Section：`heroicon-o-adjustments-horizontal`

### 后续新增资源时的 checklist
- [ ] 主标识列加 `->weight(FontWeight::Medium)`
- [ ] 技术标识符（IP、编号）加 `->fontFamily(FontFamily::Mono)`
- [ ] 金额加 `->prefix('$')`
- [ ] 邮箱/可复制内容加 `->copyable()`
- [ ] 状态列用 `->badge()->color()`，颜色遵循上方语义表
- [ ] 表单 Section 加 `->icon()` 和 `->description()`
- [ ] 低频字段用 `->toggleable(isToggledHiddenByDefault: true)` 隐藏

---

## 代码规范（必须遵守）

### UI 语言
- **所有用户可见的界面文字（按钮、标签、提示、说明）必须使用英文**
- 代码注释可以用中文，但界面显示必须是英文

### Filament 写法规范
- 统一使用 `$form->schema([...])` 和 `$table->columns([...])` 链式调用
- 统计卡片必须使用 Filament `StatsOverviewWidget`，禁止手写 HTML 卡片
- 确认弹窗使用 `->requiresConfirmation()`，禁止用浏览器原生 `confirm()`
- 导航标签（`$navigationLabel`）与页面标题（`$title`）必须保持一致

### 样式规范
- 直接使用原生 Tailwind CSS 工具类
- **禁止**在 `app.css` 中创建自定义 CSS 类（如 `oa-*`）
- 字体层级：`text-lg`（主标题）→ `text-base`（次标题）→ `text-sm`（正文）→ `text-xs`（辅助信息）
- 颜色层级：`text-gray-900` → `text-gray-700` → `text-gray-600` → `text-gray-500`

### 权限判断
- 始终用 `auth()->user()?->isAdmin()` 判断管理员，**不要**用 email 硬编码
- 员工身份查找：`Employee::where('email', auth()->user()->email)->first()`

---

## 排错流程

1. **第一步：看日志** → `tail -100 /var/www/oa/storage/logs/laravel.log`
2. **第二步：清缓存** → `php artisan optimize:clear`
3. **第三步：查文档** → 参考上方官方文档链接，尤其是 Filament 的 Upgrade Guide
4. **第四步：检查 `use` 引用** — Filament 报错很多时候是漏了 use 语句

---

## 定时任务（Cron）

定时任务配置在 `bootstrap/app.php`，cron 入口：
```bash
* * * * * cd /var/www/oa && php artisan schedule:run >> /dev/null 2>&1
```

| 任务 | 时间 | 说明 |
|------|------|------|
| `activity-logs:clean` | 每天 02:00 | 清理 90 天前的审计日志 |
| `geofeed:sync-remote --mode=test` | 每天 03:05 | 自动同步 GeoFeed 到远端（当前为 test 模式） |

查看任务列表：`php artisan schedule:list`

---

## GeoFeed 系统

- **当前模式**：Test 模式，同步到 `geofeed.test.csv`
- **自动同步**：每天 03:05 AM（Asia/Shanghai），配置在 `routes/console.php`
- **配置文件**：`/var/www/oa/config/geofeed.php`
- **本地 CSV**：`storage/app/geofeed/geofeed.test.csv`
- **同步元数据**：`storage/app/geofeed/.last_sync.json`
- **远端服务器**：`bunnycommunications.com`（SSH: `root@159.65.253.116`）
- **远端文件**：`/var/www/html/wordpress/geofeed.test.csv`（test）/ `geofeed.csv`（production）
- **远端缓存**：10 分钟

### 切换到生产模式
只需修改 `.env`（无需改代码）：
```
GEOFEED_REMOTE_URL=https://bunnycommunications.com/geofeed.csv
GEOFEED_UPLOAD_URL=https://bunnycommunications.com/geofeed-upload-prod.php?token=xxx
```
自动同步也需同步修改 `routes/console.php` 中的 `--mode=test` → `--mode=production`。

---

## 服务器配置

- **PHP 配置**：`/etc/php/8.3/fpm/php.ini`
  - `memory_limit = 256M`
  - `max_execution_time = 300`
  - `upload_max_filesize = 10M` / `post_max_size = 20M`
- **PHP-FPM 配置**：`/etc/php/8.3/fpm/pool.d/www.conf`
  - `pm.max_children = 10`，`pm.start_servers = 3`

---

## 常用 Artisan 命令

```bash
# 清理全部缓存
php artisan optimize:clear

# 查看所有路由
php artisan route:list

# 数据库迁移
php artisan migrate

# 清理旧活动日志（默认90天）
php artisan activity-logs:clean

# 系统备份（生成Excel到storage/backups/）
php artisan backup:data

# GeoFeed 手动同步到 test
php artisan geofeed:sync-remote --mode=test

# GeoFeed 手动同步到 production
php artisan geofeed:sync-remote --mode=production
```

---

## 界面结构（导航）

- **Income**（收入）：Overview、各客户账单、Add-ons
- **Expense**（支出）：Expense Overview、各 IP 供应商、IPT 供应商、数据中心
- **Workflows**（工单）：按月分组，显示当月及过去12个月
- **Compensation**（薪酬）：Salary Settings（薪酬配置，admin 专属）、Monthly Performance（绩效，admin 专属）
- **Documents**（文档）：Registration Docs
- **Metadata**（元数据）：Clients、Employees、IP Providers、IPT Providers、Datacenter Providers、Locations、Devices、IP Assets、GeoFeed Locations
