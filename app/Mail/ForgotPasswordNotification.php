<?php
namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class ForgotPasswordNotification extends Mailable {
 
    use Queueable, SerializesModels;
		
	public $subject     = '';
	public $datas       = '';
	public $link        = '';
	
	public function __construct($subject, $datas, $link) {
		$this->subject      = $subject;
		$this->datas        = $datas;
		$this->link         = $link;
	}
	
    //build the message.
    public function build() {
        $mail = $this->subject($this->subject)->view('emails.forgot-password')->with(['datas' => $this->datas, "link" => $this->link]);
		
		
		return $mail;
    }
}