<?php

namespace App\Rest;

use App\Document\Menu;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;
use Symfony\Component\Filesystem\Filesystem as FSys;

class RestMenuRequest extends RestBaseRequest
{
    public function __construct(MongoEM $em)
    {
        parent::__construct($em);

        $this->primaryIdentifier = 'mlid';
        $this->requiredFields = [
            $this->primaryIdentifier,
            'agency',
        ];
    }

    protected function exists($id, $agency)
    {
        $entity = $this->get($id, $agency);

        return !is_null($entity);
    }

    protected function get($id, $agency)
    {
        $criteria = [
            $this->primaryIdentifier => (int)$id,
            'agency' => $agency,
        ];

        $entity = $this->em
            ->getRepository('App:Menu')
            ->findOneBy($criteria);

        return $entity;
    }

    protected function insert()
    {
        $entity = $this->prepare(new Menu());

        $dm = $this->em->getManager();
        $dm->persist($entity);
        $dm->flush();

        return $entity;
    }

    protected function update($id, $agency)
    {
        $loadedEntity = $this->get($id, $agency);
        $updatedEntity = $this->prepare($loadedEntity);

        $dm = $this->em->getManager();
        $dm->flush();

        return $updatedEntity;
    }

    protected function delete($id, $agency)
    {
        $entity = $this->get($id, $agency);

        $dm = $this->em->getManager();
        $dm->remove($entity);
        $dm->flush();

        return $entity;
    }

    /**
     * Prepares the menu entity structure.
     *
     * @param Menu $menu
     *
     * @return Menu
     */
    public function prepare(Menu $menu)
    {
        $body = $this->getParsedBody();

        $mlid = !empty($body[$this->primaryIdentifier]) ? $body[$this->primaryIdentifier] : 0;
        $menu->setMlid($mlid);

        $agency = !empty($body['agency']) ? $body['agency'] : '000000';
        $menu->setAgency($agency);

        $type = !empty($body['type']) ? $body['type'] : 'undefined';
        $menu->setType($type);

        $name = !empty($body['name']) ? $body['name'] : 'Undefined';
        $menu->setName($name);

        $url = !empty($body['url']) ? $body['url'] : '';
        $menu->setUrl($url);

        $order = !empty($body['order']) ? $body['order'] : 0;
        $menu->setOrder($order);

        $enabled = !empty($body['enabled']) ? (bool)$body['enabled'] : false;
        $menu->setEnabled($enabled);

        $items = !empty($body['items']) && is_array($body['items']) ? $body['items'] : [];
        $menu->setItems($items);

        $plid = !empty($body['plid']) ? $body['plid'] : 0;
        $menu->setPlid($plid);

        $withImage = !empty($body['with_image']) ? (bool) $body['with_image'] : false;
        $menu->setWithImage($withImage);

        // TODO: Validate the structure.
        $menu->setImage(
            $this->parseImageField(!empty($body['image']) ? $body['image'] : '')
        );

        return $menu;
    }

    /**
     * Fetched menu entries.
     *
     * @param string $agency  Agency identifier.
     * @param int $amount     Number of entries to fetch.
     * @param int $skip       Number of entries to skip.
     * @param bool $countOnly Fetch only number of entries.
     *
     * @return Menu[]
     */
    public function fetchMenus($agency, $amount = 10, $skip = 0, $countOnly = false)
    {
        $qb = $this->em
            ->getManager()
            ->createQueryBuilder(Menu::class);

        $qb->field('agency')->equals($agency);

        if ($countOnly) {
            $qb->count();
        } else {
            $qb->skip($skip)->limit($amount);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Takes base64 image data from content fields and creates a physical file.
     *
     * @param array $imageField
     *
     * @return array
     */
    private function parseImageField(array $imageField): array
    {
        $imageBase64Contents = $imageField['value'];
        $imageField['value'] = null;

        $extensionMatch = preg_match('/^image\/(jpg|jpeg|gif|png)$/', $imageField['attr']);
        if (empty($imageBase64Contents) || empty($imageField['attr']) || !$extensionMatch) {
            return $imageField;
        }

        $imageFileExtension = explode('/', $imageField['attr']);
        if (empty($imageFileExtension[1])) {
            return $imageField;
        }

        $extension = $imageFileExtension[1];

        $fileSystem = new FSys();

        if (!is_writable(RestContentRequest::IMAGE_UPLOADS_PATH)) {
            // TODO: Maybe log something in that case.
            return $imageField;
        }

        $finalImageDirectory = RestContentRequest::IMAGE_UPLOADS_PATH.$this->agencyId;
        if (!$fileSystem->exists($finalImageDirectory)) {
            $fileSystem->mkdir($finalImageDirectory);
        }

        $fileName = sha1($imageBase64Contents.$this->agencyId).'.'.$extension;
        $finalImagePath = $finalImageDirectory.'/'.$fileName;
        $fileSystem->dumpFile($finalImagePath, base64_decode($imageBase64Contents));

        if ($fileSystem->exists($finalImagePath)) {
            // Simple check whether resulting file is an image.
            // If not, remove the upload immediately.
            if (function_exists('getimagesize') && getimagesize($finalImagePath)) {
                $imageField['value'] = 'files/'.$this->agencyId.'/'.$fileName;
            } else {
                $fileSystem->remove($finalImagePath);
            }
        }

        return $imageField;
    }
}
