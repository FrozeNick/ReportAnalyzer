<?php
namespace App\Controller;

use App\Entity\Report;
use App\Form\ReportType;
use App\Service\FileUploader;
use App\Service\ReportAnalyzer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class MainController extends AbstractController
{
    // Index page. Upload a csv file to generate a report
    public function report(Request $request, FileUploader $fileUploader)
    {
        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reportFile = $form->get('report')->getData();
            if ($reportFile) {
                $reportFileName = $fileUploader->upload($reportFile);
                $report->setReportFilename($reportFileName);
            }

            return $this->redirectToRoute('reportResults', [
                'reportName' => $reportFileName
            ]);
        }
        
        return $this->renderForm('form/report.html.twig', [
            'form' => $form,
        ]);
    }

    // Report results where user is redirected after csv file was uploaded
    public function reportResults(string $reportName): Response
    {
        // Create a new report analyzer by given report name
        $analyzer = new ReportAnalyzer($reportName);

        // Check if given file name is actually on our server
        if($analyzer->doesExist()) {
            // Render results page with the report name & data
            return $this->render('results.html.twig', [
                'report' => $reportName,
                'report_data' => $analyzer->analyze()
                ->getCustomerData()
                ->values()
                ->toArray()
            ]);
        } else {
            // Render the results page with no data
            return $this->render('results.html.twig');
        }
    }
}