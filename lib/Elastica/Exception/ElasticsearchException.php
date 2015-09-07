<?hh // strict
namespace Elastica\Exception;

/**
 * Elasticsearch exception.
 *
 * @author Ian Babrou <ibobrik@gmail.com>
 */
class ElasticsearchException extends \Exception implements ExceptionInterface
{
    const REMOTE_TRANSPORT_EXCEPTION = 'RemoteTransportException';

    /**
     * @var string|null Elasticsearch exception name
     */
    private ?string $_exception;

    /**
     * @var bool Whether exception was local to server node or remote
     */
    private bool $_isRemote = false;

    /**
     * Constructs elasticsearch exception.
     *
     * @param int    $code  Error code
     * @param string $error Error message from elasticsearch
     */
    public function __construct(int $code, string $error)
    {
        $this->_parseError($error);
        parent::__construct($error, $code);
    }

    /**
     * Parse error message from elasticsearch.
     *
     * @param string $error Error message
     */
    private function _parseError(string $error) : void
    {
        $errors = explode(']; nested: ', $error);

        if (count($errors) == 1) {
            $this->_exception = $this->_extractException($errors[0]);
        } else {
            if ($this->_extractException($errors[0]) == self::REMOTE_TRANSPORT_EXCEPTION) {
                $this->_isRemote = true;
                $this->_exception = $this->_extractException($errors[1]);
            } else {
                $this->_exception = $this->_extractException($errors[0]);
            }
        }
    }

    /**
     * Extract exception name from error response.
     *
     * @param string $error
     *
     * @return null|string
     */
    private function _extractException(string $error) : ?string
    {
        $matches = array();
        if (preg_match('/^(\w+)\[.*\]/', $error, $matches)) {
            return $matches[1];
        } else {
            return null;
        }
    }

    /**
     * Returns elasticsearch exception name.
     *
     * @return string|null
     */
    public function getExceptionName() : ?string
    {
        return $this->_exception;
    }

    /**
     * Returns whether exception was local to server node or remote.
     *
     * @return bool
     */
    public function isRemoteTransportException() : bool
    {
        return $this->_isRemote;
    }
}
