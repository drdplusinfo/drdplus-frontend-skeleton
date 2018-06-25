<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class Redirect extends StrictObject
{
    /** @var string */
    private $target;
    /** @var int */
    private $afterSeconds;

    public function __construct(string $target, int $afterSeconds)
    {
        $this->target = $target;
        $this->afterSeconds = $afterSeconds;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return int
     */
    public function getAfterSeconds(): int
    {
        return $this->afterSeconds;
    }
}