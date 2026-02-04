# StudyFlow - New Features Guide

## Overview
This document describes the new features implemented for the StudyFlow user management system.

---

## 1. Profile Picture Upload ✨

### Description
Users can now upload custom profile pictures to personalize their accounts. Profile pictures are displayed throughout the application in the sidebar, header, and admin panel.

### Features
- **Supported formats**: JPG, PNG, WEBP
- **Max file size**: 2MB
- **Live preview**: See your profile picture before saving
- **Automatic deletion**: Old profile pictures are deleted when uploading a new one
- **Fallback display**: Shows user initials if no profile picture is uploaded

### How to Use
1. Go to **Settings** (`/settings`)
2. In the **Profile Information** section, click **Upload Photo**
3. Select an image file (JPG, PNG, or WEBP)
4. Preview appears instantly
5. Click **Save Changes** to upload

### Technical Details
- **Upload directory**: `public/uploads/profiles/`
- **Filename**: MD5 hash of unique ID + file extension
- **Controller**: `SettingsController::index()` - lines 41-77
- **Template**: `templates/pages/settings/index.html.twig` - lines 18-42
- **Display locations**:
  - User sidebar: `templates/base.html.twig` - lines 153-163
  - Header dropdown: `templates/base.html.twig` - lines 279-289
  - Admin panel: `templates/admin/base_admin.html.twig`

### Security
- File type validation (only images allowed)
- File size validation (max 2MB)
- Secure filename generation (prevents directory traversal)
- Server-side and client-side validation

---

## 2. Delete Account Functionality 🗑️

### Description
Users can permanently delete their accounts with a secure confirmation process. The system performs a **hard delete** that completely removes all user data from the database.

### Features
- **Confirmation modal**: Prevents accidental deletion
- **Password verification**: Requires current password to proceed
- **Hard delete**: Account is permanently removed from database
- **Profile cleanup**: Profile picture is deleted from server
- **Auto logout**: User is logged out immediately before deletion
- **Cascade delete**: UserSettings and UserCareer records are automatically deleted

### How to Use
1. Go to **Settings** (`/settings`)
2. Scroll to the **Danger Zone** section at the bottom
3. Click **Delete Account**
4. A modal appears with warning message
5. Enter your **current password** to confirm
6. Click **Delete Account** button
7. You'll be logged out and redirected to landing page

### Technical Details
- **Controller**: `SettingsController::deleteAccount()` - lines 140-183
- **Route**: `/settings/delete` (POST only)
- **Template modal**: `templates/pages/settings/index.html.twig` - lines 279-312
- **JavaScript functions**:
  - `confirmDeleteAccount()` - opens modal
  - `closeDeleteModal()` - closes modal without deleting

### What Happens (Step by Step)
1. Password is verified against user's current password
2. User is logged out immediately (security token cleared)
3. Profile picture file is deleted from server (if exists)
4. User record is removed from database using `$entityManager->remove($user)`
5. Related records are automatically deleted (cascade):
   - `UserSettings` record is deleted
   - All `UserCareer` records are deleted
6. Session is invalidated
7. User is redirected to landing page with success message

### Important Notes
- **This is permanent**: Deleted accounts **cannot be recovered**
- **Database cleanup**: All user data is completely removed from all tables
- **No soft delete**: Unlike the ban system, delete is irreversible
- **Orphan removal**: Doctrine's cascade delete ensures no orphaned records remain

---

## 3. Enhanced Form Validation 🛡️

### Description
Comprehensive client-side and server-side validation to ensure data quality and security.

### Client-Side Validation

#### Settings Form
- **Required fields**: First Name, Last Name, Username, Email
- **Email format**: Valid email address pattern
- **Username format**: 3-20 characters, alphanumeric + underscores only
- **Password change**: Both current and new password required
- **Password length**: Minimum 6 characters
- **Phone number**: Valid phone format (8-20 chars, supports +, -, (), spaces)

#### JavaScript Validation Function
Location: `templates/pages/settings/index.html.twig` - lines 303-348
- Real-time validation on form submit
- Clear error messages via alerts
- Prevents form submission if validation fails

#### HTML5 Validation Attributes
- `required` - prevents empty submission
- `pattern` - regex validation for username, phone
- `minlength` - minimum password length
- `title` - helpful tooltips on hover
- `type="email"` - browser email validation

### Server-Side Validation

#### SettingsController Validation
- **Email uniqueness**: Checks if email is already taken by another user
- **Username uniqueness**: Checks if username exists
- **Password verification**: Current password must be correct for changes
- **Profile picture validation**:
  - File type check (MIME type)
  - File size check (max 2MB)
  - Extension validation

#### Error Handling
- Flash messages for all validation errors
- User-friendly error descriptions
- Redirect back to form with error message
- Form data preserved (except passwords)

---

## 4. Flash Messages System 💬

### Description
Visual feedback system that informs users about the success or failure of their actions.

### Features
- **4 message types**:
  - ✅ **Success** (green) - Action completed successfully
  - ❌ **Error/Danger** (red) - Action failed or validation error
  - ⚠️ **Warning** (yellow) - Important notice
  - ℹ️ **Info** (blue) - Informational message
- **Auto-dismiss**: Messages fade out after 5 seconds
- **Manual dismiss**: Click to close immediately
- **Icons**: Lucide icons for visual clarity
- **Animations**: Smooth fade-in from bottom-right

### Implementation
Location: `templates/base.html.twig` - lines 322-346

### Usage in Controllers
```php
// Success message
$this->addFlash('success', 'Profile updated successfully!');

// Error message
$this->addFlash('error', 'Email is already taken.');

// Warning message
$this->addFlash('warning', 'Please verify your email address.');

// Info message
$this->addFlash('info', 'Your session will expire in 5 minutes.');
```

### Current Flash Messages

#### SettingsController
- Profile update success
- Password change success
- Validation errors (email taken, username taken, incorrect password, etc.)
- Profile picture errors (invalid type, too large)

#### PublicController (Registration)
- Account created successfully
- Email already exists
- Username already taken
- Missing required fields

#### AdminController
- User banned successfully
- User unbanned successfully
- Admin role granted
- Admin role removed

---

## Files Modified

### Controllers
- `src/Controller/SettingsController.php` - Profile upload, delete account, validation
- `src/Controller/PublicController.php` - Already had flash messages
- `src/Controller/AdminController.php` - Already had flash messages

### Templates
- `templates/pages/settings/index.html.twig` - Profile upload, delete modal, validation
- `templates/base.html.twig` - Profile picture display, flash messages
- `templates/admin/base_admin.html.twig` - Profile picture display

### New Files
- `public/uploads/.gitignore` - Prevents committing user uploads
- `public/uploads/profiles/` - Directory for profile pictures

---

## Testing Checklist

### Profile Picture Upload
- [ ] Upload JPG image
- [ ] Upload PNG image
- [ ] Upload WEBP image
- [ ] Try uploading PDF (should reject)
- [ ] Try uploading 3MB file (should reject)
- [ ] Upload new picture (old one deleted)
- [ ] Check picture displays in sidebar
- [ ] Check picture displays in header
- [ ] Check picture displays in admin panel

### Delete Account
- [ ] Click Delete Account button
- [ ] Modal appears
- [ ] Try empty password (should reject)
- [ ] Try wrong password (should reject)
- [ ] Enter correct password and delete
- [ ] Verify logged out
- [ ] Verify redirected to landing page
- [ ] Verify account is banned in database
- [ ] Admin can see account in "Banned" filter
- [ ] Admin can unban account

### Form Validation
- [ ] Try empty required fields
- [ ] Try invalid email format
- [ ] Try username with spaces (should reject)
- [ ] Try username with 2 chars (should reject)
- [ ] Try username with 21 chars (should reject)
- [ ] Try duplicate email (should reject)
- [ ] Try duplicate username (should reject)
- [ ] Try password change without current password
- [ ] Try password change with 5 chars (should reject)
- [ ] Try invalid phone format

### Flash Messages
- [ ] Update profile successfully (green message)
- [ ] Change password successfully (green message)
- [ ] Try duplicate email (red message)
- [ ] Upload profile picture successfully
- [ ] Messages auto-dismiss after 5s
- [ ] Messages can be clicked to dismiss

---

## Database Impact

### No Schema Changes Required
All new features use existing database fields:
- `profile_pic` - Already existed in User entity
- `is_banned`, `banned_at`, `ban_reason` - Already existed for soft delete

### Directory Structure
```
public/
└── uploads/
    ├── .gitignore
    └── profiles/
        └── [user profile pictures]
```

---

## Security Considerations

### Profile Picture Upload
✅ File type validation (MIME type check)  
✅ File size limit (2MB)  
✅ Secure filename generation (MD5 hash)  
✅ Directory traversal prevention  
✅ File cleanup on replacement  

### Delete Account
✅ Password verification required  
✅ CSRF protection (Symfony forms)  
✅ POST-only route  
✅ Soft delete (data preservation)  
✅ Session invalidation  

### Form Validation
✅ Client-side validation (UX)  
✅ Server-side validation (security)  
✅ Input sanitization  
✅ XSS prevention (Twig auto-escaping)  
✅ SQL injection prevention (Doctrine ORM)  

---

## Next Steps (Optional Enhancements)

1. **Email Verification**
   - Send verification email on registration
   - Verify email before account activation

2. **Password Reset**
   - Forgot password functionality
   - Email-based password reset link

3. **Profile Picture Cropping**
   - Allow users to crop images before upload
   - Ensure consistent aspect ratio

4. **Account Recovery**
   - Grace period for deleted accounts
   - Email notification before permanent deletion

5. **Admin User Detail View**
   - View individual user details
   - See user activity logs
   - View user's career aspirations

6. **Two-Factor Authentication**
   - Optional 2FA for enhanced security
   - Email or SMS verification codes

---

## Conclusion

All new features have been successfully implemented and are ready for testing. The system now provides:

✅ **Profile picture upload** with validation and preview  
✅ **Delete account** with confirmation and soft delete  
✅ **Enhanced validation** on client and server side  
✅ **Flash messages** for user feedback  

The cache has been cleared and the application is ready to use!

**To test the features:**
1. Start your XAMPP server (Apache + MySQL)
2. Run `symfony server:start` or access via Apache
3. Login with existing credentials or create new account
4. Navigate to Settings to test all new features
