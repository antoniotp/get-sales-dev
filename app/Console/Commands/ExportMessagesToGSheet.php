<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Revolution\Google\Sheets\Facades\Sheets;

class ExportMessagesToGSheet extends Command
{
    private const BATCH_SIZE = 10;
    private const GOOGLE_SHEET_ID = '17uWPQU9CITs4u6TKmoN1PH81rRFT76if0_AOkxJgUKc';

    protected $signature = 'export:messages
                            {chatbot_id : ID del chatbot}
                            {channel_id : ID del channel}';

    protected $description = 'Export filtered messages by chatbot and channel to Google Sheets';

    public function handle(): int
    {
        $chatbotId = $this->argument('chatbot_id');
        $channelId = $this->argument('channel_id');

        $this->info('Fetching messages in batches...');

        $idRange = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->join('chatbot_channels as cc', 'cc.id', '=', 'c.chatbot_channel_id')
            ->where('cc.chatbot_id', $chatbotId)
            ->where('cc.channel_id', $channelId)
            ->selectRaw('MIN(m.id) as min_id, MAX(m.id) as max_id')
            ->first();

        if (!$idRange || !$idRange->min_id) {
            $this->warn('No messages found.');
            return Command::SUCCESS;
        }

        $currentFromId = (int) $idRange->min_id;
        $maxId = (int) $idRange->max_id;

        $this->info("Processing messages from ID {$currentFromId} to {$maxId}");

        $rowsToInsert = [];
        $rowsToInsert[] = [
            'Id Organización',
            'ID Agente (chatbot)',
            'Id conversación',
            'id_channel o nombre (ej Whatsapp)',
            'nombre_usuario',
            'mensaje_usuario',
            'respuesta_IA/agente (si responde una persona)',
            'timestamp'
        ];

        while ($currentFromId <= $maxId) {
            $currentToId = $currentFromId + self::BATCH_SIZE - 1;

            $this->info("Batch: {$currentFromId} - {$currentToId}");

            $rows = DB::table('messages as m')
                ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
                ->join('chatbot_channels as cc', 'cc.id', '=', 'c.chatbot_channel_id')
                ->join('channels as ch', 'ch.id', '=', 'cc.channel_id')
                ->where('cc.chatbot_id', $chatbotId)
                ->where('cc.channel_id', $channelId)
                ->whereBetween('m.id', [$currentFromId, $currentToId])
                ->orderBy('m.id')
                ->select([
                    'cc.channel_id as organization_id',
                    'cc.chatbot_id as chatbot_id',
                    'm.conversation_id',
                    'ch.name as channel_name',
                    'c.contact_name as user_name',
                    'm.type',
                    'm.content',
                    'm.created_at',
                ])
                ->get();

            foreach ($rows as $row) {
                $rowsToInsert[] = [
                    $row->organization_id,
                    $row->chatbot_id,
                    $row->conversation_id,
                    $row->channel_name,
                    $row->user_name ?? '',
                    $row->type === 'incoming' ? $row->content : '',
                    $row->type === 'outgoing' ? $row->content : '',
                    $row->created_at,
                ];
            }

            $currentFromId += self::BATCH_SIZE;
        }

        /*
        |--------------------------------------------------------------------------
        | Insert into Google Sheet
        |--------------------------------------------------------------------------
        */
        $this->info('Writing data to Google Sheet...');

        Sheets::spreadsheet(self::GOOGLE_SHEET_ID)
            ->sheet('Hoja 1')
            ->clear();

        Sheets::spreadsheet(self::GOOGLE_SHEET_ID)
            ->sheet('Hoja 1')
            ->append($rowsToInsert);

        $this->info('Data exported successfully to Google Sheets.');

        return Command::SUCCESS;
    }
}
