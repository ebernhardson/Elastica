<?hh
namespace Elastica\Query;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * QueryString query.
 *
 * @author   Nicolas Ruflin <spam@ruflin.com>, Jasper van Wanrooy <jasper@vanwanrooy.net>
 *
 * @link     http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
 */
class QueryString extends AbstractQuery
{
    /**
     * Query string.
     *
     * @var string Query string
     */
    protected string $_queryString = '';

    /**
     * Creates query string object. Calls setQuery with argument.
     *
     * @param string $queryString OPTIONAL Query string for object
     */
    public function __construct(string $queryString = '')
    {
        $this->setQuery($queryString);
    }

    /**
     * Sets a new query string for the object.
     *
     * @param string $query Query string
     *
     * @throws \Elastica\Exception\InvalidException If given parameter is not a string
     *
     * @return $this
     */
    public function setQuery(string $query = '') : this
    {
        return $this->setParam('query', $query);
    }

    /**
     * Sets the default field.
     *
     * If no field is set, _all is chosen
     *
     * @param string $field Field
     *
     * @return $this
     */
    public function setDefaultField(string $field) : this
    {
        return $this->setParam('default_field', $field);
    }

    /**
     * Sets the default operator AND or OR.
     *
     * If no operator is set, OR is chosen
     *
     * @param string $operator Operator
     *
     * @return $this
     */
    public function setDefaultOperator(string $operator) : this
    {
        return $this->setParam('default_operator', $operator);
    }

    /**
     * Sets the analyzer to analyze the query with.
     *
     * @param string $analyzer Analyser to use
     *
     * @return $this
     */
    public function setAnalyzer(string $analyzer) : this
    {
        return $this->setParam('analyzer', $analyzer);
    }

    /**
     * Sets the parameter to allow * and ? as first characters.
     *
     * If not set, defaults to true.
     *
     * @param bool $allow
     *
     * @return $this
     */
    public function setAllowLeadingWildcard(bool $allow = true) : this
    {
        return $this->setParam('allow_leading_wildcard', (bool) $allow);
    }

    /**
     * Sets the parameter to enable the position increments in result queries.
     *
     * If not set, defaults to true.
     *
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnablePositionIncrements(bool $enabled = true) : this
    {
        return $this->setParam('enable_position_increments', (bool) $enabled);
    }

    /**
     * Sets the fuzzy prefix length parameter.
     *
     * If not set, defaults to 0.
     *
     * @param int $length
     *
     * @return $this
     */
    public function setFuzzyPrefixLength(int $length = 0) : this
    {
        return $this->setParam('fuzzy_prefix_length', (int) $length);
    }

    /**
     * Sets the fuzzy minimal similarity parameter.
     *
     * If not set, defaults to 0.5
     *
     * @param float $minSim
     *
     * @return $this
     */
    public function setFuzzyMinSim(float $minSim = 0.5) : this
    {
        return $this->setParam('fuzzy_min_sim', (float) $minSim);
    }

    /**
     * Sets the phrase slop.
     *
     * If zero, exact phrases are required.
     * If not set, defaults to zero.
     *
     * @param int $phraseSlop
     *
     * @return $this
     */
    public function setPhraseSlop(int $phraseSlop = 0) : this
    {
        return $this->setParam('phrase_slop', (int) $phraseSlop);
    }

    /**
     * Sets the boost value of the query.
     *
     * If not set, defaults to 1.0.
     *
     * @param float $boost
     *
     * @return $this
     */
    public function setBoost(float $boost = 1.0) : this
    {
        return $this->setParam('boost', (float) $boost);
    }

    /**
     * Allows analyzing of wildcard terms.
     *
     * If not set, defaults to true
     *
     * @param bool $analyze
     *
     * @return $this
     */
    public function setAnalyzeWildcard(bool $analyze = true) : this
    {
        return $this->setParam('analyze_wildcard', (bool) $analyze);
    }

    /**
     * Sets the param to automatically generate phrase queries.
     *
     * If not set, defaults to true.
     *
     * @param bool $autoGenerate
     *
     * @return $this
     */
    public function setAutoGeneratePhraseQueries(bool $autoGenerate = true) : this
    {
        return $this->setParam('auto_generate_phrase_queries', (bool) $autoGenerate);
    }

    /**
     * Sets the fields. If no fields are set, _all is chosen.
     *
     * @param array $fields Fields
     *
     * @throws \Elastica\Exception\InvalidException If given parameter is not an array
     *
     * @return $this
     */
    public function setFields(array $fields) : this
    {
        if (!$fields instanceof Indexish) {
            throw new InvalidException('Parameter has to be an array');
        }

        return $this->setParam('fields', $fields);
    }

    /**
     * Whether to use bool or dis_max queries to internally combine results for multi field search.
     *
     * @param bool $value Determines whether to use
     *
     * @return $this
     */
    public function setUseDisMax(bool $value = true) : this
    {
        return $this->setParam('use_dis_max', (bool) $value);
    }

    /**
     * When using dis_max, the disjunction max tie breaker.
     *
     * If not set, defaults to 0.
     *
     * @param int $tieBreaker
     *
     * @return $this
     */
    public function setTieBreaker(int $tieBreaker = 0) : this
    {
        return $this->setParam('tie_breaker', (float) $tieBreaker);
    }

    /**
     * Set a re-write condition. See https://github.com/elasticsearch/elasticsearch/issues/1186 for additional information.
     *
     * @param string $rewrite
     *
     * @return $this
     */
    public function setRewrite(string $rewrite = '') : this
    {
        return $this->setParam('rewrite', $rewrite);
    }

    /**
     * Set timezone option.
     *
     * @param string $timezone
     *
     * @return $this
     */
    public function setTimezone(string $timezone) : this
    {
        return $this->setParam('time_zone', $timezone);
    }

    /**
     * Converts query to array.
     *
     * @see \Elastica\Query\AbstractQuery::toArray()
     *
     * @return array Query array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $params = $this->getParams();
        if (!$params->contains('query')) {
            $params->set('query', $this->_queryString);
        }
        return Map {'query_string' => $params};
    }
}
