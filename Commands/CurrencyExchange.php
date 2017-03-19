<?php

/**
 * This command creates a mercadopago test user, useful when you need to generate 
 * fake payments.
 */

namespace StangeMercadoPago\Command{

	use \Shopware\Commands\ShopwareCommand;
	use \Symfony\Component\Console\Input\InputArgument;
	use \Symfony\Component\Console\Input\InputInterface;
	use \Symfony\Component\Console\Output\OutputInterface;
	use \Symfony\Component\Console\Formatter\OutputFormatterStyle;

	class CurrencyExchange extends ShopwareCommand{

		 /**
		  * {@inheritdoc}
		  */
		 protected function configure(){

			  $this->setName('stange:mercadopago:currency:exchange')
					->setDescription('Checks the currency exchange rate from one currency to another.')
					->addArgument(
						 'from',
						 InputArgument::REQUIRED,
						 'From currency (ISO4217 Alpha 3 currency code, example: EUR'
					)
					->addArgument(
						 'to',
						 InputArgument::REQUIRED,
						 'To currency (ISO4217 Alpha 3 currency code, example: ARS'
					)
					->addArgument(
						 'amount',
						 InputArgument::OPTIONAL,
						 'Amount to convert (default: 1)'
					)
					->setDescription('Checks the currency exchange rate from one currency to another.');

		 }

		 /**
		  * {@inheritdoc}
		  */
		protected function execute(InputInterface $input, OutputInterface $output){

			$from		=	$input->getArgument('from');
			$to		=	$input->getArgument('to');
			$amount	=	$input->getArgument('amount',1);
			$amount	=	$amount	?	$amount	:	1;

			$rate	=	$this->getContainer()
			->get('basic_checkout')
			->calculateRate($from,$to);

			$output->writeln(sprintf('%s %s to %s = %s',$amount,$from,$to,$rate*$amount));

		}

	}

}
