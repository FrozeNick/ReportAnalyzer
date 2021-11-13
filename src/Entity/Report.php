<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

class Report
{
    private $reportFilename;

    public function getReportFilename()
    {
        return $this->reportFilename;
    }

    public function setReportFilename($reportFilename)
    {
        $this->reportFilename = $reportFilename;

        return $this;
    }
}