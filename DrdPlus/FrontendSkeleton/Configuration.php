<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class Configuration extends StrictObject
{
    public const CONFIG_LOCAL_YML = 'config.local.yml';
    public const CONFIG_DISTRIBUTION_YML = 'config.distribution.yml';

    public static function createFromYml(Dirs $dirs): Configuration
    {
        $localConfig = new Yaml($dirs->getDocumentRoot() . '/' . self::CONFIG_LOCAL_YML);
        $globalConfig = new Yaml($dirs->getDocumentRoot() . '/' . self::CONFIG_DISTRIBUTION_YML);
        $config = \array_merge($globalConfig->getValues(), $localConfig->getValues());

        return new static($dirs, $config);
    }

    public const WEB = 'web';
    public const LAST_STABLE_VERSION = 'last_stable_version';
    public const REPOSITORY_URL = 'repository_url';
    public const GOOGLE = 'google';
    public const ANALYTICS_ID = 'analytics_id';

    /** @var Dirs */
    private $dirs;
    /** @var array */
    private $settings;

    /**
     * @param Dirs $dirs
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidMinorVersion
     */
    public function __construct(Dirs $dirs, array $settings)
    {
        $this->dirs = $dirs;
        $this->guardValidLastMinorVersion($settings);
        $this->guardValidWebRepositoryUrl($settings);
        $this->guardValidGoogleAnalyticsId($settings);
        $this->settings = $settings;
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidMinorVersion
     */
    protected function guardValidLastMinorVersion(array $settings): void
    {
        if (!\preg_match('~^\d+[.]\d+$~', (string)($settings[self::WEB]['last_stable_version'] ?? ''))) {
            throw new Exceptions\InvalidMinorVersion(
                'Expected something like 1.13 in configuration web.last_stable_version, got ' . ($settings[self::WEB]['last_stable_version'] ?? 'nothing')
            );
        }
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidWebRepositoryUrl
     */
    protected function guardValidWebRepositoryUrl(array $settings): void
    {
        $repositoryUrl = $settings[self::WEB]['repository_url'] ?? '';
        if (!\preg_match('~^.+[.git]$~', $repositoryUrl) && !\file_exists($repositoryUrl)) {
            throw new Exceptions\InvalidWebRepositoryUrl(
                'Expected something git@github.com/foo/bar.git in configuration web.repository_url, got ' . ($repositoryUrl ?: 'nothing')
            );
        }
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidGoogleAnalyticsId
     */
    protected function guardValidGoogleAnalyticsId(array $settings): void
    {
        if (!\preg_match('~^UA-121206931-\d+$~', $settings['google']['analytics_id'] ?? '')) {
            throw new Exceptions\InvalidGoogleAnalyticsId(
                'Expected something like UA-121206931-1 in configuration google.analytics_id, got ' . ($settings['google']['analytics_id'] ?? 'nothing')
            );
        }
    }

    /**
     * @return Dirs
     */
    public function getDirs(): Dirs
    {
        return $this->dirs;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getWebLastStableVersion(): string
    {
        return $this->getSettings()[self::WEB][self::LAST_STABLE_VERSION];
    }

    public function getGoogleAnalyticsId(): string
    {
        return $this->getSettings()['google'][self::ANALYTICS_ID];
    }

    public function getWebRepositoryUrl(): string
    {
        return $this->getSettings()[self::WEB][self::REPOSITORY_URL];
    }

}