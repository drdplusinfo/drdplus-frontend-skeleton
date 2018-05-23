<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use PHPUnit\Framework\TestCase;

class SkeletonTestCase extends TestCase
{
    /** @var TestsConfiguration */
    private $testsConfiguration;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        global $testsConfiguration;
        $this->testsConfiguration = $testsConfiguration ?? new TestsConfiguration();
    }

    /**
     * @return TestsConfiguration
     */
    protected function getTestsConfiguration(): TestsConfiguration
    {
        return $this->testsConfiguration;
    }
}