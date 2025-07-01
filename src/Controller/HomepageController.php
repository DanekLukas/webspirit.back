<?php   

namespace App\Controller;

use App\Entity\Category;
use App\Repository\RefreshTokensRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\TextRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CommentRepository;
use Twig\Extra\Markdown\DefaultMarkdown;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use App\Repository\UserRepository;
use Twig\Environment;
use Symfony\Component\Routing\Attribute\Route;

class HomepageController extends AbstractController
{

  public function __construct(
    private TextRepository $textRepository,
    private RefreshTokensRepository $refreshTokenRepository,
    private CommentRepository $commentRepository,
    private CategoryRepository $categoryRepository,
    private Environment $twig,
    private UserRepository $userRepository,
    )
  {
    $twig->addRuntimeLoader(new class implements RuntimeLoaderInterface {
      public function load($class) {
          if (MarkdownRuntime::class === $class) {      
              return new MarkdownRuntime(new DefaultMarkdown());
          }
      }
  });
}

  static function convertToDateTime($dateTime) {
    if(!$dateTime) return false;
    return $dateTime->format("j. n. Y G:i");
  }

  function processTexts(array $texts, string $q, array $categoriesFiltered): Response {
    $content = [];
    foreach($texts as $text) {
      array_push(
        $content,
    [ 'id' => $text->getId(),
      'title' => $text->getTitle(),
      'text' => $text->getText(),
      'author' => $text->getAuthor(),
      'source' => $text->getSource(),
      'category' => $text->getCategory(),
      'createdBy' => $text->getCreatedBy()->getFirstName()." ".$text->getCreatedBy()->getLastName(),
      'created' => HomepageController::convertToDateTime($text->getCreateDate()),
      'updatedBy' => $text->getUpdatedBy() ? $text->getUpdatedBy()->getFirstName()." ".$text->getUpdatedBy()->getLastName() : '',
      'updated' => HomepageController::convertToDateTime($text->getLastUpdate()),
      'comments' => array_map(function ($comment) {return ['comment' => $comment->getComment(), 'by' => $comment->getCreatedBy()->getFirstName()." ".$comment->getCreatedBy()->getLastName(), 'at' => HomepageController::convertToDateTime($comment->getCreateDate())];},$this->commentRepository->getComments($text))
    ]);
    }
    // $this->redirectToRoute('HomepageController');
    $contents = $this->renderView('/layout.html.twig',
    ['contents' => $content, 'query' => $q, 'categoriesFiltered' => $categoriesFiltered ]);
    $response = new Response('Content', Response::HTTP_OK, ['content-type' => 'text/html', 'Location' => '/' ]);
    $response->setContent($contents);
    return $response;
  }

  function getCategoriesFiltered (): array {
    return ($cf = $this->categoryRepository->getFilteredCategories()) === null ? [] :
    array_map(function (Category $category): array {
    return [$category->getId(), $category->getName(), $category->getNameEn()];
    }, $cf);
  }

  #[Route('/', name: 'HomepageController')]
  function main(Request $request): Response
  {
    $categoriesFiltered = $this->getCategoriesFiltered();
    $q = $request->query->get('q');
    if ( gettype($q) !== 'string'
      || strlen($q) !== 128
      || (($rt = $this->refreshTokenRepository->findOneBy(["refreshToken" => $q])) === null)
      || ($this->userRepository->findOneBy(['name' => strtolower($rt->getUserName()), 'delete_date' => null/*, 'active' => 0*/]) === null)) $q = null;
    if($q === null) $q = bin2hex(random_bytes(64));
    $texts = $this->textRepository->getTexts(null);
    return $this->processTexts($texts, $q, $categoriesFiltered);
  }

  #[Route('/archiv', name: 'ArchiveController')]
  function archive(): Response
  {
    $categoriesFiltered = $this->getCategoriesFiltered();
    $texts = [];
    $q = bin2hex(random_bytes(64));
    return $this->processTexts($texts, $q, $categoriesFiltered);
  }

  #[Route('/archiv/{category}', name: 'CategoriesController')]
  function category(string $category): Response
  {
    $category = urldecode($category);
    $categoriesFiltered = $this->getCategoriesFiltered();
    $texts = $categoriesFiltered === null || !in_array($category, array_map(function($i){return $i[1];},$categoriesFiltered)) ? $this->textRepository->getTexts(null) : $this->textRepository->getFilteredTexts($category);
    if(is_null($texts)) {
      header("Location: /archiv");
      exit;

    }
    $q = bin2hex(random_bytes(64));
    return $this->processTexts($texts, $q, $categoriesFiltered);
  }

  #[Route('/{id}', name: 'IdController')]
  function id(string $id): void
  {
    $texts = $this->textRepository->getTexts(null);
    $c = count($texts);
    $i = 0;
    while($i < $c && $texts[$i]->getId() !== $id) $i++;
    if($c>$i) {
      header('Location: https://'.$_SERVER['SERVER_NAME'].'/#'.$id);
      exit;
    }
    $text = $this->textRepository->getText($id);
    if(is_null($text)) {
      header('Location: https://'.$_SERVER['SERVER_NAME']);
      exit;
    }    
    header('Location: https://'.$_SERVER['SERVER_NAME'].'/archiv/'.$text->getCategory()->getName().'#'.$id);
    exit;
  // $q = bin2hex(random_bytes(64));
    // return $this->processTexts([$text], $q, $categoriesFiltered);
  } 
}
  