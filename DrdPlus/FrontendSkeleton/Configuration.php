<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class Configuration extends StrictObject
{
    public static function createFromYml(Dirs $dirs): Configuration
    {
        $localConfig = new Yaml($dirs->getDocumentRoot() . '/config.local.yml');
        $globalConfig = new Yaml($dirs->getDocumentRoot() . '/config.distribution.yml');
        $config = \array_merge($globalConfig->getValues(), $localConfig->getValues());

        return new static($dirs, $config);
    }

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
        $this->guardValidGoogleAnalyticsId($settings);
        $this->guardValidWebRepositoryUrl($settings);
        $this->settings = $settings;
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidMinorVersion
     */
    protected function guardValidLastMinorVersion(array $settings): void
    {
        if (!\preg_match('~^\d+[.]\d+$~', (string)($settings['web']['last_stable_version'] ?? ''))) {
            throw new Exceptions\InvalidMinorVersion(
                'Expected something like 1.13 in configuration, got ' . ($settings['web']['last_stable_version'] ?? 'nothing')
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
                'Expected something like UA-121206931-1 in configuration, got ' . ($settings['google']['analytics_id'] ?? 'nothing')
            );
        }
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidWebRepositoryUrl
     */
    protected function guardValidWebRepositoryUrl(array $settings): void
    {
        $repositoryUrl = $settings['web']['repository_url'] ?? '';
        if (!\preg_match('~^.+[.git]$~', $repositoryUrl) && !\file_exists($repositoryUrl)) {
            throw new Exceptions\InvalidWebRepositoryUrl(
                'Expected something git@github.com/foo/bar.git in configuration, got ' . ($repositoryUrl ?: 'nothing')
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

    public function getLastStableVersion(): string
    {
        return $this->getSettings()['web']['last_stable_version'];
    }

    public function getGoogleAnalyticsId(): string
    {
        return $this->getSettings()['google']['analytics_id'];
    }

    public function getWebRepositoryUrl(): string
    {
        return $this->getSettings()['web']['repository_url'];
    }

}