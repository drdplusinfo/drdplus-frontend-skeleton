<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlDocument;
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
        $content = $controller->getContent()->getStringContent();
        $firstWithoutRedirect = new HtmlDocument($content);
        $cacheId = $firstWithoutRedirect->documentElement->getAttribute('data-cache-id');
        self::assertNull($firstWithoutRedirect->getElementById('meta_redirect'));

        $controller->setRedirect(new Redirect('/foo', 12345));
        $content = $controller->getContent()->getStringContent();
        $firstWithRedirect = new HtmlDocument($content);
        self::assertSame($cacheId, $firstWithoutRedirect->documentElement->getAttribute('data-cache-id'));
        /** @var Element $redirectElement */
        $redirectElement = $firstWithRedirect->getElementById('meta_redirect');
        self::assertNotNull($redirectElement, 'Missing expected element with ID "meta_redirect"');
        self::assertSame('Refresh', $redirectElement->getAttribute('http-equiv'));
        self::assertSame('12345; url=/foo', $redirectElement->getAttribute('content'));

        $controller->setRedirect(new Redirect('/bar', 9999));
        $content = $controller->getContent()->getStringContent();
        $secondWithRedirect = new HtmlDocument($content);
        self::assertSame($cacheId, $secondWithRedirect->documentElement->getAttribute('data-cache-id'));
        /** @var Element $redirectElement */
        $redirectElement = $secondWithRedirect->getElementById('meta_redirect');
        self::assertNotNull($redirectElement);
        self::assertSame('Refresh', $redirectElement->getAttribute('http-equiv'));
        self::assertSame('9999; url=/bar', $redirectElement->getAttribute('content'));

        $controller = $this->createController(); // without redirect
        $content = $controller->getContent()->getStringContent();
        $secondWithoutRedirect = new HtmlDocument($content);
        self::assertSame($cacheId, $firstWithoutRedirect->documentElement->getAttribute('data-cache-id'));
        self::assertNull($secondWithoutRedirect->getElementById('meta_redirect'));
    }
}