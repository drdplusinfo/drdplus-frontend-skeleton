<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class YamlReader extends StrictObject implements \ArrayAccess
{
    /** @var string */
    private $yamlFile;
    /** @var array */
    private $values;

    public function __construct(string $yamlFile)
    {
        $this->yamlFile = $yamlFile;
        $this->values = $this->fetchValues();
    }

    /**
     * @return array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotReadYamlFile
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotParseYamlFile
     */
    private function fetchValues(): array
    {
        $yamlContent = \file_get_contents($this->yamlFile);
        if ($yamlContent === false) {
            throw new Exceptions\CanNotReadYamlFile("Can not parse content '{$yamlContent}' of YAML file '{$this->yamlFile}'");
        }
        if (!$yamlContent) {
            return [];
        }
        $values = \yaml_parse($yamlContent);
        if ($values !== false) {
            return $values;
        }
        throw new Exceptions\CanNotParseYamlFile("Can not parse content '{$yamlContent}' of YAML file '{$this->yamlFile}'");
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->getValues());
    }

    public function offsetGet($offset)
    {
        return $this->getValues()[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\YamlObjectContentIsReadOnly
     */
    public function offsetSet($offset, $value): void
    {
        throw new Exceptions\YamlObjectContentIsReadOnly('Content of ' . static::class . ' can not be changed');
    }

    /**
     * @param mixed $offset
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\YamlObjectContentIsReadOnly
     */
    public function offsetUnset($offset): void
    {
        throw new Exceptions\YamlObjectContentIsReadOnly('Content of ' . static::class . ' can not be changed');
    }

}