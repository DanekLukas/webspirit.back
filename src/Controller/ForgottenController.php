<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use App\Repository\RefreshTokensRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ForgottenController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private RefreshTokensRepository $refreshTokensRepository,
    )
    {
    }

    public function sendEmail(String $email, String $refreshToken)
    {
        $link = 'https://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].'/?q='.$refreshToken;
        $email = (new Email())
            ->from('admin@danek-family.cz')
            ->to($email)
            ->priority(Email::PRIORITY_HIGH)
            ->subject('Registrace na webspirit')
            ->text("Na odkazu $link si změňte heslo.")
            ->html('<p>Na <a href="'.$link.'">tomto odkzu</a> si změňte heslo.</p>');

        $this->mailer->send($email);
    }

    function sendResponse(String $text) {
        $response = new Response('Content', Response::HTTP_OK, ['content-type' => 'text/html']);
        $contents = json_encode(['text' => $text]);
        $response->setContent($contents);
        return $response;
    }
    
    function main(Request $request)
    {
        $request = $request->getPayload();
        $email = $request->get('email');
        $user = $this->userRepository->findOneBy(['email' => $email, 'delete_date' => null]);

        if(!$user)
            return($this->sendResponse("Uživatel s adresou $email není v databázi."));

        $rt = bin2hex(random_bytes(64));
        $this->refreshTokensRepository->insertRefreshToken($rt, $user);
            $this->sendEmail($email, $rt);
        return($this->sendResponse("Na email $email byl odeslán odkaz pro změnu hesla."));
    }
}
