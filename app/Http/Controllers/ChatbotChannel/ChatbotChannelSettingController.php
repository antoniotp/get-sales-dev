<?php

namespace App\Http\Controllers\ChatbotChannel;

use App\Http\Controllers\Controller;
use App\Models\ChatbotChannel;
use Illuminate\Http\Request;

class ChatbotChannelSettingController extends Controller
{
    public function store(Request $request, ChatbotChannel $chatbot_channel)
    {
        // 1. Validar que se reciba un array de settings
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
        ]);

        // 2. Iterar sobre cada setting recibido
        foreach ($validated['settings'] as $settingData) {
            $key = $settingData['key'];
            $value = $settingData['value'];

            $setting = $chatbot_channel->settings()->withTrashed()->where('key', $key)->first();

            if (empty($value)) {
                if ($setting) {
                    $setting->delete();
                }
            } else {
                if ($setting) {
                    if ($setting->trashed()) {
                        $setting->restore();
                    }
                    $setting->update(['value' => $value]);
                } else {
                    $chatbot_channel->settings()->create([
                        'key' => $key,
                        'value' => $value,
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Settings saved successfully.');
    }
}
