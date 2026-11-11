# Database schema

| Table | Purpose | Key relationships |
|---|---|---|
| `branches` | Branch / unit master list | Parent of maintenance, licences and assessments |
| `categories` | Categorized option lists | Used by maintenance, licences and RCSA templates |
| `officers` | Responsible officers | Referenced by maintenance issues |
| `dashboard_options` | Status and priority choices | Keyed by `group_key` and `option_value` |
| `maintenance_issues` | Operational and maintenance tasks | Branch, category, optional officer |
| `licences` | Statutory licences and permits | Branch and category |
| `rcsa_templates` | Master audit checklist questions | Category; one question can be assessed by many branches |
| `rcsa_assessments` | Branch-specific response to each checklist question | Branch and RCSA template; unique branch/template pair |

## RCSA design

The template table stores each checklist question once. The assessment table stores the result for a specific branch. This separates master checklist content from branch response history and avoids duplicating the question text in every branch assessment.

The initial source contains 138 questions. Five branches therefore receive 5 × 138 = 690 assessment rows. Initial status is `N/E` (Not Evaluated), not an invented compliance result.

## Adding new options

The database is now the source for branches, categories, officers, statuses and priorities. For example, an authorized administrator can add a branch using Tinker:

```bash
php artisan tinker
```

```php
App\Models\Branch::create(['name' => 'New Branch Name', 'is_active' => true]);
```

Add an officer:

```php
App\Models\Officer::create(['name' => 'Officer Name', 'department' => 'Operations', 'is_active' => true]);
```

Add a maintenance category:

```php
App\Models\Category::create(['module' => 'maintenance', 'name' => 'New Category', 'is_active' => true]);
```

Add a priority option:

```php
App\Models\DashboardOption::create(['group_key' => 'priority', 'option_value' => 'Urgent', 'label' => 'Urgent', 'sort_order' => 0, 'is_active' => true]);
```

The dashboard fetches active values dynamically from `/dashboard-options`.
