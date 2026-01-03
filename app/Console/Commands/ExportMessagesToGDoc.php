<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Google\Client;
use Google\Service\Docs;
use Google\Service\Docs\Document;
use Google\Service\Docs\Request;
use Google\Service\Docs\BatchUpdateDocumentRequest;

class ExportMessagesToGDoc extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'export:messages 
                            {chatbot_id : ID del chatbot} 
                            {channel_id : ID del channel}';

    /**
     * The console command description.
     */
    protected $description = 'Export filtered messages by chatbot and channel to Google Docs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chatbotId = $this->argument('chatbot_id');
        $channelId = $this->argument('channel_id');

        $this->info('Consultando mensajes...');

        /*
        |--------------------------------------------------------------------------
        | 1. Consulta de datos
        |--------------------------------------------------------------------------
        */

        $rows = DB::table('messages as m')
            ->join('conversations as conv', 'conv.id', '=', 'm.conversation_id')
            ->join('chatbot_channels as cc', 'cc.id', '=', 'conv.chatbot_channel_id')
            ->join('channels as ch', 'ch.id', '=', 'cc.channel_id')
            ->where('cc.chatbot_id', $chatbotId)
            ->where('cc.channel_id', $channelId)
            ->orderBy('m.created_at')
            ->limit(10)
            ->select([
                'cc.channel_id as organization_id',
                'cc.chatbot_id as chatbot_id',
                'm.conversation_id',
                'ch.name as channel_name',
                'conv.contact_name as nombre_usuario',
                'm.type',
                'm.content',
                'm.created_at',
            ])
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No se encontraron mensajes.');
            return Command::SUCCESS;
        }

        $this->info('Transformando datos...');

        /*
        |--------------------------------------------------------------------------
        | 2. Transformación
        |--------------------------------------------------------------------------
        */

        $lines = [];
        $lines[] = implode(' | ', [
            'ID Organización',
            'ID Agente',
            'ID Conversación',
            'Canal',
            'Nombre Usuario',
            'Mensaje Usuario',
            'Respuesta IA/Agente',
            'Timestamp'
        ]);

        foreach ($rows as $row) {
            $lines[] = implode(' | ', [
                $row->organization_id,
                $row->chatbot_id,
                $row->conversation_id,
                $row->channel_name,
                $row->nombre_usuario ?? '',
                $row->type === 'incoming' ? $row->content : '',
                $row->type === 'outcoming' ? $row->content : '',
                $row->created_at,
            ]);
        }

        $content = implode("\n", $lines);

        echo $content;
        die();

        /*
        |--------------------------------------------------------------------------
        | 3. Google Docs - Autenticación
        |--------------------------------------------------------------------------
        |
        | Asegúrate de:
        | - Tener un Service Account
        | - Compartir la carpeta de Drive con el email del Service Account
        |
        */

        $this->info('Creando documento en Google Docs...');

        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->setScopes([Docs::DOCUMENTS]);

        $docsService = new Docs($client);

        /*
        |--------------------------------------------------------------------------
        | 4. Crear documento
        |--------------------------------------------------------------------------
        */

        $document = new Document([
            'title' => "Export mensajes chatbot {$chatbotId} - channel {$channelId}",
        ]);

        $doc = $docsService->documents->create($document);
        $documentId = $doc->getDocumentId();

        /*
        |--------------------------------------------------------------------------
        | 5. Insertar contenido
        |--------------------------------------------------------------------------
        */

        $requests = [
            new Request([
                'insertText' => [
                    'location' => [
                        'index' => 1,
                    ],
                    'text' => $content,
                ],
            ]),
        ];

        $docsService->documents->batchUpdate(
            $documentId,
            new BatchUpdateDocumentRequest([
                'requests' => $requests,
            ])
        );

        $this->info('Documento creado correctamente.');
        $this->info("Document ID: {$documentId}");

        return Command::SUCCESS;
    }
}
