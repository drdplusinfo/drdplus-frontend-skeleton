<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\UsagePolicy;
use PHPUnit\Framework\TestCase;

class UsagePolicyTest extends TestCase
{
    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\ArticleNameCanNotBeEmptyForUsagePolicy
     */
    public function I_can_not_create_it_without_article_name(): void
    {
        new UsagePolicy('');
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function I_can_confirm_ownership_of_visitor(): void
    {
        $_COOKIE = [];
        $usagePolicy = new UsagePolicy('foo');
        self::assertNotEmpty($_COOKIE);
        self::assertSame('confirmedOwnershipOfFoo', $_COOKIE['ownershipCookieName']);
        self::assertSame('trialOfFoo', $_COOKIE['trialCookieName']);
        self::assertSame('trialExpiredAt', $_COOKIE['trialExpiredAtName']);
        self::assertArrayNotHasKey('confirmedOwnershipOfFoo', $_COOKIE);
        $usagePolicy->confirmOwnershipOfVisitor($expiresAt = new \DateTime());
        self::assertSame((string)$expiresAt->getTimestamp(), $_COOKIE['confirmedOwnershipOfFoo']);
    }
}
