<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton\Partials;

use DrdPlus\Tests\FrontendSkeleton\TestsConfiguration;
use Granam\Tests\Tools\TestWithMockery;

abstract class SkeletonTestCase extends TestWithMockery
{
    /** @var TestsConfigurationReader */
    private $testsConfiguration;

    /**
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        global $testsConfiguration;
        $this->testsConfiguration = $testsConfiguration ?? new TestsConfiguration('https://example.com');
    }

    /**
     * @return TestsConfigurationReader
     */
    protected function getTestsConfiguration(): TestsConfigurationReader
    {
        return $this->testsConfiguration;
    }
}