# Pet Metaverse Map Modification Plan

## Summary of Changes Needed

### Current Behavior:
1. The metaverse page shows ALL pets from connected users (users who have project shares)
2. The map (iframe) displays hardcoded pets with static data
3. Clicking a pet shows name, type, level, stats but NOT the owner name

### Required Changes:
1. Map should show ONLY pets of users that the current user has shared projects with
2. When clicking a pet on the map, show the pet's name AND the owner's name

---

## Files to Modify:

### 1. `src/Controller/PetController.php`
- Already filters community to connected users
- No changes needed if the logic already filters correctly

### 2. `templates/pet/metaverse.html.twig`
- Pass community data to the map iframe via URL parameters or postMessage
- Currently loads: `src="{{ asset('assets/metaverse/petverse_map.html') }}"`
- Need to encode pet data and pass to the map

### 3. `public/assets/metaverse/petverse_map.html`
- Read pet data from URL parameters or window.parent data
- Modify the `PET_DATA` and `petBuilders` to use dynamic data
- Update `openPetPanel()` to show owner name
- Update tooltip to show owner name

---

## Implementation Steps:

### Step 1: Modify the template to pass pet data to map
- Encode community data as JSON
- Pass to iframe via URL query string or postMessage

### Step 2: Modify the map to accept and use dynamic data
- Read data from URL parameters on load
- Dynamically create PET_DATA and petBuilders
- Update panel and tooltip to show owner name

### Step 3: Test the implementation
- Verify only shared project pets appear on map
- Verify clicking shows pet name and owner name

---

## Data Structure to Pass:
```javascript
{
  "pets": [
    {
      "id": "user_123",
      "petType": "cat",
      "petName": "Whiskers",
      "ownerName": "John Doe",
      "position": { "x": -5, "z": 3 }
    }
  ]
}
```

---

## Note:
The community data already contains the filtered list of users with shared projects, so no changes needed to the controller logic - just need to pass this data to the map properly.

