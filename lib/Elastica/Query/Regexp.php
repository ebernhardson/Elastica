<?hh
namespace Elastica\Query;

/**
 * Regexp query.
 *
 * @author Aurélien Le Grand <gnitg@yahoo.fr>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-regexp-query.html
 */
class Regexp extends AbstractQuery
{
    /**
     * Construct regexp query.
     *
     * @param string $key   OPTIONAL Regexp key
     * @param string $value OPTIONAL Regexp value
     * @param float  $boost OPTIONAL Boost value (default = 1)
     */
    public function __construct(string $key = '', ?string $value = null, float $boost = 1.0)
    {
        if (!empty($key)) {
            $this->setValue($key, $value, $boost);
        }
    }

    /**
     * Sets the query expression for a key with its boost value.
     *
     * @param string $key
     * @param string $value
     * @param float  $boost
     *
     * @return $this
     */
    public function setValue(string $key, ?string $value, float $boost = 1.0) : this
    {
        return $this->setParam($key, array('value' => $value, 'boost' => $boost));
    }
}
