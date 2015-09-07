<?hh
namespace Elastica\Filter;

use Elastica\Exception\InvalidException;
use Elastica\Query\AbstractQuery;
use Indexish;

/**
 * Query filter.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-filter.html
 */
class Query extends AbstractFilter
{
    /**
     * Query.
     *
     * @var array
     */
    protected mixed $_query;

    /**
     * Construct query filter.
     *
     * @param array|\Elastica\Query\AbstractQuery $query
     */
    public function __construct(mixed $query = null)
    {
        if (!is_null($query)) {
            $this->setQuery($query);
        }
    }

    /**
     * Set query.
     *
     * @param array|\Elastica\Query\AbstractQuery $query
     *
     * @throws \Elastica\Exception\InvalidException If parameter is invalid
     *
     * @return $this
     */
    public function setQuery(mixed $query) : this
    {
        if (!$query instanceof AbstractQuery && !$query instanceof Indexish) {
            throw new InvalidException('expected an array or instance of Elastica\Query\AbstractQuery');
        }

        $this->_query = $query;

        return $this;
    }

    /**
     * @see \Elastica\Param::_getBaseName()
     */
    protected function _getBaseName() : string
    {
        if (empty($this->_params)) {
            return 'query';
        } else {
            return 'fquery';
        }
    }

    /**
     * @see \Elastica\Param::toArray()
     */
    public function toArray() : Indexish<string, mixed>
    {
        $data = parent::toArray();

        $name = $this->_getBaseName();
        $filterData = $data[$name];

        if (empty($filterData)) {
            $filterData = $this->_query;
        } else {
            /* UNSAFE_EXPR */
            $filterData['query'] = $this->_query;
        }

        $data[$name] = $filterData;

        return $this->_convertArrayable($data);
    }
}
