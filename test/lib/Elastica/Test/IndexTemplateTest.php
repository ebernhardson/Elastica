<?hh
namespace Elastica\Test;

use Elastica\Client;
use Elastica\IndexTemplate;
use Elastica\Request;
use Elastica\Response;
use Elastica\Test\Base as BaseTest;

/**
 * IndexTemplate class tests.
 *
 * @author Dmitry Balabka <dmitry.balabka@intexsys.lv>
 */
class IndexTemplateTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testInstantiate() : void
    {
        $name = 'index_template1';
        $client = $this->_getClient();
        $indexTemplate = new IndexTemplate($client, $name);
        $indexTemplate->getName();
        $this->assertSame($client, $indexTemplate->getClient());
        $this->assertEquals($name, $indexTemplate->getName());
    }

    /**
     * @group unit
     */
    public function testDelete() : void
    {
        $name = 'index_template1';
        $response = new Response('');
        /** @var \PHPUnit_Framework_MockObject_MockObject|Client $clientMock */
        $clientMock = $this->getMock('\Elastica\Client', array('request'));
        $clientMock->expects($this->once())
            ->method('request')
            ->with('/_template/'.$name, Request::DELETE, array(), null)
            ->willReturn($this->asAwaitable($response));
        $indexTemplate = new IndexTemplate($clientMock, $name);
        $this->assertSame($response, $indexTemplate->delete()->getWaitHandle()->join());
    }

    private async function asAwaitable(@\Elastica\Response $value) : Awaitable<\Elastica\Response>
    {
        return $value;
    }

    /**
     * @group unit
     */
    public function testCreate() : void
    {
        $args = array(1);
        $response = new Response('');
        $name = 'index_template1';
        /** @var \PHPUnit_Framework_MockObject_MockObject|Client $clientMock */
        $clientMock = $this->getMock('\Elastica\Client', array('request'));
        $clientMock->expects($this->once())
            ->method('request')
            ->with('/_template/'.$name, Request::PUT, $args, null)
            ->willReturn($this->asAwaitable($response));
        $indexTemplate = new IndexTemplate($clientMock, $name);
        $this->assertSame($response, $indexTemplate->create($args)->getWaitHandle()->join());
    }

    /**
     * @group unit
     */
    public function testExists() : void
    {
        $name = 'index_template1';
        $response = new Response('');
        $response->setTransferInfo(array('http_code' => 200));
        /** @var \PHPUnit_Framework_MockObject_MockObject|Client $clientMock */
        $clientMock = $this->getMock('\Elastica\Client', array('request'));
        $clientMock->expects($this->once())
            ->method('request')
            ->with('/_template/'.$name, Request::HEAD, array(), null)
            ->willReturn($this->asAwaitable($response));
        $indexTemplate = new IndexTemplate($clientMock, $name);
        $this->assertTrue($indexTemplate->exists()->getWaitHandle()->join());
    }

    /**
     * @group functional
     */
    public function testCreateTemplate() : void
    {
        $template = array(
            'template' => 'te*',
            'settings' => array(
                'number_of_shards' => 1,
            ),
        );
        $name = 'index_template1';
        $indexTemplate = new IndexTemplate($this->_getClient(), $name);
        $indexTemplate->create($template)->getWaitHandle()->join();
        $this->assertTrue($indexTemplate->exists()->getWaitHandle()->join());
        $indexTemplate->delete()->getWaitHandle()->join();
        $this->assertFalse($indexTemplate->exists()->getWaitHandle()->join());
    }
}
