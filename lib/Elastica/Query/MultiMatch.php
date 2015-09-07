<?hh
namespace Elastica\Query;

/**
 * Multi Match.
 *
 * @author Rodolfo Adhenawer Campagnoli Moraes <adhenawer@gmail.com>
 * @author Wong Wing Lun <luiges90@gmail.com>
 * @author Tristan Maindron <tmaindron@gmail.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
 */
class MultiMatch extends AbstractQuery
{
    const TYPE_BEST_FIELDS = 'best_fields';
    const TYPE_MOST_FIELDS = 'most_fields';
    const TYPE_CROSS_FIELDS = 'cross_fields';
    const TYPE_PHRASE = 'phrase';
    const TYPE_PHRASE_PREFIX = 'phrase_prefix';

    const OPERATOR_OR = 'or';
    const OPERATOR_AND = 'and';

    const ZERO_TERM_NONE = 'none';
    const ZERO_TERM_ALL = 'all';

    /**
     * Sets the query.
     *
     * @param string $query Query
     *
     * @return $this
     */
    public function setQuery(string $query = '') : this
    {
        return $this->setParam('query', $query);
    }

    /**
     * Sets Fields to be used in the query.
     *
     * @param array $fields Fields
     *
     * @return $this
     */
    public function setFields(array $fields = array()) : this
    {
        return $this->setParam('fields', $fields);
    }

    /**
     * Sets use dis max indicating to either create a dis_max query or a bool query.
     *
     * If not set, defaults to true.
     *
     * @param bool $useDisMax
     *
     * @return $this
     */
    public function setUseDisMax(bool $useDisMax = true) : this
    {
        return $this->setParam('use_dis_max', $useDisMax);
    }

    /**
     * Sets tie breaker to multiplier value to balance the scores between lower and higher scoring fields.
     *
     * If not set, defaults to 0.0.
     *
     * @param float $tieBreaker
     *
     * @return $this
     */
    public function setTieBreaker(float $tieBreaker = 0.0) : this
    {
        return $this->setParam('tie_breaker', $tieBreaker);
    }

    /**
     * Sets operator for Match Query.
     *
     * If not set, defaults to 'or'
     *
     * @param string $operator
     *
     * @return $this
     */
    public function setOperator(string $operator = 'or') : this
    {
        return $this->setParam('operator', $operator);
    }

    /**
     * Set field minimum should match for Match Query.
     *
     * @param mixed $minimumShouldMatch
     *
     * @return $this
     */
    public function setMinimumShouldMatch(mixed $minimumShouldMatch) : this
    {
        return $this->setParam('minimum_should_match', $minimumShouldMatch);
    }

    /**
     * Set zero terms query for Match Query.
     *
     * If not set, default to 'none'
     *
     * @param string $zeroTermQuery
     *
     * @return $this
     */
    public function setZeroTermsQuery(string $zeroTermQuery = 'none') : this
    {
        return $this->setParam('zero_terms_query', $zeroTermQuery);
    }

    /**
     * Set cutoff frequency for Match Query.
     *
     * @param float $cutoffFrequency
     *
     * @return $this
     */
    public function setCutoffFrequency(float $cutoffFrequency) : this
    {
        return $this->setParam('cutoff_frequency', $cutoffFrequency);
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type) : this
    {
        return $this->setParam('type', $type);
    }

    /**
     * Set fuzziness.
     *
     * @param float $fuzziness
     *
     * @return $this
     */
    public function setFuzziness(float $fuzziness) : this
    {
        return $this->setParam('fuzziness', (float) $fuzziness);
    }

    /**
     * Set prefix length.
     *
     * @param int $prefixLength
     *
     * @return $this
     */
    public function setPrefixLength(int $prefixLength) : this
    {
        return $this->setParam('prefix_length', (int) $prefixLength);
    }

    /**
     * Set max expansions.
     *
     * @param int $maxExpansions
     *
     * @return $this
     */
    public function setMaxExpansions(int $maxExpansions) : this
    {
        return $this->setParam('max_expansions', (int) $maxExpansions);
    }

    /**
     * Set analyzer.
     *
     * @param string $analyzer
     *
     * @return $this
     */
    public function setAnalyzer(string $analyzer) : this
    {
        return $this->setParam('analyzer', $analyzer);
    }
}
