<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = EmailTemplateService::getDefaultTemplates();

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }

        $this->command->info('Email templates seeded: ' . count($templates));
    }
}
