<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

/*
| PENTING:
| RefreshDatabase hanya boleh dipakai oleh test yang memang butuh database
| kosong (test gaya Breeze/Pest: Auth, Profile, Example).
|
| JANGAN terapkan ke seluruh folder 'Feature', karena test di tests/Feature/Api
| ditulis dengan PHPUnit class + trait DatabaseTransactions dan bergantung pada
| data seed. RefreshDatabase menjalankan migrate:fresh per test class sehingga
| akan menghapus seluruh data seed dan membuat test API gagal.
*/
uses(TestCase::class, RefreshDatabase::class)->in('Feature/Auth');
uses(TestCase::class, RefreshDatabase::class)->in('Feature/ProfileTest.php');
uses(TestCase::class, RefreshDatabase::class)->in('Feature/ExampleTest.php');

// Test API memakai DatabaseTransactions (rollback per test) + data seed.
uses(TestCase::class)->in('Feature/Api');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
