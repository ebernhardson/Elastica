<?hh
namespace Elastica\Exception\Bulk\Response;

use Elastica\Bulk\Action;
use Elastica\Bulk\Response;
use Elastica\Exception\BulkException;

class ActionException extends BulkException
{
    /**
     * @var \Elastica\Response
     */
    protected Response $_response;

    /**
     * @param \Elastica\Bulk\Response $response
     */
    public function __construct(Response $response)
    {
        $this->_response = $response;

        parent::__construct($this->_getErrorMessage($response));
    }

    /**
     * @return \Elastica\Bulk\Action
     */
    public function getAction() : Action
    {
        return $this->getResponse()->getAction();
    }

    /**
     * @return \Elastica\Bulk\Response
     */
    public function getResponse() : Response
    {
        return $this->_response;
    }

    /**
     * @param \Elastica\Bulk\Response $response
     *
     * @return string
     */
    public function getErrorMessage(Response $response) : string
    {
        return $this->_getErrorMessage($response);
    }

    /**
     * @param \Elastica\Bulk\Response $response
     *
     * @return string
     */
    private function _getErrorMessage(Response $response) : string
    {
        $error = $response->getError();
        $opType = $response->getOpType();
        $data = $response->getData();

        $path = '';
        if (isset(/* UNSAFE_EXPR */ $data['_index'])) {
            $path .= '/'./* UNSAFE_EXPR */ $data['_index'];
        }
        if (isset(/* UNSAFE_EXPR */ $data['_type'])) {
            $path .= '/'./* UNSAFE_EXPR */ $data['_type'];
        }
        if (isset(/* UNSAFE_EXPR */ $data['_id'])) {
            $path .= '/'./* UNSAFE_EXPR */ $data['_id'];
        }
        $message = "$opType: $path caused $error";

        return $message;
    }
}
