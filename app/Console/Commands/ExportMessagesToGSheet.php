<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Revolution\Google\Sheets\Facades\Sheets;

class ExportMessagesToGSheet extends Command
{
    private const BATCH_SIZE = 1000;

    protected $signature = 'export:messages
                            {chatbot_id : ID del chatbot}
                            {channel_id : ID del channel}
                            {sheet_id : ID del documento de Google}
                            {sheet_name : Nombre de la hoja}';

    protected $description = 'Export filtered messages by chatbot and channel to Google Sheets';

    public function handle(): int
    {
        $chatbotId = $this->argument('chatbot_id');
        $channelId = $this->argument('channel_id');
        $sheetId = $this->argument('sheet_id');
        $sheetName = $this->argument('sheet_name');

        $this->info('Fetching messages in batches...');

        $existingRows = Sheets::spreadsheet($sheetId)
            ->sheet($sheetName)
            ->get();

        $lastMessageId = 0;
        if ($existingRows->count() > 1) {
            $lastRow = $existingRows->last();
            $lastMessageId = (int) $lastRow[0]; // primera columna = message_id
        }

        $this->info("Último message_id exportado: {$lastMessageId}");

        $idRange = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->join('chatbot_channels as cc', 'cc.id', '=', 'c.chatbot_channel_id')
            ->where('cc.chatbot_id', $chatbotId)
            ->where('cc.channel_id', $channelId)
            ->selectRaw('MIN(m.id) as min_id, MAX(m.id) as max_id')
            ->first();

        if (! $idRange || ! $idRange->min_id) {
            $this->warn('No messages found.');

            return Command::SUCCESS;
        }

        $currentFromId = max($lastMessageId + 1, (int) $idRange->min_id);
        $maxId = (int) $idRange->max_id;

        if ($currentFromId > $maxId) {
            $this->info('No new messages to export.');

            return Command::SUCCESS;
        }

        $this->info("Processing new messages from ID {$currentFromId} to {$maxId}");

        $rowsToInsert = [];
        if ($existingRows->count() <= 1) {
            $rowsToInsert[] = [
                'Id Message',
                'Id Organización',
                'Id Agente (chatbot)',
                'Id conversación',
                'id_channel o nombre (ej Whatsapp)',
                'nombre_usuario',
                'mensaje_usuario',
                'respuesta_IA/agente (si responde una persona)',
                'timestamp',
                'teléfono_usuario',
            ];
        }

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
                    'm.id as message_id',
                    'cc.channel_id as organization_id',
                    'cc.chatbot_id as chatbot_id',
                    'm.conversation_id',
                    'ch.name as channel_name',
                    'c.contact_name as user_name',
                    'c.contact_phone as phone_number',
                    'm.type',
                    'm.content',
                    'm.created_at',
                ])
                ->get();

            foreach ($rows as $row) {
                $rowsToInsert[] = [
                    $row->message_id,
                    $row->organization_id,
                    $row->chatbot_id,
                    $row->conversation_id,
                    $row->channel_name,
                    $row->user_name ?? '',
                    $row->type === 'incoming' ? $row->content : '',
                    $row->type === 'outgoing' ? $row->content : '',
                    $row->created_at,
                    $row->phone_number ?? '',
                ];
            }

            $currentFromId += self::BATCH_SIZE;
        }

        /*
        |--------------------------------------------------------------------------
        | Insert into Google Sheet
        |--------------------------------------------------------------------------
        */
        $this->info('Appending new data to Google Sheet...');

        Sheets::spreadsheet($sheetId)
            ->sheet($sheetName)
            ->append($rowsToInsert);

        $this->info('Data exported successfully to Google Sheets.');

        return Command::SUCCESS;
    }
}
