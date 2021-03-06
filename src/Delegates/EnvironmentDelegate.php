<?php

namespace Hiraeth\Twig;

use Hiraeth;
use Twig;

/**
 *
 */
class EnvironmentDelegate implements Hiraeth\Delegate
{
	/**
	 * The Hiraeth application instance
	 *
	 * @access protected
	 * @var Hiraeth\Application
	 */
	protected $app = NULL;
	
	
	/**
	 * The Hiraeth configuration instance
	 *
	 * @access protected
	 * @var Hiraeth\Configuration
	 */
	protected $config = NULL;
	
	
	/**
	 * Get the class for which the delegate operates.
	 *
	 * @static
	 * @access public
	 * @return string The class for which the delegate operates
	 */
	static public function getClass()
	{
		return 'Twig\Environment';
	}
	
	
	/**
	 * Get the interfaces for which the delegate provides a class.
	 *
	 * @static
	 * @access public
	 * @return array A list of interfaces for which the delegate provides a class
	 */
	static public function getInterfaces()
	{
		return [
			'Twig_Environment'
		];
	}
	
	
	/**
	 * Construct the delegate
	 *
	 * @access public
	 * @param Hiraeth\Application $app The Hiraeth application instance
	 * @param Hiraeth\Configuration $config The Hiraeth configuration instance
	 * @return void
	 */
	public function __construct(Hiraeth\Application $app, Hiraeth\Configuration $config)
	{
		$this->app    = $app;
		$this->config = $config;
	}
	
	
	/**
	 * Get the instance of the class for which the delegate operates.
	 *
	 * @access public
	 * @param Hiraeth\Broker $broker The dependency injector instance
	 * @return Twig\Environment The instance of our logger
	 */
	public function __invoke(Hiraeth\Broker $broker)
	{
		$loader_class = $this->config->get('twig', 'loader', 'Twig\Loader\LoaderInterface');
		$cache_path   = $this->config->get('twig', 'cache_path', 'writable/cache/twig');
		$environment  = new Twig\Environment($broker->make($loader_class), [
			'debug'            => (bool) $this->app->getEnvironment('DEBUG'),
			'charset'          => $this->config->get('twig', 'charset', 'utf-8'),
			'strict_variables' => $this->config->get('twig', 'strict', TRUE),
			'cache'            => $this->app->getEnvironment('CACHING', TRUE)
				? $this->app->getDirectory($cache_path)
				: FALSE
		]);
		
		foreach (array_keys($this->config->get('*', 'twig', array())) as $config) {
			$filters    = $this->config->get($config, 'twig.filters', array());
			$globals    = $this->config->get($config, 'twig.globals', array());
			$extensions = $this->config->get($config, 'twig.extensions', array());
			
			foreach ($filters as $name => $filter) {
				if (function_exists($filter['target'])) {
					$environment->addFilter(new Twig\TwigFilter($name, $filter['target'], $filter['options'] ?? array()));
				} else {
					$environment->addFilter(new Twig\TwigFilter($name, new $filter['target'], $filter['options'] ?? array()));
				}
			}
			
			foreach ($globals as $name => $class) {
				$environment->addGlobal($name, $broker->make($class));
			}
			
			foreach ($extensions as $class) {
				$environment->addExtension($broker->make($class));
			}
		}
		return $environment;
	}
}
