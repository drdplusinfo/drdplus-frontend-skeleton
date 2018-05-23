<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use Granam\String\StringTools;
use PHPUnit\Framework\TestCase;

class TestsConfigurationTest extends TestCase
{

    /**
     * @test
     * @throws \ReflectionException
     */
    public function I_can_use_public_constant_to_set_every_config_parameter(): void
    {
        $reflectionClass = new \ReflectionClass(TestsConfiguration::class);
        $properties = $reflectionClass->getProperties();
        $constants = $reflectionClass->getConstants();
        $propertyNames = [];
        foreach ($properties as $property) {
            $propertyNames[] = $property->getName();
        }
        $constantNames = [];
        foreach ($constants as $constantName => $constantValue) {
            self::assertRegExp('~^[A-Z]+(_[A-Z]+)*$~', $constantName);
            self::assertRegExp('~^[a-z]+(_[a-z]+)*$~', $constantValue);
            self::assertSame($constantName, \strtoupper($constantValue));
            $constantNames[] = $constantName;
        }
        foreach ($propertyNames as $propertyName) {
            $expectedConstantName = StringTools::toConstantLikeName(StringTools::camelCaseToSnakeCase($propertyName));
            self::assertContains($expectedConstantName, $constantNames);
        }
        foreach ($constantNames as $constantName) {
            $expectedPropertyName = \lcfirst(
                \implode(\array_map('ucfirst', \array_map('strtolower', \explode('_', $constantName))))
            );
            self::assertContains($expectedPropertyName, $propertyNames);
        }
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function Every_boolean_setting_is_enabled_by_default(): void
    {
        $reflectionClass = new \ReflectionClass(TestsConfiguration::class);
        $methods = $reflectionClass->getMethods(
            \ReflectionMethod::IS_PUBLIC ^ \ReflectionMethod::IS_STATIC ^ \ReflectionMethod::IS_ABSTRACT
        );
        $hasGetters = [];
        foreach ($methods as $method) {
            if (\strpos($method->getName(), 'has') === 0) {
                $hasGetters[] = $method->getName();
            }
        }
        $testsConfiguration = new TestsConfiguration();
        foreach ($hasGetters as $hasGetter) {
            self::assertTrue($testsConfiguration->$hasGetter(), "$hasGetter should return true by default to ensure strict mode");
        }
    }
}
