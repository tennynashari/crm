# FlowCRM - Customer & Lead Management System

Aplikasi CRM berbasis web untuk mengelola database customer & lead, mencatat komunikasi email otomatis dan multi-channel communication, dengan fitur Next Action tracking dan Area management.

## 🚀 Technology Stack

### Backend
- **Laravel 10** - PHP Framework
- **PostgreSQL** - Database
- **Laravel Sanctum** - API Authentication

### Frontend
- **Vue.js 3** - JavaScript Framework
- **Pinia** - State Management
- **Vue Router** - Routing
- **Tailwind CSS** - Styling
- **Vite** - Build Tool

## 📋 Prerequisites

- PHP >= 8.1
- Composer
- Node.js >= 18
- PostgreSQL >= 13

## 🛠️ Installation

### 1. Setup Database

```bash
# Login to PostgreSQL
psql -U postgres

# Create database and user
CREATE DATABASE crm;
CREATE USER crm WITH PASSWORD 'crm';
GRANT ALL PRIVILEGES ON DATABASE crm TO crm;
\q
```

### 2. Backend Setup

```bash
# Navigate to backend directory
cd backend

# Install dependencies
composer install

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed initial data (users, areas, lead statuses)
php artisan db:seed

# Start Laravel development server
php artisan serve
```

Backend akan berjalan di `http://localhost:8000`

### 3. Frontend Setup

```bash
# Navigate to frontend directory (open new terminal)
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

Frontend akan berjalan di `http://localhost:5173`

## 👤 Default Login Credentials

- **Email:** admin@flowcrm.test
- **Password:** password

Atau gunakan salah satu akun sales:
- **Email:** sales1@flowcrm.test / sales2@flowcrm.test
- **Password:** password

## 📁 Project Structure

```
crm/
├── backend/              # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       └── Api/
│   │   └── Models/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   └── api.php
│   └── config/
├── frontend/             # Vue.js SPA
│   ├── src/
│   │   ├── views/       # Pages
│   │   ├── layouts/     # Layout components
│   │   ├── stores/      # Pinia stores
│   │   ├── api/         # API configuration
│   │   └── router/      # Vue Router
│   └── public/
└── README.md
```

## 🎯 Key Features

### ✅ Customer Management
- Customer database dengan field lengkap
- Area / wilayah management
- Lead status tracking
- Next action planning dengan priority

### ✅ Communication History
- **Email:** Auto-logging (coming soon)
- **Manual Channels:** WhatsApp, Telephone, Instagram, TikTok, Marketplace, etc
- Timeline view untuk semua komunikasi

### ✅ Dashboard
- Total customers per area
- Leads by status
- Hot leads (high priority)
- Overdue follow-ups
- Action today
- Dormant leads

### ✅ Mobile-Friendly
- Responsive design
- Sticky action buttons on mobile
- Touch-friendly interface
- Optimized untuk kerja sales di lapangan

### ✅ Security
- Laravel Sanctum session-based authentication
- CSRF protection
- HTTP-only cookies
- No localStorage for sensitive data

## 🔄 API Endpoints

### Authentication
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/user` - Get authenticated user

### Customers
- `GET /api/customers` - List customers (with filters)
- `POST /api/customers` - Create customer
- `GET /api/customers/{id}` - Get customer detail
- `PUT /api/customers/{id}` - Update customer
- `DELETE /api/customers/{id}` - Delete customer
- `POST /api/customers/{id}/next-action` - Update next action

### Interactions
- `GET /api/interactions` - List interactions
- `POST /api/interactions` - Create interaction
- `GET /api/interactions/{id}` - Get interaction
- `PUT /api/interactions/{id}` - Update interaction
- `DELETE /api/interactions/{id}` - Delete interaction

### Master Data
- `GET /api/areas` - List areas
- `GET /api/lead-statuses` - List lead statuses

### Dashboard
- `GET /api/dashboard/stats` - Get dashboard statistics

## 📱 Mobile Features

1. **Responsive Layout** - Optimal di semua ukuran layar
2. **Touch-Friendly** - Buttons dan forms mudah diakses
3. **Sticky Actions** - Quick access ke Email, WhatsApp, Add History
4. **Optimized Timeline** - Easy scrolling komunikasi history

## 🔐 User Roles

- **Admin** - Full system access
- **Sales** - Customer management, follow-up
- **Marketing** - Lead input
- **Manager** - Monitoring & reporting

## 🎨 Customization

### Tailwind CSS
Edit `frontend/tailwind.config.js` untuk mengubah theme colors, spacing, dll.

### API Base URL
Edit `frontend/src/api/axios.js` untuk mengubah backend URL.

## 🚧 Coming Soon

- [ ] Email integration (IMAP/SMTP)
- [ ] Email auto-sync
- [ ] Advanced reporting
- [ ] Export to Excel
- [ ] Email templates
- [ ] Notification system
- [ ] File attachments

## 📝 License

This project is private and proprietary.

## 👨‍💻 Development

```bash
# Backend - Run tests
cd backend
php artisan test

# Frontend - Build for production
cd frontend
npm run build

# Frontend - Preview production build
npm run preview
```

## 🆘 Troubleshooting

### CORS Issues
Pastikan `SANCTUM_STATEFUL_DOMAINS` di `.env` backend sudah benar:
```
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

### Database Connection Error
Cek credentials di `backend/.env`:
```
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=crm
DB_USERNAME=crm
DB_PASSWORD=crm
```

### Frontend Cannot Connect to Backend
Pastikan Laravel server running di port 8000 dan proxy di `vite.config.js` sudah benar.

## 📞 Support

Untuk pertanyaan atau issues, silakan hubungi tim development.
