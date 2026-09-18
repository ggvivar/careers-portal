<?php

namespace App\Controllers;

use App\Repositories\NotificationRepository;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class BaseController extends Controller
{
    protected $request;

    /** @var list<string> */
    protected $helpers = ['url', 'form', 'text'];

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        $unread = 0;
        $applicantId = (int) session()->get('applicant_id');

        if ($applicantId > 0) {
            try {
                $unread = (new NotificationRepository())->unreadCount($applicantId);
            } catch (Throwable $exception) {
                log_message('warning', 'Unable to load navbar notifications: {message}', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        Services::renderer()->setVar('navbarUnreadNotifications', $unread);
    }

    protected function applicantId(): int
    {
        return (int) session()->get('applicant_id');
    }
}
