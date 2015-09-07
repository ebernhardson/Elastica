<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\Boosting;
use Elastica\Query\Term;
use Elastica\Test\Base as BaseTest;

class BoostingTest extends BaseTest
{
    /**
     * @var array
     */
    protected array<int, array<string, mixed>> $sampleData = array(
        array('name' => 'Vital Lama', 'price' => 5.2),
        array('name' => 'Vital Match', 'price' => 2.1),
        array('name' => 'Mercury Vital', 'price' => 7.5),
        array('name' => 'Fist Mercury', 'price' => 3.8),
        array('name' => 'Lama Vital 2nd', 'price' => 3.2),
    );

    protected function _getTestIndex() : \Elastica\Index
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $type->setMapping(array(
            'name' => array('type' => 'string', 'index' => 'analyzed'),
            'price' => array('type' => 'float'),
        ))->getWaitHandle()->join();
        $docs = array();
        foreach ($this->sampleData as $key => $value) {
            $docs[] = new Document((string) $key, $value);
        }
        $type->addDocuments($docs)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        return $index;
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $keyword = 'vital';
        $negativeKeyword = 'Mercury';

        $query = new Boosting();
        $positiveQuery = new Term(Map {'name' => $keyword});
        $negativeQuery = new Term(Map {'name' => $negativeKeyword});
        $query->setPositiveQuery($positiveQuery);
        $query->setNegativeQuery($negativeQuery);
        $query->setNegativeBoost(0.3);

        $expected = array(
            'boosting' => Map {
                'positive' => $positiveQuery->toArray(),
                'negative' => $negativeQuery->toArray(),
                'negative_boost' => 0.3,
            },
        );
        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * @group functional
     */
    public function testNegativeBoost() : void
    {
        $keyword = 'vital';
        $negativeKeyword = 'mercury';

        $query = new Boosting();
        $positiveQuery = new Term(Map {'name' => $keyword});
        $negativeQuery = new Term(Map {'name' => $negativeKeyword});
        $query->setPositiveQuery($positiveQuery);
        $query->setNegativeQuery($negativeQuery);
        $query->setNegativeBoost(0.2);

        $response = $this->_getTestIndex()->search($query)->getWaitHandle()->join();
        $results = $response->getResults();

        $this->assertEquals($response->getTotalHits(), 4);

        $lastResult = $results[3]->getData();
        $this->assertEquals($lastResult['name'], $this->sampleData[2]['name']);
    }
}
