# Klein.php — Routing Architecture

## Overview

**Package**: `matecat/klein` (MIT, fork of `klein/klein.php`)
**Authors**: Chris O'Hara (original), Trevor Suarez (v2 refactorer), Domenico Lupinetti (v3 refactorer & Matecat fork maintainer)
**Requires**: PHP ≥8.3, ext-ctype, ext-fileinfo
**Namespace**: `Klein\` (PSR-4 → `src/Klein/`)

The original `klein/klein.php` is abandoned. Matecat maintains its own fork with PHP 8.3+ compatibility and, crucially, a **completely rewritten routing engine**.

---

## The New Routing: Radix Tree Index

The original Klein (v1/v2) dispatched by **linear scan** — every request iterated through every registered route, compiling regex and testing each one. With ~365 routes in Matecat, that's expensive.

The v3 fork introduces **`RadixRouteIndex`** — a radix-tree-based route index that prunes the search space before any regex compilation happens.

### Architecture

```
src/Klein/
├── Klein.php                     ← Main router (dispatch + filterMatchingRoutes)
├── AbstractRouteFactory.php      ← Factory pattern for Route creation
├── Routes/
│   ├── Route.php                 ← Route definition (readonly props, lazy regex, hash identity)
│   ├── RouteFactory.php          ← Default factory (namespace-aware)
│   └── RouteRegexCompiler.php    ← Extracted regex compilation logic
└── Tree/
    ├── IndexInterface.php        ← Interface: findPossibleRoutes(uri), addRoute(route)
    └── RadixRouteIndex.php       ← Radix tree implementation
```

### How `RadixRouteIndex` Works

**Registration time** (`addRoute()`):

1. Extract the **longest literal prefix** from the route path before any dynamic token (`[`, `(`, `.`, `?`, `+`, `*`, `{`)
   - `/api/v3/projects/[:id]` → literal prefix = `/api/v3/projects/`
   - `*` or custom regex → goes to **catch-all bucket** (no prefix indexable)
2. Store route under `$radixTree[prefix][route_hash]`
3. Build **parent-prefix links** upward: `/api/v3/projects/` → `/api/v3/` → `/api/` → `/`
   - These are PHP array references (`&$this->radixTree[$prefix]`) so child routes are reachable from any ancestor prefix

**Dispatch time** (`findPossibleRoutes(uri)`):

1. Tokenize the URI into segments: `/api/v3/projects/42` → `["", "api", "v3", "projects", "42"]`
2. Search **longest prefix first**: try `/api/v3/projects/42`, then `/api/v3/projects/`, then `/api/v3/`, etc.
3. Collect routes via **DFS traversal** of the tree node (for ≤130K routes) or `array_walk_recursive` (for very large sets)
4. Return matched candidate routes — these are the **only** routes that need regex testing

### The Dispatch Pipeline (`Klein::dispatch()` → `filterMatchingRoutes()`)

```
Request URI ─┐
             ├─ RadixRouteIndex::findPossibleRoutes(uri) ─── candidate routes (tree)
             │
             ├─ RadixRouteIndex::getCatchAllRoute() ──────── catch-all routes (regex/wildcard)
             │
             └─ For each candidate:
                  ├─ Static route? → direct string comparison (NO regex)
                  ├─ Dynamic route? → compile regex (lazy, cached) → preg_match
                  ├─ Method match? → matchesMethod() with HEAD/GET special handling
                  └─ Record params, mark matched
                  
             Combined: tree_candidates + catch_all_routes → iterate → dispatch callbacks
```

### Key Optimizations in v3

| Optimization | Where | Impact |
|---|---|---|
| **Radix tree prefix index** | `RadixRouteIndex` | Prunes routes from O(n) to O(subtree) per request |
| **Static route fast-path** | `filterMatchingRoutes()` | Non-dynamic routes skip regex entirely, use `===` comparison |
| **Lazy regex compilation** | `Route::getCompiledRegex()` | Regex only compiled on first match attempt, then cached |
| **Hash-based route identity** | `Route::$hash` = `hrtime(true).spl_object_hash` | O(1) dedup in tree lookups |
| **DFS vs array_walk_recursive** | `RadixRouteIndex` | Manual DFS for typical sizes (<130K), built-in walk for extreme sizes |
| **Readonly properties** | `Route` | PHP 8.1+ immutability, prevents accidental mutation |
| **`RouteRegexCompiler` extraction** | `RouteRegexCompiler` | Static utility class — regex logic separated from Route state |

---

## Route Class (v3 Redesign)

The `Route` class was heavily refactored from v2:

- **`readonly` properties**: `$namespace`, `$callback`, `$path`, `$originalPath`, `$method`, `$countMatch`, `$isNegated`, `$isCustomRegex`, `$isDynamic`, `$hash`
- **Per-URI match tracking**: `$routeMatched[$hash|$uri]` — supports multi-URI scenarios
- **Method validation via enum**: `HttpMethod::tryFrom()` validates at construction, not dispatch
- **Construction-time path analysis**: `$isDynamic`, `$isCustomRegex`, `$isNegated` all computed once, checked cheaply at dispatch

---

## Route Syntax

| Token | Meaning | Regex |
|---|---|---|
| `[i:id]` | Integer | `[0-9]++` |
| `[a:name]` | Alphanumeric | `[0-9A-Za-z]++` |
| `[h:hex]` | Hex | `[0-9A-Fa-f]++` |
| `[s:slug]` | Slug | `[0-9A-Za-z-_]++` |
| `[:param]` | Anything to next `/` | `[^/]+?` |
| `[*]` | Catch-all (lazy) | `.+?` |
| `[**]` | Catch-all (possessive) | `.++` |
| `@regex` | Custom regex | Passed through |
| `!` | Negation | Negative match |
| `[create\|edit:action]` | Enumerated values | Inline alternation |

---

## How Matecat Uses Klein

**Entry point**: `.htaccess` → `router.php` → `Klein::dispatch()`

**Route files** (7 files, ~365 routes total):

- `lib/Routes/view_routes.php` — 7 HTML page routes
- `lib/Routes/api_v1_routes.php` — legacy (proxied to v2/v3)
- `lib/Routes/api_v2_routes.php` — ~50 REST routes
- `lib/Routes/api_v3_routes.php` — ~60 modern routes
- `lib/Routes/app_routes.php` — ~100 user/auth routes
- `lib/Routes/gdrive_routes.php` — 5 GDrive integration routes
- `lib/Routes/oauth_routes.php` — OAuth callbacks

### Controller Hierarchy

```
IController (interface)
└── KleinController (params extraction, validator chain)
    └── AbstractStatefulKleinController (session)
        ├── BaseKleinViewController (PHPTAL views)
        └── AbstractDownloadController (file streaming)
```

### Route Registration Pattern

```php
// router.php defines route() helper wrapping $klein->respond()
route('/api/v3/teams', 'GET', ['\Controller\API\V3\TeamsProjectsController', 'getPaginated']);

// Grouping via with()
$klein->with('/api/v2/jobs/[:id_job]/[:password]', function () {
    route('', 'GET', ['Controller\API\V2\JobsController', 'show']);
    route('/comments', 'POST', ['Controller\API\V2\CommentsController', 'create']);
});
```

### Validation Middleware

Controllers override `afterConstruct()` to append validators:

```php
protected function afterConstruct(): void
{
    $this->appendValidator(new LoginValidator($this));
}
```

All validators run before the action method. Custom validators extend the `Base` class.

### Error Handling

Central error handling in `router.php`:

- `404` errors → JSON `NotFoundException` or HTML view depending on request type
- Exception mapping to HTTP status codes:
  - `ValidationError` → 400
  - `AuthenticationError` → 401
  - `AuthorizationError` → 403
  - `NotFoundException` → 404
  - `UnprocessableException` → 422
  - `PDOException` → 503
  - Default → 500

### Plugin Routes

`PluginsLoader::loadRoutes($klein)` dynamically loads `manifest.php` from each plugin, registering routes under `/plugins/{feature_code}/`.

---

## Key Classes Reference

### `Klein\Klein` — Main Router

```php
respond(method, path, callback): Route   // Register a route
with(namespace, routes): void            // Group routes under a prefix
dispatch(request?, response?, send?, capture?): void|string
onError(callback): void                  // Register error handler
onHttpError(callback): void              // Register HTTP error handler
afterDispatch(callback): void            // Register post-dispatch callback
getPathFor(route_name, params?, flatten?): string  // Reverse routing

// Convenience aliases
get(path, callback): Route
post(path, callback): Route
put(path, callback): Route
delete(path, callback): Route
patch(path, callback): Route
head(path, callback): Route
options(path, callback): Route

// Dispatch control
skipThis(): void        // Skip current route
skipNext(num): void     // Skip next N routes
skipRemaining(): void   // Stop dispatching
abort(code?): void      // Halt with HTTP error
```

### `Klein\Request`

```php
paramsGet(): DataCollection       // $_GET wrapper
paramsPost(): DataCollection      // $_POST wrapper
paramsNamed(): DataCollection     // Named route parameters
cookies(): DataCollection         // $_COOKIE wrapper
server(): ServerDataCollection    // $_SERVER wrapper
headers(): HeaderDataCollection   // Request headers
files(): DataCollection           // $_FILES wrapper
body(): string                    // Raw request body
param(key, default?): mixed       // Get any parameter
isSecure(): bool                  // HTTPS check
ip(): string                      // Client IP
uri(): string                     // Request URI
method(): string                  // HTTP method (alias: httpMethod())
pathname(): string                // URI path without query string
```

### `Klein\Response` (extends `AbstractResponse`)

```php
body(content?): string|static     // Get/set response body
code(code?): int|static           // Get/set HTTP status code
header(key, value?): static       // Set response header
cookie(name, value, ...): static  // Set cookie
redirect(url, code?): static      // HTTP redirect
json(data, ...): static           // Send JSON response
file(path, ...): static           // Send file
send(): static                    // Send the response
chunk(str?): static               // Chunked transfer
noCache(): static                 // Set no-cache headers
append(content): static           // Append to body
prepend(content): static          // Prepend to body
lock(): static                    // Lock response from further modification
unlock(): static                  // Unlock response
isLocked(): bool                  // Check lock state
```

### `Klein\ServiceProvider`

```php
flash(msg?, type?): string|void   // Flash messages
render(view, data?): void         // Render a view template
layout(layout): void              // Set layout template
escape(str): string               // HTML escape
validate(string): Validator       // Create validator for string
validateParam(param): Validator   // Create validator for request param
addValidator(name, callback): void // Register custom validator
```

### `Klein\Validator`

Chainable validation:

```php
notNull(): static
isLen(len): static
isInt(): static
isFloat(): static
isEmail(): static
isUrl(): static
isIp(): static
isAlpha(): static
isAlnum(): static
contains(str): static
isChars(chars): static
isRegex(pattern): static
// Custom validators via is<Method>()
```

### `Klein\App`

```php
register(name, callback): void   // Lazy service registration
// Magic __get/__call for registered services
```

---

## Summary

Matecat's Klein v3 fork replaces linear route scanning with a **radix tree prefix index** + **static route fast-path** + **lazy regex compilation**, bringing dispatch from O(all routes) down to O(subtree candidates). The `Tree/RadixRouteIndex` is the core of the new implementation — it indexes literal path prefixes at registration time and uses longest-prefix DFS lookup at dispatch time.
