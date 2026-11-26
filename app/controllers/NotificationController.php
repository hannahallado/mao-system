<?php
/** Top bar notifications: open one (marks it read) and mark all as read. */
class NotificationController extends Controller
{
    /** GET /notifications/open?key=  Marks the item read, then goes to its page. */
    public function open(): void
    {
        $item = NotificationService::find((string) $this->input('key', ''));
        if (!$item) {
            $this->redirect('/home');   // gone or not this user's
        }
        NotificationService::markRead([$item['key']]);
        $this->redirect($item['url']);   // url comes from the service, never the request
    }

    /** POST /notifications/read-all */
    public function readAll(): void
    {
        NotificationService::markRead(array_column(NotificationService::forCurrentUser(), 'key'));
        $back = (string) $this->input('back', '/home');
        // Only same-site paths (no "//host" or "http:")
        $this->redirect(preg_match('#^/(?!/)[A-Za-z0-9/_\-?=&.%]*$#', $back) ? $back : '/home');
    }
}
