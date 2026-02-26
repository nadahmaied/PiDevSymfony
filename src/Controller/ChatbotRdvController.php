<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChatbotRdvController extends AbstractController
{
    private const SYSTEM_PROMPT = 'Tu es un assistant medical virtuel pour VitalTech, une plateforme de gestion de rendez-vous medicaux en Tunisie. '
        . 'Tu aides les patients a identifier la specialite medicale adaptee a leurs symptomes et les orientes vers la prise de RDV. '
        . 'Medecins disponibles : Dr. Sarah Amrani (Cardiologue), Dr. Mohamed Kallel (Ophtalmologue), Dr. Ali Zouhaier (Neurologue), Dr. Karim Ben Youssef (Generaliste). '
        . 'Regles : Tu n\'es PAS un medecin, tu ne poses PAS de diagnostic. '
        . 'Pour toute urgence (douleur thoracique, AVC) dire d\'appeler le 190. '
        . 'Reponses courtes et claires en francais (3-4 phrases max). '
        . 'Si tu suggeres un medecin, termine par "Voulez-vous prendre un RDV ?"';

    #[Route('/api/chatbot', name: 'api_chatbot', methods: ['POST'])]
    public function chatbot(Request $request, HttpClientInterface $http): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['messages'])) {
            return $this->json(['error' => 'Messages manquants'], 400);
        }

        $apiKey = $_ENV['GROQ_API_KEY'] ?? null;
        if (!$apiKey) {
            return $this->json(['error' => 'Cle GROQ_API_KEY non configuree dans .env'], 500);
        }

        // Construire les messages avec le system prompt en premier
        $messages = array_merge(
            [['role' => 'system', 'content' => self::SYSTEM_PROMPT]],
            $data['messages']
        );

        try {
            $response = $http->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.1-8b-instant', // gratuit et rapide
                    'max_tokens'  => 400,
                    'temperature' => 0.7,
                    'messages'    => $messages,
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $body = $response->getContent(false);
                return $this->json(['error' => 'Groq ' . $statusCode . ': ' . $body], 500);
            }

            $result = $response->toArray();

            // Retourner le texte de la réponse
            $text = $result['choices'][0]['message']['content'] ?? 'Desole, une erreur s\'est produite.';

            return $this->json(['text' => $text]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}