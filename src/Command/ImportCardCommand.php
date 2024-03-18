<?php

namespace App\Command;

use App\Entity\Artist;
use App\Entity\Card;
use App\Repository\ArtistRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
        // On récupère le temps actuel
        $io = new SymfonyStyle($input, $output);
        $filepath = __DIR__ . '/../../data/cards.csv';
        $handle = fopen($filepath, 'r');

        $this->logger->info('Importing cards from ' . $filepath);
        if ($handle === false) {
            $io->error('File not found');
            return Command::FAILURE;
        }

        $i = 0;
        $this->csvHeader = fgetcsv($handle);
        while (($row = $this->readCSV($handle)) !== false) {
            $i++;
            $io->writeln($this->addCard($row)->getName());

            // TODO: Importer toutes les cartes
            if ($i > 500) {
                break;
            }
        }

        fclose($handle);
        $io->success('File found, ' . $i . ' lines read.');
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
            $this->entityManager->flush();
        }
        return $card;
    }
}
