# Testing ML Multi-Tenant Isolation

## Step 1: Train Main Company Model
1. Login sebagai: **admin@flowcrm.test**
2. Call API: `POST /api/ml/train`
3. Cek folder: `ml-service/models/crm/` harus ada file:
   - `customer_predictor.pkl`
   - `model_metadata.json`
4. Buka `model_metadata.json`, pastikan ada:
   ```json
   {
     "database": "crm",
     "customers_count": 67
   }
   ```

## Step 2: Train EcoGreen Model
1. Logout, login sebagai: **andhia@ecogreen.id**
2. Call API: `POST /api/ml/train`
3. Cek folder: `ml-service/models/crm_ecogreen/` harus ada file:
   - `customer_predictor.pkl`
   - `model_metadata.json`
4. Buka `model_metadata.json`, pastikan ada:
   ```json
   {
     "database": "crm_ecogreen",
     "customers_count": 12
   }
   ```

## Step 3: Test Prediction Isolation

### Main Company Prediction:
1. Login: **admin@flowcrm.test**
2. Call: `POST /api/ml/predict`
3. Response harus show:
   - **67 customers** (atau top 7 dari 67)
   - Model dari `/models/crm/customer_predictor.pkl`

### EcoGreen Prediction:
1. Login: **andhia@ecogreen.id**
2. Call: `POST /api/ml/predict`
3. Response harus show:
   - **12 customers** (atau top 7 dari 12)
   - Model dari `/models/crm_ecogreen/customer_predictor.pkl`

## Verification Points:

✅ **Each tenant has separate folder** in `ml-service/models/`
- `models/crm/` → Main Company
- `models/crm_ecogreen/` → EcoGreen

✅ **Each model file size different**
- Main Company model → larger (67 customers)
- EcoGreen model → smaller (12 customers)

✅ **Metadata shows correct database**
```bash
# Check Main Company
cat ml-service/models/crm/model_metadata.json

# Check EcoGreen
cat ml-service/models/crm_ecogreen/model_metadata.json
```

✅ **Predictions show different customer counts**
- Main Company: predictions dari 67 customers
- EcoGreen: predictions dari 12 customers

## Debug Log Location:
Check Laravel log for database parameter:
```bash
tail -f backend/storage/logs/laravel.log | grep "database"
```

Should show:
```
[Main Company] Training request for database: crm
[EcoGreen] Training request for database: crm_ecogreen
```
