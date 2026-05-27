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

namespace Pimcore\Bundle\GenericDataIndexBundle\Message;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\ElementType;

/**
 * Carries the pre- and post-rename paths captured from Pimcore's POST_UPDATE event
 * ("oldPath" argument) so the handler can rewrite affected children in the search
 * index without re-reading the parent's current indexed path.
 *
 * @internal
 */
final readonly class RewriteChildrenIndexPathsMessage
{
    public function __construct(
        private int $elementId,
        private ElementType $elementType,
        private string $oldFullPath,
        private string $newFullPath
    ) {
    }

    public function getElementId(): int
    {
        return $this->elementId;
    }

    public function getElementType(): ElementType
    {
        return $this->elementType;
    }

    public function getOldFullPath(): string
    {
        return $this->oldFullPath;
    }

    public function getNewFullPath(): string
    {
        return $this->newFullPath;
    }
}
