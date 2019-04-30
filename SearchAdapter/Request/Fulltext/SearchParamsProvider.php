<?php
/*
 * This file is part of the App Search Magento module.
 *
 * (c) Elastic
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elastic\AppSearch\SearchAdapter\Request\Fulltext;

use Magento\Framework\Search\RequestInterface;
use Elastic\AppSearch\SearchAdapter\Request\SearchParamsProviderInterface;
use Elastic\AppSearch\Model\Adapter\Engine\Schema\FieldNameResolverInterface;
use Elastic\AppSearch\Model\Adapter\Engine\Schema\AttributeAdapterProvider as AttributeProvider;
use Elastic\AppSearch\Model\Adapter\Engine\SchemaInterface;
use Elastic\AppSearch\SearchAdapter\Request\QueryLocatorInterface;

/**
 * Extract search fields from the search request.
 *
 * @package   Elastic\AppSearch\SearchAdapter\Request\Fulltext
 * @copyright 2019 Elastic
 * @license   Open Software License ("OSL") v. 3.0
 */
class SearchParamsProvider implements SearchParamsProviderInterface
{
    /**
     * @var string
     */
    private const SEARCH_FIELDS_KEY = 'search_fields';

    /**
     * @var string
     */
    private const WEIGHT_KEY = 'weight';

    /**
     * @var int
     */
    private const MAX_WEIGHT = 10;

    /**
     * @var QueryLocatorInterface
     */
    private $queryLocator;

    /**
     * @var FieldNameResolverInterface
     */
    private $fieldNameResolver;

    /**
     * @var AttributeProvider
     */
    private $attributeProvider;

    /**
     * Constructor.
     *
     * @param QueryLocatorInterface      $queryLocator
     * @param AttributeProvider          $attributeProvider
     * @param FieldNameResolverInterface $fieldNameResolver
     */
    public function __construct(
        QueryLocatorInterface $queryLocator,
        AttributeProvider $attributeProvider,
        FieldNameResolverInterface $fieldNameResolver
    ) {
        $this->queryLocator      = $queryLocator;
        $this->attributeProvider = $attributeProvider;
        $this->fieldNameResolver = $fieldNameResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function getParams(RequestInterface $request): array
    {
        $searchParams = [];
        $query = $this->queryLocator->getQuery($request);

        if ($query !== null && $query->getValue()) {
            foreach ($query->getMatches() ?? [] as $fieldConfig) {
                if ($fieldConfig['field'] != '*' && isset($fieldConfig['boost']) && $fieldConfig['boost'] > 0) {
                    $fieldName = $this->getSearchFieldName($fieldConfig['field']);
                    $weight    = $this->getWeight($fieldConfig['boost']);
                    $searchParams[self::SEARCH_FIELDS_KEY][$fieldName] = [self::WEIGHT_KEY => $weight];
                }
            }
        }

        return $searchParams;
    }

    /**
     * Convert field name into the index format.
     *
     * @param string $fieldName
     *
     * @return string
     */
    private function getSearchFieldName(string $fieldName): string
    {
        $attribute = $this->attributeProvider->getAttributeAdapter($fieldName);

        return $this->fieldNameResolver->getFieldName($attribute, ['type' => SchemaInterface::CONTEXT_SEARCH]);
    }

    /**
     * Coerce weight to acceptable values.
     *
     * @param string $weight
     *
     * @return float
     */
    private function getWeight(string $weight): float
    {
        return min(self::MAX_WEIGHT, (float) $weight);
    }
}
