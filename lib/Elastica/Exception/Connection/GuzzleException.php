<?hh
namespace Elastica\Exception\Connection;

use Elastica\Exception\ConnectionException;
use Elastica\Request;
use Elastica\Response;
use GuzzleHttp\Exception\TransferException;

/**
 * Transport exception.
 *
 * @author Milan Magudia <milan@magudia.com>
 */
class GuzzleException extends ConnectionException
{
    /**
     * @var TransferException
     */
    protected TransferException $_guzzleException;

    /**
     * @param \GuzzleHttp\Exception\TransferException $guzzleException
     * @param \Elastica\Request                       $request
     * @param \Elastica\Response                      $response
     */
    public function __construct(TransferException $guzzleException, ?Request $request = null, ?Response $response = null)
    {
        $this->_guzzleException = $guzzleException;
        $message = $guzzleException->getMessage();
        parent::__construct($message, $request, $response);
    }

    /**
     * @param \GuzzleHttp\Exception\TransferException $guzzleException
     *
     * @return string
     */
    public function getErrorMessage(TransferException $guzzleException)
    {
        return $guzzleException->getMessage();
    }

    /**
     * @return TransferException
     */
    public function getGuzzleException() : TransferException {
        return $this->_guzzleException;
    }
}
