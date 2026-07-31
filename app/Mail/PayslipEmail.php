<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PayslipEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $employee;
    public $periodName;
    protected $pdfContent;

    public function __construct($employee, $periodName, $pdfContent)
    {
        $this->employee = $employee;
        $this->periodName = $periodName;
        $this->pdfContent = $pdfContent; // El PDF en crudo generado por DomPDF
    }

    public function build()
    {
        return $this->subject('Tu Recibo de Nómina - ' . $this->periodName)
                    ->view('emails.payslip') // La vista HTML del correo
                    ->attachData($this->pdfContent, 'Recibo_Nomina_'.$this->employee->rfc.'.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}
