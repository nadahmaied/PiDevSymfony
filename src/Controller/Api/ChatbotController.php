<?php

namespace App\Controller\Api;

use App\Entity\Fiche;
use App\Entity\User;
use App\Repository\FicheRepository;
use App\Repository\UserRepository;
use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/chatbot')]
class ChatbotController extends AbstractController
{
    #[Route('/ask', name: 'app_api_chatbot_ask', methods: ['POST'])]
    public function ask(Request $request, ChatbotService $chatbotService, FicheRepository $ficheRepository, UserRepository $userRepository): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        $ficheId = isset($payload['ficheId']) ? (int) $payload['ficheId'] : null;
        $patientId = isset($payload['patientId']) ? (int) $payload['patientId'] : null;
        $message = isset($payload['message']) ? trim((string) $payload['message']) : '';

        if ($message === '') {
            return $this->json([
                'success' => false,
                'message' => '',
                'error' => 'message est requis.',
            ], 400);
        }

        $patient = null;
        if ($ficheId !== null) {
            $fiche = $ficheRepository->find($ficheId);
            if ($fiche instanceof Fiche) {
                $patient = $fiche->getIdU();
            }
        }
        if ($patient === null && $patientId !== null) {
            $patient = $userRepository->find($patientId);
        }

        if (!$patient instanceof User) {
            return $this->json([
                'success' => false,
                'message' => '',
                'error' => 'Patient ou fiche introuvable.',
            ], 404);
        }

        if (!$this->canAccessPatientData($patient, $patientId)) {
            return $this->json([
                'success' => false,
                'message' => '',
                'error' => 'Accès non autorisé à ces données.',
            ], 403);
        }

        $result = $chatbotService->ask($patient, $message);

        return $this->json($result);
    }

    private function canAccessPatientData(User $patient, ?int $patientId): bool
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        if ($currentUser === $patient) {
            return true;
        }

        if ($patientId !== null && $currentUser->getId() === $patientId) {
            return true;
        }

        $roles = $currentUser->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_MEDECIN', $roles, true)) {
            return true;
        }

        return false;
    }
}
