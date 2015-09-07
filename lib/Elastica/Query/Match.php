<?hh
namespace Elastica\Query;

/**
 * Match query.
 *
 * @author F21
 * @author WONG Wing Lun <luiges90@gmail.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
 */
class Match extends AbstractQuery
{
    const ZERO_TERM_NONE = 'none';
    const ZERO_TERM_ALL = 'all';

    /**
     * @param string $field
     * @param mixed  $values
     */
    public function __construct(?string $field = null, mixed $values = null)
    {
        if ($field !== null && $values !== null) {
            $this->setParam($field, $values);
        }
    }

    /**
     * Sets a param for the message array.
     *
     * @param string $field
     * @param mixed  $values
     *
     * @return $this
     */
    public function setField(string $field, mixed $values) : this
    {
        return $this->setParam($field, $values);
    }

    /**
     * Sets a param for the given field.
     *
     * @param string $field
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function setFieldParam(string $field, string $key, mixed $value) : this
    {
        if (!isset($this->_params[$field])) {
            $this->_params[$field] = array();
        }

        /* UNSAFE_EXPR */
        $this->_params[$field][$key] = $value;

        return $this;
    }

    /**
     * Sets the query string.
     *
     * @param string $field
     * @param string $query
     *
     * @return $this
     */
    public function setFieldQuery(string $field, string $query) : this
    {
        return $this->setFieldParam($field, 'query', $query);
    }

    /**
     * Set field type.
     *
     * @param string $field
     * @param string $type
     *
     * @return $this
     */
    public function setFieldType(string $field, string $type) : this
    {
        return $this->setFieldParam($field, 'type', $type);
    }

    /**
     * Set field operator.
     *
     * @param string $field
     * @param string $operator
     *
     * @return $this
     */
    public function setFieldOperator(string $field, string $operator) : this
    {
        return $this->setFieldParam($field, 'operator', $operator);
    }

    /**
     * Set field analyzer.
     *
     * @param string $field
     * @param string $analyzer
     *
     * @return $this
     */
    public function setFieldAnalyzer(string $field, string $analyzer) : this
    {
        return $this->setFieldParam($field, 'analyzer', $analyzer);
    }

    /**
     * Set field boost value.
     *
     * If not set, defaults to 1.0.
     *
     * @param string $field
     * @param float  $boost
     *
     * @return $this
     */
    public function setFieldBoost(string $field, float $boost = 1.0) : this
    {
        return $this->setFieldParam($field, 'boost', (float) $boost);
    }

    /**
     * Set field minimum should match.
     *
     * @param string     $field
     * @param int|string $minimumShouldMatch
     *
     * @return $this
     *
     * @link Possible values for minimum_should_match http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-minimum-should-match.html
     */
    public function setFieldMinimumShouldMatch(string $field, mixed $minimumShouldMatch) : this
    {
        return $this->setFieldParam($field, 'minimum_should_match', $minimumShouldMatch);
    }

    /**
     * Set field fuzziness.
     *
     * @param string $field
     * @param mixed  $fuzziness
     *
     * @return $this
     */
    public function setFieldFuzziness(string $field, mixed $fuzziness) : this
    {
        return $this->setFieldParam($field, 'fuzziness', $fuzziness);
    }

    /**
     * Set field fuzzy rewrite.
     *
     * @param string $field
     * @param string $fuzzyRewrite
     *
     * @return $this
     */
    public function setFieldFuzzyRewrite(string $field, string $fuzzyRewrite) : this
    {
        return $this->setFieldParam($field, 'fuzzy_rewrite', $fuzzyRewrite);
    }

    /**
     * Set field prefix length.
     *
     * @param string $field
     * @param int    $prefixLength
     *
     * @return $this
     */
    public function setFieldPrefixLength(string $field, int $prefixLength) : this
    {
        return $this->setFieldParam($field, 'prefix_length', (int) $prefixLength);
    }

    /**
     * Set field max expansions.
     *
     * @param string $field
     * @param int    $maxExpansions
     *
     * @return $this
     */
    public function setFieldMaxExpansions(string $field, int $maxExpansions) : this
    {
        return $this->setFieldParam($field, 'max_expansions', (int) $maxExpansions);
    }

    /**
     * Set zero terms query.
     *
     * If not set, default to 'none'
     *
     * @param string $field
     * @param string $zeroTermQuery
     *
     * @return $this
     */
    public function setFieldZeroTermsQuery(string $field, string $zeroTermQuery = 'none') : this
    {
        return $this->setFieldParam($field, 'zero_terms_query', $zeroTermQuery);
    }

    /**
     * Set cutoff frequency.
     *
     * @param string $field
     * @param float  $cutoffFrequency
     *
     * @return $this
     */
    public function setFieldCutoffFrequency(string $field, float $cutoffFrequency) : this
    {
        return $this->setFieldParam($field, 'cutoff_frequency', $cutoffFrequency);
    }
}
