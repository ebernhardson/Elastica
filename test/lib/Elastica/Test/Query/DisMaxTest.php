<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\DisMax;
use Elastica\Query\Ids;
use Elastica\Query\QueryString;
use Elastica\Test\Base as BaseTest;

class DisMaxTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $query = new DisMax();

        $idsQuery1 = new Ids();
        $idsQuery1->setIds(1);

        $idsQuery2 = new Ids();
        $idsQuery2->setIds(2);

        $idsQuery3 = new Ids();
        $idsQuery3->setIds(3);

        $boost = 1.2;
        $tieBreaker = 2.0;

        $query->setBoost($boost);
        $query->setTieBreaker($tieBreaker);
        $query->addQuery($idsQuery1);
        $query->addQuery($idsQuery2);
        $query->addQuery($idsQuery3->toArray());

        $expectedArray = array(
            'dis_max' => Map {
                'tie_breaker' => $tieBreaker,
                'boost' => $boost,
                'queries' => array(
                    $idsQuery1->toArray(),
                    $idsQuery2->toArray(),
                    $idsQuery3->toArray(),
                ),
            },
        );

        $this->assertEquals($expectedArray, $query->toArray());
    }

    /**
     * @group functional
     */
    public function testQuery() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'Basel-Stadt')),
            new Document('2', array('name' => 'New York')),
            new Document('3', array('name' => 'Baden')),
            new Document('4', array('name' => 'Baden Baden')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $queryString1 = new QueryString('Bade*');
        $queryString2 = new QueryString('Base*');

        $boost = 1.2;
        $tieBreaker = 2.0;

        $query = new DisMax();
        $query->setBoost($boost);
        $query->setTieBreaker($tieBreaker);
        $query->addQuery($queryString1);
        $query->addQuery($queryString2);
        $resultSet = $type->search($query)->getWaitHandle()->join();

        $this->assertEquals(3, $resultSet->count());
    }
}
