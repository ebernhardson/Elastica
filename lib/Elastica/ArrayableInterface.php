<?hh // strict
namespace Elastica;

use Indexish;

/**
 * Interface for params.
 *
 *
 * @author Evgeniy Sokolov <ewgraf@gmail.com>
 */
interface ArrayableInterface
{
    /**
     * Converts the object to an array.
     *
     * @return array Object as array
     */
    public function toArray() : Indexish<string, mixed>;
}
