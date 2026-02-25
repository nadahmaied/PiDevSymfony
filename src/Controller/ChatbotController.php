<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $groqApiKey
    ) {
    }

    #[Route('/chatbot', name: 'chatbot')]
    public function index(): Response
    {
        return $this->render('chatbot/index.html.twig');
    }

    #[Route('/chatbot/message', name: 'chatbot_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        try {
            error_log('Chatbot request: ' . $userMessage);
            error_log('Groq API Key present: ' . (!empty($this->groqApiKey) ? 'YES' : 'NO'));
            
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant médical virtuel pour VitalTech, une plateforme de santé. Tu aides les patients et médecins avec des questions générales sur la santé, la plateforme, et les rendez-vous. Sois professionnel, empathique et précis. Si une question nécessite un avis médical professionnel, recommande de consulter un médecin.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $userMessage
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            error_log('Groq API Status: ' . $statusCode);
            
            $responseData = $response->toArray(false);
            error_log('Groq Response: ' . json_encode($responseData));

            if (isset($responseData['choices'][0]['message']['content'])) {
                return new JsonResponse([
                    'response' => $responseData['choices'][0]['message']['content']
                ]);
            }

            if (isset($responseData['error'])) {
                error_log('Groq API Error: ' . json_encode($responseData['error']));
                return new JsonResponse(['error' => 'Erreur API: ' . $responseData['error']['message']], 500);
            }

            return new JsonResponse(['error' => 'Pas de réponse du chatbot'], 500);
        } catch (\Exception $e) {
            error_log('Chatbot exception: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            return new JsonResponse(['error' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}
