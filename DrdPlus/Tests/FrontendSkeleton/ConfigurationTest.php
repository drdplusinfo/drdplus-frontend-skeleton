<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Configuration;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class ConfigurationTest extends AbstractContentTest
{

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
     * @dataProvider provideCompleteYamlContent
     * @param array $localYamlContent
     * @param array $distributionYamlContent
     * @param array $expectedYamlContent
     */
    public function I_can_create_it_from_yaml_files(array $localYamlContent, array $distributionYamlContent, array $expectedYamlContent): void
    {
        $testingDir = \sys_get_temp_dir() . '/' . \uniqid('configuration_test', true);
        self::assertTrue(\mkdir($testingDir), 'Testing temporary dir can not be created: ' . $testingDir);
        $localTempYaml = $testingDir . '/' . Configuration::CONFIG_LOCAL_YML;
        $distributionTempYaml = $testingDir . '/' . Configuration::CONFIG_DISTRIBUTION_YML;
        $this->createYamlFile($localTempYaml, $localYamlContent);
        $this->createYamlFile($distributionTempYaml, $distributionYamlContent);
        $configuration = Configuration::createFromYml($dirs = $this->createDirs($testingDir));
        \unlink($localTempYaml);
        \unlink($distributionTempYaml);
        self::assertSame($expectedYamlContent[Configuration::WEB][Configuration::LAST_STABLE_VERSION], $configuration->getWebLastStableVersion());
        self::assertSame($expectedYamlContent[Configuration::WEB][Configuration::REPOSITORY_URL], $configuration->getWebRepositoryUrl());
        self::assertSame($expectedYamlContent[Configuration::GOOGLE][Configuration::ANALYTICS_ID], $configuration->getGoogleAnalyticsId());
        self::assertSame($dirs, $configuration->getDirs());
    }

    public function provideCompleteYamlContent(): array
    {
        $yamlContent = ['web' => ['last_stable_version' => '123.456', 'repository_url' => \sys_get_temp_dir()], 'google' => ['analytics_id' => 'UA-121206931-999']];

        return [
            [$yamlContent, [], $yamlContent],
            [[], $yamlContent, $yamlContent],
        ];
    }

    private function createYamlFile(string $file, array $data): void
    {
        self::assertTrue(\yaml_emit_file($file, $data), 'Yaml file has not been created: ' . $file);
    }
}