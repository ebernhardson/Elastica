<?hh
namespace Elastica\Filter;

/**
 * Geo polygon filter.
 *
 * @author Michael Maclean <mgdm@php.net>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-polygon-filter.html
 */
class GeoPolygon extends AbstractFilter
{
    /**
     * Key.
     *
     * @var string Key
     */
    protected string $_key = '';

    /**
     * Points making up polygon.
     *
     * @var array Points making up polygon
     */
    protected array $_points = array();

    /**
     * Construct polygon filter.
     *
     * @param string $key    Key
     * @param array  $points Points making up polygon
     */
    public function __construct(string $key, array $points)
    {
        $this->_key = $key;
        $this->_points = $points;
    }

    /**
     * Converts filter to array.
     *
     * @see \Elastica\Filter\AbstractFilter::toArray()
     *
     * @return array
     */
    public function toArray() : array
    {
        return array(
            'geo_polygon' => array(
                $this->_key => array(
                    'points' => $this->_points,
                ),
            ),
        );
    }
}
