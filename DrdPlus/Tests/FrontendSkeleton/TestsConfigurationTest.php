<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class TestsConfigurationTest extends AbstractContentTest
{
    /**
     * @test
     * @throws \ReflectionException
     */
    public function I_can_use_it(): void
    {
        $testsConfigurationReflection = new \ReflectionClass(static::getSutClass());
        $methods = $testsConfigurationReflection->getMethods(
            \ReflectionMethod::IS_PUBLIC ^ \ReflectionMethod::IS_STATIC ^ \ReflectionMethod::IS_ABSTRACT
        );
        $getters = [];
        $setters = [];
        $hasGetters = [];
        $disablingMethods = [];
        $setterReflections = [];
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (\strpos($methodName, 'get') === 0) {
                $getters[] = $methodName;
            } elseif (\strpos($methodName, 'has') === 0 || \strpos($methodName, 'can') === 0) {
                $hasGetters[] = $methodName;
            } elseif (\strpos($methodName, 'disable') === 0) {
                $disablingMethods[] = $methodName;
            } elseif (\strpos($methodName, 'set') === 0) {
                $setterReflections[] = $method;
                $setters[] = $methodName;
            }
        }
        $this->Every_boolean_setting_is_enabled_by_default($hasGetters);
        $this->Every_boolean_setting_can_be_disabled_by_specific_method($disablingMethods, $hasGetters);
        $this->I_can_call_disabling_methods_in_chain($disablingMethods);
        $this->I_can_set_what_i_can_get($getters, $setters);
        $this->I_can_call_setters_in_chain($setterReflections);
    }

    protected static function getSutClass(string $sutTestClass = null, string $regexp = '~(.+)Test$~'): string
    {
        return parent::getSutClass($sutTestClass, $regexp);
    }

    protected function createSut(): TestsConfiguration
    {
        $sutClass = static::getSutClass();

        return new $sutClass('https://example.com');
    }

    private function Every_boolean_setting_is_enabled_by_default(array $hasGetters): void
    {
        $testsConfiguration = $this->createSut();
        foreach ($hasGetters as $hasGetter) {
            self::assertTrue($testsConfiguration->$hasGetter(), "$hasGetter should return true by default to ensure strict mode");
        }
    }

    private function Every_boolean_setting_can_be_disabled_by_specific_method(array $disablingMethods, array $hasGetters): void
    {
        $testsConfiguration = $this->createSut();
        foreach ($disablingMethods as $disablingMethod) {
            $expectedHasGetter = \lcfirst(\preg_replace('~^disable~', '', $disablingMethod));
            self::assertContains(
                $expectedHasGetter,
                $hasGetters,
                "$disablingMethod does not match to any 'has' nor 'can' getter: " . \var_export($hasGetters, true)
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
        $testsConfiguration = $this->createSut();
        foreach ($disablingMethods as $disablingMethod) {
            self::assertSame(
                $testsConfiguration,
                $testsConfiguration->$disablingMethod(),
                "$disablingMethod should return the " . static::getSutClass() . ' to get fluent interface'
            );
        }
    }

    private function I_can_set_what_i_can_get(array $getters, array $setters): void
    {
        $expectedGetters = [];
        foreach ($setters as $setter) {
            $expectedGetters[] = 'get' . \substr($setter, 3);
        }
        $missingGetters = \array_diff($expectedGetters, $getters);
        self::assertSame([], $missingGetters, 'Some getters are missing');
        $expectedSetters = [];
        foreach ($getters as $getter) {
            $expectedSetters[] = 'set' . \substr($getter, 3);
        }
        $expectedSetters = \array_diff($expectedSetters, $this->getNonExistingSettersToSkip());
        $missingSetters = \array_diff($expectedSetters, $setters);
        self::assertSame([], $missingSetters, 'Some setters are missing');
    }

    protected function getNonExistingSettersToSkip(): array
    {
        return ['setLocalUrl'];
    }

    /**
     * @param array|\ReflectionMethod $setterReflections
     * @throws \LogicException
     */
    private function I_can_call_setters_in_chain(array $setterReflections): void
    {
        $testsConfiguration = $this->createSut();
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
                            $parameters[] = 'Some parameter 123.456';
                            break;
                        case 'array' :
                            $parameters[] = ['One of parameters 123.456'];
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
                "$setterReflection should return the " . static::getSutClass() . ' to get fluent interface'
            );
        }
    }

    /**
     * @test
     */
    public function I_can_add_allowed_calculation_id_prefix(): void
    {
        $testsConfiguration = new TestsConfiguration('https://example.com');
        $originalAllowedCalculationIdPrefixes = $testsConfiguration->getAllowedCalculationIdPrefixes();
        self::assertNotEmpty($originalAllowedCalculationIdPrefixes, 'Some allowed calculation ID prefixes expected');
        $returnedTestsConfiguration = $testsConfiguration->addAllowedCalculationIdPrefix('Foo allowed calculation id prefix');
        self::assertSame(
            $testsConfiguration,
            $returnedTestsConfiguration,
            'Method addAllowedCalculationIdPrefix should return ' . TestsConfiguration::class . ' instance for fluent interface'
        );
        $addedPrefixes = \array_values( // to re-index result from zero index
            \array_diff($testsConfiguration->getAllowedCalculationIdPrefixes(), $originalAllowedCalculationIdPrefixes)
        );
        self::assertSame(['Foo allowed calculation id prefix'], $addedPrefixes);
    }

    /**
     * @test
     * @expectedException \DrdPlus\Tests\FrontendSkeleton\Exceptions\AllowedCalculationPrefixShouldStartByUpperLetter
     * @expectedExceptionMessageRegExp ~říčany u čeho chceš~
     */
    public
    function I_can_not_add_allowed_calculation_id_prefix_with_lowercase_first_letter(): void
    {
        (new TestsConfiguration('https://example.com'))->addAllowedCalculationIdPrefix('říčany u čeho chceš');
    }

    /**
     * @test
     * @expectedException \DrdPlus\Tests\FrontendSkeleton\Exceptions\AllowedCalculationPrefixShouldStartByUpperLetter
     * @expectedExceptionMessageRegExp ~žbrdloch~
     */
    public
    function I_can_not_set_allowed_calculation_id_prefixes_with_even_single_one_with_lowercase_first_letter(): void
    {
        (new TestsConfiguration('https://example.com'))->setAllowedCalculationIdPrefixes([
            'Potvora na entou',
            'Kuloár',
            'žbrdloch',
        ]);
    }

    /**
     * @test
     */
    public
    function I_will_get_some_stable_version_if_has_more_versions(): void
    {
        $testsConfiguration = new TestsConfiguration('https://example.com');
        if ($this->isSkeletonChecked() && !$testsConfiguration->hasMoreVersions()) {
            self::assertSame('master', $testsConfiguration->getExpectedLastVersion(), 'Expected master as a single version');

            return;
        }
        self::assertTrue($testsConfiguration->hasMoreVersions(), 'More versions expected');
        self::assertRegExp(
            '~^\d+[.]\d+([.]\d+)?$~',
            $testsConfiguration->getExpectedLastVersion(),
            'Expected stable version in format x.y[.z]'
        );
    }

    /**
     * @test
     */
    public
    function I_can_get_last_unstable_version(): void
    {
        $testsConfiguration = new TestsConfiguration('https://example.com');
        self::assertSame('master', $testsConfiguration->getExpectedLastUnstableVersion());
        if (!$testsConfiguration->hasMoreVersions()) {
            self::assertSame(
                $testsConfiguration->getExpectedLastVersion(),
                $testsConfiguration->getExpectedLastUnstableVersion(),
                'Expected same last version and last unstable version as only a single version is expected'
            );
        }
    }
}