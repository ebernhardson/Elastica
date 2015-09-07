<?hh
namespace Elastica\Test;

use Elastica\Document;
use Elastica\Result;
use Elastica\Test\Base as BaseTest;
use Elastica\Type\Mapping;

class ResultTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testGetters() : void
    {
        // Creates a new index 'xodoa' and a type 'user' inside this index
        $typeName = 'user';

        $index = $this->_createIndex();
        $type = $index->getType($typeName);

        // Adds 1 document to the index
        $docId = '3';
        $doc1 = new Document($docId, array('username' => 'hans'));
        $type->addDocument($doc1)->getWaitHandle()->join();

        // Refreshes index
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('hans')->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());

        $result = $resultSet->current();

        $this->assertInstanceOf('Elastica\Result', $result);
        $this->assertEquals($index->getName(), $result->getIndex());
        $this->assertEquals($typeName, $result->getType());
        $this->assertEquals($docId, $result->getId());
        $this->assertGreaterThan(0, $result->getScore());
        $this->assertInternalType('array', $result->getData());
        $this->assertTrue($result->__isset('username'));
        $this->assertEquals('hans', $result->__get('username'));
    }

    /**
     * @group functional
     */
    public function testGetIdNoSource() : void
    {
        // Creates a new index 'xodoa' and a type 'user' inside this index
        $indexName = 'xodoa';
        $typeName = 'user';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType($typeName);

        $mapping = new Mapping($type);
        $mapping->disableSource();
        $mapping->send()->getWaitHandle()->join();

        // Adds 1 document to the index
        $docId = '3';
        $doc1 = new Document($docId, array('username' => 'hans'));
        $type->addDocument($doc1)->getWaitHandle()->join();

        // Refreshes index
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('hans')->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());

        $result = $resultSet->current();

        $this->assertEquals(array(), $result->getSource());
        $this->assertInstanceOf('Elastica\Result', $result);
        $this->assertEquals($indexName, $result->getIndex());
        $this->assertEquals($typeName, $result->getType());
        $this->assertEquals($docId, $result->getId());
        $this->assertGreaterThan(0, $result->getScore());
        $this->assertInternalType('array', $result->getData());
    }

    /**
     * @group functional
     */
    public function testGetTotalTimeReturnsExpectedResults() : void
    {
        $typeName = 'user';
        $index = $this->_createIndex();
        $type = $index->getType($typeName);

        // Adds 1 document to the index
        $docId = '3';
        $doc1 = new Document($docId, array('username' => 'hans'));
        $type->addDocument($doc1)->getWaitHandle()->join();

        // Refreshes index
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('hans')->getWaitHandle()->join();

        $this->assertNotNull($resultSet->getTotalTime(), 'Get Total Time should never be a null value');
        $this->assertEquals(
            'integer',
            gettype($resultSet->getTotalTime()),
            'Total Time should be an integer'
         );
    }

    /**
     * @group unit
     */
    public function testHasFields() : void
    {
        $data = array('value set');

        $result = new Result(array());
        $this->assertFalse($result->hasFields());

        $result = new Result(array('_source' => $data));
        $this->assertFalse($result->hasFields());

        $result = new Result(array('fields' => $data));
        $this->assertTrue($result->hasFields());
        $this->assertEquals($data, $result->getFields());
    }
}
