<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TemperatureExceeded extends Mailable
{
    use Queueable, SerializesModels;

    private $threshold_temperature;
    private $current_temperature;

    public function __construct($threshold_temperature, $current_temperature)
    {
        $this->threshold_temperature = $threshold_temperature;
        $this->current_temperature = $current_temperature;
    }

    public function build()
    {
        $subject = '【熱中症アラート】';
        $from = 'from@example.com';

        return $this
            ->from($from, config('app.name'))
            ->subject($subject)
            ->view('emails.temperature_exceeded', [
                'threshold_temperature' => $this->threshold_temperature,
                'current_temperature' => $this->current_temperature,
            ]);
    }
}