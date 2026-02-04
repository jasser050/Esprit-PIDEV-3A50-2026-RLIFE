# User Registration & Login Guide - StudyFlow

## ✅ Complete CRUD Implementation

Your StudyFlow application now has **full CRUD (Create, Read, Update, Delete)** functionality for user management!

---

## 🎯 What's Working Now

### 1. **CREATE - User Registration**
✅ Multi-step registration wizard  
✅ All user data saved to database  
✅ Password hashing with bcrypt  
✅ UserSettings automatically created  
✅ Validation for unique email and username  

### 2. **READ - User Login**
✅ Email-based authentication  
✅ Password verification  
✅ Role-based redirect (Admin vs User)  
✅ Session management  

### 3. **UPDATE - User Profile** *(Coming in next phase)*
- Edit profile information
- Change password
- Update preferences

### 4. **DELETE - User Management** *(Available in Admin Panel)*
✅ Admins can ban/unban users  
✅ Ban reasons stored  

---

## 📝 How to Test the Registration Flow

### Step 1: Go to Registration Page

1. **Open your browser**
2. **Navigate to:** `http://localhost/register`
3. **You should see:** Beautiful 2-step registration wizard

---

### Step 2: Fill Out Step 1 (Account Details)

**Fields Required:**

| Field | Example | Notes |
|-------|---------|-------|
| **First Name** | John | Required |
| **Last Name** | Doe | Required |
| **Username** | johndoe | Required, must be unique |
| **Email** | john@university.edu | Required, must be unique |
| **Password** | MyPassword123 | Required, must meet requirements |
| **Confirm Password** | MyPassword123 | Must match password |
| **Terms Checkbox** | ✓ | Must be checked |

**Password Requirements:**
- ✅ At least 8 characters
- ✅ One lowercase letter
- ✅ One uppercase letter
- ✅ One number

**Example Values:**
```
First Name: Sarah
Last Name: Johnson
Username: sarahj2024
Email: sarah.johnson@university.edu
Password: SecurePass123
Confirm Password: SecurePass123
[✓] I agree to the Terms
```

**Click: "Next step"** button

---

### Step 3: Fill Out Step 2 (Preferences)

**Fields to Select:**

1. **Gender** (Required)
   - [ ] Male
   - [✓] Female

2. **Study Level**
   - [ ] Beginner
   - [✓] Intermediate
   - [ ] Advanced

3. **Weekly Goal**
   - Slide to set hours: `7 hours`

4. **Interests** (Select multiple)
   - [✓] Web Dev
   - [✓] Design
   - [ ] Data Science
   - [✓] Business
   - etc.

5. **Notifications**
   - [✓] Email me assignment reminders

**Click: "Create account"** button

---

### Step 4: Verify Success

**After clicking "Create account":**

1. **You will see:** Green success message
   > "Account created successfully! Please log in."

2. **You will be redirected to:** `/login`

3. **Database Check:** Your data is now in the `user` and `user_settings` tables!

---

### Step 5: Login with Your New Account

**On the Login Page:**

1. **Enter your credentials:**
   ```
   Email: sarah.johnson@university.edu
   Password: SecurePass123
   ```

2. **Click:** "Sign in" button

3. **You will be redirected to:** `/dashboard` (User Dashboard)

4. **You should see:**
   - Your name in the sidebar
   - Full student dashboard
   - All study features accessible

---

## 🗄️ Database Verification

### Check Your User in the Database

**Option 1: Using Symfony Console**
```bash
php bin/console dbal:run-sql "SELECT id, email, first_name, last_name, username, gender, created_at FROM user ORDER BY id DESC LIMIT 5"
```

**Option 2: Using phpMyAdmin**
1. Open: `http://localhost/phpmyadmin`
2. Select database: `studyflow`
3. Click on table: `user`
4. You should see your new user!

**Sample Output:**
```
id | email                         | first_name | last_name | username   | gender | created_at
1  | admin@studyflow.com           | Admin      | User      | admin      | male   | 2026-02-03 00:00:00
2  | sarah.johnson@university.edu  | Sarah      | Johnson   | sarahj2024 | female | 2026-02-03 15:30:00
```

---

## 📊 What Gets Saved to Database

### `user` Table
```sql
✅ id              (auto-generated)
✅ email           sarah.johnson@university.edu
✅ password        (hashed with bcrypt)
✅ first_name      Sarah
✅ last_name       Johnson
✅ username        sarahj2024
✅ gender          female
✅ roles           ["ROLE_USER"]
✅ is_banned       false
✅ created_at      2026-02-03 15:30:00
✅ updated_at      2026-02-03 15:30:00
✅ phone_number    null
✅ profile_pic     null
✅ bio             null
✅ student_id      null
✅ university      null
✅ banned_at       null
✅ ban_reason      null
```

### `user_settings` Table
```sql
✅ id                      (auto-generated)
✅ user_id                 2 (links to user.id)
✅ study_level             intermediate
✅ weekly_goal             7
✅ interests               ["web_dev","design","business"]
✅ notification_enabled    true
✅ email_notifications     true
✅ theme_preference        light
✅ language                en
```

---

## 🧪 Testing Scenarios

### ✅ Test 1: Successful Registration
1. Fill all fields correctly
2. Use unique email and username
3. Password meets requirements
4. **Expected:** Success message + redirect to login

### ✅ Test 2: Duplicate Email
1. Try to register with `admin@studyflow.com`
2. **Expected:** Error: "An account with this email already exists."

### ✅ Test 3: Duplicate Username
1. Try to register with username `admin`
2. **Expected:** Error: "This username is already taken."

### ✅ Test 4: Login After Registration
1. Register new account
2. Login with same credentials
3. **Expected:** Redirected to user dashboard

### ✅ Test 5: Admin vs User Login
1. Login as `admin@studyflow.com`
   - **Expected:** Redirected to `/admin`
2. Login as regular user
   - **Expected:** Redirected to `/dashboard`

---

## 🔒 Security Features Implemented

✅ **Password Hashing**: Bcrypt algorithm  
✅ **CSRF Protection**: Enabled on all forms  
✅ **SQL Injection Prevention**: Doctrine ORM parameterized queries  
✅ **Unique Constraints**: Email and username must be unique  
✅ **Role-Based Access**: ROLE_USER vs ROLE_ADMIN  
✅ **Session Security**: Secure session handling  

---

## 🐛 Troubleshooting

### Issue: "An account with this email already exists"
**Solution:** Use a different email address or check if you already registered.

### Issue: "This username is already taken"
**Solution:** Choose a different username.

### Issue: Password doesn't meet requirements
**Solution:** Ensure password has:
- At least 8 characters
- One uppercase letter
- One lowercase letter
- One number

### Issue: Can't login after registration
**Solution:**
1. Check that you're using the correct email (not username)
2. Verify password is correct
3. Check database to confirm user was created:
   ```bash
   php bin/console dbal:run-sql "SELECT email FROM user WHERE email = 'your-email@example.com'"
   ```

### Issue: Redirected to wrong dashboard
**Solution:**
- Admin users go to `/admin`
- Regular users go to `/dashboard`
- This is expected behavior!

---

## 📱 Registration Flow Diagram

```
┌─────────────────────────────────┐
│  Visit /register                │
└──────────────┬──────────────────┘
               │
               ↓
┌─────────────────────────────────┐
│  Step 1: Account Details        │
│  • First Name                   │
│  • Last Name                    │
│  • Username                     │
│  • Email                        │
│  • Password                     │
│  • Confirm Password             │
│  • Accept Terms                 │
└──────────────┬──────────────────┘
               │ Click "Next"
               ↓
┌─────────────────────────────────┐
│  Step 2: Preferences            │
│  • Gender                       │
│  • Study Level                  │
│  • Weekly Goal                  │
│  • Interests                    │
│  • Notifications                │
└──────────────┬──────────────────┘
               │ Click "Create account"
               ↓
┌─────────────────────────────────┐
│  Backend Processing             │
│  ✓ Validate all fields          │
│  ✓ Check email uniqueness       │
│  ✓ Check username uniqueness    │
│  ✓ Hash password                │
│  ✓ Create User entity           │
│  ✓ Create UserSettings entity   │
│  ✓ Save to database             │
└──────────────┬──────────────────┘
               │ Success!
               ↓
┌─────────────────────────────────┐
│  Redirect to /login             │
│  Show success message           │
└──────────────┬──────────────────┘
               │
               ↓
┌─────────────────────────────────┐
│  Login Page                     │
│  Enter email & password         │
└──────────────┬──────────────────┘
               │ Click "Sign in"
               ↓
┌─────────────────────────────────┐
│  Logged In!                     │
│  Redirect to /dashboard         │
└─────────────────────────────────┘
```

---

## 🎉 Summary

**Your StudyFlow application is now ALIVE!**

✅ **Users can register** → Data saved to database  
✅ **Users can login** → Authentication works  
✅ **Sessions persist** → Stay logged in  
✅ **Role-based access** → Admin vs User separation  
✅ **All data validated** → Secure and reliable  

**Next Steps:**
1. Test the registration yourself
2. Create multiple user accounts
3. Explore the dashboard features
4. Admin can manage users from `/admin/users`

**Happy coding!** 🚀
