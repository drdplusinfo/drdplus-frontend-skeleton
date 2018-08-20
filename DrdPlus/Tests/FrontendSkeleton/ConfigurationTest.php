<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Configuration;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class ConfigurationTest extends AbstractContentTest
{
    use YamlFileTestTrait;

    /**
     * @test
     */
    public function I_can_use_both_config_distribution_as_well_as_local_yaml_files(): void
    {
        self::assertFileExists($this->getDocumentRoot() . '/' . Configuration::CONFIG_LOCAL_YML);
        self::assertFileExists($this->getDocumentRoot() . '/' . Configuration::CONFIG_DISTRIBUTION_YML);
    }

    /**
     * @test
     * @dataProvider provideCompleteLocalAndDistributionYamlContent
     * @param array $localYamlContent
     * @param array $distributionYamlContent
     * @param array $expectedYamlContent
     */
    public function I_can_create_it_from_yaml_files(array $localYamlContent, array $distributionYamlContent, array $expectedYamlContent): void
    {
        $yamlTestingDir = $this->getYamlTestingDir();
        $this->createYamlLocalConfig($localYamlContent, $yamlTestingDir);
        $this->createYamlDistributionConfig($distributionYamlContent, $yamlTestingDir);
        $configuration = Configuration::createFromYml($dirs = $this->createDirs($yamlTestingDir));
        self::assertSame($expectedYamlContent, $configuration->getSettings());
        self::assertSame($expectedYamlContent[Configuration::WEB][Configuration::LAST_STABLE_VERSION], $configuration->getWebLastStableVersion());
        self::assertSame($expectedYamlContent[Configuration::WEB][Configuration::REPOSITORY_URL], $configuration->getWebRepositoryUrl());
        self::assertSame($expectedYamlContent[Configuration::GOOGLE][Configuration::ANALYTICS_ID], $configuration->getGoogleAnalyticsId());
        self::assertSame($dirs, $configuration->getDirs());
    }

    public function provideCompleteLocalAndDistributionYamlContent(): array
    {
        $completeYamlContent = $this->getCompleteYamlContent();
        $limitedWebSection = $completeYamlContent;
        $limitedWebSection[Configuration::WEB] = [Configuration::LAST_STABLE_VERSION => '456.789'];
        $changedCompleteYamlContent = $completeYamlContent;
        $changedCompleteYamlContent[Configuration::WEB][Configuration::LAST_STABLE_VERSION] = '456.789';

        return [
            [$completeYamlContent, [], $completeYamlContent],
            [$limitedWebSection, $completeYamlContent, $changedCompleteYamlContent],
            [$completeYamlContent, $limitedWebSection, $completeYamlContent],
        ];
    }

    private function getCompleteYamlContent(): array
    {
        return [
            Configuration::WEB => [
                Configuration::LAST_STABLE_VERSION => '123.456',
                Configuration::REPOSITORY_URL => \sys_get_temp_dir(),
                Configuration::MENU_POSITION_FIXED => false,
                Configuration::SHOW_HOME_BUTTON => true,
            ],
            Configuration::GOOGLE => [Configuration::ANALYTICS_ID => 'UA-121206931-999']
        ];
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\InvalidMinorVersion
     * @expectedExceptionMessageRegExp ~public enemy~
     */
    public function I_can_not_create_it_with_invalid_last_stable_version(): void
    {
        $yamlContent = $this->getCompleteYamlContent();
        $yamlContent[Configuration::WEB][Configuration::LAST_STABLE_VERSION] = 'public enemy';
        new Configuration($this->createDirs(), $yamlContent);
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\InvalidWebRepositoryUrl
     * @expectedExceptionMessageRegExp ~/somewhere://over[.]the\?rainbow=GPS~
     */
    public function I_can_not_create_it_with_invalid_web_repository_url(): void
    {
        $yamlContent = $this->getCompleteYamlContent();
        $yamlContent[Configuration::WEB][Configuration::REPOSITORY_URL] = '/somewhere://over.the?rainbow=GPS';
        new Configuration($this->createDirs(), $yamlContent);
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\InvalidGoogleAnalyticsId
     * @expectedExceptionMessageRegExp ~GoogleItself~
     */
    public function I_can_not_create_it_with_invalid_google_analytics_id(): void
    {
        $yamlContent = $this->getCompleteYamlContent();
        $yamlContent[Configuration::GOOGLE][Configuration::ANALYTICS_ID] = 'GoogleItself';
        new Configuration($this->createDirs(), $yamlContent);
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\InvalidMenuPosition
     */
    public function I_can_not_create_it_without_defining_if_menu_should_be_fixed(): void
    {
        $yamlContent = $this->getCompleteYamlContent();
        unset($yamlContent[Configuration::WEB][Configuration::MENU_POSITION_FIXED]);
        new Configuration($this->createDirs(), $yamlContent);
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\InvalidShowOfHomeButton
     */
    public function I_can_not_create_it_without_defining_if_show_home_button(): void
    {
        $yamlContent = $this->getCompleteYamlContent();
        unset($yamlContent[Configuration::WEB][Configuration::SHOW_HOME_BUTTON]);
        new Configuration($this->createDirs(), $yamlContent);
    }
}