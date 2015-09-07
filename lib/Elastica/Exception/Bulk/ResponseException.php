<?hh // strict
namespace Elastica\Exception\Bulk;

use Elastica\Bulk\ResponseSet;
use Elastica\Exception\Bulk\Response\ActionException;
use Elastica\Exception\BulkException;

/**
 * Bulk Response exception.
 */
class ResponseException extends BulkException
{
    /**
     * @var \Elastica\Bulk\ResponseSet ResponseSet object
     */
    protected ResponseSet $_responseSet;

    /**
     * @var \Elastica\Exception\Bulk\Response\ActionException[]
     */
    protected array<ActionException> $_actionExceptions = array();

    /**
     * Construct Exception.
     *
     * @param \Elastica\Bulk\ResponseSet $responseSet
     */
    public function __construct(ResponseSet $responseSet)
    {
        $this->_init($responseSet);

        $message = 'Error in one or more bulk request actions:'.PHP_EOL.PHP_EOL;
        $message .= $this->_getActionExceptionsAsString();

        parent::__construct($message);
    }

    /**
     * @param \Elastica\Bulk\ResponseSet $responseSet
     */
    private function _init(ResponseSet $responseSet) : void
    {
        $this->_responseSet = $responseSet;

        foreach ($responseSet->getBulkResponses() as $bulkResponse) {
            if ($bulkResponse->hasError()) {
                $this->_actionExceptions[] = new ActionException($bulkResponse);
            }
        }
    }

    /**
     * Returns bulk response set object.
     *
     * @return \Elastica\Bulk\ResponseSet
     */
    public function getResponseSet() : ResponseSet
    {
        return $this->_responseSet;
    }

    /**
     * Returns array of failed actions.
     *
     * @return array Array of failed actions
     */
    public function getFailures() : array<ActionException>
    {
        $errors = array();

        foreach ($this->getActionExceptions() as $actionException) {
            $errors[] = $actionException->getMessage();
        }

        return $errors;
    }

    /**
     * @return \Elastica\Exception\Bulk\Response\ActionException[]
     */
    public function getActionExceptions() : array<ActionException>
    {
        return $this->_actionExceptions;
    }

    /**
     * @return string
     */
    public function getActionExceptionsAsString() : string
    {
        return $this->_getActionExceptionsAsString();
    }

    private function  _getActionExceptionsAsString() : string
    {
        $message = '';
        foreach ($this->_actionExceptions as $actionException) {
            $message .= $actionException->getMessage().PHP_EOL;
        }

        return $message;
    }
}
