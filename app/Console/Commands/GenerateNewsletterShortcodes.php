<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use App\Models\NewsletterList;
use Illuminate\Console\Command;

class GenerateNewsletterShortcodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:generate-shortcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate shortcodes for existing newsletter lists that don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $listsWithoutShortcodes = NewsletterList::whereNull('shortcode')->get();

        if ($listsWithoutShortcodes->isEmpty()) {
            $this->info('All newsletter lists already have shortcodes.');

            return;
        }

        $this->info("Found {$listsWithoutShortcodes->count()} newsletter lists without shortcodes.");

        foreach ($listsWithoutShortcodes as $list) {
            $shortcode = $this->generateUniqueShortcode();
            $list->update(['shortcode' => $shortcode]);
            $this->line("Generated shortcode '{$shortcode}' for list: {$list->name}");
        }

        $this->info('All newsletter lists now have shortcodes!');
    }

    private function generateUniqueShortcode(): string
    {
        do {
            $shortcode = Str::random(8);
        } while (NewsletterList::where('shortcode', $shortcode)->exists());

        return $shortcode;
    }
}
