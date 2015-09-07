<?hh // strict
namespace Elastica\Filter;

use Elastica\Query\AbstractQuery;

/**
 * Nested filter.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-nested-filter.html
 */
class Nested extends AbstractFilter
{
    /**
     * Adds field to mlt filter.
     *
     * @param string $path Nested object path
     *
     * @return $this
     */
    public function setPath(string $path) : this
    {
        return $this->setParam('path', $path);
    }

    /**
     * Sets nested query.
     *
     * @param \Elastica\Query\AbstractQuery $query
     *
     * @return $this
     */
    public function setQuery(AbstractQuery $query) : this
    {
        return $this->setParam('query', $query);
    }

    /**
     * Sets nested filter.
     *
     * @param \Elastica\Filter\AbstractFilter $filter
     *
     * @return $this
     */
    public function setFilter(AbstractFilter $filter) : this
    {
        return $this->setParam('filter', $filter);
    }

    /**
     * Set join option.
     *
     * @param bool $join
     *
     * @return $this
     */
    public function setJoin(bool $join) : this
    {
        return $this->setParam('join', $join);
    }
}
