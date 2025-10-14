# Database Seeding Guide

## Overview
This guide explains how to seed the NLA Payroll database with realistic test data.

## What Gets Seeded

### 1. Organizational Structure
- **7 Departments** with nested units
  - Administration (3 units)
  - Finance & Accounts (4 units)
  - Operations (4 units)
  - Information Technology (4 units)
  - Marketing & Communications (3 units)
  - Compliance & Risk (3 units)
  - Security Services (2 units)

### 2. Job Structure
- **7 Job Grades** with salary ranges (GHS 1,200 - 30,000)
  - Executive
  - Senior Management
  - Management
  - Senior Officer
  - Officer
  - Assistant Officer
  - Support Staff
- **37 Job Titles** mapped to grades

### 3. Geographic Structure
- **16 Regions** (All Ghana regions)
- **27 NLA Stations** across Ghana
  - Greater Accra (4 stations)
  - Ashanti (3 stations)
  - Other regions (1-2 stations each)

### 4. Banking
- **20 Major Ghanaian Banks**

### 5. Test Staff Data
- **50 Staff Members** with:
  - Realistic distribution across departments, units, and stations
  - 40% verified, 5% ghost employees
  - 1-2 bank accounts each (with encrypted account numbers)
  - Complete contact and employment information

## How to Seed

### Option 1: Fresh Migration with Seed
```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

### Option 2: Seed Only (if database already exists)
```bash
./vendor/bin/sail artisan db:seed
```

### Option 3: Seed Specific Seeder
```bash
./vendor/bin/sail artisan db:seed --class=DepartmentsAndUnitsSeeder
./vendor/bin/sail artisan db:seed --class=JobGradesAndTitlesSeeder
./vendor/bin/sail artisan db:seed --class=RegionsAndStationsSeeder
./vendor/bin/sail artisan db:seed --class=BanksSeeder
./vendor/bin/sail artisan db:seed --class=StaffSeeder
```

## Expected Output

When seeding completes, you'll see a summary table:

```
📊 Summary:
+----------------+-------+
| Entity         | Count |
+----------------+-------+
| Departments    | 7     |
| Units          | 23    |
| Job Grades     | 7     |
| Job Titles     | 37    |
| Regions        | 16    |
| Stations       | 27    |
| Banks          | 20    |
| Staff          | 50    |
| Bank Details   | ~60   |
+----------------+-------+
```

## Testing Queries

After seeding, test your data:

```bash
# Count staff by department
./vendor/bin/sail artisan tinker
>>> \App\Models\Department::withCount('staff')->get(['name', 'staff_count']);

# Find ghost employees
>>> \App\Models\Staff::where('is_ghost', true)->with('station')->get();

# Check verified staff
>>> \App\Models\Staff::where('is_verified', true)->count();

# Find staff with multiple bank accounts
>>> \App\Models\Staff::has('bankDetails', '>', 1)->with('bankDetails.bank')->get();

# Check staff by station
>>> \App\Models\Station::withCount('staff')->orderByDesc('staff_count')->get(['name', 'staff_count']);
```

## Notes

- Bank account numbers are **automatically encrypted** using Laravel's Crypt
- Staff numbers follow format: STF-00001 through STF-00050
- All data uses realistic Ghanaian context (regions, cities, banks, phone numbers)
- Each seeder can be run independently if needed
- The order matters: Run organizational/geographic data before staff data

## Customization

To change the number of staff created, edit `database/seeders/StaffSeeder.php`:

```php
// Line 34
$staffCount = 50; // Change this number
```

## Troubleshooting

**Issue**: "Please run DepartmentsAndUnitsSeeder first!"
**Solution**: Run seeders in order or use `db:seed` to run all

**Issue**: Foreign key constraint errors
**Solution**: Make sure migrations ran successfully first

**Issue**: Memory issues with large staff counts
**Solution**: Reduce `$staffCount` in StaffSeeder or process in chunks
