<?php
/**
 * This file is part of the <Media> project.
 *
 * @category Media
 * @package  EventSubscriber 
 * @author   Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @since    2012-07-20
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sfynx\MediaBundle\EventSubscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sfynx\CmfBundle\EventListener\abstractListener as abstractCmfListener;
use Sfynx\ToolBundle\Util\PiStringManager;

/**
 * Media entity Subscriber.
 *
 * @category Media
 * @package  EventSubscriber 
 * @author   Etienne de Longeaux <etienne.delongeaux@gmail.com>
 */
class EventSubscriberMedia  extends abstractCmfListener implements EventSubscriber
{
    /**
     * Constructor
     * 
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        
        if (class_exists('Sfynx\MediaBundle\Entity\Translation\MediathequeTranslation')) {
            $this->addAssociation('Sfynx\MediaBundle\Entity\Mediatheque', 'mapOneToMany', array(
                'fieldName'     => 'translations',
                'targetEntity'  => 'Sfynx\MediaBundle\Entity\Translation\MediathequeTranslation',
                'cascade'       => array(
                    'persist',
                    'remove',
                ),
                'mappedBy'      => 'object',
                'orderBy'       => array(
                    'locale'  => 'ASC',
                ),
            ));
            $this->addAssociation('Sfynx\MediaBundle\Entity\Translation\MediathequeTranslation', 'mapManyToOne', array(
                'fieldName'     => 'object',
                'targetEntity'  => 'Sfynx\MediaBundle\Entity\Mediatheque',
                'cascade'       => array(),
                'inversedBy'    => 'translations',
                'joinColumns'   =>  array(
                    array(
                        'name'  => 'object_id',
                        'referencedColumnName' => 'id',
                        'onDelete' => 'CASCADE'
                    ),
                ),
            ));
        }   
        
        if (class_exists('PiApp\GedmoBundle\Entity\Category')) {
            $this->addAssociation('Sfynx\MediaBundle\Entity\Mediatheque', 'mapManyToOne', array(
                'fieldName'     => 'category',
                'targetEntity'  => 'PiApp\GedmoBundle\Entity\Category',
                'cascade'       => array(
                    'persist',
                ),
                'mappedBy'      => NULL,
                'inversedBy'    => 'items_media',
                'joinColumns'   =>  array(
                    array(
                        'name'  => 'category',
                        'referencedColumnName' => 'id',
                        'nullable' => true
                    ),
                ),
                'orphanRemoval' => false,
            ));
        }
        
        if (class_exists('Sfynx\MediaBundle\Entity\Media')) {
            $this->addAssociation('Sfynx\MediaBundle\Entity\Mediatheque', 'mapManyToOne', array(
                'fieldName'     => 'image',
                'targetEntity'  => 'Sfynx\MediaBundle\Entity\Media',
                'cascade'       => array(
                    'all',
                ),
                'joinColumns'   =>  array(
                    array(
                        'name'  => 'media',
                        'referencedColumnName' => 'id',
                        'nullable' => true
                    ),
                ),
                'orphanRemoval' => false,
            ));
            $this->addAssociation('Sfynx\MediaBundle\Entity\Mediatheque', 'mapManyToOne', array(
                'fieldName'     => 'image2',
                'targetEntity'  => 'Sfynx\MediaBundle\Entity\Media',
                'cascade'       => array(
                    'all',
                ),
                'joinColumns'   =>  array(
                    array(
                        'name'  => 'media2',
                        'referencedColumnName' => 'id',
                        'nullable' => true
                    ),
                ),
                'orphanRemoval' => false,
            ));            
        }         
    }
    
    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::preUpdate,
        );
    }
    
    /**
     * @param \Doctrine\Common\EventArgs $args
     * @return void
     */
    protected function recomputeSingleEntityChangeSet(EventArgs $args)
    {
        $em = $args->getEntityManager();

        $em->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(get_class($args->getEntity())),
            $args->getEntity()
        );
    }
    
    /**
     * @param EventArgs $args
     * 
     * @return void
     */
    public function preUpdate(EventArgs $eventArgs)
    {
        $this->_MediaGedmo($eventArgs);   
        $this->_cropImage($eventArgs);
    }
    
    /**
     * @param EventArgs $args
     * 
     * @return void
     */
    public function prePersist(EventArgs $eventArgs)
    {
        $this->_MediaGedmo($eventArgs);
    }    
    
    /**
     * We are setting the Gedmo Media to null if removing the Media was checked. 
     *
     * @param object $eventArgs
     *
     * @return void
     * @access private
     * @final
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    private function _MediaGedmo($eventArgs)
    {
        $entity        = $eventArgs->getEntity();
        $entityManager = $eventArgs->getEntityManager();
        if ( $this->isUsernamePasswordToken() 
                && (($entity instanceof \Proxies\__CG__\Sfynx\MediaBundle\Entity\Mediatheque) || ($entity instanceof \Sfynx\MediaBundle\Entity\Mediatheque)) 
                && !$this->isRestrictionByRole($entity) 
                && ($entity->getMediadelete() == true) )
        {
            try {
                $entity_table = $this->getOwningTable($eventArgs, $entity);
                $query = "UPDATE $entity_table mytable SET mytable.media = null WHERE mytable.id = ?";
                $this->_connexion($eventArgs)->executeUpdate($query, array($entity->getId()));
                
                $this->container->get('bootstrap.media.provider.image')->preRemove($entity->getImage());
                $this->_connexion($eventArgs)->delete($this->getOwningTable($eventArgs, $entity->getImage()), array('id'=>$entity->getImage()->getId()));
                $this->container->get('bootstrap.media.provider.image')->postRemove($entity->getImage());                
            } catch (\Exception $e) {
            }
            $entity->setImage(null);
        }         
        // we clean the filename.
        if ($this->isUsernamePasswordToken() 
                && (($entity instanceof \Proxies\__CG__\Sfynx\MediaBundle\Entity\Mediatheque) 
                        || ($entity instanceof \Sfynx\MediaBundle\Entity\Mediatheque)
                ) 
        ){
            if ($entity->getImage() instanceof \Sfynx\MediaBundle\Entity\Mediatheque) {
                $entity->getImage()->setName($this->_cleanName($entity->getImage()->getName()));
            }
        }        
    }

    /**
     * We return the clean of a string.
     *
     * @param string $string
     * 
     * @return string name
     * @access private
     * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
     */
    private function _cleanName($string)
    {
        $string = PiStringManager::minusculesSansAccents($string);
        $string = PiStringManager::cleanFilename($string);
         
        return $string;
    }   

    /**
     * We link the entity widget type to the page.
     *
     * @param object $eventArgs
     *
     * @return void
     * @access protected
     * @final
     * @author Riad HELLAL <hellal.riad@gmail.com>
     */
    private function _cropImage($eventArgs) 
    {
    	if ($this->container->isScopeActive('request') && isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
            $entityManager = $eventArgs->getEntityManager();
            $tab_post = $this->container->get('request')->request->all();
            if (!empty($tab_post['img_crop']) && $tab_post['img_crop'] == '1') {
                    $entity = $eventArgs->getEntity();
                    $getMedia = "getMedia";
                    $setMedia = "setMedia";
                    if ($this->isUsernamePasswordToken() && method_exists($entity, $getMedia) && method_exists($entity, $setMedia)&& ( ($entity->$getMedia() instanceof \Sfynx\MediaBundle\Entity\Mediatheque) ) ) {
                            $mediaPath = $this->container->get('sonata.media.twig.extension')->path($entity->$getMedia()->getImage()->getId(), 'reference');
                            $src = $this->container->get('kernel')->getRootDir() . '/../web/' . $mediaPath;
                            if (file_exists($src)) {
                                    $extension =  pathinfo($src, PATHINFO_EXTENSION);
                                    $mediaCrop = $this->container->get('sonata.media.twig.extension')->path($entity->$getMedia()->getImage()->getId(), $tab_post['img_name']);
                                    $targ_w = $tab_post['img_width']; //$globals['tailleWidthEdito1'];
                                    $targ_h = $tab_post['img_height'];
                                    $jpeg_quality = $tab_post['img_quality'];
                                    switch ($extension) {
                                            case 'jpg':
                                                    $img_r = imagecreatefromjpeg($src);
                                                    break;
                                            case 'jpeg':
                                                    $img_r = imagecreatefromjpeg($src);
                                                    break;
                                            case 'gif':
                                                    $img_r = imagecreatefromgif($src);
                                                    break;
                                            case 'png':
                                                    $img_r = imagecreatefrompng($src);
                                                    break;
                                            default:
                                                    echo "L'image n'est pas dans un format reconnu. Extensions autorisÃ©es : jpg, jpeg, gif, png";
                                                    break;
                                    }	    
                                    $dst_r = imagecreatetruecolor($targ_w, $targ_h);
                                    imagecopyresampled($dst_r, $img_r, 0, 0, $tab_post['x'], $tab_post['y'], $targ_w, $targ_h, $tab_post['w'], $tab_post['h']);
                                    switch ($extension) {
                                            case 'jpg':
                                                    imagejpeg($dst_r, $this->container->get('kernel')->getRootDir() . '/../web/' . $mediaCrop, $jpeg_quality);
                                                    break;
                                            case 'jpeg':
                                                    imagejpeg($dst_r, $this->container->get('kernel')->getRootDir() . '/../web/' . $mediaCrop, $jpeg_quality);
                                                    break;
                                            case 'gif':
                                                    imagegif($dst_r, $this->container->get('kernel')->getRootDir() . '/../web/' . $mediaCrop);
                                                    break;
                                            case 'png':
                                                    imagepng($dst_r, $this->container->get('kernel')->getRootDir() . '/../web/' . $mediaCrop);
                                                    break;
                                            default:
                                                    echo "L'image n'est pas dans un format reconnu. Extensions autorisÃ©es : jpg, gif, png";
                                                    break;
                                    }
                                    @chmod($this->container->get('kernel')->getRootDir() . '/../web/' . $mediaCrop, 0777);
                            }
                    }
            } elseif(!empty($tab_post['img_crop']) && count($tab_post['img_crop']) >= 1){                
                if ($this->isUsernamePasswordToken() ) {
                    foreach ($tab_post['img_crop'] as $media_id => $value) {
                        if ($value == 1) {
                            $mediaPath = $this->container->get('sonata.media.twig.extension')->path($media_id, 'reference');
                            $src = $this->container->get('kernel')->getRootDir() . '/../web/' . $mediaPath;
                            if (file_exists($src)) {
                                $extension =  pathinfo($src, PATHINFO_EXTENSION);
                                $mediaCrop = $this->container->get('sonata.media.twig.extension')->path($media_id, $tab_post['img_name_'.$media_id]);
                                $targ_w = $tab_post['img_width_'.$media_id]; //$globals['tailleWidthEdito1'];
                                $targ_h = $tab_post['img_height_'.$media_id];
                                $jpeg_quality = $tab_post['img_quality_'.$media_id];
                                switch ($extension) {
                                    case 'jpg':
                                        $img_r = imagecreatefromjpeg($src);
                                        break;
                                    case 'jpeg':
                                        $img_r = imagecreatefromjpeg($src);
                                        break;
                                    case 'gif':
                                        $img_r = imagecreatefromgif($src);
                                        break;
                                    case 'png':
                                        $img_r = imagecreatefrompng($src);
                                        break;
                                    default:
                                        echo "L'image n'est pas dans un format reconnu. Extensions autorisÃ©es : jpg, jpeg, gif, png";
                                        break;
                                }
                                $dst_r = imagecreatetruecolor($targ_w, $targ_h);
                                imagecopyresampled($dst_r, $img_r, 0, 0, $tab_post['x_'.$media_id], $tab_post['y_'.$media_id], $targ_w, $targ_h, $tab_post['w_'.$media_id], $tab_post['h_'.$media_id]);
                                switch ($extension) {
                                    case 'jpg':
                                        imagejpeg($dst_r, $this->container->get('kernel')->getRootDir() . '/../web/' . $mediaCrop, $jpeg_quality);
                                        break;
                                    case 'jpeg':
                                        imagejpeg($dst_r, $this->container->get('kernel')->getRootDir() . '/../web/' . $mediaCrop, $jpeg_quality);
                                        break;
                                    case 'gif':
                                        imagegif($dst_r, $this->container->get('kernel')->getRootDir() . '/../web/' . $mediaCrop);
                                        break;
                                    case 'png':
                                        imagepng($dst_r, $this->container->get('kernel')->getRootDir() . '/../web/' . $mediaCrop);
                                        break;
                                    default:
                                        echo "L'image n'est pas dans un format reconnu. Extensions autorisÃ©es : jpg, gif, png";
                                        break;
                                }
                                @chmod($this->container->get('kernel')->getRootDir() . '/../web/' . $mediaCrop, 0777);
                            }                            
                        }
                    } // endforeach        
                }
            }
    	}
    }
}
