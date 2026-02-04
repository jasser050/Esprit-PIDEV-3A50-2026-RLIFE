# Planning Module Integration Guide

## ✅ Integration Complete!

The Planning module has been successfully integrated into the `rlife` database.

---

## 📊 Database Structure

### New Tables Added

#### 1. **seance** (Session/Event)
Represents a study session, event, or activity.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary key (auto-increment) |
| `user_id` | int(11) | Foreign key to `user` table (**NEW**) |
| `titre` | varchar(255) | Session title |
| `type_seance` | varchar(50) | Session type (Study, Exam, Meeting, etc.) |
| `description` | longtext | Session description |
| `partage_avec` | JSON | Array of user IDs to share with |
| `statut` | varchar(30) | Status (scheduled, completed, cancelled) |
| `created_at` | datetime | Creation timestamp |
| `updated_at` | datetime | Last update timestamp |

**Foreign Keys:**
- `user_id` → `user.id` (CASCADE DELETE)

#### 2. **planning** (Schedule Entry)
Links sessions to specific time slots in the calendar.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | Primary key (auto-increment) |
| `user_id` | int(11) | Foreign key to `user` table (**NEW**) |
| `seance_id` | int(11) | Foreign key to `seance` table |
| `date_debut` | datetime | Start date/time |
| `date_fin` | datetime | End date/time |
| `color` | varchar(30) | Calendar color (blue, red, green, etc.) |
| `created_at` | datetime | Creation timestamp |
| `updated_at` | datetime | Last update timestamp |

**Foreign Keys:**
- `user_id` → `user.id` (CASCADE DELETE)
- `seance_id` → `seance.id` (CASCADE DELETE)

---

## 🔗 Relationships

### Database Schema
```
user (id, email, ...)
  ↓ (1:N)
seance (id, user_id, titre, type_seance, ...)
  ↓ (1:N)
planning (id, user_id, seance_id, date_debut, date_fin, ...)
```

### Key Points
1. **Each seance belongs to one user** (creator)
2. **Each planning entry belongs to:**
   - One user (owner)
   - One seance (event details)
3. **When a user is deleted:**
   - All their seances are deleted (CASCADE)
   - All their planning entries are deleted (CASCADE)
4. **When a seance is deleted:**
   - All associated planning entries are deleted (CASCADE)

---

## 📁 Files Created

### Entities
- ✅ `src/Entity/Seance.php` - Seance entity with User relationship
- ✅ `src/Entity/Planning.php` - Planning entity with User and Seance relationships

### Repositories
- ✅ `src/Repository/SeanceRepository.php` - Custom queries for seances
- ✅ `src/Repository/PlanningRepository.php` - Custom queries for planning

### SQL Scripts
- ✅ `planning_integration.sql` - SQL script for manual import

---

## 🎯 What Changed from Original Planning Database

### ✨ Improvements Made

1. **Added `user_id` to both tables**
   - Original: No user association
   - Now: Every seance and planning is linked to a user
   - Benefit: Multi-user support, security, personalization

2. **Added timestamps (`created_at`, `updated_at`)**
   - Original: No tracking of when records were created/modified
   - Now: Full audit trail
   - Benefit: Can show "recently created" sessions, track modifications

3. **Added CASCADE DELETE**
   - Original: No automatic cleanup
   - Now: When user deletes account, all their planning data is removed
   - Benefit: Data integrity, no orphaned records

4. **Improved foreign key naming**
   - Original: Generic constraint names
   - Now: Descriptive names (`FK_seance_user`, `FK_planning_seance`)
   - Benefit: Easier to understand database structure

5. **Created Symfony entities**
   - Original: Only SQL tables
   - Now: Full ORM support with PHP classes
   - Benefit: Type safety, IDE autocomplete, easier to work with

---

## 💻 How to Use in Code

### Create a New Seance
```php
use App\Entity\Seance;

$seance = new Seance();
$seance->setUser($currentUser);
$seance->setTitre('Math Study Session');
$seance->setTypeSeance('Study');
$seance->setDescription('Review calculus chapter 5');
$seance->setStatut('scheduled');

$entityManager->persist($seance);
$entityManager->flush();
```

### Create a Planning Entry
```php
use App\Entity\Planning;

$planning = new Planning();
$planning->setUser($currentUser);
$planning->setSeance($seance);
$planning->setDateDebut(new \DateTime('2026-02-10 14:00:00'));
$planning->setDateFin(new \DateTime('2026-02-10 16:00:00'));
$planning->setColor('blue');

$entityManager->persist($planning);
$entityManager->flush();
```

### Query User's Planning
```php
// In a controller
$planningRepository = $entityManager->getRepository(Planning::class);

// Get all planning for current user
$userPlannings = $planningRepository->findByUser($currentUser->getId());

// Get upcoming planning (next 10)
$upcomingPlannings = $planningRepository->findUpcoming($currentUser->getId(), 10);

// Get planning for specific date range
$start = new \DateTime('2026-02-01');
$end = new \DateTime('2026-02-28');
$monthPlannings = $planningRepository->findByDateRange($start, $end, $currentUser->getId());
```

### Query User's Seances
```php
$seanceRepository = $entityManager->getRepository(Seance::class);

// Get all seances for user
$userSeances = $seanceRepository->findByUser($currentUser->getId());

// Get seances by status
$scheduledSeances = $seanceRepository->findByStatut('scheduled');
```

---

## 🔍 Verify Integration

### Check Tables Exist
```bash
php bin/console dbal:run-sql "SHOW TABLES"
```

Expected output:
- `doctrine_migration_versions`
- `planning` ✅
- `seance` ✅
- `user`
- `user_settings`

### Check Foreign Keys
```bash
php bin/console dbal:run-sql "SELECT TABLE_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'rlife' AND TABLE_NAME IN ('seance', 'planning')"
```

### Test Query
```bash
php bin/console dbal:run-sql "SELECT p.id, p.date_debut, p.date_fin, s.titre, u.email FROM planning p JOIN seance s ON p.seance_id = s.id JOIN user u ON p.user_id = u.id"
```

---

## 📝 Sample Data

### Current Test Data
- ✅ 1 Seance: "Math Study Session" (Study type, user: admin@rlife.com)
- ✅ 1 Planning: Feb 10, 2026, 14:00-16:00 (blue color)

### Add More Test Data
```sql
-- Add another seance
INSERT INTO seance (user_id, titre, type_seance, description, statut) 
VALUES (3, 'Physics Lab', 'Lab', 'Experiment on motion', 'scheduled');

-- Add planning for it
INSERT INTO planning (user_id, seance_id, date_debut, date_fin, color) 
VALUES (3, 2, '2026-02-11 10:00:00', '2026-02-11 12:00:00', 'green');
```

---

## 🚀 Next Steps for Planning Module

### 1. **Create Planning Controller**
```bash
php bin/console make:controller PlanningController
```

### 2. **Routes to Implement**
- `GET /planning` - Calendar view
- `GET /planning/seances` - List all seances
- `POST /planning/seance/new` - Create new seance
- `POST /planning/schedule` - Add to calendar
- `GET /planning/upcoming` - Upcoming events
- `PUT /planning/{id}` - Edit planning
- `DELETE /planning/{id}` - Delete planning

### 3. **Views to Create**
- Calendar view (monthly/weekly)
- Seance list/grid
- Create/Edit seance form
- Planning detail view

### 4. **Features to Add**
- Drag & drop calendar
- Color-coded by type
- Recurring events
- Reminders/notifications
- Share with other users
- Export to iCal/Google Calendar

---

## 👥 Team Integration Instructions

For other team members to get the planning module:

### Option 1: Run SQL Script
```bash
php bin/console dbal:run-sql < planning_integration.sql
```

### Option 2: Copy Files
1. Copy entity files:
   - `src/Entity/Seance.php`
   - `src/Entity/Planning.php`
   - `src/Repository/SeanceRepository.php`
   - `src/Repository/PlanningRepository.php`

2. Update database:
```bash
php bin/console doctrine:schema:update --force
```

3. Clear cache:
```bash
php bin/console cache:clear
```

---

## 📊 Database Statistics

```sql
-- Count seances per user
SELECT u.email, COUNT(s.id) as seance_count 
FROM user u 
LEFT JOIN seance s ON u.id = s.user_id 
GROUP BY u.id;

-- Count planning entries per user
SELECT u.email, COUNT(p.id) as planning_count 
FROM user u 
LEFT JOIN planning p ON u.id = p.user_id 
GROUP BY u.id;

-- Upcoming events
SELECT u.email, s.titre, p.date_debut, p.date_fin 
FROM planning p 
JOIN seance s ON p.seance_id = s.id 
JOIN user u ON p.user_id = u.id 
WHERE p.date_debut >= NOW() 
ORDER BY p.date_debut ASC 
LIMIT 10;
```

---

## ✅ Summary

### What Was Done
1. ✅ **Analyzed** planning database structure
2. ✅ **Enhanced** tables with `user_id` foreign keys
3. ✅ **Added** timestamps for audit trail
4. ✅ **Created** Symfony entities (Seance, Planning)
5. ✅ **Created** repositories with custom queries
6. ✅ **Imported** tables into `rlife` database
7. ✅ **Tested** integration with sample data
8. ✅ **Verified** foreign key relationships work

### Database Tables
- `user` (yours) ✅
- `user_settings` (yours) ✅
- `user_career` (yours) ✅
- `seance` (planning module) ✅
- `planning` (planning module) ✅

### Ready For
- ✅ Multi-user planning
- ✅ User-specific calendars
- ✅ Cascade deletion (data integrity)
- ✅ Symfony ORM integration
- ✅ REST API development
- ✅ Frontend integration

---

## 🎉 Success!

The planning module is now fully integrated with your user management system. Each user can have their own seances and planning entries, and everything is properly linked in the database!

**Next**: Integrate other modules (Courses, Assignments, Flashcards, Projects, etc.) following the same pattern! 🚀
