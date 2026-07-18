<x-mail::message>
# {{ __('app.email_report.greeting', ['name' => $recipientName]) }}

{{ __('app.email_report.body', ['title' => $reportTitle, 'company' => $companyName, 'period' => $periodLabel]) }}

{{ __('app.email_report.thanks') }},<br>
DynoPOS Cloud Report
</x-mail::message>
