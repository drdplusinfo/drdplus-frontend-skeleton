<?php
namespace DrdPlus\Tests\FrontendSkeleton\Web;

use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\Web\Head;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class HeadTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function Head_contains_google_analytics_with_id(): void
    {
        /** @var Head $headClass */
        $headClass = static::getSutClass();
        $servicesContainer = $this->createServicesContainer();
        /** @var Head $head */
        $head = new $headClass($servicesContainer->getConfiguration(),
            $servicesContainer->getHtmlHelper(),
            $servicesContainer->getCssFiles(),
            $servicesContainer->getJsFiles()
        );
        $headString = $head->getHeadString();
        $htmlDocument = new HtmlDocument(<<<HTML
<!DOCTYPE html>
<html>
<head>
{$headString}
</head>
</html>
HTML
        );
        /** @var \DOMElement $googleAnalytics */
        $googleAnalytics = $htmlDocument->getElementById('google_analytics_id');
        self::assertNotEmpty($googleAnalytics);
        $src = $googleAnalytics->getAttribute('src');
        self::assertNotEmpty($src);
        $parsed = \parse_url($src);
        $queryString = \urldecode($parsed['query'] ?? '');
        self::assertSame('id=' . $this->getConfiguration()->getGoogleAnalyticsId(), $queryString);
    }

    /**
     * @test
     */
    public function I_can_set_own_page_title(): void
    {
        /** @var Head $headClass */
        $headClass = static::getSutClass();
        $servicesContainer = $this->createServicesContainer();
        /** @var Head $head */
        $head = new $headClass($servicesContainer->getConfiguration(),
            $servicesContainer->getHtmlHelper(),
            $servicesContainer->getCssFiles(),
            $servicesContainer->getJsFiles(),
            'foo BAR'
        );
        self::assertSame('foo BAR', $head->getPageTitle());
        self::assertContains('<title>foo BAR</title>', $head->getHeadString());
    }
}
