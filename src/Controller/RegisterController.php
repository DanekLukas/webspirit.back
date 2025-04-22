<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use App\Repository\RefreshTokensRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
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
            ->text("Na odkazu $link si nastavte jméno a heslo.")
            ->html('<p>Na <a href="'.$link.'">tomto odkzu</a> si nastavte jméno a heslo.</p>');

        $this->mailer->send($email);
    }

    function sendResponse(String $text) {
        $response = new Response('Content', Response::HTTP_OK, ['content-type' => 'text/html']);
        $contents = json_encode(['text' => $text]);
        $response->setContent($contents);
        return $response;
    }
    
    #[Route('/register', name: 'RegisterController')]
    function main(Request $request)
    {
        $request = $request->getPayload();
        $email = $request->get('email');
        $name = $request->get('username');
        $user = $this->userRepository->findOneBy(['name' => $name]);

        if($user !== null && $user->getDeleteDate() === null)
            return($this->sendResponse("Jméno $name je již použit."));

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if($user !== null && $user->getDeleteDate() === null)
            return($this->sendResponse("Email $email je již použit. K dispozici je zapomenuté heslo."));

        $user = $this->userRepository->insertUser($name, $email, "ROLE_USER", 0 );
        $rt = bin2hex(random_bytes(64));
        $this->refreshTokensRepository->insertRefreshToken($rt, $user);
        $this->sendEmail($email, $rt);
        return($this->sendResponse("Na email $email byl odeslán odkaz pro nastavení hesla."));
    }
}
