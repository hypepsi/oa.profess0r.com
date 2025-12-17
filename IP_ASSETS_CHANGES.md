# IP Assets 模块更新说明

## 📝 新增功能

### 1. 添加 Geo Location 字段
- 位置：Location 字段后面
- 用途：手动记录地理位置信息（如 US-West, EU-Central, Asia-Pacific）
- 变更会自动记录到 Activity Logs

### 2. 自动追踪关键字段变更时间

系统现在会自动记录以下字段的**最后修改时间**：

| 字段 | 追踪时间字段 | 说明 |
|------|------------|------|
| Status → Released | `released_at` | 当状态变为 Released 时记录 |
| Client | `client_changed_at` | 当客户转换时记录（如从客户A转给客户B） |
| Cost | `cost_changed_at` | 当成本变更时记录 |
| Price | `price_changed_at` | 当价格变更时记录 |

### 3. 编辑页展示历史记录

点击列表中的**编辑图标**进入编辑页，在表单下方可以看到 **"Change History Tracking"** 卡片：

- **Released At**（如果已 Released）- 红色卡片
- **Client Last Changed At**（如果客户有变更）- 蓝色卡片，显示当前客户
- **Cost Last Changed At**（如果成本有变更）- 黄色卡片，显示当前成本
- **Price Last Changed At**（如果价格有变更）- 黄色卡片，显示当前价格

如果没有任何变更记录，会显示灰色提示卡片。

## 🗄️ 数据库变更

新增字段（已创建迁移文件）：
```sql
ALTER TABLE ip_assets ADD COLUMN geo_location VARCHAR(255) NULL;
ALTER TABLE ip_assets ADD COLUMN released_at TIMESTAMP NULL;
ALTER TABLE ip_assets ADD COLUMN client_changed_at TIMESTAMP NULL;
ALTER TABLE ip_assets ADD COLUMN cost_changed_at TIMESTAMP NULL;
ALTER TABLE ip_assets ADD COLUMN price_changed_at TIMESTAMP NULL;
```

## 🔧 技术实现

### 文件修改列表

1. **数据库迁移**
   - `database/migrations/2025_12_16_000001_add_history_tracking_to_ip_assets_table.php` (新建)

2. **模型更新**
   - `app/Models/IpAsset.php` - 添加新字段到 fillable 和 casts

3. **Observer**
   - `app/Observers/IpAssetObserver.php` (新建) - 自动追踪字段变更时间
   - `app/Providers/AppServiceProvider.php` - 注册 IpAssetObserver

4. **资源文件**
   - `app/Filament/Resources/IpAssetResource.php` - 添加 geo_location 到表单和列表
   - `app/Filament/Resources/IpAssetResource/Pages/EditIpAsset.php` - 添加历史记录 Widget
   - `app/Filament/Resources/IpAssetResource/Widgets/ChangeHistoryWidget.php` (新建) - 历史记录组件
   - `resources/views/filament/resources/ip-asset-resource/widgets/change-history.blade.php` (新建) - 历史记录视图

5. **日志增强**
   - `app/Services/ActivityLogger.php` - 添加 Billing 模型标识符
   - `app/Providers/AppServiceProvider.php` - 添加 Billing 三模型到日志记录

## 📋 部署步骤

```bash
# 1. 运行迁移
php artisan migrate

# 2. 清除缓存
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 3. 如果使用 PM2，重启服务
pm2 restart oa-app
```

## ✅ 测试验收项

1. **Geo Location 字段**
   - [ ] 创建/编辑 IP Asset 时可以填写 Geo Location
   - [ ] 修改 Geo Location 会记录到 Activity Logs

2. **Status Released 追踪**
   - [ ] 将 IP Asset 状态改为 Released
   - [ ] 进入详情页查看 "Released At" 显示正确时间
   - [ ] 再改回 Active，"Released At" 应该清空

3. **Client 转换追踪**
   - [ ] 修改 IP Asset 的 Client（从客户A改为客户B）
   - [ ] 进入详情页查看 "Client Last Changed At" 显示正确时间

4. **Cost/Price 追踪**
   - [ ] 修改 Cost 或 Price
   - [ ] 进入详情页查看对应的 "xxx Last Changed At" 显示正确时间

5. **Activity Logs 记录**
   - [ ] 所有 IP Asset 的变更操作都记录到 Activity Logs
   - [ ] Billing 模块（BillingOtherItem, CustomerBillingPayment, BillingPaymentRecord）的操作也记录到日志

## 🎨 UI 风格

完全继承现有 Filament 系统风格：
- Badge 颜色：Released=红色, Active=绿色
- 图标：变更历史使用对应的 Heroicon
- 布局：Section 分组，可折叠
- 时间格式：统一使用 `Y-m-d H:i:s` + `Asia/Shanghai` 时区

## 📌 注意事项

1. **自动追踪**：不需要手动记录时间，系统会在字段变更时自动记录
2. **只显示有值的记录**：详情页的变更历史只显示有实际变更的字段
3. **Activity Logs 增强**：所有变更都会同步记录到 Activity Logs，可以查看完整的变更历史和具体内容

