<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;
use Tracy\Debugger;

class TracyTest extends AbstractContentTest
{
    /**
     * @test
     * @throws \ReflectionException
     */
    public function Tracy_watch_it(): void
    {
        $debuggerReflection = new \ReflectionClass(Debugger::class);
        $enabled = $debuggerReflection->getProperty('enabled');
        $enabled->setAccessible(true);
        $enabled->setValue(null, false);
        self::assertFalse(Debugger::isEnabled(), 'Tracy debugger is not expected to be enabled before index call');
        /** @noinspection PhpUnusedLocalVariableInspection */
        $latestVersion = 'master';
        \ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->getDocumentRoot() . '/index.php';
        \ob_end_clean();
        self::assertTrue(Debugger::isEnabled(), 'Tracy debugger is not enabled');
    }
}