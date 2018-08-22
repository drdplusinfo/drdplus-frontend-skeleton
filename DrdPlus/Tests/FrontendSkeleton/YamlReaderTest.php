<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\YamlReader;
use PHPUnit\Framework\TestCase;

class YamlReaderTest extends TestCase
{
    use YamlFileTestTrait;

    /**
     * @test
     */
    public function I_can_get_values_from_yaml_file(): void
    {
        $yamlTestingDir = $this->getYamlTestingDir();
        $yamlFile = $this->createYamlLocalConfig($data = ['foo' => 'bar', 'baz' => ['qux' => true]], $yamlTestingDir);
        $yaml = new YamlReader($yamlFile);
        self::assertSame($data, $yaml->getValues());
        foreach ($data as $key => $value) {
            self::assertArrayHasKey($key, $yaml);
            self::assertSame($value, $yaml[$key]);
        }
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\YamlObjectContentIsReadOnly
     */
    public function I_can_not_set_value_on_yaml_object(): void
    {
        try {
            $yamlTestingDir = $this->getYamlTestingDir();
            $yamlFile = $this->createYamlLocalConfig([], $yamlTestingDir);
            $yaml = new YamlReader($yamlFile);
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getMessage());
        }
        $yaml['foo'] = 'bar';
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\YamlObjectContentIsReadOnly
     */
    public function I_can_not_remove_value_on_yaml_object(): void
    {
        try {
            $yamlTestingDir = $this->getYamlTestingDir();
            $yamlFile = $this->createYamlLocalConfig(['foo' => 'bar'], $yamlTestingDir);
            $yaml = new YamlReader($yamlFile);
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getMessage());
        }
        /** @noinspection PhpUndefinedVariableInspection */
        unset($yaml['foo']);
    }

}