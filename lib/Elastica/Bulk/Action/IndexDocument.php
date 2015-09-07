<?hh // strict
namespace Elastica\Bulk\Action;

use Elastica\AbstractUpdateAction;
use Elastica\Document;
use Indexish;

class IndexDocument extends AbstractDocument
{
    /**
     * @var string
     */
    protected string $_opType = self::OP_TYPE_INDEX;

    /**
     * @param \Elastica\Document $document
     *
     * @return $this
     */
    public function setDocument(Document $document) : this
    {
        parent::setDocument($document);

        $this->setSource($document->getData());

        return $this;
    }

    /**
     * @param \Elastica\AbstractUpdateAction $source
     *
     * @return array
     */
    protected function _getMetadata(AbstractUpdateAction $action) : Indexish<string, mixed>
    {
        $params = array(
            'index',
            'type',
            'id',
            'version',
            'version_type',
            'routing',
            'percolate',
            'parent',
            'ttl',
            'timestamp',
            'retry_on_conflict',
        );

        $metadata = $action->getOptions($params, true);

        return $metadata;
    }
}
