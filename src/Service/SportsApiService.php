<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;

class SportsApiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;
    private string $baseUrl = 'https://api.football-data.org/v4/';

    public function __construct(
        HttpClientInterface $httpClient,
        string $footballDataApiKey,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $footballDataApiKey;
        $this->logger = $logger;
    }

    /**
     * Récupère le prochain match programmé pour une équipe donnée
     *
     * @param int $teamId L'ID de l'équipe dans football-data.org
     * @return array|null Tableau avec les infos du match ou null si erreur/pas de match
     */
    public function getNextMatch(int $teamId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . "teams/{$teamId}/matches", [
                'query' => [
                    'status' => 'SCHEDULED',
                    'limit'  => 1,
                ],
                'headers' => [
                    'X-Auth-Token' => $this->apiKey,
                    'Accept'       => 'application/json',
                ],
            ]);

            $data = $response->toArray();

            if (empty($data['matches'])) {
                return null;
            }

            $match = $data['matches'][0];

//             return [
//     'match_id'     => $match['id'] ?? null,
//     'home'         => $match['homeTeam']['shortName'] ?? $match['homeTeam']['name'] ?? 'Inconnu',   // ← change ici
//     'away'         => $match['awayTeam']['shortName'] ?? $match['awayTeam']['name'] ?? 'Inconnu',   // ← change ici
//     'date'         => (new \DateTime($match['utcDate']))->setTimezone(new \DateTimeZone('Africa/Tunis'))->format('d M Y H:i'),
//     'competition'  => $match['competition']['name'] ?? 'Inconnue',
//     'status'       => $match['status'] ?? 'SCHEDULED',
//     'venue'        => $match['venue'] ?? null,
// ];
return [
    'match_id'     => $match['id'] ?? null,
    'home_team'    => $match['homeTeam']['shortName'] ?? $match['homeTeam']['name'] ?? 'Inconnu',
    'home_team_id' => $match['homeTeam']['id'] ?? null,          // ← AJOUT
    'away_team'    => $match['awayTeam']['shortName'] ?? $match['awayTeam']['name'] ?? 'Inconnu',
    'away_team_id' => $match['awayTeam']['id'] ?? null,          // ← AJOUT
    'date'         => (new \DateTime($match['utcDate']))->setTimezone(new \DateTimeZone('Africa/Tunis'))->format('d M Y H:i'),
    'competition'  => $match['competition']['name'] ?? 'Inconnue',
    'status'       => $match['status'] ?? 'SCHEDULED',
    'venue'        => $match['venue'] ?? null,
];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du prochain match pour team ' . $teamId, [
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Méthode générique pour appeler n'importe quel endpoint de l'API
     * (utile si tu veux ajouter d'autres fonctionnalités plus tard)
     */
    public function request(string $endpoint, array $query = []): ?array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . $endpoint, [
                'query' => $query,
                'headers' => [
                    'X-Auth-Token' => $this->apiKey,
                    'Accept'       => 'application/json',
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Erreur API football-data.org', [
                'endpoint'  => $endpoint,
                'query'     => $query,
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Récupère le nom d'une équipe (utile lors de l'enregistrement du favori)
     */
    public function getTeamName(int $teamId): ?string
    {
        $data = $this->request("teams/{$teamId}");
        return $data['name'] ?? $data['shortName'] ?? null;
    }
}