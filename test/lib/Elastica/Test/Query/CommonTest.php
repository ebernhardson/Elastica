<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\Common;
use Elastica\Test\Base as BaseTest;

class CommonTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $query = new Common('body', 'test query', .001);
        $query->setLowFrequencyOperator(Common::OPERATOR_AND);

        $expected = array(
            'common' => Map {
                'body' => array(
                    'query' => 'test query',
                    'cutoff_frequency' => .001,
                    'low_freq_operator' => 'and',
                ),
            },
        );

        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * @group functional
     */
    public function testQuery() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $docs = array(
            new Document('1', array('body' => 'foo baz')),
            new Document('2', array('body' => 'foo bar baz')),
            new Document('3', array('body' => 'foo bar baz bat')),
        );
        //add documents to create common terms
        for ($i = 4; $i < 24; ++$i) {
            $docs[] = new Document((string) $i, array('body' => 'foo bar'));
        }
        $type->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $query = new Common('body', 'foo bar baz bat', .5);
        $results = $type->search($query)->getWaitHandle()->join()->getResults();

        //documents containing only common words should not be returned
        $this->assertEquals(3, count($results));

        $query->setMinimumShouldMatch(2);
        $results = $type->search($query)->getWaitHandle()->join();

        //only the document containing both low frequency terms should match
        $this->assertEquals(1, $results->count());
    }
}
