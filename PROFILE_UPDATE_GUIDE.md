# Profile Update Guide - StudyFlow

## ✅ UPDATE Functionality is LIVE!

You can now **edit your profile information** and **all changes will be saved** to both the website display AND the database!

---

## 🎯 What You Can Update

### ✅ **Personal Information**
- First Name
- Last Name
- Username
- Email Address
- Phone Number
- Gender (Male/Female)
- Bio (About yourself)

### ✅ **Academic Information**
- University
- Student ID

### ✅ **Security**
- Change Password

---

## 📝 How to Update Your Profile

### Step 1: Go to Settings

1. **Login to your account**
2. **Click "Settings"** in the sidebar (at the bottom)
3. **You'll see the Settings page** with all your current information

---

### Step 2: Edit Your Information

**The form shows your current data:**

#### **Profile Information Section:**
```
First Name: Farah
Last Name: Nono
Username: farahnono
Email: farah.nono@test.com
Phone Number: +1 234 567 8900
Gender: ○ Male ● Female
Bio: I'm a computer science student passionate about web development...
```

#### **Academic Information Section:**
```
University: University of Technology
Student ID: STU123456
```

#### **Change Password Section:**
```
Current Password: (leave empty if not changing)
New Password: (leave empty if not changing)
```

---

### Step 3: Make Your Changes

**Example: Update Your Name and University**

1. **Change First Name:**
   - Current: `Farah`
   - New: `Sarah`

2. **Change University:**
   - Current: `University of Technology`
   - New: `MIT - Massachusetts Institute of Technology`

3. **Add Phone Number:**
   - Current: (empty)
   - New: `+1 555 123 4567`

4. **Update Bio:**
   ```
   I'm a passionate computer science student at MIT, 
   specializing in AI and machine learning. 
   Love coding and building innovative projects!
   ```

---

### Step 4: Save Changes

1. **Click "Save Changes"** button at the bottom
2. **You'll see:** Green success message
   > "Profile updated successfully!"
3. **Page refreshes** with your new information

---

### Step 5: Verify Changes

**Check in Multiple Places:**

1. **Settings Page:**
   - All fields show your updated information

2. **Sidebar (Bottom Left):**
   - Shows updated initials (SN instead of FN)
   - Shows updated name (Sarah Nono instead of Farah Nono)

3. **Top Bar Profile Dropdown:**
   - Click avatar → See updated name and email

4. **Database:**
   - Your data is permanently saved!

---

## 🔐 Change Password Feature

### How to Change Your Password:

1. **In Settings page, scroll to "Change Password" section**

2. **Fill in:**
   ```
   Current Password: YourOldPassword123
   New Password: MyNewSecurePass456
   ```

3. **Click "Save Changes"**

4. **You'll see TWO success messages:**
   - "Password changed successfully!"
   - "Profile updated successfully!"

5. **Next login:** Use your NEW password

### Password Change Security:
✅ **Must enter current password** (for verification)  
✅ **New password is hashed** before saving  
✅ **Old password verified** before accepting change  
✅ **Error shown** if current password is wrong  

---

## ✅ Validation & Error Handling

### **Required Fields:**
- First Name *(cannot be empty)*
- Last Name *(cannot be empty)*
- Username *(cannot be empty, must be unique)*
- Email *(cannot be empty, must be unique)*
- Gender *(must select one)*

### **Error Messages:**

**1. Empty Required Fields:**
```
❌ Please fill in all required fields.
```

**2. Email Already Taken:**
```
❌ This email is already taken by another user.
```

**3. Username Already Taken:**
```
❌ This username is already taken.
```

**4. Wrong Current Password:**
```
❌ Current password is incorrect.
```

### **Success Messages:**

**Profile Updated:**
```
✅ Profile updated successfully!
```

**Password Changed:**
```
✅ Password changed successfully!
✅ Profile updated successfully!
```

---

## 🗄️ Database Changes

### What Gets Updated in Database:

**`user` Table:**
```sql
UPDATE user SET
    first_name = 'Sarah',
    last_name = 'Nono',
    username = 'sarahnono',
    email = 'sarah.nono@mit.edu',
    phone_number = '+1 555 123 4567',
    bio = 'I am passionate about...',
    university = 'MIT',
    student_id = 'MIT2024',
    gender = 'female',
    updated_at = NOW()
WHERE id = YOUR_USER_ID;
```

### Verify in Database:

**Check your updated data:**
```bash
php bin/console dbal:run-sql "SELECT first_name, last_name, email, university, phone_number FROM user WHERE id = YOUR_ID"
```

**Or in phpMyAdmin:**
1. Go to `http://localhost/phpmyadmin`
2. Select database: `studyflow`
3. Click table: `user`
4. Find your user by email
5. See all your updated fields!

---

## 🧪 Testing Scenarios

### ✅ Test 1: Update Name
1. Go to Settings
2. Change: `Farah Nono` → `Sarah Johnson`
3. Save Changes
4. **Check:** Sidebar shows "SJ" and "Sarah Johnson"

### ✅ Test 2: Update Email
1. Change: `farah@test.com` → `sarah@mit.edu`
2. Save Changes
3. Logout and login with NEW email
4. **Works!**

### ✅ Test 3: Add University
1. University field is empty
2. Type: `Harvard University`
3. Save Changes
4. **Check database:** Field now has "Harvard University"

### ✅ Test 4: Change Password
1. Current Password: `OldPass123`
2. New Password: `NewPass456`
3. Save Changes
4. Logout
5. Login with `NewPass456`
6. **Works!**

### ✅ Test 5: Invalid Email
1. Try to use: `admin@studyflow.com` (already exists)
2. Save Changes
3. **See error:** "This email is already taken"

### ✅ Test 6: Empty Required Field
1. Clear First Name field
2. Save Changes
3. **See error:** "Please fill in all required fields"

---

## 🔄 Complete Update Flow

```
┌─────────────────────────────────┐
│  Click "Settings" in Sidebar    │
└──────────────┬──────────────────┘
               │
               ↓
┌─────────────────────────────────┐
│  Settings Page Loads            │
│  • Shows current user data      │
│  • All fields pre-filled        │
└──────────────┬──────────────────┘
               │
               ↓
┌─────────────────────────────────┐
│  User Makes Changes             │
│  • Edit name, email, etc.       │
│  • Optionally change password   │
└──────────────┬──────────────────┘
               │
               ↓
┌─────────────────────────────────┐
│  Click "Save Changes"           │
└──────────────┬──────────────────┘
               │
               ↓
┌─────────────────────────────────┐
│  Backend Validation             │
│  ✓ Check required fields        │
│  ✓ Verify email uniqueness      │
│  ✓ Verify username uniqueness   │
│  ✓ Verify current password      │
└──────────────┬──────────────────┘
               │
       ┌───────┴────────┐
       │                │
       ↓                ↓
┌──────────┐    ┌──────────────┐
│  Error   │    │   Success    │
│  Message │    │              │
└──────────┘    └──────┬───────┘
                       │
                       ↓
            ┌─────────────────────┐
            │  Update Database    │
            │  • Save all fields  │
            │  • Hash new pass    │
            │  • Set updated_at   │
            └──────┬──────────────┘
                   │
                   ↓
            ┌─────────────────────┐
            │  Show Success       │
            │  Redirect to        │
            │  Settings Page      │
            └──────┬──────────────┘
                   │
                   ↓
            ┌─────────────────────┐
            │  Changes Visible!   │
            │  • Updated in UI    │
            │  • Saved in DB      │
            └─────────────────────┘
```

---

## 💡 Pro Tips

### **1. Update Multiple Fields at Once**
You don't need to save after each field. Change everything you want, then click "Save Changes" once!

### **2. Optional Fields**
You can leave these empty:
- Phone Number
- Bio
- University
- Student ID

### **3. Password Change is Optional**
Leave password fields empty if you only want to update profile info.

### **4. Username Rules**
- Must be unique
- No spaces allowed
- Can use letters, numbers, underscores

### **5. Email Rules**
- Must be valid email format
- Must be unique
- Used for login

---

## 🎉 Summary

**Your Profile Update System is Complete!**

✅ **Edit all your information** in one place  
✅ **Changes save to database** permanently  
✅ **Updates appear instantly** across the website  
✅ **Secure password change** functionality  
✅ **Full validation** prevents errors  
✅ **User-friendly error messages**  

**Try it now:**
1. Login to your account
2. Click "Settings" in sidebar
3. Update your name, university, or any field
4. Click "Save Changes"
5. See your changes everywhere!

**Everything works perfectly!** 🚀
