<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 * @license Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DefaultSearch\DataObject\FieldDefinitionAdapter;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\DefaultSearch\AttributeType;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DataObject\FieldDefinitionServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\IndexMappingServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\SearchIndexConfigServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue;
use function in_array;
use function is_numeric;
use function is_string;

/**
 * Fields declared safe for filtering are mapped by their element type so numeric and boolean
 * comparisons work; all other calculated value fields keep the text/keyword mapping and
 * normalization they had when served by the TextKeywordAdapter.
 *
 * @internal
 */
final class CalculatedValueAdapter extends AbstractAdapter
{
    public function __construct(
        protected SearchIndexConfigServiceInterface $searchIndexConfigService,
        protected FieldDefinitionServiceInterface $fieldDefinitionService,
        private readonly IndexMappingServiceInterface $indexMappingService,
    ) {
        parent::__construct(
            $searchIndexConfigService,
            $fieldDefinitionService
        );
    }

    public function getIndexMapping(): array
    {
        return match ($this->getTypedElementType()) {
            'numeric' => ['type' => AttributeType::FLOAT->value],
            'boolean' => ['type' => AttributeType::BOOLEAN->value],
            default => $this->indexMappingService->getMappingForTextKeyword(
                $this->searchIndexConfigService->getSearchAnalyzerAttributes()
            ),
        };
    }

    public function normalize(mixed $value): mixed
    {
        // Values may arrive as varchar snapshots from the object query table
        // (calculated_fields_index_mode: query_store) or as raw calculator results (mode: live).
        return match ($this->getTypedElementType()) {
            'numeric' => is_numeric($value) ? (float) $value : null,
            // (string) false is '' in the query table, so '' means false rather than empty here
            'boolean' => $value === null ? null : !in_array($value, ['', '0', 0, 0.0, false], true),
            default => $this->normalizeAsText($value),
        };
    }

    private function normalizeAsText(mixed $value): mixed
    {
        if (is_string($value) && $value !== '') {
            return preg_replace("/src=(['\"])data:[^;]+;base64,.+?\\1/", '', $value);
        }

        return parent::normalize($value);
    }

    /**
     * The element type steers mapping and normalization only for fields declared safe for
     * filtering; the declaration requires a pimcore version whose CalculatedValue definition
     * carries the flag, hence the method_exists guard.
     */
    private function getTypedElementType(): ?string
    {
        $fieldDefinition = $this->getFieldDefinition();

        if (!$fieldDefinition instanceof CalculatedValue
            || !method_exists($fieldDefinition, 'getSafeForFiltering')
            || !$fieldDefinition->getSafeForFiltering()
        ) {
            return null;
        }

        return $fieldDefinition->getElementType();
    }
}
