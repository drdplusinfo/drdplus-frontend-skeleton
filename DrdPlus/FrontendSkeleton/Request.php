<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;
use DeviceDetector\Parser\Bot;

class Request extends StrictObject
{

    public const VERSION = 'version';
    public const UPDATE = 'update';
    public const CACHE = 'cache';
    public const DISABLE = 'disable';
    public const TABLES = 'tables';
    public const TABULKY = 'tabulky';
    public const CONFIRM = 'confirm';

    /** @var Bot */
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

    public function isVisitorBot(string $userAgent = null): bool
    {
        $this->botParser->setUserAgent($userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->botParser->discardDetails();

        return (bool)$this->botParser->parse();
    }

    public function getCurrentUrl(array $parameters = []): string
    {
        if ($parameters === []) {
            return ($_SERVER['QUERY_STRING'] ?? '') !== ''
                ? '?' . $_SERVER['QUERY_STRING']
                : '';
        }
        $queryParameters = \array_merge($_GET ?? [], $parameters);

        return '?' . \http_build_query($queryParameters);
    }

    public function getValue(string $name): ?string
    {
        return $_GET[$name] ?? $_POST[$name] ?? $_COOKIE[$name] ?? null;
    }

    public function isCliRequest(): bool
    {
        return \PHP_SAPI === 'cli';
    }

    public function getValuesFromGet(): array
    {
        return $_GET ?? [];
    }

    public function getValueFromPost(string $name)
    {
        return $_POST[$name] ?? null;
    }

    public function getValueFromGet(string $name)
    {
        return $_GET[$name] ?? null;
    }

    /**
     * @return array|string[]
     */
    public function getWantedTablesIds(): array
    {
        $wantedTableIds = \array_map(
            function (string $id) {
                return \trim($id);
            },
            \explode(',', $_GET[self::TABLES] ?? $_GET[self::TABULKY] ?? '')
        );

        return \array_filter(
            $wantedTableIds,
            function (string $id) {
                return $id !== '';
            }
        );
    }

    public function areRequestedTables(): bool
    {
        return $this->getValueFromGet(self::TABLES) !== null || $this->getValueFromGet(self::TABULKY) !== null;
    }
}