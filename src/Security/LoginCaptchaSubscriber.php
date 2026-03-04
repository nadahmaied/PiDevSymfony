<?php

namespace App\Security;

use App\Service\RecaptchaVerifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class LoginCaptchaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RecaptchaVerifier $recaptchaVerifier,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run before authentication listeners on login POST requests.
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->isMethod('POST') || $request->getPathInfo() !== '/login') {
            return;
        }

        $token = $request->request->get('g-recaptcha-response');
        $ip = $request->getClientIp();

        if ($this->recaptchaVerifier->verify(is_string($token) ? $token : null, $ip)) {
            return;
        }

        if ($request->hasSession()) {
            $session = $request->getSession();
            $lastUsername = (string) $request->request->get('_username', '');
            $session->set(SecurityRequestAttributes::LAST_USERNAME, $lastUsername);
            $session->getFlashBag()->add('error', 'Vérification reCAPTCHA invalide. Veuillez réessayer.');
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('front_login')));
    }
}
