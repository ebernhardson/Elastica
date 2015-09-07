<?hh
namespace Elastica\Aggregation;

use Elastica\Aggregation\AbstractSimpleAggregation;
use Elastica\Script;
use Indexish;

abstract class AbstractSimpleAggregation extends AbstractAggregation
{
    /**
     * Set the field for this aggregation.
     *
     * @param string $field the name of the document field on which to perform this aggregation
     *
     * @return $this
     */
    public function setField(string $field) : AbstractSimpleAggregation
    {
        return $this->setParam('field', $field);
    }

    /**
     * Set a script for this aggregation.
     *
     * @param string|Script $script
     *
     * @return $this
     */
    public function setScript(mixed $script) : AbstractSimpleAggregation {
        return $this->setParam('script', $script);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() : Indexish<string, mixed>
    {
        $array = parent::toArray();

        $baseName = $this->_getBaseName();

        if (isset(/* UNSAFE_EXPR */ $array[$baseName]['script']) && /* UNSAFE_EXPR */ $array[$baseName]['script'] instanceof Indexish) {

            $script = /* UNSAFE_EXPR */ $array[$baseName]['script'];

            unset(/* UNSAFE_EXPR */ $array[$baseName]['script']);

            foreach ($script as $k => $v) {
				/* UNSAFE_EXPR */
                $array[$baseName][$k] = $v;
            }
        }

        return $array;
    }
}
