<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UloginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private HttpClientInterface $client,
        private UrlGeneratorInterface $router,
        private UserRepository $userRepo
    ) {
    }

    public function supports(Request $request): bool
    {
        return 'app_login' === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->request->getAlnum('token');

        if ('' === $token) {
            throw new CustomUserMessageAuthenticationException('Token missing.');
        }

        /**
         * @psalm-var array{
         *     verified_email: string,
         *     profile: string,
         *     nickname: string,
         *     identity: string,
         *     manual: string,
         *     network: string,
         *     expires_in: string,
         *     photo_big: string,
         *     uid: string
         * }
         *
         * @var array<string,string>
         */
        $response = $this->client->request(
            'GET',
            sprintf('http://ulogin.ru/token.php?token=%s&host=%s',
                $token,
                urlencode($this->router->generate('app_login', referenceType: UrlGeneratorInterface::ABSOLUTE_URL))
            )
        )->toArray();

        return new SelfValidatingPassport(new UserBadge($response['uid'], function ($googleId) use ($response) {
            $existingUser = $this->userRepo->findOneBy(['googleId' => $googleId]);

            if ($existingUser) {
                return $existingUser;
            }

            $user = new User();

            $user->setName($response['nickname'])
                ->setGoogleId($response['uid'])
                ->setPictrue($response['photo_big']);

            $this->userRepo->save($user, true);

            return $user;
        }), [
            new RememberMeBadge(),
        ]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }

//    public function start(Request $request, AuthenticationException $authException = null): Response
//    {
//        /*
//         * If you would like this class to control what happens when an anonymous user accesses a
//         * protected page (e.g. redirect to /login), uncomment this method and make this class
//         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
//         *
//         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
//         */
//    }
}
