<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class Yaml extends StrictObject implements \ArrayAccess
{
    /** @var string */
    private $yamlFile;
    /** @var array */
    private $values;

    public function __construct(string $yamlFile)
    {
        $this->yamlFile = $yamlFile;
    }

    /**
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotParseYamlFile
     */
    public function getValues(): array
    {
        if ($this->values === null) {
            $values = \yaml_parse_file($this->yamlFile);
            if ($values === false) {
                throw new Exceptions\CanNotParseYamlFile("Can not parse content of YAML file '{$this->yamlFile}'");
            }
            $this->values = (array)$values;
        }

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
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\YamlContentIsReadOnly
     */
    public function offsetSet($offset, $value): void
    {
        throw new Exceptions\YamlContentIsReadOnly('Content of ' . static::class . ' can not be changed');
    }

    /**
     * @param mixed $offset
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\YamlContentIsReadOnly
     */
    public function offsetUnset($offset): void
    {
        throw new Exceptions\YamlContentIsReadOnly('Content of ' . static::class . ' can not be changed');
    }

}