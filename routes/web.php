<?php

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

Route::get('/', 'ProductController@index')->name('home');

Route::get('/admin', 'AdminController@index')->name('admin');
Route::post('/admin/product/store', 'ProductController@store')->name('admin.product.store');




Route::get('uploadfile','HomeController@uploadFile');
Route::post('uploadfile','HomeController@uploadFilePost');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');