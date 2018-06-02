<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use Granam\Scalar\Tools\ToString;
use Granam\Strict\Object\StrictObject;

class TestsConfiguration extends StrictObject
{
    // every setting SHOULD be strict (expecting instead of ignoring)

    /** @var bool */
    private $hasTables = true;
    /** @var array|string[] */
    private $someExpectedTableIds = ['IAmSoAlone'];
    /** @var bool */
    private $hasExternalAnchorsWithHashes = true;
    /** @var bool */
    private $hasMoreVersions = true;
    /** @var bool */
    private $hasCustomBodyContent = true;
    /** @var bool */
    private $hasNotes = true;
    /** @var bool */
    private $hasIds = true;
    /** @var bool */
    private $hasLinksToAltar = true;

    /**
     * @return bool
     */
    public function hasTables(): bool
    {
        return $this->hasTables;
    }

    /**
     * @return TestsConfiguration
     */
    public function disableHasTables(): TestsConfiguration
    {
        $this->hasTables = false;

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
     * @return TestsConfiguration
     */
    public function disableHasExternalAnchorsWithHashes(): TestsConfiguration
    {
        $this->hasExternalAnchorsWithHashes = false;

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
     * @return TestsConfiguration
     */
    public function disableHasMoreVersions(): TestsConfiguration
    {
        $this->hasMoreVersions = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasCustomBodyContent(): bool
    {
        return $this->hasCustomBodyContent;
    }

    /**
     * @return TestsConfiguration
     */
    public function disableHasCustomBodyContent(): TestsConfiguration
    {
        $this->hasCustomBodyContent = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasNotes(): bool
    {
        return $this->hasNotes;
    }

    /**
     * @return TestsConfiguration
     */
    public function disableHasNotes(): TestsConfiguration
    {
        $this->hasNotes = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasIds(): bool
    {
        return $this->hasIds;
    }

    /**
     * @return TestsConfiguration
     */
    public function disableHasIds(): TestsConfiguration
    {
        $this->hasIds = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasLinksToAltar(): bool
    {
        return $this->hasLinksToAltar;
    }

    /**
     * @return TestsConfiguration
     */
    public function disableHasLinksToAltar(): TestsConfiguration
    {
        $this->hasLinksToAltar = false;

        return $this;
    }
}