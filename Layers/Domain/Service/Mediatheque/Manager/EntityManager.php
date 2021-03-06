<?php
namespace Sfynx\MediaBundle\Layers\Domain\Service\Mediatheque\Manager;

use Sfynx\CoreBundle\Layers\Application\Command\Generalisation\Interfaces\CommandInterface;
use Sfynx\CoreBundle\Layers\Domain\Service\Manager\Generalisation\Interfaces\ManagerInterface;
use Sfynx\CoreBundle\Layers\Domain\Service\Manager\Generalisation\AbstractManager;
use Sfynx\CoreBundle\Layers\Domain\Model\Interfaces\EntityInterface;
use Sfynx\MediaBundle\Layers\Domain\Entity\Media;

/**
 * Layout manager working with entities (Orm, Odm, Couchdb)
 *
 * @category   Sfynx\MediaBundle\Layers
 * @package    Domain
 * @subpackage Service\Mediatheque\Manager
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 */
class EntityManager extends AbstractManager implements ManagerInterface
{
    /**
     * {@inheritDoc}
     */
    public function newFromCommand(CommandInterface $command): EntityInterface
    {
        $class = $this->getClass();
        $entity = $class::newFromCommand($command, ['image', 'image2']);
        $this->transformEntity($entity, $command);

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function buildFromCommand(EntityInterface $entity, CommandInterface $command): EntityInterface
    {
        $class = $this->getClass();
        $entity = $class::buildFromCommand($entity, $command, ['image', 'image2']);
        $this->transformEntity($entity, $command);

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function buildFromEntity(CommandInterface $command, EntityInterface $entity): CommandInterface
    {
        $class = $this->getClass();
        $command = $class::buildFromEntity($command, $entity);

        return $command;
    }

    /**
     * @param EntityInterface $entity
     * @param CommandInterface $command
     * @return EntityManager
     */
    protected function transformEntity(EntityInterface &$entity, CommandInterface $command): EntityManager
    {
        if ('' !== $command->category && null !== $command->category) {
            $entity->setCategory(
                $this->getQueryRepository()->getEntityManager()->getReference(
                    '\PiApp\GedmoBundle\Layers\Domain\Entity\Category',
                    $command->category)
            );
        }
        if (null !== $command->image) {
            if (is_null($entity->getImage())) {
                $media = Media::newFromArray($command->image);
            } else {
                $media = Media::buildFromArray($entity->getImage(), $command->image);
            }
            $media->setUpdatedAt(null);
            $entity->setImage($media);
        }

        return $this;
    }
}
