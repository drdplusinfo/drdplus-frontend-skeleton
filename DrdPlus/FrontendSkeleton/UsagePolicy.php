<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class UsagePolicy extends StrictObject
{
    /**
     * @var string
     */
    private $articleName;

    /**
     * @param string $articleName
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ArticleNameCanNotBeEmptyForUsagePolicy
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CookieCanNotBeSet
     */
    public function __construct(string $articleName)
    {
        $articleName = trim($articleName);
        if ($articleName === '') {
            throw new Exceptions\ArticleNameCanNotBeEmptyForUsagePolicy('Name of the article to confirm ownership can not be empty');
        }
        $this->articleName = $articleName;
        if (!\headers_sent()) {
            $this->setCookie('ownershipCookieName', $this->getOwnershipCookieName(), null /* expire on session end*/);
            $this->setCookie('trialCookieName', $this->getTrialCookieName(), null /* expire on session end*/);
            $this->setCookie('trialExpiredAtName', 'trialExpiredAt', null /* expire on session end*/);
        }
    }

    /**
     * @param string $cookieName
     * @param string $value
     * @param \DateTime|null $expiresAt
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CookieCanNotBeSet
     */
    private function setCookie(string $cookieName, string $value, ?\DateTime $expiresAt): bool
    {
        $cookieSet = \setcookie(
            $cookieName,
            $value,
            $expiresAt ? $expiresAt->getTimestamp() : 0 /* ends with browser session */,
            '/', // path
            $_SERVER['SERVER_NAME'] ?? '', // domain
            !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // secure if possible
            false /* not HTTP only to allow JS to read it */
        );
        if (!$cookieSet) {
            throw new Exceptions\CookieCanNotBeSet('Could not set cookie ' . $cookieName);
        }
        $_COOKIE[$cookieName] = $value;

        return true;
    }

    /**
     * @return bool
     */
    public function hasVisitorConfirmedOwnership(): bool
    {
        return !empty($_COOKIE[$this->getOwnershipCookieName()]);
    }

    /**
     * @return string
     */
    private function getOwnershipCookieName(): string
    {
        return \str_replace('.', '_', 'confirmedOwnershipOf' . ucfirst($this->articleName));
    }

    /**
     * @param \DateTime $expiresAt
     * @return bool
     * @throws \RuntimeException
     */
    public function confirmOwnershipOfVisitor(\DateTime $expiresAt): bool
    {
        return $this->setCookie($this->getOwnershipCookieName(), (string)$expiresAt->getTimestamp(), $expiresAt);
    }

    /**
     * @return bool
     */
    public function isVisitorUsingTrial(): bool
    {
        return !empty($_COOKIE[$this->getTrialCookieName()]);
    }

    /**
     * @return string
     */
    public function getTrialCookieName(): string
    {
        return \str_replace('.', '_', 'trialOf' . ucfirst($this->articleName));
    }

    /**
     * @param \DateTime $expiresAt
     * @return bool
     * @throws \RuntimeException
     */
    public function activateTrial(\DateTime $expiresAt): bool
    {
        return $this->setCookie($this->getTrialCookieName(), (string)$expiresAt->getTimestamp(), $expiresAt);
    }

    public function trialJustExpired(): bool
    {
        // expired before 5 seconds or less
        return !empty($_GET['trialExpiredAt']) && ($_GET['trialExpiredAt'] + 5) >= \time();
    }
}