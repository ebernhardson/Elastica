<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Filter\Term;
use Elastica\Query\Filtered;
use Elastica\Query\QueryString;
use Elastica\Test\Base as BaseTest;

class FilteredTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testFilteredSearch() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('helloworld');

        $type->addDocuments(array(
            new Document('1', array('id' => 1, 'email' => 'test@test.com', 'username' => 'hanswurst', 'test' => array('2', '3', '5'))),
            new Document('2', array('id' => 2, 'email' => 'test@test.com', 'username' => 'peter', 'test' => array('2', '3', '5'))),
        ))->getWaitHandle()->join();

        $queryString = new QueryString('test*');

        $filter1 = new Term();
        $filter1->setTerm('username', 'peter');

        $filter2 = new Term();
        $filter2->setTerm('username', 'qwerqwer');

        $query1 = new Filtered($queryString, $filter1);
        $query2 = new Filtered($queryString, $filter2);
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search($queryString)->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());

        $resultSet = $type->search($query1)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        $resultSet = $type->search($query2)->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());
    }

    /**
     * @group unit
     */
    public function testFilteredGetter() : void
    {
        $queryString = new QueryString('test*');

        $filter1 = new Term();
        $filter1->setTerm('username', 'peter');

        $filter2 = new Term();
        $filter2->setTerm('username', 'qwerqwer');

        $query1 = new Filtered($queryString, $filter1);
        $query2 = new Filtered($queryString, $filter2);

        $this->assertEquals($query1->getQuery(), $queryString);
        $this->assertEquals($query2->getQuery(), $queryString);
        $this->assertEquals($query1->getFilter(), $filter1);
        $this->assertEquals($query2->getFilter(), $filter2);
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testFilteredWithoutArgumentsShouldRaiseException() : void
    {
        $query = new Filtered();
        $query->toArray();
    }

    /**
     * @group functional
     */
    public function testFilteredSearchNoQuery() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('helloworld');

        $type->addDocuments(array(
            new Document('1', array('id' => 1, 'email' => 'test@test.com', 'username' => 'hanswurst', 'test' => array('2', '3', '5'))),
            new Document('2', array('id' => 2, 'email' => 'test@test.com', 'username' => 'peter', 'test' => array('2', '3', '5'))),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $filter = new Term();
        $filter->setTerm('username', 'peter');

        $query = new Filtered(null, $filter);

        $resultSet = $type->search($query)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testFilteredSearchNoFilter() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('helloworld');

        $doc = new Document('1', array('id' => 1, 'email' => 'test@test.com', 'username' => 'hanswurst', 'test' => array('2', '3', '5')));
        $type->addDocument($doc)->getWaitHandle()->join();
        $doc = new Document('2', array('id' => 2, 'email' => 'test@test.com', 'username' => 'peter', 'test' => array('2', '3', '5')));
        $type->addDocument($doc)->getWaitHandle()->join();

        $queryString = new QueryString('hans*');

        $query = new Filtered($queryString);
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search($query)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
    }
}
