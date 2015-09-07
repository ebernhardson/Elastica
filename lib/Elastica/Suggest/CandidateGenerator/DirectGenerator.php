<?hh // strict
namespace Elastica\Suggest\CandidateGenerator;

/**
 * Class DirectGenerator.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters-phrase.html#_direct_generators
 */
class DirectGenerator extends AbstractCandidateGenerator
{
    const SUGGEST_MODE_MISSING = 'missing';
    const SUGGEST_MODE_POPULAR = 'popular';
    const SUGGEST_MODE_ALWAYS = 'always';

    /**
     * @param string $field
     */
    public function __construct(string $field)
    {
        $this->setField($field);
    }

    /**
     * Set the field name from which to fetch candidate suggestions.
     *
     * @param string $field
     *
     * @return $this
     */
    public function setField(string $field) : this
    {
        return $this->setParam('field', $field);
    }

    /**
     * Set the maximum corrections to be returned per suggest text token.
     *
     * @param int $size
     *
     * @return $this
     */
    public function setSize(int $size) : this
    {
        return $this->setParam('size', $size);
    }

    /**
     * @param string $mode see SUGGEST_MODE_* constants for options
     *
     * @return $this
     */
    public function setSuggestMode(string $mode) : this
    {
        return $this->setParam('suggest_mode', $mode);
    }

    /**
     * @param int $max can only be a value between 1 and 2. Defaults to 2.
     *
     * @return $this
     */
    public function setMaxEdits(int $max) : this
    {
        return $this->setParam('max_edits', $max);
    }

    /**
     * @param int $length defaults to 1
     *
     * @return $this
     */
    public function setPrefixLength(int $length) : this
    {
        return $this->setParam('prefix_len', $length);
    }

    /**
     * @param int $min defaults to 4
     *
     * @return $this
     */
    public function setMinWordLength(int $min) : this
    {
        return $this->setParam('min_word_len', $min);
    }

    /**
     * @param int $max
     *
     * @return $this
     */
    public function setMaxInspections(int $max) : this
    {
        return $this->setParam('max_inspections', $max);
    }

    /**
     * @param float $min
     *
     * @return $this
     */
    public function setMinDocFrequency(float $min) : this
    {
        return $this->setParam('min_doc_freq', $min);
    }

    /**
     * @param float $max
     *
     * @return $this
     */
    public function setMaxTermFrequency(float $max) : this
    {
        return $this->setParam('max_term_freq', $max);
    }

    /**
     * Set an analyzer to be applied to the original token prior to candidate generation.
     *
     * @param string $pre an analyzer
     *
     * @return $this
     */
    public function setPreFilter(string $pre) : this
    {
        return $this->setParam('pre_filter', $pre);
    }

    /**
     * Set an analyzer to be applied to generated tokens before they are passed to the phrase scorer.
     *
     * @param string $post
     *
     * @return $this
     */
    public function setPostFilter(string $post) : this
    {
        return $this->setParam('post_filter', $post);
    }
}
