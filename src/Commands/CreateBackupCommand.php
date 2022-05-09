<?php

namespace Leolopez\Backup\Commands;

use Illuminate\Console\Command;
use Microsoft\Graph\Graph;
use GuzzleHttp\Client;

class CreateBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a backup and uploads it to onedrive.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->composer = app()['composer'];
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dirFile = base_path()."/storage/app/";

        try {
            $command = "mysqldump --user=".env('DB_USERNAME')." --password='".env('DB_PASSWORD')."' --host=".env('DB_HOST')." ".env('DB_DATABASE')." >  $dirFile".env('DB_DATABASE').".sql";
            $returnVar = null;
            $output  = null;

            exec($command, $output, $returnVar);
        } catch (\Throwable $th) {
            $this->error("Backup couldn't be created.");
        }

        $tenantId = config('backup.tenant_id');
        $clientId = config('backup.client_id');
        $clientSecret = config('backup.client_secret');
        $username = config('backup.username');
        $password = config('backup.password');

        $guzzle = new Client();
        $url = "https://login.microsoftonline.com/$tenantId/oauth2/token";

        $response = $guzzle->post($url, [
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'resource' => 'https://graph.microsoft.com/',
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password,
            ],
        ])
        ->getBody()
        ->getContents();

        $user_token = json_decode($response);
        $user_accessToken = $user_token->access_token;

        $graph = new Graph();
        $graph->setAccessToken($user_accessToken);

        $graph->createRequest("PUT", "/me/drive/root/children/".env('DB_DATABASE').".sql/content")
        ->upload($dirFile.env('DB_DATABASE').date('d-m-Y').".sql");

        $this->info('File uploaded to OneDrive successfully.');

        $this->composer->dumpOptimized();

        return 0;
    }
}
