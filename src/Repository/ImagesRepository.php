<?php

namespace App\Repository;

use App\Entity\Text;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @extends ServiceEntityRepository<Text>
 */
class ImagesRepository
{
    const path = __DIR__.'/../../public/img/';

    public function __construct()
    {

    }

    private static function removeExt(string $name): string
    {
        return substr($name,0,strrpos($name,'.'));
    }

    public function imageUpload(UploadedFile $uploadedFile): ?String {
        $id = '';
        $name = self::removeExt($uploadedFile->getClientOriginalName()).'.'.explode('/',$uploadedFile->getMimeType(),2)[1];
        try {
            $uploadedFile->move(self::path, $id.$name);
        } catch (\Error $e) {
            return $e->getMessage();
        }
        return $name;
    }

    public static function getImages() {
        return scandir(self::path);
    }

}
