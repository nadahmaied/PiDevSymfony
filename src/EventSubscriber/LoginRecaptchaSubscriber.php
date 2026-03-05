<?php

namespace App\EventSubscriber;

use App\Service\RecaptchaVerifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class LoginRecaptchaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RecaptchaVerifier $recaptchaVerifier
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => 'onCheckPassport',
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        if ($request->getPathInfo() !== '/login' || !$request->isMethod('POST')) {
            return;
        }

        $token = $request->request->get('g-recaptcha-response');
        $tokenString = is_string($token) ? $token : null;

        if (!$this->recaptchaVerifier->verify($tokenString, $request->getClientIp())) {
            throw new CustomUserMessageAuthenticationException('Veuillez valider le captcha pour continuer.');
        }
    }
}
