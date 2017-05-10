<?php

namespace App\Models;

use Html2Text\Html2Text;
use Illuminate\Database\Eloquent\Model;
use Log;
use Mailgun\Mailgun;

class Message extends Model
{
    public function thread()
    {
        return $this->belongsTo('App\Models\Thread');
    }

    public function reply($user_id, $body_html)
    {
        $subject = (substr($this->subject,0,3) === "Re:") ? $this->subject : ('Re: '.$this->subject);//i think we *need* this for gmail threading
        $from = $this->thread->inbox->primary_address; //todo: maybe allow overriding this?
        $to = $this->from; //replies should be sent to the from address
        $replying_to_message_id = $this->message_id;
        $threadId = $this->thread_id;

        self::sendMessage($to, $from, $subject, $body_html, $threadId, $user_id, $replying_to_message_id);
    }

    /**
     * Sends a new message
     * @param $inbox_id - The Inbox to send from
     * @param $user_id - The User who is sending the message
     * @param $to - who to send the email to
     * @param $subject - email subject
     * @param $body_html - body in HTML form
     */
    public static function newMessage($inbox_id, $user_id, $to, $subject, $body_html)
    {
        $from = Inbox::find($inbox_id)->primary_address; //todo: override?
        $threadId = Thread::create(['inbox_id'=>$inbox_id,'state'=>Thread::STATE_IN_PROGRESS])->id;
        //todo: assign this thread to a user, log thread creation
        self::sendMessage($to, $from, $subject, $body_html, $threadId, $user_id);
    }

    /**
     * @param $to - who to send the message to
     * @param $from - who to send the message from
     * @param $subject - subject
     * @param $body_html - html body
     * @param $threadId - the thread that the message should be attached to
     * @param $user_id - the User who is sending the message
     * @param null|int $replying_to_message_id
     */
    private static function sendMessage($to, $from, $subject, $body_html, $threadId, $user_id, $replying_to_message_id = null)
    {
        $body_plain  = Html2Text::convert($body_html,true);


        $m = new self();
        $m->user_id = $user_id;
        $m->thread_id = $threadId;
        $m->from = $from;
        $m->sender = $from; //sender is silly
        $m->subject = $subject;
        $m->recipient = $to;
        $m->message_id = 'pending';
        $m->body_plain = $body_plain;
        $m->body_html = $body_html;
        $m->timestamp = 0;
        if ($replying_to_message_id) {
            $m->references = $replying_to_message_id;
            $m->in_reply_to = $replying_to_message_id;
        }
        $m->save();

        $mg = Mailgun::create(env('MAILGUN_APIKEY'));
        $params = [
            'from'    => $from,
            'to'      => $to,
            'subject' => $subject,
            'text'    => $body_plain,
            'html'    => $body_html,
            'v:antelope-message-id'=>$m->id
        ];
        if ($replying_to_message_id) {
            $params['In-Reply-To'] = $replying_to_message_id;
            $params['References'] = $replying_to_message_id;
        }
        $sent = $mg->messages()->send(env('MAILGUN_DOMAIN'), $params);
        $m->message_id = $sent->getId();

        $m->save();
        Log::info('sent message #'.$m->id);

    }

//    public static function sendMessage($)
}