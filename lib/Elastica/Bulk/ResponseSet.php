<?hh
namespace Elastica\Bulk;

use Elastica\Response as BaseResponse;

class ResponseSet extends BaseResponse implements KeyedIterator<int, Response>, \Countable
{
    /**
     * @var \Elastica\Bulk\Response[]
     */
    protected array<int, Response> $_bulkResponses = array();

    /**
     * @var int
     */
    protected int $_position = 0;

    /**
     * @param \Elastica\Response        $response
     * @param \Elastica\Bulk\Response[] $bulkResponses
     */
    public function __construct(BaseResponse $response, array $bulkResponses)
    {
        parent::__construct($response->getData());

        $this->_bulkResponses = $bulkResponses;
    }

    /**
     * @return \Elastica\Bulk\Response[]
     */
    public function getBulkResponses()
   : @array<int, \Elastica\Bulk\Response> {
        return $this->_bulkResponses;
    }

    /**
     * Returns first found error.
     *
     * @return string
     */
    public function getError()
   : @string {
        $error = '';

        foreach ($this->getBulkResponses() as $bulkResponse) {
            if ($bulkResponse->hasError()) {
                $error = $bulkResponse->getError();
                break;
            }
        }

        return $error;
    }

    /**
     * @return bool
     */
    public function isOk()
   : @bool {
        $return = true;

        foreach ($this->getBulkResponses() as $bulkResponse) {
            if (!$bulkResponse->isOk()) {
                $return = false;
                break;
            }
        }

        return $return;
    }

    /**
     * @return bool
     */
    public function hasError()
   : @bool {
        $return = false;

        foreach ($this->getBulkResponses() as $bulkResponse) {
            if ($bulkResponse->hasError()) {
                $return = true;
                break;
            }
        }

        return $return;
    }

    /**
     * @return bool|\Elastica\Bulk\Response
     */
    public function current()
    {
        if ($this->valid()) {
            return $this->_bulkResponses[$this->key()];
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function next()
   : @void {
        ++$this->_position;
    }

    /**
     * @return int
     */
    public function key()
   : @int {
        return $this->_position;
    }

    /**
     * @return bool
     */
    public function valid()
   : @bool {
        return isset($this->_bulkResponses[$this->key()]);
    }

    /**
     *
     */
    public function rewind()
   : @void {
        $this->_position = 0;
    }

    /**
     * @return int
     */
    public function count()
   : @int {
        return count($this->_bulkResponses);
    }
}
