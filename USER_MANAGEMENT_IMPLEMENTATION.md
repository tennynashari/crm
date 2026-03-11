# User Management Multi-Tenant - Implementation Summary

## ✅ Implementation Complete

### Files Modified:

1. **backend/app/Http/Controllers/Api/UserController.php**
   - ✅ `index()`: Query from master DB with company_id filter
   - ✅ `show()`: Check company ownership before showing user
   - ✅ `store()`: Dual-write to master + tenant DB with same ID
   - ✅ `update()`: Update master + sync to tenant user_profiles
   - ✅ `destroy()`: Check company ownership + dual-delete

2. **backend/app/Models/User.php**
   - ✅ Set connection to 'master'
   - ✅ Add 'company_id' to fillable
   - ✅ Add company() relationship (optional)
   - ✅ Add scopeInCompany() for filtering

3. **backend/app/Console/Commands/SyncUserProfiles.php** (NEW)
   - ✅ Artisan command to sync existing users to tenant user_profiles
   - Usage: `php artisan tenant:sync-user-profiles`
   - Supports: `--company-id=X` to sync specific company only

## Key Features Implemented

### 1. Dual-Write Strategy
```php
// Create user writes to BOTH databases
DB::transaction(function() {
    // 1. Master DB: authentication + company assignment
    $userId = DB::connection('master')->table('users')->insertGetId([
        'company_id' => session('company_id'),
        ...
    ]);
    
    // 2. Tenant DB: operational FK relationships
    DB::connection('tenant')->table('user_profiles')->insert([
        'id' => $userId,  // SAME ID!
        ...
    ]);
});
```

### 2. Company Isolation
```php
// Users can only see/manage users from their own company
DB::connection('master')
    ->table('users')
    ->where('company_id', session('company_id'))
    ->get();
```

### 3. ID Synchronization
```
Master DB: users.id = 15
Tenant DB: user_profiles.id = 15  ← MUST BE SAME!

Why? FK relationships:
- customers.assigned_sales_id → user_profiles.id
- interactions.created_by_user_id → user_profiles.id
```

### 4. Transaction Safety
```php
DB::beginTransaction();
try {
    // Write to master
    // Write to tenant
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();  // Rollback both writes
}
```

## Testing Guide

### Step 1: Sync Existing Users

Run artisan command to sync existing users to tenant databases:

```bash
# Sync all companies
php artisan tenant:sync-user-profiles

# Or sync specific company
php artisan tenant:sync-user-profiles --company-id=1
```

Expected output:
```
Starting user profiles sync...
Processing company: Main Company (ID: 2)
  Found 5 users
    ✓ Synced user: admin@flowcrm.test
    ✓ Synced user: sales1@flowcrm.test
  Company Main Company: Synced 5, Skipped 0

Processing company: EcoGreen (ID: 1)
  Found 1 users
    ✓ Synced user: andhia@ecogreen.id
  Company EcoGreen: Synced 1, Skipped 0

Sync completed successfully!
```

### Step 2: Test Create User (Isolation)

#### Test A: Main Company Create User
```bash
# Login as: admin@flowcrm.test
# Frontend: POST /api/users
{
  "name": "Sales Main",
  "email": "sales.main@flowcrm.test",
  "password": "password123",
  "role": "sales",
  "is_active": true
}
```

**Verify:**
```sql
-- Master DB
SELECT id, name, email, company_id 
FROM crm_master.users 
WHERE email = 'sales.main@flowcrm.test';
-- Expected: company_id = 2 (Main Company)

-- Tenant DB
SELECT id, name, email 
FROM crm.user_profiles 
WHERE email = 'sales.main@flowcrm.test';
-- Expected: Same ID as master
```

#### Test B: EcoGreen Create User
```bash
# Login as: andhia@ecogreen.id
# Frontend: POST /api/users
{
  "name": "Sales Eco",
  "email": "sales.eco@ecogreen.id",
  "password": "password123",
  "role": "sales",
  "is_active": true
}
```

**Verify:**
```sql
-- Master DB
SELECT id, name, email, company_id 
FROM crm_master.users 
WHERE email = 'sales.eco@ecogreen.id';
-- Expected: company_id = 1 (EcoGreen)

-- Tenant DB
SELECT id, name, email 
FROM crm_ecogreen.user_profiles 
WHERE email = 'sales.eco@ecogreen.id';
-- Expected: Same ID as master
```

### Step 3: Test User List (Isolation)

#### Test A: Main Company View Users
```bash
# Login as: admin@flowcrm.test
# Frontend: GET /api/users

Expected response:
- Should ONLY see users with company_id = 2
- Should NOT see EcoGreen users (andhia@ecogreen.id, sales.eco@ecogreen.id)
```

#### Test B: EcoGreen View Users
```bash
# Login as: andhia@ecogreen.id
# Frontend: GET /api/users

Expected response:
- Should ONLY see users with company_id = 1
- Should NOT see Main Company users
```

### Step 4: Test Update User (Sync)

```bash
# Login as: admin@flowcrm.test
# Frontend: PUT /api/users/15
{
  "name": "Updated Name",
  "is_active": false
}
```

**Verify:**
```sql
-- Master DB
SELECT name, is_active 
FROM crm_master.users 
WHERE id = 15;
-- Expected: name = "Updated Name", is_active = false

-- Tenant DB
SELECT name, is_active 
FROM crm.user_profiles 
WHERE id = 15;
-- Expected: SAME as master (synced)
```

### Step 5: Test Delete User (Access Control)

#### Test A: Cannot Delete Other Company's User
```bash
# Login as: admin@flowcrm.test (company_id = 2)
# Try to delete EcoGreen user (company_id = 1)
# Frontend: DELETE /api/users/8

Expected response: 404 Not Found
Message: "User not found or access denied"
```

#### Test B: Can Delete Own Company's User
```bash
# Login as: admin@flowcrm.test
# Frontend: DELETE /api/users/15

Expected: Success
```

**Verify:**
```sql
-- Master DB
SELECT * FROM crm_master.users WHERE id = 15;
-- Expected: 0 rows (deleted)

-- Tenant DB
SELECT * FROM crm.user_profiles WHERE id = 15;
-- Expected: 0 rows (deleted)
```

### Step 6: Test FK Relationships

```bash
# Assign customer to newly created user
# Frontend: PUT /api/customers/123
{
  "assigned_sales_id": 15
}

Expected: Success (FK constraint satisfied)
```

**Verify:**
```sql
-- Tenant DB
SELECT c.company, c.assigned_sales_id, u.name 
FROM customers c
JOIN user_profiles u ON c.assigned_sales_id = u.id
WHERE c.id = 123;
-- Expected: Show customer with assigned user name
```

## Rollback / Troubleshooting

### If sync fails:
```bash
# Check user_profiles table exists
php artisan tinker
DB::connection('tenant')->table('user_profiles')->count();

# If table missing, check migrations
# User profiles table should have been created in tenant migrations
```

### If FK constraint error:
```sql
-- Check if user_profiles has the user
SELECT * FROM user_profiles WHERE id = 15;

-- If missing, insert manually or re-run sync command
```

### If company_id is NULL:
```sql
-- Update existing users to assign company
UPDATE crm_master.users 
SET company_id = 2, updated_at = NOW()
WHERE email LIKE '%@flowcrm.test';

UPDATE crm_master.users 
SET company_id = 1, updated_at = NOW()
WHERE email LIKE '%@ecogreen.id';
```

## Success Criteria

- ✅ User create: Dual-write to master + tenant DB
- ✅ User list: Only shows users from same company
- ✅ User update: Syncs changes to tenant DB
- ✅ User delete: Removes from both DBs
- ✅ ID synchronization: Master ID = Tenant ID
- ✅ FK relationships: customers.assigned_sales_id works
- ✅ Cross-tenant protection: Cannot access other company's users
- ✅ Transaction safety: All-or-nothing writes

## Notes

- Password is ONLY stored in master DB (users table)
- Tenant DB (user_profiles) does NOT store password
- Authentication always checks master DB
- Tenant DB is for operational queries (customers, interactions)
- Session stores company_id and tenant_db for context
