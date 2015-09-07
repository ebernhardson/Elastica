<?hh // strict
namespace Elastica\Aggregation;

/**
 * Class Percentiles.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-percentile-aggregation.html
 */
class Percentiles extends AbstractSimpleAggregation
{
    /**
     * @param string $name  the name of this aggregation
     * @param string $field the field on which to perform this aggregation
     */
    public function __construct(string $name, ?string $field = null)
    {
        parent::__construct($name);

        if (!is_null($field)) {
            $this->setField($field);
        }
    }

    /**
     * Set compression parameter.
     *
     * @param float $value
     *
     * @return $this
     */
    public function setCompression(float $value) : this
    {
        return $this->setParam('compression', $value);
    }

    /**
     * Set which percents must be returned.
     *
     * @param float[] $percents
     *
     * @return $this
     */
    public function setPercents(array<float> $percents) : this
    {
        return $this->setParam('percents', $percents);
    }

    /**
     * Add yet another percent to result.
     *
     * @param float $percent
     *
     * @return $this
     */
    public function addPercent(float $percent) : this
    {
        return $this->addParam('percents', $percent);
    }
}
