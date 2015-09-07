<?hh
namespace Elastica\Query;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * Terms query.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html
 */
class Terms extends AbstractQuery
{
    /**
     * Terms.
     *
     * @var array Terms
     */
    protected array $_terms = array();

    /**
     * Params.
     *
     * @var array Params
     */
    protected Map<string, mixed> $_params = Map {};

    /**
     * Terms key.
     *
     * @var string Terms key
     */
    protected string $_key = '';

    /**
     * Construct terms query.
     *
     * @param string $key   OPTIONAL Terms key
     * @param array  $terms OPTIONAL Terms list
     */
    public function __construct(string $key = '', array $terms = array())
    {
        $this->setTerms($key, $terms);
    }

    /**
     * Sets key and terms for the query.
     *
     * @param string $key   Terms key
     * @param array  $terms Terms for the query.
     *
     * @return $this
     */
    public function setTerms(string $key, array $terms) : this
    {
        $this->_key = $key;
        $this->_terms = array_values($terms);

        return $this;
    }

    /**
     * Adds a single term to the list.
     *
     * @param string $term Term
     *
     * @return $this
     */
    public function addTerm(string $term) : this
    {
        $this->_terms[] = $term;

        return $this;
    }

    /**
     * Sets the minimum matching values.
     *
     * @param int $minimum Minimum value
     *
     * @return $this
     */
    public function setMinimumMatch(int $minimum) : this
    {
        return $this->setParam('minimum_match', (int) $minimum);
    }

    /**
     * Converts the terms object to an array.
     *
     * @see \Elastica\Query\AbstractQuery::toArray()
     *
     * @throws \Elastica\Exception\InvalidException If term key is empty
     *
     * @return array Query array
     */
    public function toArray() : Indexish<string, mixed>
    {
        if (empty($this->_key)) {
            throw new InvalidException('Terms key has to be set');
        }
        $this->setParam($this->_key, $this->_terms);

        return parent::toArray();
    }
}
