<?hh
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\Sum;
use Elastica\Document;
use Elastica\Query;
use Elastica\Script;

class ScriptTest extends BaseAggregationTest
{
    protected function _getIndexForTest() : \Elastica\Index
    {
        $index = $this->_createIndex();

        $index->getType('test')->addDocuments(array(
            new Document('1', array('price' => 5)),
            new Document('2', array('price' => 8)),
            new Document('3', array('price' => 1)),
            new Document('4', array('price' => 3)),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        return $index;
    }

    /**
     * @group functional
     */
    public function testAggregationScript() : void
    {
        $agg = new Sum('sum');
        // x = (0..1) is groovy-specific syntax, to see if lang is recognized
        $script = new Script("x = (0..1); return doc['price'].value", null, 'groovy');
        $agg->setScript($script);

        $query = new Query();
        $query->addAggregation($agg);
        $response = $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('sum');

        $this->assertEquals(5 + 8 + 1 + 3, $results['value']);
    }

    /**
     * @group functional
     */
    public function testAggregationScriptAsString() : void
    {
        $agg = new Sum('sum');
        $agg->setScript("doc['price'].value");

        $query = new Query();
        $query->addAggregation($agg);
        $response = $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('sum');

        $this->assertEquals(5 + 8 + 1 + 3, $results['value']);
    }

    /**
     * @group unit
     */
    public function testSetScript() : void
    {
        $aggregation = 'sum';
        $string = "doc['price'].value";
        $params = Map {
            'param1' => 'one',
            'param2' => 1,
        };
        $lang = 'groovy';

        $agg = new Sum($aggregation);
        $script = new Script($string, $params, $lang);
        $agg->setScript($script);

        $array = $agg->toArray();

        $expected = array(
            $aggregation => Map {
                'script' => $string,
                'params' => $params,
                'lang' => $lang,
            },
        );
        $this->assertEquals($expected, $array);
    }
}
