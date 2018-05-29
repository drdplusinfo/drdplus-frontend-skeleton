<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use Granam\Scalar\Tools\ToString;
use Granam\Strict\Object\StrictObject;
use Granam\String\StringTools;

class TestsConfiguration extends StrictObject
{
    public const HAS_TABLES = 'has_tables';
    public const SOME_EXPECTED_TABLE_IDS = 'some_expected_table_ids';
    public const HAS_EXTERNAL_ANCHORS_WITH_HASHES = 'has_external_anchors_with_hashes';
    public const HAS_MORE_VERSIONS = 'has_more_versions';

    // every setting SHOULD be strict (expecting instead of ignoring)

    /** @var bool */
    private $hasTables = true;
    /** @var array|string[] */
    private $someExpectedTableIds = ['IAmSoAlone'];
    /** @var bool */
    private $hasExternalAnchorsWithHashes = true;
    /** @var bool */
    private $hasMoreVersions = true;

    public function __construct(array $configuration = [])
    {
        foreach ($configuration as $settingName => $value) {
            $setterForName = StringTools::assembleSetterForName($settingName);
            if (!\method_exists($this, $setterForName)) {
                throw new \LogicException("Unknown configuration '{$settingName}' with value " . \var_export($value, true));
            }
            $this->$setterForName($value);
        }
    }

    /**
     * @return bool
     */
    public function hasTables(): bool
    {
        return $this->hasTables;
    }

    /**
     * @param bool $hasTables
     * @return TestsConfiguration
     */
    public function setHasTables(bool $hasTables): TestsConfiguration
    {
        $this->hasTables = $hasTables;

        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getSomeExpectedTableIds(): array
    {
        return $this->someExpectedTableIds;
    }

    /**
     * @param array|string[] $someExpectedTableIds
     * @return TestsConfiguration
     */
    public function setSomeExpectedTableIds(array $someExpectedTableIds): TestsConfiguration
    {
        $this->someExpectedTableIds = [];
        foreach ($someExpectedTableIds as $someExpectedTableId) {
            $this->someExpectedTableIds[] = ToString::toString($someExpectedTableId);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasExternalAnchorsWithHashes(): bool
    {
        return $this->hasExternalAnchorsWithHashes;
    }

    /**
     * @param bool $hasExternalAnchorsWithHashes
     * @return TestsConfiguration
     */
    public function setHasExternalAnchorsWithHashes(bool $hasExternalAnchorsWithHashes): TestsConfiguration
    {
        $this->hasExternalAnchorsWithHashes = $hasExternalAnchorsWithHashes;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasMoreVersions(): bool
    {
        return $this->hasMoreVersions;
    }

    /**
     * @param bool $hasMoreVersions
     * @return TestsConfiguration
     */
    public function setHasMoreVersions(bool $hasMoreVersions): TestsConfiguration
    {
        $this->hasMoreVersions = $hasMoreVersions;

        return $this;
    }
}