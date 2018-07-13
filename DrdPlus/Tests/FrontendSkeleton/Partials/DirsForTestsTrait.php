<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton\Partials;

trait DirsForTestsTrait
{

    protected function getDocumentRoot(): string
    {
        static $documentRoot;
        if ($documentRoot === null) {
            $documentRoot = \dirname(\DRD_PLUS_INDEX_FILE_NAME_TO_TEST);
        }

        return $documentRoot;
    }

    protected function getDirForVersions(): string
    {
        return $this->getDocumentRoot() . '/versions';
    }

    protected function getVendorRoot(): string
    {
        return $this->getDocumentRoot() . '/vendor';
    }

    protected function getWebRoot(): string
    {
        return $this->getDocumentRoot() . '/web';
    }

    protected function getPartsRoot(): string
    {
        return $this->getDocumentRoot() . '/parts';
    }

    protected function getGenericPartsRoot(): string
    {
        return __DIR__ . '/../../../../parts/frontend-skeleton';
    }

}