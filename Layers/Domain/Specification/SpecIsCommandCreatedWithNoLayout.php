<?php
namespace Sfynx\MediaBundle\Layers\Domain\Specification;

use Sfynx\SpecificationBundle\Specification\AbstractSpecification;
use stdClass;
use Sfynx\CoreBundle\Layers\Application\Query\Generalisation\Interfaces\QueryInterface;

/**
 * Class SpecIsCommandCreatedWithNoLayout
 *
 * @category Sfynx\MediaBundle\Layers
 * @package Domain
 * @subpackage Specification
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 */
class SpecIsCommandCreatedWithNoLayout extends AbstractSpecification
{
    /**
     * return true if the command is validated
     *
     * @param stdClass $object
     * @return bool
     */
    public function isSatisfiedBy(stdClass $object): bool
    {
        return property_exists($object->wfCommand, 'NoLayout') &&
            is_bool($object->wfCommand->NoLayout);
    }
}
