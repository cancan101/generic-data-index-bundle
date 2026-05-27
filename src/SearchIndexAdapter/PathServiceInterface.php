<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter;

use Exception;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
interface PathServiceInterface
{
    /**
     * Directly update children paths in search index for assets as otherwise you might get strange results
     * if you rename a folder in the portal engine frontend.
     *
     * @throws Exception
     */
    public function rewriteChildrenIndexPaths(ElementInterface $element): void;

    /**
     * Same contract as {@see self::rewriteChildrenIndexPaths()} but skips the search-index
     * round-trip used to discover the old path. Callers that already know the pre-rename
     * path (e.g. from the Pimcore POST_UPDATE event's "oldPath" argument) should use this
     * variant so the rewrite is independent of whether the search index still reflects the
     * pre-rename state.
     *
     * @throws Exception
     */
    public function rewriteChildrenIndexPathsBetween(
        ElementInterface $element,
        string $oldFullPath,
        string $newFullPath
    ): void;

    public function getCurrentIndexFullPath(ElementInterface $element): ?string;
}
