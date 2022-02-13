<?php
namespace Tests\Command;

use App\Command\CoffeeFeedCommand;
use App\Service\GoogleSpreadsheetService;
use App\Service\XmlService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;
use Psr\Log\LoggerInterface;

class CoffeeFeedCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private Command $command;

    protected function setUp(): void
    {
        $xmlService = $this->createMock(XmlService::class);
        $googleSpreadsheetService = $this->createMock(GoogleSpreadsheetService::class);
        $logger = $this->createMock(LoggerInterface::class);

        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new CoffeeFeedCommand($googleSpreadsheetService, $xmlService, $logger));

        $this->command = $application->find('app:coffee-feed');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            'filePath' => 'path to file',
            'type' => 'offline'
        ));

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Started fetching data', $output);
        $this->assertStringContainsString('Started uploading data to ', $output);
        $this->assertStringContainsString('Data uploaded', $output);
    }
}