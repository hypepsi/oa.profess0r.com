# OA Management System

企业级 OA 管理系统，基于 Laravel 12 和 Filament 3 构建。

## 技术栈

- **Backend**: Laravel 12
- **Admin Panel**: Filament 3
- **Database**: MySQL 8.0
- **CSS Framework**: Tailwind CSS 3
- **PHP**: 8.2+

---

## 🎨 UI/UX 设计规范

> **重要提示**：所有 AI 工具在修改或新增页面时，必须严格遵循以下规范！

### 核心原则

#### 1. 使用原生 Tailwind CSS

**✅ DO（推荐做法）**
```blade
<div class="text-sm text-gray-500 font-medium">
    Employee Name
</div>
```

**❌ DON'T（禁止做法）**
```blade
<!-- 不要创建自定义 CSS 类 -->
<div class="oa-card-title">Employee Name</div>

<!-- 不要在 app.css 中定义自定义样式 -->
.oa-card-title { ... }
```

#### 2. 遵循 Filament 设计语言

- 所有页面必须与 Filament Admin 的默认风格保持一致
- 优先使用 Filament 内置组件（Widget、InfoList、Section 等）
- 避免过度自定义样式，保持"原生感"

#### 3. 视觉层级规范

**使用颜色和字号区分层级，避免过度使用粗体**

```blade
<!-- 主标题 -->
<h2 class="text-lg font-semibold text-gray-900">Main Title</h2>

<!-- 次要标题 -->
<h3 class="text-base font-medium text-gray-700">Subtitle</h3>

<!-- 普通内容 -->
<p class="text-sm text-gray-600">Content</p>

<!-- 元数据/辅助信息 -->
<span class="text-xs text-gray-500">Meta info</span>
```

**字体大小标准**
- `text-lg` (18px): 页面主标题
- `text-base` (16px): 次级标题、重要数据
- `text-sm` (14px): 正文、标签
- `text-xs` (12px): 辅助信息、时间戳

**颜色标准**
- `text-gray-900`: 主要内容
- `text-gray-700`: 次要内容
- `text-gray-600`: 普通内容
- `text-gray-500`: 辅助信息
- `text-gray-400`: 占位符

#### 4. 统计卡片规范

**必须使用 Filament Widget**

```php
// ✅ 正确：使用 StatsOverviewWidget
protected function getHeaderWidgets(): array
{
    return [
        \App\Filament\Widgets\CustomerBillingStats::class,
    ];
}
```

```blade
<!-- ❌ 错误：手动写卡片 HTML -->
<div class="grid grid-cols-4 gap-4">
    <div class="bg-white p-4">...</div>
</div>
```

**卡片布局要求**
- 统计卡片必须横向显示（默认 4 列布局）
- 使用 `Stat::make()` 构建
- 保持与 Filament 默认卡片样式一致

#### 5. 导航与标题一致性

**导航标签必须与页面标题完全一致**

```php
// ✅ 正确
protected static ?string $navigationLabel = 'Salary Settings';
protected static ?string $pluralModelLabel = 'Salary Settings';
protected static ?string $title = 'Salary Settings';
```

```php
// ❌ 错误
protected static ?string $navigationLabel = 'Salary Settings';
protected static ?string $title = 'Employee Compensations'; // 不一致！
```

### 组件使用规范

#### 表单字段对齐

```php
Forms\Components\Section::make('Basic Information')
    ->schema([
        Forms\Components\Select::make('employee_id')
            ->columnSpanFull(), // 全宽字段

        Forms\Components\TextInput::make('base_salary'),
        Forms\Components\TextInput::make('commission_rate'),
    ])
    ->columns(2); // 两列布局
```

#### Widget 数据传递

```php
// ✅ 正确：通过 ::make() 传递数据
protected function getHeaderWidgets(): array
{
    return [
        CustomerBillingStats::make([
            'customerId' => $this->customer->id,
        ]),
    ];
}

// 在 Widget 中接收
public ?int $customerId = null;

protected function getStats(): array
{
    $customer = Customer::find($this->customerId);
    // ...
}
```

```php
// ❌ 错误：使用 #[Reactive]
#[Reactive]
public ?int $customerId = null; // Livewire 中不工作
```

### 常见错误与修正

| 错误做法 | 正确做法 |
|---------|---------|
| 创建 `oa-*` 自定义类 | 直接使用 Tailwind utilities |
| 手写 HTML 卡片 | 使用 Filament Widget |
| 过度使用 `font-bold` | 用 `font-medium` + 颜色区分层级 |
| 导航与标题不一致 | 确保所有地方命名统一 |
| 统计卡片竖向排列 | 使用 Widget 确保横向显示 |

### 开发检查清单

在提交代码前，请确认：

- [ ] 没有在 `app.css` 中添加自定义 CSS 类
- [ ] 所有统计卡片都使用 Filament Widget
- [ ] 字体大小符合规范（lg/base/sm/xs）
- [ ] 导航标签与页面标题一致
- [ ] 视觉层级清晰（颜色 + 字号，不是加粗）
- [ ] 样式与 Filament 原生组件保持一致

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
