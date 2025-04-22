<?php
namespace App\GraphQL;

// use App\Service\UserMutationService;

use ArrayObject;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Resolver\ResolverMap;

use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use App\Repository\TextRepository;
use App\Repository\CommentRepository;
use App\Repository\ImagesRepository;

class LocalResolverMap extends ResolverMap 
{
    public function __construct(
        private UserRepository $userRepository,
        private CategoryRepository $categoryRepository,
        private TextRepository $textRepository,
        private CommentRepository $commentRepository,
        private ImagesRepository $imagesRepository
    ) {}

    /**
     * @inheritDoc
     */
    protected function map(): array 
    {
        return [
            'Query' => [        
                'Categories' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->categoryRepository->getCategories();},
                'Texts' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->textRepository->getTexts($args['id']);},
                'FilteredCategories' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->categoryRepository->getFilteredCategories();},
                'FilteredTexts' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->textRepository->getFilteredTexts($args['category']);},
            ],
            'QueryCommon' => [        
                'Categories' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->categoryRepository->getCategories();},
                'Texts' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->textRepository->getTexts($args['id']);},
                'Own' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->userRepository->getOwn();},
                'FilteredCategories' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->categoryRepository->getFilteredCategories();},
                'FilteredTexts' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->textRepository->getFilteredTexts($args['category']);},
            ],
            'MutationCommon' => [
                'InsertComment' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->commentRepository->insertComment($args['id'], $args['text'], $args['timeZone']);},
                'ActivateAccount' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->userRepository->activateAccount($args['first_name'], $args['last_name'], $args['age'], $args['password'], $args['timeZone']);},
                'ChangePassword' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->userRepository->changePassword($args['password'], $args['timeZone']);},
            ],
            'QueryAdmin' => [        
                'User' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->userRepository->getUser($args['id']);},
                'Category' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->categoryRepository->getCategory($args['id']);},
                'Categories' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->categoryRepository->getCategories();},
                'Text' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->textRepository->getText($args['id']);},
                'Texts' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->textRepository->getTexts($args['id']);},
                'Own' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->userRepository->getOwn();},
                'Images' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->imagesRepository::getImages();},
                'FilteredCategories' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->categoryRepository->getFilteredCategories();},
                'FilteredTexts' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->textRepository->getFilteredTexts($args['category']);},
                'EditTexts' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->textRepository->getEditTexts();},
                
            ],
            'MutationAdmin' => [
                'InsertUser' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->userRepository->insertUser($args['name'], $args['password'], $args['role'], $args['timeZone']);},
                'UpdateUser' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->userRepository->updateUser($args['id'], $args['name'], $args['password'], $args['role'], $args['timeZone']);},
                'InsertCategory' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->categoryRepository->insertCategory($args['name'], $args['timeZone']);},
                'UpdateCategory' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->categoryRepository->updateCategory($args['id'], $args['name'], $args['timeZone']);},
                'InsertText' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->textRepository->insertText($args['title'], $args['text'], $args['author'], $args['source'], $args['category'], $args['timeZone']);},
                'UpdateText' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->textRepository->updateText($args['id'], $args['title'], $args['text'], $args['author'], $args['source'], $args['category'], $args['timeZone']);},
                'InsertComment' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->commentRepository->insertComment($args['id'], $args['text'], $args['timeZone']);},
                'ActivateAccount' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->userRepository->activateAccount($args['first_name'], $args['last_name'], $args['age'], $args['password'], $args['timeZone']);},
                'ChangePassword' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->userRepository->changePassword($args['password'], $args['timeZone']);},
                'imageUpload' => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {return $this->imagesRepository->imageUpload($args['file']);},
            ]
        ];
    }
}
