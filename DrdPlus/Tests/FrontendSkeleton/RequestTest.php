<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DeviceDetector\Parser\Bot;
use DrdPlus\FrontendSkeleton\Request;
use Granam\Tests\Tools\TestWithMockery;

class RequestTest extends TestWithMockery
{
    public static function getCrawlerUserAgents(): array
    {
        return [
            'Mozilla/5.0 (compatible; SeznamBot/3.2; +http://napoveda.seznam.cz/en/seznambot-intro/)',
            'User-Agent: Mozilla/5.0 (compatible; SeznamBot/3.2-test4; +http://napoveda.seznam.cz/en/seznambot-intro/)',
            'Googlebot'
        ];
    }

    public static function getNonCrawlerUserAgents(): array
    {
        return [
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0', // Firefox
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36' // Chrome
        ];
    }

    /**
     * @test
     * @backupGlobals enabled
     */
    public function I_can_detect_czech_seznam_bot(): void
    {
        $request = new Request(new Bot());
        foreach (static::getCrawlerUserAgents() as $crawlerUserAgent) {
            self::assertTrue(
                $request->isVisitorBot($crawlerUserAgent),
                'Directly passed crawler has not been recognized: ' . $crawlerUserAgent
            );
            $_SERVER['HTTP_USER_AGENT'] = $crawlerUserAgent;
            self::assertTrue(
                $request->isVisitorBot(),
                'Crawler has not been recognized from HTTP_USER_AGENT: ' . $crawlerUserAgent
            );
        }
    }

    /**
     * @test
     * @backupGlobals enabled
     */
    public function I_do_not_get_non_bot_browsers_marked_as_bots(): void
    {
        $request = new Request(new Bot());
        foreach (static::getNonCrawlerUserAgents() as $nonCrawlerUserAgent) {
            self::assertFalse(
                $request->isVisitorBot($nonCrawlerUserAgent),
                'Directly passed browser has been wrongly marked as a bot: ' . $nonCrawlerUserAgent
            );
            $_SERVER['HTTP_USER_AGENT'] = $nonCrawlerUserAgent;
            self::assertFalse(
                $request->isVisitorBot(),
                'Browser has been wrongly marked as a bot from HTTP_USER_AGENT: ' . $nonCrawlerUserAgent
            );
        }
    }
}