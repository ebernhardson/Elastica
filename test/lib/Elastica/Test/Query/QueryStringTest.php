<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\QueryString;
use Elastica\Test\Base as BaseTest;

class QueryStringTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testSearchMultipleFields() : void
    {
        $str = md5(rand());
        $query = new QueryString($str);

        $expected = Map {
            'query' => $str,
        };

        $this->assertEquals(Map {'query_string' => $expected}, $query->toArray());

        $fields = array();
        $max = rand() % 10 + 1;
        for ($i = 0; $i <  $max; ++$i) {
            $fields[] = md5(rand());
        }

        $query->setFields($fields);
        $expected['fields'] = $fields;
        $this->assertEquals(Map {'query_string' => $expected}, $query->toArray());

        foreach (array(false, true) as $val) {
            $query->setUseDisMax($val);
            $expected['use_dis_max'] = $val;

            $this->assertEquals(Map {'query_string' => $expected}, $query->toArray());
        }
    }

    /**
     * @group functional
     */
    public function testSearch() : void
    {
        $index = $this->_createIndex();
        $index->getSettings()->setNumberOfReplicas(0)->getWaitHandle()->join();
        $type = $index->getType('helloworld');

        $doc = new Document('1', array('email' => 'test@test.com', 'username' => 'hanswurst', 'test' => array('2', '3', '5')));
        $type->addDocument($doc)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $queryString = new QueryString('test*');
        $resultSet = $type->search($queryString)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * Tests if search in multiple fields is possible.
     *
     * @group functional
     */
    public function testSearchFields() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $doc = new Document('1', array('title' => 'hello world', 'firstname' => 'nicolas', 'lastname' => 'ruflin', 'price' => '102', 'year' => '2012'));
        $type->addDocument($doc)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $query = new QueryString();
        $query = $query->setQuery('ruf*');
        $query = $query->setDefaultField('title');
        $query = $query->setFields(array('title', 'firstname', 'lastname', 'price', 'year'));

        $resultSet = $type->search($query)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group unit
     */
    public function testSetDefaultOperator() : void
    {
        $operator = 'AND';
        $query = new QueryString('test');
        $query->setDefaultOperator($operator);

        $data = $query->toArray();

        $this->assertEquals(/* UNSAFE_EXPR */ $data['query_string']['default_operator'], $operator);
    }

    /**
     * @group unit
     */
    public function testSetDefaultField() : void
    {
        $default = 'field1';
        $query = new QueryString('test');
        $query->setDefaultField($default);

        $data = $query->toArray();

        $this->assertEquals(/* UNSAFE_EXPR */ $data['query_string']['default_field'], $default);
    }

    /**
     * @group unit
     */
    public function testSetRewrite() : void
    {
        $rewrite = 'scoring_boolean';
        $query = new QueryString('test');
        $query->setRewrite($rewrite);

        $data = $query->toArray();

        $this->assertEquals(/* UNSAFE_EXPR */ $data['query_string']['rewrite'], $rewrite);
    }

    /**
     * @group unit
     */
    public function testSetTimezone() : void
    {
        $timezone = 'Europe/Paris';
        $text = 'date:[2012 TO 2014]';

        $query = new QueryString($text);
        $query->setTimezone($timezone);

        $expected = Map {
            'query_string' => Map {
                'query' => $text,
                'time_zone' => $timezone,
            },
        };

        $this->assertEquals($expected, $query->toArray());
        $this->assertInstanceOf('Elastica\Query\QueryString', $query->setTimezone($timezone));
    }

    /**
     * @group unit
     */
    public function testSetPhraseSlop() : void
    {
        $phraseSlop = 9;

        $query = new QueryString('test');
        $query->setPhraseSlop($phraseSlop);

        $data = $query->toArray();
        $this->assertEquals($phraseSlop, /* UNSAFE_EXPR */ $data['query_string']['phrase_slop']);
    }

    /**
     * @group functional
     */
    public function testSetBoost() : void
    {
        $index = $this->_createIndex();
        $query = new QueryString('test');
        $query->setBoost(9.3);

        $doc = new Document('', array('name' => 'test'));
        $index->getType('test')->addDocument($doc)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());
    }
}
