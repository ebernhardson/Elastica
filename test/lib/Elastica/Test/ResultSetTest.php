<?hh
namespace Elastica\Test;

use Elastica\Document;
use Elastica\Result;
use Elastica\Test\Base as BaseTest;

class ResultSetTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testGetters() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'elastica search')),
            new Document('2', array('name' => 'elastica library')),
            new Document('3', array('name' => 'elastica test')),
        ))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('elastica search')->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\ResultSet', $resultSet);
        $this->assertEquals(3, $resultSet->getTotalHits());
        $this->assertGreaterThan(0, $resultSet->getMaxScore());
        $this->assertInternalType('array', $resultSet->getResults());
        $this->assertEquals(3, count($resultSet));
    }

    /**
     * @group functional
     */
    public function testArrayAccess() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'elastica search')),
            new Document('2', array('name' => 'elastica library')),
            new Document('3', array('name' => 'elastica test')),
        ))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('elastica search')->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\ResultSet', $resultSet);
        $this->assertInstanceOf('Elastica\Result', $resultSet->offsetGet(0));
        $this->assertInstanceOf('Elastica\Result', $resultSet->offsetGet(1));
        $this->assertInstanceOf('Elastica\Result', $resultSet->offsetGet(2));

        $this->assertFalse($resultSet->offsetExists(3));
    }

    /**
     * @group functional
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testInvalidOffsetCreation() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $doc = new Document('1', array('name' => 'elastica search'));
        $type->addDocument($doc)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('elastica search')->getWaitHandle()->join();

        $result = new Result(array('_id' => 'fakeresult'));
        $resultSet->offsetSet(1, $result);
    }

    /**
     * @group functional
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testInvalidOffsetGet() : \Elastica\Result
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $doc = new Document('1', array('name' => 'elastica search'));
        $type->addDocument($doc)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('elastica search')->getWaitHandle()->join();

        return $resultSet->offsetGet(3);
    }
}
