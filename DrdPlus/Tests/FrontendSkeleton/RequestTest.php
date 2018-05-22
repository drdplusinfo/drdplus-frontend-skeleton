<?php
declare(strict_types=1); // on PHP 7+ are standard PHP methods strict to types of given parameters

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public static function getCrawlerUserAgents(): array
    {
        return [
            'Mozilla/5.0 (compatible; SeznamBot/3.2; +http://napoveda.seznam.cz/en/seznambot-intro/)',
            'User-Agent: Mozilla/5.0 (compatible; SeznamBot/3.2-test4; +http://napoveda.seznam.cz/en/seznambot-intro/)',
            'Googlebot'
        ];
    }

    /**
     * @test
     * @backupGlobals
     */
    public function I_can_detect_czech_seznam_bot(): void
    {
        $request = new Request();
        foreach (self::getCrawlerUserAgents() as $crawlerUserAgent) {
            self::assertTrue(
                $request->isVisitorBot($crawlerUserAgent),
                'Directly passed crawler has not been recognized: ' . $crawlerUserAgent
            );
            $_SERVER['HTTP_USER_AGENT'] = $crawlerUserAgent;
            self::assertTrue($request->isVisitorBot(), 'Crawler has not been recognized from HTTP_USER_AGENT: ' . $crawlerUserAgent);
        }
    }
}