<?hh
namespace Elastica\Query;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * Constant score query.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-constant-score-query.html
 */
class ConstantScore extends AbstractQuery
{
    /**
     * Construct constant score query.
     *
     * @param null|\Elastica\Filter\AbstractFilter|array $filter
     */
    public function __construct(mixed $filter = null)
    {
        if (!is_null($filter)) {
            $this->setFilter($filter);
        }
    }

    /**
     * Set filter.
     *
     * @param array|\Elastica\Filter\AbstractFilter $filter
     *
     * @return $this
     */
    public function setFilter(mixed $filter) : this
    {
        return $this->setParam('filter', $filter);
    }

    /**
     * Set query.
     *
     * @param array|\Elastica\Query\AbstractQuery $query
     *
     * @throws InvalidException If query is not an array or instance of AbstractQuery
     *
     * @return $this
     */
    public function setQuery(mixed $query) : this
    {
        if (!$query instanceof Indexish && !($query instanceof AbstractQuery)) {
            throw new InvalidException('Invalid parameter. Has to be array or instance of Elastica\Query\AbstractQuery');
        }

        return $this->setParam('query', $query);
    }

    /**
     * Set boost.
     *
     * @param float $boost
     *
     * @return $this
     */
    public function setBoost(float $boost) : this
    {
        return $this->setParam('boost', $boost);
    }
}
