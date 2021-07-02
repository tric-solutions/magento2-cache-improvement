<?php
/**
 * @copyright Copyright © TRIC Solutions. All rights reserved.
 * @license   https://www.tric.dk/TRIC-LICENSE-COMMUNITY.txt
 * @link      https://www.tric.dk
 */

namespace TRIC\CacheImprovement\MagentoFramework\Mview\View;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\Mview\{View\CollectionInterface, ViewInterface};

/**
 * Class Subscription for handling partial indexation triggers
 */
class Subscription extends \Magento\Framework\Mview\View\Subscription
{
    /**
     * List of columns that can be updated in a subscribed table
     * without creating a new change log entry
     * @var array
     */
    private $ignoredUpdateColumns;

    /**
     * @param ResourceConnection  $resource
     * @param TriggerFactory      $triggerFactory
     * @param CollectionInterface $viewCollection
     * @param ViewInterface       $view
     * @param string              $tableName
     * @param string              $columnName
     * @param array               $ignoredUpdateColumns
     */
    public function __construct(
        ResourceConnection $resource,
        TriggerFactory $triggerFactory,
        CollectionInterface $viewCollection,
        ViewInterface $view,
        $tableName,
        $columnName,
        $ignoredUpdateColumns = []
    ) {
        $this->ignoredUpdateColumns = $ignoredUpdateColumns;
        parent::__construct($resource, $triggerFactory, $viewCollection, $view, $tableName, $columnName, $this->ignoredUpdateColumns);
    }

    /**
     * Build trigger statement for INSERT, UPDATE, DELETE events
     *
     * @param string                                           $event
     * @param \Magento\Framework\Mview\View\ChangelogInterface $changelog
     *
     * @return string
     */
    protected function buildStatement($event, $changelog)
    {
        switch ($event) {
            case Trigger::EVENT_INSERT:
                $trigger = 'INSERT IGNORE INTO %s (%s) VALUES (NEW.%s);';
                break;
            case Trigger::EVENT_UPDATE:
                $tableName = $this->resource->getTableName($this->getTableName());
                $trigger = 'INSERT IGNORE INTO %s (%s) VALUES (NEW.%s);';
                if ($this->connection->isTableExists($tableName) &&
                    $describe = $this->connection->describeTable($tableName)
                ) {
                    $columnNames = array_column($describe, 'COLUMN_NAME');
                    $columnNames = array_diff($columnNames, $this->ignoredUpdateColumns);
                    if ($columnNames) {
                        $columns = [];
                        foreach ($columnNames as $columnName) {
                            // Do not create trigger for indexing when only the qty is changed on the stock item
                            if ($columnName === 'qty' && $tableName === 'cataloginventory_stock_item') {
                                continue;
                            }
                            $columns[] = sprintf(
                                'NOT(NEW.%1$s <=> OLD.%1$s)',
                                $this->connection->quoteIdentifier($columnName)
                            );
                        }
                        $trigger = sprintf(
                            'IF (%s) THEN %s END IF;',
                            implode(' OR ', $columns),
                            $trigger
                        );
                    }
                }
                break;
            case Trigger::EVENT_DELETE:
                $trigger = 'INSERT IGNORE INTO %s (%s) VALUES (OLD.%s);';
                break;
            default:
                return '';
        }

        return sprintf(
            $trigger,
            $this->connection->quoteIdentifier($this->resource->getTableName($changelog->getName())),
            $this->connection->quoteIdentifier($changelog->getColumnName()),
            $this->connection->quoteIdentifier($this->getColumnName())
        );
    }
}
