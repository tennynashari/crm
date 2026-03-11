# Analisa User Management dalam Multi-Tenant Architecture

## Current Flow (SEBELUM FIX)

### 1. User EcoGreen Create User Baru
```
User: andhia@ecogreen.id login
  ↓
Frontend: POST /api/users
  Body: { name, email, password, role, is_active }
  ↓
Backend: SetTenantFromSession middleware
  session('tenant_db') = "crm_ecogreen"
  session('company_id') = 1
  database.default = "tenant" → crm_ecogreen
  ↓
UserController::store()
  ❌ HANYA insert: name, email, password, role, is_active
  ❌ TIDAK set company_id atau database_name
  ❌ Insert ke database mana? → Depends on connection
```

## Masalah yang Ditemukan

### ❌ Problem 1: User Model Tidak Multi-Tenant Aware
**File:** `backend/app/Models/User.php`
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'is_active',
    // ❌ MISSING: 'company_id', 'database_name'
];
```

### ❌ Problem 2: UserController Tidak Set Company Context
**File:** `backend/app/Http/Controllers/Api/UserController.php`
```php
public function store(Request $request)
{
    $validated = $request->validate([...]); 
    
    // ❌ TIDAK mengambil session('company_id')
    // ❌ TIDAK mengambil session('tenant_db')
    
    $user = User::create($validated);
    
    return response()->json($user, 201);
}
```

### ❌ Problem 3: Tidak Ada Sync ke Tenant DB
User baru hanya dibuat di satu tempat, tidak ada mekanisme sync ke:
- Master DB (`crm_master.users`) → Registry semua user
- Tenant DB (`crm_ecogreen.user_profile`) → User profile di tenant DB

### ❌ Problem 4: User Index Tidak Filter by Company
**File:** `backend/app/Http/Controllers/Api/UserController.php`
```php
public function index(Request $request)
{
    $query = User::query();
    
    // ❌ TIDAK filter by company_id atau database_name
    // Hasil: User EcoGreen bisa lihat semua user dari semua company
    
    $users = $query->orderBy('name')->get();
    return response()->json($users);
}
```

## Skenario Masalah

### Scenario 1: User Baru Tidak Punya Company Context
```
1. andhia@ecogreen.id create user: "new_sales@ecogreen.id"
2. User baru tersimpan tanpa company_id dan database_name
3. new_sales@ecogreen.id login
4. AuthController tidak tahu user ini milik company mana
5. ❌ ERROR atau random database assignment
```

### Scenario 2: Data Leakage Antar Tenant
```
1. admin@flowcrm.test buka /sales page
2. Frontend call: GET /api/users?role=sales
3. Backend return SEMUA sales user dari semua company
4. ❌ admin@flowcrm.test bisa lihat sales EcoGreen
5. ❌ Bisa edit/delete user company lain
```

## Recommended Solution

### Option 1: Master + Tenant DB Dual Write (RECOMMENDED)

#### Architecture:
```
Master DB (crm_master):
├── companies (id, name, database_name, is_active)
└── users (id, name, email, password, role, company_id, database_name, is_active)
    └── company_id → FK to companies.id
    └── database_name → Which tenant DB this user belongs to

Tenant DB (crm / crm_ecogreen):
└── user_profile (id, name, email, role, is_active)
    └── Mirror of master users for current tenant only
```

#### Implementation Steps:

**Step 1: Add Columns to Master Users Table**
```bash
php artisan make:migration add_company_columns_to_users_table
```

```php
// Migration
Schema::connection('master')->table('users', function (Blueprint $table) {
    $table->unsignedBigInteger('company_id')->nullable()->after('role');
    $table->string('database_name')->nullable()->after('company_id');
    
    $table->foreign('company_id')
          ->references('id')
          ->on('companies')
          ->onDelete('cascade');
    
    $table->index('company_id');
    $table->index('database_name');
});
```

**Step 2: Update User Model**
```php
// app/Models/User.php
protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'is_active',
    'company_id',      // ✅ ADD
    'database_name',   // ✅ ADD
];

// ✅ ADD: Relationship
public function company()
{
    return $this->belongsTo(Company::class);
}

// ✅ ADD: Scope for filtering by company
public function scopeInCompany($query, $companyId)
{
    return $query->where('company_id', $companyId);
}
```

**Step 3: Update UserController::store()**
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8',
        'role' => ['required', Rule::in(['admin', 'sales'])],
        'is_active' => 'boolean',
    ]);

    // ✅ Auto-fill company context from session
    $validated['company_id'] = session('company_id');
    $validated['database_name'] = session('tenant_db');
    $validated['password'] = Hash::make($validated['password']);

    DB::beginTransaction();
    try {
        // ✅ 1. Create in Master DB
        DB::connection('master')->reconnect(); // Ensure master connection
        config(['database.default' => 'master']);
        $user = User::create($validated);
        
        // ✅ 2. Create in Tenant DB (user_profile)
        config(['database.default' => 'tenant']);
        DB::table('user_profile')->insert([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        DB::commit();
        
        // ✅ Restore tenant connection
        config(['database.default' => 'tenant']);
        
        return response()->json($user, 201);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('User creation failed', [
            'error' => $e->getMessage(),
            'company_id' => session('company_id'),
            'database' => session('tenant_db')
        ]);
        
        return response()->json([
            'message' => 'Failed to create user',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

**Step 4: Update UserController::index() - Filter by Company**
```php
public function index(Request $request)
{
    // ✅ Switch to master DB to query users table
    config(['database.default' => 'master']);
    
    $companyId = session('company_id');
    
    $query = User::where('company_id', $companyId); // ✅ Filter by company

    // Filter by role
    if ($request->has('role')) {
        $query->where('role', $request->role);
    }

    $users = $query->orderBy('name')->get();
    
    // ✅ Restore tenant connection
    config(['database.default' => 'tenant']);

    return response()->json($users);
}
```

**Step 5: Update UserController::update() - Add Sync**
```php
public function update(Request $request, $id)
{
    // ✅ Ensure user belongs to current company
    config(['database.default' => 'master']);
    $user = User::where('company_id', session('company_id'))
                ->findOrFail($id);
    
    $validated = $request->validate([...]);

    if (!empty($validated['password'])) {
        $validated['password'] = Hash::make($validated['password']);
    } else {
        unset($validated['password']);
    }

    DB::beginTransaction();
    try {
        // ✅ 1. Update in Master DB
        $user->update($validated);
        
        // ✅ 2. Sync to Tenant DB
        config(['database.default' => 'tenant']);
        DB::table('user_profile')
            ->where('id', $user->id)
            ->update([
                'name' => $validated['name'] ?? $user->name,
                'email' => $validated['email'] ?? $user->email,
                'role' => $validated['role'] ?? $user->role,
                'is_active' => $validated['is_active'] ?? $user->is_active,
                'updated_at' => now(),
            ]);
        
        DB::commit();
        
        config(['database.default' => 'tenant']);
        
        return response()->json($user);
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

**Step 6: Update UserController::destroy() - Add Company Check**
```php
public function destroy($id)
{
    config(['database.default' => 'master']);
    
    // ✅ Ensure user belongs to current company
    $user = User::where('company_id', session('company_id'))
                ->findOrFail($id);
    
    // Prevent deleting yourself
    if ($user->id === auth()->id()) {
        config(['database.default' => 'tenant']);
        return response()->json([
            'message' => 'You cannot delete your own account',
        ], 403);
    }

    DB::beginTransaction();
    try {
        // ✅ 1. Delete from Master DB
        $user->delete();
        
        // ✅ 2. Delete from Tenant DB
        config(['database.default' => 'tenant']);
        DB::table('user_profile')->where('id', $id)->delete();
        
        DB::commit();
        
        config(['database.default' => 'tenant']);
        
        return response()->json([
            'message' => 'User deleted successfully',
        ], 200);
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

## Data Migration for Existing Users

```php
// Artisan command: php artisan tenant:migrate-users

use Illuminate\Support\Facades\DB;

// Assign existing users to Main Company
DB::connection('master')->table('users')
    ->whereNull('company_id')
    ->update([
        'company_id' => 2, // Main Company ID
        'database_name' => 'crm'
    ]);

// Verify EcoGreen user has correct company
DB::connection('master')->table('users')
    ->where('email', 'andhia@ecogreen.id')
    ->update([
        'company_id' => 1, // EcoGreen Company ID
        'database_name' => 'crm_ecogreen'
    ]);
```

## Testing Checklist

### ✅ Test 1: Create User Isolation
```
1. Login as: admin@flowcrm.test (Main Company)
2. Create user: "sales1@flowcrm.test"
3. Verify:
   - Master DB: company_id = 2, database_name = "crm"
   - Tenant DB: crm.user_profile contains sales1@flowcrm.test

4. Login as: andhia@ecogreen.id (EcoGreen)
5. Create user: "sales1@ecogreen.id"
6. Verify:
   - Master DB: company_id = 1, database_name = "crm_ecogreen"
   - Tenant DB: crm_ecogreen.user_profile contains sales1@ecogreen.id
```

### ✅ Test 2: User List Isolation
```
1. Login as: admin@flowcrm.test
2. GET /api/users
3. Should ONLY see users with company_id = 2 (Main Company)

4. Login as: andhia@ecogreen.id
5. GET /api/users
6. Should ONLY see users with company_id = 1 (EcoGreen)
```

### ✅ Test 3: Cannot Edit Cross-Tenant Users
```
1. Login as: admin@flowcrm.test
2. Try to edit user ID dari EcoGreen
3. Should return: 404 Not Found (karena filtered by company_id)
```

### ✅ Test 4: User Login Uses Correct Tenant DB
```
1. Login as: sales1@ecogreen.id
2. Check session:
   - company_id = 1
   - tenant_db = "crm_ecogreen"
3. View customers:
   - Should only see 12 customers (EcoGreen data)
```

## Summary

### Current Status: ❌ NOT MULTI-TENANT SAFE

**Issues:**
1. ❌ User tidak punya company_id dan database_name
2. ❌ User baru tidak otomatis ter-assign ke company pembuat
3. ❌ User list tidak filter by company (data leakage)
4. ❌ User edit/delete tidak check company ownership

### After Implementation: ✅ FULLY ISOLATED

**Benefits:**
1. ✅ Setiap user punya company_id yang jelas
2. ✅ User baru otomatis masuk ke company pembuat
3. ✅ User list hanya menampilkan user dari company sendiri
4. ✅ Tidak bisa edit/delete user company lain
5. ✅ Data tersinkron di Master DB dan Tenant DB
