<?php

namespace App\Http\Controllers\Pos\WhatsApp;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\WhatsApp\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppCloudApiService;

class WhatsAppTemplateController extends Controller
{
    public function __construct(protected WhatsAppCloudApiService $client) {}

    public function index()
    {
        return WhatsAppTemplate::query()->where('status', 'APPROVED')->orderBy('name')->get();
    }

    /**
     * Pull the latest approved templates from Meta. Will fail with placeholder
     * credentials until real ones are configured - that's expected.
     *
     * @throws GeneralException
     */
    public function sync()
    {
        $templates = $this->client->fetchTemplates();

        $synced = 0;

        foreach ($templates as $template) {
            WhatsAppTemplate::query()->updateOrCreate(
                ['name' => $template['name'], 'language' => $template['language']],
                [
                    'wa_template_id' => $template['id'] ?? null,
                    'category' => $template['category'] ?? null,
                    'status' => $template['status'] ?? 'pending',
                    'components' => $template['components'] ?? [],
                ]
            );

            $synced++;
        }

        return successResponse("Synced {$synced} template(s) from Meta");
    }
}
