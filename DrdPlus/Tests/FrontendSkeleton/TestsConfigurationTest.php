<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use PHPUnit\Framework\TestCase;

class TestsConfigurationTest extends TestCase
{
    /**
     * @test
     * @throws \ReflectionException
     */
    public function I_can_use_it(): void
    {
        $reflectionClass = new \ReflectionClass(TestsConfiguration::class);
        $methods = $reflectionClass->getMethods(
            \ReflectionMethod::IS_PUBLIC ^ \ReflectionMethod::IS_STATIC ^ \ReflectionMethod::IS_ABSTRACT
        );
        $hasGetters = [];
        $disablingMethods = [];
        $setterReflections = [];
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (\strpos($methodName, 'has') === 0) {
                $hasGetters[] = $methodName;
            } elseif (\strpos($methodName, 'disable') === 0) {
                $disablingMethods[] = $methodName;
            } elseif (\strpos($methodName, 'set') === 0) {
                $setterReflections[] = $method;
            }
        }
        $this->Every_boolean_setting_is_enabled_by_default($hasGetters);
        $this->Every_boolean_setting_can_be_disabled_by_specific_method($disablingMethods, $hasGetters);
        $this->I_can_call_disabling_methods_in_chain($disablingMethods);
        $this->I_can_call_setters_in_chain($setterReflections);
    }

    private function Every_boolean_setting_is_enabled_by_default(array $hasGetters): void
    {
        $testsConfiguration = new TestsConfiguration();
        foreach ($hasGetters as $hasGetter) {
            self::assertTrue($testsConfiguration->$hasGetter(), "$hasGetter should return true by default to ensure strict mode");
        }
    }

    private function Every_boolean_setting_can_be_disabled_by_specific_method(array $disablingMethods, array $hasGetters): void
    {
        $testsConfiguration = new TestsConfiguration();
        foreach ($disablingMethods as $disablingMethod) {
            $expectedHasGetter = \lcfirst(\preg_replace('~^disable~', '', $disablingMethod));
            self::assertContains(
                $expectedHasGetter,
                $hasGetters,
                "$disablingMethod does not match to any 'has' getter: " . \var_export($hasGetters, true)
            );
            self::assertTrue($testsConfiguration->$expectedHasGetter());
            $testsConfiguration->$disablingMethod();
            self::assertFalse($testsConfiguration->$expectedHasGetter(), "$disablingMethod does not changed the setting");
        }
        self::assertCount(
            \count($hasGetters),
            $disablingMethods,
            'Count of disabling methods should be equal to count of boolean setting getters'
            . '; disabling methods: ' . \print_r($disablingMethods, true) . '; boolean getters' . \print_r($hasGetters, true)
        );
    }

    private function I_can_call_disabling_methods_in_chain(array $disablingMethods): void
    {
        $testsConfiguration = new TestsConfiguration();
        foreach ($disablingMethods as $disablingMethod) {
            self::assertSame(
                $testsConfiguration,
                $testsConfiguration->$disablingMethod(),
                "$disablingMethod should return the " . TestsConfiguration::class . 'to get fluent interface'
            );
        }
    }

    /**
     * @param array|\ReflectionMethod $setterReflections
     * @throws \LogicException
     */
    private function I_can_call_setters_in_chain(array $setterReflections): void
    {
        $testsConfiguration = new TestsConfiguration();
        /** @var \ReflectionMethod $setterReflection */
        foreach ($setterReflections as $setterReflection) {
            $parameterReflections = $setterReflection->getParameters();
            $parameters = [];
            foreach ($parameterReflections as $parameterReflection) {
                if ($parameterReflection->allowsNull()) {
                    $parameters[] = null;
                } elseif ($parameterReflection->isDefaultValueAvailable()) {
                    $parameters[] = $parameterReflection->getDefaultValue();
                } elseif (!$parameterReflection->hasType()) {
                    $parameters[] = null;
                } elseif ($parameterReflection->getType()->isBuiltin()) {
                    switch ($parameterReflection->getType()->getName()) {
                        case 'bool' :
                            throw new \LogicException(
                                "{$setterReflection->getName()} should not be a setter but a disabling method"
                            );
                            break;
                        case 'int' :
                            $parameters[] = 123;
                            break;
                        case 'float' :
                            $parameters[] = 123.456;
                            break;
                        case 'string' :
                            $parameters[] = '123.456';
                            break;
                        case 'array' :
                            $parameters[] = [123.456];
                            break;
                        default :
                            throw new \LogicException(
                                "Do not know how to use parameter {$parameterReflection->getName()} of type {$parameterReflection->getType()} in method {$setterReflection->getName()}"
                            );
                    }
                } else {
                    throw new \LogicException(
                        "Do not know how to use parameter {$parameterReflection->getName()} of type {$parameterReflection->getType()} in method {$setterReflection->getName()}"
                    );
                }
            }
            self::assertSame(
                $testsConfiguration,
                $setterReflection->invokeArgs($testsConfiguration, $parameters),
                "$setterReflection should return the " . TestsConfiguration::class . 'to get fluent interface'
            );
        }
    }
}
