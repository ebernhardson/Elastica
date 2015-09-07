<?hh // strict
namespace Elastica\Bulk\Action;

class CreateDocument extends IndexDocument
{
    /**
     * @var string
     */
    protected string $_opType = self::OP_TYPE_CREATE;
}
