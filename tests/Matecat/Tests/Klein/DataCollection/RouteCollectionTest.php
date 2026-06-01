<?php
/**
 * Klein (klein.php) - A fast & flexible router for PHP
 *
 * @author          Chris O'Hara <cohara87@gmail.com>
 * @author          Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @author          Domenico Lupinetti (Ostico <ostico@gmail.com>) (contributor and v3 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link            https://github.com/klein/klein.php
 * @license         MIT
 */

namespace Matecat\Tests\Klein\DataCollection;

use Klein\DataCollection\RouteCollection;
use Klein\Routes\Route;
use Matecat\Tests\Klein\AbstractKleinTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use InvalidArgumentException;

/**
 * RouteCollectionTest
 */
class RouteCollectionTest extends AbstractKleinTestCase
{

    /*
     * Data Providers and Methods
     */

    /**
     * @return array{0: array{Route, Route, Route}}
     * @throws InvalidArgumentException
     */
    public static function sampleDataProvider(): array
    {
        $sample_route = new Route(
            function () {
                echo 'woot!';
            },
            '/test/path',
            'PUT',
            'true'
        );

        $sample_other_route = new Route(
            function () {
                echo 'huh?';
            },
            '/test/dafuq',
            'HEAD',
            ''
        );

        $sample_named_route = new Route(
            function () {
                echo 'TREVOR!';
            },
            '/trevor/is/weird',
            'OPTIONS',
            null,
            false,
            'trevor'
        );


        return [[$sample_route, $sample_other_route, $sample_named_route]];
    }


    /*
     * Tests
     */

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('sampleDataProvider')]
    public function testSet(Route $sample_route, Route $sample_other_route, Route $sample_named_route): void
    {
        // Create our collection with NO data
        $routes = new RouteCollection();

        // Set our data from our test data
        $routes->set('first', $sample_route);

        $this->assertSame($sample_route, $routes->get('first'));
        $this->assertInstanceOf(Route::class, $routes->get('first'));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testSetCallableConvertsToRoute(): void
    {
        // Create our collection with NO data
        $routes = new RouteCollection();

        // Set our data
        $routes->set(
            'first',
            function () {
            }
        );

        $this->assertNotSame('value', $routes->get('first'));
        $this->assertInstanceOf(Route::class, $routes->get('first'));
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('sampleDataProvider')]
    public function testConstructorRoutesThroughAdd(Route $sample_route, Route $sample_other_route, Route $sample_named_route): void
    {
        $extra_route = new Route(
            function () {
            }
        );
        $array_of_route_instances = [
            'a' => $sample_route,
            'b' => $sample_other_route,
            'c' => $extra_route,
        ];

        // Create our collection
        $routes = new RouteCollection($array_of_route_instances);
        $this->assertSame(array_values($array_of_route_instances), array_values($routes->all()));
        $this->assertNotSame(array_keys($array_of_route_instances), $routes->keys());

        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('sampleDataProvider')]
    public function testAddRoute(Route $sample_route, Route $sample_other_route, Route $sample_named_route): void
    {
        $array_of_routes = [
            $sample_route,
            $sample_other_route,
        ];

        // Create our collection
        $routes = new RouteCollection();

        foreach ($array_of_routes as $route) {
            $routes->addRoute($route);
        }

        $this->assertSame($array_of_routes, array_values($routes->all()));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testAddCallableConvertsToRoute(): void
    {
        // Create our collection with NO data
        $routes = new RouteCollection();

        $callable = function () {
        };

        // Add our data
        $routes->add($callable);

        $this->assertNotSame($callable, current($routes->all()));
        $this->assertInstanceOf(Route::class, current($routes->all()));
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('sampleDataProvider')]
    public function testPrepareNamed(Route $sample_route, Route $sample_other_route, Route $sample_named_route): void
    {
        $array_of_routes = [
            'a' => $sample_route,
            'b' => $sample_other_route,
            'c' => $sample_named_route,
        ];

        $route_name = $sample_named_route->getName();
        $this->assertNotNull($route_name);

        // Create our collection
        $routes = new RouteCollection($array_of_routes);

        $original_keys = $routes->keys();

        // Prepare the named routes
        $routes->prepareNamed();

        $this->assertNotSame($original_keys, $routes->keys());
        $this->assertSame(count($original_keys), count($routes->keys()));
        $this->assertSame($sample_named_route, $routes->get($route_name));
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('sampleDataProvider')]
    public function testRouteOrderDoesntChangeAfterPreparing(Route $sample_route, Route $sample_other_route, Route $sample_named_route): void
    {
        $array_of_routes = ['a' => $sample_route, 'b' => $sample_other_route, 'c' => $sample_named_route];

        // Set the number of times we should loop
        $loop_num = 10;

        // Loop a set number of times to check different permutations
        for ($i = 0; $i < $loop_num; $i++) {
            // Shuffle the sample routes array
            $shuffled = $array_of_routes;
            shuffle($shuffled);

            // Create our collection and prepare the routes
            $routes = new RouteCollection(['a' => $shuffled[0], 'b' => $shuffled[1], 'c' => $shuffled[2]]);
            $routes->prepareNamed();

            $this->assertSame(
                array_values($routes->all()),
                $shuffled
            );
        }
    }
}
