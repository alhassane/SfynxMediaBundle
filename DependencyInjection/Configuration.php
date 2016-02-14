<?php
/**
 * This file is part of the <Media> project.
 *
 * @category   SonataMedia
 * @package    DependencyInjection
 * @subpackage Configuration
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @copyright  2015 PI-GROUPE
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version    2.3
 * @link       http://opensource.org/licenses/gpl-license.php
 * @since      2015-02-16
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sfynx\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 * 
 * @category   SonataMedia
 * @package    DependencyInjection
 * @subpackage Configuration
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @copyright  2015 PI-GROUPE
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version    2.3
 * @link       http://opensource.org/licenses/gpl-license.php
 * @since      2015-02-16
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sfynx_media');
        
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $this->addCropConfig($rootNode);
        
        return $treeBuilder;
    }      
    
    /**
     * Crop config
     *
     * @param ArrayNodeDefinition $rootNode An ArrayNodeDefinition instance
     * 
     * @return void
     * @access protected
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    protected function addCropConfig(ArrayNodeDefinition $rootNode) {
    	$rootNode
    	->children()
        	->arrayNode('crop')
                ->addDefaultsIfNotSet()
                ->children()
                        ->arrayNode('formats')
                        ->isRequired()
                            ->prototype('array')
                                ->children()
                                	->scalarNode('prefix')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('legend')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('width')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('height')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('ratio')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('quality')->cannotBeEmpty()->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                ->end()
            ->end()
    	->end();
    }     
}
