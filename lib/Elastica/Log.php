<?hh
namespace Elastica;

use Psr\Log\AbstractLogger;

/**
 * Elastica log object.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
class Log extends AbstractLogger
{
    /**
     * Log path or true if enabled.
     *
     * @var string|bool
     */
    protected mixed $_log = true;

    /**
     * Last logged message.
     *
     * @var string Last logged message
     */
    protected string $_lastMessage = '';

    /**
     * Inits log object.
     *
     * @param string|bool String to set a specific file for logging
     */
    public function __construct(mixed $log = '')
    {
        $this->setLog($log);
    }

    /**
     * Log a message.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null|void
     */
    public function log($level, @string $message, array $context = array())
   : @void {
        $context['error_message'] = $message;
        $this->_lastMessage = JSON::stringify($context);

        if (!empty($this->_log) && is_string($this->_log)) {
            error_log($this->_lastMessage.PHP_EOL, 3, $this->_log);
        } else {
            error_log($this->_lastMessage);
        }
    }

    /**
     * Enable/disable log or set log path.
     *
     * @param bool|string $log Enables log or sets log path
     *
     * @return $this
     */
    public function setLog(mixed $log) : this
    {
        $this->_log = $log;

        return $this;
    }

    /**
     * Return last logged message.
     *
     * @return string Last logged message
     */
    public function getLastMessage() : string
    {
        return $this->_lastMessage;
    }
}
