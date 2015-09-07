<?hh
namespace Elastica\Rescore;

use Elastica\Query as BaseQuery;
use Indexish;

/**
 * Query Rescore.
 *
 * @author Jason Hu <mjhu91@gmail.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-rescore.html
 */
class Query extends AbstractRescore
{
    /**
     * Constructor.
     *
     * @param string|\Elastica\Query\AbstractQuery $rescoreQuery
     * @param string|\Elastica\Query\AbstractQuery $query
     */
    public function __construct(mixed $query = null)
    {
        $this->setParam('query', array());
        $this->setRescoreQuery($query);
    }

    /**
     * Override default implementation so params are in the format
     * expected by elasticsearch.
     *
     * @return array Rescore array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $data = $this->getParams();

        if (!empty($this->_rawParams)) {
            $data = array_merge($data, $this->_rawParams);
        }

        $array = $this->_convertArrayable($data);

        if (/* UNSAFE_EXPR */ isset($array['query']['rescore_query']['query'])) {
            /* UNSAFE_EXPR */
            $array['query']['rescore_query'] = $array['query']['rescore_query']['query'];
        }

        return $array;
    }

    /**
     * Sets rescoreQuery object.
     *
     * @param string|\Elastica\Query|\Elastica\Query\AbstractQuery $query
     *
     * @return $this
     */
    public function setRescoreQuery(mixed $rescoreQuery) : this
    {
        $rescoreQuery = BaseQuery::create($rescoreQuery);

        $query = $this->getParam('query');
        if (!$query instanceof Indexish) {
            throw new \InvalidArgumentException('expected query array');
        }
        $query['rescore_query'] = $rescoreQuery;

        return $this->setParam('query', $query);
    }

    /**
     * Sets query_weight.
     *
     * @param float $weight
     *
     * @return $this
     */
    public function setQueryWeight(float $weight) : this
    {
        $query = $this->getParam('query');
        if (!$query instanceof Indexish) {
            throw new \InvalidArgumentException('expected query array');
        }
        $query['query_weight'] = $weight;

        return $this->setParam('query', $query);
    }

    /**
     * Sets rescore_query_weight.
     *
     * @param float $size
     *
     * @return $this
     */
    public function setRescoreQueryWeight(float $weight) : this
    {
        $query = $this->getParam('query');
        if (!$query instanceof Indexish) {
            throw new \InvalidArgumentException('expected query array');
        }
        $query['rescore_query_weight'] = $weight;

        return $this->setParam('query', $query);
    }
}
