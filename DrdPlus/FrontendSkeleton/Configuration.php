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
        $globalConfig = new YamlReader($dirs->getDocumentRoot() . '/' . static::CONFIG_DISTRIBUTION_YML);
        $config = $globalConfig->getValues();
        $localConfigFile = $dirs->getDocumentRoot() . '/' . static::CONFIG_LOCAL_YML;
        if (\file_exists($localConfigFile)) {
            $localConfig = new YamlReader($dirs->getDocumentRoot() . '/' . static::CONFIG_LOCAL_YML);
            $config = \array_replace_recursive($config, $localConfig->getValues());
        }

        return new static($dirs, $config);
    }

    // web
    public const WEB = 'web';
    public const LAST_STABLE_VERSION = 'last_stable_version';
    public const REPOSITORY_URL = 'repository_url';
    public const MENU_POSITION_FIXED = 'menu_position_fixed';
    public const SHOW_HOME_BUTTON = 'show_home_button';
    public const NAME = 'name';
    public const TITLE_SMILEY = 'title_smiley';
    // google
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
        $this->guardSetIfUseFixedMenuPosition($settings);
        $this->guardSetIfShowHomeButton($settings);
        $this->guardNonEmptyWebName($settings);
        $this->guardSetTitleSmiley($settings);
        $this->settings = $settings;
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidMinorVersion
     */
    protected function guardValidLastMinorVersion(array $settings): void
    {
        if (!\preg_match('~^(\d+[.]\d+|master)$~', (string)($settings[static::WEB][static::LAST_STABLE_VERSION] ?? ''))) {
            throw new Exceptions\InvalidMinorVersion(
                'Expected something like 1.13 or master in configuration web.last_stable_version, got '
                . ($settings[static::WEB]['last_stable_version'] ?? 'nothing')
            );
        }
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidWebRepositoryUrl
     */
    protected function guardValidWebRepositoryUrl(array $settings): void
    {
        $repositoryUrl = $settings[static::WEB][static::REPOSITORY_URL] ?? '';
        if (!\preg_match('~^.+[.git]$~', $repositoryUrl) && !\file_exists($repositoryUrl)) {
            throw new Exceptions\InvalidWebRepositoryUrl(
                'Expected something like git@github.com/foo/bar.git in configuration web.repository_url, got '
                . ($repositoryUrl
                    ? ('non-valid URL, non-existing dir ' . $repositoryUrl)
                    : 'nothing'
                )
            );
        }
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidGoogleAnalyticsId
     */
    protected function guardValidGoogleAnalyticsId(array $settings): void
    {
        if (!\preg_match('~^UA-121206931-\d+$~', $settings[static::GOOGLE][static::ANALYTICS_ID] ?? '')) {
            throw new Exceptions\InvalidGoogleAnalyticsId(
                'Expected something like UA-121206931-1 in configuration google.analytics_id, got ' . ($settings[static::GOOGLE][static::ANALYTICS_ID] ?? 'nothing')
            );
        }
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidMenuPosition
     */
    protected function guardSetIfUseFixedMenuPosition(array $settings): void
    {
        if (($settings[static::WEB][static::MENU_POSITION_FIXED] ?? null) === null) {
            throw new Exceptions\InvalidMenuPosition(
                'Expected explicitly defined menu position fix to true or false in configuration web.menu_position_fixed, got nothing'
            );
        }
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidShowOfHomeButton
     */
    protected function guardSetIfShowHomeButton(array $settings): void
    {
        if (($settings[static::WEB][static::SHOW_HOME_BUTTON] ?? null) === null) {
            throw new Exceptions\InvalidShowOfHomeButton(
                'Expected explicitly defined if show home button to true or false in configuration web.show_home_button, got nothing'
            );
        }
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\MissingWebName
     */
    protected function guardNonEmptyWebName(array $settings): void
    {
        if (($settings[static::WEB][static::NAME] ?? '') === '') {
            throw new Exceptions\MissingWebName(
                'Expected some web name in configuration web.name'
            );
        }
    }

    /**
     * @param array $settings
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\TitleSmileyIsNotSet
     */
    protected function guardSetTitleSmiley(array $settings): void
    {
        if (!\array_key_exists(static::TITLE_SMILEY, $settings[static::WEB])) {
            throw new Exceptions\TitleSmileyIsNotSet(
                'Title smiley should be set in configuration web.title_smiley, even if just an empty string'
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

    public function getWebLastStableMinorVersion(): string
    {
        return $this->getSettings()[static::WEB][static::LAST_STABLE_VERSION];
    }

    public function getGoogleAnalyticsId(): string
    {
        return $this->getSettings()['google'][static::ANALYTICS_ID];
    }

    public function getWebRepositoryUrl(): string
    {
        return $this->getSettings()[static::WEB][static::REPOSITORY_URL];
    }

    public function isMenuPositionFixed(): bool
    {
        return (bool)$this->getSettings()[static::WEB][static::MENU_POSITION_FIXED];
    }

    public function isShowHomeButton(): bool
    {
        return (bool)$this->getSettings()[static::WEB][static::SHOW_HOME_BUTTON];
    }

    public function getWebName(): string
    {
        return $this->getSettings()[static::WEB][static::NAME];
    }

    public function getTitleSmiley(): string
    {
        return (string)$this->getSettings()[static::WEB][static::TITLE_SMILEY];
    }
}