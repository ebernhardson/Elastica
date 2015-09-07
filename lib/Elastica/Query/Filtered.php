<?hh
namespace Elastica\Query;

use Elastica\ArrayableInterface;
use Elastica\Exception\InvalidException;
use Elastica\Filter\AbstractFilter;

/**
 * Filtered query. Needs a query and a filter.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-filtered-query.html
 */
class Filtered extends AbstractQuery
{
    /**
     * Constructs a filtered query.
     *
     * @param \Elastica\Query\AbstractQuery   $query  OPTIONAL Query object
     * @param \Elastica\Filter\AbstractFilter $filter OPTIONAL Filter object
     */
    public function __construct(?AbstractQuery $query = null, ?AbstractFilter $filter = null)
    {
        $this->setQuery($query);
        $this->setFilter($filter);
    }

    /**
     * Sets a query.
     *
     * @param \Elastica\Query\AbstractQuery $query Query object
     *
     * @return $this
     */
    public function setQuery(?AbstractQuery $query = null) : this
    {
        return $this->setParam('query', $query);
    }

    /**
     * Sets the filter.
     *
     * @param \Elastica\Filter\AbstractFilter $filter Filter object
     *
     * @return $this
     */
    public function setFilter(?AbstractFilter $filter = null) : this
    {
        return $this->setParam('filter', $filter);
    }

    /**
     * Gets the filter.
     *
     * @return \Elastica\Filter\AbstractFilter
     */
    public function getFilter() : ?AbstractFilter
    {
        $filter = $this->getParam('filter');
        if ($filter === null) {
            return null;
        }
        if ($filter instanceof AbstractFilter) {
            return $filter;
        }
        throw new \RuntimeException('Expected null or AbstractFilter');
    }

    /**
     * Gets the query.
     *
     * @return \Elastica\Query\AbstractQuery
     */
    public function getQuery() : ?AbstractQuery
    {
        $query = $this->getParam('query');
        if ($query === null) {
            return null;
        }
        if ($query instanceof AbstractQuery) {
            return $query;
        }
        throw new \RuntimeException('Expected null or AbstractQuery');
    }

    /**
     * Converts query to array.
     *
     * @return array Query array
     *
     * @see \Elastica\Query\AbstractQuery::toArray()
     */
    public function toArray() : array
    {
        $filtered = array();

        if ($this->hasParam('query') && $this->getParam('query') instanceof AbstractQuery) {
            $query = $this->getParam('query');
            if (!$query instanceof ArrayableInterface) {
                throw new \InvalidArgumentException('expected query to have ArrayableInterface');
            }
            $filtered['query'] = $query->toArray();
        }

        if ($this->hasParam('filter') && $this->getParam('filter') instanceof AbstractFilter) {
            $filter = $this->getParam('filter');
            if (!$filter instanceof ArrayableInterface) {
                throw new \InvalidArgumentException('expected filter to have ArrayableInterface');
            }
            $filtered['filter'] = $filter->toArray();
        }

        if (empty($filtered)) {
            throw new InvalidException('A query and/or filter is required');
        }

        return array('filtered' => $filtered);
    }
}
