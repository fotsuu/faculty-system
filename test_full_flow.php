<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Http\Controllers\RecordUploadController;
use App\Http\Controllers\DashboardController;
use App\Models\User;
use App\Models\Record;
use Illuminate\Http\Request;

$user = User::where('role','faculty')->first();
\Illuminate\Support\Facades\Auth::login($user);

// simulate upload preview using CSV path
$uploadCtrl = new RecordUploadController();
$fakeFile = new \Illuminate\Http\UploadedFile(
    __FILE__, // just dummy
    'dummy.csv',
    'text/csv',
    null,
    true
);
// override real data by calling preview exactly
$headers = ['Student ID','Name'];
$rows = [["2024-1111","John Doe"],["2024-2222","Jane"]];
$preview = ['headers'=>$headers,'rows'=>$rows,'raw_rows'=>$rows,'meta'=>[],'filename'=>'test.csv'];
session(['excel_preview_data'=>$preview]);

echo "before generate records: " . Record::where('user_id',$user->id)->count() . "\n";
$request = Request::create('/analytics/generate','POST',['report_type'=>'comprehensive']);
$request->setUserResolver(fn() => $user);
$request->setLaravelSession(app('session')->driver());
$dash = new DashboardController();
$dash->generateAnalytics($request);
echo "after generate records: " . Record::where('user_id',$user->id)->count() . "\n";

