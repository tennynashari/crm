# Test ML Service - Quick Verification

## Step-by-Step Testing Guide

### 1. Start Python ML Service

**Windows:**
```powershell
cd ml-service
python -m venv venv                    # If not done
venv\Scripts\activate
pip install -r requirements.txt        # If not done
python run.py
```

**Expected Output:**
```
Starting CRM ML Service on 127.0.0.1:5000
INFO:     Started server process
INFO:     Waiting for application startup.
INFO:     Application startup complete.
INFO:     Uvicorn running on http://127.0.0.1:5000
```

### 2. Test ML Service Endpoints

**Test Health Check:**
```bash
curl http://127.0.0.1:5000/health
```

Expected: JSON response with `status: "healthy"` or `status: "no_model"`

### 3. Add ML_SERVICE_URL to Laravel .env

Edit `backend/.env`:
```env
ML_SERVICE_URL=http://127.0.0.1:5000
```

### 4. Test from Laravel

**Test Health:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" http://127.0.0.1:8000/api/ml/health
```

**Train Model:**
```bash
curl -X POST -H "Authorization: Bearer YOUR_TOKEN" http://127.0.0.1:8000/api/ml/train
```

Expected: Success message + customers_count: 55

**Get Predictions:**
```bash
curl -X POST -H "Authorization: Bearer YOUR_TOKEN" http://127.0.0.1:8000/api/ml/predict
```

Expected: Array of 7 customers with scores

### 5. Test in Browser

1. Start all services:
   - Laravel: `cd backend && php artisan serve`
   - Python ML: `cd ml-service && python run.py`
   - Vue: `cd frontend && npm run dev`

2. Login to dashboard: http://localhost:5173

3. Look for "🤖 AI Customer Prediction" section

4. Click "🔄 Fetch & Train Model"
   - Wait ~5-10 seconds
   - Should show: "✓ Model berhasil di-train! (55 customers)"

5. Click "🎯 Predict Top Customers"
   - Should display 7 customers ranked by score
   - Each showing: rank, company, email, score, reason

6. Click on any customer card
   - Should navigate to customer detail page

## Common Issues

### Port 5000 already in use
```bash
# Windows
netstat -ano | findstr :5000
taskkill /PID <PID> /F

# Linux/Mac
lsof -ti:5000 | xargs kill -9
```

### Database connection error in Python
- Check `ml-service/.env` database credentials
- Ensure PostgreSQL is running (or MySQL if you changed it)
- Test: `psql -U crm -d crm` (PostgreSQL) or `mysql -u root -p crm_db` (MySQL)

### CORS errors in browser
- Check FastAPI CORS settings in `ml-service/app/main.py`
- Ensure `allow_origins` includes frontend URL

### Model training takes too long
- Normal for first time: ~5-10 seconds for 55 customers
- If > 30 seconds, check database query performance

## Verification Checklist

- [ ] Python service starts on port 5000
- [ ] Health endpoint responds
- [ ] Database connection works
- [ ] Laravel can connect to Python service
- [ ] Model trains successfully (55 customers)
- [ ] Predictions return 7 customers
- [ ] Dashboard UI shows AI section
- [ ] Train button works
- [ ] Predict button works
- [ ] Customer cards are clickable
- [ ] Scores are reasonable (> 0)

## Expected Results

**Top customers should have:**
- High scores (> 100)
- Recent sales (invoices_last_90d > 0)
- Active interactions
- Reasons like: "3 sales dalam 3 bulan • Revenue Rp X • Y interaksi"

**Model info should show:**
- trained_at: recent timestamp
- customers_count: 55
- model_type: "rule_based_scoring"

## Performance Benchmarks

- Training: 5-10 seconds
- Prediction: < 1 second
- Dashboard load: < 2 seconds

## Next Steps After Verification

1. **Tune Scoring Algorithm:**
   - Adjust weights in `ml-service/app/predictor.py`
   - Re-train and compare results

2. **Add More Data:**
   - Run seeder multiple times for more customers
   - Add real data from production

3. **Monitor Accuracy:**
   - Track which predicted customers actually convert
   - Refine algorithm based on results

4. **Schedule Re-training:**
   - Set up cron job to re-train weekly
   - Keep model fresh with latest data

5. **Extend to ML Model:**
   - Collect outcome data (did customer convert?)
   - Train Random Forest or other ML models
   - Compare with rule-based scoring

---

**Ready to test!** Follow the steps above and report any issues.
