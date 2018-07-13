<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class TracyTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function Tracy_watch_it(): void
    {
        $content = $this->fetchContentFromLink($this->getTestsConfiguration()->getLocalUrl(), true)['content'];
        self::assertRegExp('~<script>\nTracy[.]Debug[.]init\([^\n]+\n</script>~', $content, 'Tracy debugger is not enabled');
    }
}