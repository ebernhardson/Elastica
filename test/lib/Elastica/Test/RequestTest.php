<?hh
namespace Elastica\Test;

use Elastica\Connection;
use Elastica\Request;
use Elastica\Test\Base as BaseTest;

class RequestTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testConstructor() : void
    {
        $path = 'test';
        $method = Request::POST;
        $query = array('no' => 'params');
        $data = array('key' => 'value');

        $request = new Request($path, $method, $data, $query);

        $this->assertEquals($path, $request->getPath());
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($query, $request->getQuery());
        $this->assertEquals($data, $request->getData());
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testInvalidConnection() : void
    {
        $request = new Request('', Request::GET);
        $request->send()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testSend() : void
    {
        $connection = new Connection();
        $connection->setHost($this->_getHost());
        $connection->setPort(9200);

        $request = new Request('_status', Request::GET, array(), array(), $connection);

        $response = $request->send()->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Response', $response);
    }

    /**
     * @group unit
     */
    public function testToString() : void
    {
        $path = 'test';
        $method = Request::POST;
        $query = array('no' => 'params');
        $data = array('key' => 'value');

        $connection = new Connection();
        $connection->setHost($this->_getHost());
        $connection->setPort(9200);

        $request = new Request($path, $method, $data, $query, $connection);

        $data = $request->toArray();

        $this->assertInstanceOf('HH\Map', $data);
        $this->assertTrue(/* UNSAFE_EXPR */ $data->contains('method'));
        $this->assertTrue(/* UNSAFE_EXPR */ $data->contains('path'));
        $this->assertTrue(/* UNSAFE_EXPR */ $data->contains('query'));
        $this->assertTrue(/* UNSAFE_EXPR */ $data->contains('data'));
        $this->assertTrue(/* UNSAFE_EXPR */ $data->contains('connection'));
        $this->assertEquals($request->getMethod(), $data['method']);
        $this->assertEquals($request->getPath(), $data['path']);
        $this->assertEquals($request->getQuery(), $data['query']);
        $this->assertEquals($request->getData(), $data['data']);
        $this->assertInstanceOf('HH\Map', $data['connection']);
        $this->assertTrue(/* UNSAFE_EXPR */ $data['connection']->contains('host'));
        $this->assertTrue(/* UNSAFE_EXPR */ $data['connection']->contains('port'));
        $this->assertEquals($request->getConnection()->getHost(), /* UNSAFE_EXPR */ $data['connection']['host']);
        $this->assertEquals($request->getConnection()->getPort(), /* UNSAFE_EXPR */ $data['connection']['port']);

        $string = $request->toString();

        $this->assertInternalType('string', $string);

        $string = (string) $request;
        $this->assertInternalType('string', $string);
    }
}
