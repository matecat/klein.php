# CLAUDE.md

Guidance for Claude Code (claude.ai/code) working in this repo.

## Commands

```bash
# Install dependencies
composer install

# Run all tests
./vendor/bin/phpunit -d memory_limit=-1 --colors=always

# Run single test file
./vendor/bin/phpunit tests/Matecat/Tests/Klein/RoutingTest.php

# Run single test method
./vendor/bin/phpunit --filter testMethodName tests/Matecat/Tests/Klein/RoutingTest.php

# Static analysis (PHPStan level 8)
./vendor/bin/phpstan analyse --no-progress

# Run tests with coverage (requires xdebug)
XDEBUG_MODE=coverage ./vendor/bin/phpunit -d memory_limit=-1 --coverage-clover=coverage.xml 2>&1 > /tmp/phpunit.log
```

## Architecture

**Namespace**: `Klein\` → `src/Klein/`  
**Tests namespace**: `Matecat\Tests\Klein\` → `tests/Matecat/Tests/Klein/`  
**PHP**: ≥8.3, requires `ext-ctype` and `ext-fileinfo`

Fork of abandoned `klein/klein.php` router. Heavy refactor for PHP 8.3+. Dispatch engine rewritten.

### Core Dispatch Pipeline

`Klein::dispatch()` → `filterMatchingRoutes()`:

1. `RadixRouteIndex::findPossibleRoutes($uri)` — radix-tree lookup prunes candidates (avoids O(n) scan)
2. `RadixRouteIndex::getCatchAllRoutes()` — routes with no indexable prefix (wildcards, custom regex)
3. Static routes use `===`; dynamic routes compile regex lazily via `Route::getCompiledRegex()` and `preg_match`
4. Method matching via `HttpMethod` enum (HEAD inherits GET handlers)

### Key Classes

| Class | Role |
|---|---|
| `Klein` | Main router — `respond()` registers routes, `dispatch()` runs them |
| `RadixRouteIndex` | Radix tree: indexes routes by longest literal prefix; DFS traversal at dispatch |
| `Route` | Immutable (`readonly` props): path, method, callback, flags (`$isDynamic`, `$isCustomRegex`, `$isNegated`) computed at construction |
| `RouteRegexCompiler` | Static utility — compiles route path syntax to regex |
| `AbstractRouteFactory` | Factory pattern for `Route` creation; `RouteFactory` adds namespace support |
| `Request` / `Response` | HTTP primitives wrapping superglobals |
| `ServiceProvider` | DI container passed to route callbacks |
| `Validator` | Chainable input validation |

### Radix Tree Internals (`Tree/RadixRouteIndex.php`)

- **Registration**: extracts longest literal prefix before first dynamic token (`[`, `(`, `.`, `?`, `+`, `*`, `{`); pure-wildcard routes go to catch-all bucket
- **Parent links**: routes stored under their prefix AND all ancestor prefixes (PHP array references), so `/api/v3/` routes reachable when URI matches `/api/v3/projects/42`
- **Dispatch**: tokenizes URI, tries longest prefix first down to `/`, collects via DFS (≤130K routes) or `array_walk_recursive` (larger sets)

### Route Syntax

| Token | Meaning |
|---|---|
| `[i:name]` | Integer (`[0-9]++`) |
| `[a:name]` | Alphanumeric (`[0-9A-Za-z]++`) |
| `[h:name]` | Hex |
| `[:name]` | Any non-slash (`[^/]++`) |
| `[*:name]` | Greedy any (`.*`) |
| `!` prefix | Negated route (matches when URI does NOT match) |
| Custom regex | Full regex string as path — bypasses prefix indexing |

### PHPStan

Level 8. Config in `phpstan.neon`. Scans `src/` only. Strict exception checking enabled (`missingCheckedExceptionInThrows`, `tooWideThrowType`). `tests/functions-bootstrap.php` scanned for stubs, excluded from analysis.