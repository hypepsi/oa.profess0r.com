# OA System

> Internal operations management platform

## Stack

- **Backend**: Laravel 12 + PHP 8.3
- **Admin Panel**: Filament v3
- **Database**: MySQL 8
- **Frontend**: Livewire + Alpine.js + Tailwind CSS
- **Server**: Nginx + PHP-FPM

## Features

- 📊 IP Asset Management (CIDR, ASN, Provider tracking)
- 💰 Billing & Expense Automation
- 👥 Customer & Provider Relations
- 🔄 Workflow Management
- 📈 Real-time Statistics Widgets
- 🎨 Unified UI Design System

## Requirements

```
PHP >= 8.3
Composer >= 2.8
MySQL >= 8.0
Node.js >= 18 (for asset compilation)
```

## Deployment

### Production Setup

```bash
# Clone repository
git clone https://github.com/hypepsi/oa.profess0r.com.git
cd oa.profess0r.com

# Install dependencies
composer install --no-dev --optimize-autoloader

# Environment configuration
cp .env.example .env
php artisan key:generate

# Database migration
php artisan migrate --force

# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Web Server Configuration

**Nginx**:
```nginx
server {
    listen 80;
    server_name oa.example.com;
    root /var/www/oa/public;
    
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Development

```bash
# Install all dependencies
composer install
npm install

# Run migrations with seeders
php artisan migrate:fresh --seed

# Watch for changes (if using Vite)
npm run dev

# Serve locally
php artisan serve
```

## Maintenance

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches (as www-data to avoid permission issues)
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Reload PHP-FPM
sudo systemctl reload php8.3-fpm
```

## Architecture

```
app/
├── Filament/
│   ├── Pages/          # Custom pages (Billing, Expense)
│   ├── Resources/      # CRUD resources (IP Assets, Customers)
│   └── Widgets/        # Statistics & dashboard widgets
├── Models/             # Eloquent models
└── Services/           # Business logic (Calculators)

resources/
├── views/filament/     # Blade overrides
└── css/app.css         # Unified design system
```

## Security

- Environment variables in `.env` (not committed)
- User authentication via Filament auth
- Activity logging for audit trails
- Database-backed sessions

## License

Internal use only. Not licensed for external distribution.

---

**Timezone**: Asia/Shanghai  
**Locale**: English (en)
