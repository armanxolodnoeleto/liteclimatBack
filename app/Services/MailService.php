<?php
namespace App\Services;

class MailService {
    protected $projectId;
    protected $project;
    protected $login;
    protected $password;
    protected $host;

    public function __construct($projectId) {
        $this->projectId = $projectId;
        if ($projectId == 59) {
            $this->project = 'LaitKlimat.ru';
            $this->login = 'zakaz@laitklimat.ru';
            $this->password = 'Zpass1568';
            $this->host = 'ssl://smtp.yandex.ru';
        }else {
            $this->project = 'Xolodnoeleto.ru';
            $this->login = 'zakaz@xolodnoeleto.ru';
            $this->password = 'Zpass82827';
            $this->host = 'ssl://smtp.yandex.ru';
        }
    }

    public function sendMail($data, $theme, $view) {
        $projectId = $this->projectId;
        $message = view($view, compact('data', 'projectId'))->render();
        $this->sendMailByProject($this->login, $theme, $message);
    }

    public function sendMailToClient($email, $data, $theme, $view) {
        $projectId = $this->projectId;
        $messageForClient = view($view, compact('data', 'projectId'))->render();
        $this->sendMailByProject($email, $theme, $messageForClient);
    }

    private function sendMailByProject($email, $theme, $message) {
        $mailSMTP = new SendMailSmtpClass($this->login, $this->password, $this->host, 'Клиент', 465);
        $headers= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: ". $this->project ." <". $this->login .">\r\n";
        $headers .= "To: <" . $email.">\r\n";
        $result =  $mailSMTP->send($email, $theme, $message, $headers);

        return $result;
    }
}
