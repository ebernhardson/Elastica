<?hh // strict
namespace Elastica\Connection\Strategy;

use Elastica\Exception\InvalidException;

/**
 * Description of StrategyFactory.
 *
 * @author chabior
 */
class StrategyFactory
{
    /**
     * @param mixed|callable|string|StrategyInterface $strategyName
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return \Elastica\Connection\Strategy\StrategyInterface
     */
    public static function create(mixed $strategyName) : StrategyInterface
    {
        if ($strategyName instanceof StrategyInterface) {
            return $strategyName;
        }

        if (CallbackStrategy::isValid($strategyName)) {
            return new CallbackStrategy(/* UNSAFE_EXPR */ $strategyName);
        }

        if (is_string($strategyName)) {
			$map = self::createStrategyMap();
			if ($map->contains($strategyName)) {
				$factory = $map->at($strategyName);
				return $factory();
			}
        }

        throw new InvalidException('Can\'t create strategy instance by given argument');
    }

	protected static function createStrategyMap() : Map<string, (function() : StrategyInterface)>
	{
		return Map {
			'RoundRobin' => () ==> new RoundRobin(),
			'Simple' => () ==> new Simple(),
		};
	}
}
