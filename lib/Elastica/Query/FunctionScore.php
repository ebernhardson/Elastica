<?hh
namespace Elastica\Query;

use Elastica\Filter\AbstractFilter;
use Elastica\Script;
use Indexish;

/**
 * Class FunctionScore.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html
 */
class FunctionScore extends AbstractQuery
{
    const BOOST_MODE_MULTIPLY = 'multiply';
    const BOOST_MODE_REPLACE = 'replace';
    const BOOST_MODE_SUM = 'sum';
    const BOOST_MODE_AVERAGE = 'average';
    const BOOST_MODE_MAX = 'max';
    const BOOST_MODE_MIN = 'min';

    const SCORE_MODE_MULTIPLY = 'multiply';
    const SCORE_MODE_SUM = 'sum';
    const SCORE_MODE_AVERAGE = 'avg';
    const SCORE_MODE_FIRST = 'first';
    const SCORE_MODE_MAX = 'max';
    const SCORE_MODE_MIN = 'min';

    const DECAY_GAUSS = 'gauss';
    const DECAY_EXPONENTIAL = 'exp';
    const DECAY_LINEAR = 'linear';

    protected array $_functions = array();

    /**
     * Set the child query for this function_score query.
     *
     * @param AbstractQuery $query
     *
     * @return $this
     */
    public function setQuery(AbstractQuery $query) : this
    {
        return $this->setParam('query', $query);
    }

    /**
     * @param AbstractFilter $filter
     *
     * @return $this
     */
    public function setFilter(AbstractFilter $filter) : this
    {
        return $this->setParam('filter', $filter);
    }

    /**
     * Add a function to the function_score query.
     *
     * @param string         $functionType   valid values are DECAY_* constants and script_score
     * @param array|float    $functionParams the body of the function. See documentation for proper syntax.
     * @param AbstractFilter $filter         optional filter to apply to the function
     * @param float          $weight         function weight
     *
     * @return $this
     */
    public function addFunction(string $functionType, mixed $functionParams, ?AbstractFilter $filter = null, ?float $weight = null) : this
    {
        $function = array(
            $functionType => $functionParams,
        );
        if (!is_null($filter)) {
            $function['filter'] = $filter;
        }
        if ($weight !== null) {
            $function['weight'] = $weight;
        }

        $this->_functions[] = $function;

        return $this;
    }

    /**
     * Add a script_score function to the query.
     *
     * @param Script         $script a Script object
     * @param AbstractFilter $filter an optional filter to apply to the function
     * @param float          $weight the weight of the function
     *
     * @return $this
     */
    public function addScriptScoreFunction(Script $script, ?AbstractFilter $filter = null, ?float $weight = null): this
    {
        return $this->addFunction('script_score', $script, $filter, $weight);
    }

    /**
     * Add a decay function to the query.
     *
     * @param string         $function    see DECAY_* constants for valid options
     * @param string         $field       the document field on which to perform the decay function
     * @param string         $origin      the origin value for this decay function
     * @param string         $scale       a scale to define the rate of decay for this function
     * @param string         $offset      If defined, this function will only be computed for documents with a distance from the origin greater than this value
     * @param float          $decay       optionally defines how documents are scored at the distance given by the $scale parameter
     * @param float          $scaleWeight optional factor by which to multiply the score at the value provided by the $scale parameter
     * @param float          $weight      optional factor by which to multiply the score at the value provided by the $scale parameter
     * @param AbstractFilter $filter      a filter associated with this function
     *
     * @return $this
     */
    public function addDecayFunction(
        string $function,
        string $field,
        string $origin,
        string $scale,
        ?string $offset = null,
        ?float $decay = null,
        ?float $weight = null,
        ?AbstractFilter $filter = null
    ) : this {
        $functionParams = array(
            $field => array(
                'origin' => $origin,
                'scale' => $scale,
            ),
        );
        if (!is_null($offset)) {
            $functionParams[$field]['offset'] = $offset;
        }
        if (!is_null($decay)) {
            $functionParams[$field]['decay'] = (float) $decay;
        }

        return $this->addFunction($function, $functionParams, $filter, $weight);
    }

    /**
     * Add a boost_factor function to the query.
     *
     * @param float          $boostFactor the boost factor value
     * @param AbstractFilter $filter      a filter associated with this function
     *
     * @deprecated
     */
    public function addBoostFactorFunction(float $boostFactor, ?AbstractFilter $filter = null) : void
    {
        $this->addWeightFunction($boostFactor, $filter);
    }

    /**
     * @param float          $weight the weight of the function
     * @param AbstractFilter $filter a filter associated with this function
     */
    public function addWeightFunction(float $weight, ?AbstractFilter $filter = null) : void
    {
        $this->addFunction('weight', $weight, $filter);
    }

    /**
     * Add a random_score function to the query.
     *
     * @param number         $seed   the seed value
     * @param AbstractFilter $filter a filter associated with this function
     * @param float          $weight an optional boost value associated with this function
     */
    public function addRandomScoreFunction(int $seed, ?AbstractFilter $filter = null, ?float $weight = null) : void
    {
        $this->addFunction('random_score', array('seed' => $seed), $filter, $weight);
    }

    /**
     * Set an overall boost value for this query.
     *
     * @param float $boost
     *
     * @return $this
     */
    public function setBoost(float $boost) : this
    {
        return $this->setParam('boost', (float) $boost);
    }

    /**
     * Restrict the combined boost of the function_score query and its child query.
     *
     * @param float $maxBoost
     *
     * @return $this
     */
    public function setMaxBoost(float $maxBoost) : this
    {
        return $this->setParam('max_boost', (float) $maxBoost);
    }

    /**
     * The boost mode determines how the score of this query is combined with that of the child query.
     *
     * @param string $mode see BOOST_MODE_* constants for valid options. Default is multiply.
     *
     * @return $this
     */
    public function setBoostMode(string $mode) : this
    {
        return $this->setParam('boost_mode', $mode);
    }

    /**
     * If set, this query will return results in random order.
     *
     * @param int $seed Set a seed value to return results in the same random order for consistent pagination.
     *
     * @return $this
     */
    public function setRandomScore(?int $seed = null) : this
    {
        $seedParam = new \stdClass();
        if (!is_null($seed)) {
            $seedParam->seed = $seed;
        }

        return $this->setParam('random_score', $seedParam);
    }

    /**
     * Set the score method.
     *
     * @param string $mode see SCORE_MODE_* constants for valid options. Default is multiply.
     *
     * @return $this
     */
    public function setScoreMode(string $mode) : this
    {
        return $this->setParam('score_mode', $mode);
    }

    /**
     * Set min_score option.
     *
     * @param float $minScore
     *
     * @return $this
     */
    public function setMinScore(?float $minScore) : this
    {
        return $this->setParam('min_score', (float) $minScore);
    }

    /**
     * @return array
     */
    public function toArray() : Indexish<string, mixed>
    {
        if (count($this->_functions)) {
            $this->setParam('functions', $this->_functions);
        }

        return parent::toArray();
    }
}
