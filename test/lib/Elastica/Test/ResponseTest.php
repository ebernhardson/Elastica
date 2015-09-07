<?hh
namespace Elastica\Test;

use Elastica\Document;
use Elastica\Facet\DateHistogram;
use Elastica\Query;
use Elastica\Query\MatchAll;
use Elastica\Request;
use Elastica\Response;
use Elastica\Test\Base as BaseTest;
use Elastica\Type\Mapping;
use Indexish;

class ResponseTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testClassHierarchy() : void
    {
        $facet = new DateHistogram('dateHist1');
        $this->assertInstanceOf('Elastica\Facet\Histogram', $facet);
        $this->assertInstanceOf('Elastica\Facet\AbstractFacet', $facet);
        unset($facet);
    }

    /**
     * @group functional
     */
    public function testResponse() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('helloworld');

        $mapping = new Mapping($type, array(
            'name' => array('type' => 'string', 'store' => 'no'),
            'dtmPosted' => array('type' => 'date', 'store' => 'no', 'format' => 'yyyy-MM-dd HH:mm:ss'),
        ));
        $type->setMapping($mapping)->getWaitHandle()->join();

        $type->addDocuments(array(
            new Document('1', array('name' => 'nicolas ruflin', 'dtmPosted' => '2011-06-23 21:53:00')),
            new Document('2', array('name' => 'raul martinez jr', 'dtmPosted' => '2011-06-23 09:53:00')),
            new Document('3', array('name' => 'rachelle clemente', 'dtmPosted' => '2011-07-08 08:53:00')),
            new Document('4', array('name' => 'elastica search', 'dtmPosted' => '2011-07-08 01:53:00')),
        ))->getWaitHandle()->join();

        $query = new Query();
        $query->setQuery(new MatchAll());
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search($query)->getWaitHandle()->join();

        $engineTime = $resultSet->getResponse()->getEngineTime();
        $shardsStats = $resultSet->getResponse()->getShardsStatistics();

        $this->assertInternalType('int', $engineTime);
        $this->assertTrue($shardsStats instanceof Indexish);
        $this->assertArrayHasKey('total', $shardsStats);
        $this->assertArrayHasKey('successful', $shardsStats);
    }

    /**
     * @group functional
     */
    public function testIsOk() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $doc = new Document('1', array('name' => 'ruflin'));
        $response = $type->addDocument($doc)->getWaitHandle()->join();

        $this->assertTrue($response->isOk());
    }

    /**
     * @group functional
     */
    public function testIsOkMultiple() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $docs = array(
            new Document('1', array('name' => 'ruflin')),
            new Document('2', array('name' => 'ruflin')),
        );
        $response = $type->addDocuments($docs)->getWaitHandle()->join();

        $this->assertTrue($response->isOk());
    }

    /**
     * @group unit
     */
    public function testIsOkBulkWithErrorsField() : void
    {
        $response = new Response(json_encode(array(
            'took' => 213,
            'errors' => false,
            'items' => array(
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707891', '_version' => 4, 'status' => 200)),
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707893', '_version' => 4, 'status' => 200)),
            ),
        )));

        $this->assertTrue($response->isOk());
    }

    /**
     * @group unit
     */
    public function testIsNotOkBulkWithErrorsField() : void
    {
        $response = new Response(json_encode(array(
            'took' => 213,
            'errors' => true,
            'items' => array(
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707891', '_version' => 4, 'status' => 200)),
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707893', '_version' => 4, 'status' => 200)),
            ),
        )));

        $this->assertFalse($response->isOk());
    }

    /**
     * @group unit
     */
    public function testIsOkBulkItemsWithOkField() : void
    {
        $response = new Response(json_encode(array(
            'took' => 213,
            'items' => array(
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707891', '_version' => 4, 'ok' => true)),
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707893', '_version' => 4, 'ok' => true)),
            ),
        )));

        $this->assertTrue($response->isOk());
    }

    /**
     * @group unit
     */
    public function testIsNotOkBulkItemsWithOkField() : void
    {
        $response = new Response(json_encode(array(
            'took' => 213,
            'items' => array(
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707891', '_version' => 4, 'ok' => true)),
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707893', '_version' => 4, 'ok' => false)),
            ),
        )));

        $this->assertFalse($response->isOk());
    }

    /**
     * @group unit
     */
    public function testIsOkBulkItemsWithStatusField() : void
    {
        $response = new Response(json_encode(array(
            'took' => 213,
            'items' => array(
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707891', '_version' => 4, 'status' => 200)),
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707893', '_version' => 4, 'status' => 200)),
            ),
        )));

        $this->assertTrue($response->isOk());
    }

    /**
     * @group unit
     */
    public function testIsNotOkBulkItemsWithStatusField() : void
    {
        $response = new Response(json_encode(array(
            'took' => 213,
            'items' => array(
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707891', '_version' => 4, 'status' => 200)),
                array('index' => array('_index' => 'rohlik', '_type' => 'grocery', '_id' => '707893', '_version' => 4, 'status' => 301)),
            ),
        )));

        $this->assertFalse($response->isOk());
    }

    /**
     * @group functional
     */
    public function testGetDataEmpty() : void
    {
        $index = $this->_createIndex();

        $response = $index->request(
            'non-existent-type/_mapping',
            Request::GET
        )->getWaitHandle()->join()->getData();

        $this->assertEquals(0, count($response));
    }
}
