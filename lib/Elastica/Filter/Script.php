<?hh
namespace Elastica\Filter;

use Elastica;
use Indexish;

/**
 * Script filter.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-script-filter.html
 */
class Script extends AbstractFilter
{
    /**
     * Query object.
     *
     * @var array|\Elastica\Query\AbstractQuery
     */
    protected mixed $_query = null;

    /**
     * Construct script filter.
     *
     * @param array|string|\Elastica\Script $script OPTIONAL Script
     */
    public function __construct(mixed $script = null)
    {
        if ($script) {
            $this->setScript($script);
        }
    }

    /**
     * Sets script object.
     *
     * @param \Elastica\Script|string|array $script
     *
     * @return $this
     */
    public function setScript(mixed $script) : this
    {
        return $this->setParam('script', Elastica\Script::create($script));
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() : Indexish<string, mixed>
    {
        $array = parent::toArray();

        if (isset($array['script'])) {
            /* UNSAFE_EXPR */
            $array['script'] = $array['script']['script'];
        }

        return $array;
    }
}
