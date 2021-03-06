<?php
namespace Sfynx\MediaBundle\Layers\Domain\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Sfynx\CoreBundle\Layers\Domain\Model\Traits;
use Sfynx\MediaBundle\Layers\Domain\Entity\Interfaces\MediaInterface;

/**
 * Sfynx\MediaBundle\Layers\Domain\Entity\Mediatheque
 *
 * @ORM\MappedSuperclass
 * @ORM\Table(name="sfynx_media")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\TranslationEntity(class="Sfynx\MediaBundle\Layers\Domain\Entity\Translation\MediaTranslation")
 *
 * @category   Sfynx\MediaBundle\Layers
 * @package    Domain
 * @subpackage Entity
 *
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @copyright  2015 PI-GROUPE
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version    2.3
 * @link       http://opensource.org/licenses/gpl-license.php
 * @since      2015-02-16
 */
class Media implements MediaInterface
{
    use Traits\TraitBuild;
    use Traits\TraitEnabled;
    use Traits\TraitDatetime;

    /**
     * List of all translatable fields
     *
     * @var array
     * @access  protected
     */
    protected $_fields    = array('title', 'descriptif');

    /**
     * Name of the Translation Entity
     *
     * @var array
     * @access  protected
     */
    protected $_translationClass = 'Sfynx\MediaBundle\Layers\Domain\Entity\Translation\MediaTranslation';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    protected $name;

    /**
     * @var string $title
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=255, nullable = true)
     */
    protected $title;

    /**
     * @var text $descriptif
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="descriptif", type="text", nullable=true)
     */
    protected $descriptif;

    /**
     * @ORM\Column(name="public_uri", type="string", nullable=true)
     */
    protected $publicUri;

    /**
     * @ORM\Column(name="mime_type", type="string", nullable=true)
     */
    protected $mimeType;

    /**
     * @ORM\Column(name="provider_name_storage", type="string", nullable=true)
     */
    protected $providerName;

    /**
     * @ORM\Column(name="provider_reference", type="string", nullable=true)
     */
    protected $providerReference;

    /**
     * @ORM\Column(name="provider_data", type="json_array", nullable=true)
     */
    protected $providerData;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $extension;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    protected $metadata;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $sourceName;

    /**
     * @var UploadedFile
     */
    protected $uploadedFile;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->publicUri = null;
        $this->metadata  = [];
    }

    /**
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * toString.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('[%s] %s',
            $this->getProviderName(),
            $this->getProviderReference()
        );
    }

    /**
     * toArray.
     *
     * @return array
     */
    public function __toArray()
    {
        return array(
            'publicUri'         => $this->publicUri,
            'mimeType'          => $this->mimeType,
            'providerName'      => $this->providerName,
            'providerReference' => $this->providerReference,
            'providerData'      => $this->providerData,
            'extension'         => $this->extension,
            'metadata'          => $this->metadata,
            'createdAt'         => $this->createdAt,
            'updatedAt'         => $this->updatedAt,
        );
    }

//    /**
//     * Magic setter.
//     *
//     * @param string $name  The setter name.
//     * @param mixed  $value The value to set.
//     * @return Media
//     */
//    public function __set($name, $value)
//    {
//        $name = Inflector::tableize($name);
//        $this->setMetadata($name, $value);
//
//        return $this;
//    }
//
//    /**
//     * Magic getter.
//     *
//     * @param string $name The getter name.
//     * @return mixed The value.
//     */
//    public function __get($name)
//    {
//        $name = Inflector::tableize($name);
//
//        return $this->getMetadata($name);
//    }

    /**
     * Magic call.
     *
     * @param string $method    The method name.
     * @param mixed  $arguments The given arguments.
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $property  = $method;
        $subMethod = substr($method, 0, 3);

        if (in_array($subMethod, array('set', 'get'))) {
            $property = Inflector::tableize(substr($method, 3));

            if ('set' === $subMethod) {
                $this->$property = $arguments;
            }
        }

        return $this->$property;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->__toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($data)
    {
        $unserializedData = unserialize($data);

        $this->publicUri         = $unserializedData['publicUri'];
        $this->mimeType          = $unserializedData['mimeType'];
        $this->providerName      = $unserializedData['providerName'];
        $this->providerReference = $unserializedData['providerReference'];
        $this->providerData      = $unserializedData['providerData'];
        $this->extension         = $unserializedData['extension'];
        $this->metadata          = $unserializedData['metadata'];
        $this->createdAt         = $unserializedData['createdAt'];
        $this->updatedAt         = $unserializedData['updatedAt'];
    }

    /**
     * isImageable
     */
    public function isImageable($withPdf = true)
    {
        if (null === $this->getPublicUri()) {
            return false;
        }

        if ($withPdf
            && 'application/pdf' === $this->getMimeType()
        ) {
            return true;
        }

        return (boolean)preg_match("#^image/#", $this->getMimeType());
    }

    /**
     * Returns public data
     *
     * @return array
     */
    public function getPublicData()
    {
        return array(
            'providerName'      => $this->getProviderName(),
            'providerReference' => $this->getProviderReference(),
            'publicUri'         => $this->getPublicUri(),
            'extension'         => $this->getExtension(),
            'mimeType'          => $this->getMimeType()
        );
    }

    /**
     * Set uploaded file.
     *
     * @param UploadedFile $uploadedFile
     * @return Media
     */
    public function setUploadedFile(UploadedFile $uploadedFile)
    {
        $this->uploadedFile = $uploadedFile;

        return $this;
    }

    /**
     * Returns uploaded file.
     *
     * @return UploadedFile
     */
    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Media
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set descriptif
     *
     * @param text $descriptif
     * @return Media
     */
    public function setDescriptif ($descriptif)
    {
        $this->descriptif = $descriptif;
        return $this;
    }

    /**
     * Get descriptif
     *
     * @return text
     */
    public function getDescriptif ()
    {
        return $this->descriptif;
    }

    /**
     * Returns public uri
     *
     * @param string $publicUri
     * @return Media
     */
    public function setPublicUri($publicUri)
    {
        $this->publicUri = $publicUri;

        return $this;
    }

    /**
     * Get public uri.
     *
     * @return string
     */
    public function getPublicUri()
    {
        return $this->publicUri;
    }

    /**
     * Returns url.
     *
     * @param string $extension
     * @return string
     */
    public function getUrl($extension = null, $query = [])
    {
        if (null === $this->getPublicUri()) {
            return '';
        }

        $countValidQueries = 0;
        foreach ($query as $k => $param) {
            if (!$param) {
                unset($query[$k]);
            } else {
                $countValidQueries++;
            }
        }

        $url = sprintf(
            '%s.%s',
            $this->getPublicUri(),
            null === $extension ? $this->getExtension() : $extension
        );

        if ($countValidQueries == 0) {
            return $url;
        }

        return sprintf(
            '%s?%s',
            $url,
            http_build_query($query)
        );
    }

    /**
     * Set mimeType.
     *
     * @param string $mimeType
     * @return Media
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Returns mimeType.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set providerName.
     *
     * @param string $providerName
     * @return Media
     */
    public function setProviderName($providerName)
    {
        $this->providerName = $providerName;
        return $this;
    }

    /**
     * Returns providerName.
     *
     * @return string
     */
    public function getProviderName()
    {
        return $this->providerName;
    }

    /**
     * Set sourceName.
     *
     * @param string $sourceName
     * @return Media
     */
    public function setSourceName($sourceName)
    {
        $this->sourceName = $sourceName;
        return $this;
    }

    /**
     * Return the path upload file
     *
     * @return string
     */
    public function getSourceName()
    {
        return $this->sourceName;
    }

    /**
     * Set providerReference.
     *
     * @param string $providerReference
     * @return Media
     */
    public function setProviderReference($providerReference)
    {
        $this->providerReference = $providerReference;

        return $this;
    }

    /**
     * Returns providerReference.
     *
     * @return string
     */
    public function getProviderReference()
    {
        return $this->providerReference;
    }

    /**
     * Set providerData.
     *
     * @param array $providerData
     * @return Media
     */
    public function setProviderData($providerData)
    {
        $this->providerData = $providerData;

        return $this;
    }

    /**
     * Returns providerData.
     *
     * @return array
     */
    public function getProviderData()
    {
        return $this->providerData;
    }

    /**
     * Set extension.
     *
     * @param string $extension
     * @return Media
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Returns extension.
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @return mixed
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param mixed $metadata
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

//    /**
//     * Set metadata.
//     *
//     * @param array|string $key
//     * @param mixed        $value
//     * @return Media
//     */
//    public function setMetadata($key, $value)
//    {
//        $this->metadata[$key] = $value;
//
//        return $this;
//    }
//
//    /**
//     * Returns metadata.
//     *
//     * @param string|null $key
//     * @return mixed|null
//     */
//    public function getMetadata($key = null)
//    {
//        if (null === $key) {
//            return $this->metadata;
//        }
//
//        return $this->hasMetadata($key) ? $this->metadata[$key] : null;
//    }
//
//    /**
//     * Returns weather metadata has a key
//     *
//     * @param string $key
//     * @return mixed
//     */
//    public function hasMetadata($key)
//    {
//        return isset($this->metadata[$key]);
//    }
}
