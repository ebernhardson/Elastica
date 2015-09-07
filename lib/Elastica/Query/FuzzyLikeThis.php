<?hh
namespace Elastica\Query;

use Indexish;

/**
 * Fuzzy Like This query.
 *
 * @author Raul Martinez, Jr <juneym@gmail.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-flt-query.html
 */
class FuzzyLikeThis extends AbstractQuery
{
    /**
     * Field names.
     *
     * @var array Field names
     */
    protected array<string> $_fields = array();

    /**
     * Like text.
     *
     * @var string Like text
     */
    protected string $_likeText = '';

    /**
     * Ignore term frequency.
     *
     * @var bool ignore term frequency
     */
    protected bool $_ignoreTF = false;

    /**
     * Max query terms value.
     *
     * @var int Max query terms value
     */
    protected int $_maxQueryTerms = 25;

    /**
     * minimum similarity.
     *
     * @var float minimum similarity
     */
    protected float $_minSimilarity = 0.5;

    /**
     * Prefix Length.
     *
     * @var int Prefix Length
     */
    protected int $_prefixLength = 0;

    /**
     * Boost.
     *
     * @var float Boost
     */
    protected float $_boost = 1.0;

    /**
     * Analyzer.
     *
     * @var string Analyzer
     */
    protected string $_analyzer = '';

    /**
     * Adds field to flt query.
     *
     * @param array $fields Field names
     *
     * @return $this
     */
    public function addFields(array<string> $fields) : this
    {
        $this->_fields = $fields;

        return $this;
    }

    /**
     * Set the "like_text" value.
     *
     * @param string $text
     *
     * @return $this
     */
    public function setLikeText(string $text) : this
    {
        $text = trim($text);
        $this->_likeText = $text;

        return $this;
    }

    /**
     * Set the "ignore_tf" value (ignore term frequency).
     *
     * @param bool $ignoreTF
     *
     * @return $this
     */
    public function setIgnoreTF(bool $ignoreTF) : this
    {
        $this->_ignoreTF = (bool) $ignoreTF;

        return $this;
    }

    /**
     * Set the minimum similarity.
     *
     * @param float $value
     *
     * @return $this
     */
    public function setMinSimilarity(float $value) : this
    {
        $this->_minSimilarity = $value;

        return $this;
    }

    /**
     * Set boost.
     *
     * @param float $value Boost value
     *
     * @return $this
     */
    public function setBoost(float $value) : this
    {
        $this->_boost = (float) $value;

        return $this;
    }

    /**
     * Set Prefix Length.
     *
     * @param int $value Prefix length
     *
     * @return $this
     */
    public function setPrefixLength(int $value) : this
    {
        $this->_prefixLength = (int) $value;

        return $this;
    }

    /**
     * Set max_query_terms.
     *
     * @param int $value Max query terms value
     *
     * @return $this
     */
    public function setMaxQueryTerms(int $value) : this
    {
        $this->_maxQueryTerms = (int) $value;

        return $this;
    }

    /**
     * Set analyzer.
     *
     * @param string $text Analyzer text
     *
     * @return $this
     */
    public function setAnalyzer(string $text) : this
    {
        $text = trim($text);
        $this->_analyzer = $text;

        return $this;
    }

    /**
     * Converts fuzzy like this query to array.
     *
     * @return array Query array
     *
     * @see \Elastica\Query\AbstractQuery::toArray()
     */
    public function toArray() : Indexish<string, mixed>
    {
        $args = array();
        if (!empty($this->_fields)) {
            $args['fields'] = $this->_fields;
        }

        if (!empty($this->_boost)) {
            $args['boost'] = $this->_boost;
        }

        if (!empty($this->_analyzer)) {
            $args['analyzer'] = $this->_analyzer;
        }

        $args['min_similarity'] = ($this->_minSimilarity > 0) ? $this->_minSimilarity : 0;

        $args['like_text'] = $this->_likeText;
        $args['prefix_length'] = $this->_prefixLength;
        $args['ignore_tf'] = $this->_ignoreTF;
        $args['max_query_terms'] = $this->_maxQueryTerms;

        $data = parent::toArray();

		foreach (/* UNSAFE_EXPR */ $data['fuzzy_like_this'] as $k => $v) {
			$args[$k] = $v;
		}

        return array('fuzzy_like_this' => $args);
    }
}
