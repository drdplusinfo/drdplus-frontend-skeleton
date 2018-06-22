<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Cookie;
use Granam\Tests\Tools\TestWithMockery;

class CookieTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_set_get_and_delete_cookie(): void
    {
        self::assertNull(Cookie::getCookie('foo'));
        self::assertTrue(Cookie::setCookie('foo', 'bar'));
        self::assertSame('bar', Cookie::getCookie('foo'));
        self::assertSame('bar', $_COOKIE['foo'] ?? false);
        self::assertTrue(Cookie::deleteCookie('foo'));
        self::assertNull(Cookie::getCookie('foo'));
        self::assertFalse(\array_key_exists('foo', $_COOKIE), 'Cookie should be removed from global array as well');
    }
}