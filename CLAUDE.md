<laravel-boost-guidelines>

## IMPORTANT: Always Read This File
**Before starting any task, you MUST read this CLAUDE.md file in its entirety.** This file contains critical project-specific guidelines, conventions, and context that override default behavior. Never assume you know the project structure or conventions without reading this file first.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.8
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/vue3 (INERTIA) - v2
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== wayfinder/core rules ===

## Laravel Wayfinder

Wayfinder generates TypeScript functions and types for Laravel controllers and routes which you can import into your client side code. It provides type safety and automatic synchronization between backend routes and frontend code.

### Development Guidelines
- Always use `search-docs` to check wayfinder correct usage before implementing any features.
- Always Prefer named imports for tree-shaking (e.g., `import { show } from '@/actions/...'`)
- Avoid default controller imports (prevents tree-shaking)
- Run `php artisan wayfinder:generate` after route changes if Vite plugin isn't installed

### Feature Overview
- Form Support: Use `.form()` with `--with-form` flag for HTML form attributes — `<form {...store.form()}>` → `action="/posts" method="post"`
- HTTP Methods: Call `.get()`, `.post()`, `.patch()`, `.put()`, `.delete()` for specific methods — `show.head(1)` → `{ url: "/posts/1", method: "head" }`
- Invokable Controllers: Import and invoke directly as functions. For example, `import StorePost from '@/actions/.../StorePostController'; StorePost()`
- Named Routes: Import from `@/routes/` for non-controller routes. For example, `import { show } from '@/routes/post'; show(1)` for route name `post.show`
- Parameter Binding: Detects route keys (e.g., `{post:slug}`) and accepts matching object properties — `show("my-post")` or `show({ slug: "my-post" })`
- Query Merging: Use `mergeQuery` to merge with `window.location.search`, set values to `null` to remove — `show(1, { mergeQuery: { page: 2, sort: null } })`
- Query Parameters: Pass `{ query: {...} }` in options to append params — `show(1, { query: { page: 1 } })` → `"/posts/1?page=1"`
- Route Objects: Functions return `{ url, method }` shaped objects — `show(1)` → `{ url: "/posts/1", method: "get" }`
- URL Extraction: Use `.url()` to get URL string — `show.url(1)` → `"/posts/1"`

### Example Usage

<code-snippet name="Wayfinder Basic Usage" lang="typescript">
    // Import controller methods (tree-shakable)
    import { show, store, update } from '@/actions/App/Http/Controllers/PostController'

    // Get route object with URL and method...
    show(1) // { url: "/posts/1", method: "get" }

    // Get just the URL...
    show.url(1) // "/posts/1"

    // Use specific HTTP methods...
    show.get(1) // { url: "/posts/1", method: "get" }
    show.head(1) // { url: "/posts/1", method: "head" }

    // Import named routes...
    import { show as postShow } from '@/routes/post' // For route name 'post.show'
    postShow(1) // { url: "/posts/1", method: "get" }
</code-snippet>


### Wayfinder + Inertia
If your application uses the `<Form>` component from Inertia, you can use Wayfinder to generate form action and method automatically.
<code-snippet name="Wayfinder Form Component (Vue)" lang="vue">

<Form v-bind="store.form()"><input name="title" /></Form>

</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== inertia-vue/core rules ===

## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="vue">

    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>

</code-snippet>


=== inertia-vue/v2/forms rules ===

## Inertia + Vue Forms

<code-snippet name="`<Form>` Component Example" lang="vue">

<Form
    action="/users"
    method="post"
    #default="{
        errors,
        hasErrors,
        processing,
        progress,
        wasSuccessful,
        recentlySuccessful,
        setError,
        clearErrors,
        resetAndClearErrors,
        defaults,
        isDirty,
        reset,
        submit,
  }"
>
    <input type="text" name="name" />

    <div v-if="errors.name">
        {{ errors.name }}
    </div>

    <button type="submit" :disabled="processing">
        {{ processing ? 'Creating...' : 'Create User' }}
    </button>

    <div v-if="wasSuccessful">User created successfully!</div>
</Form>

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>

# HRIS Project Context

## What This Application Is
A **Human Resource Information System (HRIS)** for Philippine-based companies. It handles the full employee lifecycle — from hiring to payroll — with specific support for Philippine statutory contributions (SSS, PhilHealth, PagIBIG) and labor law compliance (overtime, night differential, holiday pay).

## Modular Architecture
The app uses a module-based structure inside `app/Modules/`. Each module is self-contained with its own Models, Controllers, Form Requests, and routes.

| Module | Path | Responsibility |
|---|---|---|
| Core | `app/Modules/Core/` | Employees, departments, roles/permissions, dashboard |
| Timekeeping | `app/Modules/Timekeeping/` | Attendance, DTR, leaves, shifts, overtime, biometric sync |
| Payroll | `app/Modules/Payroll/` | Payroll periods, payslips, earnings/deductions, loans |
| License | `app/Modules/License/` | Multi-company licensing, module feature gating |

## Key Conventions
- New features belong inside the appropriate module's directory — never in `app/Http/` directly.
- Module routes are registered via each module's `routes/web.php`, not the root `routes/web.php`.
- All routes are protected by `auth` and `module.access:{module}` middleware.
- Use `module.access` middleware to gate features per license.

## Domain Knowledge (Philippine Payroll)
- Statutory deductions: SSS, PhilHealth, PagIBIG — calculated via `ContributionBracket` model.
- Overtime is calculated against `WorkPolicy` settings (OT rate, rest day rate, holiday rate).
- Night differential applies to hours worked between 10PM–6AM.
- Payroll period lifecycle: `draft → processing → finalized → closed`.
- Attendance splits the day: `clock_in → morning_out → afternoon_in → clock_out`.
- Biometric data syncs via the Alpeta terminal integration (`BiometricTerminal`, `AlpetaLog`).

## Important Models & Relationships
- `Employee` → belongs to `Company`, `Department`, `Position`; has many `AttendanceRecord`, `LeaveRequest`, `PayrollItem`, `EmployeeAllowance`, `Loan`
- `AttendanceRecord` → belongs to `Employee`; tracks `clock_in`, `morning_out`, `afternoon_in`, `clock_out`, `total_hours`, `status`
- `PayrollPeriod` → has many `PayrollItem` → each has `PayrollEarning[]` and `PayrollDeduction[]`
- `ShiftTemplate` → defines work hours and break times; assigned to employees via `EmployeeSchedule`
- `Role` ↔ `Permission` (many-to-many); `User` ↔ `Role` (many-to-many)

## Frontend Conventions
- Pages live in `resources/js/pages/` (Inertia components).
- Reusable UI components live in `resources/js/components/ui/` (Shadcn-style).
- Feature-specific components go in `resources/js/components/{ModuleName}/`.
- Always import controller actions from `@/actions/` (Wayfinder-generated) — never hardcode URLs.
- Named routes are imported from `@/routes/` for non-controller routes.
- All pages must support **dark mode** using Tailwind `dark:` variants.

## Table Pagination + Search Convention

**Every data table must include client-side pagination and search.** No table should be added without both.

### Script setup (Vue Composition API)

Replace `xxx`/`Xxx` with the entity name (e.g. `dept`/`Dept`). `sourceData` is either `props.items` or a reactive `ref`.

```typescript
import { computed, ref, watch } from 'vue'

const xxxSearch = ref('')
const xxxPage = ref(1)
const xxxPerPage = ref(5) // 5 for settings tables; 10 for report tables

const filteredXxx = computed(() => {
  const q = xxxSearch.value.toLowerCase()
  if (!q) { return sourceData }
  return sourceData.filter(item =>
    item.fieldA.toLowerCase().includes(q) || (item.fieldB ?? '').toLowerCase().includes(q),
  )
})

const xxxTotalPages = computed(() => Math.max(1, Math.ceil(filteredXxx.value.length / xxxPerPage.value)))
const paginatedXxx = computed(() => {
  const start = (xxxPage.value - 1) * xxxPerPage.value
  return filteredXxx.value.slice(start, start + xxxPerPage.value)
})

function changeXxxPerPage(): void { xxxPage.value = 1 }

watch(xxxSearch, () => { xxxPage.value = 1 })
// Also add: watch(() => sourceData, () => { xxxPage.value = 1 }) when data reloads (e.g. report tables)
```

### Template — Search input (right-aligned row above table)

```html
<div class="mb-3 flex justify-end">
  <input v-model="xxxSearch" type="text" placeholder="Search..."
    class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" />
</div>
```

### Template — `<tbody>` empty states + rows

```html
<tr v-if="sourceData.length === 0">
  <td colspan="N" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No X configured yet.</td>
</tr>
<tr v-else-if="filteredXxx.length === 0">
  <td colspan="N" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No X match your search.</td>
</tr>
<tr v-for="item in paginatedXxx" :key="item.id"><!-- cells --></tr>
```

### Template — Pagination footer (after `</table>`, inside the table wrapper `<div>`)

```html
<div v-if="sourceData.length > 0" class="flex items-center border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
  <div class="flex w-1/3 items-center gap-2">
    <span class="text-sm text-gray-600 dark:text-gray-400">Per page:</span>
    <select v-model="xxxPerPage" @change="changeXxxPerPage"
      class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
      <option v-for="n in [5, 10, 25, 50]" :key="n" :value="n">{{ n }}</option>
    </select>
  </div>
  <div class="flex w-1/3 justify-center gap-2">
    <button @click="xxxPage--" :disabled="xxxPage <= 1"
      class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
      v-html="'&laquo;'" />
    <button v-for="p in xxxTotalPages" :key="p" @click="xxxPage = p"
      :class="['rounded-lg px-3 py-1 text-sm font-medium transition-colors', xxxPage === p ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700']">
      {{ p }}
    </button>
    <button @click="xxxPage++" :disabled="xxxPage >= xxxTotalPages"
      class="rounded-lg border border-gray-300 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:text-gray-400 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
      v-html="'&raquo;'" />
  </div>
  <div class="flex w-1/3 justify-end">
    <p class="text-sm text-gray-600 dark:text-gray-400">
      {{ xxxSearch ? `${filteredXxx.length} of ${sourceData.length}` : sourceData.length }} items
    </p>
  </div>
</div>
```

### Per-page defaults

| Table type | Default | Options |
|---|---|---|
| Settings / config tables | `5` | `[5, 10, 25, 50]` |
| Report tables | `10` | `[10, 25, 50]` |

## Toast Notifications (Notivue)

This project uses **Notivue** for toast notifications. All CRUD operations must show a toast — never inline flash message divs.

### Configuration (already wired in `app.ts` and `Layout.vue`)
- Plugin: `createNotivue({ position: 'top-right', pauseOnHover: true, avoidDuplicates: true, enqueue: false })`
- Layout renders: `<Notivue>` → `<NotivueSwipe>` → `<Notification :theme="lightTheme" :icons="outlinedIcons">` + `<NotificationProgress>`
- CSS overrides in `app.css` make notifications full-width (`--nv-root-left: 0px; --nv-root-right: 0px; --nv-root-top: 0px; --nv-root-width: 100vw; --nv-min-width: 100%; --nv-radius: 0px`)

### Flash → Toast (automatic)
`Layout.vue` watches `page.props.flash` and fires `push.success()` / `push.error()` automatically. So backend flash messages appear as toasts with no extra frontend code needed.

### Manual toasts (for client-side feedback)
```typescript
import { push } from 'notivue'

push.success({ title: 'Saved', message: 'Department updated.' })
push.error({ title: 'Error', message: 'Something went wrong.' })
```

### Rules
- Do NOT add inline `<div v-if="flashSuccess">` blocks to pages — the Layout handles it.
- Do NOT import `usePage` just for flash — remove it if that's the only use.
- Include a `title` in every `push` call.

## Testing Notes
- Feature tests live in `tests/Feature/`, organized by module (e.g., `tests/Feature/Timekeeping/`).
- Use model factories and existing factory states when setting up test data.
- Payroll and attendance tests require careful date/time setup — use `Carbon::setTestNow()` where needed.

## Reference
- Full system architecture: [docs/system-architecture.md](docs/system-architecture.md)
