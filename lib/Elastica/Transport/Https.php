<?hh // strict
namespace Elastica\Transport;

/**
 * Elastica Http Transport object.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
class Https extends Http
{
    /**
     * Https scheme.
     *
     * @var string https scheme
     */
    protected string $_scheme = 'https';

    /**
     * Overloads setupCurl to set SSL params.
     *
     * @param resource $connection Curl connection resource
     */
    protected function _setupCurl(resource $connection) : void
    {
        parent::_setupCurl($connection);
    }
}
