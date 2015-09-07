<?hh
namespace Elastica\Test\Filter;

use Elastica\Filter\Query;
use Elastica\Query\QueryString;
use Elastica\Test\Base as BaseTest;

class QueryTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testSimple() : void
    {
        $query = new QueryString('foo bar');
        $filter = new Query($query);

        $expected = array(
            'query' => Map {
                'query_string' => Map {
                    'query' => 'foo bar',
                },
            },
        );

        $this->assertEquals($expected, $filter->toArray());
    }

    /**
     * @group unit
     */
    public function testExtended() : void
    {
        $query = new QueryString('foo bar');
        $filter = new Query($query);
        $filter->setCached(true);

        $expected = array(
            'fquery' => Map {
                'query' => Map {
                    'query_string' => Map {
                        'query' => 'foo bar',
                    },
                },
                '_cache' => true,
            },
        );

        $this->assertEquals($expected, $filter->toArray());
    }
}
