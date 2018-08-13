<?php
namespace DrdPlus\FrontendSkeleton\Partials;

interface CurrentVersionProvider
{
    public function getCurrentVersion(): string;
}