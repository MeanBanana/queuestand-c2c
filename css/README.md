# CSS Architecture Documentation

---

## File Structure

### 1. **styles.css** (Base/Layout)
**Purpose:** Core layout, typography, forms, and page-specific styles
**Contains:**
- Reset styles
- Base typography
- Header/footer
- Navigation
- Main layout
- Forms
- Grid layouts
- About page styles
- Browse/search functionality
- Responsive breakpoints

**When to use:** Always include this as the base stylesheet

---

### 2. **components.css** (Reusable Components)
**Purpose:** Standalone, reusable UI components
**Contains:**
- Badges (status indicators)
- Buttons (all variants: primary, accept, decline, cancel, etc.)
- Toast notifications
- Modals
- Cards
- Notification badges
- Review items

---

### 3. **dashboard.css** (Dashboard-Specific)
**Purpose:** Dashboard and user-facing job management styles
**Contains:**
- Dashboard header
- Stats cards
- Dashboard grid layouts
- Job cards
- Applicant sections
- Stander information
- Notification items
- Review displays

---

### 4. **admin.css** (Admin-Specific)
**Purpose:** Admin portal specific styles
**Contains:**
- Admin navigation
- Admin tables
- Admin sections
- Filter bars
- Search bars
- Action groups
- Status selects
- Admin utility classes

---

## Import Order

Always import CSS files in this order for proper cascade:

```html
<link rel="stylesheet" href="../css/styles.css" />
<link rel="stylesheet" href="../css/dashboard.css" />  <!-- If needed -->
<link rel="stylesheet" href="../css/components.css" />
<link rel="stylesheet" href="../css/admin.css" />      <!-- If admin page -->
```

---

## Page-Specific Import Guide

### Public Pages (index.php, about.php, login.php, register.php)
```html
<link rel="stylesheet" href="css/styles.css" />
<link rel="stylesheet" href="css/components.css" />
```

### User Dashboard Pages (dashboard.php, browse-jobs.php, profile.php)
```html
<link rel="stylesheet" href="css/styles.css" />
<link rel="stylesheet" href="css/dashboard.css" />
<link rel="stylesheet" href="css/components.css" />
```

### Admin Pages (admin-dashboard.php, admin-users.php, admin-jobs.php)
```html
<link rel="stylesheet" href="../css/styles.css" />
<link rel="stylesheet" href="../css/dashboard.css" />
<link rel="stylesheet" href="../css/components.css" />
<link rel="stylesheet" href="../css/admin.css" />
```

---

## Benefits of This Architecture

### 1. **Separation of Concerns**
- Each file has a single, clear responsibility
- Easier to locate and modify specific styles
- Reduces cognitive load when working on specific features

### 2. **Better Maintainability**
- Changes to components don't affect layout
- Changes to admin styles don't affect user pages
- Easier to debug CSS issues

### 3. **Improved Performance**
- Pages only load the CSS they need
- Smaller file sizes per page
- Better browser caching (unchanged files stay cached)

### 4. **Easier Debugging**
- Know exactly which file contains the styles you need
- Browser DevTools show specific file names
- Reduced style conflicts

### 5. **Scalability**
- Easy to add new component files
- Can create page-specific CSS files as needed
- Team members can work on different files without conflicts

### 6. **Reusability**
- Components can be used across different pages
- Consistent styling through shared component classes
- DRY (Don't Repeat Yourself) principle

---

## Adding New Styles

### For a New Component:
Add to **components.css**

### For Dashboard Features:
Add to **dashboard.css**

### For Admin Features:
Add to **admin.css**

### For New Pages:
Consider creating a new CSS file (e.g., `profile.css`, `checkout.css`)

---

## Migration Notes

All inline styles have been removed from admin pages and moved to appropriate CSS files. The admin pages now follow proper programming paradigms with:

1. **Separation of Concerns** - CSS separated from HTML
2. **DRY Principle** - Reusable classes instead of repeated inline styles
3. **Maintainability** - Easy to find and update styles
4. **Consistency** - Shared component styles across pages