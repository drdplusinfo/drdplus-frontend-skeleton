<?php
namespace DrdPlus\FrontendSkeleton\Partials;

interface CurrentMinorVersionProvider
{
    public function getCurrentMinorVersion(): string;
}