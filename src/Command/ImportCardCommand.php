<?php

namespace App\Command;

use App\Entity\Card;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:card',
    description: 'Add a short description for your command',
)]
class ImportCardCommand extends Command
{
    private int $size = 0;

    public function __construct(
        private readonly CardRepository $cardRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private array $csvHeader = []
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filepath = __DIR__ . '/../../data/cards.csv';
        $handle = fopen($filepath, 'r');

        $this->logger->info('Importing cards from ' . $filepath);
        if ($handle === false) {
            $io->error('File not found');
            return Command::FAILURE;
        }

        $this->size = 0;
        $this->csvHeader = fgetcsv($handle);
        while (($row = $this->readCSV($handle)) !== false) {
            $this->size++;
            $io->writeln($this->addCard($row)->getName());

            if ($this->size > 10000) {
                break;
            }
        }

        // Effectuer un dernier flush pour s'assurer que toutes les cartes sont persistées
        $this->entityManager->flush();

        fclose($handle);
        $io->success('File found, ' . $this->size . ' lines read.');
        return Command::SUCCESS;
    }

    private function readCSV(mixed $handle): array|false
    {
        $row = fgetcsv($handle);
        if ($row === false) {
            return false;
        }
        return array_combine($this->csvHeader, $row);
    }

    private function addCard(array $row): Card
    {
        $uuid = $row['uuid'];

        $card = $this->cardRepository->findOneBy(['uuid' => $uuid]);
        if ($card === null) {
            $card = new Card();
            $card->setUuid($uuid);
            $card->setManaValue($row['manaValue']);
            $card->setManaCost($row['manaCost']);
            $card->setName($row['name']);
            $card->setRarity($row['rarity']);
            $card->setSetCode($row['setCode']);
            $card->setSubtype($row['subtypes']);
            $card->setText($row['text']);
            $card->setType($row['type']);
            $this->entityManager->persist($card);

            // Effectuer un flush tous les 1000 enregistrements
            if ($this->size % 1000 === 0) {
                $this->entityManager->flush();
            }
        }

        return $card;
    }
}