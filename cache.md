# Caching in Laravel for This Project

## What caching is

Caching is the practice of storing the result of expensive work somewhere fast so the application does not need to do the same work again on every request.

In a Laravel app, that "expensive work" is usually one of these:

- Repeating the same database query many times
- Building the same array payload for dropdowns and forms
- Calculating dashboard metrics repeatedly
- Loading configuration-like data that rarely changes

Without caching, every request must go back to the database, build the response again, and send it back. That works, but it becomes slower and wastes resources when the same information is requested over and over.

With caching, Laravel can save the result once, then reuse it until it expires or is cleared.

Think of it like this:

- Without cache: "Go to the store every time you need salt."
- With cache: "Keep salt in the kitchen because you use it often."

The goal is not to cache everything. The goal is to cache the right things.

## Why caching is important

Caching matters because it improves performance and reduces repeated work.

Main benefits:

- Faster page loads because Laravel can return saved data instead of rebuilding it
- Fewer database queries, which reduces load on MySQL/PostgreSQL
- Better scalability because the app can serve more users with less server effort
- Smoother UX, especially on list pages, registration flows, and dashboards
- Lower cost if the app grows and starts handling many requests

In a hospital system like this one, caching can help a lot with:

- Reference data
- Setup dropdowns
- Subscription package options
- Country lists
- Dashboard summaries that do not need to be real-time to the exact second

It is especially useful for data that changes rarely but is read often.

## The important rule: cache the right kind of data

Good cache candidates:

- Read-heavy data
- Data that changes rarely
- Data used by many users
- Data that is expensive to calculate
- Data that appears in dropdowns, forms, summaries, and dashboards

Bad cache candidates:

- Highly sensitive per-user state unless you key it very carefully
- Fast-changing transactional data that must always be exact
- One-off queries that are rarely repeated
- Data whose invalidation logic is unclear

In this project, for example:

- A list of subscription packages is a good cache candidate
- A list of countries is a good cache candidate
- Facility level enum options are fine to cache, although they are already cheap
- Live patient visit activity is usually not a great first cache target unless you use a very short TTL

## How caching works in Laravel

Laravel gives you a unified cache API through the `Cache` facade. You can use different storage drivers behind the scenes, but the code stays mostly the same.

Common drivers:

- `array`: only for the current request, mostly useful in tests
- `file`: stores cache on disk
- `database`: stores cache rows in database tables
- `redis`: very fast, best for production in many cases
- `memcached`: also fast, common in some deployments

## What this project is using right now

This project is already configured to use the `database` cache store.

Current setup:

- [`config/cache.php`](./config/cache.php) sets the default store from `CACHE_STORE`
- [`.env`](./.env) currently has `CACHE_STORE=database`
- The cache tables already exist through [`database/migrations/0001_01_01_000001_create_cache_table.php`](./database/migrations/0001_01_01_000001_create_cache_table.php)

That means you can start using Laravel cache in this project immediately without changing infrastructure first.

## A few cache terms you should know

### Cache key

This is the unique name used to store data.

Examples:

- `subscription-packages.index`
- `workspace-registration.subscription-packages`
- `workspace-registration.countries`
- `facility-manager.subscription-package-options`

Pick clear, stable names. Good cache keys make debugging much easier.

### TTL

TTL means "time to live". It is how long the cached value should stay before Laravel treats it as expired.

Examples:

- `now()->addMinutes(5)`
- `now()->addHour()`
- `now()->addDay()`

If the data changes rarely, use a longer TTL.
If the data changes often, use a shorter TTL.

### Cache invalidation

This means clearing or refreshing stale cached data when the underlying data changes.

This is the hardest part of caching.

If you cache subscription packages, then create, update, or delete a package, you must clear the relevant cache key. Otherwise users may keep seeing old data.

## The most common Laravel cache methods

### `Cache::get()`

Read a value from cache.

```php
use Illuminate\Support\Facades\Cache;

$packages = Cache::get('subscription-packages.index');
```

### `Cache::put()`

Store a value manually for a time period.

```php
Cache::put('subscription-packages.index', $packages, now()->addMinutes(10));
```

### `Cache::remember()`

This is the one you will use most often. Laravel checks the cache first. If the value is missing, it runs the callback, stores the result, and returns it.

```php
use Illuminate\Support\Facades\Cache;

$packages = Cache::remember(
    'subscription-packages.index',
    now()->addMinutes(10),
    fn () => SubscriptionPackage::query()->orderBy('name')->get()
);
```

### `Cache::forget()`

Remove a cached value.

```php
Cache::forget('subscription-packages.index');
```

### `Cache::rememberForever()`

Store data until you explicitly clear it.

Use this only for data you know how to invalidate safely.

```php
$countries = Cache::rememberForever(
    'workspace-registration.countries',
    fn () => Country::query()->orderBy('country_name')->get(['id', 'country_name'])
);
```

### `Cache::flush()`

This clears the whole cache store.

Avoid using it in normal feature logic.

It is mostly for maintenance or debugging.

## Where caching could be done in this project

These are the best places to start based on the current codebase.

### 1. Workspace registration options

Look at [`app/Http/Controllers/WorkspaceRegistrationController.php`](./app/Http/Controllers/WorkspaceRegistrationController.php).

That controller loads:

- Facility levels
- Subscription packages
- Countries

These are strong cache candidates because:

- They are reused
- They are mostly read-only
- They are sent to the registration page repeatedly

What to cache:

- `workspace-registration.facility-levels`
- `workspace-registration.subscription-packages`
- `workspace-registration.countries`

Suggested TTL:

- Facility levels: forever or very long
- Countries: forever or very long
- Subscription packages: 10 to 60 minutes, or forever if you always invalidate correctly

### 2. Facility manager setup option lists

Look at [`app/Http/Controllers/FacilityManagerController.php`](./app/Http/Controllers/FacilityManagerController.php).

This controller has helper methods that build:

- Subscription package options
- Country options
- Facility level options

Those are also good cache candidates for the same reason: repeated reads, low write frequency, and predictable invalidation.

### 3. Subscription package list data for non-search use cases

Look at [`app/Http/Controllers/SubscriptionPackageController.php`](./app/Http/Controllers/SubscriptionPackageController.php).

The `index()` action supports search and pagination. Because search terms and page numbers vary, this is not the first thing I would cache aggressively.

But these are still reasonable:

- Cache the default first page when there is no search term
- Cache simple option lists derived from subscription packages
- Cache "active packages" used in dropdowns or onboarding flows

I would not start by caching every search result because:

- The keys become more complicated
- Invalidation becomes harder
- The win is usually smaller than caching reference lists

### 4. Dashboard metrics with short TTL

Some dashboard summaries in the facility manager area may become expensive as data grows.

Those can be cached with short TTL values like 30 seconds, 1 minute, or 5 minutes if exact real-time precision is not required.

This works well for:

- Counts
- Trend cards
- Summary panels

Be more careful here, because dashboards can become misleading if cache lives too long.

### 5. Permission-heavy or role-based derived payloads

If you later find expensive repeated calculations that build the same payload for the same tenant or role, those can also be cached.

But those keys must include enough context, such as:

- Tenant ID
- User ID if needed
- Role or permission context if needed

If the key is too broad, one user can receive another user's cached result, which is dangerous.

## Where I would start first in this app

If I were implementing caching here, I would start in this order:

1. Countries list
2. Subscription package option lists
3. Workspace registration payload pieces
4. Facility manager setup option lists
5. Selected dashboard metrics with short TTL

That order is safe because it starts with stable reference data before moving into more dynamic data.

## How to implement caching in Laravel step by step

### Step 1: import the cache facade

```php
use Illuminate\Support\Facades\Cache;
```

### Step 2: identify the exact query or payload you want to cache

Do not cache a whole controller just because it exists.

Instead, cache the expensive or repeated part.

Good example:

- The array of subscription package options

Less good example:

- An entire highly dynamic page response with many user-specific values

### Step 3: wrap the query in `Cache::remember()`

Example using the subscription package options idea from this project:

```php
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\Cache;

$subscriptionPackages = Cache::remember(
    'workspace-registration.subscription-packages',
    now()->addMinutes(30),
    static fn (): array => SubscriptionPackage::query()
        ->orderBy('users')
        ->orderBy('name')
        ->get(['id', 'name', 'users', 'price'])
        ->map(static fn (SubscriptionPackage $package): array => [
            'id' => $package->id,
            'name' => $package->name,
            'users' => $package->users,
            'price' => $package->price,
        ])
        ->values()
        ->all(),
);
```

Now Laravel will only rebuild that payload when the cache is missing or expired.

### Step 4: clear the cache when the data changes

This is the part many people forget.

In this project, subscription package writes already go through Actions:

- [`app/Actions/CreateSubscriptionPackage.php`](./app/Actions/CreateSubscriptionPackage.php)
- [`app/Actions/UpdateSubscriptionPackage.php`](./app/Actions/UpdateSubscriptionPackage.php)
- [`app/Actions/DeleteSubscriptionPackage.php`](./app/Actions/DeleteSubscriptionPackage.php)

That makes these Actions the best place to invalidate related cache keys.

Example:

```php
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return DB::transaction(function () use ($attributes): SubscriptionPackage {
    $package = SubscriptionPackage::query()->create($attributes);

    Cache::forget('workspace-registration.subscription-packages');
    Cache::forget('facility-manager.subscription-package-options');

    return $package;
});
```

The same idea applies to update and delete operations.

### Step 5: keep keys organized

Use a naming style that tells you:

- Which feature the cache belongs to
- What data it stores
- Whether it is general or user-specific

A good pattern for this project would be:

- `workspace-registration.countries`
- `workspace-registration.subscription-packages`
- `facility-manager.subscription-package-options`
- `dashboard.tenant.{tenantId}.metrics`

That style makes the system easier to reason about.

## A practical implementation style for this project

Because this codebase prefers reusable Action classes, a clean approach is to move cached reads into dedicated Actions instead of placing everything directly inside controllers.

That keeps controllers simple and makes cache logic reusable.

Example idea:

- `app/Actions/GetWorkspaceRegistrationOptions`
- `app/Actions/GetSubscriptionPackageOptions`
- `app/Actions/GetCountryOptions`

Example Action:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\Cache;

final readonly class GetSubscriptionPackageOptions
{
    /**
     * @return array<int, array{id: string, name: string, users: int, price: mixed}>
     */
    public function handle(): array
    {
        return Cache::remember(
            'subscription-package.options',
            now()->addMinutes(30),
            static fn (): array => SubscriptionPackage::query()
                ->orderBy('users')
                ->orderBy('name')
                ->get(['id', 'name', 'users', 'price'])
                ->map(static fn (SubscriptionPackage $package): array => [
                    'id' => (string) $package->id,
                    'name' => $package->name,
                    'users' => (int) $package->users,
                    'price' => $package->price,
                ])
                ->values()
                ->all(),
        );
    }
}
```

Then the controller can call the Action, and the write Actions can clear that same key.

This fits your project structure much better than scattering cache logic all over different controllers.

## Example: caching the countries list

The countries list is a very strong candidate because it usually changes rarely.

Example:

```php
use App\Models\Country;
use Illuminate\Support\Facades\Cache;

$countries = Cache::rememberForever(
    'workspace-registration.countries',
    static fn (): array => Country::query()
        ->orderBy('country_name')
        ->get(['id', 'country_name'])
        ->map(static fn (Country $country): array => [
            'id' => $country->id,
            'name' => $country->country_name,
        ])
        ->values()
        ->all(),
);
```

Why `rememberForever()` can work here:

- Country lists are close to static reference data
- They are read often
- They rarely change

If you ever edit countries from an admin area later, then you would add `Cache::forget('workspace-registration.countries')` after those writes.

## Example: caching dashboard metrics safely

Dashboard metrics can benefit from cache, but use a short TTL.

Example:

```php
use Illuminate\Support\Facades\Cache;

$metrics = Cache::remember(
    "dashboard.tenant.{$tenant->id}.metrics",
    now()->addMinute(),
    fn (): array => [
        'patients' => Patient::query()->where('tenant_id', $tenant->id)->count(),
        'visits' => PatientVisit::query()->where('tenant_id', $tenant->id)->count(),
    ],
);
```

This is useful because:

- Counts can become expensive as tables grow
- A 1-minute delay is often acceptable for dashboards

Be careful not to use a long TTL if clinicians or administrators expect live numbers.

## Should you use database cache or Redis?

For this project today, `database` cache is okay and already configured.

Advantages of `database` cache:

- No extra infrastructure needed
- Easy to start with
- Good for learning and moderate traffic

Limitations of `database` cache:

- Slower than Redis
- Cache reads still hit the database
- Not ideal if traffic becomes high

Redis is usually a better long-term production cache because it is much faster and designed for this kind of workload.

My practical advice:

- Start with `database` cache while learning
- Use it for stable reference data first
- If the app grows and performance matters more, move to Redis later

Your application code using `Cache::remember()` will mostly stay the same.

## Important caution about cache tags

Laravel supports cache tags on some drivers, but not all drivers behave the same.

Since this project currently uses the `database` cache store, keep your first implementation simple and do not design around cache tags.

Instead, use clear individual keys and explicit `Cache::forget()` calls.

That approach is easier to learn and safer for this setup.

## Common mistakes to avoid

### 1. Caching everything

Do not do this. Cache only things that are worth caching.

### 2. Forgetting invalidation

If you cache subscription packages and never clear the cache after updates, users will see stale data.

### 3. Using vague cache keys

Avoid keys like:

- `data`
- `list`
- `items`

Use descriptive keys instead.

### 4. Using long TTLs for changing data

If data changes often, stale cache will create bugs or confusion.

### 5. Caching user-specific data with shared keys

This can leak data between tenants or users if done incorrectly.

Always scope those keys carefully.

### 6. Caching before measuring

Caching is useful, but it should solve a real repeated-read problem.

If a query is cheap and rare, caching may add complexity without much benefit.

## How to think about cache invalidation in this project

A simple rule:

- Read Actions or controllers may create cache
- Write Actions should clear cache

For example:

- Reading subscription package options creates cached data
- `CreateSubscriptionPackage`, `UpdateSubscriptionPackage`, and `DeleteSubscriptionPackage` clear it

That pattern keeps the system understandable.

## A simple mental model you can use

When deciding whether to cache something, ask:

1. Is this data read often?
2. Is it expensive enough to matter?
3. Does it change rarely?
4. Do I know exactly when to clear it?

If the answer to most of those is yes, it is usually a good cache candidate.

## My recommendation for your first real implementation

If you want to practice caching in this project, start with these two:

1. Cache the countries list used in workspace registration
2. Cache the subscription package options used in workspace registration and facility manager flows

Why these first:

- They are safe
- They are easy to understand
- They demonstrate both cache reads and invalidation
- They fit well with the existing Action pattern

## Summary

Caching in Laravel means storing repeated results so the app can respond faster and do less work.

For this project, the best early cache targets are stable reference datasets such as:

- Countries
- Subscription package option lists
- Facility level option lists
- Some dashboard summaries with short TTL

The main implementation pattern is:

1. Choose a good cache key
2. Wrap repeated queries in `Cache::remember()`
3. Use a sensible TTL
4. Clear the cache inside the write Action when the underlying data changes

If you want, the next step can be to actually implement caching for the subscription package options and countries list in this codebase so you can see the full Laravel flow in practice.
