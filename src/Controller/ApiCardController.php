<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/card', name: 'api_card_')]
#[OA\Tag(name: 'Card', description: 'Routes for all about cards')]
class ApiCardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/all', name: 'List all cards', methods: ['GET'])]
    #[OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'limit', description: 'Number of cards per page', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Get(description: 'Return all cards in the database with pagination')]
    #[OA\Response(response: 200, description: 'List all cards')]
    public function cardAll(Request $request): Response
    {
        $this->logger->info('API call to list all cards');
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        $firstResult = ($page - 1) * $limit;


        $queryBuilder = $this->entityManager->getRepository(Card::class)->createQueryBuilder('c');
        $queryBuilder->setFirstResult($firstResult);
        $queryBuilder->setMaxResults($limit);
        $cards = $queryBuilder->getQuery()->getResult();

        $this->logger->info('All cards listed');
        return $this->json($cards);
    }

    #[Route('/{uuid}', name: 'Show card', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'UUID of the card', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Put(description: 'Get a card by UUID')]
    #[OA\Response(response: 200, description: 'Show card')]
    #[OA\Response(response: 404, description: 'Card not found')]
    public function cardShow(string $uuid): Response
    {
        $this->logger->info('API call to show card with UUID: ' . $uuid);
        $card = $this->entityManager->getRepository(Card::class)->findOneBy(['uuid' => $uuid]);
        if (!$card) {
            $this->logger->error('Card not found with UUID: ' . $uuid);
            return $this->json(['error' => 'Card not found'], 404);
        }
        return $this->json($card);
    }


    public function importCards(): Response
    {
        $this->logger->info('Card import started');
        $startTime = microtime(true);

        try {
            $this->entityManager->getConnection()->beginTransaction();
            $this->entityManager->getConnection()->commit();
            $this->logger->info('Card import finished');
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            $this->logger->info('Card import duration: ' . $duration . ' seconds');
        } catch (\Exception $e) {
            $this->logger->error('Error during card import: ' . $e->getMessage());
        }

        return $this->json(['status' => 'Import finished']);
    }
}