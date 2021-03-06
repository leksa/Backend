<?php

namespace Persona\Hris\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Persona\Hris\Entity\Payroll;
use Persona\Hris\Entity\PayrollDetail;
use Persona\Hris\Entity\TaxHistory;
use Persona\Hris\Salary\Model\BenefitInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Muhamad Surya Iksanudin <surya.iksanudin@personahris.com>
 */
final class CalculateTaxController extends Controller
{
    /**
     * @ApiDoc(
     *     section="Utilities",
     *     description="Tax Calculator",
     *     requirements={
     *      {
     *          "name"="year",
     *          "dataType"="integer",
     *          "description"="Year"
     *      },
     *      {
     *          "name"="month",
     *          "dataType"="integer",
     *          "description"="Month"
     *      }
     *  })
     *
     * @Route(name="tax_calculation", path="/employee/tax/calculate.json")
     *
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function calculateAction(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));

        $taxCalculator = $this->container->get('persona.tax.tax_calculator');
        $employeeRepository = $this->container->get('persona.repository.orm.employee_repository');
        $payrollRepository = $this->container->get('persona.repository.orm.payroll_repository');
        $manager = $this->container->get('persona.manager.manager_factory')->getWriteManager();

        foreach ($employeeRepository->findActiveEmployee() as $key => $employee) {
            if (!$payrollRepository->isClosed($employee, $year, $month)) {
                continue;
            }

            $taxHistory = new TaxHistory();
            $taxHistory->setEmployee($employee);
            $taxHistory->setTaxYear($year);
            $taxHistory->setTaxMonth($month);
            $taxHistory->setTaxValue($taxCalculator->calculateTax($employee));

            $manager->persist($taxHistory);

            if (0 === $key % 17) {
                $manager->flush();
            }
        }

        $manager->flush();

        return new JsonResponse(['status' => JsonResponse::HTTP_CREATED, 'message' => 'Employee tax has been calculated']);
    }
}
