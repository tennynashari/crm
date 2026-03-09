# Lead Status Scoring - AI Customer Prediction

## 📊 Lead Status Bonus System

Model AI prediction sekarang memberikan **bonus score** untuk lead status tertentu yang menunjukkan customer potensial tinggi.

## 🎯 Lead Status Categories

### HIGH VALUE Lead Statuses (Bonus Points)

| Lead Status | Bonus Points | Prioritas | Deskripsi |
|-------------|--------------|-----------|-----------|
| **Won** 🏆 | +30 | Tertinggi | Customer sudah closing, sangat potensial untuk repeat order |
| **Hot Lead** 🔥 | +25 | Sangat Tinggi | Siap closing, butuh follow up intensif |
| **Warm Lead** ⭐ | +20 | Tinggi | Potensial tinggi, perlu nurturing lebih |
| **Qualified** ✓ | +20 | Tinggi | Sudah qualified, dalam proses deal |

### STANDARD Lead Statuses

| Lead Status | Bonus | Deskripsi |
|-------------|-------|-----------|
| Contacted | +5 (active) | Sudah dihubungi, perlu follow up |
| New Lead | +5 (active) | Lead baru, perlu approach |

### LOW VALUE Lead Statuses

| Lead Status | Impact | Deskripsi |
|-------------|--------|-----------|
| Cold Lead | 0 | Tidak aktif, perlu re-engage |
| Dormant Lead | -10 (penalty) | Tidak aktif lama |
| Lost Lead | -10 (penalty) | Deal gagal |

## 🧮 Scoring Formula Update

```python
Total Score = 
    # Sales & Revenue (tetap prioritas utama)
    + invoices_last_90d × 30
    + (revenue_last_90d / 1000) × 20
    + total_invoices × 5
    
    # Interactions
    + interactions_last_90d × 2
    + interactions_last_30d × 1
    
    # LEAD STATUS BONUS (NEW!)
    + is_won × 30              # Customer yang sudah menang
    + is_hot_lead × 25         # Hot lead bonus
    + is_warm_lead × 20        # Warm lead bonus
    + is_qualified × 20        # Qualified bonus
    
    # Other bonuses
    + engagement_score × 1.5
    + recency_bonuses
    + active_status × 5
    
    # Penalties
    - old_interaction_penalty
```

## 📈 Impact Examples

### Scenario 1: High Potential Customer
```
Customer A:
- Lead Status: Hot Lead
- Invoices last 90d: 2
- Revenue last 90d: Rp 10,000,000
- Interactions last 90d: 5

Score Calculation:
- Sales: 2 × 30 = 60
- Revenue: (10,000 / 1000) × 20 = 200
- Interactions: 5 × 2 = 10
- Hot Lead Bonus: 25
- Total: 295+ (VERY HIGH)

Display: "🔥 Hot Lead • 2 sales dalam 3 bulan • 5 interaksi"
```

### Scenario 2: Won Customer (Repeat Order Potential)
```
Customer B:
- Lead Status: Won
- Invoices last 90d: 1
- Revenue last 90d: Rp 5,000,000
- Interactions last 90d: 3

Score Calculation:
- Sales: 1 × 30 = 30
- Revenue: (5,000 / 1000) × 20 = 100
- Interactions: 3 × 2 = 6
- Won Bonus: 30
- Total: 166+ (HIGH)

Display: "🏆 Won • 1 sales dalam 3 bulan • Revenue Rp 5,000,000"
```

### Scenario 3: Warm Lead + Good History
```
Customer C:
- Lead Status: Warm Lead
- Invoices last 90d: 0
- Total Invoices: 3
- Interactions last 90d: 8

Score Calculation:
- Total Sales: 3 × 5 = 15
- Interactions: 8 × 2 = 16
- Warm Lead Bonus: 20
- Total: 51+ (MEDIUM-HIGH)

Display: "⭐ Warm Lead • 3 total sales • 8 interaksi"
```

### Scenario 4: Cold Lead (Low Priority)
```
Customer D:
- Lead Status: Cold Lead
- Invoices last 90d: 0
- Total Invoices: 1
- Interactions last 90d: 1

Score Calculation:
- Total Sales: 1 × 5 = 5
- Interactions: 1 × 2 = 2
- Cold Lead Bonus: 0
- Total: 7 (LOW)

Not in Top 7 predictions
```

## 🎨 UI Display Updates

Prediction reasons sekarang menampilkan lead status sebagai **prioritas pertama**:

```vue
<!-- Example display -->
<div class="prediction-card">
  <h4>PT Maju Jaya 123</h4>
  <p class="reason">
    🔥 Hot Lead • 2 sales dalam 3 bulan • Revenue Rp 10,000,000
  </p>
  <div class="score">295.0</div>
</div>
```

## 🔄 Seeder Distribution

MLDummyDataSeeder sekarang smart distribution:

**High Potential Customers (10):**
- 70% dapat: Hot Lead, Warm Lead, Qualified, Won
- 30% dapat: Contacted

**New Active Customers (10):**
- 60% dapat: Hot Lead, Warm Lead, Qualified
- 40% dapat: Contacted, New Lead

**Medium Potential (20):**
- 40% dapat: Warm Lead, Contacted
- 60% random

**Low Potential (15):**
- Mostly: Cold Lead, Dormant Lead, Contacted

## 📊 Expected Top 7 Predictions

Dengan system ini, Top 7 predictions akan prioritaskan:

1. **Won customers** dengan sales recent → Repeat order potential
2. **Hot Leads** dengan interaction aktif → Ready to close
3. **Warm Leads** dengan sales history bagus → High conversion chance
4. **Qualified customers** dalam deal pipeline → Monitoring progress
5. Customers dengan sales momentum tinggi (regardless of status)

## 🔍 How to Verify

After re-training model:

1. **Check Top 7:**
   - Mayoritas harus Hot/Warm/Qualified/Won
   - Scores harus signifikan lebih tinggi (100+)

2. **Check Reasons:**
   - Lead status muncul di reason pertama
   - Emoji indicators: 🔥 🏆 ⭐ ✓

3. **Test Edge Cases:**
   - Customer Won tanpa sales recent → Still high score karena bonus +30
   - Hot Lead + sales → Very high score (kombinasi bonus)

## 🎯 Business Logic

**Why this makes sense:**

1. **Won = High Retention Value**
   - Customer yang sudah closing lebih mudah closing lagi
   - Repeat order chance tinggi
   - Relationship sudah terbangun

2. **Hot Lead = Ready to Close**
   - Sales team focus di sini untuk quick wins
   - Conversion probability tinggi
   - ROI effort tinggi

3. **Warm Lead = Nurturing Priority**
   - Butuh consistent follow up
   - Potensial convert jadi hot/won
   - Good investment

4. **Qualified = Pipeline Progress**
   - Track deal progress
   - Ensure tidak stuck
   - Push to closing

## 🔧 Tuning Recommendations

Jika hasil prediksi tidak sesuai ekspektasi:

**Too many Won customers in top 7:**
- Reduce Won bonus: 30 → 20

**Not enough Hot Leads showing:**
- Increase Hot Lead bonus: 25 → 35

**Want more balance between status and sales:**
- Adjust ratio antara lead status bonus vs sales score

**Current weights good for:**
- Balanced focus: Lead status + Sales history
- Sales team yang prioritize hot leads + repeat customers
- CRM dengan clear lead qualification process

## 📝 Notes

- Lead status bonus tetap **secondary** setelah sales/revenue
- Customer dengan sales tinggi tetap top priority regardless of status
- System encourages proper lead status management
- Model perlu re-train untuk apply changes

---

**Update Model:** Setiap ada perubahan lead status logic, run:
```bash
# Re-train model with new bonus system
curl -X POST http://127.0.0.1:8000/api/ml/train
```
