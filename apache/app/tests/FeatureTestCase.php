<?php

// -----------------------------------------------------------
//  [*] FeatureTestCase — the base of the in-process tests
//
//  TestCase with every test inside a database transaction
//  that is rolled back afterwards, so each test starts from
//  the schema and seed rows of mysql/init.sql and leaves
//  nothing behind. Auto-increment counters do move on — no
//  test may assume an id.
//
//  Used by:
//    - the Feature suite (Feature/)
// -----------------------------------------------------------


namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;








// -----------------------------------------------------------
// FeatureTestCase
// -----------------------------------------------------------
//
// DatabaseTransactions opens the transaction on the default
// connection before each test and rolls it back after; the
// app runs on that same connection, so its writes go too.
//
// Used by:
//   - the Feature suite (Feature/)
// -----------------------------------------------------------

abstract class FeatureTestCase extends TestCase
{

    // A transaction around every test, rolled back after it
    use DatabaseTransactions;
}
