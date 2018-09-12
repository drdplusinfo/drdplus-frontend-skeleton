<?php
namespace DrdPlus\Tests\FrontendSkeleton\Partials;

interface TestsConfigurationReader
{
    public function getLocalUrl(): string;

    public function hasTables(): bool;

    public function getSomeExpectedTableIds(): array;

    public function hasExternalAnchorsWithHashes(): bool;

    public function hasMoreVersions(): bool;

    public function hasCustomBodyContent(): bool;

    public function hasNotes(): bool;

    public function hasIds(): bool;

    public function hasLocalLinks(): bool;

    public function hasLinksToAltar();

    public function getExpectedWebName(): string;

    public function getExpectedPageTitle(): string;

    public function getExpectedGoogleAnalyticsId(): string;

    public function getAllowedCalculationIdPrefixes(): array;

    public function getExpectedLastVersion(): string;

    public function getExpectedLastUnstableVersion(): string;

    public function hasHeadings(): bool;
}