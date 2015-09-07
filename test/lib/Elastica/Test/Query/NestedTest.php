<?hh
namespace Elastica\Test\Query;

use Elastica\Query\Nested;
use Elastica\Query\QueryString;
use Elastica\Test\Base as BaseTest;

class NestedTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testSetQuery() : void
    {
        $nested = new Nested();
        $path = 'test1';

        $queryString = new QueryString('test');
        $this->assertInstanceOf('Elastica\Query\Nested', $nested->setQuery($queryString));
        $this->assertInstanceOf('Elastica\Query\Nested', $nested->setPath($path));
        $expected = array(
            'nested' => Map {
                'query' => $queryString->toArray(),
                'path' => $path,
            },
        );

        $this->assertEquals($expected, $nested->toArray());
    }
}
