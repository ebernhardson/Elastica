<?hh // strict
namespace Elastica\Exception;

use Elastica\Request;
use Elastica\Response;

/**
 * Connection exception.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
class ConnectionException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @var \Elastica\Request Request object
     */
    protected ?Request $_request;

    /**
     * @var \Elastica\Response Response object
     */
    protected ?Response $_response;

    /**
     * Construct Exception.
     *
     * @param string             $message  Message
     * @param \Elastica\Request  $request
     * @param \Elastica\Response $response
     */
    public function __construct(string $message, ?Request $request = null, ?Response $response = null)
    {
        $this->_request = $request;
        $this->_response = $response;

        parent::__construct($message);
    }

    /**
     * Returns request object.
     *
     * @return \Elastica\Request Request object
     */
    public function getRequest() : ?Request
    {
        return $this->_request;
    }

    /**
     * Returns response object.
     *
     * @return \Elastica\Response Response object
     */
    public function getResponse() : ?Response
    {
        return $this->_response;
    }
}
