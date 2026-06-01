<?php
/**
 * Klein (klein.php) - A fast & flexible router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/klein/klein.php
 * @license     MIT
 */

namespace Matecat\Tests\Klein;

use Klein\AbstractRouteFactory;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\MockObject\TestStubBuilder;

/**
 * AbstractRouteFactoryTest
 */
class AbstractRouteFactoryTest extends AbstractKleinTestCase
{

    /**
     * Helpers
     */

    /**
     * @return list<non-empty-string>
     */
    protected function getDefaultMethodsToMock(): array
    {
        return array(
            'build',
        );
    }

    /**
     * @param list<non-empty-string>|null $methods_to_mock
     * @return TestStubBuilder<AbstractRouteFactory>
     */
    protected function getStubBuilderForFactory(?array $methods_to_mock = null): TestStubBuilder
    {
        $methods_to_mock = $methods_to_mock ?: $this->getDefaultMethodsToMock();

        return $this->getStubBuilder(AbstractRouteFactory::class)
            ->onlyMethods($methods_to_mock);
    }

    /**
     * Tests
     */

    public function testNamespaceGetSet(): void
    {
        // Test data
        $test_namespace = '/users';

        // Empty constructor
        /** @var AbstractRouteFactory&Stub $stub */
        $stub = $this->getStubBuilderForFactory()->getStub();

        $this->assertEmpty($stub->getNamespace());

        // Set in constructor
        /** @var AbstractRouteFactory&Stub $stub */
        $stub = $this->getStubBuilderForFactory()
            ->setConstructorArgs([$test_namespace,])
            ->getStub();

        $this->assertSame($test_namespace, $stub->getNamespace());

        // Set in method
        /** @var AbstractRouteFactory&Stub $stub */
        $stub = $this->getStubBuilderForFactory()->getStub();
        $stub->setNamespace($test_namespace);

        $this->assertSame($test_namespace, $stub->getNamespace());
    }

    public function testAppendNamespace(): void
    {
        // Test data
        $test_namespace = '/users';
        $test_namespace_append = '/names';

        /** @var AbstractRouteFactory&Stub $stub */
        $stub = $this->getStubBuilderForFactory()->getStub();
        $stub->setNamespace($test_namespace);
        $stub->appendNamespace($test_namespace_append);

        $this->assertSame(
            $test_namespace . $test_namespace_append,
            $stub->getNamespace()
        );
    }
}
