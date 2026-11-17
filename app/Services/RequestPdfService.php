<?php

namespace App\Services;

use App\Models\CacRegistration;
use App\Models\LoanRequest;
use App\Models\MoniepointPosRequest;
use App\Models\PassportRequest;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Http\Response;
use TCPDF;

class RequestPdfService
{
    /**
     * Generate an A4 PDF for a Passport Application Request.
     */
    public function generatePassportPdf(PassportRequest $passportRequest, ?User $actor = null): Response
    {
        $html = view('pdf.passport-request', [
            'request' => $passportRequest,
            'actor'   => $actor,
            'title'   => 'International Passport Application Docket',
        ])->render();

        return $this->renderA4Pdf(
            $html,
            "Passport-Application-{$passportRequest->reference}.pdf",
            "Passport Application - {$passportRequest->reference}"
        );
    }

    /**
     * Generate an A4 PDF for a Travel & Tours Request.
     */
    public function generateTravelPdf(TravelRequest $travelRequest, ?User $actor = null): Response
    {
        $html = view('pdf.travel-request', [
            'request' => $travelRequest,
            'actor'   => $actor,
            'title'   => 'Travel & Tours Booking Docket',
        ])->render();

        return $this->renderA4Pdf(
            $html,
            "Travel-Request-{$travelRequest->reference}.pdf",
            "Travel Request - {$travelRequest->reference}"
        );
    }

    /**
     * Generate an A4 PDF for a CAC Registration.
     */
    public function generateCacPdf(CacRegistration $cacRegistration, ?User $actor = null): Response
    {
        $html = view('pdf.cac-registration', [
            'registration' => $cacRegistration,
            'actor'        => $actor,
            'title'        => 'CAC Registration Filing Docket',
        ])->render();

        return $this->renderA4Pdf(
            $html,
            "CAC-Registration-{$cacRegistration->reference}.pdf",
            "CAC Registration - {$cacRegistration->reference}"
        );
    }

    /**
     * Generate an A4 PDF for a Business Loan Request.
     */
    public function generateLoanPdf(LoanRequest $loanRequest, ?User $actor = null): Response
    {
        $html = view('pdf.loan-request', [
            'request' => $loanRequest,
            'actor'   => $actor,
            'title'   => 'Business Loan Application Docket',
        ])->render();

        return $this->renderA4Pdf(
            $html,
            "Loan-Request-{$loanRequest->reference}.pdf",
            "Loan Request - {$loanRequest->reference}"
        );
    }

    /**
     * Generate an A4 PDF for a Moniepoint POS Terminal Request.
     */
    public function generatePosPdf(MoniepointPosRequest $posRequest, ?User $actor = null): Response
    {
        $html = view('pdf.pos-request', [
            'request' => $posRequest,
            'actor'   => $actor,
            'title'   => 'Moniepoint POS Terminal Request Docket',
        ])->render();

        return $this->renderA4Pdf(
            $html,
            "POS-Request-{$posRequest->reference}.pdf",
            "POS Request - {$posRequest->reference}"
        );
    }

    /**
     * Render raw HTML to A4 PDF using TCPDF.
     */
    protected function renderA4Pdf(string $html, string $filename, string $title): Response
    {
        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends TCPDF {
            public function Header()
            {
                // Custom header is rendered in HTML body
            }

            public function Footer()
            {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->SetTextColor(140, 140, 140);
                $this->Cell(0, 10, 'SimPhix Official Request System · Confidential Document · Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, false, 'C');
            }
        };

        $pdf->SetCreator('SimPhix Platform');
        $pdf->SetAuthor('SimPhix Digital Services');
        $pdf->SetTitle($title);
        $pdf->SetSubject($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->AddPage();

        $pdf->writeHTML($html, true, false, true, false, '');

        $binary = $pdf->Output($filename, 'S');

        return response($binary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
