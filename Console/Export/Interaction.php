<?php

/**
 * Amazon AWS Personalize integration (https://docs.aws.amazon.com/personalize/index.html)
 *
 * Use AWS Personalize to generate recommendations
 *
 * @package     ImaginationMedia\AwsPersonalize
 * @author      Igor Ludgero Miura <igor@imaginationmedia.com>
 * @copyright   Copyright (c) 2019 - 2020 Imagination Media (https://www.imaginationmedia.com/)
 * @license     https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

declare(strict_types=1);

namespace ImaginationMedia\AwsPersonalize\Console\Export;

use ImaginationMedia\AwsPersonalize\Model\Export\Interaction as ExportModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Interaction extends Command
{
    /**
     * @var ExportModel
     */
    protected $exportModel;

    /**
     * Customer constructor.
     * @param ExportModel $exportModel
     * @param string|null $name
     */
    public function __construct(
        ExportModel $exportModel,
        string $name = null
    ) {
        parent::__construct($name);
        $this->exportModel = $exportModel;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('aws:personalize:export:interaction');
        $this->setDescription('Export customer and products interaction to the AWS Personalize dataset.');
        parent::configure();
    }

    /**
     * Execute command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Starting the export process...");

        try {
            $output->writeln("Preparing interaction data to be exported...");
            $data = $this->exportModel->prepareData();
            $output->writeln("Exporting to the AWS dataset...");
            $this->exportModel->exportToAws($data);
            $output->writeln("Finished.");
        } catch (\Exception $ex) {
            $output->writeln("Process aborted. Error: " . $ex->getMessage());
        }
    }
}
