# Face ID Authentication - Sequence Diagram Documentation

## Overview

The Face ID feature provides biometric authentication during user registration. It captures facial data using the device camera, extracts facial landmarks, and stores encrypted face descriptors for future authentication.

---

## System Architecture

### Components Involved

| Component | Description |
|-----------|-------------|
| **User** | The person registering their face |
| **Browser** | Web browser with camera access |
| **Camera API** | WebRTC/MediaDevices API for video capture |
| **face-api.js** | JavaScript library for face detection & recognition |
| **Registration Form** | HTML form in Step 4 of registration |
| **Server (Symfony)** | Backend controller handling registration |
| **User Entity** | Database entity storing face_descriptor (JSON) |
| **Database** | MariaDB storage for user data |

---

## Complete Sequence Diagram

```
┌─────────┐     ┌──────────┐     ┌────────────┐     ┌─────────────┐     ┌────────────┐     ┌──────────┐
│  User   │     │ Browser  │     │ Camera API │     │ face-api.js │     │   Server   │     │ Database │
└────┬────┘     └────┬─────┘     └─────┬──────┘     └──────┬──────┘     └─────┬──────┘     └────┬─────┘
     │               │                 │                   │                  │                 │
     │  1. Navigate to Registration    │                   │                  │                 │
     │──────────────>│                 │                   │                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 2. Load face-api.min.js             │                  │                 │
     │               │─────────────────────────────────────>│                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 3. Load Face Landmark Model         │                  │                 │
     │               │<─────────────────────────────────────│                  │                 │
     │               │                 │                   │                  │                 │
     │  4. Click "Enable Face ID"      │                   │                  │                 │
     │──────────────>│                 │                   │                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 5. Request Camera Permission        │                  │                 │
     │               │────────────────>│                   │                  │                 │
     │               │                 │                   │                  │                 │
     │  6. Grant/Deny Permission       │                   │                  │                 │
     │<──────────────│                 │                   │                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 7. Return MediaStream               │                  │                 │
     │               │<────────────────│                   │                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 8. Display Video Preview            │                  │                 │
     │<──────────────│                 │                   │                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 9. Start Face Detection Loop        │                  │                 │
     │               │─────────────────────────────────────>│                  │                 │
     │               │                 │                   │                  │                 │
     │               │                 │    10. Detect Face in Frame        │                 │
     │               │                 │    (Every 100ms)   │                  │                 │
     │               │<─────────────────────────────────────│                  │                 │
     │               │                 │                   │                  │                 │
     │  11. Position Face in Oval      │                   │                  │                 │
     │──────────────>│                 │                   │                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 12. Face Detected in Correct Position│                 │                 │
     │               │<─────────────────────────────────────│                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 13. Extract 68 Facial Landmarks     │                  │                 │
     │               │─────────────────────────────────────>│                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 14. Return Landmarks Array          │                  │                 │
     │               │  [{x: 123, y: 456}, ...]            │                  │                 │
     │               │<─────────────────────────────────────│                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 15. Compute Face Descriptor (128 floats)│               │                 │
     │               │─────────────────────────────────────>│                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 16. Return Float32Array(128)        │                  │                 │
     │               │<─────────────────────────────────────│                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 17. Store Descriptor in Hidden Input │                  │                 │
     │               │  <input name="face_descriptor"       │                  │                 │
     │               │   value="[0.123, -0.456, ...]">      │                  │                 │
     │               │                 │                   │                  │                 │
     │  18. Click "Complete Registration"│                  │                  │                 │
     │──────────────>│                 │                   │                  │                 │
     │               │                 │                   │                  │                 │
     │               │ 19. POST /register                   │                  │                 │
     │               │ {first_name, last_name, email,       │                  │                 │
     │               │  password, face_descriptor: [...]}   │                  │                 │
     │               │──────────────────────────────────────────────────────────>│                 │
     │               │                 │                   │                  │                 │
     │               │                 │                   │    20. Validate Form Data        │
     │               │                 │                   │                  │                 │
     │               │                 │                   │    21. Hash Password             │
     │               │                 │                   │                  │                 │
     │               │                 │                   │    22. Create User Entity        │
     │               │                 │                   │                  │                 │
     │               │                 │                   │    23. Set face_descriptor (JSON)│                 │
     │               │                 │                   │                  │                 │
     │               │                 │                   │    24. Persist User              │
     │               │                 │                   │──────────────────────────────────>│
     │               │                 │                   │                  │                 │
     │               │                 │                   │    25. Confirm Save              │
     │               │                 │                   │<──────────────────────────────────│
     │               │                 │                   │                  │                 │
     │               │ 26. Redirect /login                  │                  │                 │
     │               │<──────────────────────────────────────────────────────────│                 │
     │               │                 │                   │                  │                 │
     │  27. Registration Complete      │                   │                  │                 │
     │<──────────────│                 │                   │                  │                 │
     │               │                 │                   │                  │                 │
```

---

## Detailed Step Breakdown

### Phase 1: Initialization

#### Step 1-3: Page Load
```javascript
// Browser loads face-api.js library
<script src="{{ asset('build/face-api.min.js') }}"></script>

// Initialize models
async function initFaceAPI() {
    await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
    await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
    await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
}
```

### Phase 2: Camera Access

#### Step 4-8: Permission & Stream
```javascript
// Request camera access
async function startCamera() {
    const stream = await navigator.mediaDevices.getUserMedia({
        video: {
            width: { ideal: 320 },
            height: { ideal: 240 },
            facingMode: 'user'
        }
    });
    
    const video = document.getElementById('face-video');
    video.srcObject = stream;
    
    return stream;
}
```

### Phase 3: Face Detection

#### Step 9-12: Real-time Detection
```javascript
// Continuous face detection loop
async function detectFace(video) {
    const options = new faceapi.TinyFaceDetectorOptions({
        inputSize: 320,
        scoreThreshold: 0.5
    });
    
    const detection = await faceapi
        .detectSingleFace(video, options)
        .withFaceLandmarks();
    
    if (detection) {
        // Face found - check position
        checkFacePosition(detection);
    }
}
```

#### Step 13-14: Landmark Extraction

The system extracts **68 facial landmarks**:

```
Face Landmark Points (68 total):
─────────────────────────────────
Jaw Line:        Points 0-16   (17 points)
Right Eyebrow:   Points 17-21  (5 points)
Left Eyebrow:    Points 22-26  (5 points)
Nose Bridge:     Points 27-30  (4 points)
Nose Tip:        Points 31-35  (5 points)
Right Eye:       Points 36-41  (6 points)
Left Eye:        Points 42-47  (6 points)
Outer Mouth:     Points 48-59  (12 points)
Inner Mouth:     Points 60-67  (8 points)
```

```javascript
// Landmark structure
const landmarks = detection.landmarks;
const positions = landmarks.positions;
// Returns: [{x: 123.45, y: 234.56}, {x: 125.67, y: 236.78}, ...]
```

### Phase 4: Descriptor Generation

#### Step 15-16: Face Embedding

```javascript
// Generate 128-dimensional face descriptor
async function generateDescriptor(video) {
    const detection = await faceapi
        .detectSingleFace(video)
        .withFaceLandmarks()
        .withFaceDescriptor();
    
    if (detection) {
        const descriptor = detection.descriptor;
        // Float32Array of 128 values
        // Example: [0.123, -0.456, 0.789, ...]
        
        return Array.from(descriptor);
    }
}
```

**Face Descriptor Explained:**
- **Type**: Float32Array
- **Length**: 128 floating-point numbers
- **Purpose**: Unique numerical representation of face
- **Size**: 512 bytes (128 × 4 bytes)

### Phase 5: Form Submission

#### Step 17-19: Data Preparation

```javascript
// Store descriptor in hidden input
const descriptorInput = document.createElement('input');
descriptorInput.type = 'hidden';
descriptorInput.name = 'face_descriptor';
descriptorInput.value = JSON.stringify(descriptor);
form.appendChild(descriptorInput);

// Submit to server
form.submit();
```

### Phase 6: Server Processing

#### Step 20-25: Backend Handling

```php
// PublicController.php
#[Route('/register', name: 'app_register', methods: ['POST'])]
public function register(Request $request, EntityManagerInterface $em): Response
{
    // 20. Validate form data
    $firstName = $request->request->get('first_name');
    $email = $request->request->get('email');
    // ... validation logic
    
    // 21. Hash password
    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
    
    // 22. Create User Entity
    $user = new User();
    $user->setFirstName($firstName);
    $user->setEmail($email);
    $user->setPassword($hashedPassword);
    
    // 23. Set face descriptor (JSON)
    $faceDescriptor = $request->request->get('face_descriptor');
    if ($faceDescriptor) {
        $user->setFaceDescriptor(json_decode($faceDescriptor, true));
    }
    
    // 24. Persist to database
    $em->persist($user);
    $em->flush();
    
    // 26. Redirect to login
    return $this->redirectToRoute('app_login');
}
```

---

## Data Structures

### Face Descriptor Format

```json
{
    "face_descriptor": [
        0.123456789,
        -0.234567891,
        0.345678912,
        -0.456789012,
        // ... 124 more values
    ]
}
```

### Database Storage

```sql
-- users table
ALTER TABLE user ADD COLUMN face_descriptor JSON DEFAULT NULL;

-- Example stored value:
{
    "descriptor": [0.123, -0.456, 0.789, ...],
    "created_at": "2026-02-21 12:00:00",
    "model_version": "1.0"
}
```

---

## Face Recognition Flow (Future Login)

```
┌─────────┐     ┌──────────┐     ┌─────────────┐     ┌──────────┐
│  User   │     │ Browser  │     │ face-api.js │     │  Server  │
└────┬────┘     └────┬─────┘     └──────┬──────┘     └────┬─────┘
     │               │                   │                 │
     │  1. Click "Login with Face ID"    │                 │
     │──────────────>│                   │                 │
     │               │                   │                 │
     │               │ 2. Start Camera   │                 │
     │               │──────────────────>│                 │
     │               │                   │                 │
     │               │ 3. Capture Face   │                 │
     │               │<──────────────────│                 │
     │               │                   │                 │
     │               │ 4. Generate Descriptor (128 floats) │
     │               │<──────────────────│                 │
     │               │                   │                 │
     │               │ 5. POST /login-face                │
     │               │ {descriptor: [...]}                │
     │               │────────────────────────────────────>│
     │               │                   │                 │
     │               │                   │    6. Fetch stored descriptor
     │               │                   │    from database
     │               │                   │                 │
     │               │                   │    7. Compare descriptors
     │               │                   │    using Euclidean distance
     │               │                   │                 │
     │               │ 8. Match Result   │                 │
     │               │  (threshold: 0.6) │                 │
     │               │<────────────────────────────────────│
     │               │                   │                 │
     │  9. Login Success / Failed        │                 │
     │<──────────────│                   │                 │
     │               │                   │                 │
```

### Comparison Algorithm

```javascript
// Euclidean distance between two descriptors
function compareDescriptors(d1, d2) {
    let sum = 0;
    for (let i = 0; i < 128; i++) {
        const diff = d1[i] - d2[i];
        sum += diff * diff;
    }
    const distance = Math.sqrt(sum);
    
    // Threshold: 0.6 (lower = same person)
    return distance < 0.6;
}
```

---

## Error Handling

### Common Issues & Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| Camera permission denied | User blocked camera access | Show instructions to enable in browser settings |
| No face detected | Poor lighting, face too far/close | Display positioning guide, adjust lighting |
| Multiple faces detected | Another person in frame | Show warning, ask to be alone |
| Face descriptor empty | Detection failed | Retry capture, check camera quality |
| Server validation error | Invalid descriptor format | Ensure JSON encoding is correct |

---

## Security Considerations

### Data Protection

1. **Local Processing**: Face descriptor generated entirely in browser
2. **No Image Storage**: Only numerical descriptor stored, not actual face image
3. **Encrypted Transmission**: HTTPS required for all data transfer
4. **Hashed Storage**: Consider encrypting descriptor in database

### Privacy

1. **Explicit Consent**: User must click "Enable Face ID"
2. **Clear Explanation**: Inform what data is stored
3. **Deletion Option**: Allow users to remove face data
4. **GDPR Compliance**: Include in privacy policy

---

## Technical Requirements

### Client-Side

- Modern browser (Chrome 79+, Firefox 63+, Safari 14+, Edge 79+)
- WebRTC support
- Camera access permission
- WebGL support (for face-api.js)

### Server-Side

- PHP 8.1+
- Symfony 6.4+
- MariaDB 10.4+ (JSON column support)
- HTTPS enabled

### Libraries

- **face-api.js** v0.22.2
- **TinyFaceDetector** model
- **FaceLandmark68Net** model
- **FaceRecognitionNet** model

---

## Performance Metrics

| Operation | Typical Time |
|-----------|--------------|
| Model Loading | 1-3 seconds |
| Camera Init | 0.5-1 second |
| Face Detection | 50-100ms per frame |
| Descriptor Generation | 100-200ms |
| Form Submission | 200-500ms |
| **Total Registration** | 3-5 seconds |

---

## File Structure

```
project/
├── assets/
│   └── controllers/
│       └── faceid_controller.js      # Stimulus controller
├── public/
│   ├── models/
│   │   ├── tiny_face_detector_model-weights_manifest.json
│   │   ├── face_landmark_68_model-weights_manifest.json
│   │   └── face_recognition_model-weights_manifest.json
│   └── build/
│       └── face-api.min.js
├── src/
│   ├── Entity/
│   │   └── User.php                   # face_descriptor column
│   └── Controller/
│       └── PublicController.php       # Registration handler
└── templates/
    └── pages/auth/
        └── register.html.twig         # Face ID step (Step 4)
```

---

## Summary

The Face ID registration flow consists of:

1. **Client-side capture**: Browser accesses camera via MediaDevices API
2. **Face detection**: face-api.js detects face and extracts 68 landmarks
3. **Descriptor generation**: 128-float embedding uniquely identifies the face
4. **Secure transmission**: Descriptor sent via HTTPS POST to server
5. **Persistent storage**: JSON-encoded descriptor stored in database

This provides a secure, privacy-conscious biometric authentication option that doesn't store actual face images, only mathematical representations.

---

# Sprint Backlog - User Module

## Sprint Overview

| Field | Value |
|-------|-------|
| **Sprint Name** | User Management Sprint |
| **Duration** | 2 Weeks |
| **Start Date** | Sprint Start |
| **End Date** | Sprint End |
| **Team Capacity** | 40 Story Points |
| **Sprint Goal** | Implement Admin ban/unban functionality and Student CRUD operations |

---

## Sprint Backlog Items

### 📋 Epic 1: Admin User Management (Ban/Unban)

#### User Story 1.1: Admin Ban User

**As an** Administrator  
**I want to** ban a user  
**So that** I can prevent malicious or rule-breaking users from accessing the platform

| Field | Details |
|-------|---------|
| **Story ID** | US-1.1 |
| **Priority** | High |
| **Story Points** | 5 |
| **Epic** | Admin User Management |

##### Acceptance Criteria
- [ ] Given I am an admin, When I view the user list, Then I see a "Ban" button for each non-banned user
- [ ] Given I click "Ban" on a user, When I confirm the action, Then the user status changes to "Banned"
- [ ] Given a user is banned, When they try to login, Then they see a "Your account has been banned" message
- [ ] Given a user is banned, When I view the user list, Then the user shows a "Banned" badge
- [ ] Given I ban a user, When the action completes, Then a ban timestamp is recorded

##### Tasks
| # | Task | Assignee | Estimate | Status |
|---|------|----------|----------|--------|
| 1.1.1 | Add `banned_at` column to User entity (already exists) | Backend | 1h | Done |
| 1.1.2 | Create AdminBanController with ban action | Backend | 2h | Pending |
| 1.1.3 | Add ban route `/admin/users/{id}/ban` | Backend | 0.5h | Pending |
| 1.1.4 | Implement login check for banned users | Backend | 1h | Pending |
| 1.1.5 | Add "Ban" button to admin user list template | Frontend | 1h | Pending |
| 1.1.6 | Create confirmation modal for ban action | Frontend | 1h | Pending |
| 1.1.7 | Add "Banned" badge styling | Frontend | 0.5h | Pending |
| 1.1.8 | Write unit tests for ban functionality | QA | 2h | Pending |

---

#### User Story 1.2: Admin Unban User

**As an** Administrator  
**I want to** unban a previously banned user  
**So that** I can restore access to users who have served their penalty

| Field | Details |
|-------|---------|
| **Story ID** | US-1.2 |
| **Priority** | High |
| **Story Points** | 3 |
| **Epic** | Admin User Management |

##### Acceptance Criteria
- [ ] Given I am an admin, When I view the user list, Then I see an "Unban" button for each banned user
- [ ] Given I click "Unban" on a banned user, When I confirm the action, Then the user status changes to "Active"
- [ ] Given a user is unbanned, When they try to login, Then they can successfully authenticate
- [ ] Given I unban a user, When the action completes, Then `banned_at` is set to NULL

##### Tasks
| # | Task | Assignee | Estimate | Status |
|---|------|----------|----------|--------|
| 1.2.1 | Add unban action to AdminBanController | Backend | 1h | Pending |
| 1.2.2 | Add unban route `/admin/users/{id}/unban` | Backend | 0.5h | Pending |
| 1.2.3 | Add "Unban" button for banned users in template | Frontend | 1h | Pending |
| 1.2.4 | Create confirmation modal for unban action | Frontend | 0.5h | Pending |
| 1.2.5 | Write unit tests for unban functionality | QA | 1h | Pending |

---

#### User Story 1.3: View Banned Users List

**As an** Administrator  
**I want to** filter and view all banned users  
**So that** I can easily manage banned accounts

| Field | Details |
|-------|---------|
| **Story ID** | US-1.3 |
| **Priority** | Medium |
| **Story Points** | 2 |
| **Epic** | Admin User Management |

##### Acceptance Criteria
- [ ] Given I am on the admin users page, When I click "Show Banned Only", Then only banned users are displayed
- [ ] Given I view banned users, When I see the list, Then each user shows ban date
- [ ] Given I am filtering banned users, When I click "Show All", Then all users are displayed

##### Tasks
| # | Task | Assignee | Estimate | Status |
|---|------|----------|----------|--------|
| 1.3.1 | Add filter parameter to users query | Backend | 1h | Pending |
| 1.3.2 | Add filter buttons to admin template | Frontend | 1h | Pending |
| 1.3.3 | Style active filter state | Frontend | 0.5h | Pending |

---

### 📋 Epic 2: Student CRUD Operations

#### User Story 2.1: Create Student (Register)

**As a** New User  
**I want to** register as a student  
**So that** I can access the StudyFlow platform

| Field | Details |
|-------|---------|
| **Story ID** | US-2.1 |
| **Priority** | Critical |
| **Story Points** | 8 |
| **Epic** | Student CRUD |

##### Acceptance Criteria
- [ ] Given I am on the registration page, When I fill in all required fields, Then I can submit the form
- [ ] Given I submit valid registration data, When the form processes, Then a new student account is created
- [ ] Given I register successfully, When creation completes, Then I am redirected to login page
- [ ] Given I submit an existing email, When the form validates, Then I see "Email already exists" error
- [ ] Given I submit invalid data, When validation runs, Then appropriate error messages display

##### Tasks
| # | Task | Assignee | Estimate | Status |
|---|------|----------|----------|--------|
| 2.1.1 | Create registration form (4-step wizard) | Frontend | 4h | Done |
| 2.1.2 | Implement PublicController register action | Backend | 3h | Done |
| 2.1.3 | Add form validation (client & server) | Both | 2h | Done |
| 2.1.4 | Implement password hashing | Backend | 1h | Done |
| 2.1.5 | Create User entity with all fields | Backend | 2h | Done |
| 2.1.6 | Implement Face ID capture (Step 4) | Frontend | 4h | Done |
| 2.1.7 | Write registration tests | QA | 2h | Pending |

---

#### User Story 2.2: Read Student Profile (View)

**As a** Student  
**I want to** view my profile information  
**So that** I can see my account details

| Field | Details |
|-------|---------|
| **Story ID** | US-2.2 |
| **Priority** | Medium |
| **Story Points** | 3 |
| **Epic** | Student CRUD |

##### Acceptance Criteria
- [ ] Given I am logged in, When I navigate to /settings, Then I see my profile information
- [ ] Given I view my profile, When the page loads, Then I see my name, email, university, and avatar
- [ ] Given I view my profile, When I look at the stats, Then I see my projects, courses, and decks count

##### Tasks
| # | Task | Assignee | Estimate | Status |
|---|------|----------|----------|--------|
| 2.2.1 | Create settings page template | Frontend | 3h | Done |
| 2.2.2 | Implement SettingsController index action | Backend | 2h | Done |
| 2.2.3 | Add profile stats calculation | Backend | 1h | Done |
| 2.2.4 | Style profile card with gradients | Frontend | 2h | Done |

---

#### User Story 2.3: Update Student Profile

**As a** Student  
**I want to** update my profile information  
**So that** I can keep my details current

| Field | Details |
|-------|---------|
| **Story ID** | US-2.3 |
| **Priority** | High |
| **Story Points** | 5 |
| **Epic** | Student CRUD |

##### Acceptance Criteria
- [ ] Given I am on settings page, When I modify my information, Then I can save changes
- [ ] Given I update my email to an existing one, When I submit, Then I see "Email already taken" error
- [ ] Given I update my password, When I enter wrong current password, Then I see "Incorrect password" error
- [ ] Given I upload a profile picture, When I save, Then the image is stored and displayed
- [ ] Given I save valid changes, When the update completes, Then I see "Profile updated" success message

##### Tasks
| # | Task | Assignee | Estimate | Status |
|---|------|----------|----------|--------|
| 2.3.1 | Create profile edit form | Frontend | 2h | Done |
| 2.3.2 | Implement update action in SettingsController | Backend | 3h | Done |
| 2.3.3 | Add file upload handling for profile pic | Backend | 2h | Done |
| 2.3.4 | Implement password change logic | Backend | 2h | Done |
| 2.3.5 | Add accessibility settings save | Backend | 2h | Done |
| 2.3.6 | Write profile update tests | QA | 2h | Pending |

---

#### User Story 2.4: Delete Student Account

**As a** Student  
**I want to** delete my account  
**So that** I can remove my data from the platform

| Field | Details |
|-------|---------|
| **Story ID** | US-2.4 |
| **Priority** | Low |
| **Story Points** | 5 |
| **Epic** | Student CRUD |

##### Acceptance Criteria
- [ ] Given I am on settings page, When I click "Delete Account", Then a confirmation modal appears
- [ ] Given I confirm deletion, When I enter my password, Then my account is permanently deleted
- [ ] Given I delete my account, When deletion completes, Then all my data is removed
- [ ] Given I delete my account, When I try to login, Then my credentials no longer work
- [ ] Given I enter wrong password in delete modal, When I submit, Then deletion is cancelled

##### Tasks
| # | Task | Assignee | Estimate | Status |
|---|------|----------|----------|--------|
| 2.4.1 | Create delete confirmation modal | Frontend | 2h | Done |
| 2.4.2 | Implement delete action in SettingsController | Backend | 3h | Done |
| 2.4.3 | Add cascade delete for related entities | Backend | 2h | Done |
| 2.4.4 | Delete profile picture file from server | Backend | 1h | Done |
| 2.4.5 | Invalidate session after deletion | Backend | 1h | Done |
| 2.4.6 | Write account deletion tests | QA | 2h | Pending |

---

#### User Story 2.5: List Students (Admin View)

**As an** Administrator  
**I want to** view a list of all students  
**So that** I can manage and monitor user accounts

| Field | Details |
|-------|---------|
| **Story ID** | US-2.5 |
| **Priority** | High |
| **Story Points** | 5 |
| **Epic** | Student CRUD |

##### Acceptance Criteria
- [ ] Given I am an admin, When I navigate to /admin/users, Then I see a list of all students
- [ ] Given I view the user list, When I look at each row, Then I see name, email, role, and status
- [ ] Given I view the user list, When I want to find a user, Then I can search by name or email
- [ ] Given I view the user list, When I click a user, Then I can see their details

##### Tasks
| # | Task | Assignee | Estimate | Status |
|---|------|----------|----------|--------|
| 2.5.1 | Create admin users page template | Frontend | 3h | Pending |
| 2.5.2 | Implement AdminUserController with list action | Backend | 2h | Pending |
| 2.5.3 | Add search/filter functionality | Backend | 2h | Pending |
| 2.5.4 | Add pagination to user list | Backend | 2h | Pending |
| 2.5.5 | Style user table with status badges | Frontend | 1h | Pending |

---

## Sprint Velocity Calculation

| Epic | Total Story Points |
|------|-------------------|
| Admin Ban/Unban | 10 |
| Student CRUD | 26 |
| **Total** | **36** |

### Capacity Check
- **Team Capacity**: 40 Story Points
- **Planned Work**: 36 Story Points
- **Buffer**: 4 Story Points (10%)

---

## Definition of Done

- [ ] Code is peer-reviewed
- [ ] Unit tests pass with >80% coverage
- [ ] Integration tests pass
- [ ] No critical bugs remaining
- [ ] Documentation updated
- [ ] UX approved (if applicable)
- [ ] Deployed to staging environment
- [ ] Product Owner sign-off

---

## Sprint Timeline

| Day | Activity |
|-----|----------|
| Day 1 | Sprint Planning, Story breakdown |
| Day 2-3 | Development - Admin Ban/Unban |
| Day 4-5 | Development - Student CRUD (Create/Read) |
| Day 6-7 | Development - Student CRUD (Update/Delete) |
| Day 8 | Testing & Bug Fixes |
| Day 9 | Code Review & Refinement |
| Day 10 | Sprint Review & Retrospective |

---

## Risks & Mitigation

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Face API model loading issues | Medium | High | Test on multiple browsers, add fallback |
| Cascade delete performance | Low | Medium | Use database transactions, test with large data |
| Profile pic upload failures | Medium | Low | Add file validation, size limits, error handling |
| Ban/unban state inconsistency | Low | High | Use database transactions, add logging |

---

## Dependencies

- User entity must have `banned_at` column (✅ Already exists)
- Admin authentication must be working (✅ Already implemented)
- File upload directory must be writable (✅ Configured)
- Face-api.js models must be loaded (✅ Available)

---

## Notes

1. All user stories follow INVEST criteria (Independent, Negotiable, Valuable, Estimable, Small, Testable)
2. Story points based on Fibonacci scale (1, 2, 3, 5, 8, 13)
3. Tasks include both frontend and backend work
4. Testing is included in each user story
5. Accessibility features already implemented in settings page
