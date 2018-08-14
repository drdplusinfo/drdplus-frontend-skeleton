<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Dirs;
use DrdPlus\FrontendSkeleton\FrontendController;
use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use DrdPlus\FrontendSkeleton\Redirect;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;
use Gt\Dom\Element;

class TrialTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_will_get_cached_content_with_injected_trial_timeout(): void
    {
        $controller = $this->createController();
        $content = require $controller->getConfiguration()->getDirs()->getGenericPartsRoot() . '/content.php';
        $firstWithoutRedirect = new HtmlDocument($content);
        $cacheId = $firstWithoutRedirect->documentElement->getAttribute('data-cache-id');
        self::assertNull($firstWithoutRedirect->getElementById('meta_redirect'));

        $controller->setRedirect(new Redirect('/foo', 12345));
        $content = require $controller->getConfiguration()->getDirs()->getGenericPartsRoot() . '/content.php';
        $firstWithRedirect = new HtmlDocument($content);
        self::assertSame($cacheId, $firstWithoutRedirect->documentElement->getAttribute('data-cache-id'));
        /** @var Element $redirectElement */
        $redirectElement = $firstWithRedirect->getElementById('meta_redirect');
        self::assertNotNull($redirectElement);
        self::assertSame('Refresh', $redirectElement->getAttribute('http-equiv'));
        self::assertSame('12345; url=/foo', $redirectElement->getAttribute('content'));

        $controller->setRedirect(new Redirect('/bar', 9999));
        $content = require $controller->getConfiguration()->getDirs()->getGenericPartsRoot() . '/content.php';
        $secondWithRedirect = new HtmlDocument($content);
        self::assertSame($cacheId, $secondWithRedirect->documentElement->getAttribute('data-cache-id'));
        /** @var Element $redirectElement */
        $redirectElement = $secondWithRedirect->getElementById('meta_redirect');
        self::assertNotNull($redirectElement);
        self::assertSame('Refresh', $redirectElement->getAttribute('http-equiv'));
        self::assertSame('9999; url=/bar', $redirectElement->getAttribute('content'));

        $controller = $this->createController(); // without redirect
        $content = require $controller->getConfiguration()->getDirs()->getGenericPartsRoot() . '/content.php';
        $secondWithoutRedirect = new HtmlDocument($content);
        self::assertSame($cacheId, $firstWithoutRedirect->documentElement->getAttribute('data-cache-id'));
        self::assertNull($secondWithoutRedirect->getElementById('meta_redirect'));
    }

    protected function createController(): FrontendController
    {
        $dirs = $this->createDirs();

        return new FrontendController($this->createConfiguration($dirs), new HtmlHelper($dirs, true, false, false, false));
    }
}