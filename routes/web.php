<?php

use App\Http\Controllers\VerifyMemberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('verify-member/{token}/{relation}',[VerifyMemberController::class,"verify_member"])->name('verify_member');
// Route::get('/', function () {
//     return view('welcome');
// });
