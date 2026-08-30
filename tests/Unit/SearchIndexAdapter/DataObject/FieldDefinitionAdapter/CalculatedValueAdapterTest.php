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

namespace Pimcore\Bundle\GenericDataIndexBundle\Tests\Unit\SearchIndexAdapter\DataObject\FieldDefinitionAdapter;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\DefaultSearch\AttributeType;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DataObject\FieldDefinitionServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DefaultSearch\DataObject\FieldDefinitionAdapter\CalculatedValueAdapter;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\IndexMappingServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\SearchIndexConfigServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue;

/**
 * @internal
 */
final class CalculatedValueAdapterTest extends Unit
{
    private const TEXT_KEYWORD_MAPPING = [
        'type' => 'text',
        'fields' => ['keyword' => ['type' => 'keyword']],
    ];

    public function testMappingIsTypedForSafeNumericField(): void
    {
        $adapter = $this->createAdapter();
        $adapter->setFieldDefinition($this->createFieldDefinition('numeric', safeForFiltering: true));

        $this->assertSame(['type' => AttributeType::FLOAT->value], $adapter->getIndexMapping());
    }

    public function testMappingIsTypedForSafeBooleanField(): void
    {
        $adapter = $this->createAdapter();
        $adapter->setFieldDefinition($this->createFieldDefinition('boolean', safeForFiltering: true));

        $this->assertSame(['type' => AttributeType::BOOLEAN->value], $adapter->getIndexMapping());
    }

    public function testMappingStaysTextKeywordForSafeStringFields(): void
    {
        foreach (['input', 'textarea', 'html', 'date'] as $elementType) {
            $adapter = $this->createAdapter();
            $adapter->setFieldDefinition($this->createFieldDefinition($elementType, safeForFiltering: true));

            $this->assertSame(self::TEXT_KEYWORD_MAPPING, $adapter->getIndexMapping());
        }
    }

    public function testMappingStaysTextKeywordForFieldsNotDeclaredSafe(): void
    {
        $adapter = $this->createAdapter();
        $adapter->setFieldDefinition($this->createFieldDefinition('numeric', safeForFiltering: false));

        $this->assertSame(self::TEXT_KEYWORD_MAPPING, $adapter->getIndexMapping());
    }

    public function testMappingStaysTextKeywordForFieldDefinitionsWithoutTheFlag(): void
    {
        $fieldDefinition = new CalculatedValue();
        $fieldDefinition->setElementType('numeric');

        $adapter = $this->createAdapter();
        $adapter->setFieldDefinition($fieldDefinition);

        $this->assertSame(self::TEXT_KEYWORD_MAPPING, $adapter->getIndexMapping());
    }

    public function testNormalizeCoercesSafeNumericValues(): void
    {
        $adapter = $this->createAdapter();
        $adapter->setFieldDefinition($this->createFieldDefinition('numeric', safeForFiltering: true));

        $this->assertSame(9.0, $adapter->normalize('9'));
        $this->assertSame(9.5, $adapter->normalize(9.5));
        $this->assertSame(0.0, $adapter->normalize('0'));
        $this->assertNull($adapter->normalize(''));
        $this->assertNull($adapter->normalize('not a number'));
        $this->assertNull($adapter->normalize(null));
    }

    public function testNormalizeCoercesSafeBooleanValues(): void
    {
        $adapter = $this->createAdapter();
        $adapter->setFieldDefinition($this->createFieldDefinition('boolean', safeForFiltering: true));

        $this->assertTrue($adapter->normalize('1'));
        $this->assertTrue($adapter->normalize(true));
        // the query table stores (string) false as '' and (string) true as '1'
        $this->assertFalse($adapter->normalize(''));
        $this->assertFalse($adapter->normalize('0'));
        $this->assertFalse($adapter->normalize(false));
        $this->assertNull($adapter->normalize(null));
    }

    public function testNormalizeKeepsStringBehaviorForFieldsNotDeclaredSafe(): void
    {
        $adapter = $this->createAdapter();
        $adapter->setFieldDefinition($this->createFieldDefinition('numeric', safeForFiltering: false));

        $this->assertSame('9', $adapter->normalize('9'));
        $this->assertSame(
            '<img  />',
            $adapter->normalize('<img src="data:image/png;base64,abc123" />')
        );
    }

    private function createAdapter(): CalculatedValueAdapter
    {
        return new CalculatedValueAdapter(
            $this->makeEmpty(SearchIndexConfigServiceInterface::class, [
                'getSearchAnalyzerAttributes' => [],
            ]),
            $this->makeEmpty(FieldDefinitionServiceInterface::class),
            $this->makeEmpty(IndexMappingServiceInterface::class, [
                'getMappingForTextKeyword' => self::TEXT_KEYWORD_MAPPING,
            ])
        );
    }

    private function createFieldDefinition(string $elementType, bool $safeForFiltering): CalculatedValue
    {
        // Subclass carrying the safe-for-filtering declaration so the test does not depend on a
        // pimcore version that already ships the flag on CalculatedValue.
        $fieldDefinition = new class($safeForFiltering) extends CalculatedValue {
            public function __construct(private readonly bool $safe)
            {
            }

            public function getSafeForFiltering(): bool
            {
                return $this->safe;
            }
        };

        $fieldDefinition->setElementType($elementType);

        return $fieldDefinition;
    }
}
