<?php

namespace App\Repository;

use App\Entity\Text;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use App\Repository\CategoryRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Text>
 */
class TextRepository extends ServiceEntityRepository
{
    const path = __DIR__ . '/../../public/img/';

    /**
     * @param Text[] | null $arr 
     */
    private function changeLang(array | null $arr, ?string $lang = null): array | null
    {
        if (is_null($lang)) $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2) === 'en' ? 'en' : 'cs';
        if ($lang === 'cs' || is_null($arr)) return $arr;
        foreach ($arr as $k => $l) {
            $arr[$k]->setTitle($l->getTitleEn() ?? '');
            $arr[$k]->setText($l->getTextEn() ?? '');
        }
        return $arr;
    }

    public function __construct(
        ManagerRegistry $registry,
        private CategoryRepository $categoryRepository,
        private UserRepository $userRepository,
        private Security $security,
    ) {
        parent::__construct($registry, Text::class);
    }
    public function getText(String $id): Text | null
    {
        // return $this->security->getUser();
        return $this->findOneBy(['id' => $id]);
    }

    public function getTexts(String | null $id, ?String $lang = null): array | null
    {
        $this->getSiteMap();
        // return $this->security->getUser();
        if ($id === null) {
            return $this
                ->changeLang(
                    $this->getEntityManager()
                        ->createQuery("select t from App\Entity\Text t where t.delete_date is null order by t.create_date desc")
                        ->setMaxResults(20)
                        ->getResult(),
                    $lang
                );
        }
        $category = $this->categoryRepository->findOneBy(['id' => $id]);
        if (!$category) return [];

        return $this
            ->changeLang(
                $this->getEntityManager()
                    ->createQuery("select t from App\Entity\Text t where t.delete_date is null and t.category = :category")
                    ->setParameter('category', $category)
                    ->getResult(),
                $lang
            );
    }

    public function getFilteredTexts(String $category, ?String $lang = null): array | null
    {
        $texts = $this->getEntityManager()->createQuery("select t from App\Entity\Text t where t.delete_date is null order by t.create_date desc")->setFirstResult(20)
            ->getResult();

        $res = [];

        foreach ($texts as $text) {
            if ($text->getCategory()->getName() === $category || $text->getCategory()->getNameEn() === $category)
                $res[] = $text;
        }

        return $this
            ->changeLang(
                count($res) === 0 ? null : $res,
                $lang
            );
    }

    public function getEditTexts(): array | null
    {
        return $this->getEntityManager()->createQuery("select t from App\Entity\Text t where t.delete_date is null and t.created_by = :me order by t.create_date desc")->setParameter('me', $this->security->getUser())
            ->getResult();
    }

    private static function writeUrl($file, $url)
    {
        fwrite($file, '<url>' . PHP_EOL);
        fwrite($file, "<loc>$url</loc>" . PHP_EOL);
        fwrite($file, '<lastmod>' . date('c') . '</lastmod>' . PHP_EOL);
        fwrite($file, '<changefreq>weekly</changefreq>' . PHP_EOL);
        fwrite($file, '<priority>1.0</priority>' . PHP_EOL);
        fwrite($file, '</url>' . PHP_EOL);
    }

    public function getSiteMap()
    {

        $filename = __DIR__ . '/../../public/sitemap.xml';

        $file = fopen($filename, "w");

        if ($file) {
            fwrite($file, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<?xml-stylesheet type="text/xsl" href="http://iprodev.github.io/PHP-XML-Sitemap-Generator/xml-sitemap.xsl"?>' . PHP_EOL . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL . 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' . PHP_EOL . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . PHP_EOL . 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . PHP_EOL . 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . PHP_EOL);
            TextRepository::writeUrl($file, 'https://webspirit.danek-family.cz');
            TextRepository::writeUrl($file, 'https://webspirit.danek-family.cz/');
            $categories = $this->categoryRepository->getFilteredCategories();
            if (is_array($categories))
                foreach ($categories as $category) {
                    TextRepository::writeUrl($file, 'https://webspirit.danek-family.cz/archiv/' . urlencode($category->getName()));
                    TextRepository::writeUrl($file, 'https://webspirit.danek-family.cz/archiv/' . urlencode($category->getNameEn()));
                }
            fwrite($file, '</urlset>');
        }
    }

    public function insertText(String $title, String $inText, String $author, String $source, String $category_id, int $timeZone): Text | null
    {
        $category = $this->categoryRepository->findOneBy(['id' => $category_id]);
        if (!$category) return null;
        $entityManager = $this->getEntityManager();
        $text = new Text();
        $text->setId(Uuid::v4()->toString());
        $text->setCreateDate((new \DateTime())->modify("$timeZone hour"));
        $text->setCreatedBy($this->security->getUser());
        $text->setTitle($title);
        $text->setText($inText);
        $text->setAuthor($author);
        $text->setSource($source);
        $text->setCategory($category);
        $entityManager->persist($text);
        $entityManager->flush();
        return $text;
    }

    public function updateText(String $id, String $title, String $title_en, String $inText, String $inText_en, String $author, String $source, String $category_id, int $timeZone): Text | null
    {
        $text  = $this->find($id);
        if (!$text || $text->getDeleteDate() !== null || $text->getCreatedBy() !== $this->security->getUser()) return null;
        $category = $this->categoryRepository->findOneBy(['id' => $category_id]);
        if (!$category) return null;
        $entityManager = $this->getEntityManager();
        $text->setLastUpdate((new \DateTime())->modify("$timeZone hour"));
        $text->setUpdatedBy($this->security->getUser());
        $text->setTitle($title);
        $text->setTitleEn($title_en);
        $text->setText($inText);
        $text->setTextEn($inText_en);
        $text->setAuthor($author);
        $text->setSource($source);
        $text->setCategory($category);
        $entityManager->persist($text);
        $entityManager->flush();
        return $text;
    }
    
    private function deepl (String $text): String
    {
        $url = 'https://api-free.deepl.com/v2/translate';

        $data = [
            'auth_key' => $_ENV['DEEPL'],
            'text' => $text,
            'target_lang' => 'EN',
            'preserve_formatting' => 1,
        ];

        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        // Execute request
        $response = curl_exec($ch);
        curl_close($ch);

        // Decode response
        $result = json_decode($response, true);

        // Output translation
        return $result['translations'][0]['text'];        
    }

    public function translate(String $id): Text | null
    {
        $text = $this->find($id);
        if (!$text || $text->getDeleteDate() !== null || $text->getCreatedBy() !== $this->security->getUser()) return null;
        $entityManager = $this->getEntityManager();
        $text->setTextEn($this->deepl($text->getText()));
        $text->setTitleEn($this->deepl($text->getTitle()));
        $entityManager->persist($text);
        $entityManager->flush();
        return $text;
    }

    private static function removeExt(string $name): string
    {
        return substr($name, 0, strrpos($name, '.'));
    }

    //    /**
    //     * @return Text[] Returns an array of Text objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Text
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
