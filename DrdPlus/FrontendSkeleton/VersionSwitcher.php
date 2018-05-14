<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class VersionSwitcher extends StrictObject
{

    /** @var Versions */
    private $versions;
    /** @var VersionSwitchMutex */
    private $versionSwitchMutex;

    public function __construct(Versions $versions, VersionSwitchMutex $versionSwitchMutex)
    {
        $this->versions = $versions;
        $this->versionSwitchMutex = $versionSwitchMutex;
    }

    /**
     * @param string $version
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\InvalidVersionToSwitchInto
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotSwitchGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotWriteLockOfVersionMutex
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLockVersionMutex
     */
    public function switchToVersion(string $version): bool
    {
        // do NOT unlock it as we need the version to be locked until we fill or use the cache (lock will be unlocked automatically on script end)
        if ($version === $this->versions->getCurrentVersion()) {
            if (!$this->versionSwitchMutex->isLockedForId($version)) {
                $this->versionSwitchMutex->lock($version);
            }

            return false;
        }
        $this->versionSwitchMutex->lock($version);
        if (!$this->versions->hasVersion($version)) {
            throw new Exceptions\InvalidVersionToSwitchInto("Required version {$version} does not exist");
        }
        $command = 'git checkout ' . \escapeshellarg($version) . ' 2>&1';
        \exec($command, $rows, $returnCode);
        if ($returnCode !== 0) {
            throw new Exceptions\CanNotSwitchGitVersion(
                "Can not switch to required version '{$version}' by command '{$command}'"
                . ", got return code '{$returnCode}' and output\n"
                . \implode("\n", $rows)
            );
        }

        return true;
    }
}