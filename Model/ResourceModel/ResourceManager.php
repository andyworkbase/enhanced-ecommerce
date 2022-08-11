<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
namespace MageCloud\EnhancedEcommerce\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\Table\StrategyInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Query\Generator as QueryGenerator;
use Magento\Store\Model\Store;

/**
 * Class ResourceManager
 * @package MageCloud\EnhancedEcommerce\Model\ResourceModel
 */
abstract class ResourceManager
{
    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var StrategyInterface
     */
    private $tableStrategy;

    /**
     * @var IndexScopeResolver
     */
    private $tableResolver;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var QueryGenerator|null
     */
    private $batchQueryGenerator;

    /**
     * @var int
     */
    private $batchQuerySize;

    /**
     * @var array
     */
    private $batchQueryResult;

    /**
     * @var string|null
     */
    private $entityType;

    /**
     * @var int[]
     */
    private $entityTypeIdMap;

    /**
     * @var int[]
     */
    private $attributeIdMap;

    /**
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resource
     * @param EavConfig $eavConfig
     * @param StrategyInterface $tableStrategy
     * @param IndexScopeResolver $tableResolver
     * @param QueryGenerator|null $batchQueryGenerator
     * @param $batchQuerySize
     * @param $entityType
     */
    public function __construct(
        MetadataPool $metadataPool,
        ResourceConnection $resource,
        EavConfig $eavConfig,
        StrategyInterface $tableStrategy,
        IndexScopeResolver $tableResolver,
        QueryGenerator $batchQueryGenerator = null,
        $batchQuerySize = 1000,
        $entityType = null
    ) {
        $this->resource = $resource;
        $this->metadataPool = $metadataPool;
        $this->eavConfig = $eavConfig;
        $this->tableStrategy = $tableStrategy;
        $this->tableResolver = $tableResolver;
        $this->batchQueryGenerator = $batchQueryGenerator ?: ObjectManager::getInstance()->get(QueryGenerator::class);
        $this->batchQuerySize = $batchQuerySize;
        $this->entityType = $entityType;
    }

    /**
     * Retrieve Metadata for an entity by entity type
     *
     * @param $entityType
     * @return EntityMetadataInterface
     * @throws \Exception
     */
    protected function getEntityMetaData($entityType = null): EntityMetadataInterface
    {
        if (null === $entityType) {
            $entityType = $this->entityType;
        }
        return $this->metadataPool->getMetadata($entityType);
    }

    /**
     * @param null $entityType
     * @return string
     */
    protected function getLinkField($entityType = null): string
    {
        try {
            $metadata = $this->getEntityMetaData($entityType);
        } catch (\Exception $e) {
            return 'entity_id';
        }
        return $metadata->getLinkField();
    }

    /**
     * Retrieve entity type id
     *
     * @return int
     * @throws \Exception
     */
    public function getEntityTypeId(): int
    {
        if (!isset($this->entityTypeIdMap[$this->entityType])) {
            $this->entityTypeIdMap[$this->entityType] = (int)$this->eavConfig->getEntityType(
                $this->metadataPool->getMetadata($this->entityType)->getEavEntityType()
            )->getId();
        }
        return $this->entityTypeIdMap[$this->entityType];
    }

    /**
     * @param $attributeCode
     * @param $entityTypeId
     * @param $connectionName
     * @return mixed
     * @throws \Exception
     */
    public function getAttributeId($attributeCode, $entityTypeId, $connectionName = null)
    {
        $connection = $this->getConnection($connectionName);
        if (!isset($this->attributeIdMap[$entityTypeId][$attributeCode])) {
            $select = $connection->select()
                ->from($this->getTable('eav_attribute', $connectionName))
                ->where(AttributeInterface::ATTRIBUTE_CODE . ' = ?', $attributeCode)
                ->where(AttributeInterface::ENTITY_TYPE_ID . ' = ?', $this->getEntityTypeId())
                ->reset(Select::COLUMNS)
                ->columns(AttributeInterface::ATTRIBUTE_ID);
            $this->attributeIdMap[$entityTypeId][$attributeCode] = (int)$connection->fetchOne($select);
        }
        return $this->attributeIdMap[$entityTypeId][$attributeCode];
    }

    /**
     * @param $attributeCode
     * @param $table
     * @param $entityIds
     * @param $storeId
     * @param $connectionName
     * @return array
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    public function getAttributeValues(
        $attributeCode,
        $table,
        $entityIds,
        $storeId = null,
        $connectionName = null
    ): array {
        $connection = $this->getConnection($connectionName);
        $linkedField = $this->getLinkField();
        $attributeId = $this->getAttributeId($attributeCode, $this->getEntityTypeId());
        $attributeTable = $this->getTable($table);

        if (null === $storeId) {
            $select = $connection->select()
                ->from(
                    $attributeTable,
                    ['value']
                )->where(
                    'attribute_id = ?',
                    $attributeId,
                    \Zend_Db::INT_TYPE
                )->where(
                    'store_id = ?',
                    Store::DEFAULT_STORE_ID,
                    \Zend_Db::INT_TYPE
                )->where(
                    $linkedField . ' IN(?)',
                    $entityIds,
                    \Zend_Db::INT_TYPE
                );
        } else {
            $select = $connection->select()
                ->from(
                    ['t1' => $attributeTable],
                    [
                        'value' => $connection->getCheckSql(
                            't2.value_id > 0',
                            't2.value',
                            't1.value'
                        )
                    ]
                )->joinLeft(
                    ['t2' => $attributeTable],
                    "t1.{$linkedField} = t2.{$linkedField}"
                    . ' AND t1.attribute_id = t2.attribute_id '
                    . ' AND t2.store_id = ' . $storeId,
                    []
                )->where(
                    't1.store_id = ?',
                    Store::DEFAULT_STORE_ID,
                    \Zend_Db::INT_TYPE
                )->where(
                    't1.attribute_id = ?',
                    $attributeId,
                    \Zend_Db::INT_TYPE
                )->where(
                    "t1.{$linkedField} IN(?)",
                    $entityIds,
                    \Zend_Db::INT_TYPE
                );
        }

        $result = [];
        $query = $select->query();
        while ($row = $query->fetch()) {
            if (isset($row['value'])) {
                $result[] = trim($row['value']);
            }
        }

        return $result;
    }

    /**
     * Get table name using the adapter.
     *
     * @param $tableName
     * @param null $connectionName
     * @return string
     */
    public function getTable($tableName, $connectionName = null): string
    {
        return $this->resource->getTableName($tableName, $connectionName);
    }

    /**
     * Return database connection.
     *
     * @param null $connectionName
     * @return AdapterInterface
     */
    public function getConnection($connectionName = null): AdapterInterface
    {
        return $this->resource->getConnection($connectionName);
    }

    /**
     * Get table strategy
     *
     * @return StrategyInterface
     */
    public function getTableStrategy(): StrategyInterface
    {
        return $this->tableStrategy;
    }

    /**
     * @param $storeId
     * @param $index
     * @return string
     */
    public function getIndexTableName($storeId, $index): string
    {
        $dimensions = new Dimension(
            \Magento\Store\Model\Store::ENTITY,
            $storeId
        );
        return $this->tableResolver->resolve(
            $index,
            [
                $dimensions
            ]
        );
    }

    /**
     * Prepare select statement for specific filter
     *
     * @param array $data
     * @param $tableName
     * @param null $connectionName
     * @return Select
     */
    protected function prepareSelect(array $data, $tableName, $connectionName = null): Select
    {
        $connection = $this->getConnection($connectionName);
        $select = $connection->select();
        $select->from($this->getTable($tableName, $connectionName));
        foreach ($data as $column => $value) {
            if ($value) {
                $select->where($connection->quoteIdentifier($column) . ' IN (?)', $value);
            } else {
                $select->where($connection->quoteIdentifier($column) . ' IN (0, NULL, \'\')');
            }
        }
        return $select;
    }

    /**
     * @param array $rows
     * @return $this
     */
    public function setBatchQueryResult(array $rows)
    {
        $this->batchQueryResult = $rows;
        return $this;
    }

    /**
     * @return array
     */
    public function getBatchQueryResult(): array
    {
        return $this->batchQueryResult;
    }

    /**
     * @return $this
     */
    public function resetBatchQueryResult()
    {
        $this->batchQueryResult = [];
        return $this;
    }

    /**
     * @param $select
     * @param $rangeField
     * @param null $connectionName
     * @return void
     * @throws LocalizedException
     */
    public function executeBatchQuery($select, $rangeField, $connectionName = null): void
    {
        $batchQueries = $this->batchQueryGenerator->generate($rangeField, $select, $this->batchQuerySize);
        $query = $batchQueries->current();
        do {
            $this->setBatchQueryResult(
                array_merge($this->getBatchQueryResult(), $this->getConnection($connectionName)->fetchAll($query))
            );
        } while ($query = $batchQueries->next());
    }
}