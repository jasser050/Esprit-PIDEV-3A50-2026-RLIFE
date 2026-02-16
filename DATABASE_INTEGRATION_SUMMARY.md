# Database Integration Summary - Project Branch Merge

## Overview
Successfully integrated database schema changes from the `project` branch into the `integration` branch.

## New Tables Added

### 1. `comment` Table
**Purpose:** Store comments on assignments
- **Fields:**
  - `id` (Primary Key)
  - `assignment_id` (Foreign Key → assignment.id)
  - `user_id` (Foreign Key → user.id)
  - `content` (TEXT - comment text)
  - `created_at` (DATETIME)
  - `updated_at` (DATETIME, nullable)

**Relationships:**
- Many-to-One with Assignment
- Many-to-One with User

**Use Case:** Users can add comments to assignments for collaboration and discussion.

---

### 2. `project_share` Table
**Purpose:** Manage project sharing between users
- **Fields:**
  - `id` (Primary Key)
  - `project_id` (Foreign Key → project.id)
  - `shared_with_user_id` (Foreign Key → user.id)
  - `shared_by_user_id` (Foreign Key → user.id)
  - `role` (VARCHAR(20) - 'viewer' or 'editor')
  - `created_at` (DATETIME)

**Relationships:**
- Many-to-One with Project
- Many-to-One with User (shared with)
- Many-to-One with User (shared by)

**Use Case:** Users can share projects with other users with specific permissions (viewer or editor).

---

### 3. `assignment_collaborator` Table
**Purpose:** Manage assignment collaborators
- **Fields:**
  - `id` (Primary Key)
  - `assignment_id` (Foreign Key → assignment.id)
  - `user_id` (Foreign Key → user.id)
  - `assigned_by_user_id` (Foreign Key → user.id)
  - `created_at` (DATETIME)

**Relationships:**
- Many-to-One with Assignment
- Many-to-One with User (collaborator)
- Many-to-One with User (assigned by)

**Use Case:** Multiple users can collaborate on the same assignment.

---

## Updated Entities

### Assignment Entity
**Changes:**
- Added validation constraints (Symfony Validator)
- Added relationships to Comment and AssignmentCollaborator
- Enhanced field validation:
  - `titre`: Required, 3-255 characters
  - `description`: Required, minimum 10 characters
  - `dateDebut`: Required
  - `dateFin`: Must be >= dateDebut
  - `priorite`: Required, choices ['Haute', 'Moyenne', 'Basse']
  - `statut`: Required, choices ['À faire', 'En cours', 'Terminé', 'Annulé']

**New Methods:**
- `getComments()`: Get all comments on assignment
- `addComment()`: Add a comment
- `removeComment()`: Remove a comment
- `getCollaborators()`: Get all collaborators
- `addCollaborator()`: Add a collaborator
- `removeCollaborator()`: Remove a collaborator

---

### Project Entity
**Changes:**
- Added validation constraints (Symfony Validator)
- Added relationship to ProjectShare
- Enhanced field validation:
  - `titre`: Required, 3-255 characters
  - `description`: Required, minimum 10 characters
  - `dateDebut`: Required
  - `dateFin`: Required, must be >= dateDebut
  - `statut`: Required, choices ['En attente', 'En cours', 'En pause', 'Terminé', 'Annulé']

**New Methods:**
- `getShares()`: Get all project shares
- `addShare()`: Add a project share
- `removeShare()`: Remove a project share

---

## New Entity Classes

### Comment.php
**Location:** `src/Entity/Comment.php`
**Repository:** `src/Repository/CommentRepository.php`

Features:
- Lifecycle callbacks (created_at auto-set)
- Pre-update callback for updated_at
- Relationships to Assignment and User

---

### ProjectShare.php
**Location:** `src/Entity/ProjectShare.php`
**Repository:** `src/Repository/ProjectShareRepository.php`

Features:
- Default role: 'viewer'
- Supports roles: 'viewer', 'editor'
- Tracks who shared and when

---

### AssignmentCollaborator.php
**Location:** `src/Entity/AssignmentCollaborator.php`
**Repository:** `src/Repository/AssignmentCollaboratorRepository.php`

Features:
- Tracks assignment collaborations
- Records who assigned the collaborator
- Timestamps for audit trail

---

## Database Constraints

### Foreign Keys (All with CASCADE DELETE)
1. **comment.assignment_id** → assignment.id
2. **comment.user_id** → user.id
3. **project_share.project_id** → project.id
4. **project_share.shared_with_user_id** → user.id
5. **project_share.shared_by_user_id** → user.id
6. **assignment_collaborator.assignment_id** → assignment.id
7. **assignment_collaborator.user_id** → user.id
8. **assignment_collaborator.assigned_by_user_id** → user.id

### Indexes
All foreign keys are indexed for performance optimization.

---

## Integration Checklist

✅ **Entities Created:**
- Comment entity
- ProjectShare entity
- AssignmentCollaborator entity

✅ **Repositories Created:**
- CommentRepository
- ProjectShareRepository
- AssignmentCollaboratorRepository

✅ **Entities Updated:**
- Assignment (validation + relationships)
- Project (validation + relationships)

✅ **Database Tables Created:**
- comment table
- project_share table
- assignment_collaborator table

✅ **Foreign Keys Added:**
- All CASCADE DELETE relationships established

✅ **Entity Mapping Verified:**
- All 23 entities properly mapped
- Doctrine recognizes new entities

---

## Usage Examples

### Adding a Comment to Assignment
```php
$comment = new Comment();
$comment->setAssignment($assignment);
$comment->setUser($user);
$comment->setContent('This is a comment');
$entityManager->persist($comment);
$entityManager->flush();
```

### Sharing a Project
```php
$share = new ProjectShare();
$share->setProject($project);
$share->setSharedWithUser($otherUser);
$share->setSharedByUser($currentUser);
$share->setRole('editor');
$entityManager->persist($share);
$entityManager->flush();
```

### Adding Collaborator to Assignment
```php
$collaborator = new AssignmentCollaborator();
$collaborator->setAssignment($assignment);
$collaborator->setUser($collaboratorUser);
$collaborator->setAssignedByUser($currentUser);
$entityManager->persist($collaborator);
$entityManager->flush();
```

---

## Next Steps

1. **Create Controllers** for managing comments, shares, and collaborators
2. **Create Forms** for adding comments and managing shares
3. **Update Templates** to display comments and collaborator lists
4. **Add API Endpoints** if building a REST API
5. **Add Permissions** to control who can comment/share/collaborate

---

## Technical Notes

- **Framework:** Symfony 6.4
- **ORM:** Doctrine
- **Database:** MySQL/MariaDB
- **Validation:** Symfony Validator component
- **Character Set:** utf8mb4 with unicode collation
- **Engine:** InnoDB (supports foreign keys)

---

## Files Modified/Created

**New Files:**
- `src/Entity/Comment.php`
- `src/Entity/ProjectShare.php`
- `src/Entity/AssignmentCollaborator.php`
- `src/Repository/CommentRepository.php`
- `src/Repository/ProjectShareRepository.php`
- `src/Repository/AssignmentCollaboratorRepository.php`

**Modified Files:**
- `src/Entity/Assignment.php` (validation + relationships)
- `src/Entity/Project.php` (validation + relationships)

---

*Integration completed successfully on: February 12, 2025*
