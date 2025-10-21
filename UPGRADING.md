# Klein Upgrade Guide

## 3.1.0 to 3.2.0

### Behavior Changes

- The execution order of the route callbacks is no more guaranteed to be the same as the order in which they were registered when mixing catch all routes and routes with specified paths.
  - `HeaderDataCollection` class is now `ucword` sanitization. 
     Underscores are not allowed in the header field names since 
     nginx and Apache will silently drop HTTP headers with underscores 
     (which are perfectly valid, according to the HTTP standard). 
     This is done to prevent ambiguities when mapping headers to CGI variables, 
     as both dashes and underscores are mapped to underscores during that process.
  - Fields are now case-sensitive.
      
    Ex: 
```shell
    curl -H 'content-TOP: Fake Content Type' -H 'content-type: application/json' https://localhost
    [
        'Host' => 'localhost',
        'User-Agent' => 'curl/8.5.0',
        'Accept' => '*/*',
        'content-TOP' => 'Fake',
        'content-type' => 'application/json',
    ] => 
    HeaderDataCollection::get('content-TOP') => NULL
    HeaderDataCollection::get('content-type') => NULL
    HeaderDataCollection::get('Content-Top') => 'Fake Content Type'
    HeaderDataCollection::get('Content-Type') => 'application/json'
```

## 2.1.1 to 2.1.2

### Interface Changes

- The `RoutePathCompilationException::createFromRoute()` method signature has changed to allow both `Exception` and `Throwable` types with dual support for PHP 5 and PHP 7
- The 4th parameter to the callbacks supported by `Klein#onError` will now be able to receive `Throwable` types under PHP 7


## 2.1.0 to 2.1.1

### Deprecations

- The `HeaderDataCollection::normalizeName()` method has been deprecated in favor of using new normalization options (via constant switches) and other more specific methods on the same class

### Interface Changes

- Three of the Klein internal callback attributes have changed both name and data structure. These attributes are protected, so the effect will only be felt by users that have extended and/or overwritten Klein's internal behaviors. The following changes were made:
    - `Klein#errorCallbacks` was renamed to `Klein#error_callbacks` and it's array data-structure was changed to use an `SplStack`
    - `Klein#httpErrorCallbacks` was renamed to `Klein#http_error_callbacks` and it's array data-structure was changed to use an `SplStack`
    - `Klein#afterFilterCallbacks` was renamed to `Klein#after_filter_callbacks` and it's array data-structure was changed to use an `SplQueue`
- `Validator#defaultAdded` was renamed to `Validator#default_added`


## 2.0.x to 2.1.0

### Deprecations

- Handling 404 and 405 errors with a specially registered route callback is now deprecated. It's now suggested to use Klein's new `onHttpError()` method instead.
- Autoloading the library with Composer no longer utilizes the PSR-0 spec. The composer autoloader now uses PSR-4.

### Interface Changes

- Some of the route callback params have changed. This will effect any route definitions with callbacks using the more advanced parameters.
    - The old params were (in order):
        - `Request $request`
        - `Response $response`
        - `Service $service`
        - `App $app`
        - `int $matched`
        - `array $methods_matched`
    - The new params are (in order):
        - `Request $request`
        - `Response $response`
        - `Service $service`
        - `App $app`
        - `Klein $klein`
        - `RouteCollection $matched`
        - `array $methods_matched`
- Non-match routes (routes that are wildcard and shouldn't consider as "matches") will no longer be considered as part of the "methods matched" array, since they aren't supposed to be matches in the first place
    - This may have implications for users that have created "match-all" OPTIONS method routes, as the OPTIONS method will no longer be considered a match.
    - If you'd like to conserve the old match behavior, you can simply mark the route as one that should be counted as a match with `$route->setCountMatch(true)`
