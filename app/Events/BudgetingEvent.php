<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\DataNotif;
use App\Models\User;

class BudgetingEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    public $token;
    public $key_id;
    public $status;
    public $created_at;

    public function __construct($token,$key_id,$message)
    {
        $this->token    = $token;
        $this->channel  = "budgeting-channel";
        $this->event    = "BudgetingEvent";
        $this->status   = 0;
        $this->messages = $message;
        $this->key_id   = $key_id;
        $this->created_at   = date("Y-m-d H:i:s");
        $this->inserDataNotif($token,$key_id,$message);
    }

    public function broadcastOn()
    {
        return ['budgeting-channel'];
    }

    public function broadcastAs()
    {
        return 'BudgetingEvent';
    }

    private function inserDataNotif($token,$key_id,$messages)
    {

        $model                  = new DataNotif();
        $model->token           = $token;
        $model->status          = 0;
        $model->channel         = 'budgeting-channel';
        $model->event           = "BudgetingEvent";
        $model->key_id          = $key_id;
        $model->messages        = $messages;
        $model->save();

    }
}