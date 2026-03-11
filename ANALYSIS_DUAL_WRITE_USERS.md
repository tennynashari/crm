# Analisa: Dual Database User Management Strategy

## Current Architecture (Berdasarkan Input User)

### Master Database: `crm_master`
```sql
Table: users
├── id
├── name
├── email
├── password
├── role
├── company_id     ✅ (SUDAH ADA)
├── is_active
└── timestamps
```
**Purpose:** Registry semua user dari semua company

### Tenant Database: `crm` / `crm_ecogreen`
```sql
Table: user_profiles
├── id
├── name
├── email
├── role
├── is_active
└── timestamps
```
**Purpose:** User profile untuk tenant-specific operations

## Strategi: Yes, Insert ke Kedua Tempat ✅

### Kenapa Harus Dual Write?

#### 1. **Master DB (crm_master.users)** - Authentication & Authorization
**Fungsi:**
- ✅ Login/Authentication (email + password)
- ✅ User registry dari semua company
- ✅ Company assignment (company_id)
- ✅ Cross-tenant user management

**Use Cases:**
- Login endpoint: Check `crm_master.users`
- User list for admin: Query `crm_master.users WHERE company_id = ?`
- Company switching: Lookup company dari user

#### 2. **Tenant DB (user_profiles)** - Operational Data
**Fungsi:**
- ✅ Foreign key relationships (customers.assigned_sales_id)
- ✅ Audit logs (created_by_user_id)
- ✅ Tenant-specific queries (tanpa join ke master)

**Use Cases:**
- Customer assignment: `UPDATE customers SET assigned_sales_id = ?`
- Interaction tracking: `INSERT interactions (created_by_user_id, ...)`
- Dashboard stats: `COUNT(*) FROM customers WHERE assigned_sales_id = ?`

### Flow: Create User Baru

```
User andhia@ecogreen.id create user: "sales@ecogreen.id"
                    ↓
UserController::store()
                    ↓
┌─────────────────────────────────────────────┐
│ DB::beginTransaction()                      │
├─────────────────────────────────────────────┤
│                                             │
│ 1. INSERT TO MASTER DB                      │
│    Connection: 'master'                     │
│    Database: crm_master                     │
│    Table: users                             │
│                                             │
│    INSERT INTO users (                      │
│        name, email, password,               │
│        role, company_id,                    │
│        is_active                            │
│    ) VALUES (                               │
│        'Sales Person',                      │
│        'sales@ecogreen.id',                 │
│        '$hashed_password',                  │
│        'sales',                             │
│        1,  ← session('company_id')          │
│        true                                 │
│    )                                        │
│    RETURNING id = 15                        │
│                                             │
├─────────────────────────────────────────────┤
│                                             │
│ 2. INSERT TO TENANT DB                      │
│    Connection: 'tenant'                     │
│    Database: crm_ecogreen                   │
│    Table: user_profiles                     │
│                                             │
│    INSERT INTO user_profiles (              │
│        id,    ← SAME ID dari master         │
│        name,                                │
│        email,                               │
│        role,                                │
│        is_active                            │
│    ) VALUES (                               │
│        15,    ← Keep ID consistency         │
│        'Sales Person',                      │
│        'sales@ecogreen.id',                 │
│        'sales',                             │
│        true                                 │
│    )                                        │
│                                             │
├─────────────────────────────────────────────┤
│ DB::commit()                                │
└─────────────────────────────────────────────┘
                    ↓
            Return success
```

### Important: ID Synchronization

**CRITICAL:** ID harus sama di kedua tempat!

```php
// ✅ CORRECT: Use same ID
$masterUser = DB::connection('master')
    ->table('users')
    ->insertGetId([...]);  // Returns ID: 15

DB::connection('tenant')
    ->table('user_profiles')
    ->insert([
        'id' => $masterUser,  // ✅ Same ID: 15
        ...
    ]);

// ❌ WRONG: Auto-increment in both tables
$masterUser = DB::connection('master')
    ->table('users')
    ->insertGetId([...]);  // Returns ID: 15

DB::connection('tenant')
    ->table('user_profiles')
    ->insertGetId([...]);  // ❌ Auto ID: 8 (different!)
```

**Kenapa ID harus sama?**
```php
// Scenario: Customer assigned to sales
// Tenant DB
customers.assigned_sales_id = 15

// When we need user details, query master:
// Master DB
users.id = 15  ← Must match!
```

### Code Implementation

#### UserController.php - Create User

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:master.users,email',  // ✅ Check uniqueness in master
        'password' => 'required|string|min:8',
        'role' => ['required', Rule::in(['admin', 'sales'])],
        'is_active' => 'boolean',
    ]);

    $validated['password'] = Hash::make($validated['password']);
    
    // ✅ Get company context from session
    $companyId = session('company_id');
    $tenantDb = session('tenant_db');
    
    if (!$companyId || !$tenantDb) {
        return response()->json([
            'message' => 'Invalid session. Please login again.'
        ], 400);
    }

    DB::beginTransaction();
    try {
        // ✅ Step 1: Insert to Master DB
        $userId = DB::connection('master')->table('users')->insertGetId([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'company_id' => $companyId,
            'is_active' => $validated['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        Log::info('User created in master DB', [
            'user_id' => $userId,
            'email' => $validated['email'],
            'company_id' => $companyId
        ]);
        
        // ✅ Step 2: Insert to Tenant DB with SAME ID
        DB::connection('tenant')->table('user_profiles')->insert([
            'id' => $userId,  // ✅ Use same ID
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        Log::info('User profile created in tenant DB', [
            'user_id' => $userId,
            'database' => $tenantDb
        ]);
        
        DB::commit();
        
        // ✅ Return user data from master
        $user = DB::connection('master')
            ->table('users')
            ->find($userId);
        
        return response()->json($user, 201);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('User creation failed', [
            'error' => $e->getMessage(),
            'company_id' => $companyId,
            'tenant_db' => $tenantDb
        ]);
        
        return response()->json([
            'message' => 'Failed to create user',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

#### UserController.php - Update User

```php
public function update(Request $request, $id)
{
    $companyId = session('company_id');
    
    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'email' => ['sometimes', 'required', 'email', 
                    Rule::unique('master.users')->ignore($id)],
        'password' => 'nullable|string|min:8',
        'role' => ['sometimes', 'required', Rule::in(['admin', 'sales'])],
        'is_active' => 'boolean',
    ]);

    if (!empty($validated['password'])) {
        $validated['password'] = Hash::make($validated['password']);
    } else {
        unset($validated['password']);
    }

    DB::beginTransaction();
    try {
        // ✅ Step 1: Check user belongs to company
        $user = DB::connection('master')
            ->table('users')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found or access denied'
            ], 404);
        }
        
        // ✅ Step 2: Update Master DB
        $validated['updated_at'] = now();
        DB::connection('master')
            ->table('users')
            ->where('id', $id)
            ->update($validated);
        
        Log::info('User updated in master DB', ['user_id' => $id]);
        
        // ✅ Step 3: Sync to Tenant DB (exclude password)
        $tenantData = array_filter($validated, function($key) {
            return in_array($key, ['name', 'email', 'role', 'is_active', 'updated_at']);
        }, ARRAY_FILTER_USE_KEY);
        
        DB::connection('tenant')
            ->table('user_profiles')
            ->where('id', $id)
            ->update($tenantData);
        
        Log::info('User profile synced to tenant DB', ['user_id' => $id]);
        
        DB::commit();
        
        // ✅ Return updated user from master
        $updatedUser = DB::connection('master')
            ->table('users')
            ->find($id);
        
        return response()->json($updatedUser);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('User update failed', [
            'user_id' => $id,
            'error' => $e->getMessage()
        ]);
        
        throw $e;
    }
}
```

#### UserController.php - Delete User

```php
public function destroy($id)
{
    $companyId = session('company_id');
    
    DB::beginTransaction();
    try {
        // ✅ Check user belongs to company
        $user = DB::connection('master')
            ->table('users')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found or access denied'
            ], 404);
        }
        
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }
        
        // ✅ Step 1: Delete from Tenant DB first (FK constraint)
        DB::connection('tenant')
            ->table('user_profiles')
            ->where('id', $id)
            ->delete();
        
        Log::info('User profile deleted from tenant DB', ['user_id' => $id]);
        
        // ✅ Step 2: Delete from Master DB
        DB::connection('master')
            ->table('users')
            ->where('id', $id)
            ->delete();
        
        Log::info('User deleted from master DB', ['user_id' => $id]);
        
        DB::commit();
        
        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('User deletion failed', [
            'user_id' => $id,
            'error' => $e->getMessage()
        ]);
        
        throw $e;
    }
}
```

#### UserController.php - List Users (View All)

```php
public function index(Request $request)
{
    $companyId = session('company_id');
    
    // ✅ Query from Master DB with company filter
    $query = DB::connection('master')
        ->table('users')
        ->where('company_id', $companyId);  // ✅ Company isolation

    // Filter by role if requested
    if ($request->has('role')) {
        $query->where('role', $request->role);
    }

    $users = $query->orderBy('name')->get();

    return response()->json($users);
}
```

## Data Consistency Considerations

### 1. Foreign Key di Tenant DB

**Problem:** FK dari customers.assigned_sales_id ke user_profiles.id

```sql
-- Tenant DB: crm_ecogreen
CREATE TABLE customers (
    id BIGINT PRIMARY KEY,
    company VARCHAR(255),
    assigned_sales_id BIGINT,
    FOREIGN KEY (assigned_sales_id) 
        REFERENCES user_profiles(id)  ← FK to user_profiles
);
```

**Solution:** Dual write memastikan user_profiles exists sebelum assignment

```php
// ✅ Safe: User exists in user_profiles
Customer::create([
    'company' => 'PT ABC',
    'assigned_sales_id' => 15  // ✅ Exists in user_profiles
]);
```

### 2. Delete Cascade Handling

**Problem:** Saat delete user, ada customers masih assigned

**Solution Option 1:** Soft Delete
```php
// Instead of hard delete, mark as inactive
DB::connection('master')
    ->table('users')
    ->where('id', $id)
    ->update(['is_active' => false, 'deleted_at' => now()]);

DB::connection('tenant')
    ->table('user_profiles')
    ->where('id', $id)
    ->update(['is_active' => false, 'deleted_at' => now()]);
```

**Solution Option 2:** Reassign Customers
```php
// Before delete, reassign customers to admin
DB::connection('tenant')
    ->table('customers')
    ->where('assigned_sales_id', $id)
    ->update(['assigned_sales_id' => $adminUserId]);
```

### 3. Rollback Scenario

```php
try {
    // Insert to master
    $userId = DB::connection('master')->table('users')->insertGetId([...]);
    
    // ❌ Tenant insert fails
    DB::connection('tenant')->table('user_profiles')->insert([...]);
    // Exception: Duplicate key or constraint violation
    
} catch (\Exception $e) {
    // ✅ Rollback removes record from both DBs
    DB::rollBack();
}
```

## Authentication Flow with Dual DB

### Login Process

```php
// AuthController::login()

// Step 1: Authenticate against Master DB
$user = DB::connection('master')
    ->table('users')
    ->where('email', $request->email)
    ->where('is_active', true)
    ->first();

if (!$user || !Hash::check($request->password, $user->password)) {
    return response()->json(['message' => 'Invalid credentials'], 401);
}

// Step 2: Get company info from Master DB
$company = DB::connection('master')
    ->table('companies')
    ->find($user->company_id);

// Step 3: Set session with tenant context
session([
    'user_id' => $user->id,
    'company_id' => $company->id,
    'tenant_db' => $company->database_name,
]);

// Step 4: Middleware sets default connection to tenant DB
// Now all queries (customers, interactions) go to tenant DB
```

## Migration untuk Existing Users

### Problem: User sudah ada di default 'users' table tapi tidak ada di user_profiles

### Solution: Data Sync Command

```php
// Artisan command: php artisan tenant:sync-user-profiles

use Illuminate\Support\Facades\DB;

// For Main Company
$mainUsers = DB::connection('master')
    ->table('users')
    ->where('company_id', 2)  // Main Company
    ->get();

foreach ($mainUsers as $user) {
    DB::connection('tenant')  // Switch to 'crm' database
        ->table('user_profiles')
        ->insertOrIgnore([  // ✅ Skip if exists
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
}

// For EcoGreen Company
$ecoUsers = DB::connection('master')
    ->table('users')
    ->where('company_id', 1)  // EcoGreen
    ->get();

// Switch to crm_ecogreen
config(['database.connections.tenant.database' => 'crm_ecogreen']);
DB::connection('tenant')->reconnect();

foreach ($ecoUsers as $user) {
    DB::connection('tenant')
        ->table('user_profiles')
        ->insertOrIgnore([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
}
```

## Summary & Recommendation

### ✅ YES - Implement Dual Write

**Why:**
1. ✅ Master DB untuk authentication & company isolation
2. ✅ Tenant DB untuk operational FK relationships
3. ✅ ID synchronization maintain referential integrity
4. ✅ Transaction ensures atomic writes (both or none)
5. ✅ Clear separation of concerns

### Implementation Checklist

- [ ] Add dual-write logic to UserController::store()
- [ ] Add dual-sync logic to UserController::update()
- [ ] Add dual-delete logic to UserController::destroy()
- [ ] Update UserController::index() to query master with company filter
- [ ] Add company_id validation in all methods
- [ ] Create Artisan command to sync existing users to user_profiles
- [ ] Test isolation: Main Company users vs EcoGreen users
- [ ] Test FK constraints: customers.assigned_sales_id
- [ ] Test rollback scenario: Transaction failure handling
- [ ] Add logging for dual-write operations

### Alternative: Sync via Eloquent Events (Advanced)

```php
// User.php Model
protected static function booted()
{
    // After user created in master
    static::created(function ($user) {
        // Auto-sync to tenant DB
        $tenantDb = $user->company->database_name;
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::connection('tenant')->reconnect();
        
        DB::connection('tenant')->table('user_profiles')->insert([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ]);
    });
    
    // Similar for updated, deleted events
}
```

This approach keeps UserController clean but adds complexity to model layer.
