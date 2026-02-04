# StudyFlow - Setup Guide & Implementation Summary

## Project Overview
**StudyFlow** is a comprehensive student productivity platform built with Symfony 7.4 that helps students manage their courses, assignments, projects, and wellbeing.

---

## What Was Implemented

### 1. Database Setup
✅ **Database Created**: `studyflow` database in MySQL (XAMPP)
✅ **Connection Configured**: `.env` file updated with MySQL credentials

### 2. Entities Created

#### **User Entity** (`src/Entity/User.php`)
Complete user management system with all requested attributes:
- `id` - Auto-incremented primary key
- `email` - Unique email address (used for login)
- `password` - Hashed password (bcrypt)
- `firstName` - User's first name
- `lastName` - User's last name
- `username` - Unique username
- `profilePic` - Path to profile picture (optional)
- `phoneNumber` - Contact number (optional)
- `gender` - Male or Female
- `bio` - User biography (optional)
- `studentId` - Student ID number (optional)
- `university` - University name (optional)
- `isBanned` - Ban status (boolean)
- `bannedAt` - Timestamp when banned
- `banReason` - Reason for ban
- `createdAt` - Registration timestamp
- `updatedAt` - Last update timestamp
- `roles` - User roles array (ROLE_USER, ROLE_ADMIN)

**Relationships:**
- OneToOne with `UserSettings`
- OneToMany with `UserCareer` (métiers du user)

#### **UserSettings Entity** (`src/Entity/UserSettings.php`)
User preferences and settings:
- `studyLevel` - Education level
- `weeklyGoal` - Study hours goal
- `interests` - Array of interests
- `notificationEnabled` - Push notifications toggle
- `emailNotifications` - Email notifications toggle
- `themePreference` - Light/Dark mode
- `language` - Interface language

#### **UserCareer Entity** (`src/Entity/UserCareer.php`)
Career aspirations ("métiers du user"):
- `careerName` - Name of career path
- `description` - Career description
- `priority` - Priority level
- `isPrimary` - Primary career flag

### 3. Authentication System

✅ **Symfony Security Bundle** installed and configured
✅ **Password Hashing** with bcrypt algorithm
✅ **Form Login** authentication
✅ **Remember Me** functionality
✅ **Logout** functionality
✅ **Role-Based Access Control**:
- `ROLE_USER` - Regular users (access to dashboard and student features)
- `ROLE_ADMIN` - Administrators (access to admin panel)

**Security Configuration:**
- Email-based login
- CSRF protection enabled
- Protected routes for authenticated users
- Separate admin routes requiring ROLE_ADMIN

### 4. Admin Panel (Backoffice)

✅ **Complete Admin Dashboard** (`/admin`)
- Modern, responsive design matching the existing UI
- Statistics overview (total users, active, banned, admins)
- Recent users table
- Sidebar navigation

✅ **User Management** (`/admin/users`)
- View all users with filtering (All, Active, Banned, Admins)
- User details display (ID, name, email, university, role, status)
- Actions available:
  - **Ban User** - Ban with custom reason
  - **Unban User** - Restore access
  - **Make Admin** - Grant admin privileges
  - **Remove Admin** - Revoke admin role

✅ **Statistics & Reports** (`/admin/statistics`)
- User growth chart (last 7 days)
- Gender distribution pie chart
- Top 5 universities table with percentages
- Interactive Chart.js visualizations

**Admin Routes:**
- `/admin` - Dashboard
- `/admin/users` - User management
- `/admin/statistics` - Analytics

### 5. Controllers Created

#### **PublicController** (`src/Controller/PublicController.php`)
- **GET `/`** - Landing page (redirects if logged in)
- **GET `/login`** - Login page
- **POST `/login`** - Handle authentication
- **GET `/logout`** - Logout
- **GET|POST `/register`** - User registration
- **GET `/welcome`** - Welcome page

#### **AdminController** (`src/Controller/AdminController.php`)
- **GET `/admin`** - Admin dashboard
- **GET `/admin/users`** - User management
- **POST `/admin/users/{id}/ban`** - Ban user
- **POST `/admin/users/{id}/unban`** - Unban user
- **POST `/admin/users/{id}/make-admin`** - Grant admin role
- **POST `/admin/users/{id}/remove-admin`** - Remove admin role
- **GET `/admin/statistics`** - View statistics

---

## Admin Access Credentials

An admin user has been created for you:

```
Email: admin@studyflow.com
Password: admin123
```

**To login as admin:**
1. Start XAMPP (Apache + MySQL)
2. Go to `http://localhost/login`
3. Enter the credentials above
4. You'll be redirected to `/admin` (admin dashboard)

**To login as regular user:**
- Register a new account at `http://localhost/register`
- You'll be redirected to `/dashboard` (user dashboard)

---

## Database Tables Created

### `user` Table
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- email (VARCHAR 180, UNIQUE)
- roles (JSON)
- password (VARCHAR 255)
- first_name (VARCHAR 100)
- last_name (VARCHAR 100)
- username (VARCHAR 50, UNIQUE)
- profile_pic (VARCHAR 255, NULL)
- phone_number (VARCHAR 20, NULL)
- gender (VARCHAR 10)
- bio (TEXT, NULL)
- student_id (VARCHAR 100, NULL)
- university (VARCHAR 255, NULL)
- is_banned (BOOLEAN, DEFAULT 0)
- banned_at (DATETIME, NULL)
- ban_reason (TEXT, NULL)
- created_at (DATETIME)
- updated_at (DATETIME)
```

### `user_settings` Table
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- user_id (INT, FOREIGN KEY)
- study_level (VARCHAR 100, NULL)
- weekly_goal (INT, NULL)
- interests (JSON, NULL)
- notification_enabled (BOOLEAN, DEFAULT 1)
- email_notifications (BOOLEAN, DEFAULT 1)
- theme_preference (VARCHAR 20, DEFAULT 'light')
- language (VARCHAR 10, DEFAULT 'en')
```

### `user_career` Table
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- user_id (INT, FOREIGN KEY)
- career_name (VARCHAR 255)
- description (TEXT, NULL)
- priority (INT, NULL)
- is_primary (BOOLEAN, DEFAULT 0)
```

---

## File Structure

```
studyflow/
├── config/
│   └── packages/
│       └── security.yaml          # Security configuration
├── migrations/
│   └── Version20260203000742.php  # Database migration
├── src/
│   ├── Command/
│   │   └── CreateAdminCommand.php # CLI command to create admin
│   ├── Controller/
│   │   ├── AdminController.php    # Admin panel controller
│   │   └── PublicController.php   # Login/Register controller
│   ├── Entity/
│   │   ├── User.php              # User entity
│   │   ├── UserSettings.php      # Settings entity
│   │   └── UserCareer.php        # Career entity
│   └── Repository/
│       ├── UserRepository.php
│       ├── UserSettingsRepository.php
│       └── UserCareerRepository.php
├── templates/
│   ├── admin/
│   │   ├── base_admin.html.twig  # Admin layout
│   │   ├── dashboard.html.twig   # Admin dashboard
│   │   ├── users.html.twig       # User management
│   │   └── statistics.html.twig  # Analytics
│   └── pages/
│       └── auth/
│           ├── login.html.twig   # Login page
│           └── register.html.twig # Registration page
├── .env                          # Environment configuration
├── insert_admin.sql              # SQL to create admin user
└── SETUP_GUIDE.md               # This file
```

---

## How to Use

### Starting the Application

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

2. **Access the Application**
   - User Interface: `http://localhost/`
   - Login: `http://localhost/login`
   - Register: `http://localhost/register`
   - Admin Panel: `http://localhost/admin` (requires admin login)

### Creating Additional Admin Users

**Method 1: Using Symfony Console Command**
```bash
php bin/console app:create-admin
```
Follow the interactive prompts to enter:
- Email
- Password
- First Name
- Last Name
- Username

**Method 2: Using SQL**
```bash
# Replace values as needed
php bin/console dbal:run-sql "INSERT INTO user (email, roles, password, first_name, last_name, username, gender, is_banned, created_at, updated_at) VALUES ('newemail@example.com', '[\"ROLE_USER\",\"ROLE_ADMIN\"]', '\$2y\$10\$lkPyHGrC9jWMQfPsb.PiDeZ9e6EpfVgCP9dVrPAvx0SsnhdJRZSau', 'Admin', 'User', 'admin2', 'male', 0, NOW(), NOW())"
```

### Admin Panel Features

#### 1. Dashboard (`/admin`)
- Overview of user statistics
- Quick metrics (total, active, banned, admins)
- Recent users list
- Visual cards with icons

#### 2. User Management (`/admin/users`)
- **Filter users** by status:
  - All Users
  - Active Users
  - Banned Users
  - Admins Only
  
- **Actions per user:**
  - **Ban**: Opens modal to enter ban reason
  - **Unban**: Instantly removes ban
  - **Make Admin**: Grants admin privileges
  - **Remove Admin**: Revokes admin role

- **User Information Displayed:**
  - User ID
  - Full name with avatar
  - Username
  - Email
  - University
  - Role badge
  - Status badge (Active/Banned)
  - Join date

#### 3. Statistics (`/admin/statistics`)
- **User Growth Chart**: Line chart showing new registrations over the last 7 days
- **Gender Distribution**: Pie chart showing male/female ratio
- **Top Universities**: Table with top 5 universities by student count
  - Shows count and percentage
  - Visual progress bars

---

## User Registration Flow

1. User visits `/register`
2. Fills in multi-step wizard:
   - **Step 1**: Basic Info (name, email, password, gender)
   - **Step 2**: Study preferences (level, goals, interests)
3. Account created with:
   - Hashed password
   - `ROLE_USER` role
   - Default `UserSettings` created
4. Redirected to `/login`
5. After login, redirected to `/dashboard`

---

## Security Features

✅ **Password Hashing**: Bcrypt with automatic cost factor
✅ **CSRF Protection**: Enabled on all forms
✅ **SQL Injection Prevention**: Doctrine ORM parameterized queries
✅ **XSS Prevention**: Twig auto-escaping
✅ **Role-Based Access**:
- `/admin/*` routes require `ROLE_ADMIN`
- `/dashboard`, `/courses`, etc. require `ROLE_USER`
- Public routes: `/`, `/login`, `/register`

✅ **Ban System**:
- Banned users cannot login
- Ban reason stored and displayed
- Ban timestamp recorded

---

## Database Connection Details

**File:** `.env`
```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/studyflow?serverVersion=8.0.32&charset=utf8mb4"
```

**Credentials:**
- Host: `127.0.0.1`
- Port: `3306`
- Database: `studyflow`
- User: `root`
- Password: _(empty)_

---

## Next Steps & Recommendations

### For Your Team (6 People)

Since you're part of a 6-person team and this is your portion (User Management), here's how to integrate with others:

1. **Share the User entity** with teammates who need authentication
2. **Provide access** to the User repository for relationship mapping
3. **Document your API** if others need to query user data

### Additional Features to Consider

1. **Email Verification**
   - Send confirmation email on registration
   - Verify email before allowing login

2. **Password Reset**
   - Forgot password functionality
   - Token-based reset system

3. **Profile Management**
   - Allow users to upload profile pictures
   - Edit bio, university, phone number
   - Change password

4. **Advanced Admin Features**
   - Bulk user actions
   - Export user data (CSV/Excel)
   - User activity logs
   - Email users from admin panel

5. **User Career Management**
   - UI to add/edit/delete careers
   - Display on user profile
   - Filter users by career interests

---

## Troubleshooting

### Cannot Login
- **Check:** XAMPP MySQL is running
- **Check:** Database `studyflow` exists
- **Check:** User exists in database: `SELECT * FROM user;`
- **Check:** Password is correct (admin123 for admin)

### Admin Panel Not Accessible
- **Check:** User has `ROLE_ADMIN` in roles column
- **Check:** Logged in user email matches admin user
- **Run:** `SELECT email, roles FROM user WHERE email = 'admin@studyflow.com';`

### Database Connection Error
- **Check:** XAMPP MySQL is running on port 3306
- **Check:** `.env` file has correct credentials
- **Run:** `php bin/console doctrine:database:create` (if database missing)

### Migration Issues
- **Clear cache:** `php bin/console cache:clear`
- **Run migrations:** `php bin/console doctrine:migrations:migrate`

---

## Commands Reference

```bash
# Clear Symfony cache
php bin/console cache:clear

# Create database (if needed)
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Create migration after entity changes
php bin/console make:migration

# Create admin user
php bin/console app:create-admin

# Run SQL command
php bin/console dbal:run-sql "YOUR SQL QUERY"

# List all routes
php bin/console debug:router

# Check security configuration
php bin/console debug:firewall
```

---

## Summary

✅ **Database**: Created `studyflow` with 3 tables (user, user_settings, user_career)
✅ **Entities**: User, UserSettings, UserCareer with all requested attributes
✅ **Authentication**: Complete login/logout/register system
✅ **Admin Panel**: Full backoffice with user management, statistics, and ban system
✅ **Security**: Role-based access control, password hashing, CSRF protection
✅ **Admin User**: Created with email `admin@studyflow.com` and password `admin123`

**All requirements have been successfully implemented!**

---

## Contact & Support

If you encounter any issues or need modifications:
1. Check this guide first
2. Review error logs in `var/log/`
3. Clear cache: `php bin/console cache:clear`
4. Check XAMPP error logs

**Happy coding!** 🚀
