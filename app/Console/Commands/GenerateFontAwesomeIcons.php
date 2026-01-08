<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;


class GenerateFontAwesomeIcons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fa:generate-icons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate FontAwesome icon list from CDN CSS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jsonPath = base_path('listArrayClassFontAwesome.json');


        if (!File::exists($jsonPath)) {
            $this->error('JSON file not found: ' . $jsonPath);
            return Command::FAILURE;
        }

        $classList = json_decode(File::get($jsonPath), true);

        if (!is_array($classList)) {
            $this->error('Invalid JSON structure');
            return Command::FAILURE;
        }

        $icons = [];

        foreach ($classList as $classString) {
            $classes = explode(' ', $classString);

            $iconClass = $classes[count($classes) - 2]; // fa-0
            $slug = str_replace('fa-', '', $iconClass);
            $label = ucwords(str_replace('-', ' ', $slug));

            $icons[$classString] = $label;
        }

        asort($icons);

        File::put(
            config_path('fontawesome.php'),
            "<?php\n\nreturn " . var_export($icons, true) . ";\n"
        );

        $this->info('FontAwesome icons generated successfully!');
        $this->info('Total icons: ' . count($icons));

        return Command::SUCCESS;
    }
}
