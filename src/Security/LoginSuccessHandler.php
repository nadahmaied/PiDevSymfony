<?php

namespace App\Security;

use App\Repository\MedecinRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface   $router,
        private MedecinRepository $medecinRepository,
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user  = $token->getUser();
        $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];

        // ── ROLE_ADMIN → backoffice admin ──
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('admin_user_index'));
        }

        // ── ROLE_MEDECIN → planning des consultations ──
        if (in_array('ROLE_MEDECIN', $roles, true)) {
            $medecin = $this->medecinRepository->findOneBy(['user' => $user]);

            if ($medecin) {
                return new RedirectResponse(
                    $this->router->generate('showAllRdvBack', ['medecinId' => $medecin->getId()])
                );
            }

            // Médecin sans fiche → admin par défaut
            return new RedirectResponse($this->router->generate('admin_user_index'));
        }

        // ── ROLE_PATIENT (et autres) → front patient ──
        return new RedirectResponse($this->router->generate('app_home'));
    }
}