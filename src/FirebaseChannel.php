<?php

namespace Alfa6661\Firebase;

use Exception;
use paragraph1\phpFCM\Client;
use paragraph1\phpFCM\Message;
use Illuminate\Events\Dispatcher;
use paragraph1\phpFCM\Recipient\Device;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Events\NotificationFailed;

class FirebaseChannel
{
    /**
     * FCM client.
     *
     * @var \paragraph1\phpFCM\Client
     */
    protected $client;

    /**
     * Events dispatcher.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;

    /**
     * Push Service constructor.
     *
     * @param \paragraph1\phpFCM\Client $client
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function __construct(Client $client, Dispatcher $events)
    {
        $this->client = $client;
        $this->events = $events;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $devices = $notifiable->routeNotificationFor('firebase');

        if (empty($devices)) {
            return;
        }

        $firebase = $notification->toFirebase($notifiable);

        try {
            foreach ($devices as $device) {

                $exp = explode("|",$device);
                if(count($exp) > 1)
                {
                    $device = $exp[1];
                    if($exp[0] == "a")
                    {
                        $message = (new Message())
                            ->addRecipient(new Device($device))
                            ->setData($firebase->data)
                            ->setNotification($firebase->notification);
                        $response = $this->client->send($message);
                    }
                    else if($exp[0] == "i")
                    {
                        $message = (new Message())
                            ->addRecipient(new Device($device))
                            ->setData($firebase->data)
                            ->setNotification($firebase->notification)
                            ->setContentAvailable(true);
                        $response = $this->client->send($message);
                    }
                }
                else
                {
                    $this->message->addRecipient(new Device($device))
                        ->setData($firebase->data);
                    $response = $this->client->send($this->message);
                }
            }
        } catch (Exception $e) {
            $this->events->fire(
                new NotificationFailed($notifiable, $notification, $this)
            );
        }
    }
}
