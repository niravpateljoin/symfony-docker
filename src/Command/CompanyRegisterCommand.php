<?php

namespace App\Command;

use App\Entity\Company;
use App\Entity\Employee;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class CompanyRegisterCommand extends Command
{
    private ManagerRegistry $managerRegistry;
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('company:register')
            ->setDescription('This command is use to create new company')
            ->setDefinition([
                new InputArgument('company_name', InputArgument::REQUIRED, 'Company Name'),
                new InputArgument('firstNameEmp', InputArgument::OPTIONAL, 'Employee First Name'),
                new InputArgument('lastNameEmp', InputArgument::OPTIONAL, 'Employee Last Name'),
                new InputArgument('emailEmp', InputArgument::OPTIONAL, 'Employee Email'),
            ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $companyName = $input->getArgument('company_name');
        $firstName = $input->getArgument('firstNameEmp');
        $lastName = $input->getArgument('lastNameEmp');
        $email = $input->getArgument('emailEmp');

        $entityManager = $this->managerRegistry->getManager();
        $company = New Company();
        $company->setName($companyName);
        $employee = New Employee();
        $employee->setFirstName($firstName);
        $employee->setLastName($lastName);
        $employee->setEmail($email);
        $employee->setCompany($company);
        $entityManager->persist($employee);

        $entityManager->persist($company);
        $entityManager->flush();
        $io->success($companyName. " successfully created with id->". $company->getId());
        return Command::SUCCESS;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $questions = [];
        if (!$input->getArgument('company_name')) {
            $question = new Question('Please choose a company name : ');
            $question->setValidator(function ($companyName) {
                if (empty($companyName)) {
                    throw new Exception('company name can not be empty');
                }
                $companyObj = $this->managerRegistry->getRepository(Company::class)->findOneByName($companyName);
                if ($companyObj instanceof Company) {
                    throw new Exception('company already exist, try another');
                }
                return $companyName;
            });
            $questions['company_name'] = $question;
        }

        if (!$input->getArgument('firstNameEmp') && !$input->getArgument('lastNameEmp') && !$input->getArgument('emailEmp')) {
            $question = new Question(
                'Enter First Name Of Employee : '
            );
            $question->setValidator(function ($employeeFirstName) {
                if (empty($employeeFirstName)) {
                    throw new Exception('employee first name can not be empty');
                }
                return $employeeFirstName;
            });

            $questionTwo = new Question(
                'Enter Last Name Of Employee : '
            );
            $questionTwo->setValidator(function ($employeeLastName) {
                if (empty($employeeLastName)) {
                    throw new Exception('employee first name can not be empty');
                }
                return $employeeLastName;
            });

            $questionThree = new Question(
                'Enter Email Of Employee : '
            );
            $questionThree->setValidator(function ($employeeEmail) {
                if (empty($employeeEmail)) {
                    throw new Exception('employee first name can not be empty');
                }
                if (!filter_var($employeeEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid Email Address.');
                }
                $employeeObj = $this->managerRegistry->getRepository(Employee::class)->findOneByEmail($employeeEmail);
                if ($employeeObj instanceof Employee) {
                    throw new Exception('employee already exist, try another.');
                }
                return $employeeEmail;
            });

            $questions['firstNameEmp'] = $question;
            $questions['lastNameEmp'] = $questionTwo;
            $questions['emailEmp'] = $questionThree;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }
}
