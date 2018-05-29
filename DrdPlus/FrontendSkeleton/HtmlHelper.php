<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;
use Granam\String\StringTools;
use Gt\Dom\Element;
use Gt\Dom\HTMLCollection;

class HtmlHelper extends StrictObject
{
    public const INVISIBLE_ID_CLASS = 'invisible-id';
    public const CALCULATION_CLASS = 'calculation';

    /** @var bool */
    private $inDevMode;
    /** @var bool */
    private $inForcedProductionMode;
    /** @var bool */
    private $shouldHideCovered;
    /** @var bool */
    private $showIntroductionOnly;
    /** @var bool */
    private $externalUrlsMarked = false;
    /** @var string */
    private $rootDir;

    public static function createFromGlobals(string $documentRootDir): HtmlHelper
    {
        return new static(
            $documentRootDir,
            !empty($_GET['mode']) && \strpos(\trim($_GET['mode']), 'dev') === 0,
            !empty($_GET['mode']) && \strpos(\trim($_GET['mode']), 'prod') === 0,
            !empty($_GET['hide']) && \strpos(\trim($_GET['hide']), 'cover') === 0,
            !empty($_GET['show']) && \strpos(\trim($_GET['show']), 'intro') === 0
        );
    }

    public function __construct(
        string $rootDir,
        bool $inDevMode,
        bool $inForcedProductionMode,
        bool $shouldHideCovered,
        bool $showIntroductionOnly
    )
    {
        $this->rootDir = $this->unifyPath($rootDir);
        $this->inDevMode = $inDevMode;
        $this->inForcedProductionMode = $inForcedProductionMode;
        $this->shouldHideCovered = $shouldHideCovered;
        $this->showIntroductionOnly = $showIntroductionOnly;
    }

    private function unifyPath(string $path): string
    {
        $path = \str_replace('\\', '/', $path);

        return \rtrim($path, '/');
    }

    /**
     * @param HtmlDocument $html
     */
    public function prepareSourceCodeLinks(HtmlDocument $html): void
    {
        if (!$this->inDevMode) {
            foreach ($html->getElementsByClassName('source-code-title') as $withSourceCode) {
                $withSourceCode->className = \str_replace('source-code-title', 'hidden', $withSourceCode->className);
                $withSourceCode->removeAttribute('data-source-code');
            }
        } else {
            foreach ($html->getElementsByClassName('source-code-title') as $withSourceCode) {
                $withSourceCode->appendChild($sourceCodeLink = new Element('a', 'source code'));
                $sourceCodeLink->setAttribute('class', 'source-code');
                $sourceCodeLink->setAttribute('href', $withSourceCode->getAttribute('data-source-code'));
            }
        }
    }

    /**
     * @param HtmlDocument $html
     */
    public function addIdsToTablesAndHeadings(HtmlDocument $html): void
    {
        $elementNames = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'th'];
        foreach ($elementNames as $elementName) {
            /** @var Element $headerCell */
            foreach ($html->getElementsByTagName($elementName) as $headerCell) {

                if ($headerCell->getAttribute('id')) {
                    continue;
                }
                if ($elementName === 'th' && \strpos(\trim($headerCell->textContent), 'Tabulka') === false) {
                    continue;
                }
                $id = false;
                /** @var \DOMNode $childNode */
                foreach ($headerCell->childNodes as $childNode) {
                    if ($childNode->nodeType === XML_TEXT_NODE) {
                        $id = \trim($childNode->nodeValue);
                        break;
                    }
                }
                if (!$id) {
                    continue;
                }
                $headerCell->setAttribute('id', $id);
            }
        }
    }

    public function replaceDiacriticsFromIds(HtmlDocument $html): void
    {
        $this->replaceDiacriticsFromChildrenIds($html->body->children);
    }

    private function replaceDiacriticsFromChildrenIds(HTMLCollection $children): void
    {
        foreach ($children as $child) {
            // recursion
            $this->replaceDiacriticsFromChildrenIds($child->children);
            $id = $child->getAttribute('id');
            if (!$id) {
                continue;
            }
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $idWithoutDiacritics = $this->unifyId($id);
            if ($idWithoutDiacritics === $id) {
                continue;
            }
            $child->setAttribute('data-original-id', $id);
            $child->setAttribute('id', \urlencode($idWithoutDiacritics));
            $child->appendChild($invisibleId = new Element('span'));
            $invisibleId->setAttribute('id', \urlencode($id));
            $invisibleId->className = self::INVISIBLE_ID_CLASS;
        }
    }

    private function unifyId(string $id): string
    {
        return StringTools::toConstantLikeValue(StringTools::camelCaseToSnakeCase($id));
    }

    public function replaceDiacriticsFromAnchorHashes(HtmlDocument $html): void
    {
        $this->replaceDiacriticsFromChildrenAnchorHashes($html->getElementsByTagName('a'));
    }

    private function replaceDiacriticsFromChildrenAnchorHashes(\Traversable $children): void
    {
        /** @var Element $child */
        foreach ($children as $child) {
            // recursion
            $this->replaceDiacriticsFromChildrenAnchorHashes($child->children);
            $href = $child->getAttribute('href');
            if (!$href) {
                continue;
            }
            $hashPosition = \strpos($href, '#');
            if ($hashPosition === false) {
                continue;
            }
            $hash = substr($href, $hashPosition + 1);
            if ($hash === '') {
                continue;
            }
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $hashWithoutDiacritics = $this->unifyId($hash);
            if ($hashWithoutDiacritics === $hash) {
                continue;
            }
            $hrefWithoutDiacritics = substr($href, 0, $hashPosition) . '#' . $hashWithoutDiacritics;
            $child->setAttribute('href', $hrefWithoutDiacritics);
        }
    }

    /**
     * @param HtmlDocument $html
     */
    public function addAnchorsToIds(HtmlDocument $html): void
    {
        $this->addAnchorsToChildrenWithIds($html->body->children);
    }

    private function addAnchorsToChildrenWithIds(HTMLCollection $children): void
    {
        /** @var Element $child */
        foreach ($children as $child) {
            if ($child->getAttribute('id') && !$child->prop_get_classList()->contains(self::INVISIBLE_ID_CLASS)
                && $child->getElementsByTagName('a')->length === 0
            ) {
                $anchorToSelf = new Element('a');
                $toMove = [];
                /** @var \DOMElement $grandChildNode */
                foreach ($child->childNodes as $grandChildNode) {
                    if (!\in_array($grandChildNode->nodeName, ['span', 'strong', 'b', 'i', '#text'], true)) {
                        break;
                    }
                    $toMove[] = $grandChildNode;
                }
                if (\count($toMove) > 0) {
                    $child->replaceChild($anchorToSelf, $toMove[0]); // pairs anchor with parent element
                    $anchorToSelf->setAttribute('href', '#' . $child->getAttribute('id'));
                    foreach ($toMove as $index => $item) {
                        $anchorToSelf->appendChild($item);
                    }
                }
            }
            // recursion
            $this->addAnchorsToChildrenWithIds($child->children);
        }
    }

    private function containsOnlyTextAndSpans(\DOMNode $element): bool
    {
        if (!$element->hasChildNodes()) {
            return true;
        }
        /** @var \DOMNode $childNode */
        foreach ($element->childNodes as $childNode) {
            if ($childNode->nodeName !== 'span' && $childNode->nodeType !== XML_TEXT_NODE) {
                return false;
            }
            if (!$this->containsOnlyTextAndSpans($childNode)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param HtmlDocument $html
     */
    public function resolveDisplayMode(HtmlDocument $html): void
    {
        if ($this->inDevMode) {
            foreach ($html->getElementsByTagName('body') as $body) {
                $this->removeImages($body);
            }
        } else {
            foreach ($html->getElementsByTagName('body') as $body) {
                $this->removeClassesAboutCodeCoverage($body);
            }
        }
        if ($this->showIntroductionOnly) {
            foreach ($html->getElementsByTagName('body') as $body) {
                $this->removeNonIntroduction($body);
                $this->removeFollowingImageDelimiters($body);
            }
        }
        if (!$this->inDevMode || !$this->shouldHideCovered) {
            return;
        }
        $classesToHide = ['covered-by-code', 'quote', 'generic', 'note', 'excluded', 'rules-authors'];
        if (!$this->showIntroductionOnly) {
            $classesToHide[] = 'introduction';
        }
        foreach ($classesToHide as $classToHide) {
            foreach ($html->getElementsByClassName($classToHide) as $nodeToHide) {
                $nodeToHide->className = str_replace($classToHide, 'hidden', $nodeToHide->className);
            }
        }
    }

    private function removeImages(Element $html): void
    {
        do {
            $somethingRemoved = false;
            /** @var Element $image */
            foreach ($html->getElementsByTagName('img') as $image) {
                $image->remove();
                $somethingRemoved = true;
            }
        } while ($somethingRemoved); // do not know why, but some nodes are simply skipped on first removal so have to remove them again
    }

    private function removeNonIntroduction(Element $html): void
    {
        do {
            $somethingRemoved = false;
            /** @var \DOMNode $childNode */
            foreach ($html->childNodes as $childNode) {
                if ($childNode->nodeType === XML_TEXT_NODE
                    || !($childNode instanceof \DOMElement)
                    || ($childNode->nodeName !== 'img'
                        && !preg_match('~\s*(introduction|quote|background-image)\s*~', (string)$childNode->getAttribute('class'))
                    )
                ) {
                    $html->removeChild($childNode);
                    $somethingRemoved = true;
                }
                // introduction is expected only as direct descendant of the given element (body)
                if ($childNode instanceof Element) {
                    $childNode->classList->remove('generic');
                }
            }
        } while ($somethingRemoved); // do not know why, but some nodes are simply skipped on first removal so have to remove them again
    }

    private function removeFollowingImageDelimiters(Element $html): void
    {
        $followingDelimiter = false;
        do {
            $somethingRemoved = false;
            /** @var Element $child */
            foreach ($html->childNodes as $child) {
                if ($child->nodeName === 'img' && $child->classList->contains('delimiter')) {
                    if ($followingDelimiter) {
                        $html->removeChild($child);
                        $somethingRemoved = true;
                    }
                    $followingDelimiter = true;
                } else {
                    $followingDelimiter = false;
                }
            }
        } while ($somethingRemoved);
    }

    private function removeClassesAboutCodeCoverage(Element $html): void
    {
        $classesToRemove = ['covered-by-code', 'generic', 'excluded'];
        foreach ($html->children as $child) {
            foreach ($classesToRemove as $classToRemove) {
                $child->classList->remove($classToRemove);
            }
            // recursion
            $this->removeClassesAboutCodeCoverage($child);
        }
    }

    /**
     * @param HtmlDocument $html
     * @param array|string[] $requiredIds filter of required tables by their IDs
     * @return array|Element[]
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\DuplicatedRequiredTableId
     */
    public function findTablesWithIds(HtmlDocument $html, array $requiredIds = []): array
    {
        $requiredIds = \array_unique($requiredIds);
        $lowerCasedRequiredIds = [];
        foreach ($requiredIds as $requiredId) {
            $unifiedId = $this->unifyId($requiredId);
            if (\array_key_exists($unifiedId, $lowerCasedRequiredIds)) {
                $requiredIdsAsString = \implode(',', $requiredIds);
                throw new Exceptions\DuplicatedRequiredTableId(
                    'IDs of tables are lower-cased and some required table IDs are same in lowercase: '
                    . "'{$requiredId}' => '{$unifiedId}' ($requiredIdsAsString)"
                );
            }
            $lowerCasedRequiredIds[$unifiedId] = $unifiedId;
        }
        $tablesWithIds = [];
        /** @var Element $table */
        foreach ($html->getElementsByTagName('table') as $table) {
            $lowerId = $table->getAttribute('id');
            if ($lowerId) {
                $tablesWithIds[$lowerId] = $table;
                continue;
            }
            $childId = $this->getChildId($table->children);
            if ($childId) {
                $tablesWithIds[$childId] = $table;
            }
        }
        if (\count($requiredIds) === 0) {
            return $tablesWithIds;
        }
        if (!$requiredIds) {
            return $tablesWithIds;
        }

        return \array_intersect_key($tablesWithIds, $lowerCasedRequiredIds);
    }

    /**
     * @param HTMLCollection $children
     * @return string|bool
     */
    private function getChildId(HTMLCollection $children)
    {
        foreach ($children as $child) {
            if ($child->getAttribute('id')) {
                return $child->getAttribute('id');
            }
            $grandChildId = $this->getChildId($child->children);
            if ($grandChildId !== false) {
                return $grandChildId;
            }
        }

        return false;
    }

    public function markExternalLinksByClass(HtmlDocument $html): void
    {
        /** @var Element $anchor */
        foreach ($html->getElementsByTagName('a') as $anchor) {
            if (!$anchor->classList->contains('internal')
                && \preg_match('~^(https?:)?//[^#]~', $anchor->getAttribute('href'))
            ) {
                $anchor->classList->add('external-url');
            }
        }
        $this->externalUrlsMarked = true;
    }

    /**
     * @param HtmlDocument $html
     * @throws \LogicException
     */
    public function externalLinksTargetToBlank(HtmlDocument $html): void
    {
        if (!$this->externalUrlsMarked) {
            throw new \LogicException('External links have to be marked first, use markExternalLinksByClass method for that');
        }
        /** @var Element $anchor */
        foreach ($html->getElementsByClassName('external-url') as $anchor) {
            if (!$anchor->getAttribute('target')) {
                $anchor->setAttribute('target', '_blank');
            }
        }
    }

    /**
     * @param HtmlDocument $html
     * @throws \LogicException
     */
    public function injectIframesWithRemoteTables(HtmlDocument $html): void
    {
        if (!$this->externalUrlsMarked) {
            throw new \LogicException('External links have to be marked first, use markExternalLinksByClass method for that');
        }
        $remoteDrdPlusLinks = [];
        /** @var Element $anchor */
        foreach ($html->getElementsByClassName('external-url') as $anchor) {
            if (!\preg_match('~(?:https?:)?//(?<host>[[:alpha:]]+\.drdplus\.info)/[^#]*#(?<tableId>tabulka_\w+)~', $anchor->getAttribute('href'), $matches)) {
                continue;
            }
            $remoteDrdPlusLinks[$matches['host']][] = $matches['tableId'];
        }
        if (\count($remoteDrdPlusLinks) === 0) {
            return;
        }
        /** @var Element $body */
        $body = $html->getElementsByTagName('body')[0];
        foreach ($remoteDrdPlusLinks as $remoteDrdPlusHost => $tableIds) {
            $iFrame = $html->createElement('iframe');
            $body->appendChild($iFrame);
            $iFrame->setAttribute('id', $remoteDrdPlusHost); // we will target that iframe via JS by remote host name
            $iFrame->setAttribute('src', "https://{$remoteDrdPlusHost}/?tables=" . \htmlspecialchars(\implode(',', $tableIds)));
            $iFrame->setAttribute('style', 'display:none');
        }
    }

    /**
     * @param HtmlDocument $html
     */
    public function makeExternalLinksLocal(HtmlDocument $html): void
    {
        foreach ($html->getElementsByClassName('external-url') as $anchor) {
            $anchor->setAttribute('href', $this->makeDrdPlusHostLocal($anchor->getAttribute('href')));
        }
        /** @var Element $iFrame */
        foreach ($html->getElementsByTagName('iframe') as $iFrame) {
            $iFrame->setAttribute('src', $this->makeDrdPlusHostLocal($iFrame->getAttribute('src')));
            $iFrame->setAttribute('id', \str_replace('drdplus.info', 'drdplus.loc', $iFrame->getAttribute('id')));
        }
    }

    private function makeDrdPlusHostLocal(string $linkWithRemoteDrdPlusHost): string
    {
        return \preg_replace(
            '~(?:https?:)?//([[:alpha:]]+)[.]drdplus[.]info~',
            'http://$1.drdplus.loc',
            $linkWithRemoteDrdPlusHost
        );
    }

    public function addVersionHashToAssets(HtmlDocument $html): void
    {
        foreach ($html->getElementsByTagName('img') as $image) {
            $this->addVersionToAsset($image, 'src');
        }
        foreach ($html->getElementsByTagName('link') as $link) {
            $this->addVersionToAsset($link, 'href');
        }
        foreach ($html->getElementsByTagName('script') as $script) {
            $this->addVersionToAsset($script, 'src');
        }
    }

    private function addVersionToAsset(Element $element, string $attributeName): void
    {
        $source = $element->getAttribute($attributeName);
        if (!$source) {
            return;
        }
        $absolutePath = $this->getAbsolutePath($source, $this->rootDir);
        $hash = $this->getFileHash($absolutePath);
        $element->setAttribute($attributeName, $source . '?version=' . \urlencode($hash));
    }

    private function getAbsolutePath(string $relativePath, string $root): string
    {
        $relativePath = \ltrim($this->unifyPath($relativePath), '/');

        return $root . '/' . $relativePath;
    }

    private function getFileHash(string $fileName): string
    {
        return \md5_file($fileName) ?: (string)\time(); // time is fallback
    }

    public function getPageTitle(): string
    {
        $title = \is_readable($this->rootDir . '/name.txt')
            ? \file_get_contents($this->rootDir . '/name.txt')
            : ('Drd+ ' . \basename($this->rootDir));
        $smiley = \is_readable($this->rootDir . '/title_smiley.txt')
            ? \file_get_contents($this->rootDir . '/title_smiley.txt')
            : '';

        return ($smiley !== '')
            ? ($smiley . ' ' . $title)
            : $title;
    }

    public function isInProduction(): bool
    {
        return $this->inForcedProductionMode || (\PHP_SAPI !== 'cli' && ($_SERVER['REMOTE_ADDR'] ?? null) !== '127.0.0.1');
    }
}