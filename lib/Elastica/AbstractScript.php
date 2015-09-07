<?hh
namespace Elastica;

use Indexish;

/**
 * Base class for Script object.
 *
 * @author Nicolas Assing <nicolas.assing@gmail.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/modules-scripting.html
 */
abstract class AbstractScript extends AbstractUpdateAction
{
    /**
     * @param array|null $params
     * @param string     $id
     */
    public function __construct(?Map<string, mixed> $params = null, ?string $id = null)
    {
        if ($params !== null) {
            $this->setParams($params);
        }

        if ($id) {
            $this->setId($id);
        }
    }
}
