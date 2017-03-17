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

	class CreateTestUser extends ShopwareCommand{

		 /**
		  * {@inheritdoc}
		  */
		 protected function configure(){

			  $this->setName('stange:mercadopago:create:test-user')
					->setDescription('Creates a mercadopago test user.')
					->addArgument(
						 'id',
						 InputArgument::REQUIRED,
						 'MercadoPago Client ID'
					)
					->addArgument(
						 'secret',
						 InputArgument::REQUIRED,
						 'MercadoPago Client Secret'
					)
					->addArgument(
						 'site-id',
						 InputArgument::REQUIRED,
						 'Site id: Argentina: MLA, Brasil: MLB, México: MLM, Venezuela: MLV, Chile: MLC, Perú: MPE, Colombia: MCO'
					)
					->setHelp('Create a mercadopago test user');

		 }

		 /**
		  * {@inheritdoc}
		  */
		protected function execute(InputInterface $input, OutputInterface $output){

			$mp	=	new \MP(
									$input->getArgument('id'),
									$input->getArgument('secret')
			);

			$r		= $mp->post('/users/test_user',"{\"site_id\":\"{$input->getArgument('site-id')}\"}");

			var_dump($r);

			if(!$r){

				throw new \Exception("Could not create test user");

			}

			/*
				Example: 
				array(2) {
				  ["status"]=>
				  int(201)
				  ["response"]=>
				  array(5) {
					 ["id"]=>
					 int(247119748)
					 ["nickname"]=>
					 string(11) "TETE7656807"
					 ["password"]=>
					 string(10) "qatest9072"
					 ["site_status"]=>
					 string(6) "active"
					 ["email"]=>
					 string(30) "test_user_2658879@testuser.com"
				  }
				}

			*/

			$output->getFormatter()
			->setStyle(
							'yheader', 
							new OutputFormatterStyle('black', 'yellow')
			);

			$output->writeln('<yheader>Your mercadopago test user has been created</yheader>');
			$output->writeln('');
			$output->writeln('Id: '.$r['response']['id']);
			$output->writeln('Email: '.$r['response']['email']);
			$output->writeln('Nickname: '.$r['response']['nickname']);
			$output->writeln('Password: '.$r['response']['password']);
			$output->writeln('');
			$output->writeln('<yheader>Make sure to save this data in a file!</yheader>');

		}

	}

}
