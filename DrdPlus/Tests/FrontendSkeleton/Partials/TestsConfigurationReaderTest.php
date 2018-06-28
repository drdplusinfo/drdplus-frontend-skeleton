<?php
namespace DrdPlus\Tests\FrontendSkeleton\Partials;

use Granam\Tests\Tools\TestWithMockery;

class TestsConfigurationReaderTest extends TestWithMockery
{
    /**
     * @test
     * @throws \ReflectionException
     */
    public function Reader_interface_covers_all_tests_configuration_getters(): void
    {
        $testsConfigurationReaderReflection = new \ReflectionClass($this->getTestsConfigurationReaderClass());
        foreach ($this->getGettersFromTestsConfiguration() as $getter) {
            self::assertTrue(
                $testsConfigurationReaderReflection->hasMethod($getter),
                $this->getSutClass() . " is missing $getter method"
            );
            self::assertTrue(
                $testsConfigurationReaderReflection->getMethod($getter)->isPublic(),
                $this->getSutClass() . " should has $getter as public"
            );
        }
    }

    protected function getTestsConfigurationReaderClass(): string
    {
        return $this->getSutClass(null, '~Test$~');
    }

    /**
     * @return array|string[]
     * @throws \ReflectionException
     */
    protected function getGettersFromTestsConfiguration(): array
    {
        $testsConfigurationClass = $this->getTestsConfigurationClass();
        $testsConfigurationReflection = new \ReflectionClass($testsConfigurationClass);
        $methods = $testsConfigurationReflection->getMethods(\ReflectionMethod::IS_PUBLIC ^ \ReflectionMethod::IS_ABSTRACT);
        $getters = [];
        foreach ($methods as $method) {
            if ($method->getNumberOfParameters() > 0
                || !$method->hasReturnType()
                || $method->getReturnType()->getName() === $testsConfigurationClass
            ) {
                continue;
            }
            $getters[] = $method->getName();
        }

        return $getters;
    }

    protected function getTestsConfigurationClass(): string
    {
        return $this->getSutClass(null, '~\\\Partials(\\\.+)ReaderTest$~');
    }

}
