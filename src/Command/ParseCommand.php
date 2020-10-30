<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ParseCommand extends Command
{
    protected static $defaultName = 'app:parse';

    protected function configure()
    {
        $this->addArgument('link', InputArgument::REQUIRED, 'Product link');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new Client([
            'base_uri' => $input->getArgument('link')
        ]);
        $response = $client->request('GET');

        if ($response->getStatusCode() == 200) {
            $html = <<<HTML
            {$response->getBody()}
            HTML;

            $crawler = new Crawler($html);;

            $output->writeln('Title: ' . $crawler->filter('#productTitle')->text());

            $output->writeln('Price: ' . $crawler->filter('#priceblock_ourprice')->text());
            
            $output->writeln('Image: ' . $crawler->filter('#landingImage')->attr('data-old-hires'));

            $output->writeln('Merchant: ' . $crawler->filter('.tabular-buybox-text > a')->text());

            $output->writeln('Description: ' .
                implode($nodeValues = $crawler->filter('.a-list-item')->each(function (Crawler $node, $i) {
                    return $node->text();
                })));

            return Command::SUCCESS;
        } else {
            $output->writeln('Something went wrong');
            $output->writeln($response->getStatusCode());
            return Command::FAILURE;
        }
    }
}