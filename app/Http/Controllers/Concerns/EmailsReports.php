<?php

namespace App\Http\Controllers\Concerns;

use App\Mail\ReportMail;
use App\Models\User;
use App\Support\Reports\ExportFormat;
use App\Support\Reports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

trait EmailsReports
{
    /**
     * Generates the report in $format and queues it as an email attachment
     * to the authenticated user's own registered email — never an
     * arbitrary address, since that would turn this into an open relay for
     * sending someone else's sales data anywhere. Only the account holder's
     * own verified email is ever used.
     */
    private function emailReport(
        ReportExport $export,
        ExportFormat $format,
        string $baseFilename,
        User $user,
        string $reportTitle,
        string $periodLabel,
    ): void {
        [$content, $mime] = match ($format) {
            ExportFormat::Csv => [Excel::raw($export->spreadsheet, $format->writerType()), 'text/csv'],
            ExportFormat::Xlsx => [
                Excel::raw($export->spreadsheet, $format->writerType()),
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            ExportFormat::Pdf => [Pdf::loadView($export->pdfView, $export->pdfData)->output(), 'application/pdf'],
        };

        Mail::to($user->email)->queue(new ReportMail(
            reportTitle: $reportTitle,
            periodLabel: $periodLabel,
            companyName: $user->company->name,
            recipientName: $user->name,
            attachmentContent: $content,
            attachmentFilename: "{$baseFilename}.{$format->value}",
            attachmentMime: $mime,
        ));
    }
}
