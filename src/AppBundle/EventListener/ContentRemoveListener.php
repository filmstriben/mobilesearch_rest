<?php

namespace AppBundle\EventListener;

use AppBundle\Document\Content;
use AppBundle\Document\Lists;
use AppBundle\Repositories\ListsRepository;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;

/**
 * Class ContentRemoveSubscriber.
 */
class ContentRemoveListener {

    /**
     * Fires when a document is deleted.
     *
     * Here we catch a content entity being removed, to update related lists.
     * Maintains consistency between list items and actual item existence.
     *
     * @param LifecycleEventArgs $eventArgs
     *   Fired event object.
     *
     * TODO: Tests coverage.
     */
    public function preRemove(LifecycleEventArgs $eventArgs) {
        $document = $eventArgs->getDocument();
        if (!$document instanceof Content) {
            return;
        }

        $dm = $eventArgs->getDocumentManager();
        /** @var ListsRepository $repository */
        $repository = $dm->getRepository(Lists::class);
        $attachedLists = $repository->findAttached($document);
        // Loop the attached lists and exclude the deleted node from
        // list items.
        foreach ($attachedLists as $list) {
            $listNodes = array_flip($list->getNids());
            if (array_key_exists($document->getNid(), $listNodes)) {
                unset($listNodes[$document->getNid()]);
                $listNodes = array_flip($listNodes);
                $list->setNids($listNodes);
            }
        }
        $dm->flush();
    }
}

