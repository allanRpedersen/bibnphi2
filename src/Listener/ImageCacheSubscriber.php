<?php
namespace App\Listener;

use App\Entity\Book;
use App\Entity\Author;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ImageCacheSubscriber implements EventSubscriber {

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var UploaderHelper
     */
    private $uploaderHelper;


    public function __construct( CacheManager $cacheManager, UploaderHelper $uploaderHelper ){

        $this->cacheManager = $cacheManager;
        $this->uploaderHelper = $uploaderHelper;

    }

    public function getSubscribedEvents(){
        return [
            'preRemove',
            'preUpdate'
        ];
    }

    public function preRemove(LifecycleEventArgs $args){

        $entity = $args->getEntity();

        dump('preRemove', $entity);

        if ($entity instanceof Author){
            $this->cacheManager->remove($this->uploaderHelper->asset($entity, 'pictureFile'));
        }

        if ($entity instanceof Book){
            $this->cacheManager->remove($this->uploaderHelper->asset($entity, 'fpImageFile'));
        }

    }

    public function preUpdate(LifecycleEventArgs $args){

        $entity = $args->getEntity();

        if ( !$entity instanceof Author and
             !$entity instanceof Book   ){ return; }

        // dump('preUpdate', $entity);

        if ($entity instanceof Author){
            if ($entity->getPictureFile() instanceof UploadedFile){
                $this->cacheManager->remove($this->uploaderHelper->asset($entity, 'pictureFile'));
            }

        }
        else
            if ($entity instanceof Book){
                if ($entity->getFpImageFile() instanceof UploadedFile){
                    $this->cacheManager->remove($this->uploaderHelper->asset($entity, 'fpImageFile'));
                }

            }


    }
}