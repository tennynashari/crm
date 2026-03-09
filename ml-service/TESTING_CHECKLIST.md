# ✅ Quick Testing Checklist - Lead Status Update

## Pre-requisites
- [ ] Python ML service sudah setup (venv + dependencies)
- [ ] Database configured di `ml-service/.env`
- [ ] Dummy data sudah di-generate (55 customers)
- [ ] Laravel backend running
- [ ] Frontend running

## Testing Steps

### 1. Start Python ML Service
```bash
cd ml-service
venv\Scripts\activate      # Windows
source venv/bin/activate   # Linux/Mac
python run.py
```

**Expected Output:**
```
Starting CRM ML Service on 127.0.0.1:5000
INFO:     Uvicorn running on http://127.0.0.1:5000
```

### 2. Test Feature Extraction (Manual)
```bash
curl http://127.0.0.1:5000/health
```

**Expected Response:**
```json
{
  "status": "no_model",
  "service": "CRM ML Service",
  "model_loaded": false
}
```

### 3. Train Model with New Lead Status Logic
Via Dashboard:
- [ ] Login ke dashboard
- [ ] Scroll ke "🤖 AI Customer Prediction"
- [ ] Click "🔄 Fetch & Train Model"
- [ ] Wait 5-10 seconds
- [ ] Should show: "✓ Model berhasil di-train! (55 customers)"

Or via API:
```bash
curl -X POST -H "Authorization: Bearer YOUR_TOKEN" \
  http://127.0.0.1:8000/api/ml/train
```

### 4. Verify Model Trained
- [ ] Model info shows: "Trained ✓"
- [ ] Last trained timestamp updated
- [ ] Customers count: 55

### 5. Get Predictions
Via Dashboard:
- [ ] Click "🎯 Predict Top Customers"
- [ ] Top 7 customers displayed
- [ ] Check each customer card

Or via API:
```bash
curl -X POST -H "Authorization: Bearer YOUR_TOKEN" \
  http://127.0.0.1:8000/api/ml/predict
```

### 6. Validate Predictions

**Check Lead Status Indicators:**
- [ ] Reason text includes emoji: 🔥 🏆 ⭐ ✓
- [ ] Lead status mentioned first in reason
- [ ] Examples:
  - "🔥 Hot Lead • 2 sales dalam 3 bulan • ..."
  - "🏆 Won • Revenue Rp ... • ..."
  - "⭐ Warm Lead • 5 interaksi • ..."

**Check Scores:**
- [ ] Scores in range: 100-500
- [ ] Hot/Warm/Qualified/Won customers have higher scores
- [ ] Top ranked customers make business sense

**Check Distribution:**
- [ ] Majority of Top 7 are high-value statuses
- [ ] At least 4-5 out of 7 are Hot/Warm/Qualified/Won
- [ ] Mix of: proven customers (Won) + promising leads (Hot)

### 7. Compare Individual Customers

**Scenario Test 1: Won Customer**
Find a "Won" customer in predictions:
- [ ] Should have high score (200+)
- [ ] Reason starts with "🏆 Won"
- [ ] Bonus +30 points applied

**Scenario Test 2: Hot Lead Customer**
Find a "Hot Lead" customer:
- [ ] Should have high score (150+)
- [ ] Reason starts with "🔥 Hot Lead"
- [ ] Bonus +25 points applied

**Scenario Test 3: Cold Lead (Should NOT be in Top 7)**
Check if any Cold/Dormant leads in top 7:
- [ ] Should be rare or none
- [ ] If present, must have exceptional sales history

### 8. Business Logic Validation

**Question Checklist:**
- [ ] Does Top 7 make sense for sales team?
- [ ] Are Won customers prioritized for repeat orders?
- [ ] Are Hot Leads highlighted for immediate action?
- [ ] Is balance good between history + potential?
- [ ] Would sales team actually use this list?

### 9. Performance Check

**Training Performance:**
- [ ] Training completed in < 15 seconds
- [ ] No errors in console
- [ ] Model saved successfully

**Prediction Performance:**
- [ ] Predictions returned in < 2 seconds
- [ ] All 7 customers have valid data
- [ ] No missing/null values

### 10. Edge Cases

**Test Case 1: Customer with Won status but no recent sales**
- [ ] Still high score due to +30 bonus
- [ ] Makes sense: repeat order potential

**Test Case 2: Customer with high sales but Cold status**
- [ ] High score from sales, not from status
- [ ] Shows sales history importance still primary

**Test Case 3: Hot Lead with no sales yet**
- [ ] Moderate score from bonus + interactions
- [ ] Makes sense: future potential

## 🐛 Troubleshooting

### Issue: Lead status not showing in reason
**Check:**
- Model re-trained after update?
- Feature extraction working?
- Database has lead_status_name field?

**Fix:**
```bash
# Re-train model
curl -X POST http://127.0.0.1:5000/train
```

### Issue: All scores too low/high
**Check:**
- Bonus values reasonable? (25-30 range)
- Sales scores not overwhelming status bonus?

**Fix:**
Edit `ml-service/app/predictor.py` and adjust bonus multipliers.

### Issue: Wrong customers in Top 7
**Check:**
- Training data quality
- Lead status distribution in seeder
- Scoring formula balance

**Fix:**
Re-run seeder and re-train:
```bash
cd backend
php artisan db:seed --class=MLDummyDataSeeder
# Then re-train model in dashboard
```

### Issue: No emoji in reasons
**Check:**
- is_hot_lead, is_warm_lead flags set correctly?
- Feature engineering extracting lead_status_name?

**Fix:**
Check Python logs for feature extraction errors.

## ✅ Success Criteria

**All Green if:**
1. ✅ Model trains successfully (55 customers)
2. ✅ Predictions return 7 customers with scores
3. ✅ Lead status emoji visible in reasons
4. ✅ High-value statuses in majority of Top 7
5. ✅ Scores make business sense (100-500 range)
6. ✅ No errors in any service
7. ✅ Dashboard UI shows predictions correctly
8. ✅ Customer cards clickable → detail page

**Bonus Points:**
- 🎯 Sales team validates predictions make sense
- 📈 Predictions better than random selection
- 🔄 System encourages proper lead status updates

## 📊 Expected Results Summary

| Metric | Expected Value | Actual | ✓/✗ |
|--------|---------------|--------|-----|
| Training Time | < 15s | ___ | ___ |
| Prediction Time | < 2s | ___ | ___ |
| Top 7 Count | 7 | ___ | ___ |
| Hot/Warm/Qual/Won % | > 60% | ___ | ___ |
| Score Range | 100-500 | ___ | ___ |
| Emoji Indicators | Yes | ___ | ___ |
| Business Logic Valid | Yes | ___ | ___ |

## 🎉 If All Tests Pass

**Congratulations!** 🎊

Your AI Customer Prediction system is now enhanced with lead status intelligence!

**Next Steps:**
1. ✅ Show to sales team for feedback
2. ✅ Monitor prediction accuracy over time
3. ✅ Adjust weights based on real outcomes
4. ✅ Add more features as needed
5. ✅ Deploy to production

**Remember:**
- Model needs re-training when lead statuses change
- Update scoring weights based on conversion data
- Keep lead statuses up to date for best results

---

**Happy Testing!** 🚀

Report any issues or unexpected behaviors for fine-tuning.
