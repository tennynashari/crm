# AI Customer Prediction - Implementation Guide

## 📖 Overview

Fitur AI Customer Prediction menggunakan Machine Learning untuk memprediksi **Top 7 Customer Potensial** berdasarkan:
- 📊 History komunikasi (interactions)
- 💰 History penjualan (invoices)
- ⏰ Recency dan frequency
- 🎯 Customer engagement patterns

## 🏗️ Architecture

```
┌─────────────────────────────────────────┐
│         Frontend (Vue 3)                │
│  Dashboard.vue - Train & Predict UI     │
└──────────────┬──────────────────────────┘
               │ HTTP
┌──────────────▼──────────────────────────┐
│      Laravel Backend (API Gateway)      │
│  MLController - Proxy to Python         │
└──────────────┬──────────────────────────┘
               │ HTTP
┌──────────────▼──────────────────────────┐
│   Python FastAPI ML Service             │
│  - Feature Engineering                  │
│  - Rule-based Scoring Algorithm         │
│  - Top 7 Customer Prediction            │
└─────────────────────────────────────────┘
```

## 📁 Project Structure

```
crm/
├── ml-service/                    # Python ML Service (NEW)
│   ├── app/
│   │   ├── main.py               # FastAPI application
│   │   ├── database.py           # DB connection & queries
│   │   ├── features.py           # Feature engineering
│   │   └── predictor.py          # Prediction logic
│   ├── models/                   # Saved models (.pkl)
│   ├── logs/                     # Log files
│   ├── requirements.txt          # Python dependencies
│   ├── .env                      # Configuration
│   └── run.py                    # Service runner
│
├── backend/                      # Laravel Backend
│   ├── app/Http/Controllers/Api/
│   │   └── MLController.php     # ML API endpoints (NEW)
│   └── database/seeders/
│       └── MLDummyDataSeeder.php # Generate training data (NEW)
│
└── frontend/                     # Vue Frontend
    └── src/views/
        └── Dashboard.vue         # AI prediction UI (UPDATED)
```

## 🚀 Setup Instructions

### 1. Setup Python ML Service

**Windows:**
```bash
# Run setup script
setup-ml.bat

# Or manually:
cd ml-service
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

**Linux/Mac:**
```bash
# Run setup script
chmod +x setup-ml.sh
./setup-ml.sh

# Or manually:
cd ml-service
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### 2. Configure ML Service

Edit `ml-service/.env`:
```env
ML_SERVICE_HOST=127.0.0.1
ML_SERVICE_PORT=5000

# Database configuration (same as Laravel)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=crm_db
DB_USER=root
DB_PASSWORD=your_password
```

### 3. Configure Laravel

Add to `backend/.env`:
```env
ML_SERVICE_URL=http://127.0.0.1:5000
```

### 4. Generate Training Data

Generate 50+ customers dengan history 6 bulan:
```bash
cd backend
php artisan db:seed --class=MLDummyDataSeeder
```

Output:
- 55 customers dengan varied profiles
- High potential: Recent sales + many interactions
- Medium potential: Some activity
- Low potential: Old inactive customers
- New active: New but very engaged

### 5. Start Services

**Terminal 1 - Laravel Backend:**
```bash
cd backend
php artisan serve
# Running on http://127.0.0.1:8000
```

**Terminal 2 - Python ML Service:**
```bash
cd ml-service
venv\Scripts\activate      # Windows
source venv/bin/activate   # Linux/Mac
python run.py
# Running on http://127.0.0.1:5000
```

**Terminal 3 - Vue Frontend:**
```bash
cd frontend
npm run dev
# Running on http://localhost:5173
```

## 🎯 How to Use

### Step 1: Train the Model
1. Login ke dashboard
2. Lihat section "🤖 AI Customer Prediction"
3. Klik button **"🔄 Fetch & Train Model"**
4. Wait ~5-10 seconds
5. Model status akan update: "✓ Trained"

### Step 2: Get Predictions
1. Klik button **"🎯 Predict Top Customers"**
2. Top 7 customer potensial akan muncul
3. Setiap customer menampilkan:
   - Rank (1-7)
   - Company name & email
   - Prediction score
   - Reason (kenapa diprediksi potensial)

### Step 3: View Customer Detail
- Klik pada customer card untuk view detail
- Navigate ke customer detail page

## 🧮 How It Works

### Feature Engineering

Untuk setiap customer, sistem menghitung features:

**Communication Features:**
- Total interactions
- Interactions last 30/90/180 days
- Last interaction recency
- Email inbound/outbound count
- Channel diversity

**Sales Features:**
- Total invoices & revenue
- **Invoices last 90 days** (HIGH WEIGHT)
- **Revenue last 90 days** (HIGH WEIGHT)
- Average invoice value
- Last invoice recency

**Engagement Features:**
- Recent engagement score
- Sales momentum
- Lead status activity

### Scoring Algorithm

Rule-based scoring dengan weights:

```python
score = (
    invoices_last_90d × 30 +           # Sangat penting
    (revenue_last_90d / 1000) × 20 +   # Sangat penting
    total_invoices × 5 +                
    interactions_last_90d × 2 +         
    engagement_score × 0.5 +
    bonuses - penalties
)
```

**Bonuses:**
- Recent interaction (< 7 days): +10
- Has revenue in last 90 days: +15
- Active lead status: +5

**Penalties:**
- Very old last interaction (> 180 days): -10

### Why Top 7?

- Focus pada customer paling potensial
- Actionable untuk sales team
- Not overwhelming dengan too many options

## 🔌 API Endpoints

### Laravel API (Protected)

```
GET  /api/ml/health       - Check ML service status
POST /api/ml/train        - Train prediction model
POST /api/ml/predict      - Get top 7 predictions
GET  /api/ml/model-info   - Get model metadata
```

### Python ML Service

```
GET  /                    - Health check
GET  /health              - Service status
POST /train               - Train model
POST /predict             - Predict top customers
GET  /model-info          - Model information
```

## 📊 Model Metadata

Model menyimpan metadata:
```json
{
  "trained_at": "2026-03-10T10:30:00",
  "customers_count": 55,
  "features_count": 25,
  "model_type": "rule_based_scoring",
  "version": "1.0.0"
}
```

## 🔄 Workflow

```
1. User clicks "Train Model"
   ↓
2. Laravel → Python ML Service
   ↓
3. Python fetches all customer data
   ↓
4. Feature engineering (25+ features)
   ↓
5. Calculate scores using algorithm
   ↓
6. Save model to disk (models/*.pkl)
   ↓
7. Return success to Laravel
   ↓
8. Update UI: "Model Trained ✓"

---

9. User clicks "Predict"
   ↓
10. Laravel → Python ML Service
    ↓
11. Python loads saved model
    ↓
12. Sort customers by score DESC
    ↓
13. Return top 7 with details
    ↓
14. Display in dashboard UI
```

## 🐛 Troubleshooting

### ML Service not responding
```bash
# Check if service is running
curl http://127.0.0.1:5000/health

# Check Python service logs
cd ml-service
python run.py
```

### Database connection error
- Verify `ml-service/.env` database credentials
- Test connection: `mysql -h 127.0.0.1 -u root -p crm_db`

### Model not trained
- Click "Fetch & Train Model" first
- Check `ml-service/models/` directory for .pkl file

### Laravel can't connect to ML service
- Ensure `ML_SERVICE_URL` in `backend/.env`
- Check if Python service is running on port 5000

## 🎨 Frontend Components

Dashboard.vue includes:
- Training button dengan loading state
- Predict button (disabled until trained)
- Model info display
- Success/error messages
- Top 7 predictions list dengan gradient cards
- Clickable customer cards → detail page

## 📈 Future Enhancements

1. **ML Model Upgrade:**
   - Random Forest Regressor
   - Neural Network
   - Learn from actual outcomes

2. **More Features:**
   - Customer lifetime value
   - Churn prediction
   - Next best action recommendation

3. **Auto Re-training:**
   - Scheduled daily/weekly training
   - Incremental learning

4. **A/B Testing:**
   - Test different scoring algorithms
   - Compare prediction accuracy

5. **Analytics:**
   - Track prediction accuracy
   - Conversion rate from predicted customers
   - ROI measurement

## 📝 Notes

- Model perlu re-train periodic (weekly/monthly)
- Predictions cached untuk performance
- Works best dengan >= 50 customers dengan history
- Scoring algorithm dapat di-tune berdasarkan business needs

## 🎓 Technical Stack

**Backend:**
- Laravel 11 (API Gateway)
- MySQL (Data storage)

**ML Service:**
- Python 3.12
- FastAPI (Web framework)
- Pandas (Data manipulation)
- Scikit-learn (ML models)
- SQLAlchemy (Database ORM)
- Joblib (Model persistence)

**Frontend:**
- Vue 3 (Composition API)
- Tailwind CSS (Styling)

---

**Questions or issues?** Check logs in `ml-service/logs/` or Laravel logs in `backend/storage/logs/`.
