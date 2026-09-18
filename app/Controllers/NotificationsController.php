<?php
namespace App\Controllers;

use App\Repositories\NotificationRepository;

class NotificationsController extends BaseController
{
    public function index()
    {
        return view('notifications/index', [
            'title' => 'Notifications',
            'notifications' => (new NotificationRepository())->list($this->applicantId()),
        ]);
    }

    public function markRead(int $id)
    {
        (new NotificationRepository())->markRead($id, $this->applicantId());
        return redirect()->to(site_url('notifications'));
    }
}
