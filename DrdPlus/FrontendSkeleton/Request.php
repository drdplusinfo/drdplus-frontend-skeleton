<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;
use DeviceDetector\Parser\Bot;

class Request extends StrictObject
{
    /**
     * @var Bot
     */
    private $botParser;

    public function __construct(Bot $botParser)
    {
        $this->botParser = $botParser;
    }

    public function getServerUrl(): string
    {
        $protocol = 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $protocol = 'https';
        } elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
            $protocol = $_SERVER['REQUEST_SCHEME'];
        }
        if (empty($_SERVER['SERVER_NAME'])) {
            return '';
        }
        $port = 80;
        if (!empty($_SERVER['SERVER_PORT']) && \is_numeric($_SERVER['SERVER_PORT'])) {
            $port = (int)$_SERVER['SERVER_PORT'];
        }
        $portString = $port === 80 || $port === 443
            ? ''
            : (':' . $port);

        return "{$protocol}://{$_SERVER['SERVER_NAME']}{$portString}";
    }

    public function getRequestRelativeRootUrl(): string
    {
        return $this->getServerUrl() . '/' . \rtrim(\dirname($_SERVER['REQUEST_URI'], '/')); /* to get index relative to current URl */
    }

    public function isVisitorBot(string $userAgent = null): bool
    {
        $this->botParser->setUserAgent($userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->botParser->discardDetails();

        return (bool)$this->botParser->parse();
    }

    public function getCurrentUrl(array $parameters = []): string
    {
        if ($parameters === []) {
            return $_SERVER['QUERY_STRING'] !== []
                ? '?' . $_SERVER['QUERY_STRING']
                : '';
        }
        $queryParameters = \array_merge($_GET, $parameters);

        return '?' . \http_build_query($queryParameters);
    }

}