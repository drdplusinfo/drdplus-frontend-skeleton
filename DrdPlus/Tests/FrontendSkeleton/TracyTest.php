<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use Tracy\Debugger;

class TracyTest extends AbstractContentTest
{
    /**
     * @test
     * @runInSeparateProcess
     */
    public function Tracy_watch_it(): void
    {
        \ob_start();
        include __DIR__ . '/../../../index.php';
        \ob_end_clean();
        self::assertTrue(Debugger::isEnabled(), 'Tracy debugger is not loaded');
    }
}