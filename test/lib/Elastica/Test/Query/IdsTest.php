<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\Ids;
use Elastica\Test\Base as BaseTest;

class IdsTest extends BaseTest
{
    protected $_index;
    protected $_type;

    protected function setUp() : void
    {
        parent::setUp();

        $index = $this->_createIndex();

        $type1 = $index->getType('helloworld1');
        $type2 = $index->getType('helloworld2');

        $doc = new Document('1', array('name' => 'hello world'));
        $type1->addDocument($doc)->getWaitHandle()->join();

        $doc = new Document('2', array('name' => 'nicolas ruflin'));
        $type1->addDocument($doc)->getWaitHandle()->join();

        $doc = new Document('3', array('name' => 'ruflin'));
        $type1->addDocument($doc)->getWaitHandle()->join();

        $doc = new Document('4', array('name' => 'hello world again'));
        $type2->addDocument($doc)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $this->_type = $type1;
        $this->_index = $index;
    }

    /**
     * @group functional
     */
    public function testSetIdsSearchSingle() : void
    {
        $query = new Ids();
        $query->setIds('1');

        $resultSet = $this->_type->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetIdsSearchArray() : void
    {
        $query = new Ids();
        $query->setIds(array('1', '2'));

        $resultSet = $this->_type->search($query)->getWaitHandle()->join();

        $this->assertEquals(2, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testAddIdsSearchSingle() : void
    {
        $query = new Ids();
        $query->addId('3');

        $resultSet = $this->_type->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testComboIdsSearchArray() : void
    {
        $query = new Ids();

        $query->setIds(array('1', '2'));
        $query->addId('3');

        $resultSet = $this->_type->search($query)->getWaitHandle()->join();

        $this->assertEquals(3, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetTypeSingleSearchSingle() : void
    {
        $query = new Ids();

        $query->setIds('1');
        $query->setType('helloworld1');

        $resultSet = $this->_index->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetTypeSingleSearchArray() : void
    {
        $query = new Ids();

        $query->setIds(array('1', '2'));
        $query->setType('helloworld1');

        $resultSet = $this->_index->search($query)->getWaitHandle()->join();

        $this->assertEquals(2, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetTypeSingleSearchSingleDocInOtherType() : void
    {
        $query = new Ids();

        // Doc 4 is in the second type...
        $query->setIds('4');
        $query->setType('helloworld1');

        $resultSet = $this->_index->search($query)->getWaitHandle()->join();

        // ...therefore 0 results should be returned
        $this->assertEquals(0, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetTypeSingleSearchArrayDocInOtherType() : void
    {
        $query = new Ids();

        // Doc 4 is in the second type...
        $query->setIds(array('1', '4'));
        $query->setType('helloworld1');

        $resultSet = $this->_index->search($query)->getWaitHandle()->join();

        // ...therefore only 1 result should be returned
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetTypeArraySearchArray() : void
    {
        $query = new Ids();

        $query->setIds(array('1', '4'));
        $query->setType(array('helloworld1', 'helloworld2'));

        $resultSet = $this->_index->search($query)->getWaitHandle()->join();

        $this->assertEquals(2, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetTypeArraySearchSingle() : void
    {
        $query = new Ids();

        $query->setIds('4');
        $query->setType(array('helloworld1', 'helloworld2'));

        $resultSet = $this->_index->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());
    }
}
