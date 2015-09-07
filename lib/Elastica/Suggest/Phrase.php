<?hh
namespace Elastica\Suggest;

use Elastica\Suggest\CandidateGenerator\AbstractCandidateGenerator;
use Indexish;

/**
 * Class Phrase.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters-phrase.html
 */
class Phrase extends AbstractSuggest
{
    /**
     * @param string $analyzer
     *
     * @return $this
     */
    public function setAnalyzer(string $analyzer) : this
    {
        return $this->setParam('analyzer', $analyzer);
    }

    /**
     * Set the max size of the n-grams (shingles) in the field.
     *
     * @param int $size
     *
     * @return $this
     */
    public function setGramSize(int $size) : this
    {
        return $this->setParam('gram_size', $size);
    }

    /**
     * Set the likelihood of a term being misspelled even if the term exists in the dictionary.
     *
     * @param float $likelihood Defaults to 0.95, meaning 5% of the words are misspelled.
     *
     * @return $this
     */
    public function setRealWordErrorLikelihood(float $likelihood) : this
    {
        return $this->setParam('real_word_error_likelihood', $likelihood);
    }

    /**
     * Set the factor applied to the input phrases score to be used as a threshold for other suggestion candidates.
     * Only candidates which score higher than this threshold will be included in the result.
     *
     * @param float $confidence Defaults to 1.0.
     *
     * @return $this
     */
    public function setConfidence(float $confidence) : this
    {
        return $this->setParam('confidence', $confidence);
    }

    /**
     * Set the maximum percentage of the terms considered to be misspellings in order to form a correction.
     *
     * @param float $max
     *
     * @return $this
     */
    public function setMaxErrors(float $max) : this
    {
        return $this->setParam('max_errors', $max);
    }

    /**
     * @param string $separator
     *
     * @return $this
     */
    public function setSeparator(string $separator) : this
    {
        return $this->setParam('separator', $separator);
    }

    /**
     * Set suggestion highlighting.
     *
     * @param string $preTag
     * @param string $postTag
     *
     * @return $this
     */
    public function setHighlight(string $preTag, string $postTag) : this
    {
        return $this->setParam('highlight', array(
            'pre_tag' => $preTag,
            'post_tag' => $postTag,
        ));
    }

    /**
     * @param float $discount
     *
     * @return $this
     */
    public function setStupidBackoffSmoothing(float $discount = 0.4) : this
    {
        return $this->setSmoothingModel('stupid_backoff', array(
            'discount' => $discount,
        ));
    }

    /**
     * @param float $alpha
     *
     * @return $this
     */
    public function setLaplaceSmoothing(float $alpha = 0.5) : this
    {
        return $this->setSmoothingModel('laplace', array(
            'alpha' => $alpha,
        ));
    }

    /**
     * @param float $trigramLambda
     * @param float $bigramLambda
     * @param float $unigramLambda
     *
     * @return $this
     */
    public function setLinearInterpolationSmoothing(float $trigramLambda, float $bigramLambda, float $unigramLambda) : this
    {
        return $this->setSmoothingModel('linear_interpolation', array(
            'trigram_lambda' => $trigramLambda,
            'bigram_lambda' => $bigramLambda,
            'unigram_lambda' => $unigramLambda,
        ));
    }

    /**
     * @param string $model  the name of the smoothing model
     * @param array  $params
     *
     * @return $this
     */
    public function setSmoothingModel(string $model, array $params) : this
    {
        return $this->setParam('smoothing', array(
            $model => $params,
        ));
    }

    /**
     * @param AbstractCandidateGenerator $generator
     *
     * @return $this
     */
    public function addCandidateGenerator(AbstractCandidateGenerator $generator) : this
    {
        return $this->setParam('candidate_generator', $generator);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() : Indexish<string, mixed>
    {
        $array = parent::toArray();

        $baseName = $this->_getBaseName();

        if (isset(/* UNSAFE_EXPR */ $array[$baseName]['candidate_generator'])) {
            $generator = /* UNSAFE_EXPR */ $array[$baseName]['candidate_generator'];
            unset(/* UNSAFE_EXPR */ $array[$baseName]['candidate_generator']);

            $keys = array_keys($generator);
            $values = array_values($generator);

            if (!isset(/* UNSAFE_EXPR */ $array[$baseName][$keys[0]])) {
                /* UNSAFE_EXPR */
                $array[$baseName][$keys[0]] = array();
            }

            /* UNSAFE_EXPR */
            $array[$baseName][$keys[0]][] = $values[0];
        }

        return $array;
    }
}
