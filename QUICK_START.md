# 🚀 Quick Start Guide - FlowCRM

## Langkah Cepat untuk Memulai

### 1️⃣ Setup Database PostgreSQL

```sql
-- Buka PostgreSQL command line atau pgAdmin
CREATE DATABASE crm;
CREATE USER crm WITH PASSWORD 'crm';
GRANT ALL PRIVILEGES ON DATABASE crm TO crm;
```

### 2️⃣ Jalankan Setup Script

**Windows:**
```cmd
cd e:\Code\crm
setup.bat
```

**Linux/Mac:**
```bash
cd /path/to/crm
chmod +x setup.sh
./setup.sh
```

### 3️⃣ Jalankan Aplikasi

**Opsi 1 - Menggunakan Start Script (Windows):**
```cmd
start.bat
```

**Opsi 2 - Manual (2 Terminal):**

Terminal 1 - Backend:
```cmd
cd backend
php artisan serve
```

Terminal 2 - Frontend:
```cmd
cd frontend
npm run dev
```

### 4️⃣ Akses Aplikasi

Buka browser: **http://localhost:5173**

Login dengan:
- Email: `admin@flowcrm.test`
- Password: `password`

## ✅ Fitur yang Sudah Bisa Digunakan

### Dashboard
- ✅ Total customers
- ✅ Hot leads (high priority)
- ✅ Overdue follow-ups
- ✅ Action today
- ✅ Customers by area
- ✅ Leads by status

### Customer Management
- ✅ List customers dengan filter (search, area, status, source)
- ✅ Tambah customer baru
- ✅ Detail customer
- ✅ Edit customer info
- ✅ Update lead status
- ✅ Update area

### Next Action
- ✅ Set next action date
- ✅ Set priority (low/medium/high)
- ✅ Set action plan
- ✅ Track overdue actions

### Communication History
- ✅ View timeline semua komunikasi
- ✅ Add manual history untuk:
  - WhatsApp
  - Telephone
  - Instagram
  - TikTok
  - Marketplace (Tokopedia, Shopee, Lazada)
  - Website Chat
  - Other channels

### Quick Actions
- ✅ Open WhatsApp (langsung ke chat)
- ✅ Call (telepon langsung dari mobile)
- ✅ Add interaction history

### Mobile Features
- ✅ Responsive design
- ✅ Touch-friendly interface
- ✅ Sticky action buttons di mobile
- ✅ Easy scrolling timeline

## 📱 Cara Kerja di Mobile

1. **Login** menggunakan HP Anda
2. **Dashboard** muncul dengan statistik lengkap
3. **List Customer** dengan filter mudah
4. **Tap Customer** untuk lihat detail
5. **Sticky Buttons** di bawah:
   - ✉️ Email (coming soon)
   - 💬 WA → Langsung buka WhatsApp
   - 📝 History → Input komunikasi manual
6. **Next Action Panel** untuk planning
7. **Timeline** history komunikasi mudah discroll

## 🎯 Workflow Sales

1. **Morning Check**
   - Buka Dashboard
   - Cek "Action Today" & "Overdue"

2. **Follow-up Customer**
   - Buka customer dari list
   - Chat via WhatsApp (klik tombol WA)
   - Setelah chat, klik "Add History"
   - Input hasil komunikasi

3. **Update Next Action**
   - Set tanggal next follow-up
   - Tulis action plan
   - Set priority

4. **Update Lead Status**
   - Pilih status sesuai progress
   - System auto log perubahan

## 🔧 Troubleshooting

### Backend Error "Connection Refused"
```bash
# Pastikan PostgreSQL running
# Windows: Services → PostgreSQL
# Linux: sudo systemctl status postgresql
```

### Frontend Cannot Connect
```bash
# Pastikan backend jalan di port 8000
# Cek: http://localhost:8000/api/user
```

### CORS Error
```bash
# Edit backend/.env
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

## 📊 Data Default

### Users
| Email | Password | Role |
|-------|----------|------|
| admin@flowcrm.test | password | Admin |
| sales1@flowcrm.test | password | Sales |
| sales2@flowcrm.test | password | Sales |

### Areas
- Jakarta (JKT)
- Bandung (BDG)
- Surabaya (SBY)
- Medan (MDN)
- Bali (DPS)

### Lead Statuses
- New Lead
- Contacted
- Qualified
- Proposal
- Negotiation
- Won
- Lost
- On Hold

## 🎨 Customization

### Ubah Warna Theme
Edit: `frontend/tailwind.config.js`

### Ubah Port
Backend: `php artisan serve --port=9000`
Frontend: Edit `frontend/vite.config.js`

### Tambah Area Baru
1. Login sebagai Admin
2. Gunakan API atau langsung ke database
3. Atau tambahkan di seeder

## 📚 Next Steps

1. Test semua fitur
2. Tambahkan customer pertama
3. Lakukan interaksi
4. Set next action
5. Track di dashboard

## 🆘 Butuh Bantuan?

Lihat dokumentasi lengkap:
- `README.md` - Full documentation
- `API_DOCUMENTATION.md` - API reference

Happy CRM-ing! 🎉
