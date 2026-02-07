# Merge Summary: User Branch to Main

## Overview
This pull request merges all changes from the `user` branch into the `main` branch, bringing significant new features and improvements to the PIDEV application.

## Branch Information
- **Source Branch**: `user` (commit: 8adf13b - "Standardize logo sizes")
- **Target Branch**: `main` (commit: a9afdf3 - "Delete insert_admin.sql")
- **PR Branch**: `copilot/merge-user-branch-to-main`

## Key Features Added

### 1. Admin Audit Logging System
Complete audit trail functionality for tracking administrative actions:
- **Entity**: `AdminAuditLog` - Stores admin action logs with details
- **Service**: `AuditLogService` - Business logic for logging admin operations
- **Controller**: `AdminAuditController` - Web interface for viewing audit logs
- **Repository**: `AdminAuditLogRepository` - Database queries for audit logs
- **Template**: `templates/admin/audit/log.html.twig` - UI for viewing logs

**Capabilities**:
- Track admin actions (create, update, delete)
- Record target type and ID
- Capture IP address and user agent
- Timestamp all actions

### 2. Admin Email System  
Bulk email sending functionality for admin users:
- **Entity**: `AdminEmailLog` - Stores email sending history
- **Service**: `AdminMailerService` - Handles email composition and sending
- **Controller**: `AdminEmailController` - Web interface for email management
- **Repository**: `AdminEmailLogRepository` - Database queries for email logs
- **Template**: `templates/admin/emails/compose.html.twig` - Rich email composition UI

**Capabilities**:
- Send emails to all users
- Send to active users only
- Send to users by role
- Send to individual users
- Track email history and status

### 3. Educational Content Management
New entities for managing educational content:
- **Assignment** (`src/Entity/Assignment.php`) - Student assignments
- **Deck** (`src/Entity/Deck.php`) - Flashcard deck collections
- **Flashcard** (`src/Entity/Flashcard.php`) - Individual flashcards for learning
- **Project** (`src/Entity/Project.php`) - Student project management

Each entity includes:
- Complete CRUD repositories
- Proper Doctrine ORM annotations
- Validation attributes
- Relationship mappings

### 4. Logo Standardization
Unified branding across the application:
- **Removed**: `public/image/logo.jpg` (old logo)
- **Added**: `public/image/logo0.png` (165KB - primary logo)
- **Added**: `public/image/logo1.png` (163KB - alternative logo)
- **Updated**: All templates to reference new logo files

### 5. Configuration Updates
Enhanced application configuration:
- Updated `composer.json` and `composer.lock` with new dependencies
- Added mailer configuration (`config/packages/mailer.yaml`)
- Updated CSRF configuration (`config/packages/csrf.yaml`)
- Updated property info and UX Turbo packages
- Added Symfony lock entries for new packages

### 6. Template Updates
UI improvements across multiple templates:
- `templates/admin/base_admin.html.twig` - Enhanced admin layout
- `templates/base.html.twig` - Updated base template
- `templates/pages/auth/login.html.twig` - Login page updates
- `templates/pages/auth/register.html.twig` - Registration page updates  
- `templates/pages/landing.html.twig` - Landing page improvements

### 7. Additional Files
- `landing.html.twig` - New landing page template (42KB)
- `compose.override.yaml` - Docker compose overrides
- `.env` updates - Environment configuration
- `nul_` - Placeholder file

## Statistics
- **Total Files Changed**: 40
- **Lines Added**: 5,327
- **Lines Removed**: 1,053
- **Net Change**: +4,274 lines

### Breakdown by Category:
- **New Entities**: 4 (Assignment, Deck, Flashcard, Project)
- **New Services**: 2 (AdminMailerService, AuditLogService)
- **New Controllers**: 2 (AdminAuditController, AdminEmailController)
- **New Repositories**: 6
- **New Templates**: 3
- **Updated Templates**: 6
- **Configuration Files**: 8
- **Images**: 2 added, 1 removed

## Testing Recommendations
Before merging, verify:
1. Database migrations run successfully for new entities
2. Composer dependencies install without conflicts
3. Admin audit logging captures actions correctly
4. Email system sends test emails successfully
5. All templates render without errors
6. Logo displays correctly across all pages
7. No broken references to the old logo.jpg file

## Security Considerations
- Audit logging provides accountability for admin actions
- Email system requires admin privileges
- IP address and user agent tracking for security monitoring
- CSRF protection updated and maintained

## Migration Steps
After merging:
```bash
# Install dependencies
composer install

# Run database migrations
php bin/console doctrine:migrations:migrate

# Clear cache
php bin/console cache:clear

# Verify setup
php bin/console about
```

## Compatibility
- Requires Symfony 6.x+
- PHP 8.1+ recommended
- Doctrine ORM for entity management
- Symfony Mailer for email functionality

## Original Commit
The changes in this PR originate from commit `8adf13b` on the `user` branch:
```
commit 8adf13b (user)
Author: [Author from user branch]
Date:   [Date from user branch]

    Standardize logo sizes
```

## Notes
- All PHP files pass syntax validation
- No merge conflicts detected
- Changes are additive; no existing functionality removed
- Proper separation of concerns maintained (Entity-Repository-Service-Controller)
