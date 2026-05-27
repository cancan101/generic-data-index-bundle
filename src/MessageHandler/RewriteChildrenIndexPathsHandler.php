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

namespace Pimcore\Bundle\GenericDataIndexBundle\MessageHandler;

use Pimcore\Bundle\GenericDataIndexBundle\Message\RewriteChildrenIndexPathsMessage;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\PathServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\ElementServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final readonly class RewriteChildrenIndexPathsHandler
{
    public function __construct(
        private PathServiceInterface $pathService,
        private ElementServiceInterface $elementService,
    ) {
    }

    public function __invoke(RewriteChildrenIndexPathsMessage $message): void
    {
        $element = $this->elementService->getElementByType(
            $message->getElementId(),
            $message->getElementType()->value
        );

        if ($element === null) {
            return;
        }

        $this->pathService->rewriteChildrenIndexPathsBetween(
            $element,
            $message->getOldFullPath(),
            $message->getNewFullPath()
        );
    }
}
