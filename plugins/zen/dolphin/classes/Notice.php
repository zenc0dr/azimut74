<?php namespace Zen\Dolphin\Classes;

use Mail;
use View;
use Zen\Dolphin\Models\Settings;

class Notice extends Core
{
    function sendMail($data)
    {
        $subject = @$data['subject'] ?? 'Уведомление';
        $to = @$data['to'] ?? $this->getNoticeEmails();
        $html = @$data['html'];
        $text = @$data['text'];

        $raw = [
            'html' => $html,
            'text' => $text,
        ];

        Mail::raw($raw, function($message) use ($subject, $to) {
            $message->subject($subject);
            $message->to($to);
        });

        $errors = Mail::failures();

        $this->log([
            'source' => 'Zen\Dolphin\Classes\Notice@sendMail',
            'text' => 'Отправка письма',
            'dump' => [
                'subject' => $subject,
                'to' => $to,
                'raw' => $raw,
                'errors' => ($errors)?'yes':'no'
            ]
        ]);
    }

    private function getNoticeEmails()
    {
        $notice_emails = Settings::get('notice_emails');

        $emailsToSend = [];
        foreach ($notice_emails as $item) {
            $emailsToSend[] = $item['email'];
        }

        return $emailsToSend;
    }
}
