<?php

namespace HitPay\Firebase;

class Message
{
    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    /**
     * The title of the notification.
     *
     * @var string
     */
    public $title;

    /**
     * The message of the notification.
     *
     * @var string
     */
    public $message;

    /**
     * The badge of the notification.
     * @warning UNUSED
     *
     * @var int
     */
    public $badge;

    /**
     * The priority of the notification.
     *
     * @var string
     */
    public $priority = self::PRIORITY_HIGH;

    /**
     * Additional data of the notification.
     *
     * @var array
     */
    public $data = [];

    /**
     * @param string|null $title
     * @param string|null $message
     * @param array       $data
     * @param string      $priority
     *
     * @return static
     */
    public static function create($title = null, $message = null, $data = [], $priority = self::PRIORITY_HIGH)
    {
        return new static($title, $message, $data, $priority);
    }

    /**
     * @param string|null $title
     * @param string|null $message
     * @param array       $data
     * @param string      $priority
     */
    public function __construct($title = null, $message = null, $data = [], $priority = self::PRIORITY_HIGH)
    {
        $this->title    = $title;
        $this->message  = $message;
        $this->data     = $data;
        $this->priority = $priority;
    }

    /**
     * Set the title of the notification.
     *
     * @param string $title
     *
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the message of the notification.
     *
     * @param string $message
     *
     * @return $this
     */
    public function message($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set the badge of the notification.
     *
     * @param int $badge
     *
     * @return $this
     */
    public function badge($badge)
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Set the priority of the notification.
     *
     * @param string $priority
     *
     * @return $this
     */
    public function priority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Add data to the notification.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function data($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Override the data of the notification.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Add an action to the notification.
     *
     * @param string $action
     * @param mixed  $params
     *
     * @return $this
     */
    public function action($action, $params = null)
    {
        return $this->data('action', [
            'action' => $action,
            'params' => $params,
        ]);
    }
}
