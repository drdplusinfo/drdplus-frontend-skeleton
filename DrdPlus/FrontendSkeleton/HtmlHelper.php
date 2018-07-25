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
    public const DATA_ORIGINAL_ID = 'data-original-id';
    public const EXTERNAL_URL = 'external-url';

    /** @var Dirs */
    private $dirs;
    /** @var bool */
    private $inDevMode;
    /** @var bool */
    private $inForcedProductionMode;
    /** @var bool */
    private $shouldHideCovered;
    /** @var bool */
    private $showIntroductionOnly;

    public static function createFromGlobals(Dirs $dirs): HtmlHelper
    {
        return new static(
            $dirs,
            !empty($_GET['mode']) && \strpos(\trim($_GET['mode']), 'dev') === 0,
            !empty($_GET['mode']) && \strpos(\trim($_GET['mode']), 'prod') === 0,
            !empty($_GET['hide']) && \strpos(\trim($_GET['hide']), 'cover') === 0,
            !empty($_GET['show']) && \strpos(\trim($_GET['show']), 'intro') === 0
        );
    }

    public function __construct(
        Dirs $dirs,
        bool $inDevMode,
        bool $inForcedProductionMode,
        bool $shouldHideCovered,
        bool $showIntroductionOnly
    )
    {
        $this->dirs = $dirs;
        $this->inDevMode = $inDevMode;
        $this->inForcedProductionMode = $inForcedProductionMode;
        $this->shouldHideCovered = $shouldHideCovered;
        $this->showIntroductionOnly = $showIntroductionOnly;
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
            $child->setAttribute(self::DATA_ORIGINAL_ID, $id);
            $child->setAttribute('id', $this->sanitizeId($idWithoutDiacritics));
            $child->appendChild($invisibleId = new Element('span'));
            $invisibleId->setAttribute('id', $this->sanitizeId($id));
            $invisibleId->className = self::INVISIBLE_ID_CLASS;
        }
    }

    private function sanitizeId(string $id): string
    {
        return \str_replace('#', '_', $id);
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
            $hashWithoutDiacritics = $this->unifyId($hash);
            if ($hashWithoutDiacritics === $hash) {
                continue;
            }
            $hrefWithoutDiacritics = substr($href, 0, $hashPosition) . '#' . $hashWithoutDiacritics;
            $child->setAttribute('href', $hrefWithoutDiacritics);
        }
    }

    /**
     * @param HtmlDocument $htmlDocument
     * @return HtmlDocument
     */
    public function addAnchorsToIds(HtmlDocument $htmlDocument): HtmlDocument
    {
        $this->addAnchorsToChildrenWithIds($htmlDocument->body->children);

        return $htmlDocument;
    }

    private function addAnchorsToChildrenWithIds(HTMLCollection $children): void
    {
        /** @var Element $child */
        foreach ($children as $child) {
            if (!\in_array($child->nodeName, ['a', 'button'], true)
                && $child->getAttribute('id')
                && !$child->prop_get_classList()->contains(self::INVISIBLE_ID_CLASS)
                && $child->getElementsByTagName('a')->length === 0 // already have some anchors, skipp it to avoid wrapping them by another one
            ) {
                $toMove = [];
                /** @var \DOMElement $grandChildNode */
                foreach ($child->childNodes as $grandChildNode) {
                    if (!\in_array($grandChildNode->nodeName, ['span', 'strong', 'b', 'i', '#text'], true)) {
                        break;
                    }
                    $toMove[] = $grandChildNode;
                }
                if (\count($toMove) > 0) {
                    $anchorToSelf = new Element('a');
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

    public function markExternalLinksByClass(HtmlDocument $htmlDocument): HtmlDocument
    {
        /** @var Element $anchor */
        foreach ($htmlDocument->getElementsByTagName('a') as $anchor) {
            if (!$anchor->classList->contains('internal')
                && \preg_match('~^(https?:)?//[^#]~', $anchor->getAttribute('href') ?? '')
            ) {
                $anchor->classList->add(self::EXTERNAL_URL);
            }
        }
        $htmlDocument->body->setAttribute('data-has-marked-external-urls', '1');

        return $htmlDocument;
    }

    /**
     * @param HtmlDocument $htmlDocument
     * @throws \LogicException
     */
    public function externalLinksTargetToBlank(HtmlDocument $htmlDocument): void
    {
        if (!$this->hasMarkedExternalUrls($htmlDocument)) {
            throw new Exceptions\ExternalUrlsHaveToBeMarkedFirst(
                'External links have to be marked first, use markExternalLinksByClass method for that'
            );
        }
        /** @var Element $anchor */
        foreach ($htmlDocument->getElementsByClassName(self::EXTERNAL_URL) as $anchor) {
            if (!$anchor->getAttribute('target')) {
                $anchor->setAttribute('target', '_blank');
            }
        }
    }

    /**
     * @param HtmlDocument $htmlDocument
     * @return HtmlDocument
     * @throws \LogicException
     */
    public function injectIframesWithRemoteTables(HtmlDocument $htmlDocument): HtmlDocument
    {
        if (!$this->hasMarkedExternalUrls($htmlDocument)) {
            throw new Exceptions\ExternalUrlsHaveToBeMarkedFirst(
                'External links have to be marked first, use markExternalLinksByClass method for that'
            );
        }
        $remoteDrdPlusLinks = [];
        /** @var Element $anchor */
        foreach ($htmlDocument->getElementsByClassName(self::EXTERNAL_URL) as $anchor) {
            if (!\preg_match('~(?:https?:)?//(?<host>[[:alpha:]]+\.drdplus\.info)/[^#]*#(?<tableId>tabulka_\w+)~', $anchor->getAttribute('href'), $matches)) {
                continue;
            }
            $remoteDrdPlusLinks[$matches['host']][] = $matches['tableId'];
        }
        if (\count($remoteDrdPlusLinks) === 0) {
            return $htmlDocument;
        }
        /** @var Element $body */
        $body = $htmlDocument->getElementsByTagName('body')[0];
        foreach ($remoteDrdPlusLinks as $remoteDrdPlusHost => $tableIds) {
            $iFrame = $htmlDocument->createElement('iframe');
            $body->appendChild($iFrame);
            $iFrame->setAttribute('id', $remoteDrdPlusHost); // we will target that iframe via JS by remote host name
            $iFrame->setAttribute(
                'src',
                "https://{$remoteDrdPlusHost}/?tables=" . \htmlspecialchars(\implode(',', \array_unique($tableIds)))
            );
            $iFrame->setAttribute('style', 'display:none');
        }

        return $htmlDocument;
    }

    private function hasMarkedExternalUrls(HtmlDocument $htmlDocument): bool
    {
        return (bool)$htmlDocument->body->getAttribute('data-has-marked-external-urls');
    }

    /**
     * @param HtmlDocument $htmlDocument
     * @return HtmlDocument
     */
    public function makeExternalDrdPlusLinksLocal(HtmlDocument $htmlDocument): HtmlDocument
    {
        if (!$this->hasMarkedExternalUrls($htmlDocument)) {
            throw new Exceptions\ExternalUrlsHaveToBeMarkedFirst(
                'External links have to be marked first, use markExternalLinksByClass method for that'
            );
        }
        foreach ($htmlDocument->getElementsByClassName(self::EXTERNAL_URL) as $anchor) {
            $anchor->setAttribute('href', $this->makeDrdPlusHostLocal($anchor->getAttribute('href')));
        }
        /** @var Element $iFrame */
        foreach ($htmlDocument->getElementsByTagName('iframe') as $iFrame) {
            $iFrame->setAttribute('src', $this->makeDrdPlusHostLocal($iFrame->getAttribute('src')));
            $iFrame->setAttribute('id', \str_replace('drdplus.info', 'drdplus.loc', $iFrame->getAttribute('id')));
        }

        return $htmlDocument;
    }

    private function makeDrdPlusHostLocal(string $linkWithRemoteDrdPlusHost): string
    {
        return \preg_replace(
            '~(?:https?:)?//((?:[^.]+[.])+)drdplus[.]info~',
            'http://$1drdplus.loc',
            $linkWithRemoteDrdPlusHost
        );
    }

    public function updateAssetLinks(HtmlDocument $htmlDocument, WebVersions $webVersions): void
    {
        $this->updateAssetLinksToCurrentVersion($htmlDocument, $webVersions);
        $this->addVersionHashToAssets($htmlDocument);
    }

    private function updateAssetLinksToCurrentVersion(HtmlDocument $htmlDocument, WebVersions $webVersions): void
    {
        if (!$webVersions->isCurrentVersionStable()) {
            return;
        }
        $relativeVersionDocumentRoot = $webVersions->getRelativeVersionDocumentRoot($webVersions->getCurrentVersion());
        foreach ($htmlDocument->getElementsByTagName('img') as $image) {
            $this->updateAssetLinkToCurrentVersion($image, 'src', $relativeVersionDocumentRoot);
        }
        foreach ($htmlDocument->getElementsByTagName('link') as $link) {
            $this->updateAssetLinkToCurrentVersion($link, 'href', $relativeVersionDocumentRoot);
        }
        foreach ($htmlDocument->getElementsByTagName('script') as $script) {
            $this->updateAssetLinkToCurrentVersion($script, 'src', $relativeVersionDocumentRoot);
        }
    }

    private function updateAssetLinkToCurrentVersion(Element $element, string $attributeName, string $relativeVersionDocumentRoot): void
    {
        $link = $element->getAttribute($attributeName);
        if ($this->isLinkExternal($link)) {
            return;
        }
        $element->setAttribute($attributeName, $relativeVersionDocumentRoot . '/' . \ltrim($link, '/'));
    }

    private function isLinkExternal(string $link): bool
    {
        $urlParts = \parse_url($link);

        return !empty($urlParts['host']);
    }

    private function addVersionHashToAssets(HtmlDocument $htmlDocument): void
    {
        $masterDocumentRoot = $this->dirs->getMasterDocumentRoot();
        foreach ($htmlDocument->getElementsByTagName('img') as $image) {
            $this->addVersionToAsset($image, 'src', $masterDocumentRoot);
        }
        foreach ($htmlDocument->getElementsByTagName('link') as $link) {
            $this->addVersionToAsset($link, 'href', $masterDocumentRoot);
        }
        foreach ($htmlDocument->getElementsByTagName('script') as $script) {
            $this->addVersionToAsset($script, 'src', $masterDocumentRoot);
        }
    }

    private function addVersionToAsset(Element $element, string $attributeName, string $masterDocumentRoot): void
    {
        $link = $element->getAttribute($attributeName);
        if ($this->isLinkExternal($link)) {
            return;
        }
        $absolutePath = $this->getAbsolutePath($link, $masterDocumentRoot);
        $hash = $this->getFileHash($absolutePath);
        $element->setAttribute($attributeName, $link . '?version=' . \urlencode($hash));
    }

    private function getAbsolutePath(string $relativePath, string $masterDocumentRoot): string
    {
        $relativePath = \ltrim($relativePath, '\\/');

        return $masterDocumentRoot . '/' . $relativePath;
    }

    private function getFileHash(string $fileName): string
    {
        return \md5_file($fileName) ?: (string)\time(); // time is a fallback
    }

    public function isInProduction(): bool
    {
        return $this->inForcedProductionMode || (\PHP_SAPI !== 'cli' && ($_SERVER['REMOTE_ADDR'] ?? null) !== '127.0.0.1');
    }
}