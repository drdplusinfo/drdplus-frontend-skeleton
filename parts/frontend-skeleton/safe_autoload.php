<?php
$requireAutoloadIfUnique = $requireAutoloadIfUnique
    ?? function (string $vendorRoot): \Composer\Autoload\ClassLoader {
        global $composer, $composerHash;
        $autoloadContent = \file_get_contents($vendorRoot . '/autoload.php');
        \preg_match('~ComposerAutoloaderInit(?<hash>[[:alnum:]]+)~', $autoloadContent, $matches);
        if ($composerHash === null || $composerHash !== $matches['hash']) {
            /** @noinspection PhpIncludeInspection */
            $composer = require $vendorRoot . '/autoload.php';
            $composerHash = $matches['hash'];
        } else {
            /** @var \Composer\Autoload\ClassLoader $composer */
            $composer->register();
        }

        return $composer;
    };

return $requireAutoloadIfUnique($documentRoot . '/vendor');