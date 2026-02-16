# RLIFE - Technical Documentation
## Student Planning Application

### Project Overview
RLIFE is a comprehensive student planning application built with Symfony 6.4, featuring user management, course management, evaluation tracking, and administrative tools.

---

## 🔐 User Authentication & Management System

### 1. User Registration System
**Location:** `src/Controller/RegistrationController.php`
- **Endpoint:** `POST /register`
- **Features:**
  - Email validation and uniqueness check
  - Password hashing using Symfony's password hasher
  - Email verification system
  - Automatic role assignment (`ROLE_USER`)
  - CSRF protection enabled
- **Form:** `src/Form/RegistrationFormType.php`
- **Entity:** `src/Entity/User.php`
- **Template:** `templates/registration/register.html.twig`

### 2. User Login System
**Location:** `src/Security/LoginFormAuthenticator.php`
- **Endpoint:** `POST /login`
- **Features:**
  - Email-based authentication
  - Remember me functionality
  - Account status verification (active/banned)
  - Automatic redirection based on user role
  - Failed login attempt tracking
- **Form:** Built-in Symfony security forms
- **Template:** `templates/security/login.html.twig`

### 3. User Profile Management
**Location:** `src/Controller/ProfileController.php`
- **Endpoints:** 
  - `GET /profile` - View profile
  - `POST /profile/edit` - Update profile
- **Features:**
  - Profile information updates
  - Password change functionality
  - Avatar upload system
  - Account preferences management
- **Template:** `templates/pages/settings/index.html.twig`

### 4. User CRUD Operations
**Standard User Operations:**
- **Create:** Registration process
- **Read:** Profile viewing, user dashboard
- **Update:** Profile editing, password changes
- **Delete:** Account deactivation (soft delete)

---

## 👑 Administrative System

### 1. Admin Dashboard
**Location:** `src/Controller/AdminController.php`
- **Endpoint:** `GET /admin`
- **Features:**
  - System statistics overview
  - User activity monitoring
  - Quick action buttons
  - System health indicators
- **Template:** `templates/admin/dashboard.html.twig`

### 2. User Management (Ban/Unban System)
**Location:** `src/Controller/Admin/UserController.php`
- **Endpoints:**
  - `GET /admin/users` - List all users
  - `POST /admin/users/{id}/ban` - Ban user
  - `POST /admin/users/{id}/unban` - Unban user
  - `POST /admin/users/{id}/delete` - Delete user account
- **Features:**
  - User status management (active/banned/deleted)
  - Ban reason logging
  - Bulk user operations
  - User activity history
  - Search and filtering capabilities

### 3. Role Management (Upgrade to Admin)
**Location:** `src/Controller/Admin/UserController.php`
- **Endpoint:** `POST /admin/users/{id}/promote`
- **Features:**
  - Role elevation (USER → ADMIN)
  - Role demotion (ADMIN → USER)
  - Permission verification
  - Role change logging
- **Roles Available:**
  - `ROLE_USER` - Standard user
  - `ROLE_ADMIN` - Administrator
  - `ROLE_SUPER_ADMIN` - Super administrator

### 4. Mailing System
**Location:** `src/Service/MailingService.php`
- **Features:**
  - Welcome emails for new users
  - Password reset emails
  - Account status notifications
  - System announcements
  - Newsletter functionality
- **Configuration:** `config/packages/mailer.yaml`
- **Templates:** `templates/emails/`

### 5. Automatic Mailing System
**Location:** `src/Command/SendAutomaticEmailsCommand.php`
- **Command:** `php bin/console app:send-automatic-emails`
- **Features:**
  - Scheduled email campaigns
  - User engagement emails
  - System maintenance notifications
  - Birthday/anniversary emails
  - Inactive user re-engagement
- **Scheduling:** Configured via cron jobs

### 6. Audit Log System
**Location:** `src/Service/AuditLogService.php`
- **Entity:** `src/Entity/AuditLog.php`
- **Controller:** `src/Controller/Admin/AdminAuditController.php`
- **Endpoints:**
  - `GET /admin/audit` - View audit logs
  - `GET /admin/audit/export` - Export logs
- **Features:**
  - Complete action logging
  - User activity tracking
  - Admin action monitoring
  - IP address logging
  - Timestamp recording
  - Filterable by date, user, action type
- **Tracked Actions:**
  - User login/logout
  - Profile changes
  - Admin actions (ban/unban/promote)
  - Course creation/editing
  - Evaluation management
  - System configuration changes

### 7. Statistics & Analytics
**Location:** `src/Controller/Admin/StatisticsController.php`
- **Endpoint:** `GET /admin/statistics`
- **Features:**
  - User registration trends
  - Login activity analytics
  - Course enrollment statistics
  - Evaluation completion rates
  - System usage metrics
  - Performance indicators
- **Charts:** Chart.js integration for visual analytics
- **Template:** `templates/admin/statistics.html.twig`

---

## 🗄️ Database Schema

### User Entity
```php
class User implements UserInterface
{
    private int $id;
    private string $email;
    private array $roles = [];
    private string $password;
    private string $firstName;
    private string $lastName;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private bool $isActive;
    private bool $isBanned;
    private string $banReason;
    private DateTime $bannedAt;
    // Additional fields...
}
```

### Audit Log Entity
```php
class AuditLog
{
    private int $id;
    private User $user;
    private string $action;
    private string $entityType;
    private int $entityId;
    private array $oldValues;
    private array $newValues;
    private string $ipAddress;
    private DateTime $timestamp;
    // Additional fields...
}
```

---

## 🛡️ Security Features

### 1. Authentication Security
- Password hashing with Argon2i
- CSRF protection on all forms
- Session security configuration
- Remember me token security
- Failed login attempt limiting

### 2. Authorization System
- Role-based access control (RBAC)
- Route-level security
- Method-level security annotations
- Dynamic permission checking

### 3. Data Protection
- Input sanitization
- SQL injection prevention
- XSS protection
- File upload security
- Email validation

---

## 📊 Key Features Summary

### User Management Features:
✅ **Registration:** Complete signup flow with email verification
✅ **Authentication:** Secure login with remember me
✅ **Profile Management:** Full CRUD operations for user profiles
✅ **Password Security:** Secure password hashing and reset functionality

### Admin Management Features:
✅ **User Control:** Ban/unban users with reason logging
✅ **Role Management:** Promote/demote users to admin roles
✅ **Mailing System:** Manual and automatic email campaigns
✅ **Audit Logging:** Complete system activity tracking
✅ **Statistics:** Comprehensive analytics dashboard
✅ **System Monitoring:** Real-time user activity tracking

### Technical Implementation:
✅ **Framework:** Symfony 6.4 with modern PHP practices
✅ **Database:** Doctrine ORM with MySQL
✅ **Security:** Multi-layer security implementation
✅ **Frontend:** Twig templates with Tailwind CSS/DaisyUI
✅ **Email:** Symfony Mailer with template system
✅ **Charts:** Chart.js for analytics visualization

---

## 📁 File Structure

```
src/
├── Controller/
│   ├── RegistrationController.php     # User registration
│   ├── SecurityController.php         # Login/logout
│   ├── ProfileController.php          # Profile management
│   └── Admin/
│       ├── AdminController.php        # Admin dashboard
│       ├── UserController.php         # User management
│       ├── AdminAuditController.php   # Audit logs
│       └── StatisticsController.php   # Analytics
├── Entity/
│   ├── User.php                      # User entity
│   └── AuditLog.php                  # Audit log entity
├── Service/
│   ├── MailingService.php            # Email service
│   └── AuditLogService.php           # Audit logging
├── Form/
│   └── RegistrationFormType.php      # Registration form
└── Security/
    └── LoginFormAuthenticator.php    # Login authentication

templates/
├── registration/
├── security/
├── admin/
│   ├── dashboard.html.twig
│   ├── users/
│   ├── statistics.html.twig
│   └── audit/
├── emails/
└── pages/settings/
```

---

## 🚀 Deployment & Configuration

### Environment Configuration:
- **Development:** Full debugging enabled
- **Production:** Optimized performance settings
- **Database:** MySQL with connection pooling
- **Email:** SMTP configuration for production
- **Security:** HTTPS enforcement in production

### Performance Optimizations:
- Database query optimization
- Caching strategies implemented
- Asset compilation and minification
- CDN integration ready

---

*This documentation covers all technical aspects of the user management and administrative systems implemented in the RLIFE application.*