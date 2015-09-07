<?hh // strict
namespace Elastica\Filter;

use Indexish;

/**
 * Prefix filter.
 *
 * @author Jasper van Wanrooy <jasper@vanwanrooy.net>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-prefix-filter.html
 */
class Prefix extends AbstractFilter
{
    /**
     * Holds the name of the field for the prefix.
     *
     * @var string
     */
    protected string $_field = '';

    /**
     * Holds the prefix string.
     *
     * @var string
     */
    protected string $_prefix = '';

    /**
     * Creates prefix filter.
     *
     * @param string $field  Field name
     * @param string $prefix Prefix string
     */
    public function __construct(string $field = '', string $prefix = '')
    {
        $this->setField($field);
        $this->setPrefix($prefix);
    }

    /**
     * Sets the name of the prefix field.
     *
     * @param string $field Field name
     *
     * @return $this
     */
    public function setField(string $field) : this
    {
        $this->_field = $field;

        return $this;
    }

    /**
     * Sets the prefix string.
     *
     * @param string $prefix Prefix string
     *
     * @return $this
     */
    public function setPrefix(string $prefix) : this
    {
        $this->_prefix = $prefix;

        return $this;
    }

    /**
     * Converts object to an array.
     *
     * @see \Elastica\Filter\AbstractFilter::toArray()
     *
     * @return array data array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $this->setParam($this->_field, $this->_prefix);

        return parent::toArray();
    }
}
