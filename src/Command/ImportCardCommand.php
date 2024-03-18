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
use Symfony\Component\Console\Helper\ProgressIndicator;
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
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface        $logger,
        private array                           $csvHeader = []
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '2G');
        // On récupère le temps actuel
        $io = new SymfonyStyle($input, $output);
        $filepath = __DIR__ . '/../../data/cards.csv';
        $handle = fopen($filepath, 'r');

        // On récupère le temps actuel
        $start = microtime(true);

        $this->logger->info('Importing cards from ' . $filepath);
        if ($handle === false) {
            $io->error('File not found');
            return Command::FAILURE;
        }

        $i = 0;
        $this->csvHeader = fgetcsv($handle);
        $uuidInDatabase = $this->entityManager->getRepository(Card::class)->getAllUuids();

        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('Importing cards...');

        while (($row = $this->readCSV($handle)) !== false) {
            $i++;

            if (!in_array($row['uuid'], $uuidInDatabase)) {
                $this->addCard($row);
            }

            if ($i % 2000 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $progressIndicator->advance();
            }
            if ($i>10000) {
                break;
            }
        }
        // Toujours flush en sorti de boucle
        $this->entityManager->flush();
        $progressIndicator->finish('Importing cards done.');

        fclose($handle);

        // On récupère le temps actuel, et on calcule la différence avec le temps de départ
        $end = microtime(true);
        $timeElapsed = $end - $start;
        $io->success(sprintf('Imported %d cards in %.2f seconds', $i, $timeElapsed));
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

    private function addCard(array $row): void
    {
        $uuid = $row['uuid'];

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

    }
}