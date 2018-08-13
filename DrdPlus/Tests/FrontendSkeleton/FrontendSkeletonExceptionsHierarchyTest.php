<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use Granam\Tests\ExceptionsHierarchy\Exceptions\AbstractExceptionsHierarchyTest;

class FrontendSkeletonExceptionsHierarchyTest extends AbstractExceptionsHierarchyTest
{
    protected function getTestedNamespace(): string
    {
        return $this->getRootNamespace();
    }

    protected function getRootNamespace(): string
    {
        return \str_replace('Tests\\', '', __NAMESPACE__);
    }

}