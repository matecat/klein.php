# Router Benchmark Tests

This benchmark suite evaluates the performance of various PHP router packages by testing their initialization, route 
registration, and dispatching efficiency under different conditions. The tests simulate real-world scenarios, 
measuring execution time and memory consumption.

## How the Benchmark Works

1. **Test Execution**  
   - Each router is tested across multiple predefined scenarios.  
   - Tests include both static and dynamic routes, with and without wildcards.  

2. **Performance Measurement**  
   - Each test is executed **20 times per router** to ensure consistent results.  
   - The **median execution time** is used to reduce the impact of outliers.  
   - Peak memory usage is recorded during execution.  

3. **Results and Ranking**  
   - The fastest router is ranked **#1** based on median execution time.  
   - A percentage comparison shows how each router performs relative to the fastest one.  
   - Memory usage is also measured and ranked.

## Test Structure

Each test follows a structured process:

- **Router Initialization** – The router is initialized before each test.  
- **Route Registration** – A predefined set of routes is registered.  
- **Request Dispatching** – The router processes test requests and returns responses.  
- **Result Validation** – The response is compared to the expected output to ensure correctness.  

## Output Format

The benchmark results are presented in a table with the following columns:

| Rank | Router | Time (ms) | Time (%) | Peak Memory (MB) | Memory (%) |
|------|--------|----------|---------|-----------------|-----------|
| 1    | FastRouter | 10.2  | 100%   | 1.2         | 100%     |
| 2    | AnotherRouter | 12.5 | 122% | 1.4         | 116%     |

- **Time (%)** compares execution time to the fastest router.  
- **Memory (%)** compares peak memory usage.  

## Why This Matters

Efficient routing is essential for high-performance web applications. By running standardized tests across different 
routers and using the median execution time, this benchmark provides a clear and objective comparison of their efficiency.

## Ease of Use & Implementation  

While these benchmarks highlight the performance of each tested router, another crucial factor is **how easy they are 
to use in a project**. If you check the **`src/Routers`** directory, you’ll find the adapter files that show how each 
router is set up. The complexity of implementation varies significantly—some routers require minimal setup, while 
others need more configuration to get started. Additionally, some routers include extra features that are **not 
covered in this benchmark** but may be valuable depending on your needs. Be sure to explore these details when 
choosing a router!
## Packages

| Name | Package | Version  |
|-----------|-------------|----------|
| Laravel | [illuminate/routing](https://github.com/illuminate/routing) | v11.46.1 |
| Rammewerk | [rammewerk/router](https://github.com/rammewerk/router) | 0.9.82   |
| Bramus | [bramus/router](https://github.com/bramus/router) | 1.6.1    |
| AltoRouter | [altorouter/altorouter](https://github.com/dannyvankooten/AltoRouter) | 2.0.3    |
| Symfony | [symfony/routing](https://symfony.com/doc/current/routing.html) | v7.3.4   |
| Klein | [matecat/klein](https://github.com/matecat/klein) | v3.2.0   |
| FastRoute | [nikic/fast-route](https://github.com/nikic/FastRoute) | v1.3.0   |
| PHRoute | [phroute/phroute](https://github.com/mrjgreen/phroute) | v2.2.0   |
| Nette | [nette/routing](https://github.com/nette/routing) | v3.1.1   |
| Jaunt | [davenusbaum/jaunt](https://github.com/davenusbaum/jaunt) | v0.0.1   |


## Benchmark Results

These tests was run **2025-10-23 13:22:50** on PHP version: **8.4.13**



### Router Initialization Performance Test 

This test measures **how quickly the router initializes** when called **1000 times**. It helps determine the overhead of 
setting up the router repeatedly. A slower result here could indicate an expensive initialization process.

`Test Suite #1:`

| Rank | Container | Time (ms) | Time (%) | Peak Memory (MB) | Peak Memory (%) |
| --- | ------------- | ------ | ------- | ------ | ------ |
| 1 | **Jaunt** | 0.089 | 100% | 0.347 | 100% |
| 2 | **PHRoute** | 0.13 | 146% | 0.347 | 100% |
| 3 | **AltoRouter** | 0.173 | 194% | 0.347 | 100% |
| 4 | **Bramus** | 0.214 | 240% | 0.347 | 100% |
| 5 | **Rammewerk Router** | 0.217 | 244% | 0.348 | 100% |
| 6 | **FastRoute** | 0.357 | 401% | 0.347 | 100% |
| 7 | **Symfony Router** | 0.596 | 670% | 0.347 | 100% |
| 8 | **Nette** | 10.416 | 11703% | 0.358 | 103% |
| 9 | **Laravel** | 13.189 | 14819% | 3.532 | 1019% |
| 10 | **Klein** | 14.966 | 16816% | 0.365 | 105% |


### Router Initialization and Route Registration Performance Test (Static Routes) 

This test measures **how efficiently the router initializes and registers routes**. It generates **500 static routes** with up to **6 segments** each 
and registers them as closure-based routes.
The total time reflects how fast the router can complete this process.

`Test Suite #2:`

| Rank | Container | Time (ms) | Time (%) | Peak Memory (MB) | Peak Memory (%) |
| --- | ------------- | ------ | ------- | ------ | ------ |
| 1 | **AltoRouter** | 0.058 | 100% | 0.977 | 100% |
| 2 | **Bramus** | 0.153 | 264% | 1.085 | 111% |
| 3 | **FastRoute** | 0.385 | 664% | 0.895 | 92% |
| 4 | **PHRoute** | 0.543 | 936% | 1.195 | 122% |
| 5 | **Symfony Router** | 0.56 | 966% | 1.192 | 122% |
| 6 | **Jaunt** | 0.615 | 1060% | 1.713 | 175% |
| 7 | **Rammewerk Router** | 0.781 | 1347% | 1.347 | 138% |
| 8 | **Nette** | 1.119 | 1929% | 1.582 | 162% |
| 9 | **Klein** | 1.781 | 2827% | 1.91 | 195% |
| 10 | **Laravel** | 2.639 | 4550% | 1.368 | 140% |


### Router Dispatch Performance Test (Static Routes) 

This test measures how efficiently the router initializes, registers, and dispatches routes. It generates **500 static routes** 
with up to **6 segments** each and registers them as closure-based routes. However, **only a single random predefined route is 
dispatched**, and this same route is used for all routers to ensure consistent results. The benchmark reflects the time 
taken for the **entire process**, including initializing the router, registering all routes, and dispatching 
**one specific route**.

`Test Suite #3:`

| Rank | Container | Time (ms) | Time (%) | Peak Memory (MB) | Peak Memory (%) |
| --- | ------------- | ------ | ------- | ------ | ------ |
| 1 | **AltoRouter** | 0.082 | 100% | 0.977 | 100% |
| 2 | **Bramus** | 0.205 | 250% | 1.088 | 111% |
| 3 | **FastRoute** | 0.302 | 368% | 0.894 | 92% |
| 4 | **PHRoute** | 0.567 | 691% | 1.195 | 122% |
| 5 | **Jaunt** | 0.593 | 723% | 1.687 | 173% |
| 6 | **Rammewerk Router** | 0.66 | 805% | 1.357 | 139% |
| 7 | **Symfony Router** | 0.75 | 915% | 1.246 | 127% |
| 8 | **Nette** | 1.405 | 1713% | 1.582 | 162% |
| 9 | **Klein** | 2.28 | 2780% | 1.958 | 200% |
| 10 | **Laravel** | 2.601 | 3172% | 1.43 | 146% |


### Router Dispatch Performance Test (Dynamic Routes) 

This test is similar to Test 3 but with dynamic routes, it measures how efficiently the router initializes, registers, and dispatches routes. 
It generates **500 dynamic routes** with up to **6 segments**, including **2 dynamic/wildcard segments**. 
However, **only a single predefined route is 
dispatched**, and this same route is used for all routers to ensure consistent results. The benchmark reflects the time 
taken for the **entire process**, including initializing the router, registering all routes, and dispatching 
**one specific route**.

`Test Suite #4:`

| Rank | Container | Time (ms) | Time (%) | Peak Memory (MB) | Peak Memory (%) |
| --- | ------------- | ------ | ------- | ------ | ------ |
| 1 | **Bramus** | 0.212 | 100% | 1.109 | 100% |
| 2 | **AltoRouter** | 0.272 | 128% | 0.626 | 56% |
| 3 | **Jaunt** | 0.891 | 420% | 1.686 | 152% |
| 4 | **Rammewerk Router** | 1.045 | 493% | 1.238 | 112% |
| 5 | **Symfony Router** | 1.09 | 514% | 0.959 | 86% |
| 6 | **FastRoute** | 1.828 | 862% | 1.038 | 94% |
| 7 | **Klein** | 2.678 | 1263% | 1.766 | 159% |
| 8 | **Nette** | 2.717 | 1282% | 1.913 | 172% |
| 9 | **Laravel** | 2.764 | 1304% | 1.147 | 103% |
| 10 | **PHRoute** | 3.068 | 1447% | 1.348 | 122% |


### Router Dispatch Performance Longest Route Test (Dynamic Routes) 

This test measures the router's "full short-lived process lifecycle". 
It generates **500 dynamic routes** with up to **6 segments**, including **2 dynamic/wildcard segments**. 
Each route is registered as a closure-based route. **Only the LONGEST route is dispatched**.

`Test Suite #5:`

| Rank | Container | Time (ms) | Time (%) | Peak Memory (MB) | Peak Memory (%) |
| --- | ------------- | ------ | ------- | ------ | ------ |
| 1 | **Bramus** | 0.467 | 100% | 1.109 | 100% |
| 2 | **Jaunt** | 0.785 | 168% | 1.709 | 154% |
| 3 | **Rammewerk Router** | 0.872 | 187% | 1.223 | 110% |
| 4 | **AltoRouter** | 1.135 | 243% | 0.641 | 58% |
| 5 | **Klein** | 2.054 | 440% | 1.745 | 157% |
| 6 | **Symfony Router** | 2.304 | 493% | 1.46 | 132% |
| 7 | **Nette** | 3.223 | 690% | 1.928 | 174% |
| 8 | **FastRoute** | 3.933 | 842% | 1.038 | 94% |
| 9 | **Laravel** | 4.922 | 1054% | 1.62 | 146% |
| 10 | **PHRoute** | 6.184 | 1324% | 1.348 | 122% |


### Router Dispatch Performance Last Route Test (Dynamic Routes) 

This test measures the router's "full short-lived process lifecycle". 
It generates **500 dynamic routes** with up to **6 segments**, including **2 dynamic/wildcard segments**. 
Each route is registered as a closure-based route. **Only the LAST route is dispatched**.
Dispatching the last route measure the worst-case O(n) lookup when iterating on a route list.

`Test Suite #6:`

| Rank | Container | Time (ms) | Time (%) | Peak Memory (MB) | Peak Memory (%) |
| --- | ------------- | ------ | ------- | ------ | ------ |
| 1 | **Bramus** | 0.543 | 100% | 1.109 | 100% |
| 2 | **Jaunt** | 0.849 | 156% | 1.691 | 152% |
| 3 | **Rammewerk Router** | 1.064 | 196% | 1.221 | 110% |
| 4 | **AltoRouter** | 1.709 | 315% | 0.664 | 60% |
| 5 | **FastRoute** | 1.774 | 327% | 1.037 | 94% |
| 6 | **Klein** | 2.549 | 469% | 1.73 | 156% |
| 7 | **Symfony Router** | 3.195 | 588% | 1.806 | 163% |
| 8 | **Nette** | 3.242 | 597% | 1.951 | 176% |
| 9 | **PHRoute** | 3.417 | 629% | 1.346 | 121% |
| 10 | **Laravel** | 6.783 | 1249% | 1.994 | 180% |


### Router Class-Based Dispatch Performance Test (Dynamic Routes) 

This test measures **how efficiently the router handles Class-based route dispatching**. It generates **500 routes** with up to **6 segments**, 
including **2 dynamic/wildcard segments**, and maps them to methods within a predefined class. Each route is registered as a Class-based route. **Only the LAST route is dispatched**.
Dispatching the last route measure the worst-case O(n) lookup when iterating on a route list.
The benchmark also reflects the router’s performance in handling Class-based route resolution.

`Test Suite #7:`

| Rank | Container | Time (ms) | Time (%) | Peak Memory (MB) | Peak Memory (%) |
| --- | ------------- | ------ | ------- | ------ | ------ |
| 1 | **Bramus** | 0.618 | 100% | 0.785 | 100% |
| 2 | **Rammewerk Router** | 0.954 | 154% | 1.226 | 156% |
| 3 | **Jaunt** | 0.983 | 159% | 1.8 | 229% |
| 4 | **FastRoute** | 1.7 | 275% | 1.148 | 146% |
| 5 | **AltoRouter** | 1.714 | 277% | 0.775 | 99% |
| 6 | **Klein** | 3.016 | 488% | 1.648 | 210% |
| 7 | **PHRoute** | 3.273 | 530% | 1.457 | 186% |
| 8 | **Nette** | 4.263 | 690% | 2.137 | 272% |
| 9 | **Symfony Router** | 5.075 | 821% | 1.929 | 246% |
| 10 | **Laravel** | 7.095 | 1148% | 2.072 | 264% |


### Router Large-Scale Route Handling Performance Test (Dynamic Routes) 

This test measures **how efficiently the router handles a large number of registered routes**. It generates **1,000 routes** 
with up to **6 segments**, including **2 dynamic/wildcard segments**. After initialization and registration, **each route is dispatched 
once** to validate its response. 
The benchmark reflects the average the overall performance in handling a high number of routes efficiently.

`Test Suite #8:`

| Rank | Container | Time (ms) | Time (%) | Peak Memory (MB) | Peak Memory (%) |
| --- | ------------- | ------ | ------- | ------ | ------ |
| 1 | **Jaunt** | 3.448 | 100% | 3.058 | 100% |
| 2 | **Rammewerk Router** | 4.123 | 120% | 2.181 | 71% |
| 3 | **Klein** | 27.75 | 805% | 4.589 | 150% |
| 4 | **Symfony Router** | 56.184 | 1629% | 3.337 | 109% |
| 5 | **Bramus** | 150.371 | 4361% | 1.866 | 61% |
| 6 | **Laravel** | 313.228 | 9084% | 4.439 | 145% |
| 7 | **FastRoute** | 316.381 | 9176% | 2.073 | 68% |
| 8 | **Nette** | 369.112 | 10705% | 3.544 | 116% |
| 9 | **PHRoute** | 375.372 | 10887% | 2.338 | 76% |
| 10 | **AltoRouter** | 1134.909 | 32915% | 0.972 | 32% |
## How to Run Benchmarks

### 1. Prerequisites
Make sure you have the following installed:
- **Docker**
- **Docker Compose**

### 2. Clone the Repository
Clone or download this repository to your local machine:
```bash
git clone https://github.com/follestad/php-router-benchmark.git
cd php-router-benchmark
```

### 3. Start the Benchmark Environment
Use **Docker Compose** to start the PHP-FPM container:
```bash
docker compose up -d
```
This will set up the environment needed for testing. You can check public/index.php for details on how the benchmark 
runs or visit http://localhost for additional instructions.

### 4. Install or Update Dependencies
Before running the benchmark, install or update dependencies:
```bash
sh benchmark.sh composer install  # For first-time setup
sh benchmark.sh composer update   # To update dependencies
sh benchmark.sh composer require .../...   # To add new packages
```
### 5. Run the Benchmark
Execute the following command to run the benchmark:
```bash
sh benchmark.sh run
```
### 6. Viewing Results
After running the benchmark, results will be **saved to /result/README.md**.
If the benchmark completes successfully, you can copy this file to replace the main README.
This will update the project documentation with the latest benchmark results.

## Understanding Router Implementations
If you’re curious about how different routers are implemented, check out the `src/Routers` directory.
Some routers require minimal setup, while others need more extensive configuration. For example:
- **Rammewerk** Router has a compact and simple setup.
- **Symfony and Laravel** require more extensive configuration.
- **Bramus** Router needed additional adjustments to properly validate results, see `src/TestCode/...` files for examples.

Exploring these implementations can give you insight into the trade-offs between simplicity and flexibility in different routing solutions

## Contributing & Disclaimer
Want to contribute? Feel free to fork the repository, add new router packages, or improve existing implementations 
by submitting a pull request!

You can find each package’s implementation under the `src/Routers` directory. Keep in mind that the test setups are
based on official documentation and guides, but they may not always represent the absolute best or most optimized 
configuration for every router. Some routers required small adjustments to fit the test structure, but these should 
have minimal impact on performance.

Additionally, some routers offer caching or compilation features to improve speed, but these haven’t been tested 
yet—hopefully, a future test will cover this!


## Credits
- [Kristoffer Follestad](https://github.com/follestad)
- [Máté Kocsis](https://github.com/kocsismate)

A huge thanks to [Máté Kocsis](https://github.com/kocsismate) for the inspiration behind this project. Many parts of 
the implementation are based on his excellent work in [php-di-container-benchmarks](https://github.com/kocsismate/php-di-container-benchmarks).
