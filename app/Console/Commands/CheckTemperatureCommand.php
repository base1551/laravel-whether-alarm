<?php

namespace App\Console\Commands;

use App\Mail\TemperatureExceeded;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class CheckTemperatureCommand extends Command
{
    // 天気情報を取得する場所（東京スカイツリー）
    const LATITUDE = '35.710079006659114';
    const LONGITUDE = '139.8106985886275';
    // const TO_EMAIL_ADDRESS = '@example.com';
    protected $signature = 'check:temperature
                            {--threshold=20 : The temperature threshold}
                            {--start=7:00 : The start time}
                            {--end=23:00 : The end time}
                            {--ignore=3 : The number of hours to ignore}';
    protected $description = 'Check temperature';

    public function handle()
    {
        $threshold_temperature = intval($this->option('threshold'));
        $ignore_hours = intval($this->option('ignore'));
        $start_dt = new Carbon($this->option('start'));
        $end_dt = new Carbon($this->option('end'));
        $now = now();

        var_dump('基準温度' . $threshold_temperature);

        // 時間チェック
        if (!$now->between($start_dt, $end_dt)) {

            $this->info('有効時間外です');
            return Command::SUCCESS;
        }

        // 温度を取得
        $current_temperature = $this->getTemperature();

        if (is_null($current_temperature)) {

            $this->error('気温の取得に失敗しました');
            return Command::FAILURE;
        }

        // 条件が合えばメール送信
        $last_email_date_time = cache('last_email_date_time');

        if (is_null($last_email_date_time) || $now->diffInHours($last_email_date_time) > $ignore_hours) { // 基準時間以上経過している場合はメール送信可

            if ($current_temperature > $threshold_temperature) { // 温度が基準を超えたかどうかチェック

                $this->info('気温が基準を超えました');
                $this->sendEmail($current_temperature);

                cache(['last_email_date_time' => $now]);
            } else {

                $this->info('気温は基準内です');
            }
        } else {

            $this->info('メール送信後、時間が経過していません');
        }

        return Command::SUCCESS;
    }

    private function getTemperature()
    {
        $temperature = null;
        $api_key = env('OPEN_WEATHER_MAP_API'); // 本来はコンフィグに登録すべきです
        $url = 'https://api.openweathermap.org/data/2.5/weather?' .
            'lat=' . self::LATITUDE . '&' .
            'lon=' . self::LONGITUDE . '&' .
            'appid=' . $api_key . '&' .
            'units=metric';
        $response = Http::get($url);

        if ($response->ok()) {

            $data = $response->json();
            $temperature = Arr::get($data, 'main.temp');
        }

        var_dump($temperature);
        if (!is_null($temperature)) {

            return intval(
                round($temperature) // 小数点以下を四捨五入
            );
        }

        return $temperature; // テスト用温度
    }

    private function sendEmail($current_temperature)
    {
        $to = env('MAIL_TO_ADDRESS');
        var_dump('to' . $to);
        $threshold_temperature = intval($this->option('threshold'));

        Mail::to($to)->send(
            new TemperatureExceeded($threshold_temperature, $current_temperature)
        );
    }
}