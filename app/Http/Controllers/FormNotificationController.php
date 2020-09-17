<?php

namespace App\Http\Controllers;

use App\Library\LandBot\LandBot;
use App\Models\Client;
use App\Models\WebNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FormNotificationController extends Controller
{

    private const BOT_ID = '662558';

    public function latestNotification(Request $request)
    {
        $client = Client::firstWhere('phone', 'like', '%' . $request->phone);

        $notification = $client->notifications()
            ->latest()
            ->first();

        if (is_null($notification))
            abort(204);

        return $notification;
    }

    public function saveAndNotify(Request $request)
    {
        $client = $this->findClient($request->url);

        $this->saveModel($client, $request);

        $this->notify($client);
    }

    private function notify(Client $client)
    {
        $this->handleLandBot($client);
        $landbotCustomer = (new LandBot())->customer();

        $landbotCustomer->assignBot($client->landbot_id, self::BOT_ID);
    }

    private function saveModel(Client $client, Request $request)
    {
        $notification = WebNotification::make([
            'origin' => $request->url,
            'data' => $request->except('url'),
            'formatted_data' => $this->formatData($request->except('url')),
        ]);
        $notification->client()->associate($client);
        $notification->save();

        return $notification;
    }

    private function findClient(String $origin)
    {
        $domain = parse_url($origin)['host'];

        $account = $this->fetchAccount($domain, [], 'cf_notification_domain')['sales_account'];

        $domainString = $account['custom_field']['cf_notification_domain'];
        $domains = collect(explode(',', $domainString));

        if (!$domains->contains($domain))
            abort(404, 'Account not found');

        return Client::firstWhere('freshsales_id', $account['id']);
    }

    private function handleLandBot($client)
    {
        if (!is_null($client->landbot_id))
            return ;

        $phone = substr($client->phone, 1);
        $landbot_id = (new LandBot())
            ->customer()
            ->searchBy('phone', $phone)['customers'][0]['id'];
        $client->update([
            'landbot_id' => $landbot_id
        ]);
    }

    private function formatData($request)
    {
        $res = "";
        foreach ($request as $key => $value) {
            $res .= $key . ": " . $value . "\n";
        }
        return $res;
    }
}
