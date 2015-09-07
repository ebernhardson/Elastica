<?hh
namespace Elastica\Test\Filter;

use Elastica\Document;
use Elastica\Filter\BoolOr;
use Elastica\Filter\Ids;
use Elastica\Test\Base as BaseTest;

class BoolOrTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testAddFilter() : void
    {
        $filter = $this->getMockForAbstractClass('Elastica\Filter\AbstractFilter');
        $orFilter = new BoolOr();
        $returnValue = $orFilter->addFilter($filter);
        $this->assertInstanceOf('Elastica\Filter\BoolOr', $returnValue);
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $orFilter = new BoolOr();

        $filter1 = new Ids();
        $filter1->setIds('1');

        $filter2 = new Ids();
        $filter2->setIds('2');

        $orFilter->addFilter($filter1);
        $orFilter->addFilter($filter2);

        $expectedArray = array(
            'or' => array(
                    $filter1->toArray(),
                    $filter2->toArray(),
                ),
            );

        $this->assertEquals($expectedArray, $orFilter->toArray());
    }

    /**
     * @group unit
     */
    public function testConstruct() : void
    {
        $ids1 = new Ids('foo', array(1, 2));
        $ids2 = new Ids('bar', array(3, 4));

        $and1 = new BoolOr(array($ids1, $ids2));

        $and2 = new BoolOr();
        $and2->addFilter($ids1);
        $and2->addFilter($ids2);

        $this->assertEquals($and1->toArray(), $and2->toArray());
    }

    /**
     * @group functional
     */
    public function testOrFilter() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $doc1 = new Document('', array('categoryId' => 1));
        $doc2 = new Document('', array('categoryId' => 2));
        $doc3 = new Document('', array('categoryId' => 3));

        $type->addDocument($doc1)->getWaitHandle()->join();
        $type->addDocument($doc2)->getWaitHandle()->join();
        $type->addDocument($doc3)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $boolOr = new \Elastica\Filter\BoolOr();
        $boolOr->addFilter(new \Elastica\Filter\Term(Map {'categoryId' => '1'}));
        $boolOr->addFilter(new \Elastica\Filter\Term(Map {'categoryId' => '2'}));

        $resultSet = $type->search($boolOr)->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());
    }
}
