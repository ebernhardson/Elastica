<?hh
namespace Elastica\Test;

use Elastica\Log;
use Elastica\Test\Base as BaseTest;
use Psr\Log\LogLevel;

class LogTest extends BaseTest
{
    private array $_context = array();
    private string $_message = 'hello world';

    public static function setUpBeforeClass() : void
    {
        if (!class_exists('Psr\Log\AbstractLogger')) {
            self::markTestSkipped('The Psr extension is not available.');
        }
    }

    /**
     * @group unit
     */
    public function testLogInterface() : void
    {
        $log = new Log();
        $this->assertInstanceOf('Psr\Log\LoggerInterface', $log);
    }

    /**
     * @group unit
     */
    public function testSetLogConfigPath() : void
    {
        $logPath = '/tmp/php.log';
        $client = $this->_getClient(array('log' => $logPath));
        $this->assertEquals($logPath, $client->getConfig('log'));
    }

    /**
     * @group unit
     */
    public function testSetLogConfigEnable() : void
    {
        $client = $this->_getClient(array('log' => true));
        $this->assertTrue($client->getConfig('log'));
    }

    /**
     * @group unit
     */
    public function testSetLogConfigEnable1() : void
    {
        $client = $this->_getClient();
        $client->setLogger(new Log());
        $this->assertFalse($client->getConfig('log'));
    }

    /**
     * @group unit
     */
    public function testEmptyLogConfig() : void
    {
        $client = $this->_getClient();
        $this->assertEmpty($client->getConfig('log'));
    }

    /**
     * @group unit
     */
    public function testGetLastMessage() : void
    {
        $log = new Log('/tmp/php.log');

        $log->log(LogLevel::DEBUG, $this->_message, $this->_context);

        $this->_context['error_message'] = $this->_message;
        $message = json_encode($this->_context);

        $this->assertEquals($message, $log->getLastMessage());
    }

    /**
     * @group unit
     */
    public function testGetLastMessage2() : void
    {
        $client = $this->_getClient(array('log' => true));
        $log = new Log($client);

        // Set log path temp path as otherwise test fails with output
        $errorLog = ini_get('error_log');
        ini_set('error_log', sys_get_temp_dir().DIRECTORY_SEPARATOR.'php.log');

        $this->_context['error_message'] = $this->_message;
        $message = json_encode($this->_context);

        $log->log(LogLevel::DEBUG, $this->_message, $this->_context);
        ini_set('error_log', $errorLog);

        $this->assertEquals($message, $log->getLastMessage());
    }

    /**
     * @group unit
     */
    public function testGetLastMessageInfo() : void
    {
        $log = $this->initLog();
        $log->info($this->_message, $this->_context);
        $this->assertEquals($this->getMessage(), $log->getLastMessage());
    }

    /**
     * @group unit
     */
    public function testGetLastMessageCritical() : void
    {
        $log = $this->initLog();
        $log->critical($this->_message, $this->_context);
        $this->assertEquals($this->getMessage(), $log->getLastMessage());
    }

    /**
     * @group unit
     */
    public function testGetLastMessageAlert() : void
    {
        $log = $this->initLog();
        $log->alert($this->_message, $this->_context);
        $this->assertEquals($this->getMessage(), $log->getLastMessage());
    }

    /**
     * @group unit
     */
    public function testGetLastMessageDebug() : void
    {
        $log = $this->initLog();
        $log->debug($this->_message, $this->_context);
        $this->assertEquals($this->getMessage(), $log->getLastMessage());
    }

    /**
     * @group unit
     */
    public function testGetLastMessageEmergency() : void
    {
        $log = $this->initLog();
        $log->emergency($this->_message, $this->_context);
        $this->assertEquals($this->getMessage(), $log->getLastMessage());
    }

    /**
     * @group unit
     */
    public function testGetLastMessageError() : void
    {
        $log = $this->initLog();
        $log->error($this->_message, $this->_context);
        $this->assertEquals($this->getMessage(), $log->getLastMessage());
    }

    /**
     * @group unit
     */
    public function testGetLastMessageNotice() : void
    {
        $log = $this->initLog();
        $log->notice($this->_message, $this->_context);
        $this->assertEquals($this->getMessage(), $log->getLastMessage());
    }

    /**
     * @group unit
     */
    public function testGetLastMessageWarning() : void
    {
        $log = $this->initLog();
        $log->warning($this->_message, $this->_context);
        $this->assertEquals($this->getMessage(), $log->getLastMessage());
    }

    private function initLog() : \Elastica\Log
    {
        $log = new Log('/tmp/php.log');

        return $log;
    }

    private function getMessage() : string
    {
        $this->_context['error_message'] = $this->_message;

        return json_encode($this->_context);
    }
}
