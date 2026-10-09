{!! $messageBody !!}

--
{{ __('inquiry.mail_footer', ['receipt' => $receiptNo, 'site' => config('app.name')]) }}