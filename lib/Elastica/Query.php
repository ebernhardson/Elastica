<?hh
namespace Elastica;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Exception\InvalidException;
use Elastica\Exception\NotImplementedException;
use Elastica\Facet\AbstractFacet;
use Elastica\Filter\AbstractFilter;
use Elastica\Query\AbstractQuery;
use Elastica\Query\MatchAll;
use Elastica\Query\QueryString;
use Elastica\Suggest\AbstractSuggest;
use Indexish;

/**
 * Elastica query object.
 *
 * Creates different types of queries
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-body.html
 */
class Query extends Param
{
    /**
     * Params.
     *
     * @var array Params
     */
    protected Map<string, mixed> $_params = Map {};

    /**
     * Suggest query or not.
     *
     * @var int Suggest
     */
    protected int $_suggest = 0;

    /**
     * Creates a query object.
     *
     * @param array|\Elastica\Query\AbstractQuery $query OPTIONAL Query object (default = null)
     */
    public function __construct(mixed $query = null)
    {
        if ($query instanceof Map) {
            $this->setRawQuery($query);
        } elseif ($query instanceof AbstractQuery) {
            $this->setQuery($query);
        } elseif ($query instanceof Suggest) {
            $this->setSuggest($query);
        } elseif ($query !== null) {
            throw new \InvalidArgumentException('expected Map, AbstractQuery or Suggest');
        }
    }

    /**
     * Transforms a string or an array to a query object.
     *
     * If query is empty,
     *
     * @param mixed $query
     *
     * @throws \Elastica\Exception\NotImplementedException
     *
     * @return self
     */
    public static function create(mixed $query) : Query
    {
        if ($query instanceof self) {
                return $query;
        } elseif ($query instanceof AbstractQuery) {
                return new self($query);
        } elseif ($query instanceof AbstractFilter) {
                $newQuery = new self();
                $newQuery->setPostFilter($query);

                return $newQuery;
        } elseif (empty($query)) {
                return new self(new MatchAll());
        } elseif ($query instanceof Indexish) {
                return new self($query);
        } elseif (is_string($query)) {
                return new self(new QueryString($query));
        } elseif ($query instanceof AbstractSuggest) {
                return new self(new Suggest($query));
        } elseif ($query instanceof Suggest) {
                return new self($query);
        }

        // TODO: Implement queries without
        throw new NotImplementedException();
    }

    /**
     * Sets query as raw array. Will overwrite all already set arguments.
     *
     * @param array $query Query array
     *
     * @return $this
     */
    public function setRawQuery(Map<string, mixed> $query) : this
    {
        $this->_params = $query;

        return $this;
    }

    /**
     * Sets the query.
     *
     * @param \Elastica\Query\AbstractQuery $query Query object
     *
     * @return $this
     */
    public function setQuery(AbstractQuery $query) : this
    {
        return $this->setParam('query', $query);
    }

    /**
     * Gets the query array.
     *
     * @return AbstractQuery
     **/
    public function getQuery() : AbstractQuery
    {
        $query = $this->getParam('query');
        if ($query instanceof AbstractQuery) {
            return $query;
        }
        throw new \RuntimeException('expected AbstractQuery');
    }

    /**
     * Set Filter.
     *
     * @param \Elastica\Filter\AbstractFilter $filter Filter object
     *
     * @return $this
     *
     * @link    https://github.com/elasticsearch/elasticsearch/issues/7422
     * @deprecated
     */
    public function setFilter(AbstractFilter $filter) : this
    {
        trigger_error('Deprecated: Elastica\Query::setFilter() is deprecated. Use Elastica\Query::setPostFilter() instead.', E_USER_DEPRECATED);

        return $this->setPostFilter($filter);
    }

    /**
     * Sets the start from which the search results should be returned.
     *
     * @param int $from
     *
     * @return $this
     */
    public function setFrom(int $from) : this
    {
        return $this->setParam('from', $from);
    }

    /**
     * Sets sort arguments for the query
     * Replaces existing values.
     *
     * @param array $sortArgs Sorting arguments
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
     */
    public function setSort(array $sortArgs) : this
    {
        return $this->setParam('sort', $sortArgs);
    }

    /**
     * Adds a sort param to the query.
     *
     * @param mixed $sort Sort parameter
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
     */
    public function addSort(mixed $sort) : this
    {
        return $this->addParam('sort', $sort);
    }

    /**
     * Sets highlight arguments for the query.
     *
     * @param array $highlightArgs Set all highlight arguments
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
     */
    public function setHighlight(array $highlightArgs) : this
    {
        return $this->setParam('highlight', $highlightArgs);
    }

    /**
     * Adds a highlight argument.
     *
     * @param mixed $highlight Add highlight argument
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
     */
    public function addHighlight(mixed $highlight) : this
    {
        return $this->addParam('highlight', $highlight);
    }

    /**
     * Sets maximum number of results for this query.
     *
     * @param int $size OPTIONAL Maximal number of results for query (default = 10)
     *
     * @return $this
     */
    public function setSize(int $size = 10) : this
    {
        return $this->setParam('size', $size);
    }

    /**
     * Alias for setSize.
     *
     * @deprecated Use the setSize() method, this method will be removed in future releases
     *
     * @param int $limit OPTIONAL Maximal number of results for query (default = 10)
     *
     * @return $this
     */
    public function setLimit(int $limit = 10) : this
    {
        return $this->setSize($limit);
    }

    /**
     * Enables explain on the query.
     *
     * @param bool $explain OPTIONAL Enabled or disable explain (default = true)
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-explain.html
     */
    public function setExplain(bool $explain = true) : this
    {
        return $this->setParam('explain', $explain);
    }

    /**
     * Enables version on the query.
     *
     * @param bool $version OPTIONAL Enabled or disable version (default = true)
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-version.html
     */
    public function setVersion(bool $version = true) : this
    {
        return $this->setParam('version', $version);
    }

    /**
     * Sets the fields to be returned by the search
     * NOTICE php will encode modified(or named keys) array into object format in json format request
     * so the fields array must a sequence(list) type of array.
     *
     * @param array $fields Fields to be returned
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-fields.html
     */
    public function setFields(array $fields) : this
    {
        return $this->setParam('fields', $fields);
    }

    /**
     * Set script fields.
     *
     * @param array|\Elastica\ScriptFields $scriptFields Script fields
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-script-fields.html
     */
    public function setScriptFields(mixed $scriptFields) : this
    {
        if ($scriptFields instanceof Indexish) {
            $scriptFields = new ScriptFields($scriptFields);
        }

        return $this->setParam('script_fields', $scriptFields);
    }

    /**
     * Adds a Script to the query.
     *
     * @param string                   $name
     * @param \Elastica\AbstractScript $script Script object
     *
     * @return $this
     */
    public function addScriptField(string $name, AbstractScript $script) : this
    {
        if (!isset(/* UNSAFE_EXPR */ $this->_params['script_fields'])) {
			/* UNSAFE_EXPR */
            $this->_params['script_fields'] = Map {};
        }
		/* UNSAFE_EXPR */
        $this->_params['script_fields'][$name] = $script;

        return $this;
    }

    /**
     * Sets all facets for this query object. Replaces existing facets.
     *
     * @param array $facets List of facet objects
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-facets.html
     * @deprecated Facets are deprecated and will be removed in a future release. You are encouraged to migrate to aggregations instead.
     */
    public function setFacets(array $facets) : this
    {
        $this->_params['facets'] = array();
        foreach ($facets as $facet) {
            $this->addFacet($facet);
        }

        return $this;
    }

    /**
     * Adds a Facet to the query.
     *
     * @param \Elastica\Facet\AbstractFacet $facet Facet object
     *
     * @return $this
     *
     * @deprecated Facets are deprecated and will be removed in a future release. You are encouraged to migrate to aggregations instead.
     */
    public function addFacet(AbstractFacet $facet) : this
    {
        if (isset(/* UNSAFE_EXPR */ $this->_params['facets'])) {
            /* UNSAFE_EXPR */
            $this->_params['facets'][] = $facet;
        } else {
            $this->_params['facets'] = Vector {$facet};
        }

        return $this;
    }

    /**
     * Adds an Aggregation to the query.
     *
     * @param AbstractAggregation $agg
     *
     * @return $this
     */
    public function addAggregation(AbstractAggregation $agg) : this
    {
        if (!array_key_exists('aggs', $this->_params)) {
            $this->_params['aggs'] = array();
        }

        if (isset(/* UNSAFE_EXPR */ $this->_params['aggs'])) {
            /* UNSAFE_EXPR */
            $this->_params['aggs'][] = $agg;
        } else {
            $this->_params['aggs'] = Vector {$agg};
        }

        return $this;
    }

    /**
     * Converts all query params to an array.
     *
     * @return array Query array
     */
    public function toArray() : Indexish<string, mixed>
    {
        if (!isset($this->_params['query']) && ($this->_suggest == 0)) {
            $this->setQuery(new MatchAll());
        }

        if (isset($this->_params['facets']) && 0 === count($this->_params['facets'])) {
            unset($this->_params['facets']);
        }

        if (isset($this->_params['post_filter']) && 0 === count($this->_params['post_filter'])) {
            unset($this->_params['post_filter']);
        }

        $array = $this->_convertArrayable($this->_params);

        if (isset($array['suggest'])) {
            $array['suggest'] = /* UNSAFE_EXPR */ $array['suggest']['suggest'];
        }

        return $array;
    }

    /**
     * Allows filtering of documents based on a minimum score.
     *
     * @param float $minScore Minimum score to filter documents by
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return $this
     */
    public function setMinScore(float $minScore) : this
    {
        if (!is_numeric($minScore)) {
            throw new InvalidException('has to be numeric param');
        }

        return $this->setParam('min_score', $minScore);
    }

    /**
     * Add a suggest term.
     *
     * @param \Elastica\Suggest $suggest suggestion object
     *
     * @return $this
     */
    public function setSuggest(Suggest $suggest) : this
    {
        $this->setParam('suggest', $suggest);

        $this->_suggest = 1;

        return $this;
    }

    /**
     * Add a Rescore.
     *
     * @param mixed $rescore suggestion object
     *
     * @return $this
     */
    public function setRescore(mixed $rescore) : this
    {
        if ($rescore instanceof Indexish) {
            $buffer = array();

            foreach ($rescore as $rescoreQuery) {
                $buffer [] = $rescoreQuery;
            }
        } else {
            $buffer = $rescore;
        }

        return $this->setParam('rescore', $buffer);
    }

    /**
     * Sets the _source field to be returned with every hit.
     *
     * @param array|bool $params Fields to be returned or false to disable source
     *
     * @return $this
     *
     * @link   http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-source-filtering.html
     */
    public function setSource(mixed $params) : this
    {
        return $this->setParam('_source', $params);
    }

    /**
     * Sets post_filter argument for the query. The filter is applied after the query has executed.
     *
     * @param array|\Elastica\Filter\AbstractFilter $filter
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-post-filter.html
     */
    public function setPostFilter(mixed $filter) : this
    {
        if (!($filter instanceof AbstractFilter)) {
            trigger_error('Deprecated: Elastica\Query::setPostFilter() passing filter as array is deprecated. Pass instance of AbstractFilter instead.', E_USER_DEPRECATED);
        }

        return $this->setParam('post_filter', $filter);
    }
}
