<?hh
namespace Elastica\Filter;

/**
 * Type Filter.
 *
 * @author James Wilson <jwilson556@gmail.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-type-filter.html
 */
class Type extends AbstractFilter
{
    /**
     * Type name.
     *
     * @var string
     */
    protected ?string $_type = null;

    /**
     * Construct Type Filter.
     *
     * @param string $type Type name
     */
    public function __construct(?string $type = null)
    {
        if ($type !== null) {
            $this->setType($type);
        }
    }

    /**
     * Ads a field with arguments to the range query.
     *
     * @param string $typeName Type name
     *
     * @return $this
     */
    public function setType(string $typeName) : this
    {
        $this->_type = $typeName;

        return $this;
    }

    /**
     * Convert object to array.
     *
     * @see \Elastica\Filter\AbstractFilter::toArray()
     *
     * @return array Filter array
     */
    public function toArray() : array
    {
        return array(
            'type' => array('value' => $this->_type),
        );
    }
}
