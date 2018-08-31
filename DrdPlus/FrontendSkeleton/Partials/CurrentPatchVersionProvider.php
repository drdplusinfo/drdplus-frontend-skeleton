<?php
namespace DrdPlus\FrontendSkeleton\Partials;

interface CurrentPatchVersionProvider
{
    public function getCurrentPatchVersion(): string;
}