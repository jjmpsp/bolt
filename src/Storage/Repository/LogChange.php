<?php
namespace Bolt\Storage\Repository;

use Bolt\Logger\ChangeLogItem;
use Bolt\Storage\Repository;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A Repository class that handles storage operations for the change log table.
 */
class LogChange extends BaseLog
{
    /**
     * Get content changelog entries for all content types.
     *
     * @param array $options An array with additional options. Currently, the
     *                       following options are supported:
     *                       - 'limit' (int)
     *                       - 'offset' (int)
     *                       - 'order' (string)
     *                       - 'direction' (string)
     *
     * @return \Bolt\Logger\ChangeLogItem[]
     */
    public function getChangeLog(array $options)
    {
        $query = $this->getChangeLogQuery($options);

        $rows = $this->findAll($query);

        $objs = [];
        foreach ($rows as $row) {
            $objs[] = new ChangeLogItem($this->app, $row);
        }

        return $objs;
    }

    /**
     * Build the query to get content changelog entries for all content types.
     *
     * @param array $options
     *
     * @return QueryBuilder
     */
    public function getChangeLogQuery(array $options)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*');

        $qb = $this->setLimitOrder($qb, $options);

        return $qb;
    }

    /**
     * Get a count of change log entries.
     *
     * @return integer
     */
    public function countChangeLog()
    {
        $query = $this->countChangeLogQuery();

        return $this->getCount($query->execute()->fetch());
    }

    /**
     * Build the query to get a count of change log entries.
     *
     * @return QueryBuilder
     */
    public function countChangeLogQuery()
    {
        $qb = $this->createQueryBuilder();
        $qb->select('COUNT(id) as count');

        return $qb;
    }

    /**
     * Get content changelog entries by content type.
     *
     * @param string $contenttype Content type slug
     * @param array  $options     Additional options:
     *                            - 'limit' (integer):     Maximum number of results to return
     *                            - 'order' (string):      Field to order by
     *                            - 'direction' (string):  ASC or DESC
     *                            - 'contentid' (integer): Filter further by content ID
     *                            - 'id' (integer):        Filter by a specific change log entry ID
     *
     * @return \Bolt\Logger\ChangeLogItem[]
     */
    public function getChangeLogByContentType($contenttype, array $options = [])
    {
        $query = $this->getChangeLogByContentTypeQuery($contenttype, $options);
        $rows = $this->findAll($query);

        $objs = [];
        foreach ($rows as $row) {
            $objs[] = new ChangeLogItem($this->app, $row);
        }

        return $objs;
    }

    /**
     * Build query to get content changelog entries by ContentType.
     *
     * @param string $contenttype
     * @param array  $options
     *
     * @return QueryBuilder
     */
    public function getChangeLogByContentTypeQuery($contenttype, array $options)
    {
        $contentTypeRepo = $this->em->getRepository($contenttype);

        $qb = $this->createQueryBuilder();
        $qb->select('log.*, log.title')
            ->from($this->getTableName(), 'log')
            ->leftJoin('log', $contentTypeRepo->getTableName(), 'content', 'content.id = log.contentid');

        // Set required WHERE
        $this->setWhere($qb, $contenttype, $options);

        // Set ORDERBY and LIMIT as requested
        $this->setLimitOrder($qb, $options);

        return $qb;
    }

    /**
     * Conditionally add LIMIT and ORDER BY to a QueryBuilder query.
     *
     * @param QueryBuilder $query
     * @param array        $options Additional options:
     *                              - 'limit' (integer):     Maximum number of results to return
     *                              - 'order' (string):      Field to order by
     *                              - 'direction' (string):  ASC or DESC
     *                              - 'contentid' (integer): Filter further by content ID
     *                              - 'id' (integer):        Filter by a specific change log entry ID
     */
    protected function setLimitOrder(QueryBuilder $query, array $options)
    {
        if (isset($options['order'])) {
            $query->orderBy($options['order'], $options['direction']);
        }
        if (isset($options['limit'])) {
            $query->setMaxResults(intval($options['limit']));

            if (isset($options['offset'])) {
                $query->setFirstResult(intval($options['offset']));
            }
        }
    }
}
