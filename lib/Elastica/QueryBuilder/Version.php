<?hh // strict
namespace Elastica\QueryBuilder;

/**
 * Abstract Version class.
 *
 * @author Manuel Andreo Garcia <andreo.garcia@googlemail.com>
 */
abstract class Version
{
    /**
     * supported query methods.
     *
     * @var string[]
     */
    protected array<string> $queries = array();

    /**
     * supported filter methods.
     *
     * @var string[]
     */
    protected array<string> $filters = array();

    /**
     * supported aggregation methods.
     *
     * @var string[]
     */
    protected array<string> $aggregations = array();

    /**
     * supported $suggester methods.
     *
     * @var string[]
     */
    protected array<string> $suggesters = array();

    /**
     * returns true if $name is supported, false otherwise.
     *
     * @param string $name
     * @param string $type
     *
     * @return bool
     */
    public function supports(string $name, string $type) : bool
    {
        switch ($type) {
            case DSL::TYPE_QUERY:
                $supports = in_array($name, $this->queries);
                break;
            case DSL::TYPE_FILTER:
                $supports = in_array($name, $this->filters);
                break;
            case DSL::TYPE_AGGREGATION:
                $supports = in_array($name, $this->aggregations);
                break;
            case DSL::TYPE_SUGGEST:
                $supports = in_array($name, $this->suggesters);
                break;
            default:
                // disables version check in Facade for custom DSL objects
                $supports = true;
        }

        return $supports;
    }

    /**
     * @return string[]
     */
    public function getAggregations() : array<string>
    {
        return $this->aggregations;
    }

    /**
     * @return string[]
     */
    public function getFilters() : array<string>
    {
        return $this->filters;
    }

    /**
     * @return string[]
     */
    public function getQueries() : array<string>
    {
        return $this->queries;
    }

    /**
     * @return string[]
     */
    public function getSuggesters() : array<string>
    {
        return $this->suggesters;
    }
}
