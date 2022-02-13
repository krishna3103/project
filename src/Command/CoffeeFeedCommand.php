<?php
namespace App\Command;

use App\Service\GoogleSpreadsheetService;
use App\Service\XmlService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;

class CoffeeFeedCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:coffee-feed';
    private GoogleSpreadsheetService $googleSpreadsheetService;
    private XmlService $xmlService;
    private LoggerInterface $logger;

    public function __construct(GoogleSpreadsheetService $googleSpreadsheetService, XmlService $xmlService, LoggerInterface $logger) {
        parent::__construct(null);
        $this->googleSpreadsheetService = $googleSpreadsheetService;
        $this->xmlService = $xmlService;
        $this->logger = $logger;
        $this->googleSpreadsheetService->setSpreadsheetId('18uX5tiqTxSdugj6LU14riUqx3mDrTxjtvSfSi0Nuz8s');
        $this->googleSpreadsheetService->setSheetTitle('coffee-feed');
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filePath', InputArgument::REQUIRED, 'Provide the absolute XML file path. This param is required')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type is \'online\' or \'offline\'. For accessing FTP file, type must be \'online\' for others \'offline\'. This is optional by default is \'online\'.')
            ->setDescription('This command push online or offline XML file data to google sheet.')
            // the command help shown when running the command with the "--help" option
            ->setHelp('Changed the config like spreadsheet id and sheet name from constructor.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try{
            $this->xmlService->setFilePath($input->getArgument('filePath'));
            $this->xmlService->setType($input->getArgument('type'));

            $io->section('Started fetching data from XML file and converting to array.');
            $data = $this->xmlService->getXmlFileContent();

            $io->section('Started uploading data to '.$this->googleSpreadsheetService->getSheetTitle().' sheet.');
            $response = $this->googleSpreadsheetService->clearAndSaveDataInGoogleSheet($data);

            $io->success('Data uploaded to '.$this->googleSpreadsheetService->getSheetTitle().' sheet completed. Total affected rows: '.$response);
            return Command::SUCCESS;
        }  catch (\Exception $exception) {

            $io->error(sprintf('%s | Line: %u', $exception->getMessage(), $exception->getLine()));
            $error = json_decode($exception->getMessage(), true);
            if (is_array($error) && array_key_exists("error", $error)) {
                $this->logger->log('error', $error['error']['message']);
            }
            return Command::FAILURE;
        }
    }
}