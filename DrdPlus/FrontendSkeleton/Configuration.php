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
        $config = \array_merge($localConfig->getValues(), $globalConfig->getValues());

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
        $this->settings = $settings;
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidMinorVersion
     */
    protected function guardValidLastMinorVersion(array $settings): void
    {
        if (!\preg_match('~^\d+[.]\d+$~', (string)($settings['web']['latest_version'] ?? ''))) {
            throw new Exceptions\InvalidMinorVersion(
                'Expected something like 1.13 in configuration, got ' . ($settings['web']['latest_version'] ?? 'nothing')
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
            throw new Exceptions\InvalidMinorVersion(
                'Expected something like UA-121206931-1 in configuration, got ' . ($settings['google']['analytics_id'] ?? 'nothing')
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

    public function getLatestVersion(): string
    {
        return $this->getSettings()['web']['latest_version'];
    }

    public function getGoogleAnalyticsId(): string
    {
        return $this->getSettings()['google']['analytics_id'];
    }

}